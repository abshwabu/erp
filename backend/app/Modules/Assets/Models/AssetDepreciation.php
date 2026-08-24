<?php

declare(strict_types=1);

namespace App\Modules\Assets\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDepreciation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'asset_depreciations';

    protected $fillable = [
        'asset_id',
        'fiscal_year',
        'depreciation_amount_cents',
        'accumulated_depreciation_cents',
        'book_value_cents',
    ];

    protected $casts = [
        'fiscal_year'                    => 'integer',
        'depreciation_amount_cents'      => 'integer',
        'accumulated_depreciation_cents' => 'integer',
        'book_value_cents'               => 'integer',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}
