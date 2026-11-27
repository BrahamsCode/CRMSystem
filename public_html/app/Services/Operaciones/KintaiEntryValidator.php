<?php

namespace App\Services\Operaciones;

use Carbon\CarbonImmutable;

/**
 * Comprobaciones de horas de kintai_new.php y kintai_update.php, con los mismos mensajes y el mismo orden.
 * Cada hora llega como par «時»/«分» de los selectores (時 = «05»…«28», 24+ = día siguiente); la marca es
 * mktime(時, 分, 0) del día consultado. Los mensajes llevan el «<br />» del legacy.
 */
class KintaiEntryValidator
{
    public function __construct(private readonly CarbonImmutable $day, private readonly int $windowEnd)
    {
    }

    /**
     * 勤怠データ新規追加.
     *
     * @return array{errors: string, times: array<string, int>}
     */
    public function forCreate(array $input): array
    {
        $errors = '';
        $times = [];
        $status = (string) ($input['status'] ?? '');

        // 出勤: obligatorio solo si el 勤務状況 es 出勤; si no, el último segundo de la jornada
        [$h, $m] = [self::text($input, 'shukkin_h'), self::text($input, 'shukkin_m')];
        if ($h !== '' && $m !== '') {
            $times['shukkin'] = KintaiService::at($this->day, (int) $h, (int) $m);
        } elseif ($h === '' && $m !== '') {
            $errors .= '・「出勤時間（時）」を選択してください。<br />';
        } elseif ($h !== '' && $m === '') {
            $errors .= '・「出勤時間（分）」を選択してください。<br />';
        } elseif ($status !== '出勤') {
            $times['shukkin'] = $this->windowEnd - 1;
        } else {
            $errors .= '・「出勤時間」を入力してください。<br />';
        }

        foreach (['gaishutu' => '外出', 'modori' => '戻り', 'taikin' => '退勤'] as $key => $label) {
            [$h, $m] = [self::text($input, $key.'_h'), self::text($input, $key.'_m')];
            if ($h !== '' && $m !== '') {
                $times[$key] = KintaiService::at($this->day, (int) $h, (int) $m);
            } elseif ($h === '' && $m !== '') {
                $errors .= "・「{$label}時間（時）」を選択してください。<br />";
            } elseif ($h !== '' && $m === '') {
                $errors .= "・「{$label}時間（分）」を選択してください。<br />";
            }
        }

        [$shukkin, $gaishutu, $modori, $taikin] = [$times['shukkin'] ?? null, $times['gaishutu'] ?? null, $times['modori'] ?? null, $times['taikin'] ?? null];

        if ($shukkin !== null) {
            if ($taikin !== null && $shukkin > $taikin) {
                $errors .= '・「出勤時間」が不正な値です。（「退勤時間」を上回っています）<br />';
            } elseif ($gaishutu !== null && $shukkin > $gaishutu) {
                $errors .= '・「出勤時間」が不正な値です。（「外出時間」を上回っています）<br />';
            } elseif ($modori !== null && $shukkin > $modori) {
                $errors .= '・「出勤時間」が不正な値です。（「戻り時間」を上回っています）<br />';
            }
        }
        if ($gaishutu !== null) {
            if ($modori !== null && $gaishutu > $modori) {
                $errors .= '・「外出時間」が不正な値です。（「戻り時間」を上回っています）<br />';
            } elseif ($taikin !== null && $gaishutu > $taikin) {
                $errors .= '・「外出時間」が不正な値です。（「退勤時間」を上回っています）<br />';
            }
        }
        if ($modori !== null) {
            if ($gaishutu === null) {
                $errors .= '・「外出時間」を入力してください。<br />';
            }
            if ($taikin !== null && $modori > $taikin) {
                $errors .= '・「戻り時間」が不正な値です。（「退勤時間」を上回っています）<br />';
            }
        }
        if ($taikin !== null && $gaishutu !== null && $modori === null) {
            $errors .= '・「戻り時間」を入力してください。<br />';
        }

        return ['errors' => $errors, 'times' => $times];
    }

    /**
     * 勤怠データ編集. `gaishutu`/`modori` son las horas de las marcas existentes (índice = posición en el
     * formulario); `*_new`, las de la fila «新規追加».
     *
     * @return array{errors: string, data: array<string, mixed>}
     */
    public function forUpdate(array $input): array
    {
        $errors = '';
        $status = (string) ($input['status'] ?? '');
        $data = ['shukkin' => null, 'taikin' => null, 'gaishutu' => [], 'modori' => [], 'gaishutu_new' => null, 'modori_new' => null];

        [$h, $m] = [self::text($input, 'shukkin_h'), self::text($input, 'shukkin_m')];
        if ($h !== '' && $m !== '') {
            $data['shukkin'] = KintaiService::at($this->day, (int) $h, (int) $m);
        } elseif ($status !== '出勤') {
            $data['shukkin'] = $this->windowEnd - 1;
        } else {
            $errors .= '・「出勤時間」が未入力です。<br />';
        }

        foreach (['gaishutu' => '外出', 'modori' => '戻り'] as $key => $label) {
            $count = count(self::list($input, $key.'_cd'));
            $hours = self::list($input, $key.'_h');
            $minutes = self::list($input, $key.'_m');
            for ($i = 0; $i < $count; $i++) {
                [$h, $m] = [self::scalar($hours[$i] ?? ''), self::scalar($minutes[$i] ?? '')];
                if ($h !== '' && $m !== '') {
                    $data[$key][$i] = KintaiService::at($this->day, (int) $h, (int) $m);
                } else {
                    $errors .= "・「{$label}時間（".($i + 1).'）」が未入力です。<br />';
                }
            }

            [$h, $m] = [self::text($input, $key.'_h_new'), self::text($input, $key.'_m_new')];
            if ($h !== '' || $m !== '') {
                if ($h !== '' && $m !== '') {
                    $data[$key.'_new'] = KintaiService::at($this->day, (int) $h, (int) $m);
                } else {
                    $errors .= "・「{$label}時間（新規追加）」が未入力です。<br />";
                }
            }
        }

        [$h, $m] = [self::text($input, 'taikin_h'), self::text($input, 'taikin_m')];
        if ($h !== '' || $m !== '') {
            if ($h !== '' && $m !== '') {
                $data['taikin'] = KintaiService::at($this->day, (int) $h, (int) $m);
            } else {
                $errors .= '・「退勤時間」が未入力です。<br />';
            }
        }

        return ['errors' => $errors.$this->updateConsistency($data, $input), 'data' => $data];
    }

