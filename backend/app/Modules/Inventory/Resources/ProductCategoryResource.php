<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class ProductCategoryResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'is_active'   => $this->is_active,
            'parent_id'   => $this->parent_id,
            'children'    => ProductCategoryResource::collection($this->whenLoaded('children')),
            'created_at'  => $this->created_at?->toIso8601String(),
        ];
    }
}
