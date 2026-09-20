<?php

namespace App\Presentation\Http\Controllers;

use App\Application\Cashier\Query\ProductsForCashier;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders the page shell. The product list is also sent along as initial data
 * for TanStack Query, so the till is usable before the first fetch finishes.
 * Every change goes through the JSON API (see Api\CashierApiController).
 */
class CashierController extends Controller
{
    public function index(ProductsForCashier $products): Response
    {
        return Inertia::render('Cashier', [
            'products' => $products->get(),
        ]);
    }
}
