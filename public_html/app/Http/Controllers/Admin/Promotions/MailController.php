<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Models\AdminMaster;
use App\Models\Promotions\MailmagMaster;
use App\Models\Promotions\MailTemplateCategory;
use App\Models\Promotions\MycouponMaster;
use App\Services\Promotions\LegacyTime;
use App\Services\Promotions\MailAudience;
use App\Services\Promotions\MailImageStore;
use App\Services\Promotions\MailRegistrar;
use App\Services\Promotions\MailRenderer;
use App\Services\Promotions\MailReplaceTags;
use App\Services\Promotions\SearchFormData;
use App\Services\Promotions\StaffContext;
use App\Support\LegacyText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Nuevo envío de メルマガ / デコメール (mail_mag/ y decomail/):
 * 発行条件選択 (mail_customer_select) → 内容設定 (mail_new, inc/mail_frm.php) →
 * 確認 (mail_new_confirm, inc/mail_confirm_frm.php) → 登録 (mail_new_send, inc/mail_send_frm.php).
 * La familia sale del parámetro de ruta `mailType` ('mail' o 'deco').
 */
class MailController extends Controller
{
    /** Campos que el formulario de redacción pinta él mismo (el resto se arrastra oculto: $frm_elements_array) */
    private const FORM_FIELDS = ['title', 'naiyou', 'FORM_tlen', 'pic_name', 'send_flg', 'tourokusya', 'bikou', 'nen', 'tuki', 'hi', 'jikan', 'fun',
        'p_s_path', 'get_coupon_cd', 'get_coupon_text', 'reigai_kaiin_cds', 'search_uniqid', 'search_display', 'mail_type', '_token', 'back_flg', 'flg', 'enter', 'act', 'mailmagcd'];

    public function __construct(
        private readonly MailAudience $audience,
        private readonly StaffContext $staff,
    ) {}

    // ------------------------------------------------------------------
    // 発行条件選択
    // ------------------------------------------------------------------

    public function select(Request $request, SearchFormData $formData, string $mailType = 'mail'): View
    {
        $uniqid = null;
        $result = null;
        $filters = [];

        if ($request->input('act') === 'up') {
            $this->audience->forget($request->input('search_uniqid'));
            $uniqid = $this->audience->store($request->all(), $this->staff->isMaster(), $this->staff->shopCd());
            $result = $this->audience->counts($uniqid);
            $filters = $request->except(['_token', 'act', 'enter', 'search_uniqid']);
        } elseif ($request->input('back_flg') === '1' && $this->audience->has($request->input('search_uniqid'))) {
            $uniqid = $request->input('search_uniqid');
            $filters = $this->audience->params($uniqid);
        }

        return view('admin.promocion.inc.mail_customer_select_frm', $formData->for($filters) + [
            'menu' => $mailType,
            'mailType' => $mailType,
            'title' => $mailType === 'deco' ? 'デコメール' : 'メール',
            'mode' => '発行条件選択',
            'activeAc' => $mailType === 'deco' ? '.ac9' : '.ac1',
            'selectAction' => route($this->route($mailType, 'select')),
            'issueAction' => route($this->route($mailType, 'create')),
            'searchFormPartial' => 'admin.promocion.inc.customer_search_form',
            'searchUniqid' => $uniqid,
            'result' => $result,
            'carry' => $request->only(['mailmagcd', 'coupon_cd', 'hakkou_cd']),
        ]);
    }

    /** この条件の会員一覧を見る (inc/customer_list.php) */
    public function customers(Request $request): View
    {
        abort_unless($this->audience->has($request->input('search_uniqid')), 404);

        return view('admin.promocion.mail_mag.customer_list', $this->audience->page($request->input('search_uniqid'), (int) $request->input('page', 1), 20) + [
            'searchUniqid' => $request->input('search_uniqid'),
        ]);
    }

    // ------------------------------------------------------------------
    // 内容設定
    // ------------------------------------------------------------------