    /** Coherencia de kintai_update.php (con los mismos «else if» y el mismo orden) */
    private function updateConsistency(array $d, array $input): string
    {
        $errors = '';
        [$shukkin, $taikin, $g, $mo, $gNew, $moNew] = [$d['shukkin'], $d['taikin'], $d['gaishutu'], $d['modori'], $d['gaishutu_new'], $d['modori_new']];
        $modoriMin = $mo[0] ?? null;
        $gaishutuMax = $g[count(self::list($input, 'gaishutu_cd')) - 1] ?? null;
        $gCount = count($g);
        $moCount = count($mo);

        if ($shukkin !== null) {
            if ($taikin !== null && $shukkin > $taikin) {
                $errors .= '・「出勤時間」が不正な値です。（「退勤時間」を上回っています）<br />';
            } elseif ($modoriMin !== null && $shukkin > $modoriMin) {
                $errors .= '・「出勤時間」が不正な値です。（「戻り時間」を上回っています）<br />';
            }
        }

        for ($i = 0; $i < $gCount; $i++) {
            if (! isset($g[$i])) {
                continue;
            }
            $n = $i + 1;
            if (isset($mo[$i]) && $g[$i] > $mo[$i]) {
                $errors .= "・「外出時間（{$n}）」が不正な値です。（「戻り時間（{$n}）」を上回っています）<br />";
            } elseif (isset($mo[$i - 1]) && $g[$i] < $mo[$i - 1]) {
                $errors .= "・「外出時間（{$n}）」が不正な値です。（「戻り時間（{$i}）」を下回っています）<br />";
            } elseif ($taikin !== null && $g[$i] > $taikin) {
                $errors .= "・「外出時間（{$n}）」が不正な値です。（「退勤時間」を上回っています）<br />";
            } elseif ($shukkin !== null && $g[$i] < $shukkin) {
                $errors .= "・「外出時間（{$n}）」が不正な値です。（「出勤時間」を下回っています）<br />";
            }
        }

        if ($gNew !== null) {
            if ($shukkin !== null && $gNew < $shukkin) {
                $errors .= '・「外出時間（新規追加）」が不正な値です。（「出勤時間」を下回っています）<br />';
            } elseif ($taikin !== null && $gNew > $taikin) {
                $errors .= '・「外出時間（新規追加）」が不正な値です。（「退勤時間」を上回っています）<br />';
            } elseif ($gCount !== $moCount && $moNew === null) {
                $errors .= '・「外出時間（新規追加）」が不正な値です。（対応する「戻り時間」が未入力です）<br />';
            } elseif ($moNew !== null && $gCount > 0) {
                // Posición del nuevo 外出 entre los existentes
                $key = null;
                foreach ($g as $k => $value) {
                    if ($gNew > $value) {
                        $key = $k;
                    } else {
                        break;
                    }
                }
                if ($key !== null && (! isset($mo[$key]) || $gNew < $mo[$key] || (isset($g[$key + 1]) && $moNew > $g[$key + 1]))) {
                    $errors .= '・「外出時間（新規追加）」または「戻り時間（新規追加）」が不正な値です。<br />';
                }
            }
        }

        for ($i = 0; $i < $moCount; $i++) {
            if (! isset($mo[$i])) {
                continue;
            }
            $n = $i + 1;
            if (! isset($g[$i])) {
                $errors .= "・「外出時間（{$n}）」を入力してください。<br />";
            }
            if ($taikin !== null && $mo[$i] > $taikin) {
                $errors .= "・「戻り時間（{$n}）」が不正な値です。（「退勤時間」を上回っています）<br />";
            }
        }

        if ($moNew !== null) {
            if ($gNew === null && $gCount === $moCount) {
                $errors .= '・「外出時間（新規追加）」を入力してください。<br />';
            }
            if ($taikin !== null && $moNew > $taikin) {
                $errors .= '・「戻り時間（新規追加）」が不正な値です。（「退勤時間」を上回っています）<br />';
            } elseif ($gCount > $moCount && $gaishutuMax !== null && $moNew < $gaishutuMax) {
                $errors .= '・「戻り時間（新規追加）」が不正な値です。（「外出時間」を下回っています）<br />';
            } elseif ($gNew !== null && $moNew < $gNew) {
                $errors .= '・「戻り時間（新規追加）」が不正な値です。（「外出時間（新規追加）」を下回っています）<br />';
            }
        }

        if ($taikin !== null && (($gNew !== null && $moNew === null) || ($gCount > $moCount && $moNew === null))) {
            $errors .= '・「戻り時間」を入力してください。<br />';
        }

        return $errors;
    }

    private static function text(array $input, string $key): string
    {
        return self::scalar($input[$key] ?? '');
    }

    private static function scalar(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private static function list(array $input, string $key): array
    {
        return is_array($input[$key] ?? null) ? array_values($input[$key]) : [];
    }
}
