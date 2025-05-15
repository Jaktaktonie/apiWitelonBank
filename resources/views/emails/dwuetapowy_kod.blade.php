<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kod Weryfikacyjny</title>
    <style>
        /* WAŻNE: Te style powinny być zinline'owane przed wysyłką! */
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; color: #333; }
        .container { background-color: #ffffff; padding: 30px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .logo { display: block; margin: 0 auto 20px; max-width: 150px; }
        h1 { color: #0056b3; text-align: center; }
        .code-panel { background-color: #e9ecef; padding: 15px; text-align: center; border-radius: 4px; margin: 20px 0; }
        .code { font-size: 28px; font-weight: bold; letter-spacing: 3px; color: #0056b3; }
        p { line-height: 1.6; }
        .footer { text-align: center; font-size: 0.9em; color: #777; margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <img src="{{ asset('path/to/your/logo.png') }}" alt="{{ config('app.name') }} Logo" class="logo">
    <h1>Twój kod weryfikacyjny</h1>
    <p>Witaj {{ $imie }},</p>
    <p>Aby dokończyć logowanie, użyj poniższego kodu:</p>
    <div class="code-panel">
        <span class="code">{{ $kod }}</span>
    </div>
    <p>Kod jest ważny przez 10 minut. Jeśli nie próbowałeś/aś się logować, prosimy o zignorowanie tej wiadomości.</p>
    <p>Z poważaniem,<br>Zespół {{ config('app.name') }}</p>
    <div class="footer">
        Jeśli masz problemy, skontaktuj się z <a href="mailto:support@example.com">support@example.com</a>.
    </div>
</div>
</body>
</html>
