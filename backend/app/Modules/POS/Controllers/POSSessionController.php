<?php

declare(strict_types=1);

namespace App\Modules\POS\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Inventory\Models\StockLocation;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\POS\Models\POSSession;
use App\Modules\POS\Models\POSTerminal;
use App\Modules\Shops\Models\Shop;
use App\Modules\Shops\Services\ShopAccessService;
use App\Modules\Shops\Services\ShopService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class POSSessionController extends BaseController
{
    public function __construct(
        protected ShopAccessService $shopAccess,
        protected ShopService $shopService
    ) {
    }

    /**
     * GET /api/pos/terminals
     * Optional query: shop_id — filter terminals for that shop.
     */
    public function terminals(Request $request): JsonResponse
    {
        $shopId = $request->query('shop_id');

        // Auto-provision a default store and terminal if none exist for tenant
        if (Schema::hasTable('shops') && ! Shop::query()->exists()) {
            $warehouse = Warehouse::firstOrCreate(
                ['code' => 'WH-MAIN'],
                ['name' => 'Main Warehouse', 'type' => 'own', 'is_active' => true]
            );
            $location = StockLocation::firstOrCreate(
                ['code' => 'WH-MAIN'],
                [
                    'warehouse_id' => $warehouse->id,
                    'name' => 'Main Warehouse',
                    'type' => 'storage',
                    'is_active' => true,
                ]
            );
            $defaultShop = Shop::firstOrCreate(
                ['code' => 'MAIN'],
                [
                    'name' => 'Main Store',
                    'stock_mode' => 'shared_warehouse',
                    'warehouse_id' => $warehouse->id,
                    'stock_location_id' => $location->id,
                    'is_active' => true,
                ]
            );
            $this->shopService->ensureTerminal($defaultShop);
        }

        $allowedShopIds = $this->shopAccess->userShopIds();
        $shopsExist = Schema::hasTable('shops') && Shop::query()->exists();

        $query = POSTerminal::query()->where('is_active', true);

        if ($shopsExist) {
            if ($allowedShopIds === []) {
                $firstShop = Shop::query()->where('is_active', true)->first();
                if ($firstShop) {
                    $allowedShopIds = [(string) $firstShop->id];
                }
            }

            if (! empty($allowedShopIds)) {
                $query->whereIn('shop_id', $allowedShopIds);
            }

            if ($shopId) {
                if (! in_array((string) $shopId, $allowedShopIds, true) && ! empty($allowedShopIds)) {
                    return $this->errorResponse('You do not have access to this shop.', 403);
                }
                $query->where('shop_id', $shopId);
            }
        }

        $terminals = $query->orderBy('name')->get();

        // If no terminals exist for an allowed shop, automatically ensure one exists
        if ($terminals->isEmpty() && $shopsExist) {
            $targetShop = $shopId ? Shop::find($shopId) : (! empty($allowedShopIds) ? Shop::find($allowedShopIds[0]) : Shop::query()->first());
            if ($targetShop) {
                $terminal = $this->shopService->ensureTerminal($targetShop);
                $terminals = collect([$terminal]);
            }
        }

        // Legacy fallback only when no shops table exists
        if ($terminals->isEmpty() && ! $shopsExist) {
            $locationId = StockLocation::query()->where('is_active', true)->value('id')
                ?? StockLocation::query()->value('id');

            if ($locationId) {
                $terminal = POSTerminal::create([
                    'name' => 'Front Counter',
                    'location_id' => $locationId,
                    'is_active' => true,
                ]);
                $terminals = collect([$terminal]);
            }
        }

        return $this->successResponse($terminals);
    }

    /**
     * GET /api/pos/sessions/current
     */
    public function current(Request $request): JsonResponse
    {
        $shopId = $request->query('shop_id');
        $query = POSSession::with(['terminal', 'shop'])
            ->where('cashier_id', auth()->id())
            ->where('status', 'open');

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $session = $query->latest('opened_at')->first();

        // Fallback to any open session for this cashier if not found for specific shop
        if (! $session && $shopId) {
            $session = POSSession::with(['terminal', 'shop'])
                ->where('cashier_id', auth()->id())
                ->where('status', 'open')
                ->latest('opened_at')
                ->first();
        }

        if ($session && ! $session->shop_id && Schema::hasTable('shops')) {
            $shop = Shop::query()->where('is_active', true)->first();
            if ($shop) {
                $session->update(['shop_id' => $shop->id]);
                $session->refresh()->load(['terminal', 'shop']);
            }
        }

        return $this->successResponse($session);
    }

    /**
     * POST /api/pos/sessions/open
     */
    public function open(Request $request): JsonResponse
    {
        $data = $request->validate([
            'terminal_id' => ['required', 'uuid', 'exists:pos_terminals,id'],
            'shop_id' => ['nullable', 'uuid', 'exists:shops,id'],
            'opening_cash_cents' => ['nullable', 'integer', 'min:0'],
        ]);

        $terminal = POSTerminal::findOrFail($data['terminal_id']);
        $shopsExist = Schema::hasTable('shops') && Shop::query()->exists();
        $shopId = $data['shop_id'] ?? $terminal->shop_id;

        if ($shopsExist) {
            if (! $shopId) {
                $shopId = Shop::query()->where('is_active', true)->value('id');
            }
            if ($shopId) {
                $this->shopAccess->assertCanAccessShop($shopId);

                $shop = Shop::findOrFail($shopId);
                if (! $shop->is_active) {
                    return $this->errorResponse('Selected shop is inactive.', 422);
                }

                // Keep terminal location aligned with shop
                if ((string) $terminal->location_id !== (string) $shop->stock_location_id) {
                    $terminal->update(['location_id' => $shop->stock_location_id, 'shop_id' => $shop->id]);
                }
            }
        }

        // Return existing open session for this cashier in this shop if one is already active
        $existingQuery = POSSession::query()
            ->where('cashier_id', auth()->id())
            ->where('status', 'open');

        if ($shopId) {
            $existingQuery->where(function ($q) use ($shopId) {
                $q->where('shop_id', $shopId)->orWhereNull('shop_id');
            });
        }

        $existing = $existingQuery->latest('opened_at')->first();

        if ($existing) {
            if ($shopId && ! $existing->shop_id) {
                $existing->update(['shop_id' => $shopId]);
            }
            return $this->successResponse($existing->fresh()->load(['terminal', 'shop']));
        }

        // Create new session for this cashier.
        // Multiple cashiers/devices can have active sessions simultaneously on the same shop or terminal.
        $session = POSSession::create([
            'terminal_id' => $data['terminal_id'],
            'shop_id' => $shopId,
            'cashier_id' => auth()->id(),
            'opened_at' => now(),
            'opening_cash_cents' => (int) ($data['opening_cash_cents'] ?? 0),
            'expected_cash_cents' => (int) ($data['opening_cash_cents'] ?? 0),
            'status' => 'open',
        ]);

        return $this->createdResponse($session->load(['terminal', 'shop']));
    }

    /**
     * POST /api/pos/sessions/{session}/close
     */
    public function close(Request $request, string $session): JsonResponse
    {
        $data = $request->validate([
            'closing_cash_cents' => ['required', 'integer', 'min:0'],
        ]);

        $posSession = POSSession::findOrFail($session);

        if ($posSession->status !== 'open') {
            return $this->errorResponse('Session is already closed.', 422);
        }

        if ((string) $posSession->cashier_id !== (string) auth()->id()) {
            return $this->errorResponse('You can only close your own session.', 403);
        }

        $posSession->update([
            'closed_at' => now(),
            'closing_cash_cents' => $data['closing_cash_cents'],
            'cash_variance_cents' => $data['closing_cash_cents'] - (int) $posSession->expected_cash_cents,
            'status' => 'closed',
        ]);

        return $this->successResponse($posSession->fresh('terminal'));
    }
}
