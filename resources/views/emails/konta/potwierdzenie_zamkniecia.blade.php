<x-mail::message>
    # Potwierdzenie Żądania Zamknięcia Konta

    Witaj {{ $uzytkownik->imie }},

    Otrzymaliśmy żądanie zamknięcia Twojego konta bankowego w WitelonBank o numerze: **{{ $konto->nr_konta }}**.

    Aby potwierdzić tę operację i ostatecznie zamknąć konto, kliknij w poniższy link:

    <x-mail::button :url="$linkPotwierdzajacy">
        Potwierdź Zamknięcie Konta
    </x-mail::button>

    Jeśli to nie Ty inicjowałeś to żądanie, zignoruj tę wiadomość lub skontaktuj się z naszym wsparciem.
    Link jest ważny przez 24 godziny.

    Pozdrawiamy,<br>
    Zespół {{ config('app.name') }}
</x-mail::message>
