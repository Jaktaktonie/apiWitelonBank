<?php

namespace App\Mail;

use App\Models\Konto; // Potrzebne do znalezienia konta odbiorcy
use App\Models\Przelew;
use App\Models\Uzytkownik; // Użytkownik (odbiorca), do którego wysyłamy email
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PrzelewOtrzymanyOdbiorcaMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Przelew $przelew;
    public Uzytkownik $odbiorcaUzytkownik; // Użytkownik (odbiorca)
    public Konto $kontoOdbiorcy; // Konto, na które wpłynął przelew

    public function __construct(Przelew $przelew, Uzytkownik $odbiorcaUzytkownik, Konto $kontoOdbiorcy)
    {
        $this->przelew = $przelew;
        $this->odbiorcaUzytkownik = $odbiorcaUzytkownik;
        $this->kontoOdbiorcy = $kontoOdbiorcy;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Otrzymano Nowy Przelew - WitelonBank',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.przelewy.otrzymany_odbiorca',
            with: [
                'przelew' => $this->przelew,
                'uzytkownik' => $this->odbiorcaUzytkownik,
                'kontoOdbiorcy' => $this->kontoOdbiorcy,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
