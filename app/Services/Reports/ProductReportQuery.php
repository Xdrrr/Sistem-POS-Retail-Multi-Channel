<?php

namespace App\Services\Reports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class ProductReportQuery extends ReportQuery
{
    public function title(): string
    {
        return 'Laporan Produk';
    }

    public function columns(): array
    {
        return ['product_name', 'category_name', 'group_name', 'quantity', 'subtotal', 'order_count'];
    }

    public function preview(array $filters): LengthAwarePaginator
    {
        $query = $this->baseQuery($filters);
        $this->applySort($query, $filters, [
            'product_name' => 'oi.product_name',
            'quantity' => 'quantity',
            'subtotal' => 'subtotal',
            'order_count' => 'order_count',
        ], 'subtotal');

        return $this->paginate($query, $filters);
    }

    public function summary(array $filters): array
    {
        $row = DB::query()->fromSub($this->baseQuery($filters), 'products_report')
            ->selectRaw('COUNT(*) as product_count')
            ->selectRaw('COALESCE(SUM(quantity), 0) as quantity')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as subtotal')
            ->first();

        return [
            'product_count' => (int) $row->product_count,
            'quantity' => $this->decimal($row->quantity),
            'subtotal' => $this->decimal($row->subtotal),
        ];
    }

    public function exportHeadings(): array
    {
        return ['Product Name', 'Category', 'Group', 'Quantity', 'Subtotal', 'Order Count'];
    }

    public function exportRows(array $filters): LazyCollection
    {
        return $this->baseQuery($filters)->orderByDesc('subtotal')->cursor();
    }

    public function formatRow(object $row): array
    {
        return [$row->product_name, $row->category_name, $row->group_name, $row->quantity, $row->subtotal, $row->order_count];
    }

    private function baseQuery(array $filters)
    {
        $query = DB::table('orders.order_items as oi')
            ->join('orders.orders as o', 'o.guid', '=', 'oi.order_guid')
            ->leftJoin('product.products as p', 'p.guid', '=', 'oi.product_guid')
            ->leftJoin('product.categories as c', 'c.guid', '=', 'p.category_guid')
            ->leftJoin('product.groups as g', 'g.guid', '=', 'p.group_guid')
            ->select(['oi.product_guid', 'oi.product_name', 'c.name as category_name', 'g.name as group_name'])
            ->selectRaw('COALESCE(SUM(oi.quantity), 0) as quantity')
            ->selectRaw('COALESCE(SUM(oi.subtotal), 0) as subtotal')
            ->selectRaw('COUNT(DISTINCT oi.order_guid) as order_count')
            ->groupBy('oi.product_guid', 'oi.product_name', 'c.name', 'g.name');

        $this->applyDateRange($query, $filters, 'o.ordered_at');
        $this->applyInFilter($query, $filters, 'category_guids', 'c.guid');
        $this->applyInFilter($query, $filters, 'group_guids', 'g.guid');
        $this->applyInFilter($query, $filters, 'statuses', 'o.status');
        $this->applySearch($query, $this->filter($filters, 'product_search'), ['oi.product_name', 'p.name']);

        return $query;
    }
}
