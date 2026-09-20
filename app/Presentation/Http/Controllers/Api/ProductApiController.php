<?php

namespace App\Presentation\Http\Controllers\Api;

use App\Application\Product\Query\ProductList;
use App\Application\Product\UseCase\AddProduct;
use App\Application\Product\UseCase\UpdateProduct;
use App\Presentation\Http\Controllers\Controller;
use App\Presentation\Http\Requests\SaveProductRequest;
use Illuminate\Http\JsonResponse;

class ProductApiController extends Controller
{
    public function index(ProductList $products): JsonResponse
    {
        return response()->json(['products' => $products->all()]);
    }

    public function store(SaveProductRequest $request, AddProduct $addProduct): JsonResponse
    {
        $product = $addProduct->execute($request->productData());

        return response()->json(['message' => "{$product->name()} ditambahkan."], 201);
    }

    public function update(SaveProductRequest $request, int $product, UpdateProduct $updateProduct): JsonResponse
    {
        $saved = $updateProduct->execute($product, $request->productData());

        return response()->json(['message' => "{$saved->name()} disimpan."]);
    }
}
