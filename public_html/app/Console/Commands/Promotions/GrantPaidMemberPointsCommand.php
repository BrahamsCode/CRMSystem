<?php

namespace App\Console\Commands\Promotions;

use App\Models\AdminMaster;
use App\Models\Promotions\PushMaster;
use App\Services\Integraciones\MobileShop\MobileShopClient;
use App\Services\Promotions\LegacyTime;
use App\Services\Promotions\PointService;
use App\Services\Promotions\PushTrigger;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 有料会員のポイント付与 (legacy system/category3/point/setPaidMemberPoint.php, a las 10:00 en el crontab de
 * cSendSchedule). El día de admin_master.paid_member_settings (`day`, 32 = fin de mes):
 * 1. refresca el estado de pago de los socios en extlink (MobileShopClient::fetchPaidMemberFlg);
 * 2. suma `points` (tienda -1, personal 1, terminal -100, action_flg `paidMemberPoint:Y-m`) a los socios de pago
 *    (paid_member_flg 2 o 3) cuyo servicio empezó en el mes de hace `months` meses;
 * 3. deja un push en push_master para esos socios y avisa a g-api (PushTrigger), con el mismo texto que el legacy.
 *
 * Nombre de clase distinto de `PaidMemberPointCommand` a propósito: PromocionServiceProvider::TASKS (S3) la
 * programaría cada minuto; la programa PromocionFidelizacionServiceProvider a las 10:00.
 */
class GrantPaidMemberPointsCommand extends Command
{
    protected $signature = 'promocion:paid-member-point';

    protected $description = '有料会員のポイント付与: puntos y push a los socios de pago el día configurado (setPaidMemberPoint.php)';

    public const TERMINAL_CD = -100;

    public const PUSH_TITLE = 'モバイルステーションからのお知らせ';

    /** extra_data del push: g-api solo usa `wherestr` con action = pointExpireAlert (comentario del legacy) */
    public const PUSH_EXTRA_DATA = '{"system":"crm","file":"setPaidMemberPoint","action":"pointExpireAlert"}';

    /** Condición del crontab: ポイント管理 activo y layout personalizado de la app (mycolle app_layout_mode = 1) */
    public static function shouldSchedule(): bool
    {
        return self::pointSystemEnabled() && self::customLayout();
    }

    public function handle(PointService $points, MobileShopClient $mobileShop, PushTrigger $push): int
    {
        if (! self::pointSystemEnabled()) {
            $this->line('ポイント管理が利用されていません。');

            return self::SUCCESS;
        }
        if (! self::customLayout()) {
            $this->line('カスタムレイアウトではありません。');

            return self::SUCCESS;
        }
        $admin = AdminMaster::query()->find(1);
        if (! $admin) {
            $this->line('マスタデータが異常です。');

            return self::SUCCESS;
        }

        $settings = @unserialize((string) $admin->paid_member_settings, ['allowed_classes' => false]);
        $settings = is_array($settings) ? $settings : [];
        $grant = (int) ($settings['points'] ?? 0);
        $months = (int) ($settings['months'] ?? 0);
        $payDay = (int) ($settings['day'] ?? 0);
        if ($grant <= 0 || $months <= 0 || $payDay <= 0) {
            $this->line('設定が異常です。');

            return self::SUCCESS;
        }

        $now = Carbon::now(LegacyTime::ZONE);
        if ($payDay !== $now->day && ! ($payDay === 32 && $now->day === $now->daysInMonth)) {
            return self::SUCCESS;
        }

        $this->refreshPaidMembers($mobileShop);

        // ポイント付与: socios cuyo servicio empezó en el mes de hace `months` meses
        $start = $now->copy()->startOfMonth()->subMonthsNoOverflow($months);
        $end = $start->copy()->endOfMonth()->startOfSecond();
        Log::info('paidMemberPoint', ['entry' => $start->format('Y-m-d').'~'.$end->format('Y-m-d').'の有料会員登録']);

        // El mismo SQL que el legacy: se guarda tal cual en push_master.wherestr (lo lee g-api)
        $customerSql = "SELECT * FROM tb_customer WHERE paid_member_flg IN (2, 3) AND paid_member_service_start_date BETWEEN {$start->getTimestamp()} AND {$end->getTimestamp()}";
        $customers = DB::select($customerSql);
        if ($customers === []) {
            Log::info('paidMemberPoint', ['entry' => '該当データがありません。']);

            return self::SUCCESS;
        }

        $actionFlg = 'paidMemberPoint:'.$now->format('Y-m');
        foreach ($customers as $customer) {
            $logData = $customer->cd.'【'.$customer->id.'】'.$customer->name.' - 登録日: '
                .Carbon::createFromTimestamp((int) $customer->paid_member_service_start_date, LegacyTime::ZONE)->format('Y-m-d').' - '.$grant.'ポイント';
            $result = $points->setPoint($grant, PointService::ADD, -1, 1, (int) $customer->cd, $actionFlg, self::TERMINAL_CD);
            Log::info('paidMemberPoint', ['entry' => $logData.($result['status'] === 'ok' ? ' OK' : ' ERROR')]);
        }

        $this->pushNotice($push, $customerSql, $grant);

        return self::SUCCESS;
    }

    /** 有料会員状況情報を更新: estado de pago de cada socio con paid_member_flg en extlink */
    private function refreshPaidMembers(MobileShopClient $mobileShop): void
    {
        $ids = DB::table('tb_customer')->whereNotNull('paid_member_flg')->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        $token = $mobileShop->fetchToken();
        foreach ($ids as $id) {
            $mobileShop->fetchPaidMemberFlg($token, $id);
        }
    }

    /** プッシュ通知を追加: push_master (tienda -1, sin versión ni post_type, como el legacy) y aviso a g-api */
    private function pushNotice(PushTrigger $push, string $customerSql, int $grant): void
    {
        $points4Push = number_format($grant);
        $message = <<<BODY
モバイルステーションプレミアム会員の皆様へ

いつもモバイルステーションのご利用ありがとうございます！
入会して1年が経過しましたので、{$points4Push}円分のポイントを贈呈致します。
来店頂ければ、ポイントでのアクセサリ購入又は機種変更時に使えるキャッシュバックとしてご利用頂けます！
詳しくは来店頂き、当店クルーにお声がけ下さい。
BODY;

        $now = time();
        $row = PushMaster::create([
            'tb_shop_cd' => -1,
            'title' => self::PUSH_TITLE,
            'message' => $message,
            'img_flg' => '',
            'wherestr' => $customerSql,
            'disp_flg' => '1',
            'touroku_date' => $now,
            'update_date' => $now,
            'search_display' => '',
            'version' => null,
            'post_type' => null,
            'extra_data' => self::PUSH_EXTRA_DATA,
        ]);

        $push->trigger('push', (int) $row->cd);
    }

    private static function pointSystemEnabled(): bool
    {
        return DB::table('tb_base_system_grandchild')->where('system_name', 'ポイント管理')->where('system_flg', '1')->exists();
    }

    private static function customLayout(): bool
    {
        return DB::table('contents_master')->where('category', 'mycolle')->where('field', 'app_layout_mode')
            ->where('disp_flg', '1')->where('text_data', '1')->exists();
    }
}
