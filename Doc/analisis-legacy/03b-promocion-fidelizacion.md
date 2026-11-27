# 03b — Promoción: fidelización (販売促進管理, parte fidelización)
> Fuente: C:\Users\User\VivaTech\crm2-php8 (rama master = código ya migrado a PHP 8.5.12). Generado el 2026-11-26. Destino: Laravel 13 + PHP 8.5.

Alcance: `public_html/system/category3/` → anketo, anketo2, coupon, mycoupon, point, stamp, smatopi, visit, reservation, más la clase `system/class/Point.php` (y `src/Point.php`, ver nota en Point). La mensajería (mail_mag, decomail, auto, follow, push, reminder, inc) la cubre otro análisis; aquí solo se anota dónde estas pantallas la reutilizan (`category3/inc/mail_frm.php`, `mail_send_frm.php`, `mail_customer_select_frm.php`).

Convenciones del legacy que se repiten en todo el grupo:
- Acceso a BD con `pg_query` y SQL concatenado (`$tableName` + `$_REQUEST`), sin ORM. Fechas como **UNIX timestamp** enteros (`touroku_date`, `update_date`, `time_limit`, `start_time`/`end_time`).
- Numeración `hatuban`: `SELECT ... FROM hatuban WHERE koumoku = '<tabla>' FOR UPDATE` → `kazu + 1` → `INSERT INTO <tabla> (cd)` → `UPDATE ... SET`. En Laravel: `id` autoincremental (no replicar `hatuban`).
- Banderas: `disp_flg = '1'` activo / `'9'` borrado lógico; `hyouji_flg = '1'` mostrar (`'9'` ocultar en point_rules); `touroku_kubun = '2'` = cliente registrado (no prospecto); `shop_cd` por tienda.
- Patrón de pantallas CRUD: `*_list.php` → `*_new.php` / `*_update.php` (cargan `*_frm.php`) → `*_errchk.php` (validación) → `*_register.php` (INSERT/UPDATE) → `*_delete.php`.
- Permisos por `$kengen->myKengen[<id>][<nivel>]` o `$_SESSION['rank'] == 'master'` en el menú (`system/inc/left_navi3.php`).

## Resumen

| Módulo/submódulo | Carpeta | Nº PHP | Nº vistas reales (pantallas) | Complejidad (S/M/L/XL) | Puntos (1 punto ≈ media jornada de un agente) |
|---|---|---|---|---|---|
| アンケート管理 (encuestas de envío) | `category3/anketo/` | 33 | 17 | L | 6 |
| お客様アンケート管理 | `category3/anketo2/` | 40 | 19 | L | 6 |
| クーポン (cupón imagen, antiguo, sin enlace en menú) | `category3/coupon/` | 24 | 11 | M | 2 (opcional) |
| マイクーポン管理 | `category3/mycoupon/` | 30 | 17 | L | 6 |
| ポイント管理 | `category3/point/` | 25 | 9 | L | 9 |
| スタンプ管理 | `category3/stamp/` | 22 | 7 | M | 6 |
| スマトピ連携 | `category3/smatopi/` | 5 | 2 | M | 3 |
| 来店履歴管理 | `category3/visit/` | 16 | 8 | M | 4 |
| 予約管理 | `category3/reservation/` | 19 | 11 | L | 10 |
| **Total** | | **214** | **101** | | **52** |

Nota: "Nº PHP" incluye `inc/`. "Vistas reales" cuenta lista/alta/edición/confirmación/detalle/config/CSV con UI o descarga; no cuenta POST de registro, AJAX, cron ni fragmentos. `anketo` y `anketo2` son casi copias (anketo2 = お客様アンケート con respuestas por cliente): se implementan con un solo módulo de encuestas.

---

## アンケート管理 (`category3/anketo/`)

- Encuestas con jerarquía **アンケート (cate) → 項目 (form)**. El staff crea la encuesta, añade preguntas (テキスト, テキストエリア, チェックボックス, セレクト, ラジオボタン, 年, 年月, 年月日, con "オプション数" y valor mostrado/enviado), marca obligatorio, ordena, la **emite** a clientes seleccionados (con cupón adjunto opcional), prueba el formulario y analiza/exporta respuestas.
- El envío usa los formularios compartidos de mensajería (`category3/inc/mail_frm.php`) con `$mail_type = "anketo"`; `mailmag_master.type_flg='anketo'` vincula el envío con la encuesta (`type_data_cd`).
- Menú: アンケート一覧 (`anketo_cate_list.php`), 過去アンケート配信一覧 (`anketo_cate_list.php?which=sended`), クリックカウンタ (`anketo_analyze.php`).

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `anketo_cate_list.php` | アンケート一覧 / 過去アンケート配信一覧 (`?which=sended`) | lista | Lista de encuestas con nº de respuestas; filtro enviadas; botones 詳細, 項目, 発行, 解析, 削除 | L tb_c3_anketo_cate, tb_c3_anketo_sub, mailmag_master | 396 líneas; paginación |
| `anketo_cate_new.php` | アンケート新規登録 | alta | Formulario nombre, comentario admin, 表示/非表示, fechas (carga `anketo_cate_frm.php`) | L/E tb_c3_anketo_cate | |
| `anketo_cate_update.php` | アンケート修正 | edición | Carga datos y `anketo_cate_frm.php` | L tb_c3_anketo_cate | |
| `anketo_cate_frm.php`, `anketo_cate_errchk.php` | — | fragmento(include) | Formulario y validación | — | |
| `anketo_cate_register.php` | (登録) | registro(POST) | INSERT/UPDATE con `hatuban` | E tb_c3_anketo_cate, hatuban | |
| `anketo_cate_action.php` | (操作) | registro(POST) | Acciones masivas: 表示切替, 並び替え, 削除 de encuesta y sus ítems | E tb_c3_anketo_cate, tb_c3_anketo_form | |
| `anketo_cate_detail.php` | アンケート詳細 | detalle | Vista de solo lectura de la encuesta | L tb_c3_anketo_cate | |
| `anketo_form_list.php` | 項目一覧 | lista | Ítems de una encuesta, subir/bajar (`rank`), editar, borrar, mostrar cupón adjunto | L tb_c3_anketo_form, tb_c3_anketo_cate, tb_c3_anketo_sub, tb_mycoupon_master, tb_mailmag_analyze, mailmag_master | 778 líneas |
| `anketo_form_new.php` / `anketo_form_update.php` | 項目新規 / 修正 | alta/edición | Título, unidad, comentario, tipo, opciones, obligatorio, clase CSS (`style_class`) | L/E tb_c3_anketo_form | `inc/anketo_function.php` traduce clases de tamaño |
| `anketo_form_frm.php`, `anketo_form_errchk.php` | — | fragmento(include) | | | |
| `anketo_form_register.php` | (登録) | registro(POST) | INSERT/UPDATE ítem con `hatuban` | E tb_c3_anketo_form, hatuban | |
| `anketo_form_delete.php` | (削除) | borrado | Borrado lógico del ítem | E tb_c3_anketo_form | |
| `anketo_form_detail.php` | 項目詳細 | detalle | Vista del ítem | L tb_c3_anketo_form, tb_c3_anketo_cate | `$add_scripts` sin definir (corregido en F9) |
| `anketo_form_issue.php` | アンケート発行 (発行条件選択) | lista/config | Filtros de clientes (categorías de búsqueda `tb_search_cate`/`tb_search_form`) para elegir destinatarios | L tb_customer, tb_koumoku, tb_search_cate, tb_search_form | guarda la cláusula `WHERE` en sesión |
| `anketo_mail.php` | 新規メール配信 | alta | Contenido del correo con enlace a la encuesta, cupón adjunto (AJAX `ajax_search_coupon.php`) | L tb_customer, tb_shop, contents_master, mailmag_master | 679 líneas |
| `anketo_mail_confirm.php` | 新規メール配信 確認 | confirmación | Vista previa y envío de prueba | L tb_shop | |
| `anketo_mail_send.php` | (配信) | registro(POST) | Crea `mailmag_master` (programado o inmediato), marca `attached_cd` (cupón) en la encuesta | E mailmag_master, tb_c3_anketo_cate, hatuban; L tb_customer, tb_shop | |
| `anketo_mail_customer_select.php`, `anketo_mail_new.php`, `anketo_mail_new_confirm.php`, `anketo_mail_new_send.php` | — | fragmento(include) | Envoltorios de 21-29 líneas sobre `category3/inc/mail_*_frm.php` | — | Dependen de mensajería (otro agente) |
| `ajax_search_coupon.php` | — | ajax | Busca マイクーポン por tienda | L tb_mycoupon_master | |
| `anketo_test.php` | アンケートテスト | detalle | Muestra el formulario real para probarlo | L tb_c3_anketo_cate, _form, _sub | |
| `anketo_test_register.php` | (テスト登録) | registro(POST) | Guarda una respuesta de prueba; agrupa por `category_inner_group` | E tb_c3_anketo_sub | numeración propia `MAX(category_inner_group)+1` |
| `anketo_answer_analyze.php` | 回答解析 | detalle | Agregados por pregunta (conteos/porcentajes) | L tb_c3_anketo_cate, _form, _sub, mailmag_master | 427 líneas |
| `anketo_answer_shousai.php` | 回答詳細 | detalle/ajax | Respuestas de una pregunta | L tb_c3_anketo_sub | |
| `csv_answer_download.php` | 回答CSV | csv | Descarga CSV de respuestas (SJIS) | L tb_c3_anketo_cate, _form, _sub | |
| `anketo_analyze.php` | クリックカウンタ | detalle | Contador de clics del envío (usa `inc/mailmag_graph2.php`) | L tb_mailmag_analyze | |
| `inc/page.php`, `css/`, `js/jquery.colorbox.js`, `cron_schedule/cronfile` | — | fragmento(include) | Paginación, Colorbox. `cronfile`: una línea de crontab hacia `mail_mag/mail_sche_send.php` (envíos programados) | — | |

