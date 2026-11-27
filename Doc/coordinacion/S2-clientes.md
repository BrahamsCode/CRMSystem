# S2 — Clientes, ventas y reservas

**Modelo:** Sonnet 5.5, esfuerzo medio · **Carpeta:** `CRMSystem-clientes/` · **Rama:** `l13/clientes`
**Módulos:** 顧客管理 (`clientes`), 販売管理 (`ventas`), 予約管理 (`reservas`). **Es dueña de:** sus carpetas según `Doc/contratos/CONVENCIONES.md` (secciones 2 y 3).

## Modo autónomo

Trabajas sin pedir confirmación. Al terminar cada tarea: actualizar este archivo y seguir con la siguiente. Si falta un detalle menor, decide lo más simple que respete las convenciones, anótalo en "Decisiones" y sigue. Al terminar todo o bloquearte: `SendMessage` a "S0 Coordinador CRM" (una línea: qué hiciste, qué sigue, qué necesitas). Cada entregable terminado con pruebas en verde: `git add` de tus rutas, commit (mensaje en español) y `git push origin <tu rama>`; después avisa a S0, que integra en `laravel13`. No haces merge ni cambias de rama; nunca `--force`.

## Próximo paso — C7 (lector, tarjetas, 入退室, escuela, コメント設定) y R6 (category3 予約 simple, 予約機種一覧). Ventas sigue en S2 (S0, 2026-11-27).

## Tarea anterior — Diseño (sin código, HECHO)

Escribir `Doc/diseno/clientes-ventas-reservas.md`:
1. **Tabla de paridad:** una fila por cada vista del análisis (`Doc/analisis-legacy/01-clientes-y-ventas.md`; en `05-operaciones-terminales-reservas.md` solo la sección de category9 (予約管理); en `07-pos-api-y-externos.md` solo `r/` y `reserve/`): archivo legacy, título (ja), ruta Laravel (`admin.<modulo>.*`), controlador@método, tablas nuevas, estado (`pendiente` / `descartado: motivo`).
2. **Modelos sobre la BD del legacy** (`CONVENCIONES.md`, sección 0): qué `tb_*` usa cada vista, un modelo Eloquent por tabla (`$table`, `$primaryKey = 'cd'`, columnas tal cual, `disp_flg = 9`, `hatuban`) y sus relaciones. Sin tablas nuevas salvo que sea imprescindible (y compatible con el legacy).
2b. **UI:** cada pantalla reproduce el HTML y los CSS del legacy (mismas clases, mismos textos); anota qué JS del legacy necesita.
3. **Servicios viejos → Laravel 13:** cada uno con su reemplazo (Mail + cola, Scheduler, StreamedResponse, Storage, Policies…).
4. **Orden de implementación** por submódulo, con dependencias de otros grupos y estimación en puntos (1 punto ≈ media jornada).
5. **Pedidos** a S1 (núcleo) y a otros grupos.
Criterio de terminado: todas las vistas del análisis tienen fila; avisar a S0 y esperar su revisión. Mientras esperas, puedes preparar factories y seeders de tu grupo (en tus carpetas).

## Después (cuando S0 apruebe el diseño y el núcleo esté integrado)

Implementar submódulo por submódulo en el orden del diseño: migraciones, modelos, Form Requests, controladores, vistas Blade (textos en `lang/ja/<modulo>.php`), rutas en `routes/modulos/<modulo>.php`, menú en `config/menu/<modulo>.php`, pruebas Pest. Cada submódulo terminado: pruebas en verde (`bash scripts/en-docker.sh php artisan test`), filas en `hecho`, commit y push de tu rama, y aviso a S0.

**Lo anterior de CRMSystem (clientes sobre tablas nuevas, UI Tailwind) se borró:** se rehace sobre `tb_customer` y compañía con la UI del legacy. El código viejo está en git (`git show 8d84c98:public_html/app/...`) solo como referencia de lógica.
**Reservas:** `r/` duplica a `reserve/` (divergentes): un solo módulo; documenta las diferencias. `category11/yoyaku` (copia dentro del POS) queda fuera. El recordatorio de S3 depende del modelo de reservas: define y publica pronto el modelo `Reservation` en tu diseño.