    public function create(Request $request, MailReplaceTags $tags, MailImageStore $images, string $mailType = 'mail'): View|RedirectResponse
    {
        $uniqid = (string) $request->input('search_uniqid');
        if (! $this->audience->has($uniqid)) {
            return redirect()->route('admin.promocion.templates.index');
        }

        $in = $request->except(['_token']);
        $backFlg = ($in['back_flg'] ?? '') === '1';

        // Volver de 確認: borrar la imagen ya promovida (se vuelve a crear al confirmar)
        if ($backFlg && ($in['p_name_b'] ?? '') !== '') {
            $images->discard(MailImageStore::folder($mailType), $this->shopId(), $in['p_name_b'], $in['p_name_s'] ?? null);
        }

        $imageUrl = null;
        if (($in['mailmagcd'] ?? '') !== '' && ! $backFlg) {
            // Reenvío desde 過去配信: título, contenido, 登録者, 備考 e imagen del envío anterior
            $past = MailmagMaster::query()->findOrFail((int) $in['mailmagcd']);
            $in['title'] = $past->getAttribute('タイトル');
            $in['naiyou'] = $past->getAttribute('pc内容');
            $in['tourokusya'] = $past->getAttribute('登録者');
            $in['bikou'] = $past->getAttribute('備考');
            if ((string) $past->getAttribute('画像有無') === '1') {
                $imageUrl = $images->url(MailImageStore::folder($mailType), (string) $past->getAttribute('店id'), $past->getAttribute('画像名小'));
                $in['p_s_path'] = $imageUrl;
            }
        } elseif (($in['p_s_path'] ?? '') !== '') {
            $imageUrl = $in['p_s_path'];
        }
        if (($in['pic_name'] ?? '') !== '' && $in['pic_name'] !== 'delete') {
            $imageUrl = $images->tempUrl(MailImageStore::folder($mailType), (string) $in['pic_name'], 'jpg')
                ?? $images->tempUrl(MailImageStore::folder($mailType), (string) $in['pic_name'], 'gif');
        }

        return $this->form($mailType, $in, $uniqid, $imageUrl, $tags);
    }

    /** Pantalla de inc/mail_frm.php (alta) o inc/mail_future_frm.php (edición, $extra['registerType'] = 'update') */
    private function form(string $mailType, array $in, string $uniqid, ?string $imageUrl, MailReplaceTags $tags, array $extra = []): View
    {
        $now = Carbon::now(LegacyTime::ZONE);
        $in += ['tuki' => $now->format('m'), 'hi' => $now->format('d'), 'jikan' => $now->format('H')];

        $templateMailType = str_contains($mailType, 'deco') ? 'deco' : '';

        return view('admin.promocion.inc.mail_frm', $this->common($mailType) + $extra + [
            'in' => $in,
            'searchUniqid' => $uniqid,
            'searchDisplay' => ($in['search_display'] ?? '') !== '' ? $in['search_display'] : $this->audience->display($uniqid),
            'carry' => array_diff_key($in, array_flip(self::FORM_FIELDS)),
            'tags' => $tags->forMailForm($mailType, $this->toolName()),
            'templateCategories' => MailTemplateCategory::query()
                ->whereIn('cd', DB::table('tb_mail_template')->select('tb_mail_template_category_cd')->where('mail_type', $templateMailType)->where('disp_flg', '1')->where('hyouji_flg', '1'))
                ->where('disp_flg', '1')->where('hyouji_flg', '1')->orderBy('rank')->get(),
            'templateMailType' => $templateMailType,
            'couponEnabled' => DB::table('tb_base_system_child')->where('system_name', 'マイクーポン管理')->where('system_flg', '1')->exists(),
            'imageUrl' => $imageUrl,
            'nowYear' => (int) $now->year,
        ]);
    }

    // ------------------------------------------------------------------
    // 予定配信 (mail_future_list, mail_future, mail_future_confirm, mail_future_send, mail_kaijo)
    // ------------------------------------------------------------------

