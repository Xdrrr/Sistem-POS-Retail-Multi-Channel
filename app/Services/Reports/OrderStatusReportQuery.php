<?php

namespace App\Services\Reports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class OrderStatusReportQuery extends ReportQuery
{
    public function title(): string
    {
        return 'Laporan Status Order';
    }

    public function columns(): array
    {
        return ['cabang', 'status', 'payment_status', 'order_count', 'total_amount'];
    }

    public function preview(array $filters): LengthAwarePaginator
    {
        $query = $this->baseQuery($filters);
        $this->applySort($query, $filters, ['cabang' => 'cb.kode', 'status' => 'status', 'payment_status' => 'payment_status', 'order_count' => 'order_count', 'total_amount' => 'total_amount'], 'order_count');

        return $this->paginate($query, $filters);
    }

    public function summary(array $filters): array
    {
        $row = DB::query()->fromSub($this->baseQuery($filters), 'status_report')
            ->selectRaw('COALESCE(SUM(order_count), 0) as order_count')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
            ->first();

        return [
            'order_count' => (int) $row->order_count,
            'total_amount' => $this->decimal($row->total_amount),
        ];
    }

    public function exportHeadings(): array
    {
        return ['Cabang', 'Status', 'Payment Status', 'Order Count', 'Total Amount'];
    }

    public function exportRows(array $filters): LazyCollection
    {
        return $this->baseQuery($filters)->orderByDesc('order_count')->cursor();
    }

    public function formatRow(object $row): array
    {
        return [$row->cabang_kode, $row->status, $row->payment_status, $row->order_count, $row->total_amount];
    }

    private function baseQuery(array $filters)
    {
        $query = DB::table('orders.orders as o')
            ->leftJoin('authentication.cabang as cb', 'cb.guid', '=', 'o.guid_cabang')
            ->select(['o.status', 'o.payment_status'])
            ->selectRaw("COALESCE(NULLIF(cb.kode, ''), 'PUSAT') as cabang_kode")
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('COALESCE(SUM(o.total_amount), 0) as total_amount')
            ->groupBy('o.status', 'o.payment_status', 'cb.kode');

        $this->applyDateRange($query, $filters, 'o.ordered_at');
        $this->applyInFilter($query, $filters, 'guid_cabang', 'o.guid_cabang');
        $this->applyInFilter($query, $filters, 'statuses', 'o.status');
        $this->applyInFilter($query, $filters, 'payment_statuses', 'o.payment_status');

        return $query;
    }
}