**Fuera de alcance por ahora (Antony, 2026-11-26):** POS (`category11`, terminales `CRMPOS-<id>`, servidores POS de tienda) y SNS (`category12`). Donde tu grupo llame al POS, deja una interfaz `PosGateway` sin implementación y anótalo.

## Lee solo

- `CLAUDE.md`, `Doc/contratos/CONVENCIONES.md` (sección 0 primero), `Doc/analisis-legacy/README.md` (hallazgos transversales)
- Del análisis, por secciones (grep del título): `Doc/analisis-legacy/01-clientes-y-ventas.md`; en `05-operaciones-terminales-reservas.md` solo la sección de category9 (予約管理); en `07-pos-api-y-externos.md` solo `r/` y `reserve/`
- Para dudas de comportamiento: el código en `C:\Users\User\VivaTech\crm2-php8\public_html` (solo lectura) y `Doc/legacy-crm2/MANUAL-TESTS.md` (tu módulo)

## Pedidos

Ver `Doc/diseno/clientes-ventas-reservas.md` §5 (S1: `staff`, Gates, rutas públicas `r/`, `shops.card_reader_mode`, `taxes`, paquetes, `PosGateway`; S3: `LoyaltyService` por `visit_id`, `tb_reserve`; S4: `customer_cards`; S5: `shop_work_days`, `MobileShopClient`).

## Decisiones

- 2026-11-26: `<modulo>` = `clientes`, `ventas`, `reservas`; lo existente (`admin.customers.*`, `views/admin/customers`, `lang/ja/customers.php`) se muda a esos nombres en la tarea C0.
- 2026-11-26: `r/` es la versión CRM y `reserve/` la versión POS (lee `tb_c2_*` del servidor POS): se migra el contrato de `r/` y `reserve/*` es alias; la rama POS queda tras `PosGateway` sin implementar.
- 2026-11-26: `営業日設定` (`shop_work_days`) es de S5; `point_setting` es de S3; envío desde 集計 es de S3.
- 2026-11-26: descartados sin enlace en el menú: `customer_level`, `school*`, `mail_comment`, `customer_new_complete`; se reabren si Japón confirma uso.
- 2026-11-26: ventas y gastos salen de `tb_customer_sub` (-1/-2) a `sales`/`sale_lines`/`expenses`.
- 2026-11-26: `category3/reservation` (`tb_reserve`) pasa a S2 (S0); comparte `reservations` con category9 (`channel = 300`). `model_reservation.php` descartado (`tb_change_model` sin escritor en el repo).
- 2026-11-26: el estado de atención (受付中/電話受付/締切間近/受付終了) es de la franja (`reservation_slots.accept_state`), no de la reserva; la reserva tiene `state` 1 受付 / 2 確定 / 9 キャンセル. Tiempos de franja en segundos desde 00:00 como el legacy (pasan de 24 h).
- 2026-11-26: `felica_no` en `customers` (pedido S4); `felica_cards` y カード管理 son de S4. Sin `paper_card_id`: el legacy solo relaciona cliente y tarjeta de papel en `url_transfer_data` (S4).
- 2026-11-26: `id = cd` en `customers`, `visits`, `products`; resto por `legacy_id_map`. Mapeo inverso para API de S5: `name` = apellido + espacio de medio ancho + nombre; `kojin_birth` = `Y/m/d` (vacío `'//'`).

