<?php

namespace App\Mail\Promotions;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * クーポンお知らせメール (legacy cOshiraseCoupon::mailSend). Texto plano, como el legacy.
 * El pie de la tienda, el reemplazo de etiquetas ({名前}…) y el enlace de login simple
 * llegan con el motor de envío de M2 (MailRenderer).
 */
class StampCouponNoticeMail extends Mailable
{
    public function __construct(public string $body) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'クーポンお知らせメール');
    }

    public function content(): Content
    {
        return new Content(text: 'admin.promocion.mail.plain', with: ['body' => $this->body]);
    }
}
