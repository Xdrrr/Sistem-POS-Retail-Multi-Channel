<?php

namespace App\Services\Reports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class CatalogReportQuery extends ReportQuery
{
    public function title(): string
    {
        return 'Laporan Katalog';
    }

    public function columns(): array
    {
        return ['name', 'sku', 'category_name', 'group_name', 'cabang', 'price', 'is_active'];
    }

    public function preview(array $filters): LengthAwarePaginator
    {
        $query = $this->baseQuery($filters);
        $this->applySort($query, $filters, ['name' => 'p.name', 'cabang' => 'cb.kode', 'price' => 'p.price', 'is_active' => 'p.is_active'], 'p.name', 'ASC');

        return $this->paginate($query, $filters);
    }

    public function summary(array $filters): array
    {
        $row = $this->baseQuery($filters)
            ->select([
                DB::raw('COUNT(*) as product_count'),
                DB::raw('COALESCE(SUM(CASE WHEN p.is_active THEN 1 ELSE 0 END), 0) as active_count'),
                DB::raw('COALESCE(AVG(p.price), 0) as average_price'),
            ])
            ->first();

        return [
            'product_count' => (int) $row->product_count,
            'active_count' => (int) $row->active_count,
            'average_price' => $this->decimal($row->average_price),
        ];
    }

    public function exportHeadings(): array
    {
        return ['Product Name', 'SKU', 'Category', 'Group', 'Cabang', 'Price', 'Active', 'Description'];
    }

    public function exportRows(array $filters): LazyCollection
    {
        return $this->baseQuery($filters)->orderBy('p.id')->lazyById(500, 'p.id', 'id');
    }

    public function formatRow(object $row): array
    {
        return [$row->name, $row->sku, $row->category_name, $row->group_name, $row->cabang_kode, $row->price, $row->is_active ? 'active' : 'inactive', $row->description];
    }

    private function baseQuery(array $filters)
    {
        $query = DB::table('product.products as p')
            ->leftJoin('product.categories as c', 'c.guid', '=', 'p.category_guid')
            ->leftJoin('product.groups as g', 'g.guid', '=', 'p.group_guid')
            ->leftJoin('authentication.cabang as cb', 'cb.guid', '=', 'p.guid_cabang')
            ->select(['p.id', 'p.name', 'p.sku', 'p.description', 'p.price', 'p.is_active', 'c.name as category_name', 'g.name as group_name', 'cb.kode as cabang_kode']);

        $this->applyInFilter($query, $filters, 'guid_cabang', 'p.guid_cabang');
        $this->applyInFilter($query, $filters, 'category_guids', 'p.category_guid');
        $this->applyInFilter($query, $filters, 'group_guids', 'p.group_guid');
        $this->applySearch($query, $this->filter($filters, 'product_search'), ['p.name']);

        $isActive = $this->filter($filters, 'is_active');
        if ($isActive !== null && $isActive !== '') {
            $query->where('p.is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
        }

        return $query;
    }
}
