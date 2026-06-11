<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Requests;

use App\Modules\Inventory\Enums\ProductStatus;
use App\Modules\Inventory\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku'                  => ['required', 'string', 'max:100', 'unique:products,sku'],
            'name'                 => ['required', 'string', 'max:255'],
            'description'          => ['nullable', 'string'],
            'type'                 => ['required', Rule::enum(ProductType::class)],
            'status'               => ['sometimes', Rule::enum(ProductStatus::class)],
            'category_id'          => ['nullable', 'uuid', 'exists:product_categories,id'],
            'cost_price'           => ['required', 'integer', 'min:0'],
            'selling_price'        => ['required', 'integer', 'min:0'],
            'has_variants'         => ['boolean'],
            'track_serial_numbers' => ['boolean'],
            'track_lots'           => ['boolean'],

            // Barcodes
            'barcodes'             => ['nullable', 'array'],
            'barcodes.*.barcode'   => ['required_with:barcodes', 'string', 'max:100'],
            'barcodes.*.type'      => ['required_with:barcodes', Rule::enum(\App\Modules\Inventory\Enums\BarcodeType::class)],
            'barcodes.*.is_primary' => ['boolean'],
        ];
    }
}
