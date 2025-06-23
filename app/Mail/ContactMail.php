<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contactData;
    public $isConfirmation;

    /**
     * Create a new message instance.
     */
    public function __construct($contactData, $isConfirmation = false)
    {
        $this->contactData = $contactData;
        $this->isConfirmation = $isConfirmation;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        if ($this->isConfirmation) {
            return new Envelope(
                subject: 'Confirmation de réception de votre message',
                replyTo: [config('mail.from.address')]
            );
        }

        return new Envelope(
            subject: 'Nouveau message de contact: ' . $this->contactData['subject'],
            replyTo: [$this->contactData['email']]
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = $this->isConfirmation ? 'emails.contact-confirmation' : 'emails.contact-message';
        
        return new Content(
            view: $view,
            with: [
                'contactData' => $this->contactData,
            ]
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
