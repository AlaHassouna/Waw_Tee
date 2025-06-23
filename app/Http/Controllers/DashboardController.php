<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use App\Models\PaymentError;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function getStats()
    {
        try {
            Log::info('Dashboard stats requested');

            // Basic counts
            $totalUsers = User::count();
            $totalOrders = Order::count();
            $totalProducts = Product::count();
            $totalCategories = Category::count();
            
            $totalRevenue = Order::where('payment_status', 'paid')->sum('total') ?? 0;
            $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
            
            Log::info('Basic stats calculated', [
                'users' => $totalUsers,
                'orders' => $totalOrders,
                'products' => $totalProducts,
                'categories' => $totalCategories,
                'revenue' => $totalRevenue
            ]);

            // Order status stats
            $orderStatusStats = Order::select('status', DB::raw('count(*) as count'))
                                    ->groupBy('status')
                                    ->get()
                                    ->map(function ($item) {
                                        return [
                                            '_id' => $item->status,
                                            'count' => $item->count
                                        ];
                                    });

            // Monthly stats for the last 12 months
            $monthlyStats = Order::where('payment_status', 'paid')
                                ->where('created_at', '>=', now()->subMonths(12))
                                ->select(
                                    DB::raw('YEAR(created_at) as year'),
                                    DB::raw('MONTH(created_at) as month'),
                                    DB::raw('COUNT(*) as orders'),
                                    DB::raw('SUM(total) as revenue')
                                )
                                ->groupBy('year', 'month')
                                ->orderBy('year', 'asc')
                                ->orderBy('month', 'asc')
                                ->get()
                                ->map(function ($item) {
                                    return [
                                        '_id' => [
                                            'year' => (int)$item->year,
                                            'month' => (int)$item->month
                                        ],
                                        'orders' => (int)$item->orders,
                                        'revenue' => (float)($item->revenue ?? 0)
                                    ];
                                });

            // Daily sales for the last 7 days
            $dailySales = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dayOrders = Order::where('payment_status', 'paid')
                                ->whereDate('created_at', $date->format('Y-m-d'))
                                ->get();
                
                $dailySales[] = [
                    'name' => $date->format('D'), // Mon, Tue, etc.
                    'sales' => (float)$dayOrders->sum('total'),
                    'orders' => $dayOrders->count(),
                    'date' => $date->format('Y-m-d')
                ];
            }

            // Top products - simplified query
            $topProducts = Product::with('category')
                                 ->orderBy('sales_count', 'desc')
                                 ->limit(10)
                                 ->get()
                                 ->map(function ($product) {
                                     return [
                                         '_id' => $product->id,
                                         'productTitle' => $product->title,
                                         'categoryTitle' => $product->category->title ?? 'Unknown',
                                         'totalSold' => $product->sales_count ?? 0,
                                         'totalRevenue' => (float)(($product->sales_count ?? 0) * ($product->price ?? 0))
                                     ];
                                 });

            // Recent orders with user info
            $recentOrders = Order::with(['user:id,name,email'])
                                ->orderBy('created_at', 'desc')
                                ->limit(10)
                                ->get()
                                ->map(function ($order) {
                                    return [
                                        '_id' => $order->id,
                                        'orderNumber' => $order->order_number ?? 'ORD-' . $order->id,
                                        'total' => (float)($order->total ?? 0),
                                        'status' => $order->status ?? 'pending',
                                        'createdAt' => $order->created_at->toISOString(),
                                        'user' => [
                                            'name' => $order->user->name ?? 'Unknown',
                                            'email' => $order->user->email ?? 'unknown@email.com'
                                        ],
                                        'items' => []
                                    ];
                                });

            // User growth for the last 12 months
            $userGrowth = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $newUsers = User::whereYear('created_at', $date->year)
                              ->whereMonth('created_at', $date->month)
                              ->count();
                
                $userGrowth[] = [
                    '_id' => [
                        'year' => $date->year,
                        'month' => $date->month
                    ],
                    'newUsers' => $newUsers
                ];
            }

            // Category stats
            $categoryStats = Category::with('products')
                                   ->get()
                                   ->map(function ($category) {
                                       return [
                                           '_id' => $category->id,
                                           'categoryName' => $category->title,
                                           'productCount' => $category->products->count(),
                                           'revenue' => 0, // Will be calculated later if needed
                                           'totalSold' => 0, // Will be calculated later if needed
                                           'value' => $category->products->count() // For pie chart
                                       ];
                                   });

            $response = [
                'status' => 'success',
                'data' => [
                    'overview' => [
                        'totalProducts' => $totalProducts,
                        'totalCategories' => $totalCategories,
                        'totalUsers' => $totalUsers,
                        'totalOrders' => $totalOrders,
                        'totalRevenue' => (float)$totalRevenue,
                        'avgOrderValue' => (float)$avgOrderValue,
                    ],
                    'orderStatusStats' => $orderStatusStats,
                    'monthlyStats' => $monthlyStats,
                    'dailySales' => $dailySales,
                    'topProducts' => $topProducts,
                    'recentOrders' => $recentOrders,
                    'userGrowth' => $userGrowth,
                    'categoryStats' => $categoryStats,
                ],
            ];

            Log::info('Dashboard response prepared', ['data_keys' => array_keys($response['data'])]);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Dashboard stats error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while fetching dashboard stats: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getSalesAnalytics(Request $request)
    {
        try {
            $period = $request->get('period', '30d');
            
            $days = match($period) {
                '7d' => 7,
                '30d' => 30,
                '90d' => 90,
                '1y' => 365,
                default => 30
            };

            // Daily sales for the specified period
            $dailySales = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dayOrders = Order::where('payment_status', 'paid')
                                ->whereDate('created_at', $date->format('Y-m-d'))
                                ->get();
                
                $dailySales[] = [
                    '_id' => [
                        'year' => $date->year,
                        'month' => $date->month,
                        'day' => $date->day
                    ],
                    'orders' => $dayOrders->count(),
                    'revenue' => (float)$dayOrders->sum('total'),
                    'avgOrderValue' => $dayOrders->count() > 0 ? (float)($dayOrders->sum('total') / $dayOrders->count()) : 0
                ];
            }

            // Product performance
            $productPerformance = Product::orderBy('sales_count', 'desc')
                                       ->limit(20)
                                       ->get()
                                       ->map(function ($product) {
                                           return [
                                               '_id' => $product->id,
                                               'productTitle' => $product->title,
                                               'totalSold' => $product->sales_count ?? 0,
                                               'totalRevenue' => (float)(($product->sales_count ?? 0) * ($product->price ?? 0))
                                           ];
                                       });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'dailySales' => $dailySales,
                    'productPerformance' => $productPerformance,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Sales analytics error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while fetching sales analytics: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getRecentOrders(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);

            $orders = Order::with(['user:id,name,email'])
                          ->orderBy('created_at', 'desc')
                          ->limit($limit)
                          ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'orders' => $orders,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Recent orders error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while fetching recent orders',
            ], 500);
        }
    }

    public function getTopProducts(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);

            $products = Product::with('category:id,title')
                              ->orderBy('sales_count', 'desc')
                              ->orderBy('view_count', 'desc')
                              ->limit($limit)
                              ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'products' => $products,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Top products error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while fetching top products',
            ], 500);
        }
    }
}
