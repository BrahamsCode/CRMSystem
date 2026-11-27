# 03a — Promociones: Mensajería (販売促進管理, parte mensajería)
> Fuente: C:\Users\User\VivaTech\crm2-php8 (rama master = código ya migrado a PHP 8.5.12). Generado el 2026-11-26. Destino: Laravel 13 + PHP 8.5.

Alcance: `public_html/system/category3/` → `mail_mag`, `decomail`, `auto`, `follow`, `reminder`, `push`, `dm`, `digital_signage`, `inc`, `class` y los PHP de la raíz (`index.php`, `process-sortable.php`). Quedan fuera anketo, coupon, point, stamp, visit, reservation, smatopi y mycoupon (otro agente). `hp/`, `admin/` y `category8` excluidos.

## Idea clave: el legacy son 5 "plantillas" con un solo motor

Casi todos los PHP de `auto/`, `follow/`, `reminder/`, `decomail/` y `mail_mag/` son **envoltorios de 15-30 líneas** que fijan 3-4 variables (`$menu`, `$mail_type`, `$tb_base_system_child_cd`, a veces `$title`/`$mode`) y hacen `require` de un formulario compartido de `category3/inc/`. La lógica real vive en 8 includes grandes:

| Include | Líneas | Hace |
|---|---|---|
| `inc/mail_customer_select_frm.php` | 379 | 発行条件選択: filtros de clientes, genera `wherestr` y `search_display` (se guardan en sesión por `search_uniqid`) |
| `inc/mail_frm.php` | 1224 | Redacción (asunto, cuerpo, imagen, plantilla, cupón, programación) |
| `inc/mail_confirm_frm.php` | 642 | Confirmación y prueba |
| `inc/mail_send_frm.php` | 672 | **Registro**: INSERT/UPDATE en `mailmag_master`, lanza el envío inmediato o programa |
| `inc/mail_future_frm.php` / `_confirm_frm` / `_send_frm` | 1192 / 571 / 590 | Lo mismo para editar un programado |
| `inc/mail_future_list_frm.php` | 330 | Lista de programados/automáticos |
| `inc/mailmag_list.php`, `mailmag_shousai(2).php`, `mailmag_sended_list.php`, `mailmag_graph(2).php` | 483/456/448/249/207/323 | Pasados, detalle, destinatarios y gráficos de clics |
| `inc/push_list.php`, `inc/push_shousai.php` | 524 / 1039 | Pasados y detalle de push |

`$mail_type` (guardado como `mailmag_master.type_flg`) decide el comportamiento: `''` (メルマガ, mail), `deco`, `follow`, `followdeco`, `auto`, `autodeco`, `yoyaku`, `posyoyaku`, `remdeco`, `posremdeco`, `coupon`, `anketo` (los dos últimos son de otro agente pero usan el mismo motor). Esto ya está resuelto en CRMSystem con `messages` + `message_rules` (ver Propuesta).

## Resumen

| Módulo/submódulo | Carpeta | Nº PHP | Nº vistas reales (pantallas) | Complejidad | Puntos |
|---|---|---|---|---|---|
| お知らせ配信管理 (メルマガ) | `mail_mag/` | 56 | 24 (+ 6 AJAX/endpoint); ~20 archivos muertos o duplicados | XL | 12 |
| デコメール管理 | `decomail/` | 16 (1 es Qdmail, sin uso) | 6 + 2 cron/envío | M | 4 |
| 自動お知らせ管理 | `auto/` | 13 | 4 | M | 4 |
| フォローお知らせ管理 | `follow/` | 13 | 4 | M | 4 |
| リマインダお知らせ管理 | `reminder/` | 10 | 4 | M | 3 |
| プッシュ通知 | `push/` | 12 | 8 + 3 AJAX + 1 cron | L | 8 |
| DM発行 | `dm/` | 3 | 1 (+2 sin uso) | S | 1 |
| 新着情報 (digital_signage de category3) | `digital_signage/` | 5 | 2 | S | 1 |
| Motor compartido (includes, CSV, gráficos, AJAX) | `inc/` | 45 (incl. 8 .php de csv_download + SWF) | 12 fragmentos grandes + 12 endpoints AJAX/CSV | XL | 14 (incluye reportes) |
| Programador / cron / envío masivo | `class/cSendSchedule.php`, `system/class/cMailsoushin.php`, `class/Push.php` | 1 (+2 fuera de category3) | n/a | XL | 8 |
| Raíz | `index.php`, `process-sortable.php` | 2 | 1 (portada del módulo) | S | 0.5 |
| **Total** | | **176 PHP** (excluye tiny_mce y .dat) | **~70 pantallas/endpoints reales** | | **~59.5** |

(Los 56 de `mail_mag` no incluyen los 150+ `.dat`, ni `data/`, `uploads/`, `cron_schedule/`. `decomail/js/tiny_mce` es TinyMCE de terceros: no se migra.)

---

## お知らせ配信管理 — `mail_mag/`

