# S5 — Operaciones e integraciones externas

**Modelo:** Sonnet 5.5, esfuerzo alto · **Carpeta:** `CRMSystem-operaciones/` · **Rama:** `l13/operaciones`
**Módulos:** 業務支援管理 (`operaciones`: staff, 勤怠, turnos, horarios) e integraciones externas (`integraciones`). **Es dueña de:** sus carpetas según `Doc/contratos/CONVENCIONES.md` (secciones 2 y 3).

## Modo autónomo

Trabajas sin pedir confirmación. Al terminar cada tarea: actualizar este archivo y seguir con la siguiente. Si falta un detalle menor, decide lo más simple que respete las convenciones, anótalo en "Decisiones" y sigue. Al terminar todo o bloquearte: `SendMessage` a "S0 Coordinador CRM" (una línea: qué hiciste, qué sigue, qué necesitas). Cada entregable terminado con pruebas en verde: `git add` de tus rutas, commit (mensaje en español) y `git push origin <tu rama>`; después avisa a S0, que integra en `laravel13`. No haces merge ni cambias de rama; nunca `--force`.

## Próximo paso — Diseño (sin código)

> **Ahora (2026-11-27, S0):** pasos 1-4, 6 (勤怠管理 `kintai`) y 8-12 hechos; QA de category5 e informe `Doc/qa/para-japon.md` (§3 y §5 al día con las respuestas de Antony). `working` (勤怠一覧/集計) lo hace S2 con mis modelos: atender lo que pida. Pendiente ⏸: shukei y air.

> **2026-11-26, cambio de base (Antony):** misma BD y misma UI que el legacy (`CONVENCIONES.md`, sección 0). Si ya escribiste tu diseño con tablas nuevas, rehaz la parte de esquema y UI. — **rehecho sobre la BD legacy el 2026-11-27, esperando revisión de S0**

Escribir `Doc/diseno/operaciones-integraciones.md`:
1. **Tabla de paridad:** una fila por cada vista del análisis (en `05-operaciones-terminales-reservas.md` solo la sección de category5 (業務支援管理); `07-pos-api-y-externos.md` (todo salvo `category11`, `category12`, `r/` y `reserve/`)): archivo legacy, título (ja), ruta Laravel (`admin.<modulo>.*`), controlador@método, tablas nuevas, estado (`pendiente` / `descartado: motivo`).
2. **Modelos sobre la BD del legacy** (`CONVENCIONES.md`, sección 0): qué `tb_*` usa cada vista, un modelo Eloquent por tabla (`$table`, `$primaryKey = 'cd'`, columnas tal cual, `disp_flg = 9`, `hatuban`) y sus relaciones. Sin tablas nuevas salvo que sea imprescindible (y compatible con el legacy).
2b. **UI:** cada pantalla reproduce el HTML y los CSS del legacy (mismas clases, mismos textos); anota qué JS del legacy necesita.
3. **Servicios viejos → Laravel 13:** cada uno con su reemplazo (Mail + cola, Scheduler, StreamedResponse, Storage, Policies…).
4. **Orden de implementación** por submódulo, con dependencias de otros grupos y estimación en puntos (1 punto ≈ media jornada).
5. **Pedidos** a S1 (núcleo) y a otros grupos.
Criterio de terminado: todas las vistas del análisis tienen fila; avisar a S0 y esperar su revisión. Mientras esperas, puedes preparar factories y seeders de tu grupo (en tus carpetas).

## Después (cuando S0 apruebe el diseño y el núcleo esté integrado)

Implementar submódulo por submódulo en el orden del diseño: migraciones, modelos, Form Requests, controladores, vistas Blade (textos en `lang/ja/<modulo>.php`), rutas en `routes/modulos/<modulo>.php`, menú en `config/menu/<modulo>.php`, pruebas Pest. Cada submódulo terminado: pruebas en verde (`bash scripts/en-docker.sh php artisan test`), filas en `hecho`, commit y push de tu rama, y aviso a S0.

