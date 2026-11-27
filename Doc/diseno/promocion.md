# Diseño — S3 Promoción (販売促進管理)

> Sesión S3 · rama `l13/promocion` · 2026-11-26 · fuentes: `Doc/analisis-legacy/03a-promocion-mensajeria.md`, `03b-promocion-fidelizacion.md`, esquema real del legacy (`crm2-php8/db/crm2019.backup`, `pg_restore -s`) y el código actual de `public_html` (`Admin/Promotions`, migraciones `2026_10_06_*`).
> Documento sin código. Lo revisa S0 antes de implementar.

## 0. Contrato que adopta el módulo

| Qué | Valor |
|---|---|
| `<modulo>` | `promocion` |
| Rutas | `routes/modulos/promocion.php`, dentro del grupo `admin` → nombres `admin.promocion.*`, URL `/{ADMINS_SUBDIR}/promocion/...` |
| Controladores | `app/Http/Controllers/Admin/Promotions/` (carpeta existente, PascalCase en inglés; no se renombra) |
| Form Requests | `app/Http/Requests/Promotions/` |
| Servicios | `app/Services/Promotions/` (existente) |
| Vistas | `resources/views/admin/promocion/` (se mueven las de `admin/promotions/`) |
| Textos | `lang/ja/promocion.php` (hoy hay textos en español sueltos en controladores, p. ej. `VisitHistoryController::PERIODS`: se pasan a `lang/ja`) |
| Menú | `config/menu/promocion.php` |
| Scheduler | `app/Providers/PromocionServiceProvider.php` (sale de `routes/console.php`) |
| Comandos / jobs / mail / enums nuevos | `app/Console/Commands/Promotions/`, `app/Jobs/Promotions/`, `app/Mail/Promotions/`, `app/Enums/Promotions/` (los existentes se quedan donde están) |
| Pruebas | `tests/Feature/Promotions/`, `tests/Unit/Promotions/` (existentes) |
| Rutas públicas sin sesión | `/p/...` (`PromotionPublicController`, ya existe): enlaces de seguimiento, cupón y encuesta. Se mueven a `routes/publico/promocion.php` (nombres `publico.promocion.*`, CONVENCIONES §2); el POST de la encuesta pública va firmado (`signed`) porque esas rutas no llevan CSRF |

**Estado de la base existente.** `Admin/Promotions` es un rediseño previo (no una copia del legacy): modelos, servicios y pruebas sirven, pero **ninguna pantalla cumple todavía la paridad** según CONVENCIONES §5: faltan campos/filtros/botones del legacy, los textos no están en `lang/ja`, la validación está en el controlador (no en Form Requests), no hay Policies y las rutas no están en `admin.promocion.*`. Por eso ninguna fila de pantalla está en `hecho`; donde ya hay base se indica en la columna "Base". Lo que sí cumple hoy es la lógica de motor (envío por cola, personalización, rebotes, reglas follow/auto, vencimiento de puntos y sellos, canje), cubierta por `tests/Feature/Promotions/PromotionLogicTest.php`.

Leyenda de estado: `pendiente` · `hecho` · `descartado: motivo` · `S2` (pantalla que se propone pasar a S2, ver Pedidos).

---

## 1. Tabla de paridad

Abreviaturas: `MC` = `MessageController`, `RC` = `RuleController`, `TC` = `TemplateController`, `CC` = `CouponController`, `SC` = `SurveyController`. Rutas sin el prefijo `admin.promocion.`.

### 1.1 Mensajería (03a)

#### お知らせ配信管理 — `mail_mag/`

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas / cambios | Base | Estado |
|---|---|---|---|---|---|---|
| `mail_customer_select.php` | 発行条件選択 (メール) | `messages.create` (paso 1, `?channel=1`) | `MC@create` | `messages.search_summary` | `CustomerSearch`, `MC@create/audience` | pendiente |
| `mail_new.php` | 新規メール配信 | `messages.create` (paso 2) | `MC@create` | `messages.image_path`, `messages.admin_id` | `MC@create` | pendiente |
| `mail_new_confirm.php` | (確認) | `messages.confirm` (POST) | `MC@confirm` | — | — | pendiente |
| `mail_new_send.php` | (登録) | `messages.store` | `MC@store` | — | `MC@store` + `SendMessageBatch` | pendiente |
| `mail_future_list.php` | 予定配信お知らせ一覧 | `messages.index?tab=scheduled` | `MC@index` | — | `MC@index` | pendiente |
| `mail_future.php` | 予定配信メール編集 | `messages.edit` | `MC@edit` | — | `MC@edit` | pendiente |
| `mail_future_test.php` | (編集, variante) | — | — | — | — | descartado: duplicado de `mail_future.php` |
| `mail_future_confirm.php` | (確認) | `messages.confirm` | `MC@confirm` | — | — | pendiente |
| `mail_future_send.php` | (登録) | `messages.update` | `MC@update` | — | `MC@update` | pendiente |
| `mail_kaijo.php` | 予約配信メール 配信解除 | `messages.cancel` | `MC@cancel` | — | `MC@cancel` | pendiente |
| `mail_past.php` | 過去配信お知らせ一覧 | `messages.index?tab=sent` | `MC@index` | — | `MC@index` | pendiente |
| `mail_past_shousai.php` | 過去配信詳細 | `messages.show` | `MC@show` | — | `MC@show` | pendiente |
| `mail_sended_list.php` (+ CSV) | 配信先一覧 | `messages.recipients`, `messages.recipients.export` | `MC@recipients`, `MC@exportRecipients` | — (`customer_message`) | — | pendiente |
| `mailmag_analyze.php` | クリックカウンタ | `analytics.clicks?channel=1` | `AnalyticsController@clicks` | `message_clicks` | `AnalyticsController@index` | pendiente |
| `mail_template.php` | テンプレート管理 | `templates.index` | `TC@index` | `message_template_categories` | `TC@index` | pendiente |
| `mail_template_entry.php`, `mail_template_register.php` | テンプレート登録 | `templates.create/store/edit/update/destroy` | `TC@create…destroy` | `message_templates.message_template_category_id` | `TC` | pendiente |
| `mail_template_category.php`, `_category_register.php` | テンプレートカテゴリ管理 | `template-categories.index/store/update/destroy`, `template-categories.sort` | `TemplateCategoryController@*` | `message_template_categories` | — | pendiente |
| `ajax_mail_template.php`, `ajax_mail_template_category.php` | (AJAX) | `templates.json`, `template-categories.*` (JSON) | `TC@json`, `TemplateCategoryController` | — | `TC@json` | pendiente |
| `test_mail_address.php`, `test_mail_address_register.php` | テストお知らせアドレス管理 | `test-addresses.index/store/update/destroy` | `TestAddressController@*` | — | `TestAddressController` (sin edición) | pendiente |
| `ajax_test_mail_address_new.php`, `ajax_test_mail_address_staff.php` | (AJAX) | `test-addresses.store`, `test-addresses.staff` (JSON) | `TestAddressController@store`, `@staff` | — | — | pendiente (staff depende de S1) |
| `base.php`, `base_mailmaga.php`, `base_mycolle.php`, `base_point.php` | メールマガジン基本情報 / マイコレ登録用QR / メルマガ用QR | `registration.index?type=mail|mycolle|point` | `RegistrationController@index` | — | `RegistrationController@index` | pendiente |
| `qr_img.php`, `qr_print_pdf.php` | QR / QR印刷 | `registration.qr` (PNG), `registration.print` (PDF) | `RegistrationController@qr`, `@print` | — (Storage) | `bacon/bacon-qr-code` | pendiente |
| `form_config.php`, `form_config_register.php` | 表示・検索・CSV項目設定 | — | — | — | — | descartado: es configuración de clientes (category1), la cubre S2 |
| `mag_customer_config.php` | メール配信条件選択 | — | — | — | — | descartado: resto solo referenciado desde category1, sin menú |
| `mailsoushin.php` | (CLI envío inmediato) | — (job) | `SendMessageBatch` → `MessageDispatcher` | — | hecho (motor) | descartado: no es vista; reemplazado por la cola |
| `mail_sche_send.php` | (CLI cron) | — (comando) | `promocion:send-scheduled` | — | `promotions:send-scheduled` | descartado: no es vista; reemplazado por el Scheduler |
| `send_schedule.php` | (include viejo) | — | — | — | — | descartado: versión antigua sin uso |
| `inc/disp_pic.php`, `inc/upload.php`, `inc/furiwake_disp.php` | (fragmentos) | — | `MC@store` (subida a `Storage`) | — | — | pendiente (dentro de `MC`) |
| `mail_new1/2`, `mail_future1/2/3`, `mail_past1`, `mail_list`, `mail_detail`, `mail_revise`, `mail_schedule`, `send`, `mm_add*`, `base2`, `aterna_a4_12` (mail_mag) | (varios) | — | — | — | — | descartado: muertos/rotos según el análisis |

#### デコメール管理 — `decomail/`

