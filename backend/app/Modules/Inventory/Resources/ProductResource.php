<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class ProductResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'sku'                  => $this->sku,
            'name'                 => $this->name,
            'description'          => $this->description,
            'type'                 => $this->type?->value ?? $this->type,
            'status'               => $this->status?->value ?? $this->status,
            'cost_price'           => $this->cost_price,
            'selling_price'        => $this->selling_price,
            'has_variants'         => $this->has_variants,
            'track_serial_numbers' => $this->track_serial_numbers,
            'track_lots'           => $this->track_lots,
            'primary_image_url'    => $this->primary_image_url,
            'images'               => $this->when(
                $this->relationLoaded('images'),
                fn () => $this->images->map(fn ($img) => [
                    'id' => $img->id,
                    'path' => $img->path,
                    'url' => $img->url,
                    'is_primary' => (bool) $img->is_primary,
                    'sort_order' => (int) $img->sort_order,
                ])
            ),
            'category'             => new ProductCategoryResource($this->whenLoaded('category')),
            'variants'             => ProductVariantResource::collection($this->whenLoaded('variants')),
            'barcodes'             => ProductBarcodeResource::collection($this->whenLoaded('barcodes')),
            'quantity_on_hand'   => $this->relationLoaded('stockLevels')
                ? (int) $this->stockLevels->sum('quantity_on_hand')
                : 0,
            'quantity_committed' => $this->relationLoaded('stockLevels')
                ? (int) $this->stockLevels->sum('quantity_committed')
                : 0,
            'quantity_on_order'  => $this->relationLoaded('stockLevels')
                ? (int) $this->stockLevels->sum('quantity_on_order')
                : 0,
            'available_quantity'   => $this->relationLoaded('stockLevels')
                ? (int) ($this->stockLevels->sum('quantity_on_hand') - $this->stockLevels->sum('quantity_committed'))
                : 0,
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }
}