**Integraciones = contratos de alto riesgo:** API JWT de la tienda móvil (13 endpoints, con sus erratas `discription`/`cutomer_cd` y errores con HTTP 200), `mpa_*`, `appview/`, `tool/api`, CTI, `version/`. **Mismas URL, mismos parámetros, mismas respuestas byte a byte.** Una prueba Pest de contrato por endpoint, comparando con la respuesta del legacy. Seguridad mejorada solo donde no rompa al cliente; lo demás, anótalo para Japón.
**No reproducir:** los calendarios que saltaban a 1970 (`mktime`, `Doc/legacy-crm2/PENDING.md` 1b) ni la inyección SQL de `change_syukkin2.php`. El staff (login, permisos) es del núcleo (S1): tú haces las pantallas de gestión (la sincronización de staff con el POS queda fuera: interfaz `PosGateway` sin implementar).

**Fuera de alcance por ahora (Antony, 2026-11-26):** POS (`category11`, terminales `CRMPOS-<id>`, servidores POS de tienda) y SNS (`category12`). Donde tu grupo llame al POS, deja una interfaz `PosGateway` sin implementación y anótalo.

## Lee solo

- `CLAUDE.md`, `Doc/contratos/CONVENCIONES.md` (sección 0 primero), `Doc/analisis-legacy/README.md` (hallazgos transversales)
- Del análisis, por secciones (grep del título): en `05-operaciones-terminales-reservas.md` solo la sección de category5 (業務支援管理); `07-pos-api-y-externos.md` (todo salvo `category11`, `category12`, `r/` y `reserve/`)
- Para dudas de comportamiento: el código en `C:\Users\User\VivaTech\crm2-php8\public_html` (solo lectura) y `Doc/legacy-crm2/MANUAL-TESTS.md` (tu módulo)

## Pedidos

Detalle en `Doc/diseno/operaciones-integraciones.md` §5 (rehecho sobre la BD legacy). Resumen:
- **S1:** modelos `Staff`/`Shop`/`Hatuban`/`AdminMaster`/`ContentsMaster`, trait de `disp_flg`, mutator de `form2Db`; rutas `operaciones` y `publico/integraciones`; registrar `OperacionesServiceProvider` e `IntegracionesServiceProvider`; estáticos de category5 y `appview` (+ `admin/css/font-awesome`); disco sobre `hp/uploads`; `firebase/php-jwt`, Intervention/Imagick, config de la tienda móvil y `CRM_API_KEY`, `DOMAIN_LIST`; niveles de permiso; `zip` y helper CSV.
- **S2:** modelos de `tb_customer(_sub)`, `tb_raiten_rireki`, `tb_koumoku`, `tb_search_*`, `sales_items`, `tb_photo_album`, `tb_yoyaku`, `numbered_ticket`; servicio de visitas (`raitenFunc`), búsqueda por teléfono, alta de socio, reserva rápida; rutas destino de `ctiWindow`.
- **S3:** servicio de puntos con los campos de `setPoint`; modelos de cupones, sellos y encuestas para `appview`.
- **S4:** modelos de `tb_url_list` y `tb_beacon_history`; imágenes y `mypage_config` de mypage en solo lectura.
- **Antony/S0:** ⏸ ¿siguen en uso `exp_cmd/cron/get_shukei_data*.php` (escriben `tb_crm_check_operation` en la BD de VIZAN 203.183.91.218) y el cliente Adobe AIR (`air/`)?
- **Antony (2026-11-27):** 勤怠 se migra (la gestión, sin quiosco): `kintai` S5 (hecho), `working` S2 sobre `WorkTime`/`ShopWork`/`KintaiHiduke`/`KintaiRecord`.

## Decisiones

