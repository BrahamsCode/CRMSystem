<?php

namespace App\Services\Promotions;

use App\Mail\Promotions\PromotionMail;
use App\Models\AdminMaster;
use App\Models\Promotions\MailmagMaster;
use App\Models\Promotions\MycouponMaster;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Envía una お知らせ guardada en mailmag_master (cMailsoushin::sendMailFromMailmagMaster):
 * destinatarios con el SQL de `振分配信`, sustitución por cliente, cupón por destinatario,
 * mailmag_sended_log, conteo de errores (baja a mailmaga_flg '9'), copia a admin_master.system_mail,
 * contadores por tipo de dirección, correo メール配信完了メール a la tienda y aviso al push de la app.
 */
class MailSender
{
    public const DECO_TYPES = ['deco', 'autodeco', 'followdeco', 'remdeco', 'posremdeco'];

    /** Recordatorios: `振分配信` es una SELECT completa (no un WHERE sobre tb_customer) */
    public const RESERVATION_TYPES = ['yoyaku', 'posyoyaku', 'remdeco', 'posremdeco'];

    /** Tipos que no actualizan los contadores ni el 配信状況 */
    public const RECURRING_TYPES = ['follow', 'auto', 'followdeco', 'autodeco', 'yoyaku', 'posyoyaku', 'remdeco', 'posremdeco'];

    public function __construct(
        private readonly MailRenderer $renderer,
        private readonly SystemDomain $domain,
        private readonly CouponService $coupons,
        private readonly PosGateway $pos,
        private readonly PushTrigger $push,
    ) {}

    /**
     * @return array{total: int, pc: int, docomo: int, au: int, softbank: int, other: int}|false
     *                                                                                            false si no hubo destinatarios
     */
    public function send(int $mailmagCd, ?CarbonInterface $now = null): array|false
    {
        $now = ($now ?? now())->copy()->setTimezone(LegacyTime::ZONE);
        $mail = MailmagMaster::query()->findOrFail($mailmagCd)->getAttributes();
        $typeFlg = trim((string) ($mail['type_flg'] ?? ''));

        if ((string) ($mail['振分配信'] ?? '') === '') {
            throw new RuntimeException('「振分配信」が空白です。');
        }

        $renderer = $this->renderer->forSender($mail['登録者'] ?? null);
        $admin = $renderer->adminMaster();
        $mailType = in_array($typeFlg, self::DECO_TYPES, true) ? 'deco' : ($typeFlg === 'anketo' ? 'anketo' : '');
        $deco = $mailType === 'deco';
        $hasImage = (string) ($mail['画像有無'] ?? '') === '1';

        $baseBody = (string) ($mail['pc内容'] ?? '');
        $baseSubject = (string) ($mail['タイトル'] ?? '');
        $images = [];
        $attachments = [];
        $footer = '';

        if ($deco) {
            [$baseBody, $images] = $renderer->adjustDeco($baseBody);
            $footer = $renderer->defaultFooter(true);
        } elseif ($hasImage) {
            // Con imagen el legacy no añade el pie: [0] grande (パソコン), [1] pequeña (resto)
            $folder = $typeFlg === '' ? 'mail_mag' : $typeFlg;
            $dir = storage_path('app/public/system/category3/'.$folder.'/uploads/'.($mail['店id'] ?? ''));
            $attachments = [$dir.'/'.($mail['画像名大'] ?? ''), $dir.'/'.($mail['画像名小'] ?? '')];
        } else {
            $footer = $renderer->defaultFooter(false);
        }

        $recipients = $this->recipients($mail, $typeFlg, $admin);

        MailmagMaster::query()->whereKey($mailmagCd)->update([
            'send_count' => DB::raw('COALESCE(send_count, 0) + 1'),
            'last_send_date' => $now->getTimestamp(),
        ]);

        if ($recipients === []) {
            if (! $deco) {
                $this->push->trigger('mailmaga', $mailmagCd);
            }

            return false;
        }

        $coupon = ($typeFlg !== 'anketo' && ($mail['type_data_cd'] ?? null) !== null)
            ? MycouponMaster::query()->withDeleted()->find($mail['type_data_cd'])
            : null;

        [$logEnabled, $logMax] = array_pad(explode('<>', (string) ($admin['mailmag_sended_log'] ?? '')), 2, 0);
        $logged = 0;
        $counts = ['pc' => 0, 'docomo' => 0, 'au' => 0, 'softbank' => 0, 'other' => 0];

        foreach ($recipients as $customer) {
            $to = (string) ($customer['email1'] ?? '');
            if ($to === '') {
                continue;
            }

            $kind = match ((string) ($customer['mailmaga_email_type'] ?? '')) {
                'パソコン' => 'pc',
                'DoCoMo' => 'docomo',
                'AU' => 'au',
                'Softbank' => 'softbank',
                default => 'other',
            };
            $counts[$kind]++;

            [$subject, $body] = $renderer->replace($baseSubject, $baseBody, $customer, $mail);
            if ($hasImage) {
                $body .= "\n\n※画像添付あり。";
            }
            $body .= $renderer->simpleLogin($customer, $mailType, $mailmagCd);

            $sent = $this->deliver(new PromotionMail(
                $subject,
                $body.($deco ? '<br/>' : "\n\n").$footer,
                $deco,
                $this->domain->fromAddress(),
                $renderer->senderName(),
                $this->domain->errorAddress(),
                $deco ? [] : array_slice($attachments, $kind === 'pc' ? 0 : 1, 1),
                $images,
            ), $to);

            $customerCd = isset($customer['cd']) ? (int) $customer['cd'] : null;

            if ($sent) {
                if ($logEnabled === '1' && $logged++ < (int) $logMax && $customerCd) {
                    DB::table('mailmag_sended_log')->insert(['mailmag_master_cd' => $mailmagCd, 'tb_customer_cd' => $customerCd, 'email' => $to]);
                }
                if ($coupon && $customerCd) {
                    $this->coupons->issue($coupon, $customerCd, $now, $now->copy()->endOfDay());
                }
            } elseif ($customerCd) {
                $this->countError($customerCd, $now);
            }
        }

        // Copia a la empresa (admin_master.system_mail) con el texto sin sustituir
        if ((string) ($admin['system_mail'] ?? '') !== '') {
            $this->deliver(new PromotionMail(
                $baseSubject,
                $baseBody.($deco ? '<br/>' : "\n\n").$footer,
                $deco,
                $this->domain->fromAddress(),
                $renderer->senderName(),
                $this->domain->errorAddress(),
                $deco ? [] : array_slice($attachments, 0, 1),
                $images,
            ), (string) $admin['system_mail']);
        }

        $counts['total'] = array_sum($counts);

        if (! in_array($typeFlg, self::RECURRING_TYPES, true) || $mail['振分配信'] === '予約用メールです') {
            MailmagMaster::query()->whereKey($mailmagCd)->update([
                'pc配信数' => $counts['pc'],
                'docomo配信数' => $counts['docomo'],
                'au配信数' => $counts['au'],
                'vodafone配信数' => $counts['softbank'],
                'その他配信数' => $counts['other'],
                '配信総数' => $counts['total'],
                '配信状況' => MailmagMaster::STATUS_SENT,
            ]);
        }

        $this->notifyShop($mail, $baseSubject, $counts, $now);

        if (! $deco) {
            $this->push->trigger('mailmaga', $mailmagCd);
        }

        return $counts;
    }

