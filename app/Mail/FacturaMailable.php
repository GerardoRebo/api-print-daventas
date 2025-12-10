<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FacturaMailable extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    /**
     * Create a new message instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $email = $this
            ->subject($this->data['message'] ?? "Tu factura está lista")
            ->from(
                $this->data['sender']['email'],
                $this->data['sender']['name'] ?? null
            )
            ->view('emails.invoice-delivery')
            ->with([
                'daysValid' => $this->data['daysValid'] ?? 15,
                'senderName' => $this->data['sender']['name'] ?? '',
                'pdfUrl' => $this->data['invoice']['pdfUrl'] ?? null,
                'xmlUrl' => $this->data['invoice']['xmlUrl'] ?? null,
            ]);

        // Reply-To (optional)
        if (!empty($this->data['replyTo'])) {
            $email->replyTo(
                $this->data['replyTo']['email'],
                $this->data['replyTo']['name'] ?? null
            );
        }

        // Attachments (optional)
        if (!empty($this->data['invoice']['pdfPath'])) {
            $email->attach(
                $this->data['invoice']['pdfPath'],
                ['as' => 'factura.pdf']
            );
        }

        if (!empty($this->data['invoice']['xmlPath'])) {
            $email->attach(
                $this->data['invoice']['xmlPath'],
                ['as' => 'factura.xml']
            );
        }

        // Brevo Sandbox header
        if (!empty($this->data['sandbox'])) {
            $email->withSymfonyMessage(function ($message) {
                $message->getHeaders()
                    ->addTextHeader('X-Sib-Sandbox', 'drop');
            });
        }

        return $email;
    }
}