    public function future(Request $request, string $mailType = 'mail'): View
    {
        $perPage = max(1, (int) $request->input('kensu', 20));
        $query = MailmagMaster::query()
            ->where('受付メールアドレス', 'なし')
            ->where('配信状況', MailmagMaster::STATUS_PENDING)
            ->when(! $this->staff->isMaster(), fn ($q) => $q->where('店id', (string) $this->staff->shopCd()))
            ->when($mailType === 'deco',
                fn ($q) => $q->where('type_flg', 'deco'),
                fn ($q) => $q->whereRaw("coalesce(type_flg, '') IN ('', 'anketo', 'coupon')"))
            ->orderByDesc('配信日時');

        return view('admin.promocion.inc.mail_future_list_frm', $this->common($mailType) + [
            'mails' => $query->paginate($perPage, ['*'], 'page', max(1, (int) $request->input('page', 1))),
            'perPage' => $perPage,
            'pageTitle' => $mailType === 'deco' ? '予約メール配信' : '予約'.$this->toolName().'配信',
            'shopNames' => $this->shopNames(),
            'kengenNo' => self::kengenNo($mailType),
        ]);
    }

    /** 修正する: el envío programado (配信状況 = 1) en el formulario, con su búsqueda guardada */
    public function edit(Request $request, MailReplaceTags $tags, MailImageStore $images, string $mailType = 'mail'): View|RedirectResponse
    {
        $in = $request->except(['_token']);
        $mail = MailmagMaster::query()->whereKey((int) ($in['mailmagcd'] ?? 0))->where('配信状況', MailmagMaster::STATUS_PENDING)->first();
        if (! $mail) {
            return redirect()->route($this->route($mailType, 'future'));
        }
        $m = fn (string $c) => (string) $mail->getAttribute($c);
        $folder = MailImageStore::folder($mailType);

        if (($in['back_flg'] ?? '') === '1' && $this->audience->has($in['search_uniqid'] ?? null)) {
            if (($in['p_name_b'] ?? '') !== '' && $in['p_name_b'] !== 'delete') {
                $images->discard($folder, $this->shopId(), $in['p_name_b'], $in['p_name_s'] ?? null);
            }
            $uniqid = (string) $in['search_uniqid'];
            $imageUrl = ($in['pic_name'] ?? '') !== '' && $in['pic_name'] !== 'delete'
                ? ($images->tempUrl($folder, (string) $in['pic_name'], 'jpg') ?? $images->tempUrl($folder, (string) $in['pic_name'], 'gif'))
                : (($in['p_s_path'] ?? '') !== '' ? $in['p_s_path'] : null);
        } else {
            $uniqid = $this->audience->storeRaw($m('振分配信'), $m('振分条件'), $m('search_display'));
            [$nen, $tuki, $hi] = array_pad(explode('/', $m('配信日')), 3, '');
            [$jikan, $fun] = array_pad(explode(':', $m('配信時間')), 2, '');
            $imageUrl = $m('画像有無') === '1' ? $images->url($folder, $m('店id'), $m('画像名小')) : null;
            $in = [
                'mailmagcd' => (string) $mail->cd, 'title' => $m('タイトル'), 'naiyou' => $m('pc内容'), 'tourokusya' => $m('登録者'),
                'bikou' => $m('備考'), 'reigai_kaiin_cds' => $m('振分条件'), 'nen' => $nen, 'tuki' => $tuki, 'hi' => $hi,
                'jikan' => $jikan, 'fun' => $fun, 'send_flg' => '0', 'search_display' => $m('search_display'),
                'p_s_path' => (string) $imageUrl,
            ];
            if ($m('type_data_cd') !== '') {
                $in['hakkou_cd'] = $m('type_data_cd');
            }
        }

        return $this->form($mailType, $in, $uniqid, $imageUrl, $tags, [
            'registerType' => 'update',
            'canCancel' => $this->can(self::kengenNo($mailType), 4),
        ]);
    }

