<?php

namespace App\Http\Controllers;

use App\Jobs\ExportReportJob;
use App\Models\ReportExport;
use App\Services\Reports\ReportQueryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function preview(Request $request, string $type, ReportQueryFactory $factory): JsonResponse
    {
        $filters = $this->validatedFilters($request, $type);
        $report = $factory->make($type);
        $pagination = $report->preview($filters);

        return $this->apiResponse('00', 'success', [
            'columns' => $report->columns(),
            'data' => $pagination->items(),
            'meta' => [
                'current_page' => $pagination->currentPage(),
                'last_page' => $pagination->lastPage(),
                'per_page' => $pagination->perPage(),
                'total' => $pagination->total(),
            ],
        ]);
    }

    public function summary(Request $request, string $type, ReportQueryFactory $factory): JsonResponse
    {
        $filters = $this->validatedFilters($request, $type);
        $report = $factory->make($type);

        return $this->apiResponse('00', 'success', $report->summary($filters));
    }

    public function export(Request $request, string $type): JsonResponse
    {
        $filters = $this->validatedFilters($request, $type);

        $export = ReportExport::query()->create([
            'guid' => (string) Str::uuid(),
            'type' => $type,
            'status' => 'queued',
            'filters' => $filters,
            'format' => 'csv',
            'requested_by' => $request->session()->get('web_auth_user_id'),
        ]);

        ExportReportJob::dispatch($export->id);

        return $this->apiResponse('00', 'success', $this->exportData($export), 'Report export queued.', 'Export laporan masuk antrean.', 201);
    }

    public function exportStatus(string $guid): JsonResponse
    {
        $export = ReportExport::query()->where('guid', $guid)->first();

        if (! $export) {
            return $this->apiResponse('01', 'failed', null, 'Report export not found.', 'Export laporan tidak ditemukan.', 404);
        }

        return $this->apiResponse('00', 'success', $this->exportData($export));
    }

    public function exportHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filter' => ['nullable', 'array'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $filter = $validated['filter'] ?? [];
        $limit = max(1, min(100, (int) ($validated['limit'] ?? 10)));
        $page = max(1, (int) ($validated['page'] ?? 1));

        $query = ReportExport::query()->latest();

        $this->applyExportHistoryFilters($query, $filter);

        $exports = $query->paginate($limit, ['*'], 'page', $page);

        return $this->apiResponse('00', 'success', [
            'data' => $exports->getCollection()
                ->map(fn (ReportExport $export): array => $this->exportData($export))
                ->values(),
            'meta' => [
                'current_page' => $exports->currentPage(),
                'last_page' => $exports->lastPage(),
                'per_page' => $exports->perPage(),
                'total' => $exports->total(),
            ],
        ]);
    }

    public function download(string $guid, ReportQueryFactory $factory): BinaryFileResponse|JsonResponse
    {
        $export = ReportExport::query()->where('guid', $guid)->first();

        if (! $export || $export->status !== 'done' || ! $export->file_path) {
            return $this->apiResponse('01', 'failed', null, 'Report export is not ready.', 'Export laporan belum siap.', 404);
        }

        $path = storage_path('app/'.$export->file_path);

        if (! File::exists($path)) {
            return $this->apiResponse('01', 'failed', null, 'Report export file not found.', 'File export laporan tidak ditemukan.', 404);
        }

        return response()->download($path, $this->downloadFilename($export, $factory));
    }

    private function validatedFilters(Request $request, string $type): array
    {
        validator(['type' => $type], [
            'type' => ['required', 'string', Rule::in(array_keys(ReportQueryFactory::TYPES))],
        ])->validate();

        $validated = $request->validate([
            'filter' => ['nullable', 'array'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'max:60'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC,asc,desc'],
        ]);

        return [
            ...($validated['filter'] ?? []),
            'limit' => $validated['limit'] ?? 20,
            'page' => $validated['page'] ?? 1,
            'order' => $validated['order'] ?? null,
            'sort' => strtoupper((string) ($validated['sort'] ?? 'DESC')),
        ];
    }

    private function exportData(ReportExport $export): array
    {
        return [
            'guid' => $export->guid,
            'type' => $export->type,
            'status' => $export->status,
            'format' => $export->format,
            'row_count' => $export->row_count,
            'error_message' => $export->error_message,
            'filters' => $export->filters,
            'created_at' => $export->created_at?->toISOString(),
            'started_at' => $export->started_at?->toISOString(),
            'finished_at' => $export->finished_at?->toISOString(),
            'download_url' => $export->status === 'done' ? route('reports.exports.download', $export->guid) : null,
        ];
    }

    private function applyExportHistoryFilters(Builder $query, array $filter): void
    {
        if (($filter['set_type'] ?? false) === true && ! empty($filter['type'])) {
            $query->where('type', $filter['type']);
        }

        if (($filter['set_status'] ?? false) === true && ! empty($filter['status'])) {
            $query->where('status', $filter['status']);
        }

        if (($filter['set_from_date'] ?? false) === true && ! empty($filter['from_date'])) {
            $query->where('created_at', '>=', Carbon::parse($filter['from_date']));
        }

        if (($filter['set_to_date'] ?? false) === true && ! empty($filter['to_date'])) {
            $query->where('created_at', '<=', Carbon::parse($filter['to_date']));
        }
    }

    private function downloadFilename(ReportExport $export, ReportQueryFactory $factory): string
    {
        $reportName = Str::slug($factory->make($export->type)->title());
        $filters = $export->filters ?? [];
        $from = $this->filenameDatePart($filters['from_date'] ?? null);
        $to = $this->filenameDatePart($filters['to_date'] ?? null);
        $range = $from || $to ? trim(($from ?: 'start').'_to_'.($to ?: 'end'), '_') : 'all-date';
        $downloadedAt = now()->format('d-m-Y_H:i');

        return "{$reportName}_{$range}_export_in_{$downloadedAt}.{$export->format}";
    }

    private function filenameDatePart(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('d-m-Y_H:i');
    }
}
