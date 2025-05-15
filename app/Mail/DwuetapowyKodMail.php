<?php

namespace App\Mail;

use App\Models\Uzytkownik;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DwuetapowyKodMail extends Mailable
{
    use Queueable, SerializesModels;

    public Uzytkownik $uzytkownik;

    /**
     * Create a new message instance.
     */
    public function __construct(Uzytkownik $uzytkownik)
    {
        $this->uzytkownik = $uzytkownik;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Twój kod logowania dwuetapowego',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.dwuetapowy_kod',
            with: [
                'kod' => $this->uzytkownik->dwuetapowy_kod,
                'imie' => $this->uzytkownik->imie,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
