<?php

namespace App\Presentation\Http\Controllers;

use App\Application\Inventory\Query\StockInHistory;
use App\Presentation\Http\Requests\ListRequest;
use Inertia\Inertia;
use Inertia\Response;

class StockInController extends Controller
{
    // Products for the picker are searched over the API when the dialog opens.
    public function index(ListRequest $request, StockInHistory $history): Response
    {
        return Inertia::render('StockIn', [
            'history' => $history->paginate($request->page(), $request->perPage())->toArray(),
        ]);
    }
}
