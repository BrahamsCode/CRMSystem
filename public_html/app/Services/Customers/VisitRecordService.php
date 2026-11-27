<?php

namespace App\Services\Customers;

use App\Models\Customers\Customer;
use App\Models\Staff;
use LogicException;

/**
 * 来店履歴の追加・修正 (`system/category1/inc/kanren_record_register.php`, `kanren_del_record.php`):
 * alta o corrección de una visita con su movimiento de puntos (`tb_point_log`, `tb_customer_point`),
 * de sellos (`tb_stamp_log`, `tb_customer.stamp_count`) y sus campos 関連情報 (`tb_customer_sub` con
 * `category_inner_group`), todo en una transacción como el legacy. Recalcula `shuki_heikin`/`last_raiten_date`.
 *
 * Lo usan la pestaña 9 de la ficha (S2) y `tool/api/category1/setRaitenDetail.php` (S5).
 *
 * CONTRATO PUBLICADO (2026-11-27). La implementación llega en la tarea C3 de S2; hasta entonces lanza LogicException.
 */
class VisitRecordService
{
    /**
     * Alta (`raiten_cd` vacío) o corrección (`raiten_cd` = tb_raiten_rireki.cd) de una visita.
     *
     * $data usa las MISMAS claves que el formulario legacy (y que setRaitenDetail):
     *   raiten_cd, raiten_y, raiten_m, raiten_d, raiten_h, raiten_i, kaikei, shiharai_houhou ('1' efectivo, '2' tarjeta),
     *   shouhi_point, sounyu_point, sounyu_point_add_remove_flg, selected_shop_cd, related_features_cd, meisai_mail,
     *   y los campos 関連情報 `form<tb_search_form.cd>` (y sus variantes) de la categoría de `admin_master.related_features`.
     * Los textos se limpian como el legacy (`form2Db` sobre todo el request).
     *
     * @param  Staff|null  $staff  personal que registra (tb_staff_cd del movimiento; null = sin personal, p. ej. tool/api)
     * @return int  `tb_raiten_rireki.cd` creado o corregido
     *
     * @throws VisitRecordValidationException  con los errores del legacy (`errors()`: campo => mensaje)
     */
    public function save(Customer $customer, array $data, ?Staff $staff = null): int
    {
        throw new LogicException('VisitRecordService::save: pendiente (S2, tarea C3)');
    }

    /** Borra la visita y anula sus movimientos de puntos/sellos y sus campos (`kanren_del_record.php`) */
    public function delete(Customer $customer, int $raitenCd, ?Staff $staff = null): void
    {
        throw new LogicException('VisitRecordService::delete: pendiente (S2, tarea C3)');
    }
}
