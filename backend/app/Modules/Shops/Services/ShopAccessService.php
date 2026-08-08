<?php

declare(strict_types=1);

namespace App\Modules\Shops\Services;

use App\Modules\Core\Enums\Permission;
use App\Modules\Core\Models\User;
use App\Modules\Shops\Models\Shop;
use App\Modules\Shops\Models\ShopUser;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShopAccessService
{
    public function canManageShops(?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        return $user?->can(Permission::ShopsManage->value) ?? false;
    }

    /**
     * Shop IDs the user may access (all when shops.manage).
     *
     * @return list<string>
     */
    public function userShopIds(?User $user = null): array
    {
        $user = $user ?? auth()->user();
        if (! $user) {
            return [];
        }

        if ($this->canManageShops($user)) {
            return Shop::query()->pluck('id')->map(fn ($id) => (string) $id)->all();
        }

        return ShopUser::query()
            ->where('user_id', $user->id)
            ->pluck('shop_id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    public function shopsForUser(?User $user = null, bool $activeOnly = true): Collection
    {
        $user = $user ?? auth()->user();
        $query = Shop::query()->with(['warehouse', 'stockLocation', 'users']);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        if (! $this->canManageShops($user)) {
            $ids = $this->userShopIds($user);
            if ($ids === []) {
                return collect();
            }
            $query->whereIn('id', $ids);
        }

        return $query->orderBy('name')->get();
    }

    public function assertCanAccessShop(string $shopId, ?User $user = null): Shop
    {
        $user = $user ?? auth()->user();
        $shop = Shop::find($shopId);

        if (! $shop) {
            throw new NotFoundHttpException('Shop not found.');
        }

        if ($this->canManageShops($user)) {
            return $shop;
        }

        $assigned = ShopUser::query()
            ->where('shop_id', $shop->id)
            ->where('user_id', $user?->id)
            ->exists();

        if (! $assigned) {
            throw new AccessDeniedHttpException('You do not have access to this shop.');
        }

        return $shop;
    }

    public function assertCanManageShops(?User $user = null): void
    {
        if (! $this->canManageShops($user)) {
            throw new AccessDeniedHttpException('You do not have permission to manage shops.');
        }
    }
}
