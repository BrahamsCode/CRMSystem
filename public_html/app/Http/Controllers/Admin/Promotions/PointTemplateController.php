<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Models\AdminMaster;
use App\Support\LegacyText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Plantillas de los avisos de puntos (popups): point_alert_mail_template_entry/register.php (aviso previo 1, en
 * admin_master), point_shikkou_mail_template_* (aviso de caducidad, admin_master; lo abre 機能設定 de category0)
 * y point_shikkou_alert_mail_template_* (avisos previos 2〜10, una fila de point_shikkou_alert por cd).
 * Los register del legacy solo aplican form2Db(): se guarda con LegacyText::db() y se imprime escapado.
 */
class PointTemplateController extends Controller
{
    /** tipo => [título h2, columna título, columna contenido, cierre del popup al guardar] */
    public const TYPES = [
        'alert' => ['■ポイント有効期限切れ事前通知メール内容登録', 'point_alert_mail_title', 'point_alert_mail_naiyou', 'popUpWinReloadClose'],
        'shikkou' => ['■ポイント失効通知メール内容登録', 'point_shikkou_mail_title', 'point_shikkou_mail_naiyou', 'popUpWinReloadClose'],
        'shikkou_alert' => ['■ポイント有効期限切れ事前通知メール内容登録', 'title', 'message', 'popUpWinClose'],
    ];

    public function edit(Request $request, string $type): View
    {
        [$heading, $titleCol, $bodyCol, $close] = self::TYPES[$type];
        $cd = $this->cd($request);
        $row = $type === 'shikkou_alert'
            ? ($cd ? (array) DB::table('point_shikkou_alert')->where('flg', '1')->where('cd', $cd)->first() : [])
            : AdminMaster::current()->getAttributes();

        return view('admin.promocion.point.mail_template', [
            'type' => $type,
            'heading' => $heading,
            'title' => (string) ($row[$titleCol] ?? ''),
            'body' => (string) ($row[$bodyCol] ?? ''),
            'cd' => $type === 'shikkou_alert' ? (string) $request->input('cd', '') : null,
            'close' => $close,
            'isTablet' => PointConfigController::isTablet($request),
            'completed' => (string) $request->input('comp') === '1',
            'surnameEnabled' => DB::table('contents_master')->where('category', 'tb_c1_customer')->where('field', 'name_input_mode_flg')->where('disp_flg', '1')->value('text_data') === '1',
            'pointEnabled' => DB::table('tb_base_system_grandchild')->where('system_name', 'ポイント管理')->where('tb_base_system_cd', 1)->where('system_flg', '1')->exists(),
            'visitsEnabled' => DB::table('tb_base_system_child')->where('system_name', '来店履歴管理')->where('system_flg', '1')->exists(),
        ]);
    }

    /** Solo guarda si llega título (como el legacy); vuelve al popup con comp=1, que lo cierra */
    public function update(Request $request, string $type): RedirectResponse
    {
        [, $titleCol, $bodyCol] = self::TYPES[$type];
        $title = LegacyText::db($request->input('title'));
        if ($title !== '') {
            $values = [$titleCol => $title, $bodyCol => LegacyText::db($request->input('naiyou'))];
            if ($type === 'shikkou_alert') {
                if ($cd = $this->cd($request)) {
                    DB::table('point_shikkou_alert')->where('cd', $cd)->update($values);
                }
            } else {
                DB::table('admin_master')->where('cd', AdminMaster::ROW_CD)->update($values);
            }
        }

        return redirect()->route('admin.promocion.points.template', ['type' => $type, 'comp' => 1]);
    }

    /** cd de point_shikkou_alert (numeric de 16 cifras): solo dígitos sin cero inicial */
    private function cd(Request $request): ?string
    {
        $cd = (string) $request->input('cd', '');

        return preg_match('/^[1-9][0-9]*$/', $cd) ? $cd : null;
    }
}
