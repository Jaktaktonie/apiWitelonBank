@component('mail::message')
    # Wniosek o Reset Hasła

    Otrzymałeś/aś tego e-maila, ponieważ otrzymaliśmy prośbę o zresetowanie hasła dla Twojego konta w WitelonBank.

    Kliknij poniższy przycisk, aby zresetować swoje hasło:
    @component('mail::button', ['url' => $resetUrl])
        Resetuj Hasło
    @endcomponent

    Ten link do resetowania hasła wygaśnie za {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60) }} minut.

    Jeśli nie prosiłeś/aś o zresetowanie hasła, żadne dalsze działania nie są wymagane.

    Z poważaniem,<br>
    Zespół {{ config('app.name') }}

    <hr>
    <small>Jeśli masz problemy z kliknięciem przycisku "Resetuj Hasło", skopiuj i wklej poniższy URL do swojej przeglądarki internetowej:<br>{{ $resetUrl }}</small>
@endcomponent
