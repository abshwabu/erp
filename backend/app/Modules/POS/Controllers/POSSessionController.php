<?php

declare(strict_types=1);

namespace App\Modules\POS\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Inventory\Models\StockLocation;
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
        $allowedShopIds = $this->shopAccess->userShopIds();
        $shopsExist = Schema::hasTable('shops') && Shop::query()->exists();

        $query = POSTerminal::query()->where('is_active', true);

        if ($shopsExist) {
            if ($allowedShopIds === []) {
                return $this->successResponse([]);
            }

            $query->whereIn('shop_id', $allowedShopIds);

            if ($shopId) {
                if (! in_array((string) $shopId, $allowedShopIds, true)) {
                    return $this->errorResponse('You do not have access to this shop.', 403);
                }
                $query->where('shop_id', $shopId);
            }
        }

        $terminals = $query->orderBy('name')->get();

        // Legacy fallback only when no shops exist yet
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

        // Ensure each allowed shop has a terminal when filtering by shop
        if ($shopsExist && $shopId && $terminals->isEmpty()) {
            $shop = $this->shopAccess->assertCanAccessShop((string) $shopId);
            $terminal = $this->shopService->ensureTerminal($shop);
            $terminals = collect([$terminal]);
        }

        return $this->successResponse($terminals);
    }

    /**
     * GET /api/pos/sessions/current
     */
    public function current(): JsonResponse
    {
        $session = POSSession::with('terminal')
            ->where('cashier_id', auth()->id())
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

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

        $existing = POSSession::query()
            ->where('cashier_id', auth()->id())
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return $this->successResponse($existing->load('terminal'));
        }

        $terminal = POSTerminal::findOrFail($data['terminal_id']);
        $shopsExist = Schema::hasTable('shops') && Shop::query()->exists();

        $shopId = $data['shop_id'] ?? $terminal->shop_id;

        if ($shopsExist) {
            if (! $shopId) {
                return $this->errorResponse('shop_id is required when shops are configured.', 422);
            }
            $this->shopAccess->assertCanAccessShop($shopId);

            if ($terminal->shop_id && (string) $terminal->shop_id !== (string) $shopId) {
                return $this->errorResponse('Terminal does not belong to the selected shop.', 422);
            }

            $shop = Shop::findOrFail($shopId);
            if (! $shop->is_active) {
                return $this->errorResponse('Selected shop is inactive.', 422);
            }

            // Keep terminal location aligned with shop
            if ((string) $terminal->location_id !== (string) $shop->stock_location_id) {
                $terminal->update(['location_id' => $shop->stock_location_id, 'shop_id' => $shop->id]);
            }
        }

        $openOnTerminal = POSSession::query()
            ->where('terminal_id', $data['terminal_id'])
            ->where('status', 'open')
            ->exists();

        if ($openOnTerminal) {
            return $this->errorResponse('This terminal already has an open session.', 422);
        }

        $session = POSSession::create([
            'terminal_id' => $data['terminal_id'],
            'shop_id' => $shopId,
            'cashier_id' => auth()->id(),
            'opened_at' => now(),
            'opening_cash_cents' => (int) ($data['opening_cash_cents'] ?? 0),
            'expected_cash_cents' => (int) ($data['opening_cash_cents'] ?? 0),
            'status' => 'open',
        ]);

        return $this->createdResponse($session->load('terminal'));
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