- Qué hace: el operador elige a quién escribir (filtros de clientes), redacta el メルマガ de texto (con imagen opcional), prueba, envía ya o lo programa. Luego consulta programados, pasados, clics, plantillas, direcciones de prueba y los QR de alta a la lista.
- Es también el **hogar de los recursos compartidos**: las plantillas (`mail_template*`) y las direcciones de prueba sirven a todos los submódulos del grupo.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L/E) | Notas |
|---|---|---|---|---|---|
| `mail_customer_select.php` | 発行条件選択 (メール) | alta | Paso 1: filtros de cliente (envoltorio de `inc/mail_customer_select_frm.php`) | tb_customer L, tb_search_form/cate L, tb_koumoku L | Genera `wherestr`; sesión por `search_uniqid` |
| `mail_new.php` | 新規メール配信 | alta | Paso 2: redactar (envoltorio de `inc/mail_frm.php`, `$mail_type="mail"`) | tb_mail_template L, tb_shop L | Imagen subida a `uploads/temp` |
| `mail_new_confirm.php` | (confirmación) | confirmación | Previsualiza, botón de prueba (`sendTestMail`), 配信/予約 | tb_customer L, tb_mycoupon_master L | `mail_confirm_frm.php` |
| `mail_new_send.php` | (registro) | registro(POST) | INSERT `mailmag_master`; envío inmediato (`exec` PHP CLI de `mailsoushin.php`) o `cSendSchedule::addSchedule` | mailmag_master E, send_schedule E, hatuban E | Token de sesión `mail_send_verify` evita doble envío |
| `mail_future_list.php` | 予定配信お知らせ一覧 | lista | Lista programados; abrir edición | mailmag_master L | `mail_future_list_frm.php` |
| `mail_future.php` | 予定配信メール編集 | edición | Edita programado; envoltorio de `mail_future_frm.php` | mailmag_master L | |
| `mail_future_test.php` | (edición, variante) | edición | Misma pantalla que `mail_future.php` | idem | Duplicado |
| `mail_future_confirm.php` | (confirmación) | confirmación | Confirma cambios de programado | idem | |
| `mail_future_send.php` | (registro) | registro(POST) | UPDATE programado + `send_schedule`, o anulación | mailmag_master E, send_schedule E | |
| `mail_kaijo.php` | 予約配信メール 配信解除 | borrado | Anula el programado: `DELETE FROM mailmag_master` + `delSchedule`; en anketo repone `tb_c3_anketo_cate` | mailmag_master E, tb_c3_anketo_cate E | Borrado **físico** (no `disp_flg`) |
| `mail_past.php` | 過去配信お知らせ一覧 | lista | Envíos pasados (`$menu="mail_past"`) | mailmag_master L | `inc/mailmag_list.php` |
| `mail_past_shousai.php` | 過去配信詳細 | detalle | Detalle de un envío: conteos por tipo de dirección y gráfico | mailmag_master, tb_mailmag_analyze L | `inc/mailmag_shousai.php` |
| `mail_sended_list.php` | 配信先一覧 | lista | Destinatarios de un envío; CSV | mailmag_sended_log L, tb_customer L | Solo si `admin_master.mailmag_sended_log` activo |
| `mailmag_analyze.php` | クリックカウンタ (配信メールからのマイページアクセス集計) | lista | Clics por sexo/edad/ocupación y por hora | tb_mailmag_analyze L | `inc/mailmag_graph2.php` |
| `mail_template.php` | テンプレート管理 | lista | Lista y crea/edita/borra plantillas; selector de categoría | tb_mail_template L, tb_mail_template_category L | CRUD vía AJAX |
| `mail_template_entry.php` / `mail_template_register.php` | テンプレート登録 | alta/registro | Alta/edición de plantilla | tb_mail_template E, hatuban E | Borrado lógico `disp_flg = 9` |
| `mail_template_category.php` / `_category_register.php` | テンプレートカテゴリ管理 | alta/registro | Categorías de plantilla, con orden (`tb_order_item`) | tb_mail_template_category E | Orden drag&drop: `process-sortable.php` |
| `ajax_mail_template.php`, `ajax_mail_template_category.php` | (AJAX) | ajax | CRUD de plantillas y categorías | idem | |
| `test_mail_address.php` / `test_mail_address_register.php` | テストお知らせアドレス管理 | alta/registro | CRUD de direcciones de prueba por tienda/staff | tb_test_mail_address E, tb_staff L, hatuban E | |
| `ajax_test_mail_address_new.php`, `ajax_test_mail_address_staff.php` | (AJAX) | ajax | Alta rápida y carga de staff | tb_test_mail_address E, tb_staff L | |
| `base.php`, `base_mailmaga.php`, `base_mycolle.php`, `base_point.php` | メールマガジン基本情報 / マイコレ登録用QR / メルマガ用QR | detalle | 4 variantes de la misma pantalla: cifras de lista por tienda, URL de alta y QR (imprimir/descargar PNG) | tb_shop L, tb_customer L | Mismo código copiado 4 veces; el QR usa `hp/uploads/c3/` (dependencia de `hp/`) |
| `qr_img.php`, `qr_print_pdf.php` | QR / QR印刷 | pdf | Genera QR con librería `qr_img` (data en `data/qrv*.dat`) e imprime | — | Sin parámetro `d` da 500; los 150 `.dat` son datos de la librería QR |
| `form_config.php` / `form_config_register.php` | 表示・検索・CSV項目設定 | config | Qué campos se muestran/buscan/exportan | tb_search_form E, tb_koumoku L | Se enlaza desde el menú; pertenece a clientes (category1) |
| `mag_customer_config.php` | メール配信条件選択 | config | Solo referenciado desde category1 | tb_search_cate L | Probable resto |
| `mailsoushin.php` | (CLI) | cron/CLI | Llama `cMailsoushin::sendMailFromMailmagMaster` (envío inmediato) | ver motor | `PHP_SAPI !== 'cli'` o 403 |
| `mail_sche_send.php` | (CLI cron) | cron/CLI | Lo invoca crontab: comprueba hora, borra `send_schedule`, envía, recrea el crontab tras `sleep(5)` | send_schedule E, mailmag_master L/E | Ver sección Programador |
| `send_schedule.php` | (include) | fragmento(include) | Versión vieja del programador (reescribe crontab); no se usa en el flujo actual | send_schedule E | Candidato a borrar |
| `inc/` (3) | — | fragmento | `disp_pic`, `upload` (subida de imagen con temp), `furiwake_disp` | — | |
| **Sin uso / muertos**: `mail_new1.php`, `mail_new2.php`, `mail_future1/2/3.php`, `mail_past1.php`, `mail_list.php`, `mail_detail.php`, `mail_revise.php`, `mail_schedule.php`, `send.php`, `mm_add.php`, `mm_add1/2.php`, `base2.php`, `mail_sended_list` duplicada, `aterna_a4_12.php` | (varios) | — | Versiones antiguas (flujo de 3 pantallas, revista sin plantilla). `mail_schedule.php` da fatal; `aterna_a4_12.php` usa `kaiin_master` (esquema viejo); `send.php` es un formulario "お問い合わせ株式会社アトレック" ajeno | — | No migrar |

