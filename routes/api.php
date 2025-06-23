<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UploadController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication required)
// Auth routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Public product routes
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products/category/{categoryId}', [ProductController::class, 'getByCategory']);

// Public category routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

// Public shipping routes
Route::get('/shipping/rates', [ShippingController::class, 'getRates']);
Route::post('/shipping/calculate', [ShippingController::class, 'calculateShipping']);

// Public order routes (for guest checkout) - EXPLICITLY PUBLIC
Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::get('/orders/track/{orderNumber}', [OrderController::class, 'trackByNumber'])->name('orders.track');
Route::post('/orders/track', [OrderController::class, 'trackByNumberAndEmail'])->name('orders.track.email');

// Public payment routes (for guest checkout) - EXPLICITLY PUBLIC
Route::post('/payments/create-intent', [PaymentController::class, 'createPaymentIntent'])->name('payments.create-intent');
Route::post('/payments/confirm', [PaymentController::class, 'confirmPayment'])->name('payments.confirm');

// PayPal payment routes (PUBLIC - for guest checkout)
Route::post('/payments/paypal/create-order', [PaymentController::class, 'createPayPalOrder'])->name('payments.paypal.create-order');
Route::post('/payments/paypal/capture-order', [PaymentController::class, 'capturePayPalOrder'])->name('payments.paypal.capture-order');

// Contact form
Route::post('/contact', [ContactController::class, 'submit']);

// File uploads (public for product images, etc.)
Route::post('/upload', [UploadController::class, 'upload']);

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->group(function () {
    // Auth user info
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

    // User orders (authenticated users only)
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');

    // Wishlist routes
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{productId}', [WishlistController::class, 'destroy']);
    Route::get('/wishlist/check/{productId}', [WishlistController::class, 'check']);

    // User profile routes - for the authenticated user to manage their own profile
    Route::get('/profile', [UserController::class, 'getProfile']);
    Route::put('/profile', [UserController::class, 'updateProfile']); // SECURE - no role modification allowed
    Route::get('/profile/orders', [UserController::class, 'getOrders']);
    Route::get('/profile/orders/{id}', [UserController::class, 'getOrder']);

    // Route pour /users/profile qui était manquante
    Route::put('/users/profile', [UserController::class, 'updateProfile']); // SECURE - same as /profile

    // Product management for authenticated users (admin only)
    Route::post('/products', [ProductController::class, 'store'])->middleware(['auth:sanctum', 'admin']);
    Route::put('/products/{id}', [ProductController::class, 'update'])->middleware(['auth:sanctum', 'admin']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->middleware(['auth:sanctum', 'admin']);

    // Category management for authenticated users (admin only)
    Route::post('/categories', [CategoryController::class, 'store'])->middleware(['auth:sanctum', 'admin']);
    Route::put('/categories/{id}', [CategoryController::class, 'update'])->middleware(['auth:sanctum', 'admin']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->middleware(['auth:sanctum', 'admin']);
});

// Admin routes (admin authentication required)
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Dashboard routes
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
    Route::get('/dashboard/analytics', [DashboardController::class, 'getSalesAnalytics']);
    Route::get('/dashboard/recent-orders', [DashboardController::class, 'getRecentOrders']);
    Route::get('/dashboard/top-products', [DashboardController::class, 'getTopProducts']);

    // Product management
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Category management
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // Order management
    Route::get('/orders', [OrderController::class, 'adminIndex']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{id}', [OrderController::class, 'update']);
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']); // CORRECTION: Route manquante
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);

    // User management (admin only)
    Route::get('/users', [UserController::class, 'index']); // CORRECTION: Route manquante
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']); // Admin can update any user (including role)
    Route::put('/users/{id}/status', [UserController::class, 'updateStatus']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Shipping management
    Route::get('/shipping/rates', [ShippingController::class, 'index']);
    Route::post('/shipping/rates', [ShippingController::class, 'store']);
    Route::get('/shipping/rates/{id}', [ShippingController::class, 'show']);
    Route::put('/shipping/rates/{id}', [ShippingController::class, 'update']);
    Route::delete('/shipping/rates/{id}', [ShippingController::class, 'destroy']);

    // Payment errors
    Route::get('/payment-errors', [PaymentController::class, 'getErrors']);
});

// Fallback route for API
Route::fallback(function () {
    return response()->json([
        'message' => 'API endpoint not found'
    ], 404);
});
