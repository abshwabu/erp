<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price_monthly' => 'decimal:2',
            'price_annually' => 'decimal:2',
        ];
    }

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }
}
