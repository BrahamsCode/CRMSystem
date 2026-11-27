<?php

namespace App\Http\Controllers\Admin\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\Operaciones\KintaiConfig;
use App\Models\Staff;
use App\Services\Operaciones\KintaiCsv;
use App\Services\Operaciones\KintaiService;
use App\Support\LegacyCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV de 勤怠 en SJIS-win (LegacyCsv): diario de la tienda (kintai/csv_out.php, botón 「CSVとして出力する」 de la
 * lista) y mensual de un empleado (monthly_csv_out.php, desde 各種設定). Las variantes Flash para AIR
 * (csv_download/) no se migran. El legacy no comprobaba la tienda: aquí el no master solo baja la suya.
 */
class KintaiCsvController extends Controller
{
    public function __construct(private readonly KintaiService $kintai)
    {
    }

    public function daily(Request $request): StreamedResponse|RedirectResponse
    {
        if ((string) $request->query('shop', '') === '') {
            return to_route('admin.operaciones.kintai.index');
        }
        $shopCd = KintaiService::shopFor(auth('admin')->user(), $request->query('shop'));
        [$yy, $mm, $dd] = KintaiService::requestDate($request->query('yy'), $request->query('mm'), $request->query('dd'));
        $day = KintaiService::date($yy, $mm, $dd);
        [$from, $to] = KintaiService::window(KintaiConfig::forShop($shopCd), $day);

        // Nombre con los valores pedidos, como el legacy (kintai + yy mm dd)
        $name = 'kintai'.$yy.sprintf('%02d%02d', $mm, $dd).'.csv';

        return LegacyCsv::download($name, KintaiCsv::daily($this->kintai->dayEntries($shopCd, $from, $to), $day));
    }

    public function monthly(Request $request): StreamedResponse|RedirectResponse
    {
        if (! KintaiService::digits($request->query('staff'), $request->query('yy'), $request->query('mm'))) {
            return to_route('admin.operaciones.kintai.index');
        }
        $staff = Staff::query()->withDeleted()->find((int) $request->query('staff'));
        $config = $staff ? KintaiConfig::query()->where('tb_shop_cd', $staff->shop_cd)->first() : null;
        [$yy, $mm] = [min(max(1900, (int) $request->query('yy')), 9000), min(max(1, (int) $request->query('mm')), 1200)];
        $name = 'kintai'.$request->query('yy').$request->query('mm').'.csv';

        // Sin empleado o sin configuración de su tienda, el legacy no encontraba jornadas: CSV vacío
        if ($config === null) {
            return LegacyCsv::download($name, []);
        }

        [$hour, $minute] = KintaiConfig::splitTime($config->kijun_time);
        $first = KintaiService::date($yy, $mm, 1);
        $from = KintaiService::at($first, $hour, $minute);
        $to = KintaiService::at($first->addDays($first->daysInMonth), $hour, $minute);

        return LegacyCsv::download($name, KintaiCsv::monthly($this->kintai->monthEntries($staff->cd, $from, $to), $hour, $minute));
    }
}
