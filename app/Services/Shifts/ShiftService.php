<?php

namespace App\Services\Shifts;

use App\Models\AuthenticationUser;
use App\Models\Order;
use App\Models\Shift;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ShiftService
{
    public function __construct(private readonly ShiftSalesSummary $summary)
    {
    }

    public function open(AuthenticationUser $user, array $data): Shift
    {
        if ($this->activeForUser($user)) {
            throw new RuntimeException('active_shift_exists');
        }

        $openedAt = Carbon::parse($data['opened_at']);
        $openingBalance = (float) $data['opening_balance'];

        return DB::transaction(fn (): Shift => Shift::query()->create([
            'guid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'user_guid' => $user->guid,
            'shift_number' => $this->generateShiftNumber($openedAt),
            'opened_at' => $openedAt,
            'work_hours' => $data['work_hours'],
            'opening_balance' => $openingBalance,
            'expected_balance' => $openingBalance,
            'notes' => $data['notes'] ?? null,
            'status' => 'open',
        ])->load(['user.detail', 'user.role']));
    }

    public function close(AuthenticationUser $user, Shift $shift, array $data): Shift
    {
        if ($shift->user_id !== $user->id || $shift->status !== 'open') {
            throw new RuntimeException('shift_not_open');
        }

        return DB::transaction(function () use ($shift, $data): Shift {
            $summary = $this->summary->forShift($shift);
            $expectedBalance = (float) $shift->opening_balance + $summary['cash_sales'];
            $closingBalance = (float) $data['closing_balance'];

            $shift->update([
                'closed_at' => Carbon::parse($data['closed_at']),
                'work_hours' => $data['work_hours'],
                'closing_balance' => $closingBalance,
                'expected_balance' => $expectedBalance,
                'difference' => $closingBalance - $expectedBalance,
                'notes' => $data['notes'] ?? $shift->notes,
                'status' => 'closed',
            ]);

            return $shift->refresh()->load(['user.detail', 'user.role']);
        });
    }

    public function attachOrder(?string $shiftGuid, AuthenticationUser $user): ?Shift
    {
        if (! $shiftGuid) {
            return null;
        }

        return Shift::query()
            ->where('guid', $shiftGuid)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->first();
    }

    public function activeForUser(AuthenticationUser $user): ?Shift
    {
        return Shift::query()
            ->with(['user.detail', 'user.role'])
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Shift::query()
            ->with(['user.detail', 'user.role'])
            ->withCount('orders');

        $this->applyListFilters($query, $filters);

        $orderMap = [
            'shift_number' => 'shift_number',
            'opened_at' => 'opened_at',
            'closed_at' => 'closed_at',
            'created_at' => 'created_at',
        ];
        $order = $orderMap[$filters['order'] ?? 'opened_at'] ?? 'opened_at';
        $sort = strtoupper($filters['sort'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        return $query->orderBy($order, $sort)
            ->paginate(
                perPage: (int) ($filters['limit'] ?? 20),
                page: (int) ($filters['page'] ?? 1),
            );
    }

    public function data(Shift $shift, bool $withOrders = false): array
    {
        $shift->loadMissing(['user.detail', 'user.role']);
        $summary = $this->summary->forShift($shift);

        return [
            'guid' => $shift->guid,
            'shift_number' => $shift->shift_number,
            'user' => [
                'guid' => $shift->user?->guid,
                'full_name' => $shift->user?->detail?->full_name ?? $shift->user?->username,
                'username' => $shift->user?->username,
                'role' => $shift->user?->role?->name,
            ],
            'opened_at' => $shift->opened_at?->toISOString(),
            'closed_at' => $shift->closed_at?->toISOString(),
            'work_hours' => (float) $shift->work_hours,
            'opening_balance' => (float) $shift->opening_balance,
            'closing_balance' => $shift->closing_balance === null ? null : (float) $shift->closing_balance,
            'expected_balance' => (float) $shift->expected_balance,
            'difference' => $shift->difference === null ? null : (float) $shift->difference,
            'status' => $shift->status,
            'notes' => $shift->notes,
            'summary' => $summary,
            'orders' => $withOrders ? $this->recentOrders($shift) : [],
            'created_at' => $shift->created_at?->toISOString(),
            'updated_at' => $shift->updated_at?->toISOString(),
        ];
    }

    private function applyListFilters(Builder $query, array $filters): void
    {
        if (($filters['set_guid'] ?? false) === true && ! empty($filters['guid'])) {
            $query->where('guid', $filters['guid']);
        }

        if (($filters['set_status'] ?? false) === true && ! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (($filters['set_user_guid'] ?? false) === true && ! empty($filters['user_guid'])) {
            $query->where('user_guid', $filters['user_guid']);
        }

        if (($filters['set_from_date'] ?? false) === true && ! empty($filters['from_date'])) {
            $query->where('opened_at', '>=', Carbon::parse($filters['from_date'])->startOfDay());
        }

        if (($filters['set_to_date'] ?? false) === true && ! empty($filters['to_date'])) {
            $query->where('opened_at', '<=', Carbon::parse($filters['to_date'])->endOfDay());
        }
    }

    private function generateShiftNumber(Carbon $openedAt): string
    {
        $prefix = 'SH-'.$openedAt->format('Ymd').'-';
        $lastNumber = Shift::query()
            ->where('shift_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('shift_number')
            ->value('shift_number');
        $sequence = $lastNumber ? ((int) substr($lastNumber, -3)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    private function recentOrders(Shift $shift): array
    {
        return Order::query()
            ->with('payments')
            ->where('shift_id', $shift->id)
            ->latest('ordered_at')
            ->limit(50)
            ->get()
            ->map(fn (Order $order): array => [
                'guid' => $order->guid,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total_amount' => (float) $order->total_amount,
                'paid_amount' => (float) $order->payments->where('status', 'paid')->sum('amount'),
                'ordered_at' => $order->ordered_at?->toISOString(),
            ])
            ->values()
            ->all();
    }
}
