<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class ProductBarcodeResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'barcode'    => $this->barcode,
            'type'       => $this->type?->value,
            'is_primary' => $this->is_primary,
        ];
    }
}
