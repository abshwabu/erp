<?php

declare(strict_types=1);

namespace App\Modules\Shops\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ShopUser extends Pivot
{
    use HasUuids;

    protected $table = 'shop_user';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'shop_id',
        'user_id',
        'role',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
