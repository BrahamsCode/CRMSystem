<?php

namespace App\Services\Senalizacion;

use App\Models\Senalizacion\SignageWebCycle;
use App\Models\Senalizacion\SignageWebPackage;
use App\Models\Senalizacion\SignageWebTerminal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Altas y cambios de la gestión de `signage/` (list/terminal.php, list/packages.php, list/cycle.php).
 */
class SignageCmsService
{
    /** Ciclo nuevo de un paquete: siempre visible, marco 1 (list/packages.php) */
    private const NEW_CYCLE = [
        'flg' => 1,
        'status' => 1,
        'flame_cd' => 1,
        'schedule_repeat_type' => SignageWebCycle::ALWAYS,
    ];

    /** 端末: alta (flg = 1 siempre, como el legacy) o cambio */
    public function saveTerminal(array $in, ?SignageWebTerminal $terminal = null): SignageWebTerminal
    {
        $terminal ??= new SignageWebTerminal(['flg' => 1]);
        $terminal->fill($this->display($in) + [
            'name' => $in['name'] ?? null,
            'packages_cd' => (int) ($in['packages_cd'] ?? 0),
        ]);
        if ($terminal->exists) {
            $terminal->flg = (int) ($in['flg'] ?? 0);
        }
        $terminal->save();

        return $terminal;
    }

    /**
     * パッケージ: el alta crea un ciclo por cada `cycle`; el cambio activa (o crea) ciclos si sube
     * y desactiva los últimos (flg = 0, no los borra) si baja, comparando con `above_cycle` del formulario.
     */
    public function savePackage(array $in, ?SignageWebPackage $package = null): SignageWebPackage
    {
        return DB::connection('signage')->transaction(function () use ($in, $package) {
            $isNew = $package === null;
            $package ??= new SignageWebPackage;
            $cycles = (int) ($in['cycle'] ?? 0);
            $package->fill($this->display($in) + [
                'flg' => (int) ($in['flg'] ?? 0),
                'name' => $in['name'] ?? null,
                'terminal_cd' => (int) ($in['terminal_cd'] ?? 0),
                'cycle' => $cycles,
            ])->save();

            if ($isNew) {
                for ($i = 0; $i < $cycles; $i++) {
                    $package->cycles()->create(self::NEW_CYCLE);
                }
            } else {
                $this->resizeCycles($package, $cycles - (int) ($in['above_cycle'] ?? 0));
            }

            return $package;
        });
    }

    private function resizeCycles(SignageWebPackage $package, int $diff): void
    {
        // Como el legacy: solo cambia flg (sin tocar update_date)
        for ($i = 0; $i < $diff; $i++) {
            $off = $package->cycles()->where('flg', 0)->orderBy('cd')->first();
            $off ? $package->cycles()->whereKey($off->cd)->toBase()->update(['flg' => 1]) : $package->cycles()->create(self::NEW_CYCLE);
        }
        for ($i = 0; $i < -$diff; $i++) {
            $on = $package->cycles()->where('flg', 1)->orderByDesc('cd')->first();
            $on && $package->cycles()->whereKey($on->cd)->toBase()->update(['flg' => 0]);
        }
    }

    /** cd del primer ciclo de cada paquete (botón 詳細 de パッケージ一覧) */
    public function firstCycleCds(): array
    {
        return SignageWebCycle::query()->toBase()
            ->selectRaw('packages_cd, min(cd) as cd')
            ->groupBy('packages_cd')
            ->pluck('cd', 'packages_cd')
            ->all();
    }

    /**
     * サイクル: 状態, 表示方法 y su franja, 表示フレーム e imágenes/efectos (serialize como el legacy).
     * 2 毎日特定の時間帯のみ guarda la hora con la fecha de hoy; 3 特定の期間のみ las dos fechas.
     */
    public function saveCycle(SignageWebCycle $cycle, array $in): SignageWebCycle
    {
        $type = (int) ($in['schedule_repeat_type'] ?? 0);
        [$start, $end] = match ($type) {
            SignageWebCycle::PERIOD => [$this->dateTime($in, 'start'), $this->dateTime($in, 'end')],
            SignageWebCycle::DAILY => [$this->todayAt($in, 'start'), $this->todayAt($in, 'end')],
            default => [null, null],
        };

        $cycle->fill([
            'status' => (int) ($in['status'] ?? 0),
            'schedule_repeat_type' => $type,
            'schedule_start_time' => $start,
            'schedule_end_time' => $end,
            'flame_cd' => (int) ($in['flame_cd'] ?? 0),
            'img_path_ary' => serialize($this->plain($in['img_path_ary'] ?? null)),
            'action_ary' => serialize($this->plain($in['action_ary'] ?? null)),
        ])->save();

        return $cycle;
    }

    /** Detalle del ciclo como lo usa list/cycle.php: arrays decodificados y fecha partida en y/m/d/h/i/s */
    public function cycleDetail(SignageWebCycle $cycle): array
    {
        $row = (array) SignageWebCycle::query()->toBase()->where('cd', $cycle->cd)->first();
        foreach (['start', 'end'] as $edge) {
            [$date, $time] = array_pad(explode(' ', explode('+', (string) $row["schedule_{$edge}_time"])[0]), 2, null);
            [$y, $m, $d] = array_pad(explode('-', (string) $date), 3, null);
            [$h, $i, $s] = array_pad(explode(':', (string) $time), 3, null);
            foreach (compact('y', 'm', 'd', 'h', 'i', 's') as $part => $value) {
                $row["schedule_{$edge}_time_{$part}"] = $value;
            }
        }
        $row['img_path_ary'] = SignageWebCycle::unserializeArray($row['img_path_ary']);
        $row['action_ary'] = SignageWebCycle::decodeActions($row['action_ary']);

        return $row;
    }

    /** display_direction / display_number / display_type del formulario (patrón de pantalla) */
    private function display(array $in): array
    {
        $type = $in['display_type'] ?? null;

        return [
            'display_direction' => in_array($in['display_direction'] ?? null, ['横', '縦'], true) ? $in['display_direction'] : null,
            'display_number' => isset($in['display_number']) && $in['display_number'] !== '' ? (int) $in['display_number'] : null,
            'display_type' => is_string($type) && preg_match('/^[hv]_[1-9]_[1-9]$/', $type) ? $type : null,
        ];
    }

    private function dateTime(array $in, string $edge): string
    {
        $n = fn (string $part) => (int) ($in["{$edge}_{$part}"] ?? 0);

        return Carbon::create($n('y'), $n('m'), $n('d'), $n('h'), $n('i'), 0)->format('Y-m-d H:i:s');
    }

    private function todayAt(array $in, string $edge): string
    {
        return now()->setTime((int) ($in["{$edge}_h"] ?? 0), (int) ($in["{$edge}_i"] ?? 0), now()->second)->format('Y-m-d H:i:s');
    }

    /** Solo arrays de escalares (lo que manda el formulario); null si no llegó, como serialize() del legacy */
    private function plain(mixed $value): mixed
    {
        if (! is_array($value)) {
            return null;
        }
        array_walk_recursive($value, function (&$v) {
            $v = is_scalar($v) ? (string) $v : null;
        });

        return $value;
    }
}