- 2026-11-26 (v2, BD legacy): sin tablas nuevas; modelos sobre `tb_*`. La v1 queda en git (`917be77`) y en el stash `obsoleto: tablas nuevas (S2 R1 reservas)` (SHA `9db26956`).
- 2026-11-26: `login_pass` se mantiene en claro como el legacy (misma BD que producción; `appview`, `ptag`, `mpa_*` comparan en claro o con `md5`). El hash queda para cuando se migren esos contratos.
- 2026-11-26: `category3/reservation` sigue sobre `tb_reserve` (sistema independiente de `tb_yoyaku`, como el legacy); `Reservation` = `tb_yoyaku`, `SimpleReservation` = `tb_reserve`.
- 2026-11-26: pantallas sin enlace en el menú (`customer_level`, `school*`, `mail_comment`, `biyou`, `model_reservation`) se migran con prioridad baja (S0: tienen pantalla porque el legacy la tenía).
- 2026-11-26: si falta `tb_felica_mode` `cd = 2`, se crea al guardar en vez de no guardar en silencio (defecto del legacy).

- 2026-11-26: estáticos de S2 copiados a `public/legacy/` (32 archivos: `system/category1/{customer,inc}/js`, `karute_menu/css`, `category9/yoyaku/{css,js,img}`, `r/{css,js,img}`), idénticos al legacy; sin Flash (`csv_download*`, `AC_RunActiveContent.js`) ni `uploads/`. `category2` y `category3/reservation` no tienen estáticos propios. No existen ni en el legacy (allí daban 404): `biyou/js/shopping_entry.js`, `config/js/topics_entry.js`, `karute_menu/js/user.js`, `reservation/js/staff_entry.js`, `yoyaku/css/popInline.css`, `yoyaku/js/popInline.js`. Los de `system/js`, `system/css`, `system/plugin`, `/common/` (S1) y `category3/{css,js,smatopi}` (S3) no los copia S2.
- 2026-11-26: los JS del legacy llaman a URL absolutas (`/system/category1/inc/ajax_search_kaiin.php`, `ajax_raiten_list.php`, `ajax_picupload.php`) y relativas (`./ajax_*.php` en `r/`, `time_table_staff.php`). S0 (CONVENCIONES §2): esas URL van en `routes/legacy/clientes.php` (sin prefijo, `web` + guard del personal, nombre `legacy.clientes.*`; CSRF por `X-CSRF-TOKEN` desde el layout de S1), las pantallas en `routes/modulos/clientes.php`; `r/` en `routes/publico/reservas.php`. Los JS no se tocan.

- 2026-11-26 (D1): `Visit` usa su propio scope (`COALESCE(disp_flg,'') <> '9'`): el legacy inserta las visitas con `disp_flg` NULL y el scope común de S1 las ocultaría. Intervalo de visitas desde `tb_touch_mode` (`mode = '1'`), como `cRaiten`. Sellos y puntos de la visita tras la interfaz `VisitLoyalty` (nula; S3 la enlaza).
- 2026-11-26 (D1): el contrato `remindable()` es por día y con no socios, como el legacy (no por rango ni solo socios como decía la v2).
- 2026-11-26 (vistas): pedidos a S1 surgidos al pasar las vistas: Gate `kengen` con `[N, M]` (el legacy usa `myKengen[N][M]`) y paso libre a master; clases extra en `.wrap` (`customerDetail`, `yoyakuWrap`) y clase de `<body>`; estáticos `system/css/popInline.css`, `system/js/{popInline,toast,jquery.colorbox,jquery.maskedinput-1.2.2}.js`, `system/img/*`; componente del árbol de áreas (`Area::createAreaCheckUi`). Rutas legacy para los JS: `/system/category1/inc/ajax_*.php`, `/system/category1/customer/zip.php`, `/system/category9/yoyaku/ajax_*.php`.

- 2026-11-26 (R1): `ajax_yoyaku_config.php`, `ajax_yoyaku_koumoku_new.php`, `ajax_yoyaku_koumoku_category.php`, `ajax_menu.php` y `ajax_free_yoyaku_rank.php` solo los usan sus pantallas: pasan a rutas `admin.reservas.*` y el JS inline llama a esas rutas. Los guardados redirigen (PRG) con el mismo mensaje. No-master: el servidor fuerza la tienda de la sesión. Categoría vacía de menú = NULL (el legacy guardaba 0 y los ocultaba).

