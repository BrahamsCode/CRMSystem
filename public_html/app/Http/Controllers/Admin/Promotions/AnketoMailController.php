<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Models\Promotions\MailmagMaster;
use App\Services\Promotions\AnketoFamily;
use App\Services\Promotions\MailAudience;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Envíos de la familia アンケート con el motor común de S3 (MailController + parciales `admin/promocion/inc/*`,
 * MailAudience, MailRegistrar, MailSender), como los envoltorios del legacy que solo fijan `$menu`, `$mail_type`
 * y `$active_ac` antes de incluir `category3/inc/mail_*_frm.php`:
 *
 * - アンケート発行 (anketo/anketo_mail_customer_select|new|new_confirm|new_send.php): mail_type = anketo
 *   (type_flg = 'anketo', type_data_cd = la encuesta) y, al registrar, la encuesta queda 発行済
 *   (`tb_c3_anketo_cate`: hyouji_flg = 1, attached_cd = cupón adjunto, hakkou_date), como mail_send_frm.php.
 * - 返信メール de お客様アンケート (anketo2/anketo_reply_new|new_confirm|new_send.php): mail_type = mail con los
 *   clientes marcados en 回答一覧 como destinatarios.
 *
 * Cada paso llama a la acción de S3 y sustituye en su vista las URL de la familia (`routes`, `selectAction`,
 * `issueAction`), el título y el bloque del menú. Pedido a S3: que MailController acepte estas familias por sí
 * mismo (prefijo de rutas y activeAc por mailType) para poder enlazar sus acciones directamente.
 */
class AnketoMailController extends Controller
{
    public function __construct(private readonly MailAudience $audience) {}

    // ------------------------------------------------------------------
    // アンケート発行 (anketo)
    // ------------------------------------------------------------------

    /** anketo_mail_customer_select.php: 発行条件選択 (hakkou_cd = la encuesta, arrastrado oculto) */
    public function select(Request $request): View
    {
        $family = AnketoFamily::from('anketo');
        $view = app()->call([app(MailController::class), 'select'], ['mailType' => 'anketo']);

        return $view->with([
            'title' => 'アンケート',
            'activeAc' => $family->ac(),
            'selectAction' => $family->route('mail.select'),
            'issueAction' => $family->route('mail.create'),
        ]);
    }

    /** anketo_mail_new.php: 内容設定 */
    public function create(Request $request): View|RedirectResponse
    {
        return $this->withRoutes(app()->call([app(MailController::class), 'create'], ['mailType' => 'anketo']), $this->anketoRoutes());
    }

    /** anketo_mail_new_confirm.php: 確認 */
    public function confirm(Request $request): View|RedirectResponse
    {
        return $this->withRoutes(app()->call([app(MailController::class), 'confirm'], ['mailType' => 'anketo']), $this->anketoRoutes());
    }

    /** anketo_mail_new_send.php: 登録 y encuesta 発行済 */
    public function store(Request $request): View|RedirectResponse
    {
        $response = app()->call([app(MailController::class), 'store'], ['mailType' => 'anketo']);

        if ($response instanceof View && ($mail = $response->getData()['mail'] ?? null) instanceof MailmagMaster
            && (string) $mail->type_flg === 'anketo' && $mail->type_data_cd) {
            $row = ['hyouji_flg' => '1', 'hakkou_date' => (string) now()->getTimestamp()];
            if ((string) $request->input('get_coupon_cd') !== '') {
                $row['attached_cd'] = (int) $request->input('get_coupon_cd');
            }
            DB::table('tb_c3_anketo_cate')->where('cd', (int) $mail->type_data_cd)->update($row);
        }

        return $this->withRoutes($response, $this->anketoRoutes());
    }

    // ------------------------------------------------------------------
    // 返信メール (anketo2)
    // ------------------------------------------------------------------

