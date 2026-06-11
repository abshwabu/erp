<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait QueryFilter
{
    /**
     * Apply default request filters to the query builder.
     */
    public function scopeFilter(Builder $builder): Builder
    {
        return $this->scopeFilterByRequest($builder, request());
    }

    /**
     * Apply request filters to the query builder.
     *
     * @param Builder $builder
     * @param Request $request
     * @return Builder
     */
    public function scopeFilterByRequest(Builder $builder, Request $request): Builder
    {
        $this->applyFilters($builder, $request->input('filter', []));
        $this->applySorts($builder, $request->input('sort'));
        $this->applyIncludes($builder, $request->input('include'));
        $this->applyFields($builder, $request->input('fields', []));

        return $builder;
    }

    protected function applyFilters(Builder $builder, array $filters): void
    {
        foreach ($filters as $field => $value) {
            if (empty($value)) continue;

            if (str_ends_with($field, '_after')) {
                $actualField = str_replace('_after', '', $field);
                $builder->where($actualField, '>=', $value);
            } elseif (str_ends_with($field, '_before')) {
                $actualField = str_replace('_before', '', $field);
                $builder->where($actualField, '<=', $value);
            } elseif (is_array($value)) {
                $builder->whereIn($field, $value);
            } else {
                $builder->where($field, $value);
            }
        }
    }

    protected function applySorts(Builder $builder, ?string $sorts): void
    {
        if (empty($sorts)) return;

        $sortFields = explode(',', $sorts);

        foreach ($sortFields as $sortField) {
            $direction = 'asc';

            if (str_starts_with($sortField, '-')) {
                $direction = 'desc';
                $sortField = substr($sortField, 1);
            }

            $builder->orderBy($sortField, $direction);
        }
    }

    protected function applyIncludes(Builder $builder, ?string $includes): void
    {
        if (empty($includes)) return;

        $relations = explode(',', $includes);
        $builder->with($relations);
    }

    protected function applyFields(Builder $builder, array $fields): void
    {
        if (empty($fields)) return;

        $tableName = $builder->getModel()->getTable();

        if (isset($fields[$tableName])) {
            $selects = explode(',', $fields[$tableName]);
            // Ensure ID is always selected if other fields are specified
            if (!in_array('id', $selects)) {
                $selects[] = 'id';
            }
            $builder->select($selects);
        }
    }
}
