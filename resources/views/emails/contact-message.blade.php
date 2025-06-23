<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau message de contact</title>
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
            background-color: #2563eb;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8fafc;
            padding: 30px;
            border: 1px solid #e2e8f0;
        }
        .info-row {
            margin-bottom: 15px;
            padding: 10px;
            background-color: white;
            border-radius: 4px;
            border-left: 4px solid #2563eb;
        }
        .label {
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .message-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background-color: #1f2937;
            color: white;
            border-radius: 0 0 8px 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Nouveau Message de Contact</h1>
        <p>Reçu le {{ $contactData['sent_at']->format('d/m/Y à H:i') }}</p>
    </div>

    <div class="content">
        <div class="info-row">
            <div class="label">Nom complet :</div>
            <div>{{ $contactData['name'] }}</div>
        </div>

        <div class="info-row">
            <div class="label">Adresse email :</div>
            <div>{{ $contactData['email'] }}</div>
        </div>

        <div class="info-row">
            <div class="label">Sujet :</div>
            <div>{{ $contactData['subject'] }}</div>
        </div>

        <div class="message-content">
            <div class="label">Message :</div>
            <div style="margin-top: 10px; white-space: pre-wrap;">{{ $contactData['message'] }}</div>
        </div>
    </div>

    <div class="footer">
        <p>Ce message a été envoyé depuis le formulaire de contact de votre site web.</p>
        <p>Vous pouvez répondre directement à cet email pour contacter {{ $contactData['name'] }}.</p>
    </div>
</body>
</html>