Flujo principal: `mail_customer_select` (filtros) → `mail_new` (redactar) → `mail_new_confirm` (prueba) → `mail_new_send` (registro + envío/programación) → `mail_future_list` o `mail_past`.

## デコメール管理 — `decomail/`

- Qué hace: lo mismo que メルマガ pero con cuerpo HTML (editor TinyMCE), enviado como correo multipart.
- Las pantallas son envoltorios con `$mail_type="deco"` de los mismos includes; las únicas lógicas propias son `mailsoushin.php` y `mail_sche_send.php` (493 líneas) con una **copia casi idéntica de `cMailsoushin`** (duplicación).

| Archivo | Título (ja) | Tipo | Qué hace | Tablas | Notas |
|---|---|---|---|---|---|
| `mail_customer_select.php` | 新規デコメール配信 (発行条件選択) | alta | Filtros | tb_customer L | |
| `mail_new.php` / `mail_new_confirm.php` / `mail_new_send.php` | 新規デコメール | alta/confirmación/registro | Redacción con TinyMCE → prueba HTML → registro | mailmag_master E | `$mail_type="deco"` |
| `mail_future_list.php`, `mail_future.php`, `mail_future_confirm.php`, `mail_future_send.php` | 予定配信デコメール一覧 | lista/edición/confirmación/registro | Programados | mailmag_master E | |
| `mail_past.php`, `mail_past_shousai.php`, `mail_sended_list.php` | 過去配信メール一覧 | lista/detalle | Pasados | mailmag_master, tb_mailmag_analyze L | `$tb_base_system_child_cd="32"` |
| `mailmag_analyze.php` | クリックカウンタ | lista | Clics | tb_mailmag_analyze L | |
| `mail_kaijo.php` | 配信解除 | borrado | Anula programado | mailmag_master E | |
| `mailsoushin.php`, `mail_sche_send.php` | (CLI) | cron/CLI | Envío con cupón: crea `tb_mycoupon` por destinatario, `{COUPON}`, `tb_raiten_rireki` para tags | tb_customer E, tb_mycoupon E, hatuban E | Copia del motor; usar solo el de `system/class` |
| `inc/qdmail.php` (4131 líneas) | — | fragmento | Librería Qdmail 3 copias, sin uso: el correo ya va por Symfony Mailer (`CrmDecomail`) | — | Borrar |
| `js/tiny_mce/` | — | fragmento | Editor TinyMCE 3 (2010) | — | Reemplazar |

Reglas no obvias: `inc/decomailTagReplace.php` convierte el HTML de TinyMCE a HTML de デコメール (`<p style="text-align:center">` → `<div align="center">`, color rgb→HEX) porque los móviles Docomo/AU/Softbank antiguos no soportan CSS; en 2026 no se necesita.

## 自動お知らせ管理 — `auto/`

- Qué hace: envíos recurrentes en fechas fijas (mismo día de ciertos meses, 32 = fin de mes) o en días de la semana. Hay variante push (`kind=autoPush`).
- Aunque `auto_new.php` y `auto_new_confirm.php` son pantallas propias (344/266 líneas), el resto son envoltorios.

| Archivo | Título (ja) | Tipo | Qué hace | Tablas | Notas |
|---|---|---|---|---|---|
| `auto_new.php` | 自動お知らせ新規作成 | alta | Elige tipo (texto/deco), modo (1=meses+días, 2=días semana), hora; `kind=autoPush` crea push | tb_office L, `push_follow_master` (push) | |
| `auto_new_confirm.php` | (confirmación) | confirmación | Resumen | — | |
| `auto_mail_customer_select.php`, `auto_mail_new.php`, `auto_mail_new_confirm.php`, `auto_mail_new_send.php` | 自動 / 配信条件選択 | alta/confirmación/registro | Filtros → redactar → registrar con `type_flg=auto/autodeco`, `send_parameter = modo<>meses<>días` | mailmag_master E | |
| `auto_mail_future_list.php` | 自動お知らせ設定一覧 | lista | Ver, 停止, anular | mailmag_master L | |
| `auto_mail_future.php`, `_confirm.php`, `_send.php` | (edición) | edición/confirmación/registro | Edita regla | mailmag_master E | |
| `mail_kaijo.php` | 配信解除 | borrado | Borra | mailmag_master E | |
| `inc/disp_pic.php`, `upload.php`, `uploads/` | — | fragmento | Imagen | — | |

## フォローお知らせ管理 — `follow/`

- Qué hace: mensaje N días después de la última visita, del alta o antes del cumpleaños; hay variante push.

