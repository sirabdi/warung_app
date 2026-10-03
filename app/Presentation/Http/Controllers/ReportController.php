<?php

namespace App\Presentation\Http\Controllers;

use App\Application\Report\Query\DailyReportQuery;
use App\Presentation\Http\Requests\ListRequest;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(ListRequest $request, DailyReportQuery $dailyReport): Response
    {
        $date = $request->date('date') ?? today();
        $lowStockThreshold = (int) config('warung.low_stock_threshold');

        $report = $dailyReport->for(
            $date->toDateTimeImmutable(),
            $lowStockThreshold,
            $request->pageOf('low_page'),
            $request->pageOf('history_page'),
            $request->perPage(),
        );

        return Inertia::render('Report', [
            'date' => $date->toDateString(),
            'isToday' => $date->isToday(),
            'summary' => $report->summary->toArray(),
            'bestSellers' => $report->bestSellers,
            'lowStock' => $report->lowStock->toArray(),
            'lowStockThreshold' => $lowStockThreshold,
            'history' => $report->history->toArray(),
        ]);
    }
}
