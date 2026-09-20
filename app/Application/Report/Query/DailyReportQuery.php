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
    public function for(DateTimeImmutable $date, int $lowStockThreshold): DailyReport;
}
