<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;

class PaginatedCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'links' => $this->getLinks(),
            'meta' => $this->getMeta(),
        ];
    }

    /**
     * Get the pagination links for the response.
     *
     * @return array<string, mixed>
     */
    protected function getLinks(): array
    {
        if ($this->resource instanceof AbstractCursorPaginator) {
            return [
                'next' => $this->resource->nextPageUrl(),
                'prev' => $this->resource->previousPageUrl(),
            ];
        }

        if ($this->resource instanceof AbstractPaginator) {
            return [
                'first' => $this->resource->url(1),
                'last' => $this->resource->url($this->resource->lastPage()),
                'prev' => $this->resource->previousPageUrl(),
                'next' => $this->resource->nextPageUrl(),
            ];
        }

        return [];
    }

    /**
     * Get the meta data for the response.
     *
     * @return array<string, mixed>
     */
    protected function getMeta(): array
    {
        if ($this->resource instanceof AbstractCursorPaginator) {
            return [
                'per_page' => $this->resource->perPage(),
                'has_more' => $this->resource->hasMorePages(),
            ];
        }

        if ($this->resource instanceof AbstractPaginator) {
            return [
                'current_page' => $this->resource->currentPage(),
                'from' => $this->resource->firstItem(),
                'last_page' => $this->resource->lastPage(),
                'path' => $this->resource->path(),
                'per_page' => $this->resource->perPage(),
                'to' => $this->resource->lastItem(),
                'total' => $this->resource->total(),
            ];
        }

        return [];
    }
}
