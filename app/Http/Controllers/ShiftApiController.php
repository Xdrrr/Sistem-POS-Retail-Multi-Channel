<?php

namespace App\Http\Controllers;

use App\Models\AuthenticationSession;
use App\Models\AuthenticationUser;
use App\Models\Shift;
use App\Services\Shifts\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

class ShiftApiController extends Controller
{
    public function __construct(private readonly ShiftService $shifts)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'filter' => ['nullable', 'array'],
            'filter.set_guid' => ['nullable', 'boolean'],
            'filter.guid' => ['nullable', 'string'],
            'filter.set_status' => ['nullable', 'boolean'],
            'filter.status' => ['nullable', 'string', 'in:open,closed'],
            'filter.set_user_guid' => ['nullable', 'boolean'],
            'filter.user_guid' => ['nullable', 'string', Rule::exists(AuthenticationUser::class, 'guid')],
            'filter.set_from_date' => ['nullable', 'boolean'],
            'filter.from_date' => ['nullable', 'date'],
            'filter.set_to_date' => ['nullable', 'boolean'],
            'filter.to_date' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:shift_number,opened_at,closed_at,created_at'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC'],
        ]);

        if ($validator->fails()) {
            return $this->apiResponse('99', 'failed', null, 'Validation failed.', 'Validasi gagal.', 422);
        }

        $validated = $validator->validated();
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

    public function store(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if (! $user) {
            return $this->apiResponse('01', 'failed', null, 'User session not found.', 'Sesi user tidak ditemukan.', 401);
        }

        $validator = Validator::make($request->all(), [
            'opened_at' => ['required', 'date'],
            'work_hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->apiResponse('99', 'failed', null, 'Validation failed.', 'Validasi gagal.', 422);
        }

        try {
            $shift = $this->shifts->open($user, $validator->validated());
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'active_shift_exists') {
                return $this->apiResponse('03', 'failed', null, 'You already have an active shift.', 'Anda masih memiliki shift aktif.', 409);
            }

            throw $exception;
        }

        return $this->apiResponse('00', 'success', $this->shifts->data($shift), 'Shift opened successfully.', 'Shift berhasil dibuka.', 201);
    }

    public function close(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if (! $user) {
            return $this->apiResponse('01', 'failed', null, 'User session not found.', 'Sesi user tidak ditemukan.', 401);
        }

        $validator = Validator::make($request->all(), [
            'guid' => ['required', 'string', Rule::exists(Shift::class, 'guid')],
            'closed_at' => ['required', 'date'],
            'work_hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'closing_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->apiResponse('99', 'failed', null, 'Validation failed.', 'Validasi gagal.', 422);
        }

        $validated = $validator->validated();
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
        $user = $this->authenticatedUser($request);

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

    private function authenticatedUser(Request $request): ?AuthenticationUser
    {
        $apiToken = $request->attributes->get('api_token');

        if (! $apiToken) {
            return null;
        }

        $session = AuthenticationSession::query()
            ->with(['user.role', 'user.detail'])
            ->where('api_token_id', $apiToken->id)
            ->latest('last_login_at')
            ->latest('id')
            ->first();

        return $session?->user;
    }
}
