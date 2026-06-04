<?php

namespace App\Services\Shifts;

use App\Models\Shift;
use Illuminate\Support\Facades\DB;

class ShiftSalesSummary
{
    public function forShift(Shift $shift): array
    {
        $orders = DB::table('orders.orders as o')
            ->where('o.shift_id', $shift->id)
            ->selectRaw('COALESCE(SUM(CASE WHEN o.status = ? THEN o.total_amount ELSE 0 END), 0) as total_sales', ['completed'])
            ->selectRaw('COUNT(DISTINCT o.id) as order_count')
            ->selectRaw('COUNT(DISTINCT CASE WHEN o.payment_status = ? THEN o.id END) as paid_order_count', ['paid'])
            ->selectRaw('COUNT(DISTINCT CASE WHEN o.payment_status IN (?, ?) THEN o.id END) as pending_payment_count', ['unpaid', 'partial'])
            ->first();

        $payments = DB::table('orders.orders as o')
            ->join('orders.payments as p', 'p.order_guid', '=', 'o.guid')
            ->where('o.shift_id', $shift->id)
            ->selectRaw('COALESCE(SUM(CASE WHEN p.status = ? AND p.method = ? THEN p.amount ELSE 0 END), 0) as cash_sales', ['paid', 'cash'])
            ->selectRaw('COALESCE(SUM(CASE WHEN p.status = ? AND p.method <> ? THEN p.amount ELSE 0 END), 0) as digital_sales', ['paid', 'cash'])
            ->first();

        return [
            'total_sales' => (float) ($orders->total_sales ?? 0),
            'cash_sales' => (float) ($payments->cash_sales ?? 0),
            'digital_sales' => (float) ($payments->digital_sales ?? 0),
            'order_count' => (int) ($orders->order_count ?? 0),
            'paid_order_count' => (int) ($orders->paid_order_count ?? 0),
            'pending_payment_count' => (int) ($orders->pending_payment_count ?? 0),
        ];
    }
}
