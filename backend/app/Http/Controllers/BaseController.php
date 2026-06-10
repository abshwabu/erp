<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\PaginatedCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Return a success JSON response.
     *
     * @param mixed $data
     * @param int $status
     * @return JsonResponse
     */
    protected function successResponse($data, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
        ], $status);
    }

    /**
     * Return a paginated JSON response.
     *
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation $query
     * @param string $resourceClass
     * @param int|null $perPage
     * @return PaginatedCollection
     */
    protected function paginatedResponse($query, string $resourceClass, ?int $perPage = null): PaginatedCollection
    {
        $perPage = $perPage ?? config('api.items_per_page', 25);
        $maxPerPage = config('api.max_items_per_page', 100);

        // Allow client to request specific per_page within bounds
        $requestPerPage = request()->input('per_page');
        if ($requestPerPage && is_numeric($requestPerPage)) {
            $perPage = min((int) $requestPerPage, $maxPerPage);
        }

        $paginator = config('api.use_cursor_pagination', false)
            ? $query->cursorPaginate($perPage)
            : $query->paginate($perPage);

        $paginator->appends(request()->query());

        // Transform the models into the specified resource class
        $paginator->setCollection($paginator->getCollection()->map(function ($item) use ($resourceClass) {
            return new $resourceClass($item);
        }));

        return new PaginatedCollection($paginator);
    }

    /**
     * Return a created (201) JSON response.
     *
     * @param mixed $resource
     * @return JsonResponse
     */
    protected function createdResponse($resource): JsonResponse
    {
        return response()->json([
            'data' => $resource,
        ], 201);
    }

    /**
     * Return a no content (204) JSON response.
     *
     * @return JsonResponse
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], $status);
    }
}
