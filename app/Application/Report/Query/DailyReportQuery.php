<?php

namespace App\Application\Report\Query;

use App\Application\Report\DTO\DailyReport;
use DateTimeImmutable;

/**
 * Core feature #4: the daily sales report.
 *
 * Read only (light CQRS): aggregation happens in the database rather than
 * through domain aggregates, because there is no business rule to protect here.
 */
interface DailyReportQuery
{
    /** The low-stock list and the day's sales come one page at a time. */
    public function for(
        DateTimeImmutable $date,
        int $lowStockThreshold,
        int $lowStockPage = 1,
        int $historyPage = 1,
        int $perPage = 10,
    ): DailyReport;
}
