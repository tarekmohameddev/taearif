<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

trait HasApiPaginationResponse
{
    /**
     * Resolve per-page with caps.
     */
    protected function pageSize(Request $request, int $default = 15, ?int $max = null): int
    {
        $max ??= (int) (config('api.pagination.max', 100)); // optional config cap
        $perPage = (int) $request->query('per_page', $default);
        return max(1, min($perPage, $max));
    }

    /**
     * Resolve page number (>= 1).
     */
    protected function pageNumber(Request $request): int
    {
        return max(1, (int) $request->query('page', 1));
    }

    /**
     * Build your custom pagination block.
     */
    protected function buildPaginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'from'         => $paginator->firstItem(),
            'to'           => $paginator->lastItem(),
        ];
    }

    /**
     * Return your standardized JSON response.
     *
     * @param  LengthAwarePaginator $paginator
     * @param  string               $resourceKey   e.g. "cards", "properties", "customers"
     * @param  callable|null        $transform     map each item: fn($item) => [...]
     * @param  array                $extraData     merged into "data" alongside resource & pagination
     */
    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        string $resourceKey,
        ?callable $transform = null,
        array $extraData = []
    ) {
        $items = $paginator->items();
        if ($transform) {
            $items = array_map($transform, $items);
        }

        return response()->json([
            'status' => 'success',
            'data'   => array_merge(
                [
                    $resourceKey => $items,
                    'pagination' => $this->buildPaginationMeta($paginator),
                ],
                $extraData
            ),
        ]);
    }

    /**
     * Convenience method: paginate a query and return your custom response.
     *
     * @param  Builder|QueryBuilder $query
     * @param  Request              $request
     * @param  string               $resourceKey
     * @param  callable|null        $transform
     * @param  array                $extraData
     */
    protected function paginateQuery(
        $query,
        Request $request,
        string $resourceKey,
        ?callable $transform = null,
        array $extraData = []
    ) {
        $perPage = $this->pageSize($request);
        $page    = $this->pageNumber($request);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $paginator->appends($request->query());

        return $this->paginatedResponse($paginator, $resourceKey, $transform, $extraData);
    }
}
