<?php

namespace App\Services\Promotions;

use App\Models\Promotions\PointLog;

/**
 * Clasificación de un movimiento de tb_point_log para ポイント集計
 * (system/category3/point/ajax_point_aggregate.php):
 * - con `kaikei` → 'accounting' (caja);
 * - `push_master_cd = 0` → 'mycolle:dailyLogin';
 * - `action_flg` de CRM o マイコレ → el propio action_flg;
 * - sin kaikei ni action_flg → 'adjust' (ajuste manual);
 * - el resto → 'other'.
 */
class PointLogClassifier
{
    /** action_flg que se suman como 獲得 (sounyu_point) */
    public const ACQUIRE_ACTIONS = [
        PointLog::ACTION_CRM_SIGNUP, PointLog::ACTION_APP_SIGNUP, PointLog::ACTION_APP_SIGN_UP, PointLog::ACTION_REFERRAL,
        PointLog::ACTION_PUSH, PointLog::ACTION_DAILY_LOGIN, PointLog::ACTION_CHECK_IN,
    ];

    public function category(array|object $row): string
    {
        $row = (array) $row;
        $kaikei = $row['kaikei'] ?? null;
        $push = $row['push_master_cd'] ?? null;
        $action = $row['action_flg'] ?? null;

        return match (true) {
            $kaikei !== null && $kaikei !== '' => 'accounting',
            $push !== null && $push !== '' && (int) $push === 0 => PointLog::ACTION_DAILY_LOGIN,
            // El legacy usa la regex '^[crm:|mycolle:]' (clase de caracteres): empieza por c, r, m, :, |, y, o, l o e
            $action !== null && preg_match('/^[crm:|mycolle:]/', (string) $action) === 1 => (string) $action,
            // `action_flg IS NULL`: una cadena vacía no es NULL y cae en 'other', como en el legacy
            $kaikei === null && $action === null => 'adjust',
            default => 'other',
        };
    }
}
