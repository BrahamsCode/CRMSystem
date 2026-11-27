<?php

namespace App\Mail\Senalizacion;

use App\Models\Senalizacion\SignageWebContact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Aviso de una お問い合わせ de GoTel (signage/mail/index.php) al soporte (PROVISIONAL). El legacy solo hacía el
 * INSERT en `contact`; este correo se envía en cola solo si config('senalizacion.contact_notify') tiene destinatario.
 */
class ContactReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(public SignageWebContact $contact) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: (string) $this->contact->subject);
    }

    public function content(): Content
    {
        return new Content(text: 'mail.senalizacion.contact-received');
    }
}
