<x-mail::message>
    # Potwierdzenie Wykonania Przelewu

    Witaj {{ $uzytkownik->imie }},

    Informujemy, że Twój przelew został pomyślnie wykonany z konta: **{{ $przelew->kontoNadawcy->nr_konta }}**.

    **Szczegóły przelewu:**
    - **Do:** {{ $przelew->nr_konta_odbiorcy }}
    - **Odbiorca:** {{ $przelew->nazwa_odbiorcy }}
    - **Tytuł:** {{ $przelew->tytul }}
    - **Kwota:** {{ number_format($przelew->kwota, 2, ',', ' ') }} {{ $przelew->waluta_przelewu }}
    - **Data realizacji:** {{ $przelew->data_realizacji->format('Y-m-d H:i:s') }}

    @if($przelew->id_zlecenia_stalego)
        Przelew został wykonany w ramach zlecenia stałego.
    @endif

    Dziękujemy za korzystanie z usług WitelonBank.

    Pozdrawiamy,<br>
    Zespół {{ config('app.name') }}
</x-mail::message>
