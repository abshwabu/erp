<?php

declare(strict_types=1);

namespace App\Modules\Ecommerce\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Ecommerce\Models\EcommerceChannel;
use App\Modules\Ecommerce\Models\EcommerceOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChannelController extends BaseController
{
    public function index(): JsonResponse
    {
        $channels = EcommerceChannel::withCount('orders')
            ->orderByDesc('created_at')
            ->get();

        return $this->successResponse($channels);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'platform'       => ['required', 'in:shopify,woocommerce,magento,custom'],
            'store_url'      => ['nullable', 'url', 'max:255'],
            'api_key'        => ['nullable', 'string', 'max:255'],
            'api_secret'     => ['nullable', 'string', 'max:255'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'is_active'      => ['sometimes', 'boolean'],
        ]);

        $channel = EcommerceChannel::create($data);

        return $this->createdResponse($channel);
    }

    public function show(string $id): JsonResponse
    {
        $channel = EcommerceChannel::with('orders')->findOrFail($id);

        return $this->successResponse($channel);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $channel = EcommerceChannel::findOrFail($id);

        $data = $request->validate([
            'name'      => ['sometimes', 'string', 'max:255'],
            'store_url' => ['nullable', 'url', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $channel->update($data);

        return $this->successResponse($channel);
    }

    public function sync(string $id): JsonResponse
    {
        $channel = EcommerceChannel::findOrFail($id);

        $channel->update(['last_sync_at' => now()]);

        return $this->successResponse([
            'message'      => 'Sync initiated successfully',
            'last_sync_at' => $channel->last_sync_at,
        ]);
    }

    public function orders(Request $request, ?string $channelId = null): JsonResponse
    {
        $query = EcommerceOrder::with('channel:id,name,platform')
            ->orderByDesc('created_at');

        if ($channelId) {
            $query->where('channel_id', $channelId);
        }

        if ($request->filled('status')) {
            $query->where('fulfillment_status', $request->input('status'));
        }

        $orders = $query->paginate(25);

        return $this->successResponse($orders);
    }

    public function syncOrder(Request $request, string $id): JsonResponse
    {
        $channel = EcommerceChannel::findOrFail($id);

        $data = $request->validate([
            'external_order_id'  => ['required', 'string', 'max:100'],
            'order_number'       => ['required', 'string', 'max:100'],
            'customer_name'      => ['required', 'string', 'max:255'],
            'customer_email'     => ['nullable', 'email', 'max:255'],
            'total_cents'        => ['required', 'integer', 'min:0'],
            'currency'           => ['sometimes', 'string', 'size:3'],
            'payment_status'     => ['sometimes', 'in:pending,paid,refunded'],
            'fulfillment_status' => ['sometimes', 'in:unfulfilled,fulfilled,cancelled'],
            'items'              => ['nullable', 'array'],
        ]);

        $order = EcommerceOrder::updateOrCreate(
            [
                'channel_id'        => $channel->id,
                'external_order_id' => $data['external_order_id'],
            ],
            array_merge($data, ['channel_id' => $channel->id])
        );

        $channel->update(['last_sync_at' => now()]);

        return $this->createdResponse($order);
    }

    public function fulfillOrder(Request $request, string $id): JsonResponse
    {
        $order = EcommerceOrder::with('channel:id,name')->findOrFail($id);

        $data = $request->validate([
            'fulfillment_status' => ['required', 'in:unfulfilled,shipped,fulfilled,cancelled'],
            'tracking_number'    => ['nullable', 'string', 'max:255'],
            'shipping_carrier'   => ['nullable', 'string', 'max:255'],
            'notes'              => ['nullable', 'string', 'max:1000'],
        ]);

        $order->update($data);

        return $this->successResponse($order->fresh('channel:id,name'));
    }

    public function destroy(string $id): JsonResponse
    {
        $channel = EcommerceChannel::findOrFail($id);
        $channel->delete();

        return $this->noContentResponse();
    }
}
