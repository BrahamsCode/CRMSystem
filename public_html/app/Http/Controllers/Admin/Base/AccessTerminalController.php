<?php

namespace App\Http\Controllers\Admin\Base;

use App\Http\Controllers\Controller;
use App\Support\LegacyText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CRM端末管理 (mantenimiento): slim_list.php (mapeo シリアル → tienda en `tb_access_mapping`) y las dos URL a las que
 * vuelve el popup de ac.crm-s.jp tras dar de alta o borrar el DNS del terminal (slim_href.php, slim_del_href.php).
 */
class AccessTerminalController extends Controller
{
    /** El popup de alta/borrado vive en el servidor de soporte; solo se aceptan vueltas desde allí (como el legacy) */
    private const SUPPORT_REFERER = '/ac\.crm-s\.jp/i';

    public function index(Request $request): View
    {
        if ($request->isMethod('post') && $request->input('act_flg') === 'change_tb_access_mapping') {
            $this->saveMapping((array) $request->input('tb_access_mapping', []));
        }

        return view('admin.base.access-terminals.index', [
            'rows' => DB::table('tb_access_mapping')->where('disp_flg', '1')->orderByDesc('cd')->get(),
            'shops' => DB::table('tb_shop')->select('cd', 'kaishamei')->where('disp_flg', '1')->orderBy('cd')->get(),
        ]);
    }

    /**
     * slim_href.php: registra la シリアル (`def_domain`.crm-s.net) con la tienda 1 si no estaba activa.
     * `reset` solo recarga la lista.
     */
    public function registered(Request $request): View
    {
        $error = '';
        if (! preg_match(self::SUPPORT_REFERER, (string) $request->headers->get('referer'))) {
            $error = '不正なアクセスと判断し、処理を中断しました。DNS登録は完了していますので端末をかざすことで端末登録を完了できます';
        } elseif ($request->filled('def_domain')) {
            $subdomain = LegacyText::db($request->input('def_domain')) . '.crm-s.net';
            if (DB::table('tb_access_mapping')->where('subdomain', $subdomain)->where('disp_flg', '1')->exists()) {
                $error = '既に登録があるシリアルです。DNSの登録は完了しましたが処理を完了できませんでした。既に利用可能状態である可能性もあります';
            } else {
                $now = time();
                // cd = epoch, como el legacy (tb_access_mapping no usa hatuban)
                DB::table('tb_access_mapping')->insert([
                    'cd' => $now, 'subdomain' => $subdomain, 'tb_shop_cd' => 1, 'hyouji_flg' => '1', 'disp_flg' => '1',
                    'touroku_date' => (string) $now, 'update_date' => (string) $now,
                ]);
            }
        } elseif (! $request->filled('reset')) {
            $error = '端末シリアルの受け取りに失敗しました。DNS登録は完了していますので端末をかざすことで端末登録を完了できます';
        }

        return $this->callbackView($error, $request->filled('reset'));
    }

    /** slim_del_href.php: da de baja (disp_flg = 9) la fila `data_cd` cuyo DNS ya borró el servidor de soporte */
    public function deleted(Request $request): View
    {
        $error = '';
        if (! preg_match(self::SUPPORT_REFERER, (string) $request->headers->get('referer'))) {
            $error = '不正なアクセスと判断し、処理を中断しました。DNS削除処理は完了していますがリストの削除を中断しました';
        } elseif ($request->filled('data_cd')) {
            DB::table('tb_access_mapping')->where('cd', (int) $request->input('data_cd'))->update(['disp_flg' => '9']);
        }

        return $this->callbackView($error, $request->filled('reset'));
    }

    /**
     * 設定する: tienda y On/Off de cada fila; las シリアル antiguas (sin DNS acs*) marcadas en 削除 pasan a disp_flg = 9.
     * El legacy nombraba los campos con comillas (`[0]['cd']`) y no llegaba a guardar nada: aquí se guarda lo que
     * la pantalla promete (decisión en Doc/diseno/nucleo-base.md).
     */
    private function saveMapping(array $rows): void
    {
        DB::transaction(function () use ($rows) {
            $now = (string) time();
            foreach ($rows as $row) {
                if (! is_array($row) || ! preg_match('/^[0-9]+$/', (string) ($row['cd'] ?? ''))) {
                    continue;
                }
                $values = [
                    'tb_shop_cd' => (int) ($row['tb_shop_cd'] ?? 0),
                    'hyouji_flg' => LegacyText::db($row['hyouji_flg'] ?? ''),
                    'update_date' => $now,
                ];
                if (($row['disp_flg'] ?? '') === '9') {
                    $values['disp_flg'] = '9';
                }
                DB::table('tb_access_mapping')->where('cd', (int) $row['cd'])->update($values);
            }
        });
    }

    private function callbackView(string $error, bool $reset): View
    {
        return view('admin.base.access-terminals.callback', ['error' => $error, 'reload' => $error === '' || $reset]);
    }
}
