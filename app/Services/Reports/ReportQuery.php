<?php

namespace App\Services\Reports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use InvalidArgumentException;

abstract class ReportQuery
{
    abstract public function title(): string;

    abstract public function columns(): array;

    abstract public function preview(array $filters): LengthAwarePaginator;

    abstract public function summary(array $filters): array;

    abstract public function exportHeadings(): array;

    abstract public function exportRows(array $filters): LazyCollection;

    abstract public function formatRow(object $row): array;

    protected function filter(array $filters, string $key, mixed $default = null): mixed
    {
        $setKey = 'set_'.$key;

        if (array_key_exists($setKey, $filters) && $filters[$setKey] !== true) {
            return $default;
        }

        return $filters[$key] ?? $default;
    }

    protected function arrayFilter(array $filters, string $key): array
    {
        $value = $this->filter($filters, $key, []);

        if ($value === null || $value === '') {
            return [];
        }

        return array_values(array_filter(is_array($value) ? $value : [$value], fn ($item): bool => $item !== null && $item !== ''));
    }

    protected function applyDateRange(Builder $query, array $filters, string $column): void
    {
        $from = $this->filter($filters, 'from_date');
        $to = $this->filter($filters, 'to_date');

        if ($from) {
            $query->where($column, '>=', Carbon::parse($from));
        }

        if ($to) {
            $query->where($column, '<=', Carbon::parse($to));
        }
    }

    protected function applyInFilter(Builder $query, array $filters, string $key, string $column): void
    {
        $values = $this->arrayFilter($filters, $key);

        if ($values !== []) {
            $query->whereIn($column, $values);
        }
    }

    protected function applySearch(Builder $query, ?string $search, array $columns): void
    {
        $search = trim((string) $search);

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $query) use ($columns, $search): void {
            foreach ($columns as $column) {
                $query->orWhere($column, 'ILIKE', '%'.$search.'%');
            }
        });
    }

    protected function paginate(Builder $query, array $filters): LengthAwarePaginator
    {
        $limit = max(1, min(100, (int) ($filters['limit'] ?? 20)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        return $query->paginate($limit, ['*'], 'page', $page);
    }

    protected function applySort(Builder $query, array $filters, array $allowed, string $defaultColumn, string $defaultSort = 'DESC'): void
    {
        $order = (string) ($filters['order'] ?? '');
        $column = $allowed[$order] ?? $defaultColumn;
        $sort = strtoupper((string) ($filters['sort'] ?? $defaultSort));

        if (! in_array($sort, ['ASC', 'DESC'], true)) {
            $sort = $defaultSort;
        }

        $query->orderBy($column, $sort);
    }

    protected function decimal(mixed $value): float
    {
        return round((float) ($value ?? 0), 2);
    }

    protected function collectionSummary(Builder $query): Collection
    {
        return DB::query()->fromSub($query, 'report_rows')->get();
    }

    protected function unsupportedType(string $type): never
    {
        throw new InvalidArgumentException("Unsupported report type [{$type}].");
    }
}
