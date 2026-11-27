<?php

namespace App\Services\Senalizacion;

use App\Models\Senalizacion\SignageWebCycle;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * Datos del reproductor web (signage/view/index.php, view/packages.php, view/ajax.php del legacy).
 * Las filas van como arrays con los valores tal cual los da PostgreSQL (como pg_fetch_assoc), porque
 * las vistas convertidas del legacy los usan así.
 */
class SignagePlayer
{
    public const NO_TERMINAL = 'データの取得に失敗しました';

    public const NO_PACKAGE = '設定されているパッケージがありません。';

    public function __construct(private readonly SignageScheduleResolver $schedule) {}

    /** view/index.php?cd=<terminal>: datos de la vista o el mensaje con el que el legacy hacía die() */
    public function forTerminal(int $terminalCd): array|string
    {
        $terminal = $this->db()->table('terminal')->where('cd', $terminalCd)->where('flg', 1)->first();
        if (! $terminal) {
            return self::NO_TERMINAL;
        }

        $data = $this->forPackage((int) $terminal->packages_cd);

        return is_array($data) ? ['_terminal_data' => (array) $terminal] + $data : $data;
    }

    /** view/packages.php?cd=<paquete> (vista previa del CMS) */
    public function forPackage(int $packagesCd): array|string
    {
        $package = $this->db()->table('packages')->where('cd', $packagesCd)->where('flg', 1)->first();
        if (! $package) {
            return self::NO_PACKAGE;
        }

        $cycles = $this->schedule->whereActive($this->db()->table('cycle')->where('packages_cd', $package->cd))
            ->orderBy('cd')
            ->get()
            ->map(function ($row) {
                $row = (array) $row;
                $row['img_path_ary'] = SignageWebCycle::unserializeArray($row['img_path_ary']);
                $row['action_ary'] = SignageWebCycle::decodeActions($row['action_ary']);

                return $row;
            })
            ->all();

        return [
            '_packages_data' => (array) $package,
            '_cycle_data' => $cycles,
            '_contents_category_data' => $this->keyed('contents_category', ['cd']),
            '_contents_data' => $this->keyed('contents', ['contents_category_cd', 'cd']),
        ];
    }

    /**
     * view/ajax.php?packages_cd=<terminal>: `update_date` de los ciclos que se muestran ahora en ese
     * terminal (el reproductor recarga la página cuando cambia). El parámetro se llama packages_cd
     * pero es el cd del terminal, como en el legacy.
     */
    public function pollRows(int $terminalCd): array
    {
        $query = $this->db()->table('cycle')
            ->join('terminal', 'cycle.packages_cd', '=', 'terminal.packages_cd')
            ->select('cycle.update_date')
            ->where('terminal.cd', $terminalCd);

        return $this->schedule->whereActive($query, 'cycle.')
            ->orderBy('cycle.cd')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /** Filas activas (flg = 1) indexadas por cd */
    private function keyed(string $table, array $order): array
    {
        $query = $this->db()->table($table)->where('flg', 1);
        foreach ($order as $column) {
            $query->orderBy($column);
        }

        return $query->get()->mapWithKeys(fn ($row) => [$row->cd => (array) $row])->all();
    }

    private function db(): Connection
    {
        return DB::connection('signage');
    }
}
