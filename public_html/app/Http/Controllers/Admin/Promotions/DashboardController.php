<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Enums\DeliveryStatus;
use App\Enums\MailMagazine;
use App\Models\CouponCustomer;
use App\Models\Customer;
use App\Models\CustomerMessage;
use App\Models\Message;
use App\Models\MessageRule;
use Illuminate\View\View;

/** Resumen del módulo (販売促進管理トップ) */
class DashboardController extends ModuleController
{
    public function index(): View
    {
        $today = now();
        $since = $today->copy()->subDays(30);

        $sent = CustomerMessage::where('sent_at', '>=', $since);
        $sentCount = (clone $sent)->count();

        return view('admin.promotions.index', [
            // Las cifras de la portada del legacy: registrados y no entregables
            'members' => [
                'total' => Customer::where('status', 1)->count(),
                'reachable' => Customer::where('status', 1)->whereNotNull('mail1')->where('mail_magazine_flg', MailMagazine::Send)->count(),
                'undeliverable' => Customer::where('status', 1)->where('mail_magazine_flg', MailMagazine::Undeliverable)->count(),
                'month' => Customer::where('created_at', '>=', $today->copy()->startOfMonth())->count(),
                'today' => Customer::whereDate('created_at', $today)->count(),
            ],
            'kpis' => [
                'sent' => $sentCount,
                'clickRate' => $sentCount ? round((clone $sent)->whereNotNull('clicked_at')->count() / $sentCount * 100, 1) : null,
                'openRate' => $sentCount ? round((clone $sent)->whereNotNull('opened_at')->count() / $sentCount * 100, 1) : null,
                'couponsUsed' => CouponCustomer::where('used_at', '>=', $since)->count(),
            ],
            'scheduled' => Message::where('delivery_status', DeliveryStatus::Scheduled)->orderBy('scheduled_at')->take(5)->get(),
            'recent' => Message::where('delivery_status', DeliveryStatus::Sent)
                ->withCount(['recipients as sent_count' => fn ($q) => $q->whereNotNull('sent_at'),
                    'recipients as clicked_count' => fn ($q) => $q->whereNotNull('clicked_at')])
                ->latest('sent_at')->take(5)->get(),
            'rules' => MessageRule::active()->get()->sortBy(fn (MessageRule $r) => $r->nextRunAt()?->timestamp ?? PHP_INT_MAX)->take(5),
        ]);
    }
}
