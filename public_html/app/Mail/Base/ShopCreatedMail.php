<?php

namespace App\Mail\Base;

use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Aviso al soporte del proveedor cuando se da de alta una tienda (shop_new_register.php):
 * 【CRMシステム】店舗が新規登録されました, desde mail_mag@<dominio> con nombre CRMシステム.
 */
class ShopCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(public Shop $shop, public string $host) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('mail_mag@' . $this->host, 'CRMシステム'),
            subject: '【CRMシステム】店舗が新規登録されました',
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.base.shop-created', with: ['sendDate' => now()->format('Y/m/d h:i:s')]);
    }
}
