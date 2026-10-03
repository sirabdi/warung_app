<?php

namespace App\Presentation\Http\Controllers\Api;

use App\Application\Category\Query\CategoryList;
use App\Application\Category\UseCase\AddCategory;
use App\Application\Category\UseCase\DeleteCategory;
use App\Application\Category\UseCase\RenameCategory;
use App\Presentation\Http\Controllers\Controller;
use App\Presentation\Http\Requests\ListRequest;
use App\Presentation\Http\Requests\SaveCategoryRequest;
use Illuminate\Http\JsonResponse;

class CategoryApiController extends Controller
{
    public function index(ListRequest $request, CategoryList $categories): JsonResponse
    {
        return response()->json($categories->paginate($request->page(), $request->perPage())->toArray());
    }

    /** Every category at once, for the pickers on the product and cashier pages. */
    public function options(CategoryList $categories): JsonResponse
    {
        return response()->json(['categories' => $categories->all()]);
    }

    public function store(SaveCategoryRequest $request, AddCategory $addCategory): JsonResponse
    {
        $category = $addCategory->execute($request->name());

        return response()->json(['message' => "Kategori {$category->name()} ditambahkan.", 'id' => $category->id()], 201);
    }

    public function update(SaveCategoryRequest $request, int $category, RenameCategory $renameCategory): JsonResponse
    {
        $saved = $renameCategory->execute($category, $request->name());

        return response()->json(['message' => "Kategori {$saved->name()} disimpan."]);
    }

    public function destroy(int $category, DeleteCategory $deleteCategory): JsonResponse
    {
        $deleted = $deleteCategory->execute($category);

        return response()->json(['message' => "Kategori {$deleted->name()} dihapus."]);
    }
}
