<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmation de commande</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8f9fa; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .order-details { background: #f8f9fa; padding: 15px; margin: 20px 0; }
        .item { border-bottom: 1px solid #eee; padding: 10px 0; }
        .total { font-weight: bold; font-size: 18px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Confirmation de commande</h1>
            <p>Merci pour votre commande !</p>
        </div>
        
        <div class="content">
            <p>Bonjour {{ $user->name }},</p>
            
            <p>Nous avons bien reçu votre commande et la préparons pour l'expédition. Voici les détails de votre commande :</p>
            
            <div class="order-details">
                <h3>Commande #{{ $order->order_number }}</h3>
                <p><strong>Date de commande :</strong> {{ $order->created_at->format('j F Y') }}</p>
                <p><strong>Méthode de paiement :</strong> {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</p>
                <p><strong>Statut :</strong> {{ ucfirst($order->status) }}</p>
            </div>
            
            <h3>Articles commandés :</h3>
            @foreach($items as $item)
            <div class="item">
                <strong>{{ $item->product_snapshot['title'] }}</strong><br>
                Variante : {{ $item->variant }}<br>
                Taille : {{ $item->size }}<br>
                Quantité : {{ $item->quantity }}<br>
                Prix : €{{ number_format($item->price, 2) }}
            </div>
            @endforeach
            
            <h3>Adresse de livraison :</h3>
            <div class="order-details">
                {{ $order->shipping_address['name'] ?? '' }}<br>
                {{ $order->shipping_address['address'] ?? '' }}<br>
                {{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['postalCode'] ?? '' }}<br>
                {{ $order->shipping_address['country'] ?? '' }}
            </div>
            
            <p>Nous vous enverrons un autre email lorsque votre commande sera expédiée.</p>
            
            <p>Merci d'avoir magasiné chez nous !</p>
        </div>
    </div>
</body>
</html>