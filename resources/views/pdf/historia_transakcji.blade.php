<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Historia Transakcji - Konto {{ $konto->nr_konta }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; margin: 0; padding: 0; } /* DejaVu Sans dla polskich znaków */
        .container { padding: 20px; }
        h1 { text-align: center; border-bottom: 1px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; word-wrap: break-word; }
        th { background-color: #f2f2f2; }
        .header-info { margin-bottom: 20px; }
        .header-info p { margin: 2px 0; }
        .footer { text-align: center; font-size: 8px; margin-top: 30px; position: fixed; bottom: 0; width: 100%; }
        .kwota-ujemna { color: red; }
        .kwota-dodatnia { color: green; }
    </style>
</head>
<body>
<div class="container">
    <h1>Historia Transakcji</h1>

    <div class="header-info">
        <p><strong>Numer konta:</strong> {{ $konto->nr_konta }}</p>
        <p><strong>Właściciel konta:</strong> {{ $konto->uzytkownik->imie }} {{ $konto->uzytkownik->nazwisko }}</p>
        <p><strong>Okres raportu:</strong>
            @if($data_od && $data_do)
                od {{ \Carbon\Carbon::parse($data_od)->format('Y-m-d') }} do {{ \Carbon\Carbon::parse($data_do)->format('Y-m-d') }}
            @elseif($data_od)
                od {{ \Carbon\Carbon::parse($data_od)->format('Y-m-d') }}
            @elseif($data_do)
                do {{ \Carbon\Carbon::parse($data_do)->format('Y-m-d') }}
            @else
                Cała historia
            @endif
        </p>
        <p><strong>Data wygenerowania:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    @if($przelewy->isEmpty())
        <p>Brak transakcji spełniających kryteria w wybranym okresie.</p>
    @else
        <table>
            <thead>
            <tr>
                <th>Data realizacji</th>
                <th>Typ</th>
                <th>Nadawca / Odbiorca</th>
                <th>Nr konta (przeciwnego)</th>
                <th>Tytuł</th>
                <th>Kwota</th>
                <th>Waluta</th>
            </tr>
            </thead>
            <tbody>
            @foreach($przelewy as $przelew)
                @php
                    $typTransakcji = '';
                    $stronaPrzeciwnaNazwa = '';
                    $stronaPrzeciwnaNrKonta = '';
                    $kwotaDlaRaportu = $przelew->kwota;
                    $klasaKwoty = '';

                    if ($przelew->id_konta_nadawcy == $konto->id) {
                        $typTransakcji = 'Wychodzący';
                        $stronaPrzeciwnaNazwa = $przelew->nazwa_odbiorcy;
                        $stronaPrzeciwnaNrKonta = $przelew->nr_konta_odbiorcy;
                        $kwotaDlaRaportu = -$przelew->kwota;
                        $klasaKwoty = 'kwota-ujemna';
                    } elseif ($przelew->nr_konta_odbiorcy == $konto->nr_konta) {
                        $typTransakcji = 'Przychodzący';
                        $stronaPrzeciwnaNazwa = $przelew->kontoNadawcy ? ($przelew->kontoNadawcy->uzytkownik->imie . ' ' . $przelew->kontoNadawcy->uzytkownik->nazwisko) : 'Nadawca zewnętrzny';
                        $stronaPrzeciwnaNrKonta = $przelew->kontoNadawcy ? $przelew->kontoNadawcy->nr_konta : 'Nieznany (zewn.)';
                        $klasaKwoty = 'kwota-dodatnia';
                    }
                @endphp
                <tr>
                    <td>{{ $przelew->data_realizacji->format('Y-m-d H:i') }}</td>
                    <td>{{ $typTransakcji }}</td>
                    <td>{{ $stronaPrzeciwnaNazwa }}</td>
                    <td>{{ $stronaPrzeciwnaNrKonta }}</td>
                    <td>{{ $przelew->tytul }}</td>
                    <td class="{{ $klasaKwoty }}">{{ number_format($kwotaDlaRaportu, 2, ',', ' ') }}</td>
                    <td>{{ $przelew->waluta_przelewu }}</td>
                </tr>
                외부
            @endforeach
            </tbody>
        </table>
    @endif
</div>
<div class="footer">
    Wygenerowano przez WitelonBank | Strona {{ '{PAGE_NUM}' }} z {{ '{PAGE_COUNT}' }}
</div>
</body>
</html>
