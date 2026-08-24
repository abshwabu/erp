<?php

declare(strict_types=1);

namespace App\Modules\Assets\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'assets';

    protected $fillable = [
        'asset_tag',
        'name',
        'category',
        'serial_number',
        'purchase_date',
        'purchase_cost_cents',
        'salvage_value_cents',
        'useful_life_years',
        'depreciation_method',
        'status',
        'assigned_to',
        'notes',
    ];

    protected $casts = [
        'purchase_cost_cents' => 'integer',
        'salvage_value_cents' => 'integer',
        'useful_life_years'   => 'integer',
        'purchase_date'       => 'date',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(AssetDepreciation::class, 'asset_id');
    }

    public static function nextTag(): string
    {
        $last = static::query()->orderByDesc('asset_tag')->value('asset_tag');
        $seq  = $last ? ((int) str_replace('AST-', '', $last)) + 1 : 1;

        return 'AST-' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
