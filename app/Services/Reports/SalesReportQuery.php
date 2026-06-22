<?php

namespace App\Services\Reports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class SalesReportQuery extends ReportQuery
{
    public function title(): string
    {
        return 'Laporan Penjualan';
    }

    public function columns(): array
    {
        return ['order_number', 'customer', 'cabang', 'order_type', 'status', 'payment_status', 'subtotal', 'discount_amount', 'tax_amount', 'total_amount', 'ordered_at'];
    }

    public function preview(array $filters): LengthAwarePaginator
    {
        $query = $this->baseQuery($filters);
        $this->applySort($query, $filters, [
            'order_number' => 'o.order_number',
            'customer_name' => 'o.customer_name',
            'cabang' => 'cb.kode',
            'order_type' => 'o.order_type',
            'status' => 'o.status',
            'payment_status' => 'o.payment_status',
            'total_amount' => 'o.total_amount',
            'ordered_at' => 'o.ordered_at',
        ], 'o.ordered_at');

        return $this->paginate($query, $filters);
    }

    public function summary(array $filters): array
    {
        $row = $this->baseQuery($filters)
            ->select([
                DB::raw('COUNT(*) as order_count'),
                DB::raw('COALESCE(SUM(o.subtotal), 0) as subtotal'),
                DB::raw('COALESCE(SUM(o.discount_amount), 0) as discount_amount'),
                DB::raw('COALESCE(SUM(o.tax_amount), 0) as tax_amount'),
                DB::raw('COALESCE(SUM(o.total_amount), 0) as total_amount'),
            ])
            ->first();

        return [
            'order_count' => (int) $row->order_count,
            'subtotal' => $this->decimal($row->subtotal),
            'discount_amount' => $this->decimal($row->discount_amount),
            'tax_amount' => $this->decimal($row->tax_amount),
            'total_amount' => $this->decimal($row->total_amount),
        ];
    }

    public function exportHeadings(): array
    {
        return ['Cabang', 'Order Number', 'Customer Name', 'Customer Phone', 'Table', 'Order Type', 'Status', 'Payment Status', 'Subtotal', 'Discount', 'Tax', 'Total', 'Ordered At', 'Notes'];
    }

    public function exportRows(array $filters): LazyCollection
    {
        return $this->baseQuery($filters)
            ->orderBy('o.id')
            ->lazyById(500, 'o.id', 'id');
    }

    public function formatRow(object $row): array
    {
        return [$row->cabang_kode, $row->order_number, $row->customer_name, $row->customer_phone, $row->table_number, $row->order_type, $row->status, $row->payment_status, $row->subtotal, $row->discount_amount, $row->tax_amount, $row->total_amount, $row->ordered_at, $row->notes];
    }

    private function baseQuery(array $filters)
    {
        $query = DB::table('orders.orders as o')
            ->leftJoin('authentication.cabang as cb', 'cb.guid', '=', 'o.guid_cabang')
            ->select(['o.id', 'o.order_number', 'o.customer_name', 'o.customer_phone', 'o.table_number', 'o.order_type', 'o.status', 'o.payment_status', 'o.subtotal', 'o.discount_amount', 'o.tax_amount', 'o.total_amount', 'o.ordered_at', 'o.notes', 'cb.kode as cabang_kode']);

        $this->applyDateRange($query, $filters, 'o.ordered_at');
        $this->applyInFilter($query, $filters, 'guid_cabang', 'o.guid_cabang');
        $this->applyInFilter($query, $filters, 'statuses', 'o.status');
        $this->applyInFilter($query, $filters, 'order_types', 'o.order_type');
        $this->applyInFilter($query, $filters, 'payment_statuses', 'o.payment_status');
        $this->applySearch($query, $this->filter($filters, 'customer_search'), ['o.customer_name', 'o.customer_phone']);

        return $query;
    }
}
