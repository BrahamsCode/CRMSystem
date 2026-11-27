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
 * Envíos de la familia アンケート que el motor de S3 (MailController) no cubre por sí mismo.
 *
 * - アンケート発行 (anketo/anketo_mail_*): ya no pasa por aquí. Sus rutas van directo a MailController con
 *   ->defaults('mailType', 'anketo'); MailRegistrar marca la encuesta como 発行済 (hyouji_flg, hakkou_date,
 *   attached_cd), como mail_send_frm.php.
 * - 返信メール de お客様アンケート (anketo2/anketo_reply_*): confirm y store van directo a MailController con
 *   ->defaults('mailType', 'mail')->defaults('mailFamily', 'anketo2.reply'). Aquí quedan solo los dos pasos con
 *   lógica propia: volver a 回答一覧 con lo marcado (replySelect) y fijar los destinatarios a partir de las
 *   respuestas marcadas antes de llamar al motor (replyCreate, con los mismos defaults en su ruta).
 */
class AnketoMailController extends Controller
{
    public function __construct(private readonly MailAudience $audience) {}

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

        return app()->call([app(MailController::class), 'create'], ['mailType' => 'mail']);
    }
}
