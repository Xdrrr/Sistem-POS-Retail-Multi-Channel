<?php

namespace App\Jobs;

use App\Models\ReportExport;
use App\Services\Reports\ReportQueryFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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

        $extension = $export->format === 'xlsx' ? 'xlsx' : 'csv';
        $filePath = 'reports/'.$export->guid.'.'.$extension;
        $absolutePath = storage_path('app/'.$filePath);

        $rowCount = 0;

        try {
            $export->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            if ($export->format === 'xlsx') {
                $rowCount = $this->writeXlsx($query, $export, $absolutePath);
            } else {
                $rowCount = $this->writeCsv($query, $export, $absolutePath);
            }

            $export->update([
                'status' => 'done',
                'file_path' => $filePath,
                'row_count' => $rowCount,
                'finished_at' => now(),
                'expired_at' => now()->addDays(7),
            ]);
        } catch (Throwable $exception) {
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

    private function writeCsv($query, $export, string $absolutePath): int
    {
        $handle = fopen($absolutePath, 'w');

        if ($handle === false) {
            throw new \RuntimeException('Unable to create export file.');
        }

        fputcsv($handle, $query->exportHeadings());
        $rowCount = 0;

        $query->exportRows($export->filters ?? [])
            ->each(function (object $row) use ($handle, $query, &$rowCount): void {
                fputcsv($handle, $query->formatRow($row));
                $rowCount++;
            });

        fclose($handle);

        return $rowCount;
    }

    private function writeXlsx($query, $export, string $absolutePath): int
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $colLetters = [];
        $headings = $query->exportHeadings();
        foreach ($headings as $colIndex => $heading) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $colLetters[] = $colLetter;
            $sheet->setCellValue($colLetter . '1', $heading);
            $sheet->getStyle($colLetter . '1')->getFont()->setBold(true);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $rowCount = 0;

        $query->exportRows($export->filters ?? [])
            ->each(function (object $row) use ($sheet, $query, &$rowCount, $colLetters): void {
                $rowCount++;
                $values = $query->formatRow($row);
                foreach ($values as $colIndex => $value) {
                    $sheet->setCellValue($colLetters[$colIndex] . ($rowCount + 1), $value);
                }
            });

        $writer = new Xlsx($spreadsheet);
        $writer->save($absolutePath);
        $spreadsheet->disconnectWorksheets();

        return $rowCount;
    }
}
