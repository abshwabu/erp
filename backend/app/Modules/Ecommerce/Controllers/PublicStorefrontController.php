<?php

declare(strict_types=1);

namespace App\Modules\Ecommerce\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Core\Models\Tenant;
use App\Modules\Ecommerce\Models\EcommerceChannel;
use App\Modules\Ecommerce\Models\EcommerceOrder;
use App\Modules\Ecommerce\Models\Storefront;
use App\Modules\Inventory\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicStorefrontController extends BaseController
{
    /**
     * Resolve and initialize the tenant that owns the requested storefront slug.
     */
    private function resolveStorefrontTenant(string $slug): ?Storefront
    {
        $isPreview = request()->boolean('preview') || request()->header('X-Tenant-ID') !== null;

        // 1. If tenancy is already initialized, find storefront in current tenant context
        if (tenancy()->initialized) {
            return Storefront::where('slug', $slug)
                ->when(! $isPreview, fn ($q) => $q->where('is_published', true))
                ->first();
        }

        // 2. Try explicit tenant identifier from header or query param
        $tenantId = request()->header('X-Tenant-ID') ?? request()->query('tenant');
        if ($tenantId) {
            $tenant = Tenant::query()
                ->where(function ($q) use ($tenantId) {
                    if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $tenantId)) {
                        $q->where('id', $tenantId);
                    }
                    $q->orWhere('slug', $tenantId);
                })
                ->first();

            if ($tenant) {
                tenancy()->initialize($tenant);
                return Storefront::where('slug', $slug)
                    ->when(! $isPreview, fn ($q) => $q->where('is_published', true))
                    ->first();
            }
        }

        // 3. Scan active tenants to find the owner of this storefront slug
        $tenants = Tenant::where('status', 'active')->get();
        foreach ($tenants as $t) {
            try {
                $store = $t->run(function () use ($slug, $isPreview) {
                    return Storefront::where('slug', $slug)
                        ->when(! $isPreview, fn ($q) => $q->where('is_published', true))
                        ->first();
                });

                if ($store) {
                    tenancy()->initialize($t);
                    return $store;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    public function getStore(string $slug): JsonResponse
    {
        $storefront = $this->resolveStorefrontTenant($slug);

        if (! $storefront) {
            return $this->errorResponse('Storefront not found or is not published yet.', 404);
        }

        $isPreview = request()->boolean('preview') || request()->header('X-Tenant-ID') !== null;
        $storefront->load(['pages' => fn ($q) => $q->when(! $isPreview, fn ($pq) => $pq->where('is_published', true))->orderBy('order')]);

        // Fetch products for storefront catalog
        $products = Product::where('status', 'active')
            ->select(['id', 'name', 'sku', 'selling_price', 'cost_price', 'category_id', 'type', 'description'])
            ->with('category:id,name')
            ->orderBy('name')
            ->limit(50)
            ->get();

        return $this->successResponse([
            'storefront' => $storefront,
            'products'   => $products,
        ]);
    }

    public function checkout(Request $request, string $slug): JsonResponse
    {
        $storefront = $this->resolveStorefrontTenant($slug);

        if (! $storefront) {
            return $this->errorResponse('Storefront not found or is not published.', 404);
        }

        $data = $request->validate([
            'customer_name'      => ['required', 'string', 'max:255'],
            'customer_email'     => ['required', 'email', 'max:255'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid'],
            'items.*.name'       => ['required', 'string'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'items.*.price_cents'=> ['required', 'integer', 'min:0'],
            'shipping_address'   => ['nullable', 'string'],
            'notes'              => ['nullable', 'string'],
        ]);

        // Find or create default Storefront channel
        $channel = EcommerceChannel::firstOrCreate(
            ['name' => 'Storefront: ' . $storefront->name],
            ['platform' => 'custom', 'is_active' => true]
        );

        $totalCents = 0;
        foreach ($data['items'] as $item) {
            $totalCents += $item['price_cents'] * $item['quantity'];
        }

        $orderNumber = '#WEB-' . strtoupper(Str::random(6));
        $externalId  = 'STORE-' . (string) Str::uuid();

        $order = EcommerceOrder::create([
            'channel_id'         => $channel->id,
            'external_order_id'  => $externalId,
            'order_number'       => $orderNumber,
            'customer_name'      => $data['customer_name'],
            'customer_email'     => $data['customer_email'],
            'total_cents'        => $totalCents,
            'currency'           => 'USD',
            'payment_status'     => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'items'              => $data['items'],
        ]);

        return $this->createdResponse([
            'order_number' => $order->order_number,
            'total_cents'  => $order->total_cents,
            'message'      => 'Thank you for your order! Your purchase was received successfully.',
            'order'        => $order,
        ]);
    }
}