Mismas pantallas que メルマガ con `channel=2` (email HTML). El editor TinyMCE 3 se cambia por uno actual (ver Pedidos).

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Estado |
|---|---|---|---|---|---|
| `mail_customer_select.php` | 新規デコメール配信 (発行条件選択) | `messages.create?channel=2` | `MC@create` | — | pendiente |
| `mail_new.php`, `mail_new_confirm.php`, `mail_new_send.php` | 新規デコメール | `messages.create/confirm/store` | `MC@create/confirm/store` | — | pendiente |
| `mail_future_list.php`, `mail_future.php`, `mail_future_confirm.php`, `mail_future_send.php` | 予定配信デコメール一覧 | `messages.index?channel=2&tab=scheduled`, `edit/confirm/update` | `MC@*` | — | pendiente |
| `mail_past.php`, `mail_past_shousai.php`, `mail_sended_list.php` | 過去配信メール一覧 | `messages.index?channel=2&tab=sent`, `show`, `recipients` | `MC@*` | — | pendiente |
| `mailmag_analyze.php` | クリックカウンタ | `analytics.clicks?channel=2` | `AnalyticsController@clicks` | `message_clicks` | pendiente |
| `mail_kaijo.php` | 配信解除 | `messages.cancel` | `MC@cancel` | — | pendiente |
| `mailsoushin.php`, `mail_sche_send.php` | (CLI) | — | — | — | descartado: copia del motor; un solo `MessageDispatcher` |
| `inc/qdmail.php` | — | — | — | — | descartado: librería sin uso |
| `js/tiny_mce/` | — | — | — | — | descartado: se reemplaza por editor actual |

#### 自動お知らせ管理 — `auto/`

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Base | Estado |
|---|---|---|---|---|---|---|
| `auto_new.php` | 自動お知らせ新規作成 | `rules.create?group=auto` (`trigger` 6 meses+días / 7 semana; `channel` 1/2/3) | `RC@create` | `message_rules.point_amount`, `point_expiry_days`, `blocks` (push) | `RC@create` | pendiente |
| `auto_new_confirm.php` | (確認) | `rules.confirm` (POST) | `RC@confirm` | — | — | pendiente |
| `auto_mail_customer_select.php`, `auto_mail_new.php`, `auto_mail_new_confirm.php`, `auto_mail_new_send.php` | 自動 / 配信条件選択 | `rules.create` (pasos filtros/redacción), `rules.confirm`, `rules.store` | `RC@create/confirm/store` | — | `RC` | pendiente |
| `auto_mail_future_list.php` | 自動お知らせ設定一覧 | `rules.index?group=auto` | `RC@index` | — | `RC@index` | pendiente |
| `auto_mail_future.php`, `_confirm.php`, `_send.php` | (編集) | `rules.edit/confirm/update` | `RC@edit/confirm/update` | — | `RC` | pendiente |
| `mail_kaijo.php` | 配信解除 | `rules.destroy` (停止 = `rules.toggle`) | `RC@destroy`, `RC@toggle` | — | `RC` | pendiente |
| `inc/disp_pic.php`, `upload.php` | — | — | `RC@store` (Storage) | — | — | pendiente (dentro de `RC`) |

#### フォローお知らせ管理 — `follow/`

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Base | Estado |
|---|---|---|---|---|---|---|
| `follow_new.php` | フォローお知らせ新規作成 | `rules.create?group=follow` (`trigger` 1 来店後 / 2 登録後 / 3 誕生日前) | `RC@create` | — | `RC@create` | pendiente |
| `follow_new_confirm.php` | (確認: clientes que encajan hoy) | `rules.confirm` | `RC@confirm` (usa `RuleRunner::audience`) | — | `RuleRunner` | pendiente |
| `follow_mail_*` (select, new, confirm, send) | フォロー | `rules.create/confirm/store` | `RC@*` | — | `RC` | pendiente |
| `follow_mail_future_list.php` | フォローお知らせ設定一覧 | `rules.index?group=follow` | `RC@index` | — | `RC@index` | pendiente |
| `mail_future_list.php` (follow) | (variante antigua) | — | — | — | — | descartado: duplicado |
| `follow_mail_future*.php` (3) | (編集) | `rules.edit/confirm/update` | `RC@*` | — | `RC` | pendiente |
| `mail_kaijo.php`, `inc/*` | 配信解除 | `rules.destroy` | `RC@destroy` | — | `RC` | pendiente |

#### リマインダお知らせ管理 — `reminder/`

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Estado |
|---|---|---|---|---|---|
| `reminder_new.php` | リマインダーメール新規作成 | `rules.create?group=reminder` (`trigger` 8) | `RC@create` | — | pendiente (modelo `Reservation` de S2; stub mientras tanto) |
| `reminder_new_confirm.php` | (確認: reservas afectadas) | `rules.confirm` | `RC@confirm` | — | pendiente (S2 `Reservation`) |
| `reminder_mail_new*.php` (3), `reminder_mail_future*.php` (4), `mail_kaijo.php` | リマインダお知らせ設定一覧 / 編集 / 解除 | `rules.index?group=reminder`, `create/confirm/store/edit/update/destroy` | `RC@*` | — | pendiente |
| Variantes `posyoyaku`/`posremdeco` | (reserva en POS de tienda) | — | `PosGateway` (interfaz sin implementación) | — | descartado: POS fuera de alcance |

#### プッシュ通知 — `push/`

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas / cambios | Estado |
|---|---|---|---|---|---|
| `push_customer_select.php` | プッシュ通知 / 発行条件選択 | `messages.create?channel=3` | `MC@create` | — | pendiente |
| `push_new.php` + `push_new.js` | プッシュ通知新規作成 | `messages.create?channel=3` (bloques imagen+enlace, puntos, vencimiento, prueba a un socio) | `MC@create` | `messages.blocks`, `messages.point_amount`, `messages.point_expires_at`, `message_rules.point_expiry_days` | pendiente |
| `ajaxSavePush.php` | (AJAX guardar) | `messages.store` / `rules.store` | `MC@store`, `RC@store` | — | pendiente |
| `ajaxChangeStatus.php` | (AJAX pausar/anular) | `rules.toggle`, `rules.destroy`, `messages.cancel` | `RC@toggle/destroy`, `MC@cancel` | — | pendiente |
| `ajaxSearchMember.php` | (AJAX buscar socio de prueba) | `messages.test-customer` (JSON) | `MC@searchTestCustomer` | — | pendiente |
| `push_complete.php` | プッシュ通知 (完了) | redirección a `messages.show` con aviso | `MC@store` | — | pendiente |
| `push_follow_list.php?type_flg=scheduled` | 予定通知設定一覧 | `messages.index?channel=3&tab=scheduled` | `MC@index` | — | pendiente |
| `push_follow_list.php?type_flg=follow` | フォロー通知設定一覧 | `rules.index?channel=3&group=follow` | `RC@index` | — | pendiente |
| `push_follow_list.php?type_flg=auto` | 自動通知設定一覧 | `rules.index?channel=3&group=auto` | `RC@index` | — | pendiente |
| `push_past.php` | 過去配信内容一覧 | `messages.index?channel=3&tab=sent` | `MC@index` | — | pendiente |
| `push_past_shousai.php` | 過去配信詳細 (entregados, abiertos, perfil) | `messages.show` | `MC@show` | `customer_message.open_count` | pendiente |
| `push_history.php`, `push_history_detail.php` | プッシュ履歴 | `push-history.index`, `push-history.show` | `PushHistoryController@index/show` | — | pendiente |
| `sendFollowPush.php` | (cron 5 min) | — | `promocion:run-rules` | — | descartado: no es vista; lo cubre `RuleRunner` |

#### DM発行 — `dm/`

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Estado |
|---|---|---|---|---|---|
| `customer_list.php` | DM発行 | `dm.index` | `DirectMailController@index` | — | pendiente |
| `aterna_a4_12.php` | (宛名ラベル A4×12) | `dm.labels` (PDF) | `DirectMailController@labels` | — | pendiente (paquete PDF de S1) |
| `japanese.php` | — | — | — | — | descartado: utilidades de texto, no vista |

#### 新着情報 — `digital_signage/` (category3)

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Estado |
|---|---|---|---|---|---|
| `digital_signage_entry.php` | 新着情報管理 | `news.index`, `news.create`, `news.edit` | `NewsController@index/create/edit` | `signage_news` | pendiente (baja prioridad; uso sin confirmar) |
| `digital_signage_register.php` | 新着情報登録 | `news.store/update/destroy` | `NewsController@store/update/destroy` | `signage_news` | pendiente |

#### Motor compartido — `inc/`, `class/`, raíz

| Legacy | Tipo | Ruta Laravel | Controlador@método / servicio | Estado |
|---|---|---|---|---|
| `inc/ajax_get_template_titles.php`, `ajax_get_template_body.php` | ajax | `templates.json` | `TC@json` | pendiente |
| `inc/ajax_search_kaiin.php`, `ajax_reigai_kaiin.php` | ajax | `messages.audience`, `messages.excluded` | `MC@audience`, `MC@excluded` (`messages.excluded_customer_ids`) | pendiente |
| `inc/ajax_search_coupon.php` | ajax | `coupons.search` (JSON) | `CC@search` | pendiente |
| `inc/ajax_sendTestMail.php`, `ajax_testMailList.php` | ajax | `messages.test` | `MC@sendTest` | pendiente |
| `inc/ajax_mail_delete.php`, `ajax_change_status.php`, `ajax_get_shop_data.php` | ajax | `messages.destroy`, `messages.cancel`, `shops` vía `ModuleController::shops()` | `MC@destroy/cancel` | pendiente |
| `inc/csv_download.php`, `csv_shousai_download.php`, `csv_download/csv_*.php` (+ SWF) | csv | `messages.export`, `messages.recipients.export`, `analytics.clicks.export` | `StreamedResponse` | pendiente |
| `inc/customer_list.php`, `inc/customer_detail.php` | popup | enlace a `admin.clientes.show` (S2) | — | descartado: se usa la ficha de cliente de S2 |
| `inc/mailWordsReplace.php`, `decomailTagReplace.php`, `regextest.php` | fragmento | — | `MessageRenderer` (etiquetas `{ID}`…); sin conversión デコメール | pendiente (verificar todas las etiquetas) |
| `inc/page_control.php`, `page_ctl.php`, `graph_sql.php`, `accordion_c3.php` | fragmento | — | paginador de Laravel, consultas agregadas, menú | descartado: no son vistas |
| `inc/mail_customer_select_frm.php`, `mail_frm.php`, `mail_confirm_frm.php`, `mail_send_frm.php`, `mail_future_frm.php` (+ `_confirm_frm`, `_send_frm`), `mail_future_list_frm.php`, `mailmag_list.php`, `mailmag_shousai(2).php`, `mailmag_sended_list.php`, `mailmag_graph(2).php`, `push_list.php`, `push_shousai.php`; `/system/inc/ajax_picupload.php` (subida de imagen push); envoltorios `anketo_mail_*`, `anketo_reply_*`, `mycoupon_mail_*` | fragmentos compartidos | las vistas que los usan (filas de arriba) | partials Blade de `MC`/`RC`/`AnalyticsController`; subida con `Storage` en `MC@store` | pendiente (dentro de esas vistas) |
| `class/cSendSchedule.php` | cron | — | `PromocionServiceProvider` (Scheduler) | pendiente |
| `index.php` | portada | `index` | `DashboardController@index` (+ limpieza de temporales por Scheduler) | pendiente |
| `process-sortable.php` | ajax | `template-categories.sort`, `templates.sort` | `TemplateCategoryController@sort`, `TC@sort` | pendiente |