| Archivo | Título (ja) | Tipo | Qué hace | Tablas | Notas |
|---|---|---|---|---|---|
| `follow_new.php` | フォローお知らせ新規作成 | alta | Elige tienda (-1 = todas), 用途 (1=来店後, 2=登録後, 3=誕生日前), días y hora; `kind=followPush` | tb_shop L, tb_search_cate L | |
| `follow_new_confirm.php` | (confirmación) | confirmación | Cuenta cuántos clientes encajan hoy | tb_customer_sub, tb_raiten_rireki, tb_yoyaku L | |
| `follow_mail_customer…` (via select), `follow_mail_new*.php` (3) | フォロー | alta/confirmación/registro | `type_flg=follow/followdeco`, `send_parameter = tienda<>用途<>días` | mailmag_master E | |
| `follow_mail_future_list.php` y `mail_future_list.php` | フォローお知らせ設定一覧 | lista | La segunda es una variante antigua | mailmag_master L | duplicado |
| `follow_mail_future*.php` (3) | (edición) | edición/confirmación/registro | | mailmag_master E | |
| `mail_kaijo.php`, `inc/*`, `uploads/` | — | borrado/fragmento | | | |

## リマインダお知らせ管理 — `reminder/`

- Qué hace: aviso X días antes de la reserva (`yoyaku`/`remdeco`; `posyoyaku`/`posremdeco` si la reserva está en el POS de la tienda).

| Archivo | Título (ja) | Tipo | Qué hace | Tablas | Notas |
|---|---|---|---|---|---|
| `reminder_new.php` | リマインダーメール新規作成 | alta | Tienda, tipo, días antes, hora; detecta POS (`tb_shop.pos_domain`) | tb_shop L, tb_base_system_grandchild L (`購買履歴`) | `kind` POS/CRM mezclado (2013.07.25) |
| `reminder_new_confirm.php` | (confirmación) | confirmación | Cuenta reservas afectadas | tb_yoyaku, tb_yoyaku_uketsuke_day, tb_customer, tb_staff L | Requiere módulo de reservas |
| `reminder_mail_new*.php` (3), `reminder_mail_future*.php` (4), `mail_kaijo.php` | リマインダお知らせ設定一覧 / 編集 / 解除 | lista/edición/registro | Igual que follow | mailmag_master E | |

## プッシュ通知 — `push/`

- Qué hace: notificaciones a la app (マイコレ): inmediatas, programadas (`scheduled`), de seguimiento (`follow`) y automáticas (`auto`). Pueden otorgar puntos (`post_type=2`). Consulta de historial y de aperturas.
- **Servicio externo**: `class/Push.php::exec` hace un `GET http://{b.g-api.net | b-develop.g-api.net}/api/mpa/pushMessage?domain=…&type=…&cd=…` por `fsockopen` (sin TLS); el servidor externo envía el push real a los dispositivos. Solo si `admin_master.application_use_flg=1`. Variable `admin_master.push_api_host` elige producción/desarrollo.

| Archivo | Título (ja) | Tipo | Qué hace | Tablas | Notas |
|---|---|---|---|---|---|
| `push_customer_select.php` | プッシュ通知 / 発行条件選択 | alta | Filtros (`mail_customer_select_frm`, `$mail_type="push"`) | tb_customer L | |
| `push_new.php` (888) + `push_new.js` | プッシュ通知新規作成 | alta | Texto, varios bloques con imagen+enlace (`contents[]` serializado), puntos y vencimiento, programar, prueba a un miembro | contents_master, tb_c1_customer L, tb_c3_anketo2 L | Subida por `/system/inc/ajax_picupload.php` |
| `ajaxSavePush.php` | (AJAX) | ajax | Valida (puntos, fecha futura, vencimiento) e INSERT en `push_master` (inmediato/programado) o `push_follow_master` (follow/auto) | push_master E, push_follow_master E, hatuban E | Exige `sessId = session_id()` |
| `ajaxChangeStatus.php` | (AJAX) | ajax | Pausar/anular | push_follow_master E | |
| `ajaxSearchMember.php` | (AJAX) | ajax | Busca cliente para la prueba | tb_customer L | |
| `push_complete.php` | プッシュ通知 (finalizado) | confirmación | Pantalla final | — | |
| `push_follow_list.php?type_flg=scheduled|follow|auto` | 予定通知設定一覧 / フォロー通知設定一覧 / 自動通知設定一覧 | lista | Lista y anula (`disp_flg` 1/7/9) | push_follow_master L/E | 3 entradas del menú, 1 archivo |
| `push_past.php` | 過去配信内容一覧 | lista | Pasados (`inc/push_list.php`) | push_notifications L | |
| `push_past_shousai.php` | 過去配信詳細 | detalle | Entregados, abiertos, por perfil (`inc/push_shousai.php`) | push_notifications, tb_customer L | `open_cnt` |
| `push_history.php`, `push_history_detail.php` | プッシュ履歴 | lista/detalle | Historial por cliente | push_notifications, tb_customer, tb_shop L | Con enlace en menú |
| `sendFollowPush.php` | (CLI cron, cada 5 min) | cron/CLI | Para cada `push_follow_master` con `next_send_date <= now`: crea `push_master`, recalcula `next_send_date` (`calculateNextSendDate`), llama `Push::exec` | push_follow_master E, push_master E, contents_master E | Lleva `last_push_date` en `contents_master` (`push_follow`) para no reenviar tras pausas |

## DM発行 — `dm/`

- Qué hace: elige clientes y genera una plantilla de etiquetas/hojas de DM (hoja A4 de 12 etiquetas) para imprimir. 

