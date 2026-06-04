<?php

namespace App\Services\Reports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class CustomerReportQuery extends ReportQuery
{
    public function title(): string
    {
        return 'Laporan Customer';
    }

    public function columns(): array
    {
        return ['customer_name', 'customer_phone', 'order_count', 'total_spent', 'last_ordered_at'];
    }

    public function preview(array $filters): LengthAwarePaginator
    {
        $query = $this->baseQuery($filters);
        $this->applySort($query, $filters, [
            'customer_name' => 'customer_name',
            'order_count' => 'order_count',
            'total_spent' => 'total_spent',
            'last_ordered_at' => 'last_ordered_at',
        ], 'total_spent');

        return $this->paginate($query, $filters);
    }

    public function summary(array $filters): array
    {
        $row = DB::query()->fromSub($this->baseQuery($filters), 'customer_report')
            ->selectRaw('COUNT(*) as customer_count')
            ->selectRaw('COALESCE(SUM(order_count), 0) as order_count')
            ->selectRaw('COALESCE(SUM(total_spent), 0) as total_spent')
            ->first();

        return [
            'customer_count' => (int) $row->customer_count,
            'order_count' => (int) $row->order_count,
            'total_spent' => $this->decimal($row->total_spent),
        ];
    }

    public function exportHeadings(): array
    {
        return ['Customer Name', 'Customer Phone', 'Order Count', 'Total Spent', 'Last Ordered At'];
    }

    public function exportRows(array $filters): LazyCollection
    {
        return $this->baseQuery($filters)->orderByDesc('total_spent')->cursor();
    }

    public function formatRow(object $row): array
    {
        return [$row->customer_name, $row->customer_phone, $row->order_count, $row->total_spent, $row->last_ordered_at];
    }

    private function baseQuery(array $filters)
    {
        $query = DB::table('orders.orders as o')
            ->selectRaw("COALESCE(NULLIF(o.customer_name, ''), 'Walk-in') as customer_name")
            ->selectRaw("COALESCE(NULLIF(o.customer_phone, ''), '-') as customer_phone")
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('COALESCE(SUM(o.total_amount), 0) as total_spent')
            ->selectRaw('MAX(o.ordered_at) as last_ordered_at')
            ->groupByRaw("COALESCE(NULLIF(o.customer_name, ''), 'Walk-in'), COALESCE(NULLIF(o.customer_phone, ''), '-')");

        $this->applyDateRange($query, $filters, 'o.ordered_at');
        $this->applySearch($query, $this->filter($filters, 'customer_search'), ['o.customer_name']);
        $this->applySearch($query, $this->filter($filters, 'customer_phone'), ['o.customer_phone']);

        $minTransactions = $this->filter($filters, 'min_transactions');
        if ($minTransactions !== null && $minTransactions !== '') {
            $query->havingRaw('COUNT(*) >= ?', [(int) $minTransactions]);
        }

        $minTotalSpent = $this->filter($filters, 'min_total_spent');
        if ($minTotalSpent !== null && $minTotalSpent !== '') {
            $query->havingRaw('COALESCE(SUM(o.total_amount), 0) >= ?', [(float) $minTotalSpent]);
        }

        return $query;
    }
}
