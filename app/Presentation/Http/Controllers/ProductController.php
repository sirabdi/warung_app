<?php

namespace App\Presentation\Http\Controllers;

use App\Application\Product\Query\ProductList;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(ProductList $products): Response
    {
        return Inertia::render('Products', [
            'products' => $products->all(),
        ]);
    }
}