### 1.2 Fidelización (03b)

#### アンケート管理 — `anketo/` → `surveys` con `type = 1` (encuesta de envío)

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas / cambios | Base | Estado |
|---|---|---|---|---|---|---|
| `anketo_cate_list.php` | アンケート一覧 | `surveys.index?type=1` | `SC@index` | `surveys.type` | `SC@index` | pendiente |
| `anketo_cate_list.php?which=sended` | 過去アンケート配信一覧 | `messages.index?tab=sent&survey=1` | `MC@index` | — | — | pendiente |
| `anketo_cate_new.php`, `_frm`, `_errchk`, `_register` | アンケート新規登録 | `surveys.create/store` | `SC@create/store` (`SurveyRequest`) | `surveys.admin_comment` | `SC` | pendiente |
| `anketo_cate_update.php` | アンケート修正 | `surveys.edit/update` | `SC@edit/update` | — | `SC` | pendiente |
| `anketo_cate_action.php` | (表示切替・並び替え・削除) | `surveys.bulk` | `SC@bulk` | `surveys.sort` | — | pendiente |
| `anketo_cate_detail.php` | アンケート詳細 | `surveys.show` | `SC@show` | — | `SC@show` | pendiente |
| `anketo_form_list.php` | 項目一覧 | `surveys.questions.index` | `SurveyQuestionController@index` | — | — | pendiente |
| `anketo_form_new.php`, `anketo_form_update.php`, `_frm`, `_errchk`, `_register` | 項目新規 / 修正 | `surveys.questions.create/store/edit/update`, `surveys.questions.move` | `SurveyQuestionController@*` | `survey_questions.unit`, `comment`, `size` | (hoy dentro de `SC@store`) | pendiente |
| `anketo_form_delete.php` | (削除) | `surveys.questions.destroy` | `SurveyQuestionController@destroy` | — | — | pendiente |
| `anketo_form_detail.php` | 項目詳細 | `surveys.questions.show` | `SurveyQuestionController@show` | — | — | pendiente |
| `anketo_form_issue.php` | アンケート発行 (発行条件選択) | `messages.create?survey={id}` | `MC@create` | — | — | pendiente |
| `anketo_mail.php`, `anketo_mail_confirm.php`, `anketo_mail_send.php` + envoltorios `anketo_mail_*` | 新規メール配信 (encuesta + cupón) | `messages.create/confirm/store` con `survey_id`, `coupon_id` | `MC@*` | — | `messages.survey_id` | pendiente |
| `ajax_search_coupon.php` | (AJAX) | `coupons.search` | `CC@search` | — | — | pendiente |
| `anketo_test.php`, `anketo_test_register.php` | アンケートテスト | `surveys.test`, `surveys.test.store` | `SC@test`, `SC@storeTest` | `survey_responses.test_flg` | — | pendiente |
| `anketo_answer_analyze.php` | 回答解析 | `surveys.analysis` | `SurveyResponseController@analysis` | — | — | pendiente |
| `anketo_answer_shousai.php` | 回答詳細 (por pregunta) | `surveys.analysis.question` | `SurveyResponseController@question` | — | — | pendiente |
| `csv_answer_download.php` | 回答CSV | `surveys.export` | `SurveyResponseController@export` (StreamedResponse, SJIS-win) | — | — | pendiente |
| `anketo_analyze.php` | クリックカウンタ | `analytics.clicks?survey={id}` | `AnalyticsController@clicks` | `message_clicks` | — | pendiente |
| `inc/page.php`, css, colorbox, `cron_schedule/cronfile` | fragmento | — | — | — | — | descartado: no son vistas |

#### お客様アンケート管理 — `anketo2/` → `surveys` con `type = 2`

Las vistas idénticas en función a `anketo/` usan las mismas rutas con `?type=2`. Solo se listan las propias.

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Estado |
|---|---|---|---|---|---|
| Vistas comunes (`anketo_cate_*`, `anketo_form_*`, `anketo_mail*`, `anketo_test*`, `anketo_answer_shousai`, `csv_answer_download`, `anketo_analyze`, `ajax_search_coupon`) | お客様アンケート新規登録 / 修正 / 項目… | las de `anketo/` con `type=2` | `SC`, `SurveyQuestionController`, `SurveyResponseController` | — | pendiente |
| `anketo_cate_list.php` (con casilla 選択) | お客様アンケート一覧 | `surveys.index?type=2` | `SC@index` | `surveys.app_flg` | pendiente |
| `ajax_set_anketo.php` | (AJAX encuesta activa en la app) | `surveys.app` (POST) | `SC@setApp` | `surveys.app_flg` (índice único parcial) | pendiente |
| `anketo_answer_list.php` | お客様アンケート回答一覧 | `surveys.responses.index` | `SurveyResponseController@index` | — | pendiente |
| `anketo_answer_detail.php` | 回答詳細 (por cliente) | `surveys.responses.show` | `SurveyResponseController@show` | — | pendiente |
| `anketo_reply_customer_select.php`, `anketo_reply_new.php`, `_confirm.php`, `_send.php` | 返信メール | `messages.create?reply_survey={id}` | `MC@create/confirm/store` | `messages.survey_reply_flg` | pendiente |
| `anketo_answer_analyze.php` | 回答解析 | `surveys.analysis` | `SurveyResponseController@analysis` | — | pendiente (corrige el bug de nombres `anketo`/`anketo2`) |

#### クーポン — `coupon/` (cupón-imagen antiguo)

| Legacy | Título (ja) | Estado |
|---|---|---|
| `coupon_list.php` | クーポン画像一覧 | descartado: sin enlace en el menú, reemplazado por マイクーポン |
| `coupon_new.php` + `coupon_newup.php` | クーポン新規追加 | descartado: idem |
| `coupon_revise.php` + `coupon_reviseup.php` | クーポン修正 | descartado: idem |
| `coupon_detail.php` | クーポン画像詳細 | descartado: idem |
| `coupon_deleteup.php` | クーポン画像削除 | descartado: idem |
| `coupon_send.php` | クーポンを配信する | descartado: idem |
| `coupon_bg_list.php`, `coupon_bg_new(up).php`, `coupon_bg_revise(up).php`, `coupon_bg_detail.php`, `coupon_bg_deleteup.php` | クーポン背景画像 一覧/新規/修正/詳細/削除 | descartado: idem |
| `pit_coupon.php`, `pit_coupon1.php`, `pit_coupon2.php` | クーポン画像 | descartado: rotos (`shop_master`), sin enlaces |

#### マイクーポン管理 — `mycoupon/`

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas / cambios | Base | Estado |
|---|---|---|---|---|---|---|
| `mycoupon_new.php`, `mycoupon_update.php`, `_frm`, `_errchk` | クーポン新規登録 / 修正 | `coupons.create/edit` | `CC@create/edit` (`CouponRequest`) | `coupons.attention` → ya `notes`; enum `CouponUsage` +7 来店時印刷, +8 ガチャ | `CC` | pendiente |
| `mycoupon_register.php` | (登録) | `coupons.store/update` | `CC@store/update` | — | `CC` | pendiente |
| `mycoupon_list.php` | クーポン一覧 | `coupons.index` | `CC@index` | — | `CC@index` | pendiente |
| `mycoupon_detail.php` | クーポン詳細 | `coupons.show` | `CC@show` | — | `CC@show` | pendiente |
| `mycoupon_delete.php` | (削除) | `coupons.destroy` | `CC@destroy` | — | `CC@destroy` | pendiente |
| `mycoupon_issue.php` | クーポン発行 (発行条件選択) | `coupons.issue.create` | `CouponIssueController@create` | `coupon_issues` | — | pendiente |
| `mycoupon_issue_detail.php` | 発行内容確認 | `coupons.issue.confirm` | `CouponIssueController@confirm` | — | — | pendiente |
| `customer_list.php` | 顧客一覧 (popup) | `coupons.issue.customers` | `CouponIssueController@customers` | — | — | pendiente |
| `mycoupon_hakkou.php` | クーポン発行 (実行) | `coupons.issue.store` | `CouponIssueController@store` → `IssueCouponsJob` | `coupon_issues` | `CC@issue` (uno a uno) | pendiente |
| `mycoupon_create.php`, `mycoupon_complete.php` | クーポン発行完了 | `coupons.issue.show` | `CouponIssueController@show` | — | — | pendiente |
| `mycoupon_rireki.php` | クーポン履歴 | `coupons.history` | `CouponStatsController@history` | — | — | pendiente |
| `mycoupon_ranking.php` | 使用ランキング | `coupons.ranking` | `CouponStatsController@ranking` | — | — | pendiente |
| `mycoupon_stats.php` | クーポン利用数確認 | `coupons.stats` | `CouponStatsController@index` | — | `CC@stats` | pendiente |
| `mycoupon_stats_detail.php` | クーポン利用詳細 | `coupons.stats.show` | `CouponStatsController@show` | — | — | pendiente |
| `mycoupon_analyze.php` | クリックカウンタ | `analytics.clicks?coupon={id}` | `AnalyticsController@clicks` | `message_clicks` | — | pendiente |
| `mycoupon_mail*.php` (7) | 新規メール配信 (cupón) | `messages.create?coupon={id}`, `confirm`, `store` | `MC@*` | — | `messages.coupon_id` | pendiente |
| `mail_past.php`, `mail_past_shousai.php`, `mail_sended_list.php` | 過去クーポン配信一覧 / 詳細 | `messages.index?tab=sent&coupon=1`, `show`, `recipients` | `MC@*` | — | — | pendiente |
| `inc/page_control.php`, `js/user.js`, `cron_schedule/cronfile` | fragmento | — | — | — | — | descartado: no son vistas |