| Archivo | Título | Tipo | Qué hace | Tablas | Notas |
|---|---|---|---|---|---|
| `customer_list.php` | DM発行 | lista | Busca clientes, elige y abre la hoja | tb_customer, tb_koumoku L | |
| `aterna_a4_12.php` | (impresión) | pdf | Etiquetas Atena A4 x12 | tb_customer L | |
| `japanese.php` | — | fragmento | Funciones de texto japonés (`ereg`→`preg` ya migradas) | — | |

## 新着情報 — `digital_signage/`

- Qué hace: alta de noticias con imagen (tabla `tb_digital_signage`) para un panel de señalización. No hay enlace desde otros archivos PHP de system; confirmar si se usa (menú dinámico por `tb_base_system_*`). El resto de `digital_signage` está en category7 (otro grupo).

| Archivo | Título | Tipo | Qué hace | Tablas | Notas |
|---|---|---|---|---|---|
| `digital_signage_entry.php` | 新着情報管理 | lista/alta | Lista y formulario | tb_digital_signage L, tb_officialposition L | |
| `digital_signage_register.php` | 新着情報登録 | registro(POST) | INSERT/UPDATE con `hatuban` | tb_digital_signage E, hatuban E | |
| `inc/*`, `js/` | — | fragmento | Subida de imagen | — | |

## Motor compartido — `inc/`, `class/` y raíz

| Archivo | Tipo | Qué hace |
|---|---|---|
| `inc/ajax_get_template_titles.php`, `ajax_get_template_body.php` | ajax | Insertan plantilla en el cuerpo |
| `inc/ajax_search_kaiin.php`, `ajax_reigai_kaiin.php` | ajax | Buscan clientes y gestionan "例外会員" (`mailmag_master.振分条件`) |
| `inc/ajax_search_coupon.php` | ajax | Busca cupón para adjuntar (tb_mycoupon_master) |
| `inc/ajax_sendTestMail.php`, `ajax_testMailList.php` | ajax | Envía prueba; lista direcciones de prueba |
| `inc/ajax_mail_delete.php`, `ajax_change_status.php`, `ajax_get_shop_data.php` | ajax | Borrado/estado/datos de tienda |
| `inc/csv_download.php`, `csv_shousai_download.php`, `csv_download/csv_*.php` (mailmag_graph2, mailmag_shousai2, mail_sended_list) | csv | CSV de resultados. Usa un SWF Flash (`csv_download/sample.swf`, `.as`) |
| `inc/customer_list.php`, `customer_detail.php` | fragmento | Lista y ficha de cliente en popup (840 líneas) |
| `inc/mailWordsReplace.php`, `decomailTagReplace.php`, `regextest.php` | fragmento | Sustitución de palabras clave (`{ID}`, etc.) y HTML デコ |
| `inc/page_control.php`, `page_ctl.php`, `graph_sql.php`, `accordion_c3.php` | fragmento | Paginación, SQL de gráficos, menú acordeón |
| `class/cSendSchedule.php` | cron/CLI | Genera el crontab (ver siguiente sección) |
| `index.php` | lista | Portada: borra temporales `uploads/temp` de coupon/mail_mag/auto/follow con `find … -mtime +1 -exec rm` |
| `process-sortable.php` | ajax | Guarda orden por drag&drop |

---

## Envío programado y masivo (cómo funciona hoy)

### 1. Registro
`mail_*_send.php` → `inc/mail_send_frm.php` inserta una fila en **`mailmag_master`** con:
`店id`, `配信日時` (fecha-hora completa para envíos únicos; solo `HH:MM` para recurrentes), `タイトル`, `pc内容`, `type_flg`, `type_data_cd` (cupón/encuesta), `send_parameter`, `振分配信` (la cláusula `WHERE` SQL generada por el filtro de clientes; guardada como texto), `search_display`, `振分条件` (clientes de excepción), `pos_search`/`pos_tb_shop_cd`, `登録者`, `配信状況` (1=pendiente, 2=enviado) y contadores por tipo de dirección.
- Envío inmediato (`send_flg=1`): `exec(php mailsoushin.php {shop} {cd} {usuario})` en segundo plano.
- Programado: `cSendSchedule::addSchedule($cd)` → crea fila en **`send_schedule`** (`cd` por `hatuban`, `配信時間`, `mag_cd`, `flg=1`) y regenera el crontab. Avisa por correo interno de la reserva.

### 2. `cSendSchedule::createSchedule()` (427 líneas)
1. Marca como enviado (`配信状況='2'`) y borra de `send_schedule` lo no recurrente cuya hora ya pasó.
2. Recorre `send_schedule` ⨝ `mailmag_master` y arma **una línea de crontab por envío**, según `type_flg`:
   - `follow/followdeco/yoyaku/posyoyaku/remdeco/posremdeco`: cron diario a `HH:MM` (los días-offset se evalúan dentro del envío).
   - `auto/autodeco`: `send_parameter = 1<>meses<>días` → próxima fecha futura (32 = último día del mes; si ya pasó este año, el próximo año) en cron de día/mes; `2<>días_semana` → día de la semana.
   - resto (envío único): cron con día y mes de la fecha; el año no cabe en cron, así que `mail_sche_send.php` comprueba `配信時間 <= now` y, si no, sale.
