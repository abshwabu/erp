<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Requests;

use App\Modules\Inventory\Enums\ProductStatus;
use App\Modules\Inventory\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'sku'                  => ['sometimes', 'string', 'max:100', "unique:products,sku,{$productId}"],
            'name'                 => ['sometimes', 'string', 'max:255'],
            'description'          => ['nullable', 'string'],
            'type'                 => ['sometimes', Rule::enum(ProductType::class)],
            'status'               => ['sometimes', Rule::enum(ProductStatus::class)],
            'category_id'          => ['nullable', 'uuid', 'exists:product_categories,id'],
            'cost_price'           => ['sometimes', 'integer', 'min:0'],
            'selling_price'        => ['sometimes', 'integer', 'min:0'],
            'has_variants'         => ['boolean'],
            'track_serial_numbers' => ['boolean'],
            'track_lots'           => ['boolean'],

            // Variants
            'variants'                       => ['nullable', 'array'],
            'variants.*.id'                  => ['nullable', 'uuid'],
            'variants.*.sku'                 => ['required_with:variants', 'string', 'max:100'],
            'variants.*.name'                => ['required_with:variants', 'string', 'max:255'],
            'variants.*.cost_price'          => ['nullable', 'integer', 'min:0'],
            'variants.*.selling_price'       => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.attribute_value_ids' => ['nullable', 'array'],
            'variants.*.is_active'           => ['boolean'],
            'variants.*.stock'               => ['nullable', 'integer', 'min:0'],
        ];
    }
}
