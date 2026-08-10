<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shift\CloseShiftRequest;
use App\Http\Requests\Shift\IndexShiftRequest;
use App\Http\Requests\Shift\StoreShiftRequest;
use App\Models\Shift;
use App\Services\Shifts\ShiftService;
use App\Traits\ResolvesAuthUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ShiftApiController extends Controller
{
    use ResolvesAuthUser;

    public function __construct(private readonly ShiftService $shifts)
    {
    }

    public function index(IndexShiftRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $paginator = $this->shifts->list([
            ...($validated['filter'] ?? []),
            'limit' => $validated['limit'] ?? 20,
            'page' => $validated['page'] ?? 1,
            'order' => $validated['order'] ?? 'opened_at',
            'sort' => $validated['sort'] ?? 'DESC',
        ]);

        return $this->apiResponse('00', 'success', [
            'data' => collect($paginator->items())
                ->map(fn (Shift $shift): array => $this->shifts->data($shift))
                ->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreShiftRequest $request): JsonResponse
    {
        $user = $this->resolveAuthUser($request);

        if (! $user) {
            return $this->apiResponse('01', 'failed', null, 'User session not found.', 'Sesi user tidak ditemukan.', 401);
        }

        try {
            $shift = $this->shifts->open($user, $request->validated());
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'active_shift_exists') {
                return $this->apiResponse('03', 'failed', null, 'You already have an active shift.', 'Anda masih memiliki shift aktif.', 409);
            }

            throw $exception;
        }

        return $this->apiResponse('00', 'success', $this->shifts->data($shift), 'Shift opened successfully.', 'Shift berhasil dibuka.', 201);
    }

    public function close(CloseShiftRequest $request): JsonResponse
    {
        $user = $this->resolveAuthUser($request);

        if (! $user) {
            return $this->apiResponse('01', 'failed', null, 'User session not found.', 'Sesi user tidak ditemukan.', 401);
        }

        $validated = $request->validated();
        $shift = Shift::query()->where('guid', $validated['guid'])->firstOrFail();

        try {
            $shift = $this->shifts->close($user, $shift, $validated);
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'shift_not_open') {
                return $this->apiResponse('02', 'failed', null, 'Shift is not open or does not belong to this user.', 'Shift tidak aktif atau bukan milik user ini.', 409);
            }

            throw $exception;
        }

        return $this->apiResponse('00', 'success', $this->shifts->data($shift, withOrders: true), 'Shift closed successfully.', 'Shift berhasil ditutup.');
    }

    public function active(Request $request): JsonResponse
    {
        $user = $this->resolveAuthUser($request);

        if (! $user) {
            return $this->apiResponse('01', 'failed', null, 'User session not found.', 'Sesi user tidak ditemukan.', 401);
        }

        $shift = $this->shifts->activeForUser($user);

        return $this->apiResponse('00', 'success', $shift ? $this->shifts->data($shift) : null);
    }

    public function show(string $guid): JsonResponse
    {
        $shift = Shift::query()
            ->with(['user.detail', 'user.role'])
            ->where('guid', $guid)
            ->first();

        if (! $shift) {
            return $this->apiResponse('01', 'failed', null, 'Shift not found.', 'Shift tidak ditemukan.', 404);
        }

        return $this->apiResponse('00', 'success', $this->shifts->data($shift, withOrders: true));
    }
}