#### ポイント管理 — `point/` + `class/Point.php`

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas / cambios | Base | Estado |
|---|---|---|---|---|---|---|
| `point_config.php` (8 formularios) | ポイント仕様管理 | `points.edit`, `points.update` | `PointController@edit/update` (`PointSettingRequest`) | `point_settings` + columnas (§2.3), `customer_group_point_rules` + columnas | `PointController` | pendiente |
| `ajax_point_yukoukigen_kakunin.php` | (AJAX afectados por caducidad) | `points.expiry-preview` | `PointController@expiryPreview` | — | — | pendiente |
| `point_rules_list.php` | ポイントルール一覧 | `point-rules.index` | `PointRuleController@index` | `point_bonus_rules` | — | pendiente |
| `point_rules_new.php`, `point_rules_update.php`, `_frm`, `_errchk`, `_register` | ポイントルール新規 / 修正 | `point-rules.create/store/edit/update`, `point-rules.toggle` | `PointRuleController@*` (`PointBonusRuleRequest`) | `point_bonus_rules` | — | pendiente |
| `point_rules_delete.php` | (削除) | `point-rules.destroy` | `PointRuleController@destroy` (SoftDeletes) | — | — | pendiente |
| `point_aggregate.php`, `ajax_point_aggregate.php` | ポイント集計 | `points.aggregate`, `points.aggregate.data` (JSON) | `PointAggregateController@index/data` | — | — | pendiente |
| `csv_grant_points.php` | ポイント一括付与 | `points.import` (GET), `points.import.template` (CSV), `points.import.store` | `PointImportController@*` → `ImportPointsJob` | — | — | pendiente |
| `point_alert_mail_template_entry.php`, `_register.php` | ポイント有効期限切れ前通知 テンプレート | `points.templates.edit/update?kind=notice` | `PointTemplateController@*` | `point_settings.notices` (asunto y cuerpo) | — | pendiente |
| `point_shikkou_mail_template_entry.php`, `_register.php` | ポイント失効通知 テンプレート | `points.templates.edit/update?kind=expired` | `PointTemplateController@*` | `point_settings.expired_subject/body` | — | pendiente |
| `point_shikkou_alert_mail_template_entry.php`, `_register.php` | 失効前通知 テンプレート (múltiple) | `points.templates.edit/update?kind=notice&index=N` | `PointTemplateController@*` | `point_settings.notices[]` | — | pendiente |
| `sendPointShikkouMail.php`, `sendPointShikkouAlertMail.php`, `sendPointShikkouAlertMailMultiple.php` | (cron) | — | `promocion:loyalty` | — | `ProcessLoyaltyExpiry` | descartado: no es vista; lo cubre el comando |
| `setRegisterPoint.php` | (cron 5 min) | — | `promocion:grant-signup-points` | `point_settings.signup_delay_*` | — | pendiente (comando) |
| `setPaidMemberPoint.php` | (cron 10:00) | — | `promocion:grant-paid-member-points` | `point_settings.paid_member_settings` | — | pendiente (comando) |
| `mailCommon.php`, `inc/page.php` | fragmento | — | `LoyaltyNoticeMail` | — | — | descartado: no son vistas |

#### スタンプ管理 — `stamp/`

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas / cambios | Base | Estado |
|---|---|---|---|---|---|---|
| `stamp_pattern.php`, `stamp_pattern_register.php` | スタンプデザイン管理 | `stamps.designs.index/update` | `StampDesignController@index/update` | `stamp_designs` | — | pendiente |
| `stamp_config.php`, `stamp_config_register.php` | スタンプ仕様管理 | `stamps.edit/update` | `StampController@edit/update` (`StampSettingRequest`) | `stamp_settings` + columnas (§2.3) | `StampController` | pendiente |
| `ajax_stamp_shikkou_kakunin.php` | (AJAX afectados) | `stamps.expiry-preview` | `StampController@expiryPreview` | — | — | pendiente |
| `stamp_rules_list.php` | スタンプルール一覧 | `stamp-rules.index` | `StampBonusRuleController@index` | `stamp_bonus_rules` | — | pendiente |
| `stamp_rules_new.php`, `stamp_rules_update.php`, `_frm`, `_errchk`, `_register` | スタンプルール新規 / 修正 | `stamp-rules.create/store/edit/update` | `StampBonusRuleController@*` | `stamp_bonus_rules` | — | pendiente |
| `stamp_rules_delete.php` | (削除) | `stamp-rules.destroy` | `StampBonusRuleController@destroy` | — | — | pendiente |
| `oshirase_coupon.php`, `ajax_oshirase_coupon.php`, `oshirase_coupon_register.php` | クーポンお知らせ機能管理 | `stamps.rewards.index/store/destroy` | `StampController@rewards/storeReward/destroyReward` | `stamp_rules` (ya existe: premio por N sellos) | `StampController@storeRule/destroyRule` | pendiente |
| `stamp_alert_mail_template_entry/_register.php` | スタンプ失効前通知 テンプレート | `stamps.templates.edit/update?kind=notice` | `StampTemplateController@*` | `stamp_settings.notice_subject/body` | — | pendiente |
| `stamp_shikkou_mail_template_entry/_register.php` | スタンプ失効通知 テンプレート | `stamps.templates.edit/update?kind=expired` | `StampTemplateController@*` | `stamp_settings.expired_subject/body` | — | pendiente |
| `sendStampShikkouMail.php`, `sendStampShikkouAlertMail.php` | (cron) | — | `promocion:loyalty` | — | `ProcessLoyaltyExpiry` | descartado: no es vista; lo cubre el comando |
| `inc/page.php` | fragmento | — | — | — | — | descartado: no es vista |

#### スマトピ連携 — `smatopi/`

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Estado |
|---|---|---|---|---|---|
| `smatopi_message.php`, `ajax_smatopiMessage.php`, `ajax_smatopiLine.php` | スマトピ / メッセージ作成 | `smatopi.create`, `smatopi.store` | `SmatopiController@create/store` → `SmatopiClient` (HTTP) | `smatopi_messages` | pendiente (opcional, tras `Gate smatopi`; confirmar uso) |
| `smatopi_sent_history.php`, `ajax_smatopiSentHistory.php` | スマトピ / メッセージ送信履歴 | `smatopi.history` | `SmatopiController@history` | `smatopi_messages` | pendiente (opcional) |

#### 来店履歴管理 — `visit/`

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Base | Estado |
|---|---|---|---|---|---|---|
| `visit_stats2.php` | 来店履歴一覧 | `visit-history.index` | `VisitHistoryController@index` | — (lee `visits` de S2) | `VisitHistoryController@index` | pendiente |
| `day_visit_list.php` | 来店履歴一覧 (日別) | `visit-history.day` | `VisitHistoryController@day` | — | — | pendiente |
| `store_visit_list.php` | 個別来店履歴 | `visit-history.shop` | `VisitHistoryController@shop` | — | — | pendiente |
| `csv_download.php`, `storecsv_download.php`, `daycsv_download.php` | CSV de visitas | `visit-history.export?scope=period|day|shop` | `VisitHistoryController@export` (StreamedResponse) | — | — | pendiente (`daycsv_download.php` está vacío: se implementa el CSV diario igual que los otros) |
| `customer_detail.php` | 顧客登録情報 | enlace a `admin.clientes.show` | — | — | — | descartado: es la ficha de cliente de S2 |
| `touch_config.php`, `touch_config_register.php` | カードリーダ設定 | `admin.customers.visit-interval` (S2) | `Customers\VisitIntervalController` | `shops.visit_interval_seconds` | hecho por S2 | descartado: cubierto por S2 |
| `visit_stats.php`, `new_visit_stats.php`, `stats_test*.php`, `csv_test*.php` | (copias antiguas) | — | — | — | — | descartado: duplicados/pruebas |
| `fix_visit_stats.php` | (corrección masiva) | — | — | — | — | descartado: script peligroso, bloqueado a 403 |

#### 予約管理 — `reservation/` (category3)

