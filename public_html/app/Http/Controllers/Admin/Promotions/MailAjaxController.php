<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Mail\Promotions\PromotionMail;
use App\Models\Promotions\MailTemplate;
use App\Models\Promotions\MailmagMaster;
use App\Models\Promotions\MycouponMaster;
use App\Models\Promotions\TestMailAddress;
use App\Services\Promotions\LegacyTime;
use App\Services\Promotions\MailAudience;
use App\Services\Promotions\MailImageStore;
use App\Services\Promotions\MailRegistrar;
use App\Services\Promotions\MailRenderer;
use App\Services\Promotions\MailSender;
use App\Services\Promotions\StaffContext;
use App\Services\Promotions\SystemDomain;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use App\Support\LegacyText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * AJAX de los formularios de envío (URL del legacy, las llaman los JS de category3):
 * inc/ajax_get_template_titles.php, ajax_get_template_body.php, ajax_search_coupon.php,
 * ajax_testMailList.php, ajax_sendTestMail.php, ajax_reigai_kaiin.php, ajax_search_kaiin.php, ajax_get_shop_data.php,
 * ajax_change_status.php
 * y mail_mag/inc/upload.php, disp_pic.php (rutas relativas de staff_entry.js).
 */
class MailAjaxController extends Controller
{
    public function __construct(private readonly StaffContext $staff) {}

    /** <option> de las plantillas de una categoría */
    public function templateTitles(Request $request): Response
    {
        $templates = MailTemplate::query()
            ->where('tb_mail_template_category_cd', (int) $request->input('cd'))
            ->where('mail_type', (string) $request->input('mail_type', ''))
            ->where('disp_flg', '1')->where('hyouji_flg', '1')
            ->orderBy('rank')->get(['cd', 'title']);

        $options = '<option value="">▼選択してください</option>'.PHP_EOL;
        foreach ($templates as $template) {
            $options .= '<option value="'.$template->cd.'">'.e($template->title).'</option>'.PHP_EOL;
        }

        return response($options);
    }

    /** Cuerpo de la plantilla (texto o HTML de デコメール), tal cual */
    public function templateBody(Request $request): Response
    {
        $body = MailTemplate::query()->whereKey((int) $request->input('cd'))->where('disp_flg', '1')->where('hyouji_flg', '1')->value('naiyou');

        return response((string) $body);
    }

    /** Cupones para adjuntar: de la tienda o de todas (-1); coupon_kubun '3' en encuestas, '' en el resto */
    public function searchCoupon(Request $request): View
    {
        $query = MycouponMaster::query()->where('disp_flg', '1');
        if ($request->filled('cd')) {
            $query->where('cd', (int) $request->input('cd'));
        }
        if ($request->filled('shop_cd')) {
            $query->whereIn('tb_shop_cd', array_merge(MailAudience::cds($request->input('shop_cd')), [-1]));
        }
        if ($request->filled('title')) {
            $query->where('title', 'like', '%'.$request->input('title').'%');
        }
        $query->where('coupon_kubun', str_contains((string) $request->input('mail_type'), 'anketo') ? '3' : '');

        return view('admin.promocion.inc.ajax.search_coupon', ['coupons' => $query->orderBy('cd')->limit(200)->get()]);
    }

    /** Direcciones de prueba: todas (master) o las de su tienda */
    public function testMailList(Request $request): View
    {
        $addresses = TestMailAddress::query()->where('disp_flg', '1')
            ->when(! $this->staff->isMaster(), fn ($q) => $q->where('tb_shop_cd', $this->staff->shopCd()))
            ->orderBy('rank')->get();

        return view('admin.promocion.inc.ajax.test_mail_list', [
            'addresses' => $addresses,
            'selected' => MailAudience::cds($request->input('testMail_cds')),
        ]);
    }

