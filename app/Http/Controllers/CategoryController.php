<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);
            $active = $request->get('active');

            $query = Category::query();

            if ($active === 'true') {
                $query->where('is_active', true);
            }

            $categories = $query->orderBy('sort_order')
                               ->orderBy('title')
                               ->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'categories' => $categories->items(),
                    'pagination' => [
                        'page' => $categories->currentPage(),
                        'limit' => $categories->perPage(),
                        'total' => $categories->total(),
                        'pages' => $categories->lastPage(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while fetching categories',
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $category = Category::with(['products' => function ($query) {
                $query->where('is_active', true)->limit(10);
            }])->find($id);

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'category' => $category,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while fetching category',
            ], 500);
        }
    }

    public function getProductsBySlug($slug, Request $request)
    {
        try {
            $category = Category::where('slug', $slug)->first();

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category not found',
                ], 404);
            }

            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);
            $sortBy = $request->get('sortBy', 'created_at');
            $sortOrder = $request->get('sortOrder', 'desc');

            $products = Product::where('category_id', $category->id)
                              ->where('is_active', true)
                              ->orderBy($sortBy, $sortOrder)
                              ->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'category' => $category,
                    'products' => $products->items(),
                    'pagination' => [
                        'page' => $products->currentPage(),
                        'limit' => $products->perPage(),
                        'total' => $products->total(),
                        'pages' => $products->lastPage(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while fetching products',
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:100|unique:categories',
            'description' => 'sometimes|string|max:500',
            'image' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer',
            'meta_title' => 'sometimes|string|max:60',
            'meta_description' => 'sometimes|string|max:160',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $data = $request->all();
            $data['slug'] = Str::slug($request->title);

            $category = Category::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Category created successfully',
                'data' => [
                    'category' => $category,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while creating category',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:100|unique:categories,title,' . $id,
            'description' => 'sometimes|string|max:500',
            'image' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer',
            'meta_title' => 'sometimes|string|max:60',
            'meta_description' => 'sometimes|string|max:160',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category not found',
                ], 404);
            }

            $data = $request->all();
            if ($request->has('title')) {
                $data['slug'] = Str::slug($request->title);
            }

            $category->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Category updated successfully',
                'data' => [
                    'category' => $category,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while updating category',
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category not found',
                ], 404);
            }

            // Check if category has products
            if ($category->products()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete category with products',
                ], 400);
            }

            $category->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Category deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while deleting category',
            ], 500);
        }
    }
}
