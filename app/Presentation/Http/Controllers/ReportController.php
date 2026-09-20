<?php

namespace App\Presentation\Http\Controllers;

use App\Application\Report\Query\DailyReportQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request, DailyReportQuery $dailyReport): Response
    {
        $date = $request->date('date') ?? today();
        $lowStockThreshold = (int) config('warung.low_stock_threshold');

        $report = $dailyReport->for($date->toDateTimeImmutable(), $lowStockThreshold);

        return Inertia::render('Report', [
            'date' => $date->toDateString(),
            'isToday' => $date->isToday(),
            'summary' => $report->summary->toArray(),
            'bestSellers' => $report->bestSellers,
            'lowStock' => $report->lowStock,
            'lowStockThreshold' => $lowStockThreshold,
            'history' => $report->history,
        ]);
    }
}
