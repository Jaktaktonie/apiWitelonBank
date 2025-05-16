<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $token;
    public string $email;

    /**
     * Create a new message instance.
     */
    public function __construct(string $token, string $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'WitelonBank - Reset Hasła',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // URL do Twojego frontendu/API, który obsłuży formularz resetowania z tokenem
        // Na razie zakładamy, że frontend obsłuży /reset-password?token=...&email=...
        // Możesz to dostosować, np. jeśli cała logika ma być w API (choć zwykle jest frontend)
        $resetUrl = config('app.frontend_url', 'http://localhost:3000') . // Przykładowy URL frontendu
            '/reset-hasla?token=' . $this->token . '&email=' . urlencode($this->email);

        return new Content(
            markdown: 'emails.auth.reset-password', // Utworzymy ten widok Blade
            with: [
                'resetUrl' => $resetUrl,
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
