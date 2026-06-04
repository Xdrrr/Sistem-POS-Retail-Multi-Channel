<?php

namespace App\Http\Controllers;

use App\Models\AuthenticationUser;
use App\Models\Shift;
use App\Services\Shifts\ShiftService;
use Inertia\Inertia;
use Inertia\Response;

class ShiftPageController extends Controller
{
    public function __construct(private readonly ShiftService $shifts)
    {
    }

    public function index(): Response
    {
        $paginator = $this->shifts->list([
            'limit' => 50,
            'page' => 1,
            'order' => 'opened_at',
            'sort' => 'DESC',
        ]);
        $items = collect($paginator->items())
            ->map(fn (Shift $shift): array => $this->shifts->data($shift))
            ->values();
        $active = $items->where('status', 'open')->values();

        return Inertia::render('Shift/Index', [
            'title' => 'Shift',
            'serverTime' => now()->format('l, d F Y at h:i A'),
            'summary' => [
                'active_count' => $active->count(),
                'today_count' => Shift::query()->whereDate('opened_at', now()->toDateString())->count(),
                'closed_today' => Shift::query()->whereDate('closed_at', now()->toDateString())->where('status', 'closed')->count(),
                'total_sales_today' => $items
                    ->filter(fn (array $shift): bool => str_starts_with((string) $shift['opened_at'], now()->toDateString()))
                    ->sum(fn (array $shift): float => (float) $shift['summary']['total_sales']),
            ],
            'shifts' => $items,
            'cashiers' => AuthenticationUser::query()
                ->with(['detail', 'role'])
                ->whereHas('role', fn ($query) => $query->whereIn('name', ['Cashier', 'Manager', 'Owner', 'Superadmin']))
                ->orderBy('username')
                ->get()
                ->map(fn (AuthenticationUser $user): array => [
                    'guid' => $user->guid,
                    'name' => $user->detail?->full_name ?? $user->username,
                    'username' => $user->username,
                    'role' => $user->role?->name,
                ]),
        ]);
    }

    public function show(string $guid): Response
    {
        $shift = Shift::query()
            ->with(['user.detail', 'user.role'])
            ->where('guid', $guid)
            ->firstOrFail();

        return Inertia::render('Shift/Show', [
            'title' => 'Shift Detail',
            'serverTime' => now()->format('l, d F Y at h:i A'),
            'shift' => $this->shifts->data($shift, withOrders: true),
        ]);
    }
}