- 2026-11-27 (C1): `tb_customer.cd` del alta se numera como `createHatsuban()` del legacy (epoch + tienda + 6 cifras al azar), no con el contador `hatuban`, igual que `customer_new_register.php`; `id` (会員番号) = `createID()` de `system/inc/function.php`. Los modelos con `HasHatuban` respetan un `cd` ya puesto.
- 2026-11-27 (C1): la búsqueda guardada (`describe()`) va en la sesión `customer_search.<uniqid>` y se copia en `promocion.audience.<uniqid>` para el 「この条件で配信する」 de S3 (en el legacy compartían `wherestr_<uniqid>`). Pedido a S0: acordar una sola clave.
- 2026-11-27 (C1): permisos comprobados en el servidor (alta 1001/2, edición 1001/3, borrar/recuperar 1001/4, CSV 1002/1); el legacy solo ocultaba botones. Recuperar comprueba el correo duplicado como `customer_recover_confirm.php`. ポイント管理/履歴 del menú con el Gate `clientes.ext-shop` (= `isExtShopDomain`).

- 2026-11-27 (C2/R3): búsqueda guardada solo en `customer_search.<uniqid>` (quitada la copia a `promocion.audience.*`, acuerdo con S0/S3). Grupo y 来店動機 numeran con `createHatsuban` como el legacy; 顧客レベル con `hatuban`. El reparto de rangos suma `tb_point_log` + `tb_stamp_log` como el script legacy; S3 tenía previsto `promocion:customer-rank` para lo mismo: decidir uno (pedido a S0). El alta de reserva comprueba el aforo de la franja (`people`) con `lockForUpdate`; mover/cambiar desde el 予約表 no, como el legacy. Correo de reserva sin `md5(login_pass)`: enlace firmado a `publico.mypage.login` solo si S4 publica esa ruta.

- 2026-11-27 (C5/C6/R5/ventas): 売上データ作成 lista el personal de la tienda elegida (el legacy fijaba `shop_cd = 1`). El 会員検索 del formulario de venta sigue con `SaleCustomerSearch` (port de `karute_menu/ajax_search_kaiin.php`, búsqueda en línea por palabras), no con el popup `admin.clientes.lookup` (otra pantalla del legacy, `karute_customer_search.php`): misma UI que el legacy. model1 → メール: el servidor arma el filtro (`MailAudience::storeRaw` de S3), no SQL del navegador. stamp1 sin `sashihiki_kaikei` (columna inexistente).

## Hecho

- 2026-11-27 C5: CSVインポート (`CustomerCsvImporter`, SJIS-win, transacción por fila), plantilla CSV, `csv_export.php` (volcado de maestras, `nucleo.maintenance`), tarea `clientes:limpiar-temporales`. La portada `category1/` es del núcleo (NUCLEO.md §3): quitada la de C5. C6: 集計ツール (model1/2, point1/2, stamp1), 顧客情報集計, csv出力管理画面 (todo con `clientes.view`). R5: 店舗毎予約数, 予約一覧 (`tb_reserve`), 年間カレンダー, 予約一覧出力, CSV 予約, 予約プリント. QA S1: Gate `clientes.ext-shop.enabled`; ■顧客一覧 solo tras buscar; quitadas las entradas `category9/`. Suite 761 en verde.

- 2026-11-27 C4: 追加情報 múltiple (`CustomRecordService`), selector de cliente (`karute_customer_search`), AJAX `ajax_category`/`ajax_staff`, カルテ alta/edición + カルテ詳細 + 施術カテゴリー (`biyou/`), マイアルバム con Storage en `hp/uploads/c1/photoalbum/<cd>/` (GD, mismos tamaños). Suite 717 en verde. Pendiente menor: la pestaña custom no envía `wintype=no_headers`; 「カルテを削除する」 sigue en `#` (el legacy apunta a un archivo que no existe).

