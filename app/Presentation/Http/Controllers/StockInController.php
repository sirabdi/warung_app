<?php

namespace App\Presentation\Http\Controllers;

use App\Application\Inventory\Query\StockInHistory;
use App\Application\Product\Query\ProductList;
use Inertia\Inertia;
use Inertia\Response;

class StockInController extends Controller
{
    public function index(ProductList $products, StockInHistory $history): Response
    {
        return Inertia::render('StockIn', [
            'products' => $products->all(),
            'history' => $history->latest(),
        ]);
    }
}
