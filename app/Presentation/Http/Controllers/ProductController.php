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

    public function create(CategoryList $categories): Response
    {
        return Inertia::render('ProductForm', [
            'product' => null,
            'categories' => $categories->all(),
        ]);
    }

    public function edit(int $product, ProductList $products, CategoryList $categories): Response
    {
        return Inertia::render('ProductForm', [
            // Another store's product is not found either: the list is store-scoped.
            'product' => $products->find($product) ?? abort(404),
            'categories' => $categories->all(),
        ]);
    }
}
