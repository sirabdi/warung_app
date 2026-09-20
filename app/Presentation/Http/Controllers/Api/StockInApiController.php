<?php

namespace App\Presentation\Http\Controllers\Api;

use App\Application\Inventory\Query\StockInHistory;
use App\Application\Inventory\UseCase\RecordStockIn;
use App\Presentation\Http\Controllers\Controller;
use App\Presentation\Http\Requests\RecordStockInRequest;
use Illuminate\Http\JsonResponse;

class StockInApiController extends Controller
{
    public function index(StockInHistory $history): JsonResponse
    {
        return response()->json(['history' => $history->latest()]);
    }

    public function store(RecordStockInRequest $request, RecordStockIn $recordStockIn): JsonResponse
    {
        $result = $recordStockIn->execute(
            $request->integer('product_id'),
            $request->integer('qty'),
            $request->user()?->id,
        );

        return response()->json([
            'message' => "+{$result->qty} {$result->productName} (stok sekarang {$result->currentStock}).",
            'current_stock' => $result->currentStock,
        ], 201);
    }
}
