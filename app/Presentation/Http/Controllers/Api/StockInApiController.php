<?php

namespace App\Presentation\Http\Controllers\Api;

use App\Application\Inventory\Query\StockInHistory;
use App\Application\Inventory\UseCase\RecordStockIn;
use App\Presentation\Http\Controllers\Controller;
use App\Presentation\Http\Requests\ListRequest;
use App\Presentation\Http\Requests\RecordStockInRequest;
use Illuminate\Http\JsonResponse;

class StockInApiController extends Controller
{
    public function index(ListRequest $request, StockInHistory $history): JsonResponse
    {
        return response()->json($history->paginate($request->page(), $request->perPage())->toArray());
    }

    public function store(RecordStockInRequest $request, RecordStockIn $recordStockIn): JsonResponse
    {
        $result = $recordStockIn->execute(
            $request->integer('product_id'),
            $request->integer('qty'),
            $request->user()?->id,
        );

        return response()->json([
            'message' => sprintf(
                '+%s %s (stok sekarang %s).',
                $result->unit->format($result->qty),
                $result->productName,
                $result->unit->format($result->currentStock),
            ),
            'current_stock' => $result->currentStock,
        ], 201);
    }
}