    /** 予定解除する (mail_kaijo.php): confirmación y, con dele = 1, borrado físico y fuera de send_schedule */
    public function cancel(Request $request, string $mailType = 'mail'): View
    {
        $cd = (int) $request->input('mailmagcd');
        $mail = MailmagMaster::query()->find($cd);

        if ($request->input('dele') == 1 && $mail) {
            DB::transaction(function () use ($mail) {
                if ((string) $mail->type_flg === 'anketo' && $mail->type_data_cd) {
                    DB::table('tb_c3_anketo_cate')->where('cd', $mail->type_data_cd)->update(['hakkou_date' => null, 'hyouji_flg' => null, 'attached_cd' => null]);
                }
                DB::table('send_schedule')->where('mag_cd', $mail->cd)->delete();
                MailmagMaster::query()->whereKey($mail->cd)->delete();
            });

            return view('admin.promocion.mail_mag.mail_kaijo', $this->common($mailType) + ['done' => true, 'mail' => null]);
        }

        return view('admin.promocion.mail_mag.mail_kaijo', $this->common($mailType) + ['done' => false, 'mail' => $mail]);
    }

    /** Permiso del legacy ($kengen->myKengen[n][f]); sin usuario (consola, pruebas) = master */
    private function can(int $contentsCd, int $function): bool
    {
        return $this->staff->isMaster() || \Illuminate\Support\Facades\Gate::allows('kengen.'.$contentsCd.'.'.$function);
    }

    /** $kengenNo de mail_future_list_frm.php */
    public static function kengenNo(string $mailType): int
    {
        return match ($mailType) {
            'deco' => 1006,
            'follow', 'followdeco', 'reminder', 'yoyaku', 'posyoyaku', 'remdeco', 'posremdeco' => 1007,
            'auto', 'autodeco' => 1008,
            default => 1005,
        };
    }

    // ------------------------------------------------------------------
    // 確認
    // ------------------------------------------------------------------

