<?php

namespace App\Services\Reports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class FinancialReportQuery extends ReportQuery
{
    public function title(): string
    {
        return 'Laporan Keuangan';
    }

    public function columns(): array
    {
        return ['period', 'cabang', 'order_count', 'subtotal', 'discount_amount', 'tax_amount', 'total_amount'];
    }

    public function preview(array $filters): LengthAwarePaginator
    {
        $query = $this->baseQuery($filters);
        $this->applySort($query, $filters, ['period' => 'period', 'cabang' => 'cb.kode', 'total_amount' => 'total_amount', 'order_count' => 'order_count'], 'period');

        return $this->paginate($query, $filters);
    }

    public function summary(array $filters): array
    {
        $row = DB::query()->fromSub($this->baseQuery($filters), 'financial_report')
            ->selectRaw('COALESCE(SUM(order_count), 0) as order_count')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as subtotal')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) as discount_amount')
            ->selectRaw('COALESCE(SUM(tax_amount), 0) as tax_amount')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
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
        return ['Period', 'Cabang', 'Order Count', 'Subtotal', 'Discount', 'Tax', 'Total'];
    }

    public function exportRows(array $filters): LazyCollection
    {
        return $this->baseQuery($filters)->orderBy('period')->cursor();
    }

    public function formatRow(object $row): array
    {
        return [$row->period, $row->cabang_kode, $row->order_count, $row->subtotal, $row->discount_amount, $row->tax_amount, $row->total_amount];
    }

    private function baseQuery(array $filters)
    {
        $query = DB::table('orders.orders as o')
            ->leftJoin('authentication.cabang as cb', 'cb.guid', '=', 'o.guid_cabang')
            ->selectRaw('DATE(o.ordered_at) as period')
            ->selectRaw("COALESCE(NULLIF(cb.kode, ''), 'PUSAT') as cabang_kode")
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('COALESCE(SUM(o.subtotal), 0) as subtotal')
            ->selectRaw('COALESCE(SUM(o.discount_amount), 0) as discount_amount')
            ->selectRaw('COALESCE(SUM(o.tax_amount), 0) as tax_amount')
            ->selectRaw('COALESCE(SUM(o.total_amount), 0) as total_amount')
            ->groupByRaw('DATE(o.ordered_at), cb.kode');

        $this->applyDateRange($query, $filters, 'o.ordered_at');
        $this->applyInFilter($query, $filters, 'guid_cabang', 'o.guid_cabang');
        $this->applyInFilter($query, $filters, 'statuses', 'o.status');
        $this->applyInFilter($query, $filters, 'payment_statuses', 'o.payment_status');

        return $query;
    }
}
