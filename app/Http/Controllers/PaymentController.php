<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentError;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct()
    {
        // Set Stripe secret key
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
    }

    /**
     * Create a payment intent (PUBLIC - no auth required for guest checkout)
     */
    public function createPaymentIntent(Request $request)
    {
        try {
            Log::info('PaymentController - Creating payment intent', [
                'request_data' => $request->all(),
                'user_authenticated' => auth()->check(),
                'user_id' => auth()->id()
            ]);

            // Validation avec orderId acceptant string ou integer
            $request->validate([
                'amount' => 'required|numeric|min:1',
                'currency' => 'required|string|max:3',
                'orderId' => 'required', // Accepte string ou integer
                'metadata' => 'sometimes|array'
            ]);

            $amount = $request->amount; // Already in cents from frontend
            $currency = strtolower($request->currency);
            $orderId = (string) $request->orderId; // Convertir en string
            $metadata = $request->metadata ?? [];

            // Add order ID to metadata
            $metadata['order_id'] = $orderId;

            Log::info('PaymentController - Creating Stripe PaymentIntent', [
                'amount' => $amount,
                'currency' => $currency,
                'order_id' => $orderId,
                'metadata' => $metadata
            ]);

            // Vérifier que la commande existe
            $order = Order::find($orderId);
            if (!$order) {
                Log::error('PaymentController - Order not found', [
                    'order_id' => $orderId
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Order not found'
                ], 404);
            }

            // Create payment intent with Stripe
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => $currency,
                'metadata' => $metadata,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            Log::info('PaymentController - Payment intent created successfully', [
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'clientSecret' => $paymentIntent->client_secret,
                    'id' => $paymentIntent->id
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('PaymentController - Validation error', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all())
            ], 422);

        } catch (\Stripe\Exception\CardException $e) {
            Log::error('PaymentController - Stripe card error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            // Log payment error with safe data
            $this->logPaymentError([
                'order_id' => $request->orderId ?? null,
                'error_type' => 'card_error',
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'stripe_error_id' => $e->getStripeCode()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Card error: ' . $e->getMessage()
            ], 400);

        } catch (\Stripe\Exception\RateLimitException $e) {
            Log::error('PaymentController - Stripe rate limit error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Too many requests. Please try again later.'
            ], 429);

        } catch (\Stripe\Exception\InvalidRequestException $e) {
            Log::error('PaymentController - Stripe invalid request error', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Invalid payment request: ' . $e->getMessage()
            ], 400);

        } catch (\Stripe\Exception\AuthenticationException $e) {
            Log::error('PaymentController - Stripe authentication error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Payment authentication failed.'
            ], 401);

        } catch (\Stripe\Exception\ApiConnectionException $e) {
            Log::error('PaymentController - Stripe API connection error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Payment service unavailable. Please try again.'
            ], 503);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('PaymentController - Stripe API error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Payment processing error. Please try again.'
            ], 500);

        } catch (\Exception $e) {
            Log::error('PaymentController - General error creating payment intent', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create payment intent. Please try again.'
            ], 500);
        }
    }

    /**
     * Confirm payment (PUBLIC - no auth required for guest checkout)
     */
    public function confirmPayment(Request $request)
    {
        try {
            Log::info('PaymentController - Starting confirmPayment', [
                'request_data' => $request->all(),
                'user_authenticated' => auth()->check(),
                'user_id' => auth()->id()
            ]);

            // Validation
            $validator = Validator::make($request->all(), [
                'paymentIntentId' => 'required|string',
                'orderId' => 'required'
            ]);

            if ($validator->fails()) {
                Log::error('PaymentController - Validation failed', [
                    'errors' => $validator->errors(),
                    'request_data' => $request->all()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed: ' . implode(', ', $validator->errors()->all())
                ], 422);
            }

            $paymentIntentId = $request->paymentIntentId;
            $orderId = $request->orderId;

            Log::info('PaymentController - Processing payment confirmation', [
                'payment_intent_id' => $paymentIntentId,
                'order_id' => $orderId
            ]);

            // Retrieve the payment intent from Stripe
            try {
                $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
                Log::info('PaymentController - Retrieved payment intent from Stripe', [
                    'payment_intent_id' => $paymentIntent->id,
                    'status' => $paymentIntent->status,
                    'amount' => $paymentIntent->amount
                ]);
            } catch (\Exception $e) {
                Log::error('PaymentController - Failed to retrieve payment intent from Stripe', [
                    'payment_intent_id' => $paymentIntentId,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Failed to retrieve payment information from Stripe'
                ], 400);
            }

            // Find the order
            $order = null;
            try {
                if (is_numeric($orderId)) {
                    $order = Order::find((int) $orderId);
                    Log::info('PaymentController - Searched order by ID', [
                        'order_id' => $orderId,
                        'found' => $order ? true : false
                    ]);
                }
            
                // If not found by ID, try by order_number
                if (!$order) {
                    $order = Order::where('order_number', $orderId)->first();
                    Log::info('PaymentController - Searched order by order_number', [
                        'order_number' => $orderId,
                        'found' => $order ? true : false
                    ]);
                }

                if (!$order) {
                    Log::error('PaymentController - Order not found', [
                        'order_id' => $orderId,
                        'order_id_type' => gettype($orderId)
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'Order not found'
                    ], 404);
                }

                Log::info('PaymentController - Order found', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'current_status' => $order->status,
                    'current_payment_status' => $order->payment_status
                ]);

            } catch (\Exception $e) {
                Log::error('PaymentController - Error finding order', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Error finding order'
                ], 500);
            }

            // Check payment intent status and update order
            if ($paymentIntent->status === 'succeeded') {
                // Check if order is already marked as paid
                if ($order->payment_status === 'paid' && $order->payment_intent_id === $paymentIntentId) {
                    Log::info('PaymentController - Payment already processed', [
                        'order_id' => $order->id,
                        'payment_intent_id' => $paymentIntentId
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment already processed successfully',
                        'data' => [
                            'paymentIntent' => [
                                'id' => $paymentIntent->id,
                                'status' => $paymentIntent->status,
                                'amount' => $paymentIntent->amount,
                                'currency' => $paymentIntent->currency
                            ],
                            'order' => [
                                'id' => $order->id,
                                'order_number' => $order->order_number,
                                'status' => $order->status,
                                'payment_status' => $order->payment_status,
                                'total' => $order->total ?? $order->total_amount
                            ]
                        ]
                    ]);
                }

                // Payment succeeded on Stripe, update our database
                Log::info('PaymentController - Updating order with successful payment', [
                    'order_id' => $order->id,
                    'payment_intent_id' => $paymentIntentId
                ]);

                try {
                    // Use database transaction for safety
                    DB::beginTransaction();

                    // Update order fields - use 'completed' instead of 'paid' for status
                    $order->status = 'confirmed';  // Changed from 'paid' to 'completed'
                    $order->payment_status = 'paid';
                    $order->payment_intent_id = $paymentIntentId;
            
                    // Prepare payment details
                    $paymentDetails = [
                        'stripe_payment_intent_id' => $paymentIntentId,
                        'amount' => $paymentIntent->amount / 100,
                        'currency' => $paymentIntent->currency,
                        'payment_method' => 'stripe',
                        'status' => $paymentIntent->status,
                        'confirmed_at' => now()->toISOString()
                    ];
            
                    $order->payment_details = $paymentDetails;
            
                    // Ensure total fields are set
                    if (!$order->total && $order->total_amount) {
                        $order->total = $order->total_amount;
                    } elseif (!$order->total_amount && $order->total) {
                        $order->total_amount = $order->total;
                    }

                    Log::info('PaymentController - About to save order', [
                        'order_id' => $order->id,
                        'status' => $order->status,
                        'payment_status' => $order->payment_status,
                        'payment_intent_id' => $order->payment_intent_id
                    ]);
            
                    // Save the order
                    $saved = $order->save();

                    if (!$saved) {
                        throw new \Exception('Failed to save order to database');
                    }

                    DB::commit();

                    Log::info('PaymentController - Payment confirmed and order updated successfully', [
                        'order_id' => $order->id,
                        'payment_intent_id' => $paymentIntentId,
                        'order_status' => $order->status,
                        'payment_status' => $order->payment_status
                    ]);

                    return response()->json([
                        'success' => true,
                        'data' => [
                            'paymentIntent' => [
                                'id' => $paymentIntent->id,
                                'status' => $paymentIntent->status,
                                'amount' => $paymentIntent->amount,
                                'currency' => $paymentIntent->currency
                            ],
                            'order' => [
                                'id' => $order->id,
                                'order_number' => $order->order_number,
                                'status' => $order->status,
                                'payment_status' => $order->payment_status,
                                'total' => $order->total ?? $order->total_amount
                            ]
                        ]
                    ]);

                } catch (\Exception $e) {
                    DB::rollBack();
                    
                    Log::error('PaymentController - Failed to save order', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
            
                    return response()->json([
                        'success' => false,
                        'error' => 'Failed to update order status: ' . $e->getMessage()
                    ], 500);
                }

            } elseif ($paymentIntent->status === 'requires_confirmation') {
                // Payment intent needs to be confirmed
                Log::info('PaymentController - Payment intent requires confirmation', [
                    'payment_intent_id' => $paymentIntentId
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Payment requires additional confirmation',
                    'requires_action' => true,
                    'payment_intent' => [
                        'id' => $paymentIntent->id,
                        'status' => $paymentIntent->status,
                        'client_secret' => $paymentIntent->client_secret
                    ]
                ], 400);

            } else {
                // Payment failed or in another state
                Log::warning('PaymentController - Payment not succeeded', [
                    'payment_intent_id' => $paymentIntentId,
                    'status' => $paymentIntent->status,
                    'order_id' => $order->id
                ]);

                try {
                    // Update order with failed payment status
                    $order->payment_status = 'failed';
                    $order->payment_intent_id = $paymentIntentId;
                    $order->payment_details = [
                        'stripe_payment_intent_id' => $paymentIntentId,
                        'status' => $paymentIntent->status,
                        'failed_at' => now()->toISOString(),
                        'last_payment_error' => $paymentIntent->last_payment_error
                    ];
                    $order->save();
                } catch (\Exception $e) {
                    Log::error('PaymentController - Failed to update order with failed payment', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'error' => 'Payment not completed. Status: ' . $paymentIntent->status,
                    'payment_intent' => [
                        'id' => $paymentIntent->id,
                        'status' => $paymentIntent->status
                    ]
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('PaymentController - Unexpected error in confirmPayment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            // Log payment error with safe data
            $this->logPaymentError([
                'order_id' => $request->orderId ?? null,
                'error_type' => 'confirmation_error',
                'error_message' => $e->getMessage(),
                'payment_intent_id' => $request->paymentIntentId ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to confirm payment. Please contact support.',
                'debug' => app()->environment('local') ? [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => basename($e->getFile())
                ] : null
            ], 500);
        }
    }

    /**
     * Create PayPal order (PUBLIC - no auth required for guest checkout)
     */
    public function createPayPalOrder(Request $request)
    {
        try {
            Log::info('PaymentController - Creating PayPal order', [
                'request_data' => $request->all(),
                'user_authenticated' => auth()->check(),
                'user_id' => auth()->id()
            ]);

            // Validation
            $request->validate([
                'amount' => 'required|numeric|min:1',
                'currency' => 'required|string|max:3',
                'orderId' => 'required'
            ]);

            $amount = $request->amount;
            $currency = strtoupper($request->currency);
            $orderId = (string) $request->orderId;

            // Vérifier que la commande existe
            $order = Order::find($orderId);
            if (!$order) {
                Log::error('PaymentController - Order not found for PayPal', [
                    'order_id' => $orderId
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Order not found'
                ], 404);
            }

            // Get PayPal access token
            $accessToken = $this->getPayPalAccessToken();
            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to authenticate with PayPal'
                ], 500);
            }

            // Create PayPal order
            $paypalOrderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $orderId,
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => number_format($amount, 2, '.', '')
                        ]
                    ]
                ],
                'application_context' => [
                    'return_url' => url('/checkout/paypal/success'),
                    'cancel_url' => url('/checkout/paypal/cancel')
                ]
            ];

            $response = $this->makePayPalRequest('POST', '/v2/checkout/orders', $paypalOrderData, $accessToken);

            if ($response && isset($response['id'])) {
                Log::info('PaymentController - PayPal order created successfully', [
                    'paypal_order_id' => $response['id'],
                    'order_id' => $orderId
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'orderID' => $response['id']
                    ]
                ]);
            } else {
                Log::error('PaymentController - Failed to create PayPal order', [
                    'response' => $response
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Failed to create PayPal order'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('PaymentController - Error creating PayPal order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create PayPal order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Capture PayPal order (PUBLIC - no auth required for guest checkout)
     */
    public function capturePayPalOrder(Request $request)
    {
        try {
            Log::info('PaymentController - Capturing PayPal order', [
                'request_data' => $request->all(),
                'user_authenticated' => auth()->check(),
                'user_id' => auth()->id()
            ]);

            // Validation
            $request->validate([
                'paypalOrderId' => 'required|string',
                'orderId' => 'required',
                'amount' => 'required|numeric'
            ]);

            $paypalOrderId = $request->paypalOrderId;
            $orderId = (string) $request->orderId;
            $amount = $request->amount;

            // Find the order
            $order = Order::find($orderId);
            if (!$order) {
                Log::error('PaymentController - Order not found for PayPal capture', [
                    'order_id' => $orderId
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Order not found'
                ], 404);
            }

            // Get PayPal access token
            $accessToken = $this->getPayPalAccessToken();
            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to authenticate with PayPal'
                ], 500);
            }

            // Capture PayPal order
            $response = $this->makePayPalRequest('POST', "/v2/checkout/orders/{$paypalOrderId}/capture", [], $accessToken);

            if ($response && isset($response['status']) && $response['status'] === 'COMPLETED') {
                // Update order with PayPal payment details
                try {
                    DB::beginTransaction();

                    $order->status = 'confirmed';
                    $order->payment_status = 'paid';
                    $order->payment_intent_id = $paypalOrderId;

                    $paymentDetails = [
                        'paypal_order_id' => $paypalOrderId,
                        'amount' => $amount,
                        'currency' => $response['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'] ?? 'EUR',
                        'payment_method' => 'paypal',
                        'status' => 'completed',
                        'confirmed_at' => now()->toISOString(),
                        'paypal_response' => $response
                    ];

                    $order->payment_details = $paymentDetails;
                    $order->save();

                    DB::commit();

                    Log::info('PaymentController - PayPal payment captured and order updated successfully', [
                        'order_id' => $order->id,
                        'paypal_order_id' => $paypalOrderId
                    ]);

                    return response()->json([
                        'success' => true,
                        'data' => [
                            'paypalOrder' => $response,
                            'order' => [
                                'id' => $order->id,
                                'order_number' => $order->order_number,
                                'status' => $order->status,
                                'payment_status' => $order->payment_status,
                                'total' => $order->total ?? $order->total_amount
                            ]
                        ]
                    ]);

                } catch (\Exception $e) {
                    DB::rollBack();
                    
                    Log::error('PaymentController - Failed to save PayPal order', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'Failed to update order status: ' . $e->getMessage()
                    ], 500);
                }

            } else {
                Log::error('PaymentController - PayPal capture failed', [
                    'paypal_order_id' => $paypalOrderId,
                    'response' => $response
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'PayPal payment capture failed'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('PaymentController - Error capturing PayPal order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to capture PayPal payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get PayPal access token
     */
    private function getPayPalAccessToken()
    {
        try {
            $clientId = env('PAYPAL_CLIENT_ID');
            $clientSecret = env('PAYPAL_CLIENT_SECRET');
            $baseUrl = env('PAYPAL_MODE', 'sandbox') === 'live' 
                ? 'https://api.paypal.com' 
                : 'https://api.sandbox.paypal.com';

            if (!$clientId || !$clientSecret) {
                Log::error('PayPal credentials not configured');
                return null;
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v1/oauth2/token');
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $clientSecret);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                return $data['access_token'] ?? null;
            }

            Log::error('Failed to get PayPal access token', [
                'http_code' => $httpCode,
                'response' => $response
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Error getting PayPal access token', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Make PayPal API request
     */
    private function makePayPalRequest($method, $endpoint, $data = [], $accessToken = null)
    {
        try {
            $baseUrl = env('PAYPAL_MODE', 'sandbox') === 'live' 
                ? 'https://api.paypal.com' 
                : 'https://api.sandbox.paypal.com';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $baseUrl . $endpoint);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ]);

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if (!empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return json_decode($response, true);
            }

            Log::error('PayPal API request failed', [
                'method' => $method,
                'endpoint' => $endpoint,
                'http_code' => $httpCode,
                'response' => $response
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Error making PayPal API request', [
                'error' => $e->getMessage(),
                'method' => $method,
                'endpoint' => $endpoint
            ]);
            return null;
        }
    }

    /**
     * Safe method to log payment errors
     */
    private function logPaymentError(array $data)
    {
        try {
            PaymentError::create($data);
        } catch (\Exception $e) {
            Log::error('Failed to log payment error', [
                'original_data' => $data,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function stripeWebhook(Request $request)
    {
        return response()->json(['status' => 'success']);
    }

    public function paypalWebhook(Request $request)
    {
        return response()->json(['status' => 'success']);
    }

    /**
     * Get payment errors (Admin only)
     */
    public function getErrors()
    {
        try {
            $errors = PaymentError::with('order')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('PaymentController - Error fetching payment errors', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch payment errors'
            ], 500);
        }
    }

    public function resolveError(Request $request, $id)
    {
        return response()->json(['status' => 'not_implemented'], 501);
    }

    private function handlePaymentFailure($paymentIntent)
    {
        try {
            $orderId = $paymentIntent->metadata->order_id ?? null;
            
            if ($orderId) {
                PaymentError::create([
                    'order_id' => $orderId,
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'error_code' => $paymentIntent->last_payment_error->code ?? 'unknown',
                    'error_message' => $paymentIntent->last_payment_error->message ?? 'Payment failed',
                    'error_type' => $paymentIntent->last_payment_error->type ?? 'card_error',
                    'decline_code' => $paymentIntent->last_payment_error->decline_code ?? null,
                    'amount' => $paymentIntent->amount / 100,
                    'currency' => $paymentIntent->currency,
                    'customer_email' => $paymentIntent->receipt_email ?? '',
                    'metadata' => json_encode($paymentIntent->metadata),
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't throw
            \Log::error('Failed to create payment error record: ' . $e->getMessage());
        }
    }
}
