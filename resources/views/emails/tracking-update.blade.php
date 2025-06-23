<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Numéro de suivi disponible</title>
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
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .content {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .tracking-info {
            background-color: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #2196f3;
        }
        .tracking-number {
            font-size: 18px;
            font-weight: bold;
            color: #1976d2;
            margin: 10px 0;
        }
        .button {
            display: inline-block;
            background-color: #2196f3;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: bold;
        }
        .order-details {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 Votre commande est en route !</h1>
    </div>

    <div class="content">
        <h2>Bonjour {{ $user->name ?? ($order->first_name . ' ' . $order->last_name) }},</h2>
        
        <p>Excellente nouvelle ! Votre commande <strong>{{ $order->order_number }}</strong> a été expédiée et un numéro de suivi est maintenant disponible.</p>

        <div class="tracking-info">
            <h3>🚚 Informations de suivi</h3>
            <div class="tracking-number">
                Numéro de suivi : {{ $order->tracking_number }}
            </div>
            @if($order->estimated_delivery)
                <p><strong>Livraison estimée :</strong> {{ \Carbon\Carbon::parse($order->estimated_delivery)->format('d/m/Y') }}</p>
            @endif
        </div>

        <div class="order-details">
            <h3>📋 Détails de votre commande</h3>
            <p><strong>Numéro de commande :</strong> {{ $order->order_number }}</p>
            <p><strong>Statut :</strong> {{ ucfirst($order->status) }}</p>
            <p><strong>Total :</strong> {{ number_format($order->total, 2) }} €</p>
        </div>

        <h3>🔍 Comment suivre votre commande ?</h3>
        <p>Vous pouvez suivre votre commande directement sur notre site web :</p>
        
        <ol>
            <li>Connectez-vous à votre compte</li>
            <li>Visitez votre profil</li>
            <li>Cliquez sur "Suivi des commandes"</li>
            <li>Consultez le statut en temps réel</li>
        </ol>

        <div style="text-align: center;">
            <a href="{{ config('app.frontend_url') }}/login" class="button">
                Se connecter et suivre ma commande
            </a>
        </div>

        <p>Vous pouvez également utiliser le numéro de suivi <strong>{{ $order->tracking_number }}</strong> directement sur le site du transporteur.</p>

        <p>Si vous avez des questions concernant votre commande, n'hésitez pas à nous contacter.</p>

        <p>Merci pour votre confiance !</p>
        
        <p>L'équipe de votre boutique</p>
    </div>

    <div class="footer">
        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        <p>© {{ date('Y') }} Votre Boutique. Tous droits réservés.</p>
    </div>
</body>
</html>
