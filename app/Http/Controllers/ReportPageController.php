<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ProductGroup;
use App\Models\ReportExport;
use App\Services\Reports\ReportQueryFactory;
use Inertia\Inertia;
use Inertia\Response;

class ReportPageController extends Controller
{
    public function index(ReportQueryFactory $factory): Response
    {
        $now = now();

        return Inertia::render('Reports/Index', [
            'serverTime' => $now->format('l, d F Y at h:i A'),
            'appTimezone' => config('app.timezone'),
            'serverDatetime' => $now->format('Y-m-d\TH:i'),
            'reportTypes' => $factory->options(),
            'categories' => Category::query()
                ->orderBy('name')
                ->get(['guid', 'name'])
                ->map(fn (Category $category): array => [
                    'guid' => $category->guid,
                    'name' => $category->name,
                ]),
            'groups' => ProductGroup::query()
                ->orderBy('name')
                ->get(['guid', 'name'])
                ->map(fn (ProductGroup $group): array => [
                    'guid' => $group->guid,
                    'name' => $group->name,
                ]),
        ]);
    }

    public function exports(ReportQueryFactory $factory): Response
    {
        return Inertia::render('Reports/Exports', [
            'serverTime' => now()->format('l, d F Y at h:i A'),
            'reportTypes' => $factory->options(),
        ]);
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
            'created_at' => $export->created_at?->toISOString(),
            'finished_at' => $export->finished_at?->toISOString(),
            'download_url' => $export->status === 'done' ? route('reports.exports.download', $export->guid) : null,
        ];
    }
}
