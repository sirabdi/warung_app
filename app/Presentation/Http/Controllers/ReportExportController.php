<?php

namespace App\Presentation\Http\Controllers;

use App\Application\Report\Query\SalesExportQuery;
use App\Infrastructure\Export\DailyReportWorkbook;
use App\Presentation\Http\Requests\ListRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** The report's day as an .xlsx download, for the same date the page shows. */
class ReportExportController extends Controller
{
    public function __invoke(ListRequest $request, SalesExportQuery $export): BinaryFileResponse
    {
        $date = ($request->date('date') ?? today())->toImmutable();
        $threshold = (int) config('warung.low_stock_threshold');

        $store = $request->user()->store;
        $workbook = new DailyReportWorkbook(
            $store->name,
            $store->address,
            $date,
            now()->toImmutable(),
            $export->lines($date),
            $export->lowStock($threshold),
            $threshold,
        );

        $path = tempnam(sys_get_temp_dir(), 'report');
        $workbook->saveTo($path);

        return response()->download($path, $workbook->fileName())->deleteFileAfterSend();
    }
}