Mismo dominio que 予約管理 de S2 (category9, `r/`, `reserve/`). Se propone que lo implemente S2 sobre su modelo `Reservation` (ver Pedidos). Las filas quedan aquí para no perder paridad.

| Legacy | Título (ja) | Ruta Laravel propuesta | Estado |
|---|---|---|---|
| `reserve_customer_search.php` | 予約新規登録 / 顧客一覧・検索 | `admin.reservas.*` | S2 |
| `reserve_new.php`, `reserve_new_confirm.php`, `reserve_new_register.php`, `reserve_new_complete.php` | 予約新規登録 / 確認 / 完了 | `admin.reservas.*` | S2 |
| `reserve_calendar.php` | 予約・来店一覧 | `admin.reservas.*` | S2 |
| `reserve_list.php` | 予約一覧 | `admin.reservas.*` | S2 |
| `reserve_detail.php`, `reserve_change_status.php`, `reserve_delete.php` | 予約詳細 / 予約情報 | `admin.reservas.*` | S2 |
| `category_entry.php`, `category_register.php`, `category_delete.php` | 予約カテゴリ登録・変更 | `admin.reservas.*` | S2 |
| `config.php`, `config_register.php` | 予約項目設定 | `admin.reservas.*` | S2 |
| `ajax_category.php`, `ajax_staff.php`, `inc/check_select.php` | (AJAX) | `admin.reservas.*` | S2 |
| `model_reservation.php` | 予約機種一覧 | `admin.reservas.*` | S2 |

---

## 2. Esquema

Todas las tablas nuevas siguen ESTANDARES_BD: `id()` (bigserial), `text`, `smallint` con comentario + enum, `status` default 1, `timestamps()`, `softDeletes()`, FK `{tabla}_id` bigint, `uid` único si lo ve el cliente final. Migraciones nuevas: `AAAA_MM_DD_HHMMSS_promocion_<accion>.php`. **No se editan** las migraciones `2026_10_06_*`: los cambios van en migraciones `promocion_alter_*` nuevas (S0 puede decidir fusionarlas antes del primer despliegue).

### 2.1 Tablas nuevas

| Tabla | Columnas propias (además de las estándar) | Para |
|---|---|---|
| `message_template_categories` | `name` text, `sort` integer | テンプレートカテゴリ管理 |
| `message_clicks` | `customer_message_id` bigint FK, `message_id` bigint FK, `customer_id` bigint FK, `clicked_at` timestamp, `customer_age` smallint null, `customer_sex` smallint null, `customer_occupation` smallint null — índice (`message_id`, `clicked_at`) | クリックカウンタ: cada acceso con perfil del momento (como `tb_mailmag_analyze`) |
| `signage_news` | `name` text, `image_path` text null, `play_seconds` integer, `sort` integer | 新着情報 |
| `point_bonus_rules` | `shop_id` FK, `type` smallint (1 franja horaria, 2 día de la semana + franja, 3 periodo, 4 día del mes, 5 fecha concreta), `start_time` time null, `end_time` time null, `weekday` smallint null (0 = domingo), `month_day` smallint null (32 = fin de mes), `starts_at` timestamp null, `ends_at` timestamp null, `points` integer, `rank_ids` json null (ids de `ranks`) | ポイントルール |
| `stamp_bonus_rules` | igual que `point_bonus_rules` con `stamps` integer en lugar de `points`, sin `rank_ids` | スタンプルール |
| `stamp_designs` | `name` text, `image_path` text, `available_flg` smallint, `default_flg` smallint, `sort` integer | スタンプデザイン管理 (catálogo de 79) |
| `coupon_issues` | `coupon_id` FK, `admin_id` FK, `filters` jsonb, `search_summary` text, `excluded_customer_ids` jsonb, `recipient_count` integer, `issued_count` integer, `issued_at` timestamp null | Emisión masiva (reemplaza `$_SESSION['wherestr']`) |
| `smatopi_messages` | `shop_id` FK null, `subject` text, `body` text, `request_data` json, `filters` jsonb, `sent_at` timestamp | Historial スマトピ |
| `lottery_draws` | `customer_id` FK, `shop_id` FK null, `rank` smallint (`LotteryRank`: 1 diamante … 4 bronce, 5 ハズレ), `coupon_customer_id` FK null (cupón entregado), `drawn_at` timestamp, `expires_at` timestamp null (plazo de canje) — índice (`customer_id`, `drawn_at`) | Sorteo ガチャリン: reemplaza `tb_kujibiki_log` y `tb_kujibiki_count` (los intentos del día se cuentan sobre esta tabla; las existencias de premio = `lottery_prizes.quantity` de S4 − premios ya sorteados, sin escribir en tablas de S4) |

### 2.2 Cambios en tablas existentes del módulo (migraciones `promocion_alter_*`)

| Tabla | Cambio | Motivo legacy |
|---|---|---|
| `messages` | + `admin_id` bigint FK null (登録者), `search_summary` text null (`search_display`), `excluded_customer_ids` jsonb default `[]` (`振分条件`), `image_path` text null (画像), `blocks` json null (bloques push imagen+enlace), `point_amount` integer null, `point_expires_at` timestamp null (push con puntos), `survey_reply_flg` smallint default 0 | mail_send_frm, push_new |
| `message_rules` | + `admin_id`, `search_summary`, `image_path`, `blocks`, `point_amount` integer null, `point_expiry_days` integer null | auto/follow push con puntos |
| `message_templates` | + `message_template_category_id` bigint FK null; `category` (enum) se mantiene como "uso" | tb_mail_template |
| `customer_message` | + `open_count` integer default 0 | `push_notifications.open_cnt` |
| `coupons` | − `display_flg` (se funde en `status`, CONVENCIONES §4); enum `CouponUsage` + `VisitPrint = 7` (会員の来店時に印刷用), `Lottery = 8` (ガチャリン用). S4 añade los pivotes `coupon_signage_program` y `coupon_signage_display` sin tocar esta tabla | `coupon_kubun`, `hyouji_flg` |
| `coupon_customer` | + `printed_at` timestamp null (`print_flg`), `confirmed_at` timestamp null (`kakunin_flg`) | `tb_mycoupon`, reproductor `digitalsignage` |
| `surveys` | + `type` smallint (1 encuesta de envío, 2 お客様アンケート), `admin_comment` text null, `app_flg` smallint default 0 (índice único parcial `WHERE app_flg = 1 AND deleted_at IS NULL`), `sort` integer | anketo/anketo2 |
| `survey_questions` | + `unit` text null, `comment` text null, `size` smallint null (`FieldSize`) | tb_c3_anketo_form |
| `survey_responses` | + `test_flg` smallint default 0; se quita el `unique(survey_id, customer_id)` (el legacy permite responder varias veces y respuestas de prueba) | tb_c3_anketo_sub |
| `point_settings` | + `notice_subject/notice_body` dentro de cada elemento de `notices` json, `expired_subject` text, `expired_body` text, `signup_delay_days` integer, `signup_hour` smallint, `signup_minute` smallint, `beacon_visit_flg` smallint, `earn_display_type` smallint, `paid_member_settings` json null | point_config, setRegisterPoint, setPaidMemberPoint |
| `customer_group_point_rules` | + `cash_rate` numeric(5,2) null, `card_rate` numeric(5,2) null, `use_limit` integer null, `include_used_flg` smallint; `rate` queda obsoleta (se copia a ambas al migrar) | tb_customer_group |
| `stamp_settings` | + `cash_rate`/`card_rate` numeric(5,2), `mypage_exchange_flg` smallint, `update_type` smallint, `notice_subject/notice_body`, `expired_subject/expired_body` text | stamp_config |
| `customer_points`, `customer_stamps` | + `staff_id` bigint null (FK cuando S1 publique staff), `terminal_id` bigint FK null, `message_id` bigint FK null (push con puntos); enum `PointMovement` + 9 alta desde app (マイコレ), 10 login diario, 11 push, 12 importación CSV | tb_point_log.action_flg, ポイント集計 |

### 2.3 Mapeo legacy → nuevo (para el importador de S1)

Reglas generales: `cd` → `legacy_id_map` del importador de S1, salvo **`coupons.id = tb_mycoupon_master.cd` y `coupon_customer.id = tb_mycoupon.cd`** (CONVENCIONES §4: salen en `appview/coupon_control.php` como `coupon_cd` y `my_coupon_cd`; al usarse, la fila pasa de `tb_mycoupon` a `tb_mycoupon_used` con el mismo `cd`, así que es 1:1); el importador ajusta la secuencia después; `disp_flg = '9'` → `deleted_at = ahora`; `hyouji_flg = '9'`/`'0'` → `status = 0` (CONVENCIONES §4); fechas UNIX (`touroku_date`, `update_date`) → `created_at`/`updated_at` con `Carbon::createFromTimestamp(..., 'Asia/Tokyo')`; `tb_shop_cd = -1` o vacío → `shop_id = null` (todas las tiendas); `hatuban` y `send_schedule` **no se migran**.