    public function confirm(Request $request, MailImageStore $images, MailRenderer $renderer, string $mailType = 'mail'): View|RedirectResponse
    {
        $uniqid = (string) $request->input('search_uniqid');
        if (! $this->audience->has($uniqid)) {
            return redirect()->route('admin.promocion.templates.index');
        }

        $deco = str_contains($mailType, 'deco');
        $update = $request->routeIs('admin.promocion.*.edit-confirm');
        $in = $request->except(['_token']);
        $dispBody = null;
        foreach ($in as $key => $value) {
            if (! is_string($value)) {
                continue;
            }
            // Como mail_confirm_frm.php: el HTML de la デコメール se guarda tal cual (KV, ' → ’, recortado);
            // el resto pasa por get_form() con < > en ancho completo
            if ($deco && $key === 'naiyou') {
                $dispBody = $in[$key] = LegacyText::trim(mb_convert_kana(str_replace("'", '’', $value), 'KV'));
            } else {
                $in[$key] = LegacyText::form(str_replace(['<', '>'], ['＜', '＞'], $value));
            }
        }

        $in['title'] = ($in['title'] ?? '') !== '' ? mb_convert_kana($in['title'], 'KV') : $this->senderTitle().'からのお知らせ！';

        $error = ($in['naiyou'] ?? '') === '' ? $this->toolName().'内容を入力して下さい。' : null;

        // La edición de follow/auto/recordatorios no tiene fecha (mail_future_confirm_frm.php)
        $past = false;
        $recurring = in_array($mailType, ['follow', 'followdeco', 'auto', 'autodeco', 'yoyaku', 'posyoyaku', 'remdeco', 'posremdeco'], true);
        if (($in['send_flg'] ?? '') === '0' && ! ($update && $recurring)) {
            $at = Carbon::createFromFormat('Y/m/d H:i:s', sprintf('%s/%s/%s %s:%s:00', $in['nen'] ?? '', $in['tuki'] ?? '', $in['hi'] ?? '', $in['jikan'] ?? '', $in['fun'] ?? ''), LegacyTime::ZONE);
            $past = $at === false || $at->lessThan(Carbon::now(LegacyTime::ZONE));
        }

        // Imagen: de un cupón, la temporal subida o la del envío anterior
        $image = null;
        $folder = MailImageStore::folder($mailType);
        if (($in['coupon_cd'] ?? '') !== '') {
            $image = $images->promoteCoupon($folder, (int) $in['coupon_cd'], $this->shopId());
        } elseif (($in['pic_name'] ?? '') !== '' && $in['pic_name'] !== 'delete') {
            $image = $images->promote($folder, (string) $in['pic_name'], $this->shopId());
            // Imagen nueva: no reutilizar la del envío anterior (en la edición mailmagcd es el propio envío)
            if (isset($in['mailmagcd']) && ! $update) {
                $in['mailmagcd'] = '';
            }
        } elseif ($update && ($in['pic_name'] ?? '') === 'delete') {
            // Quitar la imagen del envío programado al registrar
            $in['p_name_b'] = 'delete';
            unset($in['p_s_path']);
        }
        if ($image) {
            $in = array_merge($in, array_diff_key($image, ['url' => 1]));
        }

        // Vista previa con un cliente al azar del filtro
        $excluded = MailAudience::cds($in['reigai_kaiin_cds'] ?? null);
        $sample = $this->audience->sample($uniqid, $excluded);
        $body = (string) ($in['naiyou'] ?? '');
        if ($sample === null) {
            $body = str_replace('{最終来店日}', Carbon::now(LegacyTime::ZONE)->format('m月d日'), $body);
            $sample = ['id' => '1001234', 'name' => $this->staff->user()?->name, 'kojin_birth' => Carbon::now(LegacyTime::ZONE)->format('Y/m/d'),
                'zip' => '123-4567', 'pref' => '大阪府', 'add' => '大阪市○○○', 'tel' => '0123-456-7890', 'stamp_count' => '12', 'service_point' => '1200'];
        }
        [, $preview] = $renderer->forSender($in['tourokusya'] ?? null)->replace('', $body, $sample);
        $nl = $deco ? '<br/>' : "\n";
        $preview .= $nl.$nl.'<span style="color:#0000FF;">＊＊＊＊ここに簡単ログインが入ります＊＊＊＊</span>'.$nl.$nl;

        $coupon = $mailType === 'mycoupon' && ($in['hakkou_cd'] ?? '') !== ''
            ? MycouponMaster::query()->withDeleted()->find((int) $in['hakkou_cd'])
            : null;

        return view('admin.promocion.inc.mail_confirm_frm', $this->common($mailType) + [
            'in' => $in,
            'error' => $error,
            'pastTime' => $past,
            'dispBody' => $dispBody,
            'preview' => $preview,
            'image' => $image ?? (($in['p_s_path'] ?? '') !== '' ? ['url' => $in['p_s_path']] : null),
            'excludedCustomers' => $this->audience->customers($excluded),
            'shopNames' => $this->shopNames(),
            'coupon' => $coupon,
            'fromMail' => app(\App\Services\Promotions\SystemDomain::class)->fromAddress(),
            'verify' => (string) time(),
            'update' => $update,
        ]);
    }

    // ------------------------------------------------------------------
    // 登録
    // ------------------------------------------------------------------

    public function store(Request $request, MailRegistrar $registrar, string $mailType = 'mail'): View|RedirectResponse
    {
        $uniqid = (string) $request->input('search_uniqid');
        $in = $request->except(['_token']);
        $verify = (string) ($in['mail_send_verify'] ?? '');

        // Doble clic / recarga: se muestra el envío ya registrado con este token
        if ($verify !== '' && session('promocion.mail_send_verify') === $verify && session('promocion.mail_send_mailmagcd')) {
            return $this->complete($mailType, MailmagMaster::query()->findOrFail(session('promocion.mail_send_mailmagcd')), $in);
        }

        if ($request->routeIs('admin.promocion.*.update')) {
            $mail = MailmagMaster::query()->whereKey((int) ($in['mailmagcd'] ?? 0))->where('配信状況', MailmagMaster::STATUS_PENDING)->first();
            if (! $mail) {
                return redirect()->route($this->route($mailType, 'future'));
            }
            if (! $this->audience->has($uniqid) || $verify === '' || ($in['naiyou'] ?? '') === '' || ($in['jikan'] ?? '') === '' || ($in['fun'] ?? '') === '') {
                return redirect()->route('admin.promocion.templates.index');
            }
            $mail = $registrar->update($mail, $mailType, $in, $uniqid, $this->shopId());
            $this->audience->forget($uniqid);
            session(['promocion.mail_send_verify' => $verify, 'promocion.mail_send_mailmagcd' => $mail->cd]);

            return $this->complete($mailType, $mail, $in);
        }

        $invalid = ! $this->audience->has($uniqid) || $verify === '' || ($in['naiyou'] ?? '') === ''
            || (in_array($mailType, ['follow', 'followdeco', 'auto', 'autodeco'], true) && (($in['step2_jikan'] ?? '') === '' || ($in['step2_fun'] ?? '') === ''))
            || (($in['send_flg'] ?? '') === '1' && (($in['jikan'] ?? '') === '' || ($in['fun'] ?? '') === ''));
        if ($invalid) {
            return redirect()->route('admin.promocion.templates.index');
        }

        $mail = $registrar->register($mailType, $in, $uniqid, $this->shopId());
        $this->audience->forget($uniqid);
        session(['promocion.mail_send_verify' => $verify, 'promocion.mail_send_mailmagcd' => $mail->cd]);

        return $this->complete($mailType, $mail, $in);
    }

