<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mise à jour du statut de commande</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 5px; }
        .content { padding: 20px 0; }
        .order-details { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .status-badge { 
            display: inline-block; 
            padding: 5px 10px; 
            border-radius: 3px; 
            font-weight: bold; 
            text-transform: uppercase;
        }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-confirmed { background-color: #17a2b8; color: #fff; }
        .status-processing { background-color: #007bff; color: #fff; }
        .status-shipped { background-color: #28a745; color: #fff; }
        .status-delivered { background-color: #28a745; color: #fff; }
        .status-completed { background-color: #28a745; color: #fff; }
        .status-cancelled { background-color: #dc3545; color: #fff; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .item-row { border-bottom: 1px solid #eee; padding: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Mise à jour du statut de commande</h1>
        </div>
        
        <div class="content">
            <p>Bonjour {{ $customerName }},</p>
            
            <p>Le statut de votre commande a été mis à jour :</p>
            
            <div class="order-details">
                <h3>Détails de la commande</h3>
                <p><strong>Numéro de commande :</strong> {{ $order->order_number }}</p>
                <p><strong>Statut :</strong> <span class="status-badge status-{{ $order->status }}">{{ ucfirst($order->status) }}</span></p>
                <p><strong>Total :</strong> €{{ number_format($order->total_amount, 2) }}</p>
                <p><strong>Date de commande :</strong> {{ $order->created_at->format('j F Y') }}</p>
                
                @if($order->tracking_number)
                <p><strong>Numéro de suivi :</strong> {{ $order->tracking_number }}</p>
                @endif
            </div>
            
            @if($order->status === 'shipped')
            <p>Bonne nouvelle ! Votre commande a été expédiée et est en route vers vous.</p>
            @elseif($order->status === 'delivered')
            <p>Votre commande a été livrée ! Nous espérons que vous apprécierez votre achat.</p>
            @elseif($order->status === 'cancelled')
            <p>Votre commande a été annulée. Si vous avez des questions, veuillez contacter notre service client.</p>
            @elseif($order->status === 'confirmed')
            <p>Votre commande a été confirmée et est en préparation pour l'expédition.</p>
            @elseif($order->status === 'processing')
            <p>Votre commande est en cours de traitement. Nous vous informerons lorsqu'elle sera expédiée.</p>
            @endif
            
            <h3>Articles commandés</h3>
            @if($order->items && count($order->items) > 0)
                @foreach($order->items as $item)
                <div class="item-row">
                    <p><strong>{{ $item->product->title ?? 'Produit' }}</strong></p>
                    <p>
                        @if($item->size)
                            Taille : {{ $item->size }}
                        @endif
                        
                        @if($item->color)
                            @if($item->size) | @endif
                            Couleur : 
                            @if(is_array($item->color) && isset($item->color['name']))
                                {{ $item->color['name'] }}
                            @elseif(is_string($item->color))
                                {{ $item->color }}
                            @else
                                N/A
                            @endif
                        @endif
                        
                        @if($item->quantity)
                            @if($item->size || $item->color) | @endif
                            Quantité : {{ $item->quantity }}
                        @endif
                    </p>
                    <p><strong>Prix : €{{ number_format($item->price, 2) }}</strong></p>
                </div>
                @endforeach
            @else
                <p>Aucun article trouvé pour cette commande.</p>
            @endif
            
            <p>Merci pour votre achat !</p>
        </div>
        
        <div class="footer">
            <p>Ceci est un message automatique. Merci de ne pas répondre à cet email.</p>
        </div>
    </div>
</body>
</html>