    /** テストメール: con un cliente al azar del filtro (o datos de ejemplo) a las direcciones elegidas */
    public function sendTestMail(Request $request, MailRenderer $renderer, MailAudience $audience, SystemDomain $domain): Response
    {
        $type = (string) $request->input('testType');
        $deco = in_array($type, MailSender::DECO_TYPES, true);
        $renderer->forSender($request->input('testTourokusya'));
        $subject = (string) $request->input('testTitle');
        $body = (string) $request->input('testBody');
        $images = [];
        $attachments = [];
        $footer = '';

        if ($deco) {
            [$body, $images] = $renderer->adjustDeco($body);
            $footer = $renderer->defaultFooter(true);
        } elseif ($request->filled('testPic')) {
            $body .= "\n\n※画像添付あり。";
            $attachments[] = storage_path('app/public/system/category3/'.MailImageStore::folder($type).'/uploads/'
                .basename((string) $request->input('testPicShop', '')).'/'.basename((string) $request->input('testPic')));
        } else {
            $footer = $renderer->defaultFooter(false);
        }

        $uniqid = (string) $request->input('search_uniqid');
        $sample = $audience->has($uniqid) ? $audience->sample($uniqid, MailAudience::cds($request->input('reigai_kaiin_cds'))) : null;
        if ($sample === null) {
            $body = str_replace('{最終来店日}', Carbon::now(LegacyTime::ZONE)->format('m月d日'), $body);
            $sample = ['id' => '1001234', 'name' => $this->staff->user()?->name, 'kojin_birth' => Carbon::now(LegacyTime::ZONE)->format('Y/m/d'),
                'zip' => '123-4567', 'pref' => '大阪府', 'add' => '大阪市○○○', 'tel' => '0123-456-7890', 'stamp_count' => '12', 'service_point' => '1200'];
        }
        [$subject, $body] = $renderer->replace($subject, $body, $sample);
        $nl = $deco ? '<br/>' : "\n";
        $body .= $nl.$nl.'＊＊＊ここに簡単ログインが入ります＊＊＊＊'.$nl.$nl;

        $addresses = TestMailAddress::query()->whereIn('cd', MailAudience::cds($request->input('testMail_cds')))->pluck('email');
        foreach ($addresses as $to) {
            try {
                Mail::to($to)->send(new PromotionMail($subject, $body.$nl.($deco ? '' : $nl).$footer, $deco, $domain->fromAddress(),
                    $renderer->senderName(), $domain->errorAddress(), $attachments, $images));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response('送信しました。');
    }

    /** 例外顧客: type=display muestra los elegidos; si no, la lista paginada del filtro con casillas */
    public function exceptions(Request $request, MailAudience $audience): Response|View
    {
        $excluded = MailAudience::cds($request->input('reigai_kaiin_cds'));

        if ($request->input('type') === 'display') {
            $customers = $audience->customers($excluded);
            if ($customers->isEmpty()) {
                return response('');
            }
            $html = '<div style="margin:20px 0;padding: 10px;border:solid 1px #CCCCCC;">'.PHP_EOL;
            foreach ($customers as $customer) {
                $html .= '【'.e($customer->id).'】 '.e($customer->name).' ('.e($customer->email1).')<br />';
            }

            return response($html.'<input type="button" value="クリアー" onclick="fnc_KaiinClear()" />'.PHP_EOL.'</div>'.PHP_EOL);
        }

        $uniqid = (string) $request->input('search_uniqid');
        if (! $audience->has($uniqid)) {
            return response('');
        }

        return view('admin.promocion.inc.ajax.reigai_kaiin', $audience->page($uniqid, (int) $request->input('page', 1), (int) $request->input('kensu', 15) ?: 15) + [
            'excluded' => $excluded,
        ]);
    }

    /** mail_mag/inc/upload.php: "success" o "error" (lo lee ajaxupload) */
    public function upload(Request $request, MailImageStore $images, string $folder = 'mail_mag'): Response
    {
        $file = $request->file('userfile');
        $ext = strtolower((string) $request->input('ext'));
        if (! $file || ! in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
            return response('error');
        }

        $ok = $images->storeTemp($folder, $file, $ext, (string) $request->input('time'), $request->input('lastTime'), $request->input('lastExt'));

        return response($ok ? 'success' : 'error');
    }

    /** mail_mag/inc/disp_pic.php: la imagen temporal o "ERROR" */
    public function displayPicture(Request $request, MailImageStore $images, string $folder = 'mail_mag'): Response
    {
        $url = $images->tempUrl($folder, (string) $request->input('t'), (string) $request->input('ext'));

        return response($url === null ? 'ERROR' : '<br/><br/><img src="'.e($url).'" /><br/><br/>');
    }

    /**
     * ajax_search_kaiin.php (例外顧客 de follow/auto, reigai_kaiin2.js): con type=display, la ficha de cada cd
     * con su botón 解除; si no, buscador de socios activos por tienda, nombre/カナ, ID y 管理番号 (200 como máximo).
     */
    public function searchCustomers(Request $request): Response|View
    {
        if ($request->input('type') === 'display') {
            $html = '';
            foreach (DB::table('tb_customer')->whereIn('cd', MailAudience::cds($request->input('reigai_kaiin_cds')))->get(['cd', 'id', 'name', 'email1']) as $c) {
                $html .= '<div id="member_'.$c->cd.'">'.PHP_EOL
                    .'<table width="400px" style="table-layout:fixed;border:none;"><tr>'.PHP_EOL
                    .'<td width="85%" style="border-right:none;padding:1px;">【'.e($c->id).'】&emsp;'.e($c->name).'&emsp;('.e($c->email1).')</td>'.PHP_EOL
                    .'<td width="15%" style="border-left:none;padding:1px;text-align:center"><input type="button" value="解除" onclick="fnc_DeleteMember(\''.$c->cd.'\');"/></td>'.PHP_EOL
                    .'</tr></table>'.PHP_EOL.'</div>'.PHP_EOL;
            }

            return response($html);
        }

        $like = fn (string $k) => '%'.addcslashes(LegacyText::db($request->input($k)), '%_\\\\').'%';
        $rows = DB::table('tb_customer')->where('disp_flg', '1')->where('touroku_kubun', '2')
            ->when((string) $request->input('shop_cd', '') !== '', fn ($q) => $q->where('tb_shop_cd', (int) $request->input('shop_cd')))
            ->when((string) $request->input('name', '') !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', $like('name'))->orWhere('name_kana', 'like', $like('name'))))
            ->when((string) $request->input('id', '') !== '', fn ($q) => $q->where('id', 'like', $like('id')))
            ->when((string) $request->input('kanri_id', '') !== '', fn ($q) => $q->where('kanri_id', 'like', $like('kanri_id')))
            ->orderBy('id')->limit(200)->get(['cd', 'id', 'kanri_id', 'name', 'kojin_birth']);

        return view('admin.promocion.inc.ajax.search_kaiin', ['rows' => $rows]);
    }

    /** ajax_get_shop_data.php: <option> de tiendas (master: todas con una vacía; si no, la suya) */
    public function shopOptions(): Response
    {
        $html = $this->staff->isMaster() ? "<option value=\"\">&nbsp;</option>\n" : '';
        $shops = DB::table('tb_shop')->where('disp_flg', '<>', '9')
            ->when(! $this->staff->isMaster(), fn ($q) => $q->where('cd', $this->staff->shopCd()))->orderBy('cd')->get(['cd', 'kaishamei']);
        foreach ($shops as $shop) {
            $html .= '<option value="'.$shop->cd.'" >'.e($shop->kaishamei).'</option>';
        }

        return response($html);
    }

    /**
     * ajax_change_status.php: 停止 (disp_flg = 7, fuera de send_schedule) o 起動 (disp_flg vacío y de nuevo en
     * send_schedule) de envíos follow/auto/recordatorio. El legacy comprobaba el id de sesión; aquí, CSRF y el
     * permiso de 予約解除 de su tipo (kengen n-4), que es el que muestra los botones.
     */
    public function changeStatus(Request $request, MailRegistrar $registrar): Response
    {
        $st = (string) $request->input('st');
        if (! in_array($st, ['停止', '起動'], true)) {
            return response('');
        }
        foreach (MailmagMaster::query()->whereIn('cd', MailAudience::cds($request->input('cd')))->get() as $mail) {
            if (! $this->staff->isMaster() && ! Gate::allows('kengen.'.MailController::kengenNo((string) $mail->type_flg).'.4')) {
                continue;
            }
            if ($st === '停止') {
                MailmagMaster::query()->whereKey($mail->cd)->update(['disp_flg' => '7']);
                DB::table('send_schedule')->where('mag_cd', $mail->cd)->delete();
            } else {
                MailmagMaster::query()->whereKey($mail->cd)->update(['disp_flg' => '']);
                $registrar->schedule($mail->refresh());
            }
        }

        return response('');
    }
}
