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
            'trial_ends_at',
            'settings',
            'created_at',
            'updated_at',
        ]);
    }

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'trial_ends_at' => 'datetime',
        ];
    }

    public function onTrial(): bool
    {
        if ($this->status === 'trial_expired') {
            return false;
        }

        if ($this->status === 'trial') {
            return !$this->trial_ends_at || $this->trial_ends_at->isFuture();
        }

        return (bool) ($this->trial_ends_at && $this->trial_ends_at->isFuture());
    }

    public function trialExpired(): bool
    {
        if ($this->status === 'trial_expired') {
            return true;
        }

        if ($this->status === 'trial' && $this->trial_ends_at && $this->trial_ends_at->isPast()) {
            return true;
        }

        return false;
    }

    public function daysLeftInTrial(): int
    {
        if (!$this->trial_ends_at || $this->trial_ends_at->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->trial_ends_at);
    }

    public function needsPlanSelection(): bool
    {
        // If demo or active paid plan
        if ($this->slug === 'demo') {
            return false;
        }

        return $this->trialExpired();
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

    /**
     * Check if this tenant's active plan allows access to the specified module.
     */
    public function hasModuleAccess(string $module): bool
    {
        if (!$this->plan_id) {
            return true;
        }

        $plan = $this->plan ?? Plan::find($this->plan_id);
        if (!$plan) {
            return true;
        }

        return $plan->hasModule($module);
    }
}
