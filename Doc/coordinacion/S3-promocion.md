# S3 — Promoción (販売促進管理)

**Modelo:** Sonnet 5.5, esfuerzo medio · **Carpeta:** `CRMSystem-promocion/` · **Rama:** `l13/promocion`
**Módulos:** 販売促進管理 (`promocion`): mensajería y fidelización. **Es dueña de:** sus carpetas según `Doc/contratos/CONVENCIONES.md` (secciones 2 y 3).

## Modo autónomo

Trabajas sin pedir confirmación. Al terminar cada tarea: actualizar este archivo y seguir con la siguiente. Si falta un detalle menor, decide lo más simple que respete las convenciones, anótalo en "Decisiones" y sigue. Al terminar todo o bloquearte: `SendMessage` a "S0 Coordinador CRM" (una línea: qué hiciste, qué sigue, qué necesitas). Cada entregable terminado con pruebas en verde: `git add` de tus rutas, commit (mensaje en español) y `git push origin <tu rama>`; después avisa a S0, que integra en `laravel13`. No haces merge ni cambias de rama; nunca `--force`.

## Próximo paso — F1 (マイクーポン)

Alta/edición/lista/detalle/borrado, emisión con filtro (reutiliza `MailAudience` y `inc/mail_*_frm` con `mailType = mycoupon`), historial, ranking, uso, envío por correo; `CouponService::markUsed/markPrinted` para S4/S5.

M2 terminado (2026-11-27): M2c con 予定配信 (lista, 修正 reutilizando `inc/mail_frm` con `registerType = update`, 確認/登録 de edición, 予定解除 con borrado físico) y 過去配信 (lista con búsqueda, tasa de clics y push, borrado lógico; 詳細 con クリックカウンタ por sexo/edad/ocupación y CSV de clics; 配信先 con apertura y CSV), para mail_mag y decomail; menú メルマガ管理 y デコメール管理 (`.ac9`). Pruebas `M2MailHistoryTest` (6); suite 315 en verde.

M2b terminado (2026-11-27, 651e247): 新規メール/デコメール配信 completo (`MailController`, `MailAjaxController`, `MailAudience`, `MailRegistrar`, `MailImageStore`, vistas `inc/mail_frm`, `mail_confirm_frm`, `mail_send_frm`); pruebas `M2MailFlowTest` (7). Pedido de S5 (b2ab4e3): `PointService::exchangeSalesItem`. Texto según CONVENCIONES §4 (`Form2Db` en M1, `LegacyText::form` en 確認).

M2a (motor) terminado (2026-11-26): `MailRenderer` (pie, etiquetas, enlace a Mi página, ajuste デコメール), `MailSender` (envío desde `mailmag_master` como `cMailsoushin`), `PromotionMail`, `SystemDomain`, `PushTrigger`/`GApiPushTrigger` (mismo GET a g-api que `Push.php::exec`) y `promocion:send-mail` (cada minuto, con `SendSchedulePlanner`; `promocion:send-mail <cd>` envía ya). Pruebas `MailSenderTest` (9); suite 199 en verde. Sigue M2b: pantallas 発行条件選択 → redacción → confirmación → registro.

M1 terminado (2026-11-26): `TemplateController`, `TemplateCategoryController`, `TestAddressController` (Admin/Promotions), Form Requests, servicios `RankOrder`, `StaffContext`, `MailReplaceTags`; rutas `routes/modulos/promocion.php` (pantallas `admin.promocion.*`) y `routes/legacy/promocion.php` (AJAX/registros con la URL del legacy, `legacy.promocion.*`); menú `config/menu/promocion.php` (bloque メルマガ管理). Pruebas `M1TemplatesTest` (11); suite completa 190 en verde. Las pruebas montan un layout mínimo y cargan `routes/legacy/promocion.php` mientras S1 no publique layout, login del personal y carga de `routes/legacy`.

## Diseño v2 (aprobado)

