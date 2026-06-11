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
            'type'                 => $this->type?->value,
            'status'               => $this->status?->value,
            'cost_price'           => $this->cost_price,
            'selling_price'        => $this->selling_price,
            'has_variants'         => $this->has_variants,
            'track_serial_numbers' => $this->track_serial_numbers,
            'track_lots'           => $this->track_lots,
            'primary_image_url'    => $this->when(
                $this->relationLoaded('images'),
                fn () => $this->primary_image_url
            ),
            'category'             => new ProductCategoryResource($this->whenLoaded('category')),
            'variants'             => ProductVariantResource::collection($this->whenLoaded('variants')),
            'barcodes'             => ProductBarcodeResource::collection($this->whenLoaded('barcodes')),
            // available_quantity stubbed until StockLocation module is built
            'available_quantity'   => 0,
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }
}
