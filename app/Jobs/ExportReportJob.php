<?php

namespace App\Jobs;

use App\Models\ReportExport;
use App\Services\Reports\ReportQueryFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Throwable;

class ExportReportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $exportId)
    {
    }

    public function handle(ReportQueryFactory $factory): void
    {
        $export = ReportExport::query()->findOrFail($this->exportId);
        $query = $factory->make($export->type);
        $directory = storage_path('app/reports');

        File::ensureDirectoryExists($directory);

        $filePath = 'reports/'.$export->guid.'.csv';
        $absolutePath = storage_path('app/'.$filePath);
        $handle = fopen($absolutePath, 'w');

        if ($handle === false) {
            throw new \RuntimeException('Unable to create report export file.');
        }

        $rowCount = 0;

        try {
            $export->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            fputcsv($handle, $query->exportHeadings());

            $query->exportRows($export->filters ?? [])
                ->each(function (object $row) use ($handle, $query, &$rowCount): void {
                    fputcsv($handle, $query->formatRow($row));
                    $rowCount++;
                });

            fclose($handle);

            $export->update([
                'status' => 'done',
                'file_path' => $filePath,
                'row_count' => $rowCount,
                'finished_at' => now(),
                'expired_at' => now()->addDays(7),
            ]);
        } catch (Throwable $exception) {
            fclose($handle);

            if (File::exists($absolutePath)) {
                File::delete($absolutePath);
            }

            $export->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            throw $exception;
        }
    }
}
