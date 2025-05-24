<?php

namespace App\Mail;

use App\Models\Konto;
use App\Models\Uzytkownik;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PotwierdzenieZamknieciaKontaMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Konto $konto;
    public Uzytkownik $uzytkownik;
    public string $linkPotwierdzajacy;

    public function __construct(Konto $konto, Uzytkownik $uzytkownik, string $linkPotwierdzajacy)
    {
        $this->konto = $konto;
        $this->uzytkownik = $uzytkownik;
        $this->linkPotwierdzajacy = $linkPotwierdzajacy;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Potwierdzenie Żądania Zamknięcia Konta - WitelonBank',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.konta.potwierdzenie_zamkniecia',
            with: [
                'konto' => $this->konto,
                'uzytkownik' => $this->uzytkownik,
                'linkPotwierdzajacy' => $this->linkPotwierdzajacy,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