2026-11-26: diseño rehecho sobre la BD y la UI del legacy: paridad con tablas `tb_*` por vista, un modelo por tabla en `App\Models\Promotions\`, §2b UI (CSS/JS por submódulo), §3 servicios reaprovechando el stash, orden (≈ 83 pts) y pedidos. Al implementar, recuperar la lógica con `git stash show -p` del stash `obsoleto: tablas nuevas`.

### Antes (histórico)

2026-11-26: S0 detuvo la capa de datos (Antony: misma BD y misma UI que el legacy, `database/schema/pgsql-schema.sql`; modelos Eloquent sobre las `tb_*`, sin tablas nuevas). El trabajo sin commit (migraciones `promocion_*`, modelos y factories nuevos, `PointRuleResolver`, `LotteryService`, `CouponIssuer::markPrinted`, conversores `Legacy/`, `PromocionServiceProvider`, `PosGateway`, `ReservationReminders` y sus pruebas) quedó en `git stash` con el mensaje `obsoleto: tablas nuevas`. La lógica de servicios y el Scheduler siguen valiendo: recuperarlos de ahí al rehacer sobre las tablas legacy. No se llegó a correr la suite completa con ese código (`migrate:fresh --seed` sí pasó).

## (Hecho) Diseño (sin código)

Escribir `Doc/diseno/promocion.md`:
1. **Tabla de paridad:** una fila por cada vista del análisis (`Doc/analisis-legacy/03a-promocion-mensajeria.md` y `03b-promocion-fidelizacion.md`): archivo legacy, título (ja), ruta Laravel (`admin.<modulo>.*`), controlador@método, tablas nuevas, estado (`pendiente` / `descartado: motivo`).
2. **Modelos sobre la BD del legacy** (`CONVENCIONES.md`, sección 0): qué `tb_*` usa cada vista, un modelo Eloquent por tabla (`$table`, `$primaryKey = 'cd'`, columnas tal cual, `disp_flg = 9`, `hatuban`) y sus relaciones. Sin tablas nuevas salvo que sea imprescindible (y compatible con el legacy).
2b. **UI:** cada pantalla reproduce el HTML y los CSS del legacy (mismas clases, mismos textos); anota qué JS del legacy necesita.
3. **Servicios viejos → Laravel 13:** cada uno con su reemplazo (Mail + cola, Scheduler, StreamedResponse, Storage, Policies…).
4. **Orden de implementación** por submódulo, con dependencias de otros grupos y estimación en puntos (1 punto ≈ media jornada).
5. **Pedidos** a S1 (núcleo) y a otros grupos.
Criterio de terminado: todas las vistas del análisis tienen fila; avisar a S0 y esperar su revisión. Mientras esperas, puedes preparar factories y seeders de tu grupo (en tus carpetas).

## Después (cuando S0 apruebe el diseño y el núcleo esté integrado)

Implementar submódulo por submódulo en el orden del diseño: migraciones, modelos, Form Requests, controladores, vistas Blade (textos en `lang/ja/<modulo>.php`), rutas en `routes/modulos/<modulo>.php`, menú en `config/menu/<modulo>.php`, pruebas Pest. Cada submódulo terminado: pruebas en verde (`bash scripts/en-docker.sh php artisan test`), filas en `hecho`, commit y push de tu rama, y aviso a S0.

**Lo anterior de CRMSystem (promociones sobre tablas nuevas, UI Tailwind) se borró:** se rehace sobre `mailmag_master`, `tb_mycoupon*`, `tb_point_*`, `tb_stamp_*`… con la UI del legacy. El código viejo está en git (`git show 8d84c98:public_html/app/...`) solo como referencia de lógica. **El crontab del legacy (`cSendSchedule`) se reemplaza por el Scheduler**, registrado en `app/Providers/PromocionServiceProvider.php`; las tareas de puntos, sellos y rangos también son tuyas. Es el grupo más grande: si S0 lo decide, se parte en dos sesiones (mensajería / fidelización).

## Lee solo

- `CLAUDE.md`, `Doc/contratos/CONVENCIONES.md` (sección 0 primero), `Doc/analisis-legacy/README.md` (hallazgos transversales)
- Del análisis, por secciones (grep del título): `Doc/analisis-legacy/03a-promocion-mensajeria.md` y `03b-promocion-fidelizacion.md`
- Para dudas de comportamiento: el código en `C:\Users\User\VivaTech\crm2-php8\public_html` (solo lectura) y `Doc/legacy-crm2/MANUAL-TESTS.md` (tu módulo)

## Pedidos

Detalle en `Doc/diseno/promocion.md` §5. Resumen:
- **S1:** cargar `routes/modulos/promocion.php` y `routes/publico/promocion.php`; registrar `PromocionServiceProvider`; niveles de permiso; módulos activables (プッシュ通知, ガチャリン, スマトピ, ポイント交換クーポン, 印刷…); config global (`system_mail`, `mail_footer`…); paquete PDF y editor HTML (npm); staff; importador con el mapeo §2.3.
- **S2:** modelo `Reservation` (recordatorio); que las pantallas de `category3/reservation/` pasen a S2 (decide S0); `CustomerSearch` y `admin.customers.show`; `customer_devices`; perfil (edad/sexo/ocupación) en `visits`.
- **S5:** `appview`/`mpa_*` llaman a `LoyaltyService`/`PushSender` en vez de escribir tablas.
- **S0/Antony:** push (el servidor `b.g-api.net` lee `push_master`/`mailmag_master` del CRM → propuesta `FcmPushSender` con credenciales FCM); uso real de 新着情報, DM発行 y スマトピ; importe de descuento de los cupones migrados; CSV SJIS-win o UTF-8.
- **S4 (aceptados):** sorteo con `LotterySetting`/`LotteryPrize` + `lottery_draws`; `CouponIssuer::markPrinted/markUsed`; pivotes `coupon_signage_*`.

## Decisiones

Ver `Doc/diseno/promocion.md` §6. Las principales: controladores en `Admin/Promotions` (existente) con rutas/vistas/lang/menú `promocion`; ninguna pantalla en `hecho` porque la base existente no cumple aún la paridad (textos fuera de `lang/ja`, sin Form Requests ni Policies, faltan campos); `anketo`+`anketo2` → `surveys.type`; `hyouji_flg` → `status`; históricos de envío como solo lectura (la SQL `振分配信` no se migra); saldos de puntos/sellos como movimiento inicial.

## Hecho

- 2026-11-27 — M2c: 予定配信 y 過去配信 de メルマガ/デコメール (M2 completo).

- 2026-11-27 — M2b: 新規メール/デコメール配信; S5: `exchangeSalesItem`; M1/M2 con la convención de texto.

- 2026-11-26 — M2a: motor de envío (`MailSender`, `MailRenderer`, `promocion:send-mail`, push g-api).

- 2026-11-26 — M1: plantillas, categorías y direcciones de prueba (controladores, rutas, menú, 11 pruebas).

- 2026-11-26 — Diseño `Doc/diseno/promocion.md`: paridad (todas las vistas de 03a y 03b), esquema y mapeo `tb_*`, servicios viejos → Laravel, orden (bloque 0: 3 pts, mensajería: ≈ 33, fidelización: ≈ 35,5) y pedidos. Esperando revisión de S0.
