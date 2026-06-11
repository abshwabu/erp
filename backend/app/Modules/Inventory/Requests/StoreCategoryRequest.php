<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => [
                'nullable',
                'string',
                'max:255',
                // Uniqueness scoped to the tenant's own DB connection
                'unique:product_categories,slug' . ($categoryId ? ",{$categoryId}" : ''),
            ],
            'description' => ['nullable', 'string'],
            'parent_id'   => ['nullable', 'uuid', 'exists:product_categories,id'],
            'is_active'   => ['boolean'],
        ];
    }
}