**Flujo principal**: lista (`anketo_cate_list`) → alta encuesta (`cate_new` → `errchk` → `register`) → ítems (`form_list` → `form_new` → `register`) → prueba (`anketo_test` → `test_register`) → emisión (`form_issue` filtro destinatarios → `anketo_mail` → `anketo_mail_confirm` → `anketo_mail_send`) → resultados (`answer_analyze`, `answer_shousai`, `csv_answer_download`, `anketo_analyze`).

**Reglas de negocio no obvias**
- Mientras la encuesta es borrador (`hyouji_flg` aún no activado) el título sale `『』`: la consulta pide `disp_flg='1'`; es comportamiento esperado (MANUAL-TESTS #67).
- Cupón de premio: `tb_c3_anketo_cate.attached_cd` = `tb_mycoupon_master.cd`; se entrega al responder (el cupón se emite desde el lado de Mi Página/hp, no verificado aquí).
- Las respuestas se guardan **una fila por ítem** en `tb_c3_anketo_sub`, agrupadas por `category_inner_group` (una "respuesta" completa). Respuestas de prueba se mezclan con las reales (no hay bandera de prueba clara).
- Orden de ítems por columna `rank` (intercambio con el vecino al subir/bajar).
- Un correo de encuesta registra `mailmag_master` con `type_flg='anketo'`.

**Servicios viejos y reemplazo**: correo vía `crm_send_mail`/`cMailsoushin` → `Mailable` + cola; cron `mail_sche_send.php` → `promotions:send-scheduled` (ya existe); CSV SJIS → `StreamedResponse` con `mb_convert_encoding` a SJIS-win; colorbox/jQuery UI → modal Blade/JS actual.

---

## お客様アンケート管理 (`category3/anketo2/`)

- Segunda familia de encuestas: igual estructura que `anketo/` pero con tablas `tb_c3_anketo2_*`, **respuestas ligadas a cliente** (lista y detalle por cliente con su historial de visitas) y la posibilidad de **elegir una encuesta única** para que aparezca en la app/Mi Página (`contents_master` categoría `tb_c3_anketo2`, campo `set_anketo_cd`). Incluye además el envío de **respuesta (返信) al cliente**.
- Menú: お客様アンケート新規登録 (`anketo_cate_new.php`), お客様アンケート一覧 (`anketo_cate_list.php`).

Vistas **idénticas en función** a `anketo/` (mismo nombre, tablas `anketo2`): `anketo_cate_new/update/frm/errchk/register/action/detail`, `anketo_form_list/new/update/frm/errchk/register/delete/detail/issue`, `anketo_mail*`, `anketo_test*`, `anketo_answer_analyze/shousai`, `csv_answer_download`, `anketo_analyze`, `ajax_search_coupon`, `inc/*`. Diferencias y archivos propios:

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `anketo_cate_list.php` | お客様アンケート一覧 | lista | Lista con casilla **選択** (guarda por AJAX cuál es la encuesta activa), 詳細, 解析, 回答 | L tb_c3_anketo2_cate, contents_master | La casilla usa `attr('checked')` |
| `ajax_set_anketo.php` | — | ajax | Guarda `set_anketo_cd` (INSERT/UPDATE) | E contents_master | |
| `anketo_answer_list.php` | お客様アンケート回答一覧 | lista | Respuestas por cliente: filtro por periodo/año (`MIN(touroku_date)`), nombre, nº de cliente, última visita | L tb_c3_anketo2_cate, _form, _sub, tb_customer, tb_raiten_rireki | 708 líneas; paginado |
| `anketo_answer_detail.php` | 回答詳細 (por cliente) | detalle | Todas las respuestas de un cliente a la encuesta | L tb_c3_anketo2_cate, _form, _sub, tb_customer | |
| `anketo_reply_customer_select.php`, `anketo_reply_new.php`, `anketo_reply_new_confirm.php`, `anketo_reply_new_send.php` | 返信メール | alta/confirmación/registro | Enviar un correo de respuesta a quien contestó (reutiliza `mail_frm.php`) | L/E mensajería | Dependen de mensajería |
| `anketo_answer_analyze.php` | 回答解析 | detalle | Igual que anketo; `$tb_c3_anketo2_sub_count` sin definir (corregido) | L tb_c3_anketo2_* | Mezcla nombres `tb_c3_anketo_*` (sin 2) en algunas consultas: **posible bug latente** |

**Reglas**: las tablas se llaman `tb_c3_anketo2_cate/_form/_sub`, pero columnas y varias consultas usan `tb_c3_anketo_cate_cd`/`tb_c3_anketo_form_cd` (sin "2"): en la migración hay que unificar a `survey_id`/`survey_question_id`. Solo una encuesta puede estar "activa" para la app (contents_master). `attached_cd` = cupón de premio.

---

## クーポン (`category3/coupon/`) — sistema antiguo de cupón-imagen

- **Sin enlace en el menú** (`left_navi3.php` no apunta a `coupon/`; solo la referencian `category3/index.php` y `inc/mail_*_frm.php`). Cupón como **imagen generada** (fondo + texto) para enviar al móvil; reemplazado por マイクーポン. Candidato a **no migrar** salvo confirmación del cliente. `pit_coupon1.php` consulta `shop_master`, tabla del esquema antiguo (BROKEN-FILES). `process-sortable.php` (arrastrar para ordenar) está roto: falta `inc/kihon.php`.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `coupon_list.php` | クーポン画像一覧 | lista | Lista por tienda, ordenar por arrastre | L tb_coupon, tb_shop | |
| `coupon_new.php` + `coupon_newup.php` | クーポン新規追加 | alta + registro | Elige fondo, sube imagen, genera imagen (`inc/make_coupon.php`, GD) | L tb_haikei; E tb_coupon, hatuban | |
| `coupon_revise.php` + `coupon_reviseup.php` | クーポン修正 | edición + registro | | L/E tb_coupon, L tb_haikei | |
| `coupon_detail.php` | クーポン画像詳細 | detalle | Vista + impresión (`css/coupon_detail_print.css`) | L tb_coupon | |
| `coupon_deleteup.php` | クーポン画像削除 | borrado | | E tb_coupon | |
| `coupon_send.php` | クーポンを配信する | detalle/correo | Pantalla para enviar el cupón | L tb_coupon | |
| `coupon_bg_list.php` | クーポン背景画像一覧 | lista | | L tb_haikei | |
| `coupon_bg_new.php` + `coupon_bg_newup.php` | 背景画像新規追加 | alta + registro | Sube fondo (`uploads/haikei/`) | E tb_haikei, hatuban | |
| `coupon_bg_revise.php` + `coupon_bg_reviseup.php` | 背景画像修正 | edición + registro | | L/E tb_haikei | |
| `coupon_bg_detail.php` | 背景画像詳細 | detalle | | L tb_haikei | |
| `coupon_bg_deleteup.php` | 背景画像削除 | borrado | | E tb_haikei | |
| `pit_coupon.php`, `pit_coupon1.php`, `pit_coupon2.php` | クーポン画像 | detalle/fragmento | Cupón "pit" (variante de visualización/uso). `pit_coupon2` escribe `pit_coupon` | L/E pit_coupon, shop_master | roto, sin enlaces |
| `inc/*`, `js/*`, `uploads/` | — | fragmento(include) | Menú, subida, generación de imagen | — | |

Reemplazo si se migra: Storage + generación con Intervention Image, no recomendado.

---

## マイクーポン管理 (`category3/mycoupon/`)

- Cupones digitales por tienda: tipos de uso (`coupon_kubun`: por ejemplo ポイント交換用 con `shouhi_point`, ガチャリン/くじ, スタンプ), vigencia (`limit_flg`: 1 semana / 1 mes / 3 meses / N días / hasta fecha), flag `saitouroku_flg` (reemitir al reinscribirse). Se **emite** a clientes seleccionados (filtro + 発行), se envía por correo, se canjea en caja, y hay **estadísticas** de uso, ranking y clics.
- Menú: クーポン新規登録, クーポン一覧, 過去クーポン配信一覧, 使用ランキング, クリックカウンタ, クーポン利用数確認.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `mycoupon_new.php` / `mycoupon_update.php` | クーポン新規登録 / 修正 | alta/edición | Carga `mycoupon_frm.php` | L/E tb_mycoupon_master | |
| `mycoupon_frm.php`, `mycoupon_errchk.php` | — | fragmento(include) | Título, comentario, 使用区分, vigencia, 注意事項, tienda, `shouhi_point`, nombre de premio (くじ) | L tb_shop, tb_base_system_child/grandchild (ガチャリン管理 activo), contents_master | 570 líneas |
| `mycoupon_register.php` | (登録) | registro(POST) | INSERT/UPDATE maestro; si el tipo es くじ guarda premio en `contents_master` | E tb_mycoupon_master, contents_master, hatuban; L tb_c6_kujibiki | |
| `mycoupon_list.php` | クーポン一覧 | lista | Filtros tienda/uso, 表示切替, borrar, 詳細, 発行 | L tb_mycoupon_master, tb_shop | |
| `mycoupon_detail.php` | クーポン詳細 | detalle | Datos, nº emitidos/usados, clientes que lo usaron y hora (`tb_mycoupon_used`) | L tb_mycoupon_master, tb_mycoupon_used, tb_customer, tb_shop, mailmag_master | 721 líneas |
| `mycoupon_delete.php` | (削除) | borrado | Borrado lógico | E tb_mycoupon_master | |
| `mycoupon_issue.php` | クーポン発行 (発行条件選択) | lista/config | Filtros de clientes (misma mecánica que anketo) | L tb_customer, tb_search_cate/_form, tb_koumoku, tb_mycoupon_master | |
| `mycoupon_issue_detail.php` | 発行内容確認 | confirmación | Resumen del cupón y nº de destinatarios | L tb_mycoupon_master, tb_shop | |
| `customer_list.php` | 顧客一覧 | lista (popup) | Clientes de la condición; última visita | L tb_customer, tb_raiten_rireki | |
| `mycoupon_hakkou.php` | クーポン発行 (実行) | registro(POST) | Para cada cliente del `$_SESSION['wherestr']` INSERT en `tb_mycoupon` con `time_limit` calculado | E tb_mycoupon, hatuban; L tb_customer, tb_mycoupon_master | **SQL guardado en sesión**: reemplazar por ids/filtros validados |
| `mycoupon_create.php` / `mycoupon_complete.php` | クーポン発行完了 | confirmación | Pantalla "発行完了" | L/E tb_mycoupon | Cargan `session.php` antes de `db.php` (BROKEN-FILES) |
| `mycoupon_rireki.php` | クーポン履歴 | lista | Historial de uso por cliente/cupón | L tb_mycoupon, tb_mycoupon_master, tb_mycoupon_used, tb_shop | |
| `mycoupon_ranking.php` | 使用ランキング | lista | Ranking de cupones por usos, filtros tienda/uso | L tb_mycoupon_master, tb_mycoupon_used, tb_shop, mailmag_master | |
| `mycoupon_stats.php` | クーポン利用数確認 | lista | Recuento de usos del día/periodo por tienda | L tb_mycoupon_master, tb_mycoupon_used, tb_shop | |
| `mycoupon_stats_detail.php` | クーポン利用詳細 | detalle | Quién lo usó | L tb_customer, tb_mycoupon_used, tb_shop | |
| `mycoupon_analyze.php` | クリックカウンタ | detalle | Contador de clics | L tb_mailmag_analyze | |
| `mycoupon_mail.php`, `mycoupon_mail_confirm.php`, `mycoupon_mail_send.php`, `mycoupon_mail_customer_select.php`, `mycoupon_mail_new*.php` | 新規メール配信 | alta/confirmación/registro | Envío del cupón por correo (reutiliza mensajería) | L/E mailmag_master, tb_customer, tb_shop, contents_master | Dependen de mensajería |
| `mail_past.php`, `mail_past_shousai.php`, `mail_sended_list.php` | 過去クーポン配信一覧 / 詳細 | lista/detalle | Envíos pasados con cupón y estadística de clics | L mailmag_master, tb_mailmag_analyze | |
| `inc/page_control.php`, `js/user.js`, `cron_schedule/cronfile` | — | fragmento(include) | Paginación; crontab de envíos | — | |

**Flujo principal**: alta maestro (`mycoupon_new` → `errchk` → `register`) → lista → emisión (`mycoupon_issue` filtro → `issue_detail` → `mycoupon_hakkou` → `mycoupon_complete`) o envío por correo (`mycoupon_mail*`) → canje en caja (escribe `tb_mycoupon_used`, desde POS/Mi Página, fuera del grupo) → estadísticas.

**Reglas no obvias**
- Vencimiento al emitir: `limit_flg` 1/2/3 = +7/+30/+90 días; 4 = +`time_limit` días; 5 = fecha fija (`time_limit` = timestamp 23:59:59).
- `shouhi_point` solo se guarda si `coupon_kubun` es 2 o 10 (cupón de canje por puntos).
- Opción 9 (ガチャリン) solo visible si el sistema hijo "ガチャリン管理" está activo.
- `saitouroku_flg`: antifraude, reemitir o no al volver a registrarse (2012).

Ya cubierto en CRMSystem: `coupons`, `coupon_customer`, `CouponIssuer`, `CouponController`, enums `CouponUsage/Validity/DiscountType`, `LotteryRank` (くじ). Falta: ranking/estadísticas por periodo (existe `AnalyticsController`; verificar) y vista de historial de uso.

---

## ポイント管理 (`category3/point/`) + `class/Point.php`

- **Configuración del programa de puntos** (8 formularios en `point_config.php`), **reglas de bonificación** por tramos, **resumen de puntos otorgados/gastados**, **carga masiva por CSV**, y los **cron** de caducidad con avisos por correo/push.
- Menú: ポイント仕様管理, ポイントルール一覧, ポイント集計, ポイント一括付与 (`left_navi3.php`).

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `point_config.php` | ポイント仕様管理 | config | 8 formularios: 有効期間の使用選択, 有効期間延長動作, 有効期間(días), 失効処理を行う時間, 失効前通知, **店舗ごとのルール** (使用上限, 現金/カード還元率, 使用ポイントを含む), **顧客グループごと** (igual), ビーコン来店処理, ポイント獲得種別 (利用/表示/獲得数), 紹介 (紹介者/被紹介者, 上限, コメント), 登録時付与 (ポイント, タイミング, コメント), 有料会員 | L/E admin_master, tb_shop, tb_customer_group, tb_customer_point, tb_customer, point_shikkou_alert, contents_master | 1454 líneas: la más grande; usa columnas serializadas en `tb_shop` |
| `point_rules_list.php` | ポイントルール一覧 | lista | Lista de reglas por tienda, 編集/削除/表示 | L tb_point_rules, tb_shop | |
| `point_rules_new.php` / `point_rules_update.php` | ポイントルール新規 / 修正 | alta/edición | Carga `point_rules_frm.php` | L/E tb_point_rules | |
| `point_rules_frm.php`, `point_rules_errchk.php` | — | fragmento(include) | Tienda, **ルール区分** (時間 / 曜日 / 期間 / 毎月○○日 / 日付), horas/fechas, `point_count`, **ランク** (使用金額 / 来店回数) | L tb_shop, tb_kingaku_rank_master, tb_raiten_rank_master | |
| `point_rules_register.php` | (登録) | registro(POST) | INSERT/UPDATE según `type_flg` | E tb_point_rules, hatuban | |
| `point_rules_delete.php` | (削除) | borrado | DELETE físico (`WHERE cd`) | E tb_point_rules | Único borrado físico del grupo |
| `point_aggregate.php` | ポイント集計 | lista | Cuadro por tienda: 会員登録(CRM) / (マイコレ) / (紹介), 来店, 紹介, 毎日ログイン, プッシュ, 会計, 手動調整, その他, 小計; filtros de fecha | L tb_shop | 350 líneas con JS |
| `ajax_point_aggregate.php` | — | ajax | Agrupa `tb_point_log` por `action_flg` | L tb_point_log, tb_shop | |
| `csv_grant_points.php` | ポイント一括付与 | csv | Descarga plantilla `base.csv` (顧客番号,付与ポイント) y sube CSV (SJIS-win) → `Point::setPoint` (agregar) por cliente `id`, `touroku_kubun='2'`; error por línea | L tb_customer; E tb_customer_point, tb_point_log | Valida regex numérica; archivo temporal en `uploads/temp` |
| `ajax_point_yukoukigen_kakunin.php` | — | ajax | Cuenta clientes que se verían afectados al cambiar la caducidad | L tb_customer, tb_customer_point, admin_master | |
| `point_alert_mail_template_entry.php` / `_register.php` | ポイント有効期限切れ前通知 テンプレート | config/registro | Editor de plantilla del aviso (ventana 通知内容) | L/E admin_master, contents_master | |
| `point_shikkou_mail_template_entry.php` / `_register.php` | ポイント失効通知 テンプレート | config/registro | Plantilla del aviso de caducidad ocurrida | L/E admin_master | |
| `point_shikkou_alert_mail_template_entry.php` / `_register.php` | 失効前通知 テンプレート (variante múltiple) | config/registro | | L/E point_shikkou_alert | Duplicadas también en `category0/config/` |
| `sendPointShikkouMail.php` | (cron) | cron/CLI | Pone a NULL `service_point` y `point_yukoukigen` de los vencidos, **inserta `tb_point_log`** con `shori_mae_point` y `shouhi_point`, y notifica | L/E tb_customer_point, tb_point_log, tb_customer; L tb_raiten_rireki, tb_office, admin_master | solo CLI (403 desde web) |
| `sendPointShikkouAlertMail.php`, `sendPointShikkouAlertMailMultiple.php` | (cron) | cron/CLI | Avisos previos a la caducidad (uno o varios días) por push (`push_master`) | E push_master; L tb_customer, tb_customer_point | |
| `setRegisterPoint.php` | (cron) | cron/CLI | Cada 5 min: por tienda lee `tb_shop.customer_registration_settings` (serializado: `registPoints`, `days`, `hours`, `minutes`) y da puntos a quien se registró hace N días (acción `crm:newRegist`/`mycolle:newRegist`) | L tb_shop, tb_customer, tb_point_log; E vía `Point::setPoint` | |
| `setPaidMemberPoint.php` | (cron) | cron/CLI | Puntos a miembros de pago (`paid_member_settings`) | L tb_customer, admin_master, contents_master; E push_master | |
| `mailCommon.php`, `inc/page.php` | — | fragmento(include) | Envío de aviso y paginación | L tb_office | |

**Flujo principal**: configurar (`point_config`) → crear reglas (`rules_list` → `rules_new` → `errchk` → `register`) → los puntos se acumulan en caja/visita (`Point::setAccountingPoint*`, `setComingInfo`, fuera de este grupo) → caducidad por cron → resultados (`point_aggregate`).

**Reglas de negocio no obvias (de `Point.php` y reglas)**
- `Point::setPoint($puntos, $tipo, $shop, $staff, $cliente, $action_flg, $terminal)`: tipo 1 = sumar, tipo 2 = restar (falla si `saldo < puntos`: "…削減することは出来ません"). Usa `BEGIN … FOR UPDATE` en `tb_customer_point`; después `UPDATE`+`INSERT … WHERE NOT EXISTS`, inserta `tb_point_log` (`sounyu_point` suma / `shouhi_point` resta, `shori_mae_point`, `action_flg`). Cada alta/baja **renueva `point_yukoukigen`** = hoy + `admin_master.point_shikkou_kikan` (365 días por defecto) − 1 s.
- **Prioridad de reglas** (`getPointCount`): la primera que encaje gana, en este orden: **5 fecha concreta → 4 día del mes (32 = fin de mes) → 3 periodo → 2 día de la semana + franja → 1 franja horaria**, y si ninguna aplica, valor por defecto (`getDefaultPointSetting`, % por importe efectivo/tarjeta). Filtros por ランク (`kingaku_rank_master_cds`, `raiten_rank_master_cds`) y tienda; `hyouji_flg <> '9'`.
- Con la opción "有効期間延長動作" la compra extiende o no la caducidad (`point_shikkou_sousa`); `point_yukoukigen_flg = '2'` desactiva la caducidad.
- Puntos por importe: `cash_rate`/`card_rate` % por tienda y por **grupo de cliente**, con tope `use_limit` y bandera "calcular incluyendo puntos usados".
- Referidos: puntos al referente (con tope) y al referido.
- Edad/ranking de visita (`getCustomerAge`) y `exchangeSalesItem` (canje de ítem de venta por puntos).
- **`src/Point.php`** (86 líneas) es la versión recortada con namespace; **la lógica real vive en `system/class/Point.php` (761 líneas)**, que extiende `cHatuban` y llama a `category1/inc/raiten_function.php` (`changeCustomerRaitenData`) y `CrmSystem\Sales\SalesItem`.

**Servicios viejos y reemplazo**: cron por `crontab` → Scheduler (`promotions:loyalty`, ya existe `ProcessLoyaltyExpiry`); correo de avisos (`cMailsoushin`) → `Mailable` + cola; push (`push_master`) → `PushSender`; CSV SJIS con `file()` → `Storage`/`StreamedResponse` + validación de `FormRequest`; DELETE físico → SoftDeletes.

---

## スタンプ管理 (`category3/stamp/`)

- Tarjeta de sellos: diseño de tarjeta (79 diseños), especificación (máx. de sellos, bono de registro, por tienda), reglas de emisión por tramos, aviso con cupón al alcanzar N sellos, caducidad y avisos.
- Menú: スタンプデザイン管理, スタンプ仕様管理, スタンプルール一覧, クーポンお知らせ機能管理.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `stamp_pattern.php` | スタンプデザイン管理 | config | Marcar diseños disponibles y diseño por defecto | L tb_stamp_pattern | JS limita marcados a `stamp_max` |
| `stamp_pattern_register.php` | スタンプデザイン管理 (登録) | registro(POST) | `pic_shop_flg`, `pic_default` | E tb_stamp_pattern | |
| `stamp_config.php` | スタンプ仕様管理 | config | スタンプ有効期間の使用選択, 更新仕様, 有効期間, 失効処理時間, 失効前通知, **店舗ごと**: 来店時のスタンプ (間隔), 現金/カード還元, スタンプ数最大, マイページ交換用 | L tb_stamp_config, tb_shop, admin_master, hatuban | 933 líneas |
| `stamp_config_register.php` | (登録) | registro(POST) | `stamp_max`, `touroku_bonus`, `limit_flg` por tienda; `admin_master.*` (`stamp_shikkou_*`, `raiten_stamp_flg`, `mypage_stamp_hyouji_flg`); recalcula `tb_customer.stamp_yukoukigen` al activar caducidad | E tb_stamp_config, admin_master, tb_customer, tb_shop | |
| `stamp_rules_list.php` | スタンプルール一覧 | lista | Reglas por tienda | L tb_stamp_rules, tb_shop | |
| `stamp_rules_new.php` / `stamp_rules_update.php` | スタンプルール新規 / 修正 | alta/edición | Carga `stamp_rules_frm.php` | L/E tb_stamp_rules | |
| `stamp_rules_frm.php`, `stamp_rules_errchk.php` | — | fragmento(include) | Tienda, 区分 (時間/曜日/期間/毎月/日付), `stamp_count` | L tb_shop | |
| `stamp_rules_register.php`, `stamp_rules_delete.php` | (登録/削除) | registro(POST)/borrado | Igual que reglas de puntos (mismo `switch type_flg`) | E tb_stamp_rules, hatuban | |
| `oshirase_coupon.php` | クーポンお知らせ機能管理 | config | Elige tienda (AJAX) y nº de sellos → cupón de aviso | L tb_stamp_config | |
| `ajax_oshirase_coupon.php`, `oshirase_coupon_register.php` | — | ajax/registro | Carga y guarda `tb_stamp_oshirase_coupon` (tienda, nº de sellos, cupón, mensaje) | L/E tb_stamp_oshirase_coupon; L tb_mycoupon_master, contents_master | |
| `stamp_alert_mail_template_entry/_register.php`, `stamp_shikkou_mail_template_entry/_register.php` | テンプレート | config/registro | Plantillas de aviso previo y de caducidad | L/E admin_master, contents_master | |
| `ajax_stamp_shikkou_kakunin.php` | — | ajax | Cuenta clientes afectados al cambiar caducidad | L tb_customer | |
| `sendStampShikkouMail.php`, `sendStampShikkouAlertMail.php` | (cron) | cron/CLI | Caduca sellos (`tb_stamp_log`, `tb_customer.stamp_count`) y avisa | E tb_stamp_log, tb_customer; L tb_customer_point, tb_raiten_rireki | solo CLI |
| `inc/page.php` | — | fragmento(include) | | | |

**Reglas no obvias**: las reglas de sello son **de acumulación por tramo horario/día/periodo** como las de puntos (misma tabla de tipos 1-5), no "premio por N sellos"; el premio con cupón está en `tb_stamp_oshirase_coupon`. El saldo se guarda en `tb_customer.stamp_count`, `stamp_yukoukigen`. Un bono de registro (`touroku_bonus`) se concede al alta.

---

## スマトピ連携 (`category3/smatopi/`)

- Envío de mensajes **LINE / push** a través del servicio externo **smatopi.net** (SaaS aparte con su propio login). Se muestra en el menú solo si `admin_master.smatopi_flg = 1` y el sistema hijo "スマトピ連携" está activo. Las pantallas se abren en pestaña nueva.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `smatopi_message.php` | スマトピ / メッセージ作成 | alta | Redacta mensaje (texto, cupón, tags), elige destinatarios; usa `class/Smatopi.php` | L tb_shop | JS `smatopiMessage.js`, `smatopiLine.js`, `smatopiCommon.js` |
| `ajax_smatopiMessage.php` | — | ajax | Envía el mensaje vía API `…/api/my-colle/` y guarda historial | E smatopi_message | |
| `ajax_smatopiLine.php` | — | ajax | Envío LINE por lista de clientes (`$_SESSION['lineMessage'][token]['sql']`) | E smatopi_message | SQL guardado en sesión |
| `smatopi_sent_history.php` | スマトピ / メッセージ送信履歴 | lista | Historial con detalle | L tb_shop | `smatopiSentHistory.js` |
| `ajax_smatopiSentHistory.php` | — | ajax | Lista el historial | L smatopi_message | |

Servicio externo: `Smatopi` (constantes `DEVELOP_API_END_POINT`/`PRODUCTION_API_END_POINT`, métodos `getFriendsCount`, `getMessageCount`, `getTags`, `getCoupons`, `sendMessage`, `sendLineMessage`; ID `getDomainSmatopiId`). No se puede probar en Docker (MANUAL-TESTS #38-40). En CRMSystem el canal push queda tras la interfaz `PushSender`; Smatopi sería una **implementación adicional** de canal LINE (Http client).

---

## 来店履歴管理 (`category3/visit/`)

- Historial de visitas y estadísticas (por hora/día/semana, sexo, edad, ocupación), exportación CSV, y **configuración del lector de tarjetas** (intervalo mínimo entre lecturas).
- Menú: 来店履歴一覧 (`visit_stats2.php?shop_cd=&kikan=0`), カードリーダ設定.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `visit_stats2.php` | 来店履歴一覧 | lista | Estadística por periodo (`kikan` = 3 opciones: día/mes/…), tienda o todas; gráficos por hora y día de la semana | L tb_raiten_rireki, tb_customer, tb_shop | La versión **vigente** (956 líneas) |
| `day_visit_list.php` | 来店履歴一覧 (日別) | lista | Visitas de un día con nombre de cliente | L tb_raiten_rireki, tb_customer, tb_shop | |
| `store_visit_list.php` | 個別来店履歴 | lista | Visitas por tienda y hora | L tb_raiten_rireki, tb_customer, tb_shop | |
| `customer_detail.php` | 顧客登録情報 | detalle | Ficha del cliente desde una visita (sellos, categorías de búsqueda) | L tb_customer, tb_customer_sub, tb_search_*, tb_shop, tb_stamp_config | Redirige a `customer_list.php` inexistente si no hay cliente (404) |
| `csv_download.php`, `daycsv_download.php`, `storecsv_download.php` | CSV de visitas | csv | Descarga por periodo/día/tienda (cabeceras `text/x-csv`, EUC/SJIS) | L tb_raiten_rireki, tb_shop | `daycsv_download.php` está vacío (0 líneas) |
| `touch_config.php` | カードリーダ設定 | config | Intervalo del lector por tienda (0 = sin límite, 3600…) | L tb_touch_mode, tb_shop, hatuban | |
| `touch_config_register.php` | (登録) | registro(POST) | | E tb_touch_mode | En CRMSystem es `shops.visit_interval_seconds` (ya existe) |
| `visit_stats.php`, `new_visit_stats.php`, `stats_test.php`, `stats_test2.php`, `csv_test.php`, `csv_test2.php` | (versiones antiguas / pruebas) | lista/csv | Copias previas de `visit_stats2.php` | L tb_raiten_rireki, tb_customer, tb_shop | **No migrar** |
| `fix_visit_stats.php` | (corrección) | cron/CLI | Reescribe `customer_age/sex/job` de toda `tb_raiten_rireki`; **bloqueado a 403** (peligroso) | E tb_raiten_rireki | No migrar; sustituir por columnas derivadas en `visits` |

**Reglas no obvias**: `tb_raiten_rireki` guarda en cada visita edad, sexo y ocupación del cliente **en ese momento** (`customer_age`, `customer_sex`, `customer_job`), no calculados al leer. `raiten_time` es timestamp.

Ya cubierto en CRMSystem: `visits`, `VisitHistoryController` (`/promotions/visit-history`), `VisitIntervalController`. Falta: listado por día y por tienda, CSV, ficha desde visita.

---

## 予約管理 (`category3/reservation/`)

- Reservas de clientes con **categorías de reserva** por tienda, **campos configurables** (項目), calendario mensual, lista, cambio de estado (cancelar / confirmar / marcar 来店), y "予約機種一覧" (reservas de cambio de modelo con puntos del cliente; uso específico).
- Menú: 予約新規登録, 予約・来店一覧, 予約カテゴリ登録・変更, 予約項目設定, 予約機種一覧.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `reserve_customer_search.php` | 予約新規登録 / 顧客一覧・検索 | lista | Buscador de clientes (mismos filtros de búsqueda) para elegir a quién reservar | L tb_customer, tb_search_cate, tb_search_form | |
| `reserve_new.php` | 予約新規登録 | alta | Tienda, categoría, personal, fecha/hora, campos configurables | L tb_customer, tb_koumoku, tb_reserve, tb_reserve_name, tb_shop, tb_staff | 433 líneas |
| `reserve_new_confirm.php` | 予約新規登録 確認 | confirmación | Resumen | L idem | |
| `reserve_new_register.php` | (登録) | registro(POST) | INSERT/UPDATE con `hatuban` | E tb_reserve, hatuban; L tb_customer, tb_reserve_name, tb_shop, tb_staff | |
| `reserve_new_complete.php` | 予約新規登録 完了 | confirmación | Pantalla final con datos | L tb_reserve, … | |
| `reserve_calendar.php` | 予約・来店一覧 / 予約一覧 | lista | Calendario mensual, cambio de mes, horario de trabajo de la tienda | L tb_reserve, tb_shop, tb_shop_work | |
| `reserve_list.php` | 予約一覧 | lista | Lista del día/rango | L tb_reserve, tb_customer, tb_shop | |
| `reserve_detail.php` | 予約詳細 | detalle | Detalle con botones de estado | L tb_reserve, tb_customer, tb_reserve_name, tb_shop, tb_staff | |
| `reserve_change_status.php` | 予約情報 | registro(POST) | `reserve_flg='9'` cancelar, `'2'` confirmar, `raiten_flg='1'` marcar como asistió | E tb_reserve | |
| `reserve_delete.php` | 予約情報 (削除) | borrado | Borra la reserva | E tb_reserve | |
| `category_entry.php` | 予約カテゴリ登録・変更 | lista/alta | Alta/edición inline de categorías por tienda | L/E tb_reserve_name, tb_shop | |
| `category_register.php`, `category_delete.php` | 予約カテゴリー登録 / 削除 | registro(POST)/borrado | | E tb_reserve_name, hatuban | |
| `config.php` | 予約項目設定 | config | Casillas de campos que pide el formulario de reserva | L tb_koumoku, tb_shop, kaiin_master | `kaiin_master` = tabla antigua (revisar) |
| `config_register.php` | 予約項目設定 (登録) | registro(POST) | | E tb_koumoku | |
| `ajax_category.php`, `ajax_staff.php`, `inc/check_select.php` | — | ajax/fragmento | Categorías por tienda y personal por tienda | L tb_reserve_name, tb_staff | |
| `model_reservation.php` | 予約機種一覧 | lista | Listado de reservas de cambio de modelo con fecha calculada (`resevation_type`: 今すぐ, 3/6 meses, 1/2 años) y puntos | L tb_change_model, tb_customer, tb_customer_point, tb_shop | De un cliente concreto; `menu = model_reservation` también en `push_history` |

**Reglas no obvias**: `reserve_flg` 2 = confirmada, 9 = cancelada; `raiten_flg = '1'` = asistió (vincula con visita); categoría de reserva por tienda; campos de formulario por `tb_koumoku`; el **recordatorio** (`reminder/`, otro agente) depende de `tb_reserve`. Hay una segunda familia de reservas en `category9/yoyaku` / `category11/yoyaku` (con `$posConnect`, fuera de este grupo).

---

## Tablas de BD del grupo

| Tabla | Para qué | Submódulos que la usan | Notas (columnas clave) |
|---|---|---|---|
| `tb_c3_anketo_cate` / `tb_c3_anketo2_cate` | Encuesta (cabecera) | anketo, anketo2 | `name`, `comment`, `hyouji_flg`, `start_date`, `end_date`, `attached_cd` (cupón premio), `disp_flg` |
| `tb_c3_anketo_form` / `tb_c3_anketo2_form` | Pregunta | anketo, anketo2 | `tb_c3_anketo_cate_cd`, `form_type`, `flds_row`, `eachform<N>`/`eachsend<N>`, `style_class`, `rank`, obligatorio |
| `tb_c3_anketo_sub` / `tb_c3_anketo2_sub` | Respuesta (una fila por ítem) | anketo, anketo2 | `tb_customer_cd`, `category_inner_group` (grupo de respuesta), `touroku_date` |
| `tb_mycoupon_master` | Maestro de cupón | mycoupon, anketo*, stamp | `title`, `comment`, `limit_flg`, `time_limit`, `coupon_kubun`, `shouhi_point`, `saitouroku_flg`, `attention`, `tb_shop_cd` |
| `tb_mycoupon` | Cupón emitido a un cliente | mycoupon | `tb_mycoupon_master_cd`, `tb_customer_cd`, `time_limit`, `coupon_kubun` |
| `tb_mycoupon_used` | Uso/canje | mycoupon | cupón + cliente + tienda + hora |
| `tb_coupon`, `tb_haikei`, `pit_coupon` | Cupón-imagen antiguo, fondos | coupon | `shop_master` es esquema antiguo |
| `tb_point_rules` | Reglas de puntos por tramo | point | `tb_shop_cd`, `type_flg` 1-5, `start_time`, `end_time`, `day_of_week`, `point_count`, `kingaku_rank_master_cds`, `raiten_rank_master_cds`, `hyouji_flg` |
| `tb_point_log` | Movimientos de puntos | point | `tb_customer_cd`, `tb_shop_cd`, `tb_staff_cd`, `tb_terminal_cd`, `action_flg`, `shori_mae_point`, `sounyu_point`, `shouhi_point` |
| `tb_customer_point` | Saldo de puntos | point, reservation | `tb_customer_cd`, `service_point`, `point_yukoukigen` |
| `tb_kingaku_rank_master`, `tb_raiten_rank_master` | Rangos por gasto / visitas | point | Condición de reglas |
| `tb_customer_group` | Grupo de cliente (con regla de puntos propia) | point | |
| `point_shikkou_alert` | Avisos de caducidad múltiples | point | |
| `tb_stamp_config` | Configuración de sellos por tienda | stamp, visit | `tb_shop_cd`, `stamp_max`, `touroku_bonus`, `limit_flg` |
| `tb_stamp_rules` | Reglas de sellos por tramo | stamp | como `tb_point_rules` + `stamp_count` |
| `tb_stamp_pattern` | Catálogo de 79 diseños | stamp | `pic_shop_flg`, `pic_default` |
| `tb_stamp_log` | Movimientos de sellos | stamp | |
| `tb_stamp_oshirase_coupon` | Cupón de aviso por nº de sellos | stamp | |
| `tb_raiten_rireki` | Visitas | visit, point, stamp, anketo2, mycoupon | `user_cd`, `raiten_time`, `customer_age/sex/job` |
| `tb_touch_mode` | Intervalo del lector | visit | |
| `tb_reserve` | Reservas | reservation | `reserve_flg`, `raiten_flg`, `update` |
| `tb_reserve_name` | Categorías de reserva | reservation | `tb_shop_cd` |
| `tb_koumoku` | Campos de formulario configurables | reservation, anketo*, mycoupon | |
| `tb_shop_work`, `tb_staff` | Horario y personal | reservation | |
| `tb_change_model` | Reservas de cambio de modelo | reservation | |
| `smatopi_message` | Historial de envíos Smatopi | smatopi | `title`, `message`, `request_data`, `query`, `shop_cd` |
| `admin_master` (cd=1) | Parámetros globales: `point_*`, `stamp_*`, `smatopi_flg`, plantillas de aviso | point, stamp, smatopi | Se mapea a `point_settings`/`stamp_settings`/config |
| `contents_master` | Pares clave/valor ("contenidos adicionales") | anketo2, mycoupon, point, stamp | `category`, `field`, `int_data` |
| `mailmag_master`, `tb_mailmag_analyze`, `push_master` | Envíos y clics | anketo*, mycoupon, point | Cubiertos por el análisis de mensajería |
| `hatuban` | Contador de numeración | casi todos | No migrar |
| `tb_customer`, `tb_shop`, `tb_office` | Cliente (`stamp_count`, `stamp_yukoukigen`), tienda (`customer_registration_settings` serializado), empresa | todos | Grupo clientes/tiendas |

## Propuesta Laravel 13

**Modelos Eloquent** (los marcados ✓ ya existen en `public_html/app/Models`): `Coupon` ✓, `CouponCustomer` ✓, `PointSetting` ✓, `CustomerGroupPointRule` ✓, `CustomerPoint` ✓, `StampSetting` ✓, `StampRule` ✓, `CustomerStamp` ✓, `Survey` ✓, `SurveyQuestion` ✓, `SurveyResponse` ✓, `Rank` ✓, `RankSchedule` ✓, `Visit` ✓, `VisitMotive` ✓. Nuevos: `PointBonusRule` (reglas de tramo, tabla `point_bonus_rules`), `StampBonusRule` (tramos de sello; **no** es `StampRule`), `Reservation`, `ReservationCategory`, `ReservationField`, `SmatopiMessage`, y opcionalmente `StampDesign` (79 diseños).

**Rutas** (prefijo ya usado `promotions.`, grupo existente en `routes/web.php`): `/promotions/coupons`, `/promotions/surveys` (+ `/{survey}/responses`, `/{survey}/analysis`, `/{survey}/export`), `/promotions/stamps`, `/promotions/points` (+ `/rules`, `/aggregate`, `/import`), `/promotions/visit-history`. Nuevo grupo `/reservations` (módulo propio, ya está anotado como módulo 9 pendiente): `calendar`, `list`, `categories`, `settings`. Smatopi: `/promotions/smatopi/messages`, `/history`.

**Controladores**: ya existen `CouponController`, `StampController`, `PointController`, `SurveyController`, `VisitHistoryController`. Añadir: `SurveyResponseController` (lista, detalle, análisis, CSV), `PointRuleController`, `PointAggregateController`, `PointImportController` (CSV con `FormRequest` + cola), `CouponStatsController` (ranking, uso, historial), `VisitExportController` (CSV por día/tienda), `ReservationController` + `ReservationCategoryController` + `ReservationSettingsController`, `SmatopiController`.

**Jobs/commands/scheduler**: ya existen `ProcessLoyaltyExpiry` (equivale a `sendPointShikkouMail` + `sendStampShikkouMail` + avisos previos), `SendScheduledMessages`, `RunMessageRules`. Añadir: `loyalty:grant-signup-points` (equivale a `setRegisterPoint.php`, cada 5 min o diario), `loyalty:grant-paid-member-points` (`setPaidMemberPoint.php`), `ImportPointsJob` (CSV masivo), `IssueCouponsJob` (reemplaza `mycoupon_hakkou.php`, sin `$_SESSION['wherestr']`).

**Policies/Gates**: `CouponPolicy`, `SurveyPolicy`, `PointPolicy` por tienda y por permiso (equivalente a `myKengen[1009/1005/1011]`); `Gate::define('smatopi')` condicionado a un flag de configuración.

**Qué ya cubre CRMSystem (según `Doc/database/TABLAS_MODULO_PROMOCIONES.md` y migraciones)**

| Legacy | Cubierto por | Estado |
|---|---|---|
| マイクーポン maestro, emisión, canje, ranking | `coupons`, `coupon_customer`, `CouponIssuer`, `CouponController`, enums de uso/vigencia | **Cubierto** salvo historial de uso y estadísticas por periodo (ver `AnalyticsController`) |
| スタンプ仕様 (máx., bono, intervalo, caducidad, avisos) | `stamp_settings` + `StampController` | Cubierto; **sin** reglas por tramo horario y **sin** diseños (79) |
| スタンプルール (tramos) | no | **Falta** (`stamp_rules` actual = premio por nº de sellos, equivale a `oshirase_coupon`) |
| クーポンお知らせ (premio por nº de sellos) | `stamp_rules` (`stamp_count`, `coupon_id`, `notify_flg`, `message`, `reset_flg`) | Cubierto |
| ポイント仕様 (caducidad, % efectivo/tarjeta, referidos, alta) | `point_settings`, `customer_group_point_rules`, `PointController` | Cubierto |
| ポイントルール (5 tipos con rangos) | no | **Falta** |
| ポイント集計, 一括付与 CSV | no (`customer_points` + `LoyaltyService::addPoints` ya sirven de base) | **Falta** UI |
| `Point::setPoint` / vencimiento / saldo | `LoyaltyService::addPoints/expire/refreshBalances` | Cubierto (diseño por movimientos con saldo derivado; difiere del legacy "saldo + log") |
| ランク (rangos por gasto/visitas) | `ranks`, `rank_schedules` | Cubierto |
| アンケート (cabecera, preguntas, respuestas) | `surveys`, `survey_questions`, `survey_responses` (answers jsonb) | Cubierto el modelo; **falta** análisis, CSV, lista de respuestas por cliente, "encuesta activa en la app" |
| 来店履歴 | `visits`, `VisitHistoryController` | Cubierto en parte (faltan listas por día/tienda y CSV) |
| カードリーダ設定 | `shops.visit_interval_seconds` + `VisitIntervalController` | Cubierto |
| 予約 | no | **Falta** todo (tabla `reservations` anotada como módulo 9) |
| スマトピ | `PushSender` (interfaz) | Falta implementación y pantallas |
| クーポン imagen (`coupon/`) | no | No migrar |

**Orden recomendado de implementación**
1. (Dependencias previas: clientes, tiendas, staff, grupos de cliente, rangos — ya hechos en módulo 1.)
2. Reglas de puntos por tramo + `PointAggregate` + CSV de carga (los puntos ya se calculan por `LoyaltyService`; reutilizar `Point::getPointCount` como especificación de prioridad).
3. Reglas de sellos por tramo + diseños de tarjeta.
4. Estadísticas/ranking/historial de cupones y encuestas (análisis, CSV, respuestas por cliente, encuesta activa).
5. Historial de visitas (día/tienda/CSV) y ficha desde visita.
6. Reservas (módulo 9; luego activar recordatorios de mensajería).
7. Smatopi (opcional, depende del cliente de Japón).

Dependencias con otros grupos: **mensajería** (envío de cupón/encuesta, clics, plantillas de aviso, `mailmag_master`), **clientes** (`tb_customer`, filtros de búsqueda `tb_search_*`, grupos, rangos), **tiendas/staff** (`tb_shop`, `tb_staff`, `tb_shop_work`), **POS/caja** (suma de puntos y canje de cupón: `setAccountingPoint*`, `tb_mycoupon_used`), **Mi Página/app** (muestra cupón/encuesta activa/sellos; `hp/` fuera de alcance).

## Riesgos y dudas

1. **Regla de puntos y duplicados de lógica**: `Point.php` mezcla puntos, rangos, visitas, venta de ítems y canje (761 líneas); `src/Point.php` es una copia recortada (86 líneas) con otro namespace. Definir una fuente única (`LoyaltyService`) y cubrir con pruebas la **prioridad de reglas** (fecha > día del mes > periodo > día de la semana > hora). Duda: ¿el cálculo `cash_rate`/`card_rate` del nuevo diseño reemplaza por completo `tb_point_rules` o conviven? (ver `PENDING.md` y "Efectivo o tarjeta" en `TABLAS_MODULO_PROMOCIONES.md`).
2. **SQL en sesión y concatenado**: `mycoupon_hakkou.php` usa `$_SESSION['wherestr']` como cláusula SQL; `ajax_smatopiLine.php` usa `$_SESSION['lineMessage'][token]['sql']`; `anketo_form_issue.php`, `mycoupon_issue.php` guardan filtros de la misma manera. Migrar a filtros estructurados (`CustomerSearch`) y jobs con ids, nunca SQL.
3. **Integraciones externas** (`Doc\legacy-crm2\INTEGRATIONS.md`): **smatopi.net** (API `…/api/my-colle/`, servicio de terceros sin sandbox en Docker); correo y push; servidor de envíos con cron (`mail_sche_send.php`); reconocimiento de que `crontab` en producción llama a `sendPointShikkouMail`, `setRegisterPoint`, etc. — no hay el crontab real en el repositorio, solo un `cronfile` de ejemplo en `anketo/` y `mycoupon/`.
4. **Pantallas rotas / peligrosas** (`BROKEN-FILES.md`): `coupon/pit_coupon1.php` (`shop_master`), `process-sortable.php` (rutas), `visit/customer_detail.php` (redirige a `customer_list.php` inexistente), `mycoupon_create/complete.php` (cargan `session.php` antes de `db.php`), `visit/fix_visit_stats.php` (bloqueado, reescribe `tb_raiten_rireki`). `reservation/config.php` consulta `kaiin_master` (esquema antiguo) — confirmar si falla.
5. **Tablas que faltan / esquema antiguo**: `shop_master`, `kaiin_master` (no existen en el esquema actual); `tb_c3_anketo_cate_cd` usado también por `anketo2` (columna sin "2") — verificar contra `MISSING-TABLES.md`; `tb_c6_kujibiki` (ガチャリン) depende de otro sistema hijo.
6. **Decisiones pendientes**: ¿se migra el cupón-imagen `coupon/` (sin enlace, roto)? ¿`anketo` y `anketo2` se unifican en `surveys`? (recomendado) ¿Se conserva el borrado físico de `tb_point_rules`? ¿Qué se hace con `visit_stats*.php`/`stats_test*.php` duplicados (no migrar)? ¿Sigue Smatopi en uso (se oculta tras `smatopi_flg`)? ¿La "respuesta de prueba" (`anketo_test_register`) debe poder marcarse y excluirse de las estadísticas?
7. **Adobe Flash/CSV**: las descargas CSV del legacy (Flash en otras pantallas) no aplican aquí, pero los CSV de visitas y encuestas usan SJIS/EUC-JP: confirmar con el cliente si se mantiene SJIS (Excel japonés) o UTF-8 con BOM.
8. **Guardado de saldos**: el legacy usa saldo almacenado + log; CRMSystem calcula saldo por suma de movimientos (`customer_points`). Hay que **migrar datos** (convertir saldo actual y fecha de caducidad en movimiento inicial) y decidir cómo representar `point_yukoukigen` único frente a vencimiento por movimiento.
