<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class ProductVariantResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'sku'                 => $this->sku,
            'name'                => $this->name,
            'cost_price'          => $this->cost_price,
            'selling_price'       => $this->selling_price,
            'attribute_value_ids' => $this->attribute_value_ids,
            'is_active'           => $this->is_active,
            'created_at'          => $this->created_at?->toIso8601String(),
        ];
    }
}