    /**
     * anketo_reply_customer_select.php («条件を選び直す» del 確認): vuelve a 回答一覧 con sus filtros y las casillas
     * marcadas (el legacy las guardaba en $_SESSION['reply_mail']).
     */
    public function replySelect(Request $request): RedirectResponse
    {
        $keep = ['pcd', 'page', 'kensu', 'page2', 'kensu2', 'which', 'from_year', 'from_month', 'from_day', 'from_h', 'from_i',
            'to_year', 'to_month', 'to_day', 'to_h', 'to_i'];
        $query = array_filter($request->only($keep), fn ($v) => is_scalar($v) && (string) $v !== '');
        $marked = (array) session()->pull('promocion.anketo_reply_mail', []);
        if ($marked !== []) {
            $query['reply_mail'] = $marked;
        }

        return redirect()->to(AnketoFamily::from('anketo2')->route('answers', $query));
    }

    /**
     * anketo_reply_new.php: destinatarios = los socios de las respuestas marcadas (reply_mail[<cd de sub>]),
     * activos y con touroku_kubun = 2 como el WHERE del legacy (que los buscaba por `email1 LIKE`, y podía
     * coger a otros socios con una dirección parecida).
     */
    public function replyCreate(Request $request): View|RedirectResponse
    {
        $marked = array_filter((array) $request->input('reply_mail', []), 'is_scalar');
        if ($marked !== []) {
            session(['promocion.anketo_reply_mail' => $marked]);
            $customers = DB::table('tb_c3_anketo2_sub as s')->join('tb_customer as c', 'c.cd', '=', 's.tb_customer_cd')
                ->whereIn('s.cd', array_map('intval', array_keys($marked)))
                ->distinct()->orderBy('c.cd')->get(['c.cd', 'c.email1']);
            $cds = $customers->pluck('cd')->map(fn ($cd) => (int) $cd)->all();
            $where = "WHERE disp_flg = '1' AND touroku_kubun = '2' AND cd IN (".($cds === [] ? '0' : implode(',', $cds)).')';
            $display = $customers->pluck('email1')->unique()->map(fn ($m) => 'メールアドレス1：'.$m)->implode('<>');
            $this->audience->forget($request->input('search_uniqid'));
            $request->merge(['search_uniqid' => $this->audience->storeRaw($where, '', $display)]);
            $request->request->remove('reply_mail');
            $request->query->remove('reply_mail');
        }

        return $this->withRoutes(app()->call([app(MailController::class), 'create'], ['mailType' => 'mail']), $this->replyRoutes(), '.ac109');
    }

    /** anketo_reply_new_confirm.php */
    public function replyConfirm(Request $request): View|RedirectResponse
    {
        return $this->withRoutes(app()->call([app(MailController::class), 'confirm'], ['mailType' => 'mail']), $this->replyRoutes(), '.ac109');
    }

    /** anketo_reply_new_send.php */
    public function replyStore(Request $request): View|RedirectResponse
    {
        return $this->withRoutes(app()->call([app(MailController::class), 'store'], ['mailType' => 'mail']), $this->replyRoutes(), '.ac109');
    }

    // ------------------------------------------------------------------

    private function withRoutes(View|RedirectResponse $response, array $routes, string $ac = '.ac8'): View|RedirectResponse
    {
        if (! $response instanceof View) {
            return $response;
        }
        $data = $response->getData();

        return $response->with([
            'routes' => array_merge($data['routes'] ?? [], $routes),
            'activeAc' => $ac,
        ]);
    }

    /** URL del flujo de アンケート; programados y pasados siguen en お知らせ配信 (mail_future_list incluye type_flg anketo) */
    private function anketoRoutes(): array
    {
        $family = AnketoFamily::from('anketo');

        return [
            'select' => $family->route('mail.select'),
            'create' => $family->route('mail.create'),
            'confirm' => $family->route('mail.confirm'),
            'store' => $family->route('mail.store'),
        ];
    }

    private function replyRoutes(): array
    {
        $family = AnketoFamily::from('anketo2');

        return [
            'select' => $family->route('reply.select'),
            'create' => $family->route('reply.create'),
            'confirm' => $family->route('reply.confirm'),
            'store' => $family->route('reply.store'),
        ];
    }
}