3. Añade `getOtherSchedule()`: tareas **de otros módulos** en el mismo crontab:
   - Puntos: `sendPointShikkouAlertMail.php`, `sendPointShikkouMail.php`, `sendPointShikkouAlertMailMultiple.php` por alerta, `setRegisterPoint.php` (cada 5 min si `application_use_flg=1`), `setPaidMemberPoint.php` (10:00 diario).
   - Sellos: `sendStampShikkouAlertMail.php`, `sendStampShikkouMail.php`.
   - Rangos de cliente (category1): `setCustomerKingakuRank.php`, `setCustomerRaitenRank.php` a la hora de `admin_master`.
   - Push: `push/sendFollowPush.php` cada 5 min si el módulo `プッシュ通知` está activo en `tb_base_system_child`.
4. Escribe `mail_mag/cron_schedule/cronfile` y ejecuta `crontab <archivo>` (reemplaza **todo** el crontab del usuario) o `crontab -r` si no hay nada.
5. Tras cada envío, `mail_sche_send.php` espera 5 s y regenera el crontab para pasar al siguiente.

### 3. Envío masivo — `system/class/cMailsoushin.php::sendMailFromMailmagMaster` (1057 líneas)
1. Lee `mailmag_master`; aborta si `振分配信` vacío.
2. Obtiene destinatarios ejecutando `SELECT * FROM tb_customer {振分配信} [AND mailmaga_flg='1'] ORDER BY touroku_date DESC` (SQL guardado como texto: **riesgo de inyección y de esquema congelado**). Para `yoyaku/remdeco` ejecuta la SQL guardada contra la BD CRM o, si son `pos*`, contra la **BD del POS de la tienda** (`tb_shop.pos_domain`, `POS_DB_PASSWORD`). Para メルマガ con `pos_search`, consulta antes al POS y sustituye `{POS_RESULT}` por la lista de `tb_customer_cd`.
3. `UPDATE send_count+1, last_send_date`. Sin destinatarios: solo dispara el push espejo.
4. Cupón: si `type_data_cd` y no es anketo, calcula vencimiento (`limit_flg` 1=7 días, 2=30, 3=90, 4=N días, 5=fecha fija a las 23:59:59) y por cada envío OK crea `tb_mycoupon` (`hatuban` con `FOR UPDATE`).
5. Bucle secuencial: salta clientes sin `email1`; cuenta por `mailmaga_email_type` (パソコン, DoCoMo, AU, Softbank, その他); `replaceMessage` sustituye `{ID}` y demás etiquetas (nombre, puntos `tb_customer_point`, última visita `tb_raiten_rireki`, rangos, campos `tb_customer_sub`); añade pie y `addSimpleLogin` (enlace de mi página con login simple + contador de clics); envía texto (`send`, con imagen adjunta distinta para PC y móvil) o HTML (`sendDeco` vía `CrmDecomail`/Symfony Mailer).
6. Éxito: registra en `mailmag_sended_log` hasta N destinatarios (`admin_master.mailmag_sended_log = flag<>máximo`). Fallo: `tb_customer.error_send_cnt++`; con más de 3 errores `mailmaga_flg='9'` (baja automática).
7. Cierre: actualiza contadores y `配信状況='2'` (solo en no recurrentes) y envía correo-resumen "メール配信完了メール" a la tienda y copia a `admin_master.system_mail`; después `Push::exec(...,"mailmaga",cd)` envía el push espejo.
Sin límite de tasa, sin cola, sin reintentos, un solo proceso PHP CLI por envío (riesgo de timeout con listas grandes).

### 4. Seguimiento
`addSimpleLogin` mete en el correo un enlace con `tb_customer.easy_login`; los accesos a mi página se registran en `tb_mailmag_analyze`, base de `mailmag_analyze.php` y de los gráficos por perfil.

---

## Servicios viejos que usa y reemplazo propuesto

| Servicio viejo | Reemplazo en Laravel 13 |
|---|---|
| `crontab` reescrito por PHP (`cSendSchedule`) con `exec("crontab …")` | `routes/console.php` con `Schedule::command(...)->everyMinute()->withoutOverlapping()` + un único cron `schedule:run`. Sin `send_schedule`: se consulta `messages.scheduled_at <= now()` |
| `exec(php mailsoushin.php …)` para el envío inmediato | `SendMessageBatch::dispatch()` en la cola (lotes de 200) |
| `mb_send_mail` / `crm_send_mail` / Qdmail | `Mailable` (`PromotionMail`) por la cola; plantilla HTML sin CSS para móviles antiguos |
| `振分配信` = SQL guardada como texto | `messages.filters` (jsonb) reconstruido con `CustomerSearch`; nunca SQL en BD |
| `tb_customer.error_send_cnt`, `mailmaga_flg='9'` | `customers.bounce_count` / `mail_magazine_flg` (ya modelado) |
| `hatuban` (contador con `FOR UPDATE`) | `id` autoincremental/`uid` |
| Push por GET a `b.g-api.net` | Interfaz `PushSender` (FCM/APNs); `customer_devices` |
| TinyMCE 3 + conversión a "デコメール" | Editor moderno (TipTap/Quill) en un `Mailable` HTML; ya no hay "デコメール", todo es HTML |
| CSV con Flash (`sample.swf`) | `StreamedResponse` CSV |
| `ajaxSavePush.php` con `session_id()` como CSRF | Form Request + CSRF de Laravel |
| Subida a `uploads/{shop}` y `temp` + `find -mtime` | `Storage` + `schedule` para limpiar temporales |
| Gráficos PHP (`mailmag_graph2`) | Consultas agregadas + componente de gráfico |
| Dos motores (cMailsoushin y copia en decomail) | Un solo `MessageDispatcher` |

---

## Tablas de BD del grupo

