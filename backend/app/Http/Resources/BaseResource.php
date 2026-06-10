<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseResource extends JsonResource
{
    /**
     * Additional meta data.
     *
     * @var array<string, mixed>
     */
    protected array $metaData = [];

    /**
     * Additional links data.
     *
     * @var array<string, mixed>
     */
    protected array $linksData = [];

    /**
     * Add meta data to the resource response.
     *
     * @param array<string, mixed> $meta
     * @return $this
     */
    public function withMeta(array $meta): static
    {
        $this->metaData = array_merge($this->metaData, $meta);
        return $this;
    }

    /**
     * Add links data to the resource response.
     *
     * @param array<string, mixed> $links
     * @return $this
     */
    public function withLinks(array $links): static
    {
        $this->linksData = array_merge($this->linksData, $links);
        return $this;
    }

    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        $with = [];

        if (!empty($this->metaData)) {
            $with['meta'] = $this->metaData;
        }

        if (!empty($this->linksData)) {
            $with['links'] = $this->linksData;
        }

        return $with;
    }

    /**
     * Transform the resource into an array.
     * By default we just return what the parent does, but ensure it's wrapped
     * if not already handled by the collection.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Enforce the data envelope for single resources if not already present
        // However, JsonResource already wraps in 'data' if `static::$wrap = 'data'` (which is default).
        return parent::toArray($request);
    }
}
