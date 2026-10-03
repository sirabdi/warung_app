<?php

namespace App\Presentation\Http\Controllers\Api;

use App\Application\Cashier\Query\ProductsForCashier;
use App\Application\Cashier\UseCase\RecordSale;
use App\Presentation\Http\Controllers\CashierController;
use App\Presentation\Http\Controllers\Controller;
use App\Presentation\Http\Requests\ProductListRequest;
use App\Presentation\Http\Requests\RecordSaleRequest;
use Illuminate\Http\JsonResponse;

class CashierApiController extends Controller
{
    public function index(ProductListRequest $request, ProductsForCashier $products): JsonResponse
    {
        return response()->json($products->paginate(
            $request->search(),
            $request->categoryId(),
            $request->page(),
            $request->perPage(CashierController::PER_PAGE),
        )->toArray());
    }

    public function store(RecordSaleRequest $request, RecordSale $recordSale): JsonResponse
    {
        $result = $recordSale->execute($request->cart(), $request->user()?->id);

        return response()->json([
            'message' => 'Terjual '.$result->total->format(),
            'code' => $result->code,
            'total' => $result->total->amount,
            'item_count' => $result->itemCount,
        ], 201);
    }
}
