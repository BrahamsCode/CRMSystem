<?php

namespace App\Services\Customers;

use App\Models\AdminMaster;
use App\Models\BaseSystemGrandchild;

/**
 * 顧客ランク振り分け設定 (legacy: category1/rank/customer_rank_schedule.php y _register.php).
 * Columnas de admin_master por tipo (`kingaku` / `raiten`):
 *   `<tipo>_rank_shori_date`  '1<>meses<>días' (32 = 月末) o '2<>días de la semana' (0 = 日 … 6 = 土); vacío = OFF
 *   `<tipo>_rank_shori_time`  'HH:MM' (vacío = sin tarea)
 *   `<tipo>_rank_shori_kikan` días hacia atrás que se cuentan
 * El legacy reescribía el crontab (cSendSchedule); ahora ClientesServiceProvider programa clientes:asignar-rangos.
 */
class RankScheduleService
{
    /** Nombre exacto del módulo en tb_base_system_grandchild que muestra cada bloque de la pantalla */
    public const MODULES = [
        RankService::KINGAKU => '顧客ランク（使用金額ランク）',
        RankService::RAITEN => '顧客ランク（来店回数ランク）',
    ];

    public function isAvailable(string $type): bool
    {
        return BaseSystemGrandchild::query()
            ->where('system_name', self::MODULES[$type])
            ->where('system_flg', '1')
            ->exists();
    }

    /** Columnas crudas de admin_master para el tipo */
    public function raw(string $type): array
    {
        $master = AdminMaster::current();

        return [
            'date' => (string) $master->getAttribute("{$type}_rank_shori_date"),
            'time' => (string) $master->getAttribute("{$type}_rank_shori_time"),
            'kikan' => (string) $master->getAttribute("{$type}_rank_shori_kikan"),
        ];
    }

    /**
     * Lo que la pantalla muestra: active (ON/OFF), type_flg, month[], day[], week[], hour, minute, kikan.
     */
    public function settings(string $type): array
    {
        $raw = $this->raw($type);
        $settings = ['active' => $raw['date'] !== '', 'type_flg' => '', 'month' => [], 'day' => [], 'week' => []];

        if ($raw['date'] !== '') {
            $parameters = explode('<>', $raw['date']);
            if ($parameters[0] === '1') {
                $settings['type_flg'] = '1';
                $settings['month'] = explode(',', $parameters[1] ?? '');
                $settings['day'] = explode(',', $parameters[2] ?? '');
            } elseif ($parameters[0] === '2') {
                $settings['type_flg'] = '2';
                $settings['week'] = explode(',', $parameters[1] ?? '');
            }
        }

        $time = explode(':', $raw['time']);
        $settings['hour'] = $time[0];
        $settings['minute'] = $time[1] ?? '';
        $settings['kikan'] = $raw['kikan'];

        return $settings;
    }

    /**
     * customer_rank_schedule_register.php. Sin type_flg (botón 設定を解除する) todo queda vacío = OFF.
     *
     * @param  array{type_flg?: string, month?: array, day?: array, week?: array, hour?: string, minute?: string, rank_shori_kikan?: string}  $input
     */
    public function save(string $type, array $input): void
    {
        $date = match ((string) ($input['type_flg'] ?? '')) {
            '1' => '1<>' . implode(',', $input['month'] ?? []) . '<>' . implode(',', $input['day'] ?? []),
            '2' => '2<>' . implode(',', $input['week'] ?? []),
            default => '',
        };
        $hour = (string) ($input['hour'] ?? '');

        AdminMaster::query()->whereKey(AdminMaster::ROW_CD)->update([
            "{$type}_rank_shori_date" => $date,
            "{$type}_rank_shori_time" => $hour !== '' ? $hour . ':' . ($input['minute'] ?? '') : '',
            "{$type}_rank_shori_kikan" => (string) ($input['rank_shori_kikan'] ?? ''),
        ]);
    }
}
