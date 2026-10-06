<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Enums\MessageChannel;
use App\Models\CouponCustomer;
use App\Models\CustomerMessage;
use App\Models\Message;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Rendimiento de los envíos (配信お知らせからのマイページアクセス集計).
 * El legacy tiene una pantalla por canal; aquí se comparan juntos.
 */
class AnalyticsController extends ModuleController
{
    public function index(Request $request): View
    {
        $days = in_array($request->integer('days'), [7, 30, 90, 365], true) ? $request->integer('days') : 30;
        $since = now()->subDays($days)->startOfDay();

        $base = CustomerMessage::query()
            ->join('messages', 'messages.id', '=', 'customer_message.message_id')
            ->whereNull('messages.deleted_at')
            ->where('customer_message.sent_at', '>=', $since);

        $byChannel = (clone $base)
            ->groupBy('messages.channel')
            ->get([
                'messages.channel',
                DB::raw('count(*) as sent'),
                DB::raw('count(customer_message.opened_at) as opened'),
                DB::raw('count(customer_message.clicked_at) as clicked'),
            ])
            ->keyBy(fn ($r) => (int) $r->channel);

        $byDay = (clone $base)
            ->selectRaw("to_char(customer_message.sent_at, 'YYYY-MM-DD') as dia, count(*) as sent, count(customer_message.clicked_at) as clicked")
            ->groupBy('dia')->orderBy('dia')->get()->keyBy('dia');

        $series = [];
        for ($d = $since->copy(); $d->lte(today()); $d->addDay()) {
            $row = $byDay->get($d->toDateString());
            $series[] = ['date' => $d->copy(), 'sent' => (int) ($row->sent ?? 0), 'clicked' => (int) ($row->clicked ?? 0)];
        }

        return view('admin.promotions.analytics', [
            'days' => $days,
            'channels' => collect(MessageChannel::cases())->map(fn (MessageChannel $c) => [
                'channel' => $c,
                'sent' => (int) ($byChannel[$c->value]->sent ?? 0),
                'opened' => (int) ($byChannel[$c->value]->opened ?? 0),
                'clicked' => (int) ($byChannel[$c->value]->clicked ?? 0),
            ]),
            'series' => $series,
            'top' => Message::where('sent_at', '>=', $since)
                ->withCount(['recipients as sent_count' => fn ($q) => $q->whereNotNull('sent_at'),
                    'recipients as clicked_count' => fn ($q) => $q->whereNotNull('clicked_at')])
                ->get()
                ->filter(fn ($m) => $m->sent_count > 0)
                ->sortByDesc(fn ($m) => $m->clicked_count / $m->sent_count)
                ->take(8),
            'coupons' => [
                'issued' => CouponCustomer::where('issued_at', '>=', $since)->count(),
                'used' => CouponCustomer::where('used_at', '>=', $since)->count(),
                'fromMessages' => CouponCustomer::whereNotNull('message_id')->where('used_at', '>=', $since)->count(),
            ],
            'surveyResponses' => SurveyResponse::where('answered_at', '>=', $since)->count(),
        ]);
    }
}