- **BD y UI del legacy (2026-11-27):** sin tablas nuevas; modelos sobre `tb_*` con `cd`, `hatuban` y `disp_flg='9'`; HTML y CSS de cada pantalla copiados. El diseño con tablas nuevas quedó en git (84f6590) y su código en el stash `b050e409` «obsoleto: tablas nuevas (S5 operaciones)».
- **Columnas por día** (`tb_work_time`, `tb_staff_shift`, `tb_shop_work`): lista blanca de días 1..31, nunca el nombre de columna armado con la entrada (evita la inyección de `change_syukkin2`).
- **Corte de jornada:** el mismo de cada pantalla del legacy («hora < 5» en working, `kijun_time` en kintai), en `WorkTimeService`.
- **`url_transfer_data` es de S5** (lo escribe `ptag`); sin PK en el legacy: modelo sin clave primaria, solo inserta.
- **`tb_felica_mode`:** modelo `FelicaMode` de S5; la fila `cd=2` la edita S2.
- **Estáticos de category5 y `appview`:** S5 los copia (`public/legacy/system/category5/`, `public/appview/`), pendiente de confirmar por S0/S1.
- **`/api/<desconocido>`:** 404 JSON en lugar del fatal del legacy.
- **`setRaitenDetail` JSONP de error:** se emite el formato correcto (el legacy está roto por precedencia de operadores).
- **`change_syukkin2.php`, `schedule.php`, `shift.php`, archivos temporales CSV:** descartados (duplicados, sin enlace o reemplazados). `ajax_session_update` vuelve a `pendiente ⏸` (con la UI del legacy la tienda va en la sesión).
- **`version/<canal>/v.php` y APK:** descartados (touchcation, S0); `crm_version.php` se mantiene.
- **Rutas de integración** en `routes/publico/integraciones.php`, nombre `publico.integraciones.*` (convención S0).
- **`tool/api`:** su JSON se arma como texto en el legacy; se reproduce con plantillas, no con `LegacyJson`.
- **Sin cabecera `Authorization`** la API del legacy da 500 sin cuerpo: Laravel responde igual (fixture).
- **`mpa_setraiten` con socio válido** da fatal 500 en el legacy tras registrar la visita: Laravel responde el JSON `ok` (corrección anotada para Japón).
- **`numbered_ticket` / クイック予約:** descartado (touchcation, S0).
- **Modelos:** `app/Models/Operaciones|Integraciones/`; controladores públicos en `Publico\Integraciones\`; URL literales de los JS en `routes/legacy/operaciones.php` (convenciones S0).
- **スタッフ一覧 (2026-11-27):** el WHERE no se guarda como SQL sino como condiciones validadas (`StaffSearch`) en la sesión; solo columnas reales de `tb_staff`. Los campos de texto conservan el valor buscado (el legacy los vaciaba por una variable sin asignar). Lista, paginación y 詳細 por GET; el detalle sin `cd` existente da 404.
- **CSV de personal:** mismos bytes que el legacy (SJIS, `"	<valor>"`, CRLF, `user.csv`); `Content-Type: text/x-csv; charset=Shift_JIS` (Symfony añade charset a `text/*`).
- **削除する / カード解除:** redirigen a la lista sin la pantalla intermedia «解除中です。» de 2 s. La baja no borra `tb_work_time` (solo vacía `felica_no`; el diseño decía lo contrario y se corrigió). カード解除 pide el permiso 1024/3 (el legacy no pedía ninguno); 削除/復元 el 1024/4; nunca se borra el cd 1.
- **復元する:** se vuelve a comprobar que el ID y la メールアドレス1 no los use otro activo (el legacy solo avisaba en la confirmación). En la confirmación se muestran 都道府県, 雇用形態, 役職 y 携帯電話番号 (variables sin asignar en el legacy).

- **スタッフ新規登録/修正 (2026-11-27):** los datos viajan en crudo entre formulario y confirmación y se aplica get_form2 al guardar; lo leído de `tb_staff` se des-escapa para el formulario. Se guardan los teléfonos ya sin guiones (el legacy validaba la versión limpia pero guardaba la original). 所属部署: el texto libre gana al desplegable siempre y se agrega a `tb_belongpost` solo si no existe (el legacy duplicaba en el alta y vaciaba el campo en la edición). `rank_pass` = '' como el legacy, salvo master/cd 1, que conservan contraseña, grupo y `rank_pass`. La foto se sube con JS propio (rutas de Laravel) en lugar de `staff_entry.js`; se guarda como JPEG reducido a 200 px. Sin las pantallas intermedias «登録中です。».

- **tool/api y CTI (2026-11-27):** salida con `ToolApiOutput` (texto concatenado como el legacy, filas leídas como `col::text`); las consultas que el legacy rompía (cd no numérico, fecha inválida, offset negativo) dan el mismo `NO_DATA`, sin SQL armado con la entrada. Se mantiene sin cambios lo inseguro del contrato y se anota para Japón: `getStaffList` devuelve `tb_staff.*` con `login_pass`; CTI sin autenticación. `ctiWindow` redirige a las URL del legacy (las rutas de S2 que las sirvan son cosa de S2/S1). `htmlData` sale del parcial `custom_field` de S2 (mismo marcado, otros espacios). `setRaitenDetail` queda pendiente del servicio de S2 de `kanren_record_register`.

- **mpa_* (2026-11-27):** imágenes de `hp/uploads/c6` y del álbum en el disco public (`AppImages` reproduce is_file/filemtime/glob); `mpa_setraiten` usa el `VisitService` de S2 (sin terminal ni personal) y con éxito responde `ok` (el legacy daba fatal 500); `mpa_albumUpdate` con Imagick (mismos tamaños 480x>, 240x>, 150x150, 75x75) y la misma URL fija de vuelta a la app. Las rutas quedan como `publico.integraciones.api.mpa.*`.

- **Menú スケジュール管理 (2026-11-27):** シフト区分管理 y los turnos van en el bloque スケジュール管理 (vacío en el legacy; allí los turnos solo salían en 勤怠管理 para un `SERVER_ADDR` concreto). シフト区分管理 es pantalla propia (en el legacy era una sección de 各種設定, ⏸).

- **Turnos (2026-11-27):** el master elige cualquier tienda (el legacy volvía siempre a la primera por un bug) y el horario es el de la tienda elegida; sin `kintai_tb_config` se usan sus valores por defecto (el legacy redirigía a 勤怠一覧, ⏸). 月別 pinta solo los días del mes (el legacy, 31 filas siempre) y solo guarda empleados activos de la tienda. Los datos del JS se emiten con `@json`.

- **勤怠管理 kintai (2026-11-27):** mismas pantallas, mensajes y JS del legacy. Cambios: 編集/削除, 端末パスワード変更, 各種設定 y CSV mensual exigen master (el legacy solo ocultaba botones y enlaces); la edición y el borrado solo tocan marcas de esa jornada (el legacy actualizaba cualquier `cd`); la ステータス ignora marcas borradas; textos escapados; borrados por POST/DELETE con CSRF; al volver con errores se pintan las horas enviadas; 備考 se guarda una vez (el legacy escapaba dos veces las comillas); el 勤務状況 debe ser uno del selector; en el CSV cada 外出/戻り suma 24 h según su propio día (el legacy usaba la última marca de 出勤/退勤); jquery.alerts cargado siempre (en UI clásica 削除 fallaba). Sin el botón Flash de AIR ni `ajax_session_update`. La sección シフト区分 de 各種設定 enlaza a su pantalla. El enlace 勤怠一覧 del menú lo añade S2 con su ruta.

- **appview (2026-11-27):** vistas PHP planas con la plantilla del legacy (CRLF) para comparar byte a byte; sin autenticación por `customer_cd` (contrato, anotado para Japón). `introduction.php` y `detail_view.php` descartados: leen la BD `admin` (`shop`, `news`, `blog`, `good_count`), fuera de alcance. Con `domain`, solo el paquete «crm» (*.crm-s.net, *.crm-tis.jp) usa esta BD; los dominios del POS responden «接続できませんでした». Las pantallas de cupones/sellos usan `MobileTheme` (colores y bandas de `hp/inc_m`; `emoji()` vacío, como en smartphone).

- **member_regist / customerUpdate (2026-11-27):** solo el paquete crm (swa/rwa son BD del POS → «接続できませんでした»). Los valores que vuelven al formulario y el `domain` salen con h() (el legacy los imprimía tal cual: XSS). Cambios de comportamiento anotados para Japón: con sellos de bienvenida (`touroku_bonus` > 0) el legacy daba un fatal (`new Hatuban`; la clase es `cHatuban`) y no daba de alta: aquí sí; el push «ご登録ありがとうございます» se filtraba por `cd = <login_id>` y no llegaba a nadie: aquí va al socio nuevo; `appview/pushMessage.php` no existe y ningún push salía: con `CustomerPushService` de S3 sí salen (hasta que S0 lo integre, `AppviewPush` no envía nada). `mobile_kishu` nunca se guardaba (el legacy perdía `$ua`): igual aquí. `synchronizePRO` → `PosGateway::syncCustomers('unsynchronized')` (nulo).

## Hecho

- 2026-11-26 — Diseño con tablas nuevas, aprobado por S0 (obsoleto desde el cambio de base).
- 2026-11-27 — `LegacyJson` (json_encode sin flags y filas como texto) con 8 pruebas unitarias (557be46, 6425449).
- 2026-11-27 — Diseño rehecho sobre la BD y la UI del legacy (`Doc/diseno/operaciones-integraciones.md`): paridad completa, modelos por `tb_*` con dueño, UI y estáticos por pantalla, servicios, orden (63 puntos, 22 ⏸), pedidos y contratos con los mismos `cd` y columnas. **Esperando revisión de S0.**
- 2026-11-27 — Estáticos de category5 (`public/legacy/system/category5/`) y de `appview` (`public/appview/`, más `public/legacy/admin/{css,fonts}` con el font-awesome que usa `appview`: `public/admin/` daba 403 al tapar el prefijo `/admin` de Laravel).
- 2026-11-27 — Fixtures de contrato: 53 casos de `/api/*` y `mpa_*` grabados del legacy con Apache + FPM sobre la BD legacy + `tests/Fixtures/integraciones/legacy-seed.sql` (README con hallazgos); `LegacyJson` reproduce byte a byte todos los cuerpos JSON.
- 2026-11-27 — Modelos sobre `tb_*` en `app/Models/Operaciones/` (`StaffGroup`, `OfficialPosition`, `BelongPost`, `EmploymentSystem`, `Information`, `StaffCard`, `FelicaMode`, `ShopWork`, `KintaiConfig`, `KintaiHiduke`, `KintaiRecord`, `WorkTime`, `StaffShift`, `StaffShiftKind`) e `Integraciones/UrlTransferData`; `LegacyMonth`, `HasDayColumns` (lista blanca de columnas por día) y `PosGateway` nulos con sus providers. 27 pruebas; suite completa en verde (59).
- 2026-11-27 — Vistas Blade (HTML del legacy) de スタッフグループ設定, スタッフ検索項目設定 e インフォメーション管理 (sin controladores: esperan el layout y el login de S1).
- 2026-11-27 — API JWT de la tienda móvil (`routes/publico/integraciones.php`, `Publico\Integraciones\MobileShopController`, servicios `MobileShop\*`): auth, shop, customer, point, mobile_shop/{customer, passcode, point, sales_items, fetch_paid_member, send_unpaid_member_status} y 404. Prueba de contrato contra los 53 casos grabados: todos los implementados coinciden byte a byte (verificado instalando `firebase/php-jwt` en local; la prueba queda saltada hasta que S1 lo integre). Pendientes: `point_use` y `point_exchange_item` (servicio de puntos de S3).
- 2026-11-27 — Controladores y rutas de スタッフグループ設定, スタッフ検索項目設定 e インフォメーション管理 con su menú (`StaffScreensTest`).
- 2026-11-27 — `point_use` y `point_exchange_item` con el `PointService` de S3 (af8dd48): la API JWT completa coincide con el legacy; `MobileShopContractTest` autosuficiente (eb92d9a). Integrado por S0 (0396404).
- 2026-11-27 — スタッフ検索・一覧 (búsqueda de `search_set1/2`, paginación, CSV para master), スタッフ登録情報 (4 pestañas), 削除する, 削除を取り消す/復元する y カード関連付け解除 (`StaffController`, `StaffSearch`, `StaffListTest` 10 pruebas). Suite completa: 395 en verde.
- 2026-11-27 — スタッフ新規登録 / 修正 completos: formulario, confirmación con `LegacyCheck`, 書き直す, registro (createHatsuban/createID, `hatuban`, 所属部署 nuevo, `PosGateway`), 完了 y foto por AJAX (`StaffEntryController`, `StaffPhotoStore`, `StaffEntryTest` 8 pruebas). スタッフ管理 terminado salvo lo de 勤怠 (⏸). Suite: 403 en verde.
- 2026-11-27 — Pedidos de S0: `MobileShopClient` como interfaz + `HttpMobileShopClient` (para S2) y `UrlTransferData::customer()` (para S4) (362b8f3).
- 2026-11-27 — `tool/api` (7 de 8 endpoints + `CheckCrmApiKey`) y CTI (3) con fixtures del legacy grabadas sobre datos sintéticos (`tool-seed.sql`, 47 casos) y `ToolApiContractTest`: todo coincide byte a byte (salvo espacios del `htmlData`). Pendiente: `setRaitenDetail` (servicio de S2). Suite: 442 en verde.
- 2026-11-27 — `mpa_*` (5 endpoints: corresponding, getTopBanner, getCoverFlow, setraiten, albumUpdate): los 12 casos grabados coinciden (setraiten_ok corregido) y `MpaTest` cubre imágenes por tienda, beacon y álbum. Suite: 503 en verde (con laravel13 fusionado por S0).
- 2026-11-27 — 営業日設定 (`ShopWorkController`, `LegacyHolidays` con las reglas de `check_holiday` tal cual) y bloque 初期設定管理 del menú (`ShopWorkTest`).
- 2026-11-27 — シフト区分管理 (`StaffShiftKindController`, `LegacyRanking` compartido con スタッフグループ) (`ShiftKindTest`).
- 2026-11-27 — 日別シフト管理 y 月別シフト管理 (`StaffShiftController`, `ShiftBoard`, vistas con el JS/CSS del legacy) (`StaffShiftTest`). Pasos 1-4 y 8-10 hechos; suite 573 en verde.
- 2026-11-27 — `setRaitenDetail` sobre `VisitRecordService::save` de S2 (mismas claves; errores con el formato del legacy; `add_record_verify` en sesión) (`SetRaitenDetailTest`; contrato contra el legacy `todo` hasta C3).
- 2026-11-27 — Utilitarios: `crm_version.php` (vacío, como en crm2-php8 y development), `/ptag/<ID>`, `air/air_info_out.php` y los XML de `air/` (`UtilityTest`) (51df245). `integraciones:shukei` ⏸ (BD externa de VIZAN, llamador desconocido).
- 2026-11-27 — `appview` lote A (479f281): memberId, qr_code, member_init/comp/login/kiyaku/comfirm, privacyPage (20 casos grabados del legacy, `AppviewContractTest`) y photo_gallery/visit_history con `Http::fake` (`AppviewMemberTest`). `introduction`/`detail_view` descartados (BD `admin`). Suite: 595 en verde.
- 2026-11-27 — `appview` lote B: coupon, coupon_detail, coupon_control, coupon_use, used_coupon_list y stamp (45 casos en `AppviewContractTest`; uso e impresión con `CouponService` de S3 en `AppviewCouponTest`). `MobileTheme` porta lo de `hp/inc_m` que usan.
- 2026-11-27 — `appview` lote C1: member_complete (alta con las columnas que existen; el legacy no guardaba), anketo2 (solo la redirección sin sesión de hp/user); reservation y anketo2_register descartados (`AppviewContractTest` 50 casos, `AppviewRegistrationTest`). `setRaitenDetail`: contrato con el legacy grabado (8 casos) y orden de errores corregido (validación antes de `add_record_verify`).
- 2026-11-27 — Pruebas intermitentes arregladas (empates de ORDER BY en las fixtures; 9633cfe). `appview` lote C2: member_regist y customerUpdate (`AppCustomer`; 60 casos en `AppviewContractTest`, efectos en `AppviewRegistrationTest`). **`appview` terminado** (24 pantallas: 20 hechas, 4 descartadas).
- 2026-11-27 — Pedido de S0 (sesión `mobile` de la reserva web): en el legacy la rellenan `r/index.php` y `reserve/index.php` con el `token = md5(cd:touroku_date)` con el que la app (マイコレ) los abre (ya en R7 de S2), o el login de `hp/user` (fuera de alcance). El login de appview no deja sesión: devuelve el cd a la app por postMessage. Prueba de punta a punta `AppviewReservationTest` (login de appview → token → `r/` y `reserve/`).
- 2026-11-27 — QA de category5 contra la muestra (`Doc/qa/diferencias-operaciones.md`): sin diferencias propias salvo dos de 日別シフト管理, corregidas (calendarios: el mes actual y los dos siguientes; la fecha como enlace) (b26e761).
- 2026-11-27 — Informe consolidado para Antony y Japón: `Doc/qa/para-japon.md` (94372dc; integrado en 49889b5). Libre a la espera de lo que encuentre S1 en la regresión.
- 2026-11-27 — Respuestas de Antony en `Doc/qa/para-japon.md` (§3 firmes/provisionales/pendientes de Japón y §5 deuda del enlace de `mpa_getTopBanner`; 28ff9b7). 勤怠管理 (`category5/kintai`) completo: lista del día, alta, edición, borrados, 端末パスワード変更, 各種設定 (horas e imagen de fondo) y CSV diario/mensual (`KintaiService`, `KintaiEntryValidator`, `KintaiCsv`, `KintaiBackground`; `KintaiTest` 11 pruebas).
- 2026-11-27 — Diseño cerrado con S0: portada y paginador en hecho (S1/S2), filas «otro» en descartado o hecho (c72a70e); カードリーダー/カード descartadas (touchcation, reversible; en `para-japon.md` §4). Solo queda ⏸ `shukei`.
- 2026-11-27 — Datos de demostración (`database/seeders/Demo/OperacionesDemoSeeder.php`, CONVENCIONES §4): 営業日設定 del mes actual y el siguiente (水曜 y 祝日 = 休日), turnos de esos dos meses (早番/遅番/通し con la シフト区分 existente; crea ホール/キッチン/レジ si no hay), 勤怠 de los últimos 30 días en `tb_work_time` y en el terminal kintai (entrada, 外出/戻り, salida, 遅刻/早出/残業/早退, algún 欠勤/有給), `kintai_tb_config` de las tiendas sin él (08-22), 3 インフォメーション y los menús de category5 activos (初期設定 y スケジュール管理 vienen apagados en el backup). Reproducible con crc32 por empleado y día; idempotente por día (solo rellena días vacíos). El master solo entra si no hay otro staff. CTI no siembra nada propio (lee clientes y visitas de S2); los terminales de señalización y カード son de S4. `DemoSeederTest`.
- 2026-11-27 — Tercera pasada, fase A: `OperacionesDemoSeeder` dividido en `Demo/Operaciones/{Personal,Asistencia,Integraciones}DemoSeeder`.
  - **Personal:** 所属, campos vacíos de la ficha del staff demo de S1 (uno con 備考 de 255 caracteres), 5 tarjetas FeliCa (3 asignadas), más campos en スタッフ検索項目設定 y 12 インフォメーション (por tienda, por 役職, larga y en kana de ancho medio).
  - **Asistencia:**
    - 92 días de 勤怠 en la jornada estándar de la tienda, con 残業 de hasta 4 h, sin salida o sin 戻り, 欠勤/有給 y 休日出勤;
    - 残業 pasada la medianoche el último día de mes y el 31/12 → 1/1;
    - la jornada de hoy en curso;
    - turnos 締め que cruzan la medianoche, con huecos y solapes.
  - **Integraciones:** passcode de la tienda móvil por tienda (uno con cero inicial).
  - **Staff:** no crea ninguno (es de S1).
- 2026-11-27 — Tercera pasada, fase B (primera vuelta en :8081). 2 fallos corregidos con prueba: ログインID vacía en スタッフ登録 (`管理者用ID@else` no compilaba) y error de JS en 勤怠一覧 day2 (comillas sin escapar). Detalle en `Doc/qa/regresion.md`. Pendiente: claves de API en el `.env` de :8081 (S0) y segunda vuelta tras la recarga.
- 2026-11-27 — Tercera pasada, segunda vuelta (:8081 en 738907f). Corregido: turnos con «undefined» al guardar シフト fuera del horario del tablero (21b8635). Además, g-api de appview pasa por el interruptor común `App\Support\GApi` (3528d74) y la API JWT y tool/api funcionan con las claves locales. Resumen en `Doc/qa/regresion.md`.
