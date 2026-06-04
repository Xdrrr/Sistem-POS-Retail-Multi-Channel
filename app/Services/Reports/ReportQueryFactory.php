<?php

namespace App\Services\Reports;

use InvalidArgumentException;

class ReportQueryFactory
{
    public const TYPES = [
        'sales' => SalesReportQuery::class,
        'payments' => PaymentReportQuery::class,
        'products' => ProductReportQuery::class,
        'financial' => FinancialReportQuery::class,
        'customers' => CustomerReportQuery::class,
        'status' => OrderStatusReportQuery::class,
        'catalog' => CatalogReportQuery::class,
    ];

    public function make(string $type): ReportQuery
    {
        $class = self::TYPES[$type] ?? null;

        if (! $class) {
            throw new InvalidArgumentException("Unsupported report type [{$type}].");
        }

        return app($class);
    }

    public function options(): array
    {
        return collect(self::TYPES)
            ->keys()
            ->map(function (string $type): array {
                $query = $this->make($type);

                return [
                    'key' => $type,
                    'title' => $query->title(),
                    'columns' => $query->columns(),
                ];
            })
            ->values()
            ->all();
    }
}
