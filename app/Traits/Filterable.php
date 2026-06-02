<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Filterable
{
    protected function applyFilter(Request $request, Builder $query, array $allowedFilters = []): Builder
    {
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $order = $request->input('order', 'created_at');
        $sort = $request->input('sort', 'DESC');

        // Apply filter conditions based on set_* flags
        $filter = $request->input('filter', []);
        if (!empty($allowedFilters) && !empty($filter)) {
            foreach ($allowedFilters as $field) {
                $setKey = 'set_' . $field;
                if (isset($filter[$setKey]) && $filter[$setKey] === true && array_key_exists($field, $filter)) {
                    $query->where($field, $filter[$field]);
                }
            }
        }

        // Apply pagination
        $query->limit($limit)->skip(($page - 1) * $limit);

        // Apply ordering
        $query->orderBy($order, $sort);

        return $query;
    }
}
