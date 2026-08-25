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
            'sku'                  => ['nullable', 'string', 'max:100', 'unique:products,sku'],
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
            'initial_stock'        => ['nullable', 'integer', 'min:0'],
            'location_id'          => ['nullable', 'uuid', 'exists:stock_locations,id'],

            // Barcodes
            'barcodes'             => ['nullable', 'array'],
            'barcodes.*.barcode'   => ['required_with:barcodes', 'string', 'max:100'],
            'barcodes.*.type'      => ['required_with:barcodes', Rule::enum(\App\Modules\Inventory\Enums\BarcodeType::class)],
            'barcodes.*.is_primary' => ['boolean'],

            // Variants
            'variants'                       => ['nullable', 'array'],
            'variants.*.sku'                 => ['required_with:variants', 'string', 'max:100'],
            'variants.*.name'                => ['required_with:variants', 'string', 'max:255'],
            'variants.*.cost_price'          => ['nullable', 'integer', 'min:0'],
            'variants.*.selling_price'       => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.attribute_value_ids' => ['nullable', 'array'],
            'variants.*.is_active'           => ['boolean'],
            'variants.*.stock'               => ['nullable', 'integer', 'min:0'],

            // Images
            'images'                         => ['nullable', 'array'],
            'images.*.path'                  => ['nullable', 'string'],
            'images.*.url'                   => ['nullable', 'string'],
            'images.*.is_primary'            => ['nullable', 'boolean'],
            'images.*.sort_order'            => ['nullable', 'integer'],
            'primary_image_url'              => ['nullable', 'string'],
        ];
    }
}
