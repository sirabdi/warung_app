<?php

namespace App\Presentation\Http\Controllers;

use App\Application\Cashier\Query\ProductsForCashier;
use App\Application\Category\Query\CategoryList;
use App\Presentation\Http\Requests\ProductListRequest;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders the page shell. The first page of products is also sent along as
 * initial data for TanStack Query, so the till is usable before the first
 * fetch finishes. Every change goes through the JSON API (see
 * Api\CashierApiController).
 */
class CashierController extends Controller
{
    public const PER_PAGE = 15;

    public function index(ProductListRequest $request, ProductsForCashier $products, CategoryList $categories): Response
    {
        return Inertia::render('Cashier', [
            'products' => $products
                ->paginate($request->search(), $request->categoryId(), $request->page(), $request->perPage(self::PER_PAGE))
                ->toArray(),
            'categories' => $categories->all(),
        ]);
    }
}
