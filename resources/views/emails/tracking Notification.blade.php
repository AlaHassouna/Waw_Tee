<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Information Available</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #3b82f6;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8fafc;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .tracking-box {
            background-color: white;
            border: 2px solid #3b82f6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .tracking-number {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
            font-family: monospace;
            background-color: #eff6ff;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .button {
            display: inline-block;
            background-color: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 10px 5px;
        }
        .button:hover {
            background-color: #2563eb;
        }
        .order-details {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
        .steps {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .step {
            display: flex;
            align-items: center;
            margin: 10px 0;
            padding: 10px;
            background-color: #f1f5f9;
            border-radius: 6px;
        }
        .step-number {
            background-color: #3b82f6;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 Your Order is on its Way!</h1>
        <p>Tracking information is now available</p>
    </div>

    <div class="content">
        <p>Hello <strong>{{ $order->user->name }}</strong>,</p>

        <p>Great news! Your order has been shipped and tracking information is now available.</p>

        <div class="order-details">
            <h3>Order Details</h3>
            <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
            <p><strong>Order Date:</strong> {{ $order->created_at->format('F j, Y') }}</p>
            <p><strong>Total:</strong> ${{ number_format($order->total, 2) }}</p>
        </div>

        <div class="tracking-box">
            <h3>🚚 Tracking Number</h3>
            <div class="tracking-number">{{ $order->tracking_number }}</div>
            @if($order->estimated_delivery)
                <p><strong>Estimated Delivery:</strong> {{ \Carbon\Carbon::parse($order->estimated_delivery)->format('F j, Y') }}</p>
            @endif
        </div>

        <div class="steps">
            <h3>How to Track Your Order on Our Website:</h3>
            <div class="step">
                <div class="step-number">1</div>
                <div>Visit our website and log in to your account</div>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <div>Go to your Profile page</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div>Click on the "Tracking" tab</div>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <div>Enter your tracking number to get real-time updates</div>
            </div>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.frontend_url') }}/login" class="button">Login to Track Order</a>
            <a href="{{ config('app.frontend_url') }}/profile" class="button">Go to Profile</a>
        </div>

        <p>You can also track your package directly with the carrier using the tracking number provided above.</p>

        <p>If you have any questions about your order or need assistance with tracking, please don't hesitate to contact our customer support team.</p>

        <p>Thank you for your business!</p>
    </div>

    <div class="footer">
        <p>This email was sent regarding your order {{ $order->order_number }}</p>
        <p>If you have any questions, please contact our support team.</p>
    </div>
</body>
</html>