<?php

namespace App\Console\Commands;

use App\Enums\DeliveryStatus;
use App\Models\Message;
use App\Services\Promotions\MessageDispatcher;
use Illuminate\Console\Command;

/** Envía los mensajes programados cuya hora ya llegó (予約配信) */
class SendScheduledMessages extends Command
{
    protected $signature = 'promotions:send-scheduled';

    protected $description = 'Envía los mensajes programados cuya fecha ya llegó';

    public function handle(MessageDispatcher $dispatcher): int
    {
        $due = Message::active()
            ->where('delivery_status', DeliveryStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($due as $message) {
            $count = $dispatcher->send($message);
            $this->line("«{$message->subject}»: {$count} destinatarios");
        }

        return self::SUCCESS;
    }
}
