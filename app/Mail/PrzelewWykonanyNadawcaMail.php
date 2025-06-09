<?php

namespace App\Mail;

use App\Models\Przelew;
use App\Models\Uzytkownik;

// Użytkownik (nadawca), do którego wysyłamy email
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PrzelewWykonanyNadawcaMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Przelew $przelew;
    public Uzytkownik $nadawca; // Użytkownik (nadawca)

    public function __construct(Przelew $przelew, Uzytkownik $nadawca)
    {
        $this->przelew = $przelew;
        $this->nadawca = $nadawca;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Potwierdzenie Wykonania Przelewu - WitelonBank',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.przelewy.wykonany_nadawca',
            with: [
                'przelew' => $this->przelew,
                'uzytkownik' => $this->nadawca, // Przekazujemy nadawcę jako użytkownika
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
