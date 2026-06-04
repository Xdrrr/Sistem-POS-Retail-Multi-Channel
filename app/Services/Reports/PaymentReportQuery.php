<?php

namespace App\Services\Reports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class PaymentReportQuery extends ReportQuery
{
    public function title(): string
    {
        return 'Laporan Pembayaran';
    }

    public function columns(): array
    {
        return ['payment_number', 'order_number', 'customer_name', 'method', 'status', 'amount', 'paid_at', 'reference_number'];
    }

    public function preview(array $filters): LengthAwarePaginator
    {
        $query = $this->baseQuery($filters);
        $this->applySort($query, $filters, [
            'payment_number' => 'p.payment_number',
            'method' => 'p.method',
            'status' => 'p.status',
            'amount' => 'p.amount',
            'paid_at' => 'p.paid_at',
        ], 'p.paid_at');

        return $this->paginate($query, $filters);
    }

    public function summary(array $filters): array
    {
        $row = $this->baseQuery($filters)
            ->select([
                DB::raw('COUNT(*) as payment_count'),
                DB::raw("COALESCE(SUM(CASE WHEN p.status = 'paid' THEN p.amount ELSE 0 END), 0) as paid_amount"),
                DB::raw("COALESCE(SUM(CASE WHEN p.method = 'cash' AND p.status = 'paid' THEN p.amount ELSE 0 END), 0) as cash_amount"),
                DB::raw("COALESCE(SUM(CASE WHEN p.method != 'cash' AND p.status = 'paid' THEN p.amount ELSE 0 END), 0) as digital_amount"),
            ])
            ->first();

        return [
            'payment_count' => (int) $row->payment_count,
            'paid_amount' => $this->decimal($row->paid_amount),
            'cash_amount' => $this->decimal($row->cash_amount),
            'digital_amount' => $this->decimal($row->digital_amount),
        ];
    }

    public function exportHeadings(): array
    {
        return ['Payment Number', 'Order Number', 'Customer Name', 'Method', 'Status', 'Amount', 'Paid At', 'Reference Number', 'Notes'];
    }

    public function exportRows(array $filters): LazyCollection
    {
        return $this->baseQuery($filters)
            ->orderBy('p.id')
            ->lazyById(500, 'p.id', 'id');
    }

    public function formatRow(object $row): array
    {
        return [$row->payment_number, $row->order_number, $row->customer_name, $row->method, $row->status, $row->amount, $row->paid_at, $row->reference_number, $row->notes];
    }

    private function baseQuery(array $filters)
    {
        $query = DB::table('orders.payments as p')
            ->leftJoin('orders.orders as o', 'o.guid', '=', 'p.order_guid')
            ->select(['p.id', 'p.payment_number', 'o.order_number', 'o.customer_name', 'p.method', 'p.status', 'p.amount', 'p.paid_at', 'p.reference_number', 'p.notes']);

        $this->applyDateRange($query, $filters, 'p.paid_at');
        $this->applyInFilter($query, $filters, 'methods', 'p.method');
        $this->applyInFilter($query, $filters, 'statuses', 'p.status');

        return $query;
    }
}