    private function complete(string $mailType, MailmagMaster $mail, array $in): View
    {
        return view('admin.promocion.inc.mail_send_frm', $this->common($mailType) + [
            'mail' => $mail->refresh(),
            'in' => $in,
            'imageUrl' => app(MailImageStore::class)->url(MailImageStore::folder($mailType), (string) $mail->getAttribute('店id'), $mail->getAttribute('画像名小')),
            'shopNames' => $this->shopNames(),
        ]);
    }

    // ------------------------------------------------------------------

    private function common(string $mailType): array
    {
        return [
            'mailType' => $mailType,
            'menu' => $mailType,
            'toolName' => $this->toolName(),
            // Bloque del menú lateral: メルマガ管理 (.ac1) o デコメール管理 (.ac9)
            'activeAc' => $mailType === 'deco' ? '.ac9' : '.ac1',
            'isMaster' => $this->staff->isMaster(),
            'shops' => $this->staff->shops(),
            'staffShopCd' => $this->staff->shopCd(),
            'senderTitle' => $this->senderTitle(),
            'routes' => [
                'select' => route($this->route($mailType, 'select')),
                'create' => route($this->route($mailType, 'create')),
                'confirm' => route($this->route($mailType, 'confirm')),
                'store' => route($this->route($mailType, 'store')),
                'future' => route($this->route($mailType, 'future')),
                'edit' => route($this->route($mailType, 'edit')),
                'editConfirm' => route($this->route($mailType, 'edit-confirm')),
                'update' => route($this->route($mailType, 'update')),
                'cancel' => route($this->route($mailType, 'cancel')),
                'past' => route($this->route($mailType, 'past')),
            ],
        ];
    }

    private function route(string $mailType, string $action): string
    {
        return 'admin.promocion.'.($mailType === 'deco' ? 'deco' : 'mail').'.'.$action;
    }

    /** $tool_name['mail_tool_name'] (getMailmagazineName): お知らせ con la app activa, si no メール */
    private function toolName(): string
    {
        return (int) AdminMaster::current()->application_use_flg === 1 ? 'お知らせ' : 'メール';
    }

    /** `$_SESSION['shop_cd']` como 店id de mailmag_master; master sin tienda = -1 (empresa) */
    private function shopId(): string
    {
        return (string) ($this->staff->shopCd() ?? -1);
    }

    /** Nombre por defecto del asunto: empresa (master) o tienda */
    private function senderTitle(): string
    {
        if ($this->staff->isMaster()) {
            return (string) DB::table('tb_office')->where('cd', 1)->value('kaishamei');
        }

        return (string) DB::table('tb_shop')->where('cd', $this->staff->shopCd())->value('kaishamei');
    }

    /** [cd => kaishamei] de todas las tiendas y -1 = empresa */
    private function shopNames(): array
    {
        return DB::table('tb_shop')->pluck('kaishamei', 'cd')->all() + ['-1' => (string) DB::table('tb_office')->where('cd', 1)->value('kaishamei')];
    }
}
