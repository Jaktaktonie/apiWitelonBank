<x-mail::message>
    # Otrzymano Nowy Przelew

    Witaj {{ $uzytkownik->imie }},

    Informujemy, że na Twoje konto **{{ $kontoOdbiorcy->nr_konta }}** wpłynął nowy przelew.

    **Szczegóły przelewu:**
    - **Od (Nadawca):** {{ $przelew->kontoNadawcy ? $przelew->kontoNadawcy->uzytkownik->imie . ' ' . $przelew->kontoNadawcy->uzytkownik->nazwisko : 'Nieznany nadawca (przelew zewnętrzny)' }}
    - **Numer konta nadawcy:** {{ $przelew->kontoNadawcy ? $przelew->kontoNadawcy->nr_konta : 'Nieznany (przelew zewnętrzny)' }}
    - **Tytuł:** {{ $przelew->tytul }}
    - **Kwota:** +{{ number_format($przelew->kwota, 2, ',', ' ') }} {{ $przelew->waluta_przelewu }}
    - **Data otrzymania:** {{ $przelew->data_realizacji->format('Y-m-d H:i:s') }}

    Twoje aktualne saldo na tym koncie wynosi: **{{ number_format($kontoOdbiorcy->saldo, 2, ',', ' ') }} {{ $kontoOdbiorcy->waluta }}**.

    Dziękujemy za korzystanie z usług WitelonBank.

    Pozdrawiamy,<br>
    Zespół {{ config('app.name') }}
</x-mail::message>