    /** Envía un correo; false si el transporte falla (equivale al `mail()` === false del legacy) */
    protected function deliver(PromotionMail $mail, string $to): bool
    {
        try {
            Mail::to($to)->send($mail);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /** @return list<array<string, mixed>> */
    private function recipients(array $mail, string $typeFlg, array $admin): array
    {
        if (in_array($typeFlg, self::RESERVATION_TYPES, true)) {
            // POS fuera de alcance: los recordatorios del POS no tienen destinatarios
            if (in_array($typeFlg, SendSchedulePlanner::POS_TYPES, true)) {
                return [];
            }
            $rows = DB::select($mail['振分配信']);
        } else {
            $where = preg_replace("/ and mailmaga_flg = '1'/i", '', (string) $mail['振分配信']);

            $posCds = null;
            if ((string) ($mail['pos_search'] ?? '') !== '') {
                $posCds = implode(',', $this->pos->searchCustomerCds((int) ($mail['pos_tb_shop_cd'] ?? 0), (string) $mail['pos_search'])) ?: '-1';
            }
            $where = str_replace('{POS_RESULT}', (string) $posCds, $where);

            // mail_send_filter vacío = solo clientes que aceptan correos
            if ((string) ($admin['mail_send_filter'] ?? '') === '') {
                $where .= " AND mailmaga_flg = '1'";
            }

            $rows = DB::select('SELECT * FROM tb_customer '.$where.' ORDER BY touroku_date DESC');
        }

        return array_map(fn ($row) => (array) $row, $rows);
    }

    /** Suma un error de envío; al quinto (ya llevaba más de 3) deja de recibir correos */
    private function countError(int $customerCd, CarbonInterface $now): void
    {
        $previous = (int) DB::table('tb_customer')->where('cd', $customerCd)->value('error_send_cnt');
        DB::table('tb_customer')->where('cd', $customerCd)->update(['error_send_cnt' => $previous + 1]);

        if ($previous > 3) {
            DB::table('tb_customer')->where('cd', $customerCd)->update(['update_date' => (string) $now->getTimestamp(), 'mailmaga_flg' => '9']);
        }
    }

    /** メール配信完了メール a la tienda del envío */
    private function notifyShop(array $mail, string $subject, array $counts, CarbonInterface $now): void
    {
        $shop = DB::table('tb_shop')->where('cd', (int) ($mail['店id'] ?? 0))->first();
        if (! $shop || (string) $shop->email === '') {
            return;
        }

        $body = '「'.$shop->kaishamei."」のメール配信が完了しました。\n"
            ."以下は配信内容です。\n"
            .'メールID：'.$mail['cd']."\n"
            .'抽出条件：'.($mail['search_display'] ?? '')."\n"
            .'メールタイトル：'.$subject."\n"
            .'パソコン配信数：'.$counts['pc']."\n"
            .'DoCoMo配信数：'.$counts['docomo']."\n"
            .'AU配信数：'.$counts['au']."\n"
            .'Softbank配信数：'.$counts['softbank']."\n"
            .'その他配信数：'.$counts['other']."\n"
            .'配信総数：'.$counts['total']."\n"
            .'配信日時：'.Carbon::instance($now)->format('Y/m/d H:i:s')."\n";

        try {
            Mail::raw($body, fn ($m) => $m->to((string) $shop->email)
                ->subject('メール配信完了メール')
                ->from($this->domain->fromAddress(), 'メルマガ配信センター'));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