| Tabla | Para qué | Submódulos que la usan | Notas (columnas clave) |
|---|---|---|---|
| `mailmag_master` | Cabecera de **cada envío** (メルマガ, deco, follow, auto, reminder, anketo, coupon) y de las reglas recurrentes | mail_mag, decomail, auto, follow, reminder, inc | `cd`, `店id`, `配信日時`, `配信時間`, `タイトル`, `pc内容`, `type_flg`, `type_data_cd`, `send_parameter`, `振分配信`, `search_display`, `振分条件`, `pos_search`, `pos_tb_shop_cd`, `配信状況`, `disp_flg`, `*配信数`, `send_count`, `last_send_date` (nombres de columna en japonés) |
| `send_schedule` | Cola del cron: una fila por envío pendiente | mail_mag, decomail, auto, follow, reminder | `cd`, `店id`, `配信時間`, `flg=1`, `mag_cd`. Se reconstruye entera: no hay que migrarla |
| `mailmag_sended_log` | Destinatarios de un envío (tope configurable) | mail_mag, inc | `mailmag_master_cd`, `tb_customer_cd`, `email` |
| `tb_mailmag_analyze` | Accesos a mi página desde un correo (clics) | mail_mag, decomail, push, inc | base de los gráficos por perfil/hora |
| `tb_mail_template`, `tb_mail_template_category` | Plantillas y categorías | mail_mag (todos) | `disp_flg=9` = borrado; orden en `tb_order_item` |
| `tb_test_mail_address` | Direcciones de prueba por tienda | mail_mag | |
| `push_master` | Un push emitido (inmediato o generado por una regla) | push, inc | `post_type` (1 mensaje, 2 con puntos), `post_point`, `point_expiration_date`, `wherestr`, `type_flg`, `push_follow_master_cd`, `version=2` |
| `push_follow_master` | Reglas push `follow`/`auto`/`scheduled` | push, auto, follow | `send_time`, `send_parameter`, `next_send_date`, `post_point`, `point_expiration_days`, `disp_flg` (1, 7 pausa, 9) |
| `push_notifications` | Entrega por cliente y estado de apertura | push, inc | `type`, `type_master_cd`, `tb_customer_cd`, `open_cnt` |
| `tb_digital_signage` | Noticias del panel | digital_signage | |
| `contents_master` | Ajustes clave/valor (p. ej. `push_follow.last_push_date`) y bloques push | push | `category`, `field`, `int_data`, `text_data` |
| `hatuban` | Contadores de numeración (`send_schedule`, `push_master`, `tb_mycoupon`, `tb_digital_signage`…) | todos | `koumoku`, `kazu` |
| `admin_master` | Configuración global (envío, push, puntos, `mail_send_filter`, `mailmag_sended_log`, `system_mail`, `push_api_host`, `application_use_flg`) | motor | fila `cd=1` |
| Lecturas de otros módulos | `tb_customer`, `tb_customer_sub`, `tb_customer_point`, `tb_raiten_rireki`, `tb_shop`, `tb_staff`, `tb_office`, `tb_search_form`/`tb_search_cate`/`tb_koumoku`, `tb_yoyaku*`, `tb_mycoupon(_master)`, `tb_c3_anketo*`, `tb_base_system*`, `tb_officialposition` | todos | Pertenecen a clientes, tiendas, staff, cupones, reservas |
| Tabla antigua | `kaiin_master`, `number_master` | `aterna_a4_12.php` y mail_mag viejos | Esquema obsoleto; no migrar |

---

## Propuesta Laravel 13

### Qué cubre ya CRMSystem (`public_html`)
Diseño en `Doc/database/TABLAS_MODULO_PROMOCIONES.md`; código bajo `app/Http/Controllers/Admin/Promotions`, `app/Services/Promotions`, `resources/views/admin/promotions`.

| Legacy | CRMSystem | Estado |
|---|---|---|
| メルマガ + デコメール + push, listas de programados/pasados | `Message` (+ `CustomerMessage`), enums `MessageChannel` (TextEmail=1, HtmlEmail=2, Push=3), `DeliveryStatus` (Draft, Scheduled, Sending, Sent, Cancelled), `MessageController` (`promotions.messages.*`, duplicar, cancelar) | Diseñado y con rutas |
| 発行条件選択 | `App\Services\Customers\CustomerSearch`, `messages.filters` jsonb | Reutiliza módulo 1 |
| follow / auto / reminder (8 variantes) | `MessageRule` + enum `RuleTrigger` (AfterVisit, AfterSignup, BeforeBirthday, VisitCycle, Anniversary, Dates, Weekdays, BeforeReservation), `RuleController` (`promotions.rules.*`) | Hecho; BeforeReservation espera la tabla `reservations` (módulo 9) |
| テンプレート管理 (+categorías) | `MessageTemplate`, enum `TemplateCategory`, `TemplateController` | Hecho |
| テストメールアドレス | `test_mail_addresses`, `TestAddressController` | Hecho |
| Envío masivo | Job `SendMessageBatch` (200 por lote) → `MessageDispatcher::sendBatch`, `Mail\PromotionMail` | Hecho |
| Cron | `promotions:send-scheduled` (cada minuto), `promotions:run-rules` (cada minuto, una vez al día por regla), `promotions:loyalty` (15 min) en `routes/console.php` | Hecho |
| Seguimiento | `/p/o/{uid}.gif`, `/p/n/{uid}`, `/p/c/{uid}` firmadas; `customer_message` (abierto, clic) | Diseñado |
| Rendimiento (クリックカウンタ) | `AnalyticsController` | Hecho |
| QR / base de メルマガ | `RegistrationController` | Hecho |
| Push | `PushSender` + `LogPushSender` (solo log) | **Pendiente** FCM/APNs |
| Rebotes | `customers.bounce_count`, `mail_magazine_flg` | Hecho |

