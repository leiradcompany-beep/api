<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;

    /**
     * Create a new message instance.
     */
    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Verification Code - Leirad Home Cleaning',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: '
                <h1>Welcome to Leirad Home Cleaning!</h1>
                <p>Your verification code is:</p>
                <h2 style="color: #4CAF50;">' . $this->otp . '</h2>
                <p>This code will expire in 10 minutes.</p>
            ',
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
