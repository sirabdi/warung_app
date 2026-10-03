<?php

namespace App\Presentation\Http\Controllers;

use App\Application\Category\Query\CategoryList;
use App\Application\Product\Query\ProductList;
use App\Presentation\Http\Requests\ProductListRequest;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(ProductListRequest $request, ProductList $products, CategoryList $categories): Response
    {
        return Inertia::render('Products', [
            'products' => $products
                ->paginate($request->search(), $request->page(), $request->perPage(), $request->categoryId())
                ->toArray(),
            'categories' => $categories->all(),
            'filters' => ['search' => $request->search(), 'category' => $request->categoryId()],
        ]);
    }
}
