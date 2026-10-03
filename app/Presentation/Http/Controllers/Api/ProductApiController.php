<?php

namespace App\Presentation\Http\Controllers\Api;

use App\Application\Product\Query\ProductList;
use App\Application\Product\UseCase\AddProduct;
use App\Application\Product\UseCase\UpdateProduct;
use App\Presentation\Http\Controllers\Controller;
use App\Presentation\Http\Requests\ProductListRequest;
use App\Presentation\Http\Requests\SaveProductRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    public function index(ProductListRequest $request, ProductList $products): JsonResponse
    {
        return response()->json(
            $products
                ->paginate($request->search(), $request->page(), $request->perPage(), $request->categoryId())
                ->toArray(),
        );
    }

    /** Existing products the name being typed may duplicate: GET /api/products/similar?name=&except=. */
    public function similar(Request $request, ProductList $products): JsonResponse
    {
        $input = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'except' => ['nullable', 'integer'],
        ]);

        return response()->json([
            'data' => $products->similar($input['name'] ?? '', isset($input['except']) ? (int) $input['except'] : null),
        ]);
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
