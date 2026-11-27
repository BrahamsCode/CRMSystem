<?php

namespace App\Services\Senalizacion;

use App\Models\Terminales\SignageDisplay;
use App\Models\Terminales\SignageProgram;
use Illuminate\Http\Request;

/**
 * Configuración del reproductor digitalsignage/index2.php (index.php solo hace require de index2.php).
 *
 * - shop_cd: el de la petición o 1; display_cd: obligatorio (sin él, 「ただ今システムがビジーです」).
 * - tb_display y su programa (tb_c7_movie_program_cds) se leen sin filtrar disp_flg, como el legacy:
 *   un display sin programa válido da «error1» y un display inexistente «error2».
 * - Qué hace la pantalla sale del programa: disp_conf 1 (archivos subidos) o 2 (システム内蔵, según
 *   tb_dispaly_systems_cds: 1 インフォメーション, 2 スロット, 3 鬼ごっこ, 5 ルーレット) y las banderas de
 *   cupón (act_flg, feli_act_flg, mypage_act_flg).
 */
class PlayerResolver
{
    public const BUSY = 'busy';

    public const ERROR_PROGRAM = 'error1';

    public const ERROR_DISPLAY = 'error2';

    /** @return string|array<string, mixed> un estado de error (BUSY / ERROR_*) o los datos de la vista */
    public function resolve(Request $request): string|array
    {
        $shopCd = $request->input('shop_cd') ?: 1;
        $displayCd = $request->input('display_cd');
        if (! $displayCd) {
            return self::BUSY;
        }

        $display = SignageDisplay::withDeleted()->find((int) $displayCd);
        if (! $display) {
            return self::ERROR_DISPLAY;
        }

        $programCd = (string) $display->tb_c7_movie_program_cds;
        $program = ctype_digit($programCd) ? SignageProgram::withDeleted()->find((int) $programCd) : null;
        if (! $program) {
            return self::ERROR_PROGRAM;
        }

        $files = (string) $program->disp_conf === SignageProgram::CONF_FILES;
        $system = (string) $program->disp_conf === SignageProgram::CONF_SYSTEM ? (string) $program->tb_dispaly_systems_cds : '';
        $feliCoupon = (string) $program->feli_tb_mycoupon_master_cds;
        $mypageCoupon = (string) $program->mypage_tb_mycoupon_master_cds;

        return [
            'shopCd' => (int) $shopCd,
            'displayCd' => (int) $displayCd,
            'requestShopCd' => (string) $request->input('shop_cd', ''),
            'requestDisplayCd' => (string) $displayCd,
            'program' => $program,
            'programCd' => (int) $programCd,
            // ↓デジタルサイネージの更新日を取得 (xml_mod = update_date del programa)
            'xmlMod' => (string) $program->update_date,
            'kanshiTime' => time(),
            'files' => $files,
            'system' => $system,
            // 画面タッチクーポン: el legacy mandaba la posición 0 de tb_mycoupon_master_cds (vacía si la
            // primera casilla no estaba marcada); aquí, el primer cupón marcado
            'touchCoupon' => $files && (string) $program->act_flg === '1'
                ? (string) (collect($program->couponCds())->first(fn ($cd) => $cd !== '') ?? '')
                : null,
            'mujyouken' => $files && (string) $program->feli_act_flg === '1' && $feliCoupon !== '',
            'shinkyaku' => $files && (string) $program->feli_act_flg === '2' && $feliCoupon !== '',
            'kaiinRaiten' => $files && (string) $program->feli_act_flg === '3' && $feliCoupon !== '',
            'mypage' => $files && (string) $program->mypage_act_flg === '1' && $mypageCoupon !== '',
            'feliCoupon' => $feliCoupon,
            'mypageCoupon' => $mypageCoupon,
            'roulette' => $system === '5',
            'slot' => $system === '2',
            'floormap' => $system === '1',
            'tigGame' => $system === '3',
            'setting' => (string) $program->vertical_setting === 'portrait' ? 'portrait' : 'landscape',
        ];
    }
}
