<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\Promotions\MessageDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Envía un lote de destinatarios de un mensaje */
class SendMessageBatch implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @param list<int> $recipientIds ids de customer_message */
    public function __construct(
        public int $messageId,
        public array $recipientIds,
    ) {}

    public function handle(MessageDispatcher $dispatcher): void
    {
        $message = Message::find($this->messageId);

        if ($message) {
            $dispatcher->sendBatch($message, $this->recipientIds);
        }
    }
}
