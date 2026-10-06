<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Enums\AddressType;
use App\Enums\MailMagazine;
use App\Models\Customer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\View\View;

/**
 * QR para que el cliente se dé de alta desde el móvil y cifras de la lista de
 * correo por tipo de dirección (メールマガジン基本情報).
 */
class RegistrationController extends ModuleController
{
    public function index(): View
    {
        $byType = Customer::where('status', 1)->where('mail_magazine_flg', MailMagazine::Send)->whereNotNull('mail1')
            ->selectRaw("coalesce(address_type::text, '') as tipo, count(*) as total")
            ->groupBy('tipo')->pluck('total', 'tipo');

        $qrs = $this->shops()->map(function ($shop) {
            $url = rtrim(config('crm.mypage_url'), '/') . '/register?shop=' . $shop->uid;

            return ['shop' => $shop, 'url' => $url, 'svg' => $this->qr($url)];
        });

        return view('admin.promotions.registration', [
            'qrs' => $qrs,
            'types' => collect(AddressType::cases())->map(fn ($t) => [$t->label(), (int) ($byType[$t->value] ?? 0)])
                ->push(['Sin identificar', (int) ($byType[''] ?? 0)]),
            'total' => (int) $byType->sum(),
        ]);
    }

    private function qr(string $url): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd()));

        // Quita la declaración XML para poder insertarlo en el HTML
        return preg_replace('/^<\?xml[^>]*>\s*/', '', $writer->writeString($url));
    }
}
