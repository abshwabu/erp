<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;

    protected $guarded = [];

    public static function getCustomColumns(): array
    {
        return array_merge(parent::getCustomColumns(), [
            'name',
            'slug',
            'custom_domain',
            'plan_id',
            'status',
            'settings',
            'created_at',
            'updated_at',
        ]);
    }

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user');
    }

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class, 'plan_id', 'plan_id');
    }
}
