<?php

namespace App\Services\Promotions;

use App\Models\AdminMaster;
use App\Models\Shop;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Datos de メールマガジン基本情報 / QRコード情報 (mail_mag/base.php y sus variantes base_mailmaga.php,
 * base_mycolle.php y base_point.php). Las 4 pantallas del legacy son copias del mismo archivo con bloques
 * quitados; aquí son un `type` con los bloques que muestra cada una.
 */
class QrBaseInfo
{
    /** Bloques de cada variante: counts = メール会員登録状況, app = QR de la app (si application_use_flg), mobile = 携帯WEBマイページ */
    public const TYPES = [
        'base' => ['counts' => true, 'app' => 'mycolle', 'mobile' => true],
        'mailmaga' => ['counts' => true, 'app' => null, 'mobile' => true],
        'mycolle' => ['counts' => false, 'app' => 'mycolle', 'mobile' => false],
        'point' => ['counts' => false, 'app' => 'point', 'mobile' => false],
    ];

    /** URL de qr_img.php (pública, como el legacy) */
    public const QR_PATH = '/system/category3/mail_mag/qr_img.php';

    public function __construct(
        private readonly StaffContext $staff,
        private readonly SystemDomain $domain,
    ) {}

    /**
     * Datos de la vista.
     *
     * @param  string  $serverName  `$_SERVER['SERVER_NAME']` (lo que codifican los QR de la app)
     * @return array<string, mixed>
     */
    public function forType(string $type, string $serverName): array
    {
        $blocks = self::TYPES[$type];
        $shops = $this->shops();
        $appSection = $blocks['app'] !== null && (string) AdminMaster::current()->application_use_flg === '1'
            ? $this->appQrs($blocks['app'], $shops, $serverName)
            : null;

        return [
            'type' => $type,
            'counts' => $blocks['counts'] ? $this->counts() : null,
            'hasShops' => $shops->isNotEmpty(),
            'appTitle' => $blocks['app'] === 'point' ? 'ポイント用QRコード' : 'マイコレ用の会員登録用QRコード',
            'appQrs' => $appSection,
            'mobileQrs' => $blocks['mobile'] ? $this->mobileQrs($shops) : null,
        ];
    }

    /**
     * メール会員登録状況: clientes con touroku_kubun 2, mailmaga_flg 1 y disp_flg 1 por mailmaga_email_type
     * (sin filtro de tienda, como el legacy). その他 suma "その他" y los tipos desconocidos.
     *
     * @return array{pc: int, docomo: int, au: int, softbank: int, other: int, total: int}
     */
    public function counts(): array
    {
        $byType = DB::table('tb_customer')
            ->where('touroku_kubun', '2')->where('mailmaga_flg', '1')->where('disp_flg', '1')
            ->groupBy('mailmaga_email_type')
            ->selectRaw('mailmaga_email_type AS type, count(*) AS n')
            ->get()
            ->mapWithKeys(fn ($row) => [(string) $row->type => (int) $row->n]);

        $counts = [
            'pc' => $byType['パソコン'] ?? 0,
            'docomo' => $byType['DoCoMo'] ?? 0,
            'au' => $byType['AU'] ?? 0,
            'softbank' => $byType['Softbank'] ?? 0,
        ];
        $total = (int) $byType->sum();

        return $counts + ['other' => $total - array_sum($counts), 'total' => $total];
    }

    /** Tiendas con disp_flg 1: todas para master, solo la propia para el resto, por cd */
    public function shops(): Collection
    {
        return Shop::query()->where('disp_flg', '1')
            ->when(! $this->staff->isMaster(), fn ($q) => $q->where('cd', (int) $this->staff->shopCd()))
            ->orderBy('cd')
            ->get(['cd', 'kaishamei']);
    }

    /**
     * QR de la app: uno por tienda y el de 店舗コード埋め込み無し.
     * mycolle codifica `<SERVER_NAME>?shop_cd=<cd>`; point, la URL de viewUsePointInfo del API (push_api_host).
     *
     * @return list<array{name: string, query: string}>
     */
    private function appQrs(string $kind, Collection $shops, string $serverName): array
    {
        if ($kind === 'point') {
            $api = (int) AdminMaster::current()->push_api_host === 0 ? 'b-develop.g-api.net' : 'b.g-api.net';
            $base = 'http://'.$api.'/api/mpa/viewUsePointInfo%3fdomain='.$serverName;
            $withShop = fn ($cd) => $base.'%26shop_cd='.$cd;
        } else {
            $base = $serverName;
            $withShop = fn ($cd) => $base.'%3fshop_cd='.$cd;
        }

        $qrs = $shops->map(fn ($shop) => ['name' => (string) $shop->kaishamei, 'query' => 'd='.$withShop($shop->cd)])->all();
        $qrs[] = ['name' => '店舗コード埋め込み無し', 'query' => 'd='.$base];

        return $qrs;
    }

    /**
     * 携帯WEBマイページ用の会員登録用QR: `<hp_address>mailsyori/base.php?subject=<cd>` en JPEG, Q, módulo 3
     * (el `yoshi=<time>` del legacy evita la caché del navegador).
     *
     * @return list<array{name: string, query: string}>
     */
    private function mobileQrs(Collection $shops): array
    {
        $hp = $this->domain->hpAddress();
        $time = time();

        return $shops->map(fn ($shop) => [
            'name' => (string) $shop->kaishamei,
            'query' => 'd='.$hp.'mailsyori/base.php?subject='.$shop->cd.'&e=Q&s=3&t=j&yoshi='.$time,
        ])->all();
    }

    /** `data-qr_code` del legacy: URL absoluta de qr_img.php en el dominio del sistema ($hp_domain) */
    public function absoluteQrUrl(string $query): string
    {
        return 'http://'.$this->domain->domain().self::QR_PATH.'?'.$query;
    }
}
