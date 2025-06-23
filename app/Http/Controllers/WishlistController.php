<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class WishlistController extends Controller
{
    public function index()
    {
        try {
            // Étape 1: Vérifier l'authentification
            $user = auth()->user();
            Log::info('Wishlist request - User:', ['user_id' => $user ? $user->id : 'null']);
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated',
                ], 401);
            }

            // Étape 2: Créer ou récupérer la wishlist
            $wishlist = Wishlist::firstOrCreate(['user_id' => $user->id]);
            Log::info('Wishlist found/created:', ['wishlist_id' => $wishlist->id]);

            // Étape 3: Version simplifiée sans relations complexes
            try {
                $wishlist->load(['products' => function ($query) {
                    $query->where('is_active', true)
                          ->select('id', 'title', 'slug', 'price', 'compare_price', 'images', 'category_id', 'is_active');
                }]);
                Log::info('Products loaded successfully');
            } catch (\Exception $e) {
                Log::error('Error loading products:', ['error' => $e->getMessage()]);
                // Fallback: charger sans conditions
                $wishlist->load('products');
            }

            // Étape 4: Charger les catégories séparément si nécessaire
            foreach ($wishlist->products as $product) {
                if ($product->category_id) {
                    try {
                        $product->load('category:id,title,slug');
                    } catch (\Exception $e) {
                        Log::error('Error loading category for product ' . $product->id, ['error' => $e->getMessage()]);
                        // Créer une catégorie par défaut
                        $product->category = (object) [
                            'id' => $product->category_id,
                            'title' => 'Unknown Category',
                            'slug' => 'unknown'
                        ];
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'wishlist' => $wishlist,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Wishlist error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Server error: ' . $e->getMessage(),
                'debug' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null
            ], 500);
        }
    }

    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'productId' => 'required|exists:products,id',
            'variant' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $user = auth()->user();
            $productId = $request->productId;
            $variant = $request->variant;

            // Check if product is active
            $product = Product::where('id', $productId)
                             ->where('is_active', true)
                             ->first();

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found or inactive',
                ], 404);
            }

            // Get or create wishlist
            $wishlist = Wishlist::firstOrCreate(['user_id' => $user->id]);

            // Check if product already exists in wishlist
            $existingItem = $wishlist->products()
                                   ->where('product_id', $productId)
                                   ->where('variant', $variant)
                                   ->first();

            if ($existingItem) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product already in wishlist',
                ], 400);
            }

            // Add product to wishlist
            $wishlist->products()->attach($productId, [
                'variant' => $variant,
                'added_at' => now(),
            ]);

            $wishlist->load('products');

            return response()->json([
                'status' => 'success',
                'message' => 'Product added to wishlist',
                'data' => [
                    'wishlist' => $wishlist,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Add to wishlist error:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while adding to wishlist: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function remove($productId)
    {
        try {
            $user = auth()->user();
            $wishlist = Wishlist::where('user_id', $user->id)->first();

            if (!$wishlist) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Wishlist not found',
                ], 404);
            }

            $removed = $wishlist->products()->detach($productId);

            if (!$removed) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found in wishlist',
                ], 404);
            }

            $wishlist->load('products');

            return response()->json([
                'status' => 'success',
                'message' => 'Product removed from wishlist',
                'data' => [
                    'wishlist' => $wishlist,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Remove from wishlist error:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while removing from wishlist: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function check($productId, Request $request)
    {
        try {
            $user = auth()->user();
            $variant = $request->query('variant');
            
            $wishlist = Wishlist::where('user_id', $user->id)->first();
            
            if (!$wishlist) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'inWishlist' => false,
                    ],
                ]);
            }

            $query = $wishlist->products()->where('product_id', $productId);
            
            if ($variant) {
                $query->where('variant', $variant);
            }
            
            $inWishlist = $query->exists();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'inWishlist' => $inWishlist,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Check wishlist error:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while checking wishlist: ' . $e->getMessage(),
            ], 500);
        }
    }
}