| Legacy | Nuevo | Columnas |
|---|---|---|
| `mailmag_master` (`type_flg` '', `deco`, `coupon`, `anketo`) | `messages` | `店id`→`shop_id`; `タイトル`→`subject`; `pc内容`→`body` (los `docomo/au/vodafone/その他内容` se descartan); `type_flg` ''/coupon/anketo → `channel=1`, `deco` → `channel=2`; `type_data_cd` → `coupon_id` (si coupon) o `survey_id` (si anketo); `配信日時` → `scheduled_at`/`sent_at`; `配信状況` 1→`delivery_status=2` (programado), 2→4 (enviado); `配信総数`→`recipient_count`; `search_display`→`search_summary`; `備考`→`note`; `登録者`→`admin_id` (por nombre, null si no encaja); `画像名大`→`image_path` (archivo copiado a Storage). **`振分配信` y `振分条件` (SQL) no se migran**: `filters = {}` y el envío queda como histórico de solo lectura. `pos_search`, `pos_tb_shop_cd`, `*配信数` por operador, `画像*` restantes: descartados |
| `mailmag_master` (`type_flg` follow, followdeco, auto, autodeco, yoyaku, remdeco) | `message_rules` | `send_parameter` → follow: `tienda<>用途<>días` → `shop_id`, `trigger` (1 来店後→1, 2 登録後→2, 3 誕生日前→3), `days`; auto: `1<>meses<>días` → `trigger=6`, `months`, `month_days` (32 → "end"); `2<>días` → `trigger=7`, `weekdays`; yoyaku/remdeco: `trigger=8`, `days`. `配信時間` HH:MM → `run_hour`/`run_minute`; `*deco` → `channel=2`, resto `channel=1`; `disp_flg 7` → `status=0`; `振分配信` → `filters={}` (**revisar a mano** las reglas activas: la SQL no se reinterpreta; lista para Antony) |
| `mailmag_master` (`posyoyaku`, `posremdeco`) | — | No se migran (POS fuera de alcance) |
| `send_schedule` | — | No se migra |
| `mailmag_sended_log` | `customer_message` | `mailmag_master_cd`→`message_id`, `tb_customer_cd`→`customer_id`, `email`→`recipient`, `sent_at` = `sent_at` del mensaje |
| `tb_mailmag_analyze` | `message_clicks` (+ `customer_message.clicked_at/click_count`) | `mailmag_master_cd`→`message_id`, `tb_customer_cd`→`customer_id`, `touroku_date`→`clicked_at`, `customer_age/sex/job` → `customer_age/sex/occupation` (sexo y ocupación con la tabla de códigos de S2) |
| `tb_mail_template_category` | `message_template_categories` | `name`, `rank`→`sort`, `hyouji_flg`→`status` |
| `tb_mail_template` | `message_templates` | `title`→`name` y `subject`, `naiyou`→`body`, `tb_mail_template_category_cd`→`message_template_category_id`, `mail_type` deco→`html_flg=1`, `rank`→`sort`, `category`=1 (newsletter) |
| `tb_test_mail_address` | `test_mail_addresses` | `tb_shop_cd`→`shop_id`, `name`, `email`→`mail` |
| `push_master` (inmediatos y programados) | `messages` (`channel=3`) | `tb_shop_cd`→`shop_id`, `title`→`subject`, `message`→`body`, `extra_data`/`pic_link`→`blocks`, `post_point`→`point_amount` (si `post_type=2`), `point_expiration_date`→`point_expires_at`, `search_display`→`search_summary`, `wherestr`→`filters={}`, `push_follow_master_cd`→`message_rule_id` |
| `push_follow_master` | `message_rules` (`channel=3`) | `type_flg` follow/auto/scheduled → `trigger` según `send_parameter` (igual que mailmag); `send_time`→`run_hour/minute`; `post_point`→`point_amount`, `point_expiration_days`→`point_expiry_days`; `disp_flg 7`→`status=0`; `scheduled` → `messages` programado |
| `push_notifications` | `customer_message` | `type_master_cd`→`message_id` (si `type='push'`; si `type='mailmaga'` → el `messages` de ese `mailmag_master`), `tb_customer_cd`→`customer_id`, `registration_id`→`recipient`, `touroku_date`→`sent_at`, `open_cnt`→`open_count`, `open_date`→`opened_at` |
| `tb_digital_signage` | `signage_news` | `title`→`name`, `play_time`→`play_seconds`, `rank`→`sort`, imagen (`uploads/`) → `image_path` |
| `tb_mycoupon_master` | `coupons` | `tb_shop_cd`→`shop_id`, `title`→`name`, `comment`→`description`, `attention`→`notes` (separado por líneas, máx. 5), `coupon_kubun` ''→1, 2→2, 3→3, 4→4, 5→5, 6→7, 9→8, 10→6 (resto → 1); `shouhi_point`→`cost`; `limit_flg` 1..5→`validity_type`; `time_limit` → `validity_days` (4) o `valid_until` (5, fecha del timestamp); `saitouroku_flg` '1'→`reissue_flg=0`, si no 1; `hyouji_flg`→`status`; `type` y `value` nulos = cupón solo de texto, como el legacy (decisión de S0) |
| `tb_mycoupon` + `tb_mycoupon_used` | `coupon_customer` | `tb_mycoupon_master_cd`→`coupon_id`, `tb_customer_cd`→`customer_id`, `touroku_date`→`issued_at`, `time_limit`→`expires_at`, `used_flg='1'` y `used_date`→`used_at`, `tb_shop_cd` (used)→`used_shop_id`, `print_flg='1'`→`printed_at` (= `touroku_date` si no hay otra fecha), `kakunin_flg='1'`→`confirmed_at` |
| `tb_kujibiki_log` | `lottery_draws` | `tb_customer_cd`→`customer_id`, `kujibiki_name` (ダイヤ/ゴールド/シルバー/ブロンズ/ハズレ) → `rank` 1..5, `time_limit`→`expires_at`, `touroku_date`→`drawn_at`; `tb_mycoupon_master_cd` + `mycoupon_flg` → `coupon_customer_id` si se encuentra el cupón emitido |
| `tb_kujibiki_count` | — | No se migra: es el contador del día (`int_data`), se deriva de `lottery_draws` |
| `contents_master` (`category='tb_c6_kujibiki'`) | `lottery_settings`, `lottery_prizes` (S4) | Lo migra S4; el contador de existencias `int_data` no se migra (se deriva) |
| `tb_c3_anketo_cate` / `tb_c3_anketo2_cate` | `surveys` (`type` 1 / 2) | `name`, `comment`→`admin_comment`, `start_date`→`starts_on`, `end_date`→`ends_on`, `attached_cd`→`coupon_id`, `hyouji_flg`→`status`, `rank`→`sort`; `contents_master(tb_c3_anketo2, set_anketo_cd)` → `app_flg=1` |
| `tb_c3_anketo_form` / `tb_c3_anketo2_form` | `survey_questions` | `tb_c3_anketo_cate_cd`→`survey_id`, `name`, `form_type`→`type` (`CustomFieldType`), `titles`/`data` → `options` json, `hissu`→`required_flg`, `unit`, `comment`, `style_class`→`size`, `rank`→`sort` |
| `tb_c3_anketo_sub` / `tb_c3_anketo2_sub` | `survey_responses` | Agrupar por (`tb_c3_anketo_cate_cd`, `category_inner_group`) → una fila; `tb_customer_cd`→`customer_id`; cada fila legacy → `answers[{question_id}]` desde `text_data`/`float_data`/`array_data`; `MIN(touroku_date)`→`answered_at` |
| `admin_master` (`point_*`) | `point_settings` (una fila por tienda, copiando los valores globales) | `point_yukoukigen_flg` 2→`expiry_flg=0`; `point_shikkou_kikan`→`expiry_days`; `point_shikkou_time`→`expiry_hour/minute`; `point_shikkou_sousa`→`extend_mode`; `point_alert_*`/`point_shikkou_mail_*` → `notices`/`expired_*` |
| `tb_shop` (`point_percentage_genkin/card`, `shiyou_point_jyougen`, `introduce_settings`, `customer_registration_settings`, `login_point_settings`) | `point_settings` | `cash_rate`, `card_rate`, `use_limit`; `introduce_settings` (serializado) → `referral_*`/`referee_points`; `customer_registration_settings` → `signup_points`, `signup_delay_days`, `signup_hour/minute` |
| `point_shikkou_alert` | `point_settings.notices[]` | `alert_day`→`days`, `alert_time`→`hour/minute`, `title`/`message`→`notice_subject/body` |
| `tb_customer_group` (columnas de puntos) | `customer_group_point_rules` | `point_percentage_genkin`→`cash_rate`, `point_percentage_card`→`card_rate`, `shiyou_point_jyougen`→`use_limit`, `keisan_flg`→`include_used_flg` |
| `tb_point_rules` | `point_bonus_rules` | `tb_shop_cd`→`shop_id`, `type_flg`→`type`, `start_time`/`end_time` (según tipo: hora del día o timestamp) → `start_time/end_time` o `starts_at/ends_at`, `day_of_week`→`weekday` o `month_day` (tipo 4), `point_count`→`points`, `tb_kingaku_rank_master_cds` + `tb_raiten_rank_master_cds` → `rank_ids` (vía mapa de `ranks`), `hyouji_flg`→`status` |
| `tb_customer_point` + `tb_point_log` | `customer_points` | Por cliente: **un movimiento inicial** `points = service_point`, `type = 7` (ajuste), `note = 'saldo migrado'`, `expires_at = point_yukoukigen`; `tb_point_log` se importa como histórico con `status = 0` (no suma al saldo) para que ポイント集計 muestre el pasado. `action_flg`→`type` (tabla de correspondencia en el importador) |
| `tb_stamp_config` + `admin_master.stamp_*` | `stamp_settings` | `stamp_max`→`card_size`, `touroku_bonus`→`signup_bonus`, `limit_flg`→`expiry_flg`; `stamp_shikkou_kikan`→`expiry_days`; `stamp_*_mail_*`→`notice_*`/`expired_*`; `tb_shop.stamp_percentage_*`→`cash_rate/card_rate`; `raiten_stamp_flg`→`visit_stamp_flg` |
| `tb_stamp_rules` | `stamp_bonus_rules` | Igual que `tb_point_rules` con `stamp_count`→`stamps` |
| `tb_stamp_pattern` | `stamp_designs` | `name`, `pic_default`→`default_flg`, `pic_shop_flg`→`available_flg`, `narabi`→`sort`; imágenes a Storage |
| `tb_stamp_oshirase_coupon` | `stamp_rules` (existente) | `tb_shop_cd`→`shop_id`, `stamp_count`, `tb_mycoupon_master_cd`→`coupon_id`, `naiyou`→`message` |
| `tb_customer.stamp_count`, `stamp_yukoukigen` + `tb_stamp_log` | `customer_stamps` | Igual que puntos: movimiento inicial con el saldo; log como histórico `status = 0` |
| `smatopi_message` | `smatopi_messages` | `title`→`subject`, `message`→`body`, `request_data`, `record_date`→`sent_at`, `shop_cd`→`shop_id`, `query` → descartada (SQL) |
| `tb_coupon`, `tb_haikei`, `pit_coupon`, `kaiin_master`, `number_master` | — | No se migran |
| `tb_touch_mode` | `shops.visit_interval_seconds` | Lo migra S2 |

