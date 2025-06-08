<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Raport Finansowy Przelewów</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            text-align: center;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 10px;
            margin-bottom: 30px;
            color: #0056b3;
        }
        .section {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .section h2 {
            margin-top: 0;
            border-bottom: 1px solid #ccc;
            padding-bottom: 8px;
            color: #333;
        }
        .details {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #e7f3fe;
            border-left: 5px solid #007bff;
            border-radius: 5px;
        }
        .details p {
            margin: 5px 0;
        }
        .details strong {
            display: inline-block;
            width: 150px;
        }
        .currency-block {
            margin-left: 20px;
            margin-top: 5px;
        }
        footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            height: 50px;
        }
    </style>
</head>
<body>
<footer>
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font("DejaVu Sans", "normal");
            $size = 9;
            $y = $pdf->get_height() - 30;

            // Tekst z numerem strony
            $text = "Strona {PAGE_NUM} z {PAGE_COUNT}";
            $page_text_width = $fontMetrics->get_text_width($text, $font, $size);
            $x_page = ($pdf->get_width() - $page_text_width) / 2;
            $pdf->text($x_page, $y, $text, $font, $size);

            // Tekst "Wygenerowano przez..."
            $text_generated = "Wygenerowano przez WitelonBank dnia {{ now()->format('Y-m-d H:i') }}";
                $pdf->text(30, $y, $text_generated, $font, $size);
            }
    </script>
</footer>

<div class="container">
    <h1>Raport Finansowy Przelewów</h1>

    <div class="details">
        <p><strong>Okres od:</strong> {{ $okres_od }}</p>
        <p><strong>Okres do:</strong> {{ $okres_do }}</p>
    </div>

    <div class="section">
        <h2>Podsumowanie Ogólne</h2>
        <p><strong>Liczba wszystkich przelewów:</strong> {{ $liczba_wszystkich_przelewow }}</p>
        <div>
            <strong>Suma wszystkich przelewów (wg waluty):</strong>
            @forelse($suma_wszystkich_przelewow as $waluta => $suma)
                <div class="currency-block">{{ $waluta }}: {{ number_format($suma, 2, ',', ' ') }}</div>
            @empty
                <div class="currency-block">Brak danych</div>
            @endforelse
        </div>
    </div>

    <div class="section">
        <h2>Przelewy Zrealizowane</h2>
        <p><strong>Liczba przelewów zrealizowanych:</strong> {{ $liczba_przelewów_zrealizowanych }}</p>
        <div>
            <strong>Suma przelewów zrealizowanych (wg waluty):</strong>
            @forelse($suma_przelewów_zrealizowanych as $waluta => $suma)
                <div class="currency-block">{{ $waluta }}: {{ number_format($suma, 2, ',', ' ') }}</div>
            @empty
                <div class="currency-block">Brak danych</div>
            @endforelse
        </div>
    </div>

    <div class="section">
        <h2>Przelewy Oczekujące</h2>
        <p><strong>Liczba przelewów oczekujących:</strong> {{ $liczba_przelewów_oczekujących }}</p>
        <div>
            <strong>Suma przelewów oczekujących (wg waluty):</strong>
            @forelse($suma_przelewów_oczekujących as $waluta => $suma)
                <div class="currency-block">{{ $waluta }}: {{ number_format($suma, 2, ',', ' ') }}</div>
            @empty
                <div class="currency-block">Brak danych</div>
            @endforelse
        </div>
    </div>
</div>
</body>
</html>
