<?php

namespace App\Mail\Promotions;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Symfony\Component\Mime\Email;

/**
 * お知らせメール de mailmag_master (cMailsoushin::send / sendDeco):
 * - texto: cuerpo + pie, con la imagen adjunta (grande a パソコン, pequeña al resto);
 * - デコメール: HTML con las imágenes incrustadas por cid (CrmDecomail::easyDeco).
 * Los rebotes van a mail_err@<dominio> (el `-f` del legacy).
 */
class PromotionMail extends Mailable
{
    /**
     * @param  array<int, string>  $attachmentPaths  adjuntos (texto)
     * @param  array<string, string>  $inlineImages  [nombre cid => ruta] (デコメール)
     */
    public function __construct(
        public string $mailSubject,
        public string $body,
        public bool $deco,
        public string $fromAddress,
        public string $fromName,
        public ?string $returnPathAddress = null,
        public array $attachmentPaths = [],
        public array $inlineImages = [],
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
                foreach ($this->inlineImages as $name => $path) {
                    if (is_file($path)) {
                        $email->embedFromPath($path, $name);
                    }
                }
            }],
        );
    }

    public function content(): Content
    {
        return $this->deco
            ? new Content(html: 'admin.promocion.mail.html', with: ['body' => $this->body])
            : new Content(text: 'admin.promocion.mail.plain', with: ['body' => $this->body]);
    }

    public function attachments(): array
    {
        return array_values(array_map(
            fn (string $path) => Attachment::fromPath($path),
            array_filter($this->attachmentPaths, 'is_file'),
        ));
    }
}