---

## 3. Servicios viejos → Laravel 13

| Servicio viejo | Reemplazo | Dónde |
|---|---|---|
| `cSendSchedule` reescribe el crontab del usuario con `exec("crontab …")` | Scheduler con un único `schedule:run` en cron. Las tareas se registran en `PromocionServiceProvider::boot()` con `Schedule::command(...)->withoutOverlapping()->onOneServer()` (no en `routes/console.php`) | `app/Providers/PromocionServiceProvider.php` |
| — tarea: envíos programados (`mail_sche_send.php`) | `promocion:send-scheduled` cada minuto (`messages.scheduled_at <= now()`) | comando existente, renombrado |
| — tarea: follow / auto / reminder / `sendFollowPush.php` | `promocion:run-rules` cada minuto (cada regla una vez al día a su hora) | existente |
| — tareas de puntos y sellos (`sendPointShikkou*`, `sendStampShikkou*`) | `promocion:loyalty` cada 15 min (respeta la hora de cada tienda) | existente (`ProcessLoyaltyExpiry`) |
| — `setRegisterPoint.php` (cada 5 min) | `promocion:grant-signup-points` cada 5 min | nuevo |
| — `setPaidMemberPoint.php` (10:00) | `promocion:grant-paid-member-points` diario 10:00 | nuevo |
| — rangos `setCustomerKingakuRank/RaitenRank` | `promocion:update-ranks` a la hora de `rank_schedules` (cada minuto comprueba) | nuevo (los rangos son de S3 según CONVENCIONES) |
| — limpieza de `uploads/temp` (`find -mtime +1` en `index.php`) | `promocion:clean-temp` diario sobre `Storage::disk('local')->files('promocion/temp')` | nuevo |
| `exec(php mailsoushin.php …)` | `SendMessageBatch::dispatch()` (lotes de 200) en cola `database` | existente |
| Dos motores (`cMailsoushin` y copia en decomail), `mb_send_mail`, Qdmail, `CrmDecomail` | `MessageDispatcher` + `PromotionMail` (Mailable en cola); HTML sin conversión デコメール | existente |
| `replaceMessage` / `mailWordsReplace` | `MessageRenderer` (completar la lista de etiquetas del legacy) | existente |
| Correo "メール配信完了メール" a la tienda + copia a `system_mail` | `MessageSentSummaryMail` (Mailable en cola) al terminar el último lote | nuevo |
| `addSimpleLogin` (`easy_login` en el enlace) | Enlace firmado `/p/c/{uid}` → `URL::signedRoute`; el login de Mi Página lo resuelve S4 | existente |
| `tb_mailmag_analyze` | `message_clicks` + `customer_message.clicked_at` | nuevo |
| `error_send_cnt` / `mailmaga_flg='9'` | `customers.bounce_count` / `mail_magazine_flg` | existente |
| `振分配信` / `wherestr` / `$_SESSION['wherestr']` / `lineMessage[…]['sql']` (SQL como texto) | `filters` jsonb + `CustomerSearch` (S2); emisión con `coupon_issues` + `IssueCouponsJob` | existente/nuevo |
| `hatuban` con `FOR UPDATE` | `id` autoincremental; saldos con `DB::transaction` + `lockForUpdate` en `LoyaltyService` | existente |
| `Point::setPoint` / `getPointCount` (761 líneas) | `LoyaltyService::addPoints` + `PointRuleResolver` (prioridad 5 → 4 → 3 → 2 → 1 → valor por defecto) | nuevo resolver |
| Push por `GET http://b.g-api.net/api/mpa/pushMessage` (el servidor externo lee `push_master`/`mailmag_master` del CRM y envía por FCM) | Interfaz `PushSender`; implementación `FcmPushSender` (FCM HTTP v1) sobre `customer_devices`; `LogPushSender` en local/pruebas | ver Pedidos (credenciales) |
| `cKujibiki` (sorteo: intentos por día con `FOR UPDATE` en `tb_kujibiki_count`, resultado por probabilidad, existencias en `contents_master`, cupón con vencimiento) | `LotteryService::draw(Customer)` en `DB::transaction` + `lockForUpdate` sobre el cliente; lee `LotterySetting`/`LotteryPrize` (S4, solo lectura), escribe `lottery_draws` y entrega el cupón con `CouponIssuer`. Lo llama Mi Página (S4) | nuevo |
| Reproductor `digitalsignage` marca el cupón del socio impreso/usado (`ajax_kaiin_mycoupon_print_crawl.php` escribía `tb_mycoupon`) | `CouponIssuer::markPrinted(CouponCustomer)`, `CouponIssuer::markUsed(CouponCustomer, ?Shop)` (ya existe `use`); S4 los llama, no escribe `coupon_customer` | nuevo / existente |
| Smatopi (`class/Smatopi.php`, `fsockopen`/curl) | `SmatopiClient` con `Http::` (timeouts, reintentos), detrás de `Gate::define('smatopi')` | nuevo |
| POS (`pos_search`, `posyoyaku`, `POS_DB_PASSWORD`) | `App\Services\Promotions\PosGateway` (interfaz sin implementación, CONVENCIONES §4) | fuera de alcance |
| CSV con Flash (`sample.swf`) / `header('text/x-csv')` | `StreamedResponse` con `mb_convert_encoding(..., 'SJIS-win')` (como el legacy) | nuevo helper en `app/Services/Promotions/CsvWriter` (o el que publique S1) |
| CSV de subida (`file()` + regex) | `PointImportRequest` (mimes, tamaño) + `ImportPointsJob` que lee de `Storage` | nuevo |
| QR `qr_img` + `.dat` | `bacon/bacon-qr-code` (ya instalado) | existente |
| PDF (QR印刷, etiquetas DM) | Paquete PDF que defina S1 | pedido |
| Subidas a `uploads/{shop}`, `hp/uploads/c3/` | `Storage::disk('public')` en `promocion/…`; las URL viejas que lea la app se redirigen (S4) | nuevo |
| TinyMCE 3 | Editor HTML actual (TipTap) | pedido (npm) |
| `ajaxSavePush` con `session_id()` como CSRF | Form Request + CSRF | nuevo |
| Permisos `myKengen[1005/1009/1011]`, `rank == 'master'` | Policies por modelo (`MessagePolicy`, `CouponPolicy`, `SurveyPolicy`, `PointPolicy`…) con el nivel de S1; filtro por tienda (master ve todas) | depende de S1 |
| Menú dinámico `tb_base_system_child` (プッシュ通知, ガチャリン管理, スマトピ連携, ポイント交換クーポン, 印刷) | Módulos activables de S1 (`Gate`/flag por función) | pedido |
| `admin_master.mailmag_sended_log` (tope del log de destinatarios) | `customer_message` guarda todo; sin tope | decisión |

---

## 4. Orden de implementación y puntos

1 punto ≈ media jornada. Total ≈ **71,5 puntos** (el análisis daba 111,5 sin contar la base ya hecha). Dividido en dos bloques independientes por si S0 parte el grupo; el bloque 0 lo hace quien empiece.

### Bloque 0 — Común (3 pts)

| # | Tarea | Depende de | Pts |
|---|---|---|---|
| 0.1 | Reubicar al contrato: `routes/modulos/promocion.php` (`admin.promocion.*`), vistas a `admin/promocion/`, `lang/ja/promocion.php`, `config/menu/promocion.php`, `PromocionServiceProvider` (Scheduler), comandos `promocion:*`, Policies base. Pruebas existentes en verde | S1: layout, carga de `routes/modulos`, registro del provider, niveles de permiso | 3 |

### Bloque M — Mensajería (≈ 33 pts)