### Lo que falta o hay que decidir
- **Push real**: implementar `PushSender` (FCM/APNs) con `customer_devices`; hoy el legacy depende de `b.g-api.net`.
- **Push con puntos** (`post_type=2`, `post_point`, `point_expiration_*`): comprobar que `Message`/`MessageRule` lo cubren (coordinar con `LoyaltyService` de puntos).
- **POS**: `pos_search` y `posyoyaku/posremdeco` consultan BD externas; sin equivalente (ver Riesgos).
- **Envío a destinatarios "例外会員"** (`振分条件`) y **configuración de log de destinatarios** (`admin_master.mailmag_sended_log`): `customer_message` ya guarda todo, no hace falta tope.
- **DM発行** (etiquetas A4 x12) y **新着情報/digital_signage**: sin equivalente. DM: PDF con DomPDF/Snappy a partir de `CustomerSearch`. 新着情報: modelo `News` + `Storage` (S).
- **Imágenes adjuntas** (distinta para PC/móvil): simplificar a una imagen incrustada.
- Migración de datos: no copiar `send_schedule`; convertir `mailmag_master` recurrentes (`follow/auto/reminder`) a `message_rules` y los envíos pasados a `messages` (sin `振分配信`).

### Rutas, controladores y jobs sugeridos
Prefijo existente `admin/promotions`. Añadir solo `news` (`Promotions\NewsController`) y `dm` (`Promotions\DirectMailController`) si el cliente los usa. Policies/Gates por tienda (`rank=master` ve todas, el resto solo su `shop_cd`, como `push_follow_list.php`).

### Orden recomendado
1. Plantillas + direcciones de prueba (ya).
2. Envío de メルマガ/デコメール con cola y `CustomerSearch` (ya; falta verificar etiquetas `{ID}`… y límites de envío con listas grandes).
3. Automatizaciones follow/auto (ya) y seguimiento de clics.
4. `PushSender` real (depende de la app de マイページ, grupo 06/mi página).
5. Reminder con reservas (depende de módulo 9 reservas).
6. Importación del histórico (`mailmag_master`, `push_*`).
7. DM y 新着情報 (baja prioridad).

Dependencias con otros grupos: clientes (`CustomerSearch`, campos), tiendas (`pos_domain`), staff (direcciones de prueba), puntos/sellos/cupones (push con puntos, cupones adjuntos), reservas (reminder), mi página/app (push, `easy_login`).

---

## Riesgos y dudas

1. **Crontab**: el legacy sobrescribe todo el crontab del usuario y mezcla tareas de puntos, sellos, rangos y push. En Docker no existe `crontab` (PENDING.md 1.1; respondido 2026-11-18: se usará cron real). En Laravel: un solo `schedule:run`; los comandos de puntos/sellos/rangos pertenecen a otros grupos pero deben registrarse en el mismo scheduler.
2. **SQL guardada como texto** (`振分配信`, `pos_search`): inyección y deuda de esquema; el histórico no se puede reinterpretar. Decidir si se migran los envíos pasados solo como resumen.
3. **Push** depende de `b.g-api.net` por HTTP plano sin TLS (INTEGRATIONS.md); `pos_domain` de cada tienda y `POS_DB_PASSWORD` con BD remotas (`crm2019`) sin definir equivalente. En desarrollo el servidor externo rechazó conexiones (MISSING-TABLES: beacon).
4. **Envío masivo secuencial sin cola** y con `exec` de PHP CLI; riesgo de timeouts, doble envío y bloqueo de la `send_schedule` (`FOR UPDATE`). Definir límites del proveedor SMTP/SES.
5. **Pantallas rotas/candidatas a borrar** (BROKEN-FILES / PENDING): `mail_mag/mail_schedule.php` (fatal), `aterna_a4_12.php` (usa `kaiin_master`), `qr_img.php` sin parámetro `d`, 3 copias de Qdmail sin uso, `hp/mailsyori/mm_sche.php`/`mm_send.php` (hp, solo mencionado), `dm/japanese.php` (funciones eliminadas en PHP 7 ya convertidas). Confirmar con Antony antes de borrar (regla de no borrar legacy sin permiso).
6. **Dependencia de `hp/`**: los QR y URLs de alta (`user/mm_add.php`, `hp/uploads/c3/`) apuntan a `hp/` (obsoleta). Hay que rehacer el alta pública a la lista (ya en `PromotionPublicController`/`RegistrationController`).
7. **Datos con campos en japonés**: `mailmag_master` usa columnas como `タイトル`, `pc内容`, `配信日時` (CamelCase japonés, PostgreSQL con comillas); un mapeo de importación es obligatorio.
8. **Móviles feature-phone** (Docomo/AU/Softbank, `デコメール`): conversión HTML y contadores por operador son obsoletos; decidir si se mantienen las estadísticas por tipo de dirección.
9. **Menú dinámico**: `tb_base_system_child` activa/desactiva funciones (`プッシュ通知`, `購買履歴`); falta decidir equivalente (flags de tenant/Gates). `digital_signage` de category3 no tiene enlaces: confirmar uso.
10. Decisiones pendientes: ¿el correo de resumen "メール配信完了メール" y la copia a `system_mail` se mantienen? ¿se conserva `easy_login` (login simple en enlaces del correo)? Su reemplazo es el enlace firmado `/p/c/{uid}`.
