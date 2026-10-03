<?php

namespace App\Presentation\Http\Controllers;

use App\Application\Inventory\Query\StockInHistory;
use App\Application\Product\Query\ProductList;
use App\Presentation\Http\Requests\ListRequest;
use Inertia\Inertia;
use Inertia\Response;

class StockInController extends Controller
{
    // Products for the picker are searched over the API when the dialog opens.
    // ?product=12 opens it with that product picked ("Tambah stok" on Produk).
    public function index(ListRequest $request, StockInHistory $history, ProductList $products): Response
    {
        return Inertia::render('StockIn', [
            'history' => $history->paginate($request->page(), $request->perPage())->toArray(),
            'selectedProduct' => $request->filled('product') ? $products->find($request->integer('product')) : null,
        ]);
    }
}
