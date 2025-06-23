<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Mail\OrderConfirmationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Get orders for authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $orders = Order::where('user_id', $user->id)
            ->with(['items.product'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    }

    /**
     * Get orders for admin
     */
    public function adminIndex(Request $request)
    {
        try {
            \Log::info('Admin orders request received', [
                'user' => $request->user() ? $request->user()->id : 'none',
                'user_role' => $request->user() ? $request->user()->role : 'none'
            ]);

            $query = Order::with(['items.product', 'user'])
                ->orderBy('created_at', 'desc');

            // Filtres optionnels
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            if ($request->has('payment_status') && $request->payment_status !== '') {
                $query->where('payment_status', $request->payment_status);
            }

            if ($request->has('search') && $request->search !== '') {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhereHas('user', function($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            $perPage = $request->get('per_page', 20);
            $orders = $query->paginate($perPage);

            \Log::info('Admin orders fetched successfully', [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage()
            ]);

            return response()->json([
                'success' => true,
                'orders' => $orders->items(),
                'pagination' => [
                    'total' => $orders->total(),
                    'per_page' => $orders->perPage(),
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching admin orders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new order (public route for guest checkout)
     */
    public function store(Request $request)
    {
        \Log::info('Order creation request received', [
            'request_data' => $request->all(),
            'auth_user' => Auth::user() ? Auth::user()->id : 'guest',
        ]);

        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.productId' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.size' => 'required|string',
            'items.*.color' => 'required',
            'items.*.variant' => 'required|string',
            'items.*.price' => 'required|numeric|min:0',
            'shippingAddress' => 'required|array',
            'shippingAddress.firstName' => 'required|string|max:255',
            'shippingAddress.lastName' => 'required|string|max:255',
            'shippingAddress.email' => 'required|email|max:255',
            'shippingAddress.phone' => 'required|string|max:20',
            'shippingAddress.street' => 'required|string|max:255',
            'shippingAddress.city' => 'required|string|max:255',
            'shippingAddress.state' => 'required|string|max:255',
            'shippingAddress.zipCode' => 'required|string|max:20',
            'shippingAddress.country' => 'required|string|max:2',
            'paymentMethod' => 'required|in:stripe,paypal',
            'shippingCost' => 'required|numeric|min:0',
            'taxAmount' => 'required|numeric|min:0',
            'totalAmount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'userId' => 'nullable|exists:users,id'
        ]);

        if ($validator->fails()) {
            \Log::error('Order validation failed', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Generate unique order number
            $orderNumber = 'ORD-' . strtoupper(Str::random(8));
            while (Order::where('order_number', $orderNumber)->exists()) {
                $orderNumber = 'ORD-' . strtoupper(Str::random(8));
            }

            $shippingAddress = $request->input('shippingAddress');
            $shippingCost = $request->input('shippingCost', 0);
            $taxAmount = $request->input('taxAmount', 0);
            $totalAmount = $request->input('totalAmount');

            // Calculate subtotal from items
            $subtotal = 0;
            foreach ($request->input('items') as $item) {
                $price = is_string($item['price']) ? floatval($item['price']) : $item['price'];
                $subtotal += $price * $item['quantity'];
            }

            // Determine user ID - prioritize authenticated user, then request userId, then null for guest
            $userId = null;
            if (Auth::check()) {
                $userId = Auth::id();
                \Log::info('Using authenticated user ID: ' . $userId);
            } elseif ($request->has('userId') && $request->input('userId')) {
                $userId = $request->input('userId');
                \Log::info('Using provided user ID: ' . $userId);
            } else {
                \Log::info('Creating guest order (no user ID)');
            }

            // Create order with all required fields
            $orderData = [
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'status' => 'pending',
                'payment_method' => $request->input('paymentMethod'),
                'payment_status' => 'pending',
                'currency' => $request->input('currency', 'EUR'),
                
                // Financial fields
                'shipping_cost' => $shippingCost,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'subtotal' => $subtotal,
                'total' => $totalAmount, // For backward compatibility
                
                // Address fields
                'first_name' => $shippingAddress['firstName'],
                'last_name' => $shippingAddress['lastName'],
                'email' => $shippingAddress['email'],
                'phone' => $shippingAddress['phone'],
                'street' => $shippingAddress['street'],
                'city' => $shippingAddress['city'],
                'state' => $shippingAddress['state'],
                'zip_code' => $shippingAddress['zipCode'],
                'country' => $shippingAddress['country'],
                
                // JSON fields
                'shipping_address' => $shippingAddress,
                'billing_address' => $request->input('billingAddress', $shippingAddress),
                'notes' => $request->input('notes'),
            ];

            \Log::info('Creating order with data', $orderData);

            $order = Order::create($orderData);

            \Log::info('Order created successfully', ['order_id' => $order->id, 'order_number' => $order->order_number]);

            // Create order items with product snapshot
            foreach ($request->input('items') as $item) {
                $product = Product::find($item['productId']);
                if (!$product) {
                    throw new \Exception("Product not found: {$item['productId']}");
                }

                // Create product snapshot
                $productSnapshot = [
                    'id' => $product->id,
                    'title' => $product->title,
                    'description' => $product->description,
                    'price' => $product->price,
                    'images' => $product->images,
                    'category_id' => $product->category_id,
                    'sku' => $product->sku,
                    'brand' => $product->brand,
                    'material' => $product->material,
                    'care_instructions' => $product->care_instructions,
                    'sizes' => $product->sizes,
                    'colors' => $product->colors,
                    'variants' => $product->variants,
                    'is_active' => $product->is_active,
                    'is_featured' => $product->is_featured,
                    'weight' => $product->weight,
                    'dimensions' => $product->dimensions,
                    'stock_quantity' => $product->stock_quantity,
                    'min_order_quantity' => $product->min_order_quantity,
                    'max_order_quantity' => $product->max_order_quantity,
                    'tags' => $product->tags,
                    'meta_title' => $product->meta_title,
                    'meta_description' => $product->meta_description,
                    'sales_count' => $product->sales_count,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                ];

                $price = is_string($item['price']) ? floatval($item['price']) : $item['price'];

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['productId'],
                    'product_snapshot' => $productSnapshot,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'size' => $item['size'],
                    'color' => is_array($item['color']) ? $item['color'] : json_decode($item['color'], true),
                    'variant' => $item['variant'],
                    'customization' => isset($item['customization']) ? $item['customization'] : null,
                ]);

                \Log::info('Order item created', ['order_item_id' => $orderItem->id]);

                // Update product sales count
                $product->increment('sales_count', $item['quantity']);
            }

            DB::commit();

            \Log::info('Order transaction committed successfully');

            // Send confirmation email
            try {
                Mail::to($order->email)->send(new OrderConfirmationMail($order));
                \Log::info('Order confirmation email sent successfully');
            } catch (\Exception $e) {
                \Log::error('Failed to send order confirmation email: ' . $e->getMessage());
            }

            // Load relationships for response
            $order->load('items.product');

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'order' => $order
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show order details
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            // Si c'est un admin, il peut voir toutes les commandes
            if ($user && $user->role === 'admin') {
                $order = Order::where('id', $id)
                    ->with(['items.product', 'user'])
                    ->first();
            } else {
                // Sinon, seulement ses propres commandes
                $order = Order::where('id', $id)
                    ->where('user_id', $user->id)
                    ->with(['items.product'])
                    ->first();
            }

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'order' => $order
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching order details', [
                'order_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order details'
            ], 500);
        }
    }

    /**
     * Track order by number (public route)
     */
    public function trackByNumber($orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['items.product'])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        // Return limited information for security
        return response()->json([
            'success' => true,
            'order' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total_amount' => $order->total_amount,
                'currency' => $order->currency,
                'created_at' => $order->created_at,
                'items' => $order->items->map(function ($item) {
                    return [
                        'product_title' => $item->product->title ?? 'Product not found',
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'size' => $item->size,
                        'color' => $item->color,
                        'variant' => $item->variant,
                    ];
                })
            ]
        ]);
    }

    /**
     * Track order by number and email (public route with email verification)
     */
    public function trackByNumberAndEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_number' => 'required|string',
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::where('order_number', $request->input('order_number'))
            ->where('email', $request->input('email'))
            ->with(['items.product'])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or email does not match'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'order' => $order
        ]);
    }

    /**
     * Update order (admin only)
     */
    public function update(Request $request, $id)
    {
        try {
            \Log::info('Updating order', ['order_id' => $id, 'data' => $request->all()]);

            $order = Order::find($id);
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'sometimes|in:pending,confirmed,processing,shipped,delivered,cancelled',
                'payment_status' => 'sometimes|in:pending,paid,failed,refunded',
                'tracking_number' => 'sometimes|nullable|string',
                'notes' => 'sometimes|nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = $request->only(['status', 'payment_status', 'tracking_number', 'notes']);
            $order->update($updateData);

            // Send appropriate emails based on what was updated
            try {
                if ($order->email) {
                    $emailService = new \App\Services\EmailService();
                    
                    // Check if status was changed
                    if (isset($updateData['status'])) {
                        $emailService->sendOrderStatusUpdate($order);
                        \Log::info('Order status update email sent successfully', ['order_id' => $order->id]);
                    }
                    
                    // Check if tracking number was added or updated
                    if (isset($updateData['tracking_number']) && !empty($updateData['tracking_number'])) {
                        $emailService->sendTrackingUpdate($order);
                        \Log::info('Tracking update email sent successfully', ['order_id' => $order->id]);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send order update email: ' . $e->getMessage(), ['order_id' => $order->id]);
            }

            \Log::info('Order updated successfully', ['order_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'order' => $order->load('items.product', 'user')
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating order', [
                'order_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order status (admin only)
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,completed,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::find($id);
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            $order->update(['status' => $request->input('status')]);

            // Send status update email if order has an email
            try {
                if ($order->email) {
                    $emailService = new \App\Services\EmailService();
                    $emailService->sendOrderStatusUpdate($order);
                    \Log::info('Order status update email sent successfully', ['order_id' => $order->id]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send order status update email: ' . $e->getMessage(), ['order_id' => $order->id]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'order' => $order->load('items.product', 'user')
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating order status', [
                'order_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete order (admin only)
     */
    public function destroy($id)
    {
        try {
            \Log::info('Deleting order', ['order_id' => $id]);

            $order = Order::find($id);
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            $order->delete();

            \Log::info('Order deleted successfully', ['order_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting order', [
                'order_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
