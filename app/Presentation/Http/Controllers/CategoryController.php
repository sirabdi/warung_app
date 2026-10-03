<?php

namespace App\Presentation\Http\Controllers;

use App\Application\Category\Query\CategoryList;
use App\Presentation\Http\Requests\ListRequest;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(ListRequest $request, CategoryList $categories): Response
    {
        return Inertia::render('Categories', [
            'categories' => $categories->paginate($request->page(), $request->perPage())->toArray(),
        ]);
    }
}
