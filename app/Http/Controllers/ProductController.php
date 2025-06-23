<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 12);
            $category = $request->get('category');
            $search = $request->get('search');
            $minPrice = $request->get('minPrice');
            $maxPrice = $request->get('maxPrice');
            $tags = $request->get('tags');
            $variant = $request->get('variant');
            $featured = $request->get('featured');
            $sort = $request->get('sort', '-created_at');

            $query = Product::where('is_active', true);

            if ($category) {
                $categoryDoc = Category::where('slug', $category)->first();
                if ($categoryDoc) {
                    $query->where('category_id', $categoryDoc->id);
                }
            }

            if ($search) {
                $query->search($search);
            }

            if ($minPrice || $maxPrice) {
                if ($minPrice) $query->where('price', '>=', $minPrice);
                if ($maxPrice) $query->where('price', '<=', $maxPrice);
            }

            if ($tags) {
                $tagsArray = explode(',', $tags);
                $query->where(function ($q) use ($tagsArray) {
                    foreach ($tagsArray as $tag) {
                        $q->orWhereJsonContains('tags', $tag);
                    }
                });
            }

            if ($variant) {
                $query->whereJsonContains('variants', [['type' => $variant]]);
            }

            if ($featured === 'true') {
                $query->where('is_featured', true);
            }

            $orderBy = 'created_at';
            $orderDirection = 'desc';
            if ($sort === '-created_at') {
                $orderBy = 'created_at';
                $orderDirection = 'desc';
            }

            $products = $query->with('category:id,title,slug')
                             ->orderBy($orderBy, $orderDirection)
                             ->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => 'success',
                'data' => [
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
            Log::error('Error fetching products: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while fetching products',
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $product = Product::with('category:id,title,slug')->find($id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                ], 404);
            }

            $product->increment('view_count');

            return response()->json([
                'status' => 'success',
                'data' => [
                    'product' => $product,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching product: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while fetching product',
            ], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info('Product creation request received', $request->all());

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:100',
            'description' => 'required|string|max:1000',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'variants' => 'required|array',
            'colors' => 'required|array',
            'sizes' => 'required|array',
        ]);

        if ($validator->fails()) {
            Log::error('Product validation failed', $validator->errors()->toArray());
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            // Préparer les données avec des valeurs par défaut
            $productData = [
                'title' => $request->title,
                'slug' => $request->slug ?: \Str::slug($request->title),
                'description' => $request->description,
                'price' => $request->price,
                'category_id' => $request->category_id,
                'variants' => json_encode($request->variants),
                'colors' => json_encode($request->colors),
                'sizes' => json_encode($request->sizes),
                'tags' => json_encode($request->tags ?: []),
                'images' => json_encode($request->images ?: []),
                'sku' => $request->sku ?: 'SKU-' . time(),
                'track_quantity' => $request->track_quantity ?: false,
                'quantity' => $request->quantity ?: 0,
                'allow_backorder' => $request->allow_backorder ?: false,
                'requires_shipping' => $request->requires_shipping ?? true,
                'taxable' => $request->taxable ?? true,
                'is_active' => $request->is_active ?? true,
                'is_featured' => $request->is_featured ?? false,
                'sales_count' => 0,
                'view_count' => 0,
            ];

            Log::info('Creating product with data', $productData);

            $product = Product::create($productData);
            
            // Incrémenter le compteur de produits de la catégorie
            Category::find($product->category_id)->increment('product_count');

            $product->load('category:id,title,slug');

            Log::info('Product created successfully', ['product_id' => $product->id]);

            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully',
                'data' => [
                    'product' => $product,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating product: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while creating product: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                ], 404);
            }

            $oldCategoryId = $product->category_id;
            $product->update($request->all());

            if ($request->has('category_id') && $oldCategoryId != $request->category_id) {
                Category::find($oldCategoryId)->decrement('product_count');
                Category::find($request->category_id)->increment('product_count');
            }

            $product->load('category:id,title,slug');

            return response()->json([
                'status' => 'success',
                'message' => 'Product updated successfully',
                'data' => [
                    'product' => $product,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating product: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while updating product',
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                ], 404);
            }

            Category::find($product->category_id)->decrement('product_count');
            $product->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Product deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting product: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while deleting product',
            ], 500);
        }
    }

    public function featured()
    {
        try {
            $products = Product::where('is_active', true)
                              ->where('is_featured', true)
                              ->with('category:id,title,slug')
                              ->limit(8)
                              ->orderBy('created_at', 'desc')
                              ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'products' => $products,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching featured products: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while fetching featured products',
            ], 500);
        }
    }
}