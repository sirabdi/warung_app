<?php

namespace App\Presentation\Http\Controllers\Api;

use App\Application\Report\Query\DailyReportQuery;
use App\Presentation\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportApiController extends Controller
{
    public function index(Request $request, DailyReportQuery $dailyReport): JsonResponse
    {
        $date = $request->date('date') ?? today();
        $lowStockThreshold = (int) config('warung.low_stock_threshold');

        $report = $dailyReport->for($date->toDateTimeImmutable(), $lowStockThreshold);

        return response()->json([
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