| # | Submódulo | Depende de | Pts |
|---|---|---|---|
| M1 | Plantillas + categorías (orden) + direcciones de prueba (con staff) | 0.1; S1 staff | 2 |
| M2 | Envíos メルマガ / デコメール: asistente filtros → redacción → confirmación → registro, prueba, imagen, 例外会員, programados, pasados, detalle, destinatarios, CSV, anulación, correo resumen, editor HTML | 0.1; S2 `CustomerSearch`; S1 editor (npm) | 7 |
| M3 | Automatizaciones auto / follow (con confirmación y conteo) | M2 | 4 |
| M4 | Clics: `message_clicks`, クリックカウンタ por sexo/edad/ocupación y hora, CSV | M2 | 3 |
| M5 | Push: bloques, puntos, prueba a un socio, listas, historial por cliente, `FcmPushSender` | M2, M3, F2 (puntos); credenciales FCM | 7 |
| M6 | Recordatorio de reservas (`trigger` 8) con stub de `Reservation` → modelo de S2 | M3; S2 `Reservation` | 2 |
| M7 | QR / 基本情報 (4 variantes) + impresión PDF | 0.1; S1 PDF | 1,5 |
| M8 | DM発行 + etiquetas A4×12 PDF | S2 `CustomerSearch`; S1 PDF | 1,5 |
| M9 | 新着情報 (`signage_news`) | 0.1; confirmación de uso | 1 |
| M10 | Apoyo al importador (mapeo §2.3, pruebas de conversión de `send_parameter`) | S1 importador | 2 |
| M11 | Portada del módulo + `promocion:clean-temp` | 0.1 | 1 |

### Bloque F — Fidelización (≈ 35,5 pts)

| # | Submódulo | Depende de | Pts |
|---|---|---|---|
| F1 | Cupones: formulario completo, usos 7/8, emisión masiva (`coupon_issues`, `IssueCouponsJob`), historial, ranking, uso y detalle, búsqueda AJAX, `markPrinted`/`markUsed` para S4 | 0.1; S2 `CustomerSearch` | 5 |
| F1b | Sorteo: `LotteryService::draw` + `lottery_draws` (para Mi Página de S4) | F1; S4 `LotterySetting`/`LotteryPrize` | 1,5 |
| F2 | Puntos: ポイント仕様管理 completo (8 formularios, grupos de cliente), plantillas de aviso, vista previa de afectados | 0.1 | 4 |
| F3 | Reglas de puntos por tramo + `PointRuleResolver` (prioridad con pruebas) | F2 | 3 |
| F4 | ポイント集計 + carga masiva CSV + `grant-signup-points` / `grant-paid-member-points` + rangos (`update-ranks`) | F2 | 4 |
| F5 | Sellos: 仕様 completo, diseños, reglas por tramo, クーポンお知らせ, plantillas | 0.1 | 5 |
| F6 | Encuestas unificadas (tipo 1 y 2): cabecera, preguntas con orden, prueba, emisión, análisis, CSV, respuestas por cliente, encuesta activa en la app, 返信メール | 0.1; M2 (emisión y respuesta) | 9 |
| F7 | Historial de visitas: lista, por día, por tienda, CSV | 0.1; S2 `visits` | 2,5 |
| F8 | スマトピ (opcional) | confirmación de uso + credenciales | 3 |

Orden sugerido con una sola sesión: 0.1 → M1 → M2 → F1 → F2 → F3 → M3 → M4 → F5 → F6 → F4 → F7 → M5 → M6 → M7 → M8 → M11 → M10 → M9 → F8. Con dos sesiones: S3a = bloque 0 + M; S3b = F (empieza tras 0.1; F6 espera a M2).

---

## 5. Pedidos

### A S1 (núcleo)
1. Cargar `routes/modulos/promocion.php` en el grupo `admin` y `routes/publico/promocion.php` fuera de él (enlaces `/p/...` de correos y app; `/p/c/{uid}` con middleware `signed`).
2. Registrar `App\Providers\PromocionServiceProvider` en `bootstrap/providers.php` (ya existe, con el Scheduler y los bindings); al registrarlo, quitar las 3 líneas `promotions:*` de `routes/console.php` y el binding de `PushSender` de `AppServiceProvider` (el provider usa `bindIf`). Hasta S0/S1 decidan el renombrado, los comandos siguen como `promotions:*`.
3. Niveles de permiso (equivalente a `cKengen` 1005/1009/1011 y `rank=master`) para las Policies.
4. Módulos activables por función: プッシュ通知, ガチャリン管理, スマトピ連携, ポイント交換クーポン, 印刷 (coupon), アンケート, スタンプ管理.
5. Configuración global equivalente a `admin_master`: `system_mail`, `mail_footer`, `mail_send_filter`, `application_use_flg`, `smatopi_flg`, `domain_smatopi_id`.
6. Paquete PDF (QR印刷, etiquetas DM) y paquete npm de editor HTML (TipTap o el que S1 elija).
7. Tabla/modelo de staff (direcciones de prueba por staff; `staff_id` en movimientos de puntos y sellos).
8. Importador: usar el mapeo de §2.3; S3 aporta las funciones de conversión (`send_parameter`, `coupon_kubun`, `action_flg`) en `app/Services/Promotions/Legacy/` con pruebas.
9. Helper de CSV SJIS-win (si S1 lo centraliza); si no, S3 usa uno propio.
10. Nota: el `composer.json` de esta rama aún dice `laravel/framework ^12.0`; S3 implementa sobre lo que integre S1.

### A S2 (clientes / reservas)
1. Modelo `Reservation` (con `customer_id`, `shop_id`, `reserved_at`, estado) para el recordatorio `trigger` 8; S3 usa un stub hasta entonces.
2. **Propuesta:** que las pantallas de `category3/reservation/` (予約新規登録, calendario, lista, detalle, estado, categorías, 予約項目設定, 予約機種一覧) pasen a S2, que es dueña de 予約管理. S0 decide.
3. `CustomerSearch` estable (S3 la usa en mensajes, cupones, DM y emisión de encuestas) y ruta `admin.clientes.show` para enlazar desde visitas y destinatarios.
4. Lectura de `customer_devices` (token FCM) — o confirmar que `CustomerDevice` pasa a S3.
5. Edad/sexo/ocupación en `visits` en el momento de la visita (`tb_raiten_rireki.customer_age/sex/job`) para las estadísticas de 来店履歴; si no se añade, S3 calcula con el perfil actual (ver Decisiones).

### A S4 (Mi Página) — y lo que S3 ofrece a S4
1. Mi Página muestra cupones, encuesta activa (`surveys.app_flg`), sellos y puntos leyendo los modelos de S3 (solo lectura) y resuelve el login desde el enlace firmado `/p/c/{uid}` (reemplazo de `easy_login`).
2. **Aceptado (pedido de S4):** el sorteo lee `LotterySetting`/`LotteryPrize` de S4; `tb_kujibiki_count`/`tb_kujibiki_log` → `lottery_draws` (S3). S4 llama a `LotteryService::draw()`.
3. **Aceptado:** `CouponIssuer::markPrinted()` / `markUsed()` para el reproductor `digitalsignage` (`ajax_kaiin_mycoupon_print_crawl.php`).
4. **Aceptado:** pivotes `coupon_signage_program` / `coupon_signage_display` de S4 con FK a `coupons`; S3 no cambia `coupons.id` ni borra físicamente cupones (SoftDeletes).

### A S5 (integraciones)
1. `appview/member_regist.php` y los `mpa_*` escriben `push_master`/`push_notifications` y dan puntos: deben llamar a `LoyaltyService` y `PushSender` de S3, no escribir tablas.

### A S0 / Antony (confirmaciones)
1. Push: el servidor `b.g-api.net` lee directamente `push_master`/`mailmag_master` del CRM legacy. Con el esquema nuevo ese contrato se rompe. Propuesta: `FcmPushSender` directo (necesita la cuenta de servicio FCM de la app マイコレ). Alternativa: mantener vistas SQL de compatibilidad para g-api.
2. ¿Se usan 新着情報 (`category3/digital_signage`), DM発行 y スマトピ? Si no, se descartan (≈ 5,5 pts).
3. ~~Cupones migrados sin importe~~ — resuelto por S0: `type`/`value` nulos = cupón solo de texto.
4. CSV en SJIS-win (como el legacy) o UTF-8 con BOM.

---

## 6. Decisiones tomadas en este diseño

- Controladores en `Admin/Promotions` (carpeta existente); rutas, vistas, textos y menú con `promocion` según CONVENCIONES.
- Ninguna pantalla se marca `hecho`: la base existente no cumple todavía la paridad (§0).
- `anketo` y `anketo2` → una sola tabla `surveys` con `type`; se corrige el bug de nombres de columnas de `anketo2`.
- Las pantallas de confirmación del legacy (確認) se mantienen como paso `confirm` (POST que muestra la vista previa) en mensajes y reglas.
- El envío histórico se migra como solo lectura (sin filtros); las reglas activas con SQL guardada se listan para revisión manual.
- Saldos de puntos y sellos: movimiento inicial con el saldo legacy; el log viejo se importa con `status = 0` para el agregado.
- Clics con perfil del momento en `message_clicks` (como `tb_mailmag_analyze`).
- Estadísticas por operador móvil (DoCoMo/AU/Softbank) y conversión デコメール: descartadas.
- Sin tope del log de destinatarios (`mailmag_sended_log`): `customer_message` guarda todos.
- Borrado físico de `tb_point_rules` → SoftDeletes.
- Respuestas de prueba de encuestas con `test_flg = 1`, excluidas de análisis y CSV.
- `hyouji_flg` → `status` en todas las tablas (CONVENCIONES §4); `coupons.display_flg` se funde en `status`.
- Configuración propia (categorías de `admin_master`/`contents_master` que solo usa promoción) en tablas tipadas `point_settings`, `stamp_settings` (una fila por tienda, ya existen); la encuesta activa de la app (`contents_master tb_c3_anketo2`) va en `surveys.app_flg` y el `last_push_date` de `push_follow` en `message_rules.last_run_at`: no hace falta tabla genérica.
- Sorteo sin contador propio: intentos del día y existencias se derivan de `lottery_draws`.
- Touchcation (quiosco) fuera de alcance: ninguna vista de este grupo dependía solo del quiosco.
