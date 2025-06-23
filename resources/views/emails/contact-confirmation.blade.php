<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de réception</title>
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
            background-color: #059669;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f0fdf4;
            padding: 30px;
            border: 1px solid #bbf7d0;
        }
        .message-summary {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #d1fae5;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background-color: #1f2937;
            color: white;
            border-radius: 0 0 8px 8px;
        }
        .contact-info {
            background-color: #e0f2fe;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✓ Message Reçu</h1>
        <p>Merci pour votre message !</p>
    </div>

    <div class="content">
        <p>Bonjour <strong>{{ $contactData['name'] }}</strong>,</p>
        
        <p>Nous avons bien reçu votre message concernant "<strong>{{ $contactData['subject'] }}</strong>" et nous vous remercions de nous avoir contactés.</p>

        <div class="message-summary">
            <h3>Résumé de votre message :</h3>
            <p><strong>Envoyé le :</strong> {{ $contactData['sent_at']->format('d/m/Y à H:i') }}</p>
            <p><strong>Sujet :</strong> {{ $contactData['subject'] }}</p>
            <p style="margin-top: 15px; padding: 10px; background-color: #f8fafc; border-radius: 4px;">
                {{ Str::limit($contactData['message'], 200) }}
            </p>
        </div>

        <p>Notre équipe examine votre demande et vous répondra dans les plus brefs délais, généralement sous 24-48 heures ouvrables.</p>

        <div class="contact-info">
            <h4>Informations de contact :</h4>
            <p><strong>Email :</strong> contact@votresite.com</p>
            <p><strong>Téléphone :</strong> +33 1 23 45 67 89</p>
            <p><strong>Horaires :</strong> Lundi - Vendredi : 9h00 - 18h00</p>
        </div>
    </div>

    <div class="footer">
        <p>Ceci est un message automatique de confirmation.</p>
        <p>Si vous avez des questions urgentes, n'hésitez pas à nous appeler.</p>
    </div>
</body>
</html>