- 2026-11-27 C3: 来店処理 (`VisitService`), alta/corrección/borrado de 来店履歴 (`VisitRecordService`), puntos y sellos manuales (`ManualLoyaltyService`), ポイント履歴, pestañas 8/9/10. R4: 予約表 (`TimetableService`), 予約表 (スタッフ別) y AJAX legacy. V1-V3 (商品情報管理: カルテメニュー, 商品, 割引) completas con 17 pruebas, entregadas a S4 en esta misma rama. Pestaña 11 con `@includeIf('admin.ventas.partials.customer-tab')` para S4. Suite 695 en verde con `memory_limit=512M` (con 128M se agota).

- 2026-11-27 C2: 表示・検索・CSV項目設定, 顧客追加情報設定 (categorías y campos), 顧客グループ, 来店動機, 顧客レベル, 顧客ランク設定 y 振り分け + comando `clientes:asignar-rangos` programado desde `admin_master`. R3: 予約 (alta en 3 pasos + correo), 時間選択, 顧客情報入力, `ajax_yoyaku_register`/`ajax_move_objs` (servicio `ReservationService`), AJAX de apoyo, 顧客関連づけ. 有料会員 (`ajax_changePaidMemberFlg`) con `MobileShopClient` de S5. Suite 546/547 (el fallo es una prueba de S3, `F1CouponsTest`, que pasa sola).

- 2026-11-27 C1: 顧客一覧・検索 + CSV (standard, DM用), ficha con las 14 pestañas + personalizadas, borrar/recuperar/liberar tarjeta/borrar マイクーポン, alta/edición/confirmación/書き直す, 会員検索 de 紹介者; menú lateral `config/menu/clientes.php`; URL legacy en `routes/legacy/clientes.php`. 50 pruebas de pantalla; suite 442/442.

- 2026-11-26 R1: 予約設定 (tienda, formulario, funciones, 注釈), 予約項目, 予約項目カテゴリ, 設備, スタッフ施術管理, フリー予約: controladores, Form Requests, servicios, vistas del legacy, rutas en `routes/modulos/reservas.php`. 33 pruebas de pantalla con layout y usuario de prueba (`tests/Feature/Reservations/ScreenSetup.php`, se quita cuando S1 publique layout, login y Gate). Suite 212/212. Imágenes de menú en el disco `public` (`hp/uploads/c9/yoyaku_koumoku/`).

- 2026-11-26 D1: modelos sobre `tb_*` en `app/Models/{Customers,Sales,Reservations}` (Customer, CustomerSub, CustomCategory, CustomField, Visit, VisitMotive, CustomerLevel, Karte, ServiceCategory, CustomerPhoto, ReportLink, School*Config, Job, Prefecture, Zip, ChangeModel; SalesItem, ServiceMenu, Discount; Reservation, ReservationSlot, ReservationMenu, ReservationMenuCategory, StaffWork, Facility, FreeReservationRank, ReservationConfig, SimpleReservation, SimpleReservationCategory). Servicios: `VisitService::register`, `CustomerSearch` (port de `search_set2.php`: `apply`, `query`, `byPhone`, `describe`, `forStaff`, `forCategory`), `CustomerFelicaService`, interfaces `VisitLoyalty` y `PosGateway` con implementación nula en `ClientesServiceProvider`. Vistas Blade (HTML del legacy): 顧客一覧・検索 + parciales de búsqueda, ficha + 14 pestañas + personalizada, alta/edición/confirmación, 予約表. 38 pruebas propias; suite 60/60.

- 2026-11-26: diseño v2 sobre la BD y la UI del legacy (paridad con tablas `tb_*`, modelos por tabla y dueño, UI/JS por pantalla, contrato `Reservation` sobre `tb_yoyaku`, orden 84 pts).

- 2026-11-26: diseño `Doc/diseno/clientes-ventas-reservas.md` (paridad de todas las vistas de 01, category9 de 05, `r/`/`reserve/` de 07 y category3/reservation; esquema con mapeo `tb_*` en el Anexo A; servicios; orden 76 puntos; pedidos). Pendiente de revisión de S0.
