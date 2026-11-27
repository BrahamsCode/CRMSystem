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
 * Plantillas de los avisos de sellos (popups de スタンプ仕様管理): stamp_alert_mail_template_entry/register.php
 * (aviso previo) y stamp_shikkou_mail_template_entry/register.php (aviso de caducidad), ambas en admin_master.
 * Mismo patrón que PointTemplateController de S3: se guarda con LegacyText::db() (form2Db) y se imprime escapado.
 */
class StampTemplateController extends Controller
{
    /** tipo => [título h2, columna título, columna contenido] */
    public const TYPES = [
        'alert' => ['■スタンプ有効期限切れ事前通知メール内容登録', 'stamp_alert_mail_title', 'stamp_alert_mail_naiyou'],
        'shikkou' => ['■スタンプ失効通知メール内容登録', 'stamp_shikkou_mail_title', 'stamp_shikkou_mail_naiyou'],
    ];

    public function edit(Request $request, string $type): View
    {
        [$heading, $titleCol, $bodyCol] = self::TYPES[$type];
        $admin = AdminMaster::current();

        return view('admin.promocion.stamp.mail_template', [
            'type' => $type,
            'heading' => $heading,
            'title' => (string) $admin->{$titleCol},
            'body' => (string) $admin->{$bodyCol},
            'isTablet' => PointConfigController::isTablet($request),
            'completed' => (string) $request->input('comp') === '1',
            'surnameEnabled' => DB::table('contents_master')->where('category', 'tb_c1_customer')->where('field', 'name_input_mode_flg')->where('disp_flg', '1')->value('text_data') === '1',
            'stampEnabled' => DB::table('tb_base_system_child')->where('system_name', 'スタンプ管理')->where('tb_base_system_cd', 3)->where('system_flg', '1')->exists(),
            'visitsEnabled' => DB::table('tb_base_system_child')->where('system_name', '来店履歴管理')->where('system_flg', '1')->exists(),
        ]);
    }

    /** Solo guarda si llega título (como el legacy); vuelve al popup con comp=1, que lo cierra */
    public function update(Request $request, string $type): RedirectResponse
    {
        [, $titleCol, $bodyCol] = self::TYPES[$type];
        $title = LegacyText::db($request->input('title'));
        if ($title !== '') {
            DB::table('admin_master')->where('cd', AdminMaster::ROW_CD)->update([$titleCol => $title, $bodyCol => LegacyText::db($request->input('naiyou'))]);
        }

        return redirect()->route('admin.promocion.stamps.template', ['type' => $type, 'comp' => 1]);
    }
}
