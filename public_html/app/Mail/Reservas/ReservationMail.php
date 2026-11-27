<?php

namespace App\Mail\Reservas;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Symfony\Component\Mime\Email;

/**
 * Correo de reserva en texto plano (yoyaku_register.php y cYoyaku::sendYoyakuMail).
 * El asunto y el cuerpo los compone ReservationMailer con el mismo texto que el legacy.
 * Remitente `mail_mag@<dominio>` con el nombre del sitio; los rebotes a `mail_err@<dominio>`.
 * Síncrono (QUEUE_CONNECTION=sync), como el mail() del legacy.
 */
class ReservationMail extends Mailable
{
    /** Destinatario según el legacy: 'shop' (お店), 'staff' (担当) o 'customer' (お客様) */
    public function __construct(
        public string $mailSubject,
        public string $body,
        public string $fromAddress,
        public string $fromName,
        public string $recipientType,
        public ?string $returnPathAddress = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->fromAddress, $this->fromName !== '' ? $this->fromName : null),
            subject: $this->mailSubject,
            using: [function (Email $email) {
                if ($this->returnPathAddress) {
                    $email->returnPath($this->returnPathAddress);
                }
            }],
        );
    }

    public function content(): Content
    {
        return new Content(text: 'admin.reservas.mail.plain', with: ['body' => $this->body]);
    }
}
