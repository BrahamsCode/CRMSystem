# Tareas de cron del legacy → Scheduler de Laravel

El legacy no tenía un crontab fijo: `system/category3/class/cSendSchedule.php` lo reescribía (`crontab <archivo>`) cada vez
que se guardaba un envío o una configuración, con una línea por tarea según `admin_master`, `tb_office` y los módulos
activos. Aquí todo corre con un único `* * * * * php artisan schedule:run`; cada tarea se registra **una sola vez**
(`tests/Feature/Promotions/ScheduleSinDuplicadosTest.php`) y su condición se lee en cada `schedule:run`.

Fuente: los 17 archivos de `crm2-php8/public_html` con la guarda `PHP_SAPI` (paso 26c y 26g de `Doc/legacy-crm2/REFACTORING.md`).
Revisado por S3 el 2026-11-28.

## Tareas del crontab (cSendSchedule)

| Tarea del legacy | Frecuencia en el crontab (cSendSchedule) | Comando Laravel | Dónde se programa | Estado |
|---|---|---|---|---|
| `category3/mail_mag/mail_sche_send.php <店id> <mag_cd> <登録者>` | Una línea por envío de `mailmag_master` (`配信状況 = 1`): envío único a su `配信日時`; follow/auto/recordatorios a su `HH:MM` (auto con meses/días o días de la semana) | `promocion:send-mail` (cada minuto, `SendSchedulePlanner` decide qué sale) | `PromocionServiceProvider::TASKS` | hecho (M2a, S3) |
| `category3/decomail/mail_sche_send.php` | Igual (デコメール; mismo `mailmag_master`) | `promocion:send-mail` | `PromocionServiceProvider::TASKS` | hecho (M2a, S3) |
| `category3/point/sendPointShikkouAlertMail.php` | `min hora * * *` a `admin_master.point_shikkou_alert_time` (si falta, `tb_office.start_work_time`); con ポイント管理 activo y `point_yukoukigen_flg != 2` | `promocion:point-shikkou-alert` | `PromocionFidelizacionServiceProvider` (cada minuto con `PointExpiryMailer::isDue`) | hecho (S3, 2026-11-28) |
| `category3/point/sendPointShikkouAlertMailMultiple.php <cd>` | Una línea por fila de `point_shikkou_alert` (`flg = 1`, `alert_day` ≠ '' y ≠ '0') a su `alert_time`; mismas condiciones | `promocion:point-shikkou-alert --due` (los avisos de esta hora; `--cd=<cd>` uno concreto) | `PromocionFidelizacionServiceProvider` (cada minuto con `dueAlertCds`) | hecho (S3, 2026-11-28) |
| `category3/point/sendPointShikkouMail.php` | `min hora * * *` a `admin_master.point_shikkou_time` (si falta, `start_work_time`); mismas condiciones | `promocion:point-shikkou` | `PromocionFidelizacionServiceProvider` (cada minuto con `isDue`) | hecho (S3, 2026-11-28). El correo de 失効 está comentado en el legacy desde 2017: solo `tb_point_log` (action_flg 7) y saldo a NULL |
| `category3/point/setRegisterPoint.php` | `*/5 * * * *` con ポイント管理 activo y `application_use_flg = 1` | `promocion:register-point` | `PromocionFidelizacionServiceProvider` | hecho (F4, S2) |
| `category3/point/setPaidMemberPoint.php` | `0 10 * * *` con ポイント管理 activo y `mycolle.app_layout_mode = 1` | `promocion:paid-member-point` | `PromocionFidelizacionServiceProvider` (10:00 Asia/Tokyo) | hecho (F4, S2) |
| `category3/stamp/sendStampShikkouAlertMail.php` | `min hora * * *` a `stamp_shikkou_alert_time` (12:00 si falta); con スタンプ管理 activo y `stamp_shikkou_sousa = 1` | `promocion:stamp-shikkou-alert` | `PromocionFidelizacionServiceProvider` (cada minuto con `StampExpiryMailer::isDue`) | hecho (F5, S2) |
| `category3/stamp/sendStampShikkouMail.php` | `min hora * * *` a `stamp_shikkou_time` (12:00 si falta); mismas condiciones | `promocion:stamp-shikkou` | `PromocionFidelizacionServiceProvider` | hecho (F5, S2). Estaba también en `TASKS` cada minuto sin condición: quitado (S3, 2026-11-28) |
| `category3/push/sendFollowPush.php` | `*/5 * * * *` con プッシュ通知 activo | `promocion:send-follow-push` (no hace nada con el módulo apagado) | `PromocionServiceProvider::TASKS` | hecho (M5, S4) |
| `category1/rank/setCustomerKingakuRank.php` | `min hora * * *` a `admin_master.kingaku_rank_shori_time` (sin hora, no hay línea) | `clientes:asignar-rangos --tipo=kingaku` | `ClientesServiceProvider` (cada minuto con `RankAssigner::isScheduledAt`) | hecho (S2; comprobado en `schedule:list`) |
| `category1/rank/setCustomerRaitenRank.php` | `min hora * * *` a `raiten_rank_shori_time` | `clientes:asignar-rangos --tipo=raiten` | `ClientesServiceProvider` | hecho (S2; comprobado en `schedule:list`) |

## Otros archivos con la guarda PHP_SAPI (no están en el crontab)

| Archivo del legacy | Qué es | Laravel | Estado |
|---|---|---|---|
| `category3/mail_mag/mailsoushin.php`, `category3/decomail/mailsoushin.php` | Envío inmediato de una お知らせ (`?sh=&cd=`; desde el paso 26g solo CLI) | `promocion:send-mail <cd>` | hecho (M2a, S3) |
| `category3/visit/fix_visit_stats.php` | Reescritura única de edad/sexo/ocupación en todo `tb_raiten_rireki`; nadie la enlaza ni la programa | — | descartado: arreglo puntual de datos, no tarea periódica |
| `system/category0/config/test_fix.php` | Herramienta de prueba de BD | — | descartado: herramienta de desarrollo |
| `admin/api.php`, `appview/ms_paid_member_test.php` | `admin/` fuera de alcance; prueba de depuración (paso 36) | — | descartado: fuera de alcance / depuración |

## Tareas nuevas de Laravel (sin equivalente en el crontab)

| Comando | Programación | Dueño |
|---|---|---|
| `promocion:clean-temp` | `daily` (`TASKS`) | S3 (temporales de subida) |
| `nucleo:limpiar-subidas` | cada hora | S1 |
| `clientes:limpiar-temporales` | cada hora | S2 |

Ninguna tarea de promoción queda en «falta».
