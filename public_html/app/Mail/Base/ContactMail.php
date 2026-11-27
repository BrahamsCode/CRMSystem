<?php

namespace App\Mail\Base;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * 要望・お問い合わせ (system/contact/contact_complete.php): aviso al proveedor desde la お問合せ de la cabecera.
 * Remitente y destino: config('crm.notify.contact'), con el nombre «CRM　要望・お問い合わせフォーム» como el legacy.
 */
class ContactMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public const FROM_NAME = 'CRM　要望・お問い合わせフォーム';

    /** @param array{domain: string, kaishamei: string, shop_name: string, tantou_name: string, tantou_name_kana: string, type: string, comment: string} $data */
    public function __construct(public array $data, public string $sendDate) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('crm.notify.contact'), self::FROM_NAME),
            subject: '【' . self::FROM_NAME . '】' . $this->data['kaishamei'] . ' ' . $this->data['shop_name'],
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.base.contact');
    }
}
