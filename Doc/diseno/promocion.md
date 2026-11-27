# Diseño — S3 Promoción (販売促進管理)

> Sesión S3 · rama `l13/promocion` · 2026-11-26 (v2: **misma BD y misma UI que el legacy**, CONVENCIONES §0).
> Fuentes: `Doc/analisis-legacy/03a-promocion-mensajeria.md`, `03b-promocion-fidelizacion.md`, el código de `crm2-php8/public_html/system/category3` y el esquema `public_html/database/schema/pgsql-schema.sql`.
> La v1 (tablas nuevas) queda en git (`0d7d40f`); su lógica de servicios está en el stash `obsoleto: tablas nuevas` y se reaprovecha (§3).

## 0. Contrato del módulo

| Qué | Valor |
|---|---|
| `<modulo>` | `promocion` |
| Rutas admin | `routes/modulos/promocion.php` → nombres `admin.promocion.*`. URL: las del legacy sin `.php` (`/promocion/mail_mag/mail_new`…), para que los enlaces copiados del HTML cambien lo mínimo |
| URL literales del legacy | `routes/legacy/promocion.php` (`legacy.promocion.*`): los `ajax_*.php` y los `action` de formularios que llaman los JS copiados (`/system/category3/…`), sin prefijo y con el guard del personal; el layout pone el CSRF en jQuery, así que los JS no se tocan (CONVENCIONES §2) |
| Rutas públicas | `routes/publico/promocion.php` (`publico.promocion.*`): solo si un contrato externo llama a category3 (hoy ninguno; los enlaces de los correos van a Mi página, S4) |
| Controladores | `app/Http/Controllers/Admin/Promotions/` · Form Requests en `app/Http/Requests/Promotions/` |
| Servicios | `app/Services/Promotions/` |
| Modelos | `app/Models/Promotions/` (un modelo por tabla legacy, §2.1) |
| Vistas | `resources/views/admin/promocion/<carpeta legacy>/<archivo>.blade.php` (misma estructura que `system/category3`) |
| Menú | `config/menu/promocion.php` (mismas entradas que `system/inc/left_navi3.php`) |
| Scheduler y bindings | `app/Providers/PromocionServiceProvider.php` |
| Pruebas | `tests/Feature/Promotions/`, `tests/Unit/Promotions/` |

Estado: el código de promociones de CRMSystem se borró en `laravel13`; **todas las filas están `pendiente`** o `descartado`.

---

## 1. Tabla de paridad

`L` = lee, `E` = escribe. Rutas sin el prefijo `admin.promocion.`. Abreviaturas: `MC` = `MailController`, `RC` = `RuleController` (pantallas propias de auto/follow/reminder), `TC` = `TemplateController`.

### 1.1 Mensajería (03a)

Las familias (メルマガ, デコメール, 自動, フォロー, リマインダ, cupón, encuesta) comparten el motor del legacy (`category3/inc/mail_*_frm.php`): en Laravel, un `MailController` con el parámetro `mail_type` (`''`, `deco`, `auto`/`autodeco`, `follow`/`followdeco`, `yoyaku`/`remdeco`, `coupon`, `anketo`, `push`) y los mismos parciales Blade.

#### お知らせ配信管理 — `mail_mag/`

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas (L/E) | Estado |
|---|---|---|---|---|---|
| `mail_customer_select.php` | 発行条件選択 (メール) | `mail.select` | `MC@select` | L tb_customer, tb_search_form, tb_search_cate, tb_koumoku | pendiente |
| `mail_new.php` | 新規メール配信 | `mail.create` | `MC@create` | L tb_mail_template, tb_shop | pendiente |
| `mail_new_confirm.php` | (確認) | `mail.confirm` (POST) | `MC@confirm` | L tb_customer, tb_mycoupon_master | pendiente |
| `mail_new_send.php` | (登録) | `mail.store` | `MC@store` | E mailmag_master, send_schedule, hatuban | pendiente |
| `mail_future_list.php` | 予定配信お知らせ一覧 | `mail.future` | `MC@future` | L mailmag_master | pendiente |
| `mail_future.php` | 予定配信メール編集 | `mail.edit` | `MC@edit` | L mailmag_master | pendiente |
| `mail_future_test.php` | (編集, variante) | — | — | — | descartado: duplicado de `mail_future.php` |
| `mail_future_confirm.php` | (確認) | `mail.edit.confirm` | `MC@confirmEdit` | L mailmag_master | pendiente |
| `mail_future_send.php` | (登録) | `mail.update` | `MC@update` | E mailmag_master, send_schedule | pendiente |
| `mail_kaijo.php` | 予約配信メール 配信解除 | `mail.cancel` | `MC@cancel` | E mailmag_master (DELETE físico, como el legacy), send_schedule; tb_c3_anketo_cate (anketo) | pendiente |
| `mail_past.php` | 過去配信お知らせ一覧 | `mail.past` | `MC@past` | L mailmag_master | pendiente |
| `mail_past_shousai.php` | 過去配信詳細 | `mail.show` | `MC@show` | L mailmag_master, tb_mailmag_analyze | pendiente |
| `mail_sended_list.php` (+ CSV) | 配信先一覧 | `mail.recipients`, `mail.recipients.csv` | `MC@recipients`, `MC@recipientsCsv` | L mailmag_sended_log, tb_customer, admin_master | pendiente |
| `mailmag_analyze.php` | クリックカウンタ | `mail.analyze` | `AnalyzeController@index` | L tb_mailmag_analyze, mailmag_master | pendiente |
| `mail_template.php` | テンプレート管理 | `templates.index` | `TC@index` | L tb_mail_template, tb_mail_template_category | pendiente |
| `mail_template_entry.php`, `mail_template_register.php` | テンプレート登録 | `templates.create/store/edit/update/destroy` | `TC@*` | E tb_mail_template, hatuban | pendiente |
| `mail_template_category.php`, `_category_register.php` | テンプレートカテゴリ管理 | `template-categories.*` | `TemplateCategoryController@*` | E tb_mail_template_category (orden por `rank`), hatuban | pendiente |
| `ajax_mail_template.php`, `ajax_mail_template_category.php` | (AJAX) | `templates.ajax`, `template-categories.ajax` | `TC@ajax`, `TemplateCategoryController@ajax` | E tb_mail_template, tb_mail_template_category | pendiente |
| `test_mail_address.php`, `test_mail_address_register.php` | テストお知らせアドレス管理 | `test-addresses.*` | `TestAddressController@*` | E tb_test_mail_address, hatuban; L tb_staff | pendiente |
| `ajax_test_mail_address_new.php`, `ajax_test_mail_address_staff.php` | (AJAX) | `test-addresses.ajax-new`, `test-addresses.ajax-staff` | `TestAddressController@ajaxNew`, `@ajaxStaff` | E tb_test_mail_address; L tb_staff | pendiente |
| `base.php`, `base_mailmaga.php`, `base_mycolle.php`, `base_point.php` | メールマガジン基本情報 / マイコレ登録用QR / メルマガ用QR | `base.show?type=` | `BaseController@show` | L tb_shop, tb_customer | pendiente |
| `qr_img.php`, `qr_print_pdf.php` | QR / QR印刷 | `base.qr` (PNG), `base.qr-print` (PDF) | `BaseController@qr`, `@print` | — | pendiente |
| `form_config.php`, `form_config_register.php` | 表示・検索・CSV項目設定 | — | — | — | descartado: configuración de clientes (category1), la cubre S2 |
| `mag_customer_config.php` | メール配信条件選択 | — | — | — | descartado: resto solo referenciado desde category1, sin menú |
| `mailsoushin.php` | (CLI envío inmediato) | — | `MailSender` (servicio, §3) | E mailmag_master, mailmag_sended_log, tb_customer, tb_mycoupon | descartado: no es vista; lo reemplaza el servicio |
| `mail_sche_send.php` | (CLI cron) | — | comando `promocion:send-mail` (§3) | E send_schedule, mailmag_master | descartado: no es vista; lo reemplaza el Scheduler |
| `send_schedule.php` | (include viejo) | — | — | — | descartado: versión antigua sin uso |
| `inc/disp_pic.php`, `inc/upload.php`, `inc/furiwake_disp.php` | (fragmentos) | `mail.upload` | `MC@upload` | — (archivos en Storage, mismas rutas `uploads/`) | pendiente |
| `mail_new1/2`, `mail_future1/2/3`, `mail_past1`, `mail_list`, `mail_detail`, `mail_revise`, `mail_schedule`, `send`, `mm_add*`, `base2`, `aterna_a4_12` (mail_mag) | (varios) | — | — | — | descartado: muertos/rotos según el análisis |

#### デコメール管理 — `decomail/`

Mismas pantallas con `mail_type = deco` (`$tb_base_system_child_cd = 32`); cuerpo HTML con TinyMCE.

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Estado |
|---|---|---|---|---|---|
| `mail_customer_select.php` | 新規デコメール配信 (発行条件選択) | `deco.select` | `MC@select` (`deco`) | L tb_customer, tb_search_* | pendiente |
| `mail_new.php`, `mail_new_confirm.php`, `mail_new_send.php` | 新規デコメール | `deco.create/confirm/store` | `MC@*` | E mailmag_master, send_schedule, hatuban | pendiente |
| `mail_future_list.php`, `mail_future.php`, `mail_future_confirm.php`, `mail_future_send.php` | 予定配信デコメール一覧 | `deco.future/edit/edit.confirm/update` | `MC@*` | E mailmag_master, send_schedule | pendiente |
| `mail_past.php`, `mail_past_shousai.php`, `mail_sended_list.php` | 過去配信メール一覧 | `deco.past/show/recipients` | `MC@*` | L mailmag_master, tb_mailmag_analyze, mailmag_sended_log | pendiente |
| `mailmag_analyze.php` | クリックカウンタ | `deco.analyze` | `AnalyzeController@index` | L tb_mailmag_analyze | pendiente |
| `mail_kaijo.php` | 配信解除 | `deco.cancel` | `MC@cancel` | E mailmag_master, send_schedule | pendiente |
| `mailsoushin.php`, `mail_sche_send.php` | (CLI) | — | — | — | descartado: copia del motor; un solo `MailSender` |
| `inc/qdmail.php` | — | — | — | — | descartado: librería sin uso |
| `js/tiny_mce/` | — | — | — | — | descartado: no es vista; se copia tal cual a `public/legacy/` (§2b) |

#### 自動お知らせ管理 — `auto/`

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Estado |
|---|---|---|---|---|---|
| `auto_new.php` | 自動お知らせ新規作成 | `auto.create` | `RC@create` (`auto`) | L tb_office; push: push_follow_master | pendiente |
| `auto_new_confirm.php` | (確認) | `auto.confirm` | `RC@confirm` | — | pendiente |
| `auto_mail_customer_select.php`, `auto_mail_new.php`, `auto_mail_new_confirm.php`, `auto_mail_new_send.php` | 自動 / 配信条件選択 | `auto.mail.select/create/confirm/store` | `MC@*` (`auto`/`autodeco`) | E mailmag_master (`type_flg`, `send_parameter`), send_schedule, hatuban | pendiente |
| `auto_mail_future_list.php` | 自動お知らせ設定一覧 | `auto.future` | `MC@future` | L mailmag_master | pendiente |
| `auto_mail_future.php`, `_confirm.php`, `_send.php` | (編集) | `auto.edit/edit.confirm/update` | `MC@*` | E mailmag_master, send_schedule | pendiente |
| `mail_kaijo.php` | 配信解除 | `auto.cancel` | `MC@cancel` | E mailmag_master, send_schedule | pendiente |
| `inc/disp_pic.php`, `upload.php` | — | `mail.upload` | `MC@upload` | — | pendiente |

#### フォローお知らせ管理 — `follow/`

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Estado |
|---|---|---|---|---|---|
| `follow_new.php` | フォローお知らせ新規作成 | `follow.create` | `RC@create` (`follow`) | L tb_shop, tb_search_cate | pendiente |
| `follow_new_confirm.php` | (確認: clientes que encajan hoy) | `follow.confirm` | `RC@confirm` | L tb_customer, tb_customer_sub, tb_raiten_rireki, tb_yoyaku | pendiente |
| `follow_mail_*` (select, new, confirm, send) | フォロー | `follow.mail.*` | `MC@*` (`follow`/`followdeco`) | E mailmag_master, send_schedule | pendiente |
| `follow_mail_future_list.php` | フォローお知らせ設定一覧 | `follow.future` | `MC@future` | L mailmag_master | pendiente |
| `mail_future_list.php` (follow) | (variante antigua) | — | — | — | descartado: duplicado |
| `follow_mail_future*.php` (3) | (編集) | `follow.edit/edit.confirm/update` | `MC@*` | E mailmag_master, send_schedule | pendiente |
| `mail_kaijo.php`, `inc/*` | 配信解除 | `follow.cancel` | `MC@cancel` | E mailmag_master, send_schedule | pendiente |

#### リマインダお知らせ管理 — `reminder/`

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Estado |
|---|---|---|---|---|---|
| `reminder_new.php` | リマインダーメール新規作成 | `reminder.create` | `RC@create` (`yoyaku`) | L tb_shop (`pos_domain`), tb_base_system_grandchild | pendiente |
| `reminder_new_confirm.php` | (確認: reservas afectadas) | `reminder.confirm` | `RC@confirm` | L tb_yoyaku, tb_yoyaku_uketsuke_day, tb_customer, tb_staff (vía `Reservation` de S2) | pendiente |
| `reminder_mail_new*.php` (3), `reminder_mail_future*.php` (4), `mail_kaijo.php` | リマインダお知らせ設定一覧 / 編集 / 解除 | `reminder.mail.*`, `reminder.future/edit/update/cancel` | `MC@*` (`yoyaku`/`remdeco`) | E mailmag_master, send_schedule | pendiente |
| Variantes `posyoyaku`/`posremdeco` | (reserva en POS de tienda) | — | `Promotions\PosGateway` (implementación nula) | — | descartado: POS fuera de alcance |

#### プッシュ通知 — `push/`

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Estado |
|---|---|---|---|---|---|
| `push_customer_select.php` | プッシュ通知 / 発行条件選択 | `push.select` | `MC@select` (`push`) | L tb_customer, tb_search_* | pendiente |
| `push_new.php` + `push_new.js` | プッシュ通知新規作成 | `push.create` | `PushController@create` | L contents_master, tb_c1_customer, tb_c3_anketo2_cate | pendiente |
| `ajaxSavePush.php` | (AJAX guardar) | `push.ajax-save` | `PushController@save` | E push_master / push_follow_master, hatuban | pendiente |
| `ajaxChangeStatus.php` | (AJAX pausar/anular) | `push.ajax-status` | `PushController@status` | E push_follow_master (`disp_flg` 1/7/9) | pendiente |
| `ajaxSearchMember.php` | (AJAX socio de prueba) | `push.ajax-member` | `PushController@searchMember` | L tb_customer | pendiente |
| `push_complete.php` | プッシュ通知 (完了) | `push.complete` | `PushController@complete` | — | pendiente |
| `push_follow_list.php?type_flg=scheduled\|follow\|auto` | 予定 / フォロー / 自動通知設定一覧 | `push.follow-list?type_flg=` | `PushController@followList` | L/E push_follow_master | pendiente |
| `push_past.php` | 過去配信内容一覧 | `push.past` | `PushController@past` | L push_master, push_notifications | pendiente |
| `push_past_shousai.php` | 過去配信詳細 | `push.show` | `PushController@show` | L push_master, push_notifications, tb_customer | pendiente |
| `push_history.php`, `push_history_detail.php` | プッシュ履歴 | `push.history`, `push.history.show` | `PushController@history`, `@historyShow` | L push_notifications, tb_customer, tb_shop | pendiente |
| `sendFollowPush.php` | (cron 5 min) | — | comando `promocion:send-follow-push` | E push_follow_master, push_master, contents_master | descartado: no es vista; Scheduler |

#### DM発行 — `dm/`

| Legacy | Título | Ruta Laravel | Controlador@método | Tablas | Estado |
|---|---|---|---|---|---|
| `customer_list.php` | DM発行 | `dm.index` | `DmController@index` | L tb_customer, tb_koumoku | pendiente (uso por confirmar) |
| `aterna_a4_12.php` | (宛名ラベル A4×12) | `dm.labels` (PDF) | `DmController@labels` | L tb_customer (el legacy lee `kaiin_master`, inexistente: se corrige) | pendiente (uso por confirmar) |
| `japanese.php` | — | — | — | — | descartado: utilidades de texto, no vista |

#### 新着情報 — `digital_signage/` (category3)

| Legacy | Título | Ruta Laravel | Controlador@método | Tablas | Estado |
|---|---|---|---|---|---|
| `digital_signage_entry.php` | 新着情報管理 | `news.index` | `NewsController@index` | L tb_digital_signage, tb_officialposition | pendiente (uso por confirmar) |
| `digital_signage_register.php` | 新着情報登録 | `news.store` | `NewsController@store` | E tb_digital_signage, hatuban | pendiente (uso por confirmar) |

#### Motor compartido — `inc/`, `class/`, raíz

| Legacy | Tipo | Ruta Laravel | Controlador@método / servicio | Estado |
|---|---|---|---|---|
| `inc/ajax_get_template_titles.php`, `ajax_get_template_body.php` | ajax | `templates.titles`, `templates.body` | `TC@titles`, `TC@body` | pendiente |
| `inc/ajax_search_kaiin.php`, `ajax_reigai_kaiin.php` | ajax | `mail.ajax-search`, `mail.ajax-reigai` | `MC@searchCustomer`, `MC@reigai` (`振分条件`) | pendiente |
| `inc/ajax_search_coupon.php` | ajax | `coupons.ajax-search` | `CouponController@search` | pendiente |
| `inc/ajax_sendTestMail.php`, `ajax_testMailList.php` | ajax | `mail.test`, `mail.test-list` | `MC@sendTest`, `MC@testList` | pendiente |
| `inc/ajax_mail_delete.php`, `ajax_change_status.php`, `ajax_get_shop_data.php` | ajax | `mail.ajax-delete`, `mail.ajax-status`, `mail.ajax-shop` | `MC@*` | pendiente |
| `inc/csv_download.php`, `csv_shousai_download.php`, `csv_download/csv_*.php` (+ SWF) | csv | `mail.csv`, `mail.show.csv`, `mail.analyze.csv` | `StreamedResponse` SJIS-win | pendiente |
| `inc/customer_list.php`, `inc/customer_detail.php` | popup | `mail.customers` (popup); ficha: `admin.clientes.show` | `MC@customers` | pendiente (lista); ficha: S2 |
| `inc/mail_customer_select_frm.php`, `mail_frm.php`, `mail_confirm_frm.php`, `mail_send_frm.php`, `mail_future_frm.php` (+ `_confirm_frm`, `_send_frm`), `mail_future_list_frm.php`, `mailmag_list.php`, `mailmag_shousai(2).php`, `mailmag_sended_list.php`, `mailmag_graph(2).php`, `push_list.php`, `push_shousai.php`; `/system/inc/ajax_picupload.php`; envoltorios `anketo_mail_*`, `anketo_reply_*`, `mycoupon_mail_*` | fragmentos | — | parciales Blade `admin/promocion/inc/*` + servicios `MailRegistrar`, `CustomerFilter` | pendiente (dentro de sus vistas) |
| `inc/mailWordsReplace.php`, `decomailTagReplace.php`, `regextest.php` | fragmento | — | `MailRenderer` (mismas etiquetas y misma conversión デコメール) | pendiente |
| `inc/page_control.php`, `page_ctl.php`, `graph_sql.php`, `accordion_c3.php` | fragmento | — | parciales Blade (misma paginación y menú) | descartado: no son vistas |
| `class/cSendSchedule.php` | cron | — | `PromocionServiceProvider` (Scheduler) | pendiente |
| `index.php` | portada | `index` | `DashboardController@index` | pendiente |
| `process-sortable.php` | ajax | — | — | descartado: solo ordena `tb_coupon`/`tb_haikei` (cupón-imagen, descartado) |

### 1.2 Fidelización (03b)

#### アンケート管理 — `anketo/` (tablas `tb_c3_anketo_*`)

`anketo` y `anketo2` conservan sus tablas: un solo juego de controladores con el parámetro `family` (`anketo` | `anketo2`) que elige los modelos.

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Estado |
|---|---|---|---|---|---|
| `anketo_cate_list.php` | アンケート一覧 | `anketo.index` | `SurveyController@index` | L tb_c3_anketo_cate, tb_c3_anketo_sub, mailmag_master | pendiente |
| `anketo_cate_list.php?which=sended` | 過去アンケート配信一覧 | `anketo.index?which=sended` | `SurveyController@index` | idem | pendiente |
| `anketo_cate_new.php`, `_frm`, `_errchk`, `_register` | アンケート新規登録 | `anketo.create/store` | `SurveyController@create/store` | E tb_c3_anketo_cate, hatuban | pendiente |
| `anketo_cate_update.php` | アンケート修正 | `anketo.edit/update` | `SurveyController@edit/update` | E tb_c3_anketo_cate | pendiente |
| `anketo_cate_action.php` | (表示切替・並び替え・削除) | `anketo.action` | `SurveyController@action` | E tb_c3_anketo_cate, tb_c3_anketo_form | pendiente |
| `anketo_cate_detail.php` | アンケート詳細 | `anketo.show` | `SurveyController@show` | L tb_c3_anketo_cate | pendiente |
| `anketo_form_list.php` | 項目一覧 | `anketo.forms.index` | `SurveyFormController@index` | L tb_c3_anketo_form, _cate, _sub, tb_mycoupon_master, tb_mailmag_analyze, mailmag_master | pendiente |
| `anketo_form_new.php`, `anketo_form_update.php`, `_frm`, `_errchk`, `_register` | 項目新規 / 修正 | `anketo.forms.create/store/edit/update`, `anketo.forms.move` | `SurveyFormController@*` | E tb_c3_anketo_form (`rank`), hatuban | pendiente |
| `anketo_form_delete.php` | (削除) | `anketo.forms.destroy` | `SurveyFormController@destroy` | E tb_c3_anketo_form (`disp_flg = 9`) | pendiente |
| `anketo_form_detail.php` | 項目詳細 | `anketo.forms.show` | `SurveyFormController@show` | L tb_c3_anketo_form, _cate | pendiente |
| `anketo_form_issue.php` | アンケート発行 (発行条件選択) | `anketo.issue` | `MC@select` (`anketo`) | L tb_customer, tb_koumoku, tb_search_cate, tb_search_form | pendiente |
| `anketo_mail.php`, `anketo_mail_confirm.php`, `anketo_mail_send.php` + envoltorios `anketo_mail_*` | 新規メール配信 | `anketo.mail.*` | `MC@*` (`anketo`) | E mailmag_master (`type_flg='anketo'`), tb_c3_anketo_cate (`attached_cd`), hatuban | pendiente |
| `ajax_search_coupon.php` | (AJAX) | `coupons.ajax-search` | `CouponController@search` | L tb_mycoupon_master | pendiente |
| `anketo_test.php`, `anketo_test_register.php` | アンケートテスト | `anketo.test`, `anketo.test.store` | `SurveyController@test/storeTest` | E tb_c3_anketo_sub (`category_inner_group` = MAX+1) | pendiente |
| `anketo_answer_analyze.php` | 回答解析 | `anketo.analyze` | `SurveyAnswerController@analyze` | L tb_c3_anketo_cate, _form, _sub, mailmag_master | pendiente |
| `anketo_answer_shousai.php` | 回答詳細 | `anketo.answer` | `SurveyAnswerController@detail` | L tb_c3_anketo_sub | pendiente |
| `csv_answer_download.php` | 回答CSV | `anketo.csv` | `SurveyAnswerController@csv` (SJIS-win) | L tb_c3_anketo_* | pendiente |
| `anketo_analyze.php` | クリックカウンタ | `anketo.click` | `AnalyzeController@index` | L tb_mailmag_analyze | pendiente |
| `inc/page.php`, css, colorbox, `cron_schedule/cronfile` | fragmento | — | — | — | descartado: no son vistas |
| `inc/anketo_function.php` | fragmento (clases de tamaño de ítem) | — | helper de `SurveyFormController` | — | pendiente (dentro de 項目新規 / 修正) |

#### お客様アンケート管理 — `anketo2/` (tablas `tb_c3_anketo2_*`)

Vistas comunes: las mismas rutas con prefijo `anketo2.` y los modelos `Anketo2*`. Propias:

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Estado |
|---|---|---|---|---|---|
| Vistas comunes (`anketo_cate_*`, `anketo_form_*`, `anketo_mail*`, `anketo_test*`, `anketo_answer_shousai`, `csv_answer_download`, `anketo_analyze`, `ajax_search_coupon`) | お客様アンケート新規登録 / 修正 / 項目… | `anketo2.*` | idem con `family=anketo2` | tb_c3_anketo2_* | pendiente |
| `anketo_cate_list.php` (casilla 選択) | お客様アンケート一覧 | `anketo2.index` | `SurveyController@index` | L tb_c3_anketo2_cate, contents_master | pendiente |
| `ajax_set_anketo.php` | (AJAX encuesta activa) | `anketo2.ajax-set` | `SurveyController@setActive` | E contents_master (`tb_c3_anketo2` / `set_anketo_cd`) | pendiente |
| `anketo_answer_list.php` | お客様アンケート回答一覧 | `anketo2.answers` | `SurveyAnswerController@index` | L tb_c3_anketo2_*, tb_customer, tb_raiten_rireki | pendiente |
| `anketo_answer_detail.php` | 回答詳細 (por cliente) | `anketo2.answers.show` | `SurveyAnswerController@show` | L tb_c3_anketo2_*, tb_customer | pendiente |
| `anketo_reply_customer_select.php`, `anketo_reply_new.php`, `_confirm.php`, `_send.php` | 返信メール | `anketo2.reply.*` | `MC@*` (`anketo`) | E mailmag_master | pendiente |
| `anketo_answer_analyze.php` | 回答解析 | `anketo2.analyze` | `SurveyAnswerController@analyze` | L tb_c3_anketo2_* | pendiente (el legacy mezcla `tb_c3_anketo_*` en alguna consulta: se usa siempre la familia `anketo2`) |

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

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Estado |
|---|---|---|---|---|---|
| `mycoupon_new.php`, `mycoupon_update.php`, `_frm`, `_errchk` | クーポン新規登録 / 修正 | `coupons.create/edit` | `CouponController@create/edit` | L tb_shop, tb_base_system_child/grandchild, contents_master | pendiente |
| `mycoupon_register.php` | (登録) | `coupons.store/update` | `CouponController@store/update` | E tb_mycoupon_master, contents_master (premio くじ), hatuban | pendiente |
| `mycoupon_list.php` | クーポン一覧 | `coupons.index` | `CouponController@index` | L tb_mycoupon_master, tb_shop | pendiente |
| `mycoupon_detail.php` | クーポン詳細 | `coupons.show` | `CouponController@show` | L tb_mycoupon_master, tb_mycoupon_used, tb_customer, tb_shop, mailmag_master | pendiente |
| `mycoupon_delete.php` | (削除) | `coupons.destroy` | `CouponController@destroy` | E tb_mycoupon_master (`disp_flg = 9`) | pendiente |
| `mycoupon_issue.php` | クーポン発行 (発行条件選択) | `coupons.issue` | `CouponIssueController@select` | L tb_customer, tb_search_cate/_form, tb_koumoku | pendiente |
| `mycoupon_issue_detail.php` | 発行内容確認 | `coupons.issue.confirm` | `CouponIssueController@confirm` | L tb_mycoupon_master, tb_shop | pendiente |
| `customer_list.php` | 顧客一覧 (popup) | `coupons.issue.customers` | `CouponIssueController@customers` | L tb_customer, tb_raiten_rireki | pendiente |
| `mycoupon_hakkou.php` | クーポン発行 (実行) | `coupons.issue.store` | `CouponIssueController@store` | E tb_mycoupon, hatuban | pendiente (filtro validado en vez de `$_SESSION['wherestr']`) |
| `mycoupon_create.php`, `mycoupon_complete.php` | クーポン発行完了 | `coupons.issue.complete` | `CouponIssueController@complete` | L tb_mycoupon | pendiente |
| `mycoupon_rireki.php` | クーポン履歴 | `coupons.history` | `CouponStatsController@history` | L tb_mycoupon, tb_mycoupon_master, tb_mycoupon_used, tb_shop | pendiente |
| `mycoupon_ranking.php` | 使用ランキング | `coupons.ranking` | `CouponStatsController@ranking` | L tb_mycoupon_master, tb_mycoupon_used, tb_shop, mailmag_master | pendiente |
| `mycoupon_stats.php` | クーポン利用数確認 | `coupons.stats` | `CouponStatsController@index` | L tb_mycoupon_master, tb_mycoupon_used, tb_shop | pendiente |
| `mycoupon_stats_detail.php` | クーポン利用詳細 | `coupons.stats.show` | `CouponStatsController@show` | L tb_customer, tb_mycoupon_used, tb_shop | pendiente |
| `mycoupon_analyze.php` | クリックカウンタ | `coupons.click` | `AnalyzeController@index` | L tb_mailmag_analyze | pendiente |
| `mycoupon_mail*.php` (7) | 新規メール配信 (cupón) | `coupons.mail.*` | `MC@*` (`coupon`) | E mailmag_master; tb_mycoupon (al enviar) | pendiente |
| `mail_past.php`, `mail_past_shousai.php`, `mail_sended_list.php` | 過去クーポン配信一覧 / 詳細 | `coupons.mail.past/show/recipients` | `MC@*` | L mailmag_master, tb_mailmag_analyze, mailmag_sended_log | pendiente |
| `inc/page_control.php`, `js/user.js`, `cron_schedule/cronfile` | fragmento | — | — | — | descartado: no son vistas |

#### ポイント管理 — `point/` + `class/Point.php`

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Estado |
|---|---|---|---|---|---|
| `point_config.php` (8 formularios) | ポイント仕様管理 | `points.config`, `points.config.update?form=` | `PointConfigController@edit/update` | L/E admin_master, tb_shop (columnas serializadas), tb_customer_group, contents_master, point_shikkou_alert; L tb_customer_point, tb_customer | pendiente |
| `ajax_point_yukoukigen_kakunin.php` | (AJAX afectados) | `points.ajax-expiry` | `PointConfigController@expiryPreview` | L tb_customer, tb_customer_point, admin_master | pendiente |
| `point_rules_list.php` | ポイントルール一覧 | `point-rules.index` | `PointRuleController@index` | L tb_point_rules, tb_shop | pendiente |
| `point_rules_new.php`, `point_rules_update.php`, `_frm`, `_errchk`, `_register` | ポイントルール新規 / 修正 | `point-rules.create/store/edit/update` | `PointRuleController@*` | E tb_point_rules, hatuban; L tb_kingaku_rank_master, tb_raiten_rank_master | pendiente |
| `point_rules_delete.php` | (削除) | `point-rules.destroy` | `PointRuleController@destroy` | E tb_point_rules (DELETE físico, como el legacy) | pendiente |
| `point_aggregate.php`, `ajax_point_aggregate.php` | ポイント集計 | `points.aggregate`, `points.aggregate.ajax` | `PointAggregateController@index/data` | L tb_point_log, tb_shop | pendiente |
| `csv_grant_points.php` | ポイント一括付与 | `points.grant-csv` (GET/POST), `points.grant-csv.base` | `PointGrantController@*` | L tb_customer; E tb_customer_point, tb_point_log | pendiente |
| `point_alert_mail_template_entry.php`, `_register.php` | ポイント有効期限切れ前通知 テンプレート | `points.alert-template` | `PointTemplateController@alert` | L/E admin_master, contents_master | pendiente |
| `point_shikkou_mail_template_entry.php`, `_register.php` | ポイント失効通知 テンプレート | `points.shikkou-template` | `PointTemplateController@shikkou` | L/E admin_master | pendiente |
| `point_shikkou_alert_mail_template_entry.php`, `_register.php` | 失効前通知 テンプレート (múltiple) | `points.shikkou-alert-template` | `PointTemplateController@shikkouAlert` | L/E point_shikkou_alert | pendiente |
| `sendPointShikkouMail.php`, `sendPointShikkouAlertMail.php`, `sendPointShikkouAlertMailMultiple.php` | (cron) | — | comandos `promocion:point-*` | E tb_customer_point, tb_point_log, push_master | descartado: no son vistas; Scheduler |
| `setRegisterPoint.php`, `setPaidMemberPoint.php` | (cron) | — | comandos `promocion:register-point`, `promocion:paid-member-point` | E tb_customer_point, tb_point_log, push_master | descartado: no son vistas; Scheduler |
| `mailCommon.php`, `inc/page.php` | fragmento | — | `LoyaltyMail` (Mailable) | — | descartado: no son vistas |

#### スタンプ管理 — `stamp/`

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Estado |
|---|---|---|---|---|---|
| `stamp_pattern.php`, `stamp_pattern_register.php` | スタンプデザイン管理 | `stamps.pattern`, `stamps.pattern.update` | `StampPatternController@*` | L/E tb_stamp_pattern | pendiente |
| `stamp_config.php`, `stamp_config_register.php` | スタンプ仕様管理 | `stamps.config`, `stamps.config.update` | `StampConfigController@*` | L/E tb_stamp_config, admin_master, tb_shop, tb_customer (`stamp_yukoukigen`), hatuban | pendiente |
| `ajax_stamp_shikkou_kakunin.php` | (AJAX afectados) | `stamps.ajax-expiry` | `StampConfigController@expiryPreview` | L tb_customer | pendiente |
| `stamp_rules_list.php` | スタンプルール一覧 | `stamp-rules.index` | `StampRuleController@index` | L tb_stamp_rules, tb_shop | pendiente |
| `stamp_rules_new.php`, `stamp_rules_update.php`, `_frm`, `_errchk`, `_register` | スタンプルール新規 / 修正 | `stamp-rules.create/store/edit/update` | `StampRuleController@*` | E tb_stamp_rules, hatuban | pendiente |
| `stamp_rules_delete.php` | (削除) | `stamp-rules.destroy` | `StampRuleController@destroy` | E tb_stamp_rules | pendiente |
| `oshirase_coupon.php`, `ajax_oshirase_coupon.php`, `oshirase_coupon_register.php` | クーポンお知らせ機能管理 | `stamps.oshirase`, `stamps.oshirase.ajax`, `stamps.oshirase.store` | `StampOshiraseController@*` | L/E tb_stamp_oshirase_coupon; L tb_mycoupon_master, contents_master | pendiente |
| `stamp_alert_mail_template_entry/_register.php` | スタンプ失効前通知 テンプレート | `stamps.alert-template` | `StampTemplateController@alert` | L/E admin_master, contents_master | pendiente |
| `stamp_shikkou_mail_template_entry/_register.php` | スタンプ失効通知 テンプレート | `stamps.shikkou-template` | `StampTemplateController@shikkou` | L/E admin_master | pendiente |
| `sendStampShikkouMail.php`, `sendStampShikkouAlertMail.php` | (cron) | — | comandos `promocion:stamp-*` | E tb_stamp_log, tb_customer | descartado: no son vistas; Scheduler |
| `inc/page.php` | fragmento | — | — | — | descartado: no es vista |

#### スマトピ連携 — `smatopi/`

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Estado |
|---|---|---|---|---|---|
| `smatopi_message.php`, `ajax_smatopiMessage.php`, `ajax_smatopiLine.php` | スマトピ / メッセージ作成 | `smatopi.message`, `smatopi.ajax-message`, `smatopi.ajax-line` | `SmatopiController@*` → `SmatopiClient` | E smatopi_message; L tb_shop, admin_master (`smatopi_flg`, `domain_smatopi_id`) | pendiente (uso por confirmar) |
| `smatopi_sent_history.php`, `ajax_smatopiSentHistory.php` | スマトピ / メッセージ送信履歴 | `smatopi.history`, `smatopi.ajax-history` | `SmatopiController@history/historyData` | L smatopi_message | pendiente (uso por confirmar) |

#### 来店履歴管理 — `visit/`

| Legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas | Estado |
|---|---|---|---|---|---|
| `visit_stats2.php` | 来店履歴一覧 | `visit.stats` | `VisitStatsController@index` | L tb_raiten_rireki, tb_customer, tb_shop | pendiente |
| `day_visit_list.php` | 来店履歴一覧 (日別) | `visit.day` | `VisitStatsController@day` | idem | pendiente |
| `store_visit_list.php` | 個別来店履歴 | `visit.store-list` | `VisitStatsController@shop` | idem | pendiente |
| `csv_download.php`, `storecsv_download.php`, `daycsv_download.php` | CSV de visitas | `visit.csv`, `visit.store-csv`, `visit.day-csv` | `VisitStatsController@*Csv` (SJIS-win) | L tb_raiten_rireki, tb_shop | pendiente (`daycsv_download.php` está vacío en el legacy: se implementa igual que los otros) |
| `customer_detail.php` | 顧客登録情報 | enlace a `admin.clientes.show` | — | — | descartado: es la ficha de cliente de S2 |
| `touch_config.php`, `touch_config_register.php` | カードリーダ設定 | `visit.touch-config` | `TouchConfigController@*` | L/E tb_touch_mode, hatuban | pendiente (intervalo del lector de tarjetas de visita) |
| `visit_stats.php`, `new_visit_stats.php`, `stats_test*.php`, `csv_test*.php` | (copias antiguas) | — | — | — | descartado: duplicados/pruebas |
| `fix_visit_stats.php` | (corrección masiva) | — | — | — | descartado: script peligroso, bloqueado a 403 |

#### 予約管理 — `reservation/` (category3)

Pasa a S2 (decisión de S0): mismas tablas (`tb_reserve`, `tb_reserve_name`, `tb_koumoku`, `tb_change_model`) y su UI.

| Legacy | Título (ja) | Estado |
|---|---|---|
| `reserve_customer_search.php` | 予約新規登録 / 顧客一覧・検索 | S2 |
| `reserve_new.php`, `reserve_new_confirm.php`, `reserve_new_register.php`, `reserve_new_complete.php` | 予約新規登録 / 確認 / 完了 | S2 |
| `reserve_calendar.php` | 予約・来店一覧 | S2 |
| `reserve_list.php` | 予約一覧 | S2 |
| `reserve_detail.php`, `reserve_change_status.php`, `reserve_delete.php` | 予約詳細 / 予約情報 | S2 |
| `category_entry.php`, `category_register.php`, `category_delete.php` | 予約カテゴリ登録・変更 | S2 |
| `config.php`, `config_register.php` | 予約項目設定 | S2 |
| `ajax_category.php`, `ajax_staff.php`, `inc/check_select.php` | (AJAX) | S2 |
| `model_reservation.php` | 予約機種一覧 | S2 |

---

## 2. Modelos sobre la BD del legacy

Reglas (CONVENCIONES §0, §4): `protected $table`, `protected $primaryKey = 'cd'`, `public $incrementing = false` (el `cd` sale de `hatuban`), `public $timestamps = false` (las fechas `touroku_date`/`update_date` son enteros UNIX o texto según la tabla y las rellena un trait), columnas tal cual (también las japonesas de `mailmag_master`), borrado lógico `disp_flg = '9'` con scope global, banderas con constantes. Traits que se piden a S1: `HasHatuban`, `LegacySoftDeletes`, `LegacyTimestamps`.

**Ninguna tabla nueva.** Todo cabe en el legacy: contadores de envío en `mailmag_master`, clics en `tb_mailmag_analyze`, aperturas en `push_notifications`, intentos del sorteo en `tb_kujibiki_count`, configuración en `admin_master`, `contents_master` y `tb_shop`.

### 2.1 Modelos de S3 (`App\Models\Promotions\*`)

| Modelo | Tabla | Relaciones | Notas |
|---|---|---|---|
| `MailmagMaster` | `mailmag_master` | `sendedLogs()` hasMany MailmagSendedLog (`mailmag_master_cd`); `clicks()` hasMany MailmagAnalyze; `schedule()` hasOne SendSchedule (`mag_cd`); `coupon()` belongsTo MycouponMaster (`type_data_cd`, `type_flg = coupon`); `anketo()` belongsTo AnketoCate/Anketo2Cate (`type_data_cd`, `type_flg = anketo`) | Columnas japonesas (`店id`, `タイトル`, `pc内容`, `配信日時`, `配信時間`, `配信状況`, `振分配信`, `振分条件`, `*配信数`…). Constantes de `type_flg` y `配信状況` (1 pendiente, 2 enviado). Lo lee g-api |
| `SendSchedule` | `send_schedule` | `mail()` belongsTo MailmagMaster (`mag_cd`) | Se escribe como el legacy; lo lee el Scheduler |
| `MailmagSendedLog` | `mailmag_sended_log` | `mail()`, `customer()` (`tb_customer_cd`) | Sin PK: solo inserción y lectura |
| `MailmagAnalyze` | `tb_mailmag_analyze` | `mail()`, `customer()` | Clics con edad/sexo/ocupación del momento |
| `MailTemplate` | `tb_mail_template` | `category()` belongsTo MailTemplateCategory | `hyouji_flg`, `rank`, `mail_type` |
| `MailTemplateCategory` | `tb_mail_template_category` | `templates()` | Orden por `rank` |
| `TestMailAddress` | `tb_test_mail_address` | `shop()` | |
| `PushMaster` | `push_master` | `followMaster()` belongsTo PushFollowMaster; `notifications()` hasMany PushNotification (`type_master_cd`, `type = 'push'`) | Lo lee g-api |
| `PushFollowMaster` | `push_follow_master` | `pushes()` hasMany PushMaster | `disp_flg` 1 activo, 7 pausa, 9 borrado |
| `PushNotification` | `push_notifications` | `customer()`; `push()` o `mail()` según `type` | `open_cnt`, `open_date` |
| `DigitalSignageNews` | `tb_digital_signage` | — | 新着情報 de category3 |
| `AnketoCate`, `AnketoForm`, `AnketoSub` | `tb_c3_anketo_cate`, `_form`, `_sub` | cate hasMany form (`tb_c3_anketo_cate_cd`) y sub; form hasMany sub (`tb_c3_anketo_form_cd`); cate `coupon()` (`attached_cd`) | Una respuesta = grupo de `sub` por `category_inner_group` |
| `Anketo2Cate`, `Anketo2Form`, `Anketo2Sub` | `tb_c3_anketo2_*` | igual (las FK se llaman `tb_c3_anketo_cate_cd`/`_form_cd`, sin "2") | Trait `AnketoFamily` común |
| `MycouponMaster` | `tb_mycoupon_master` | `issued()` hasMany Mycoupon; `used()` hasMany MycouponUsed; `shop()` | Constantes de `coupon_kubun` y `limit_flg` |
| `Mycoupon` | `tb_mycoupon` | `master()`, `customer()`, `shop()` | Cupón entregado; al usarse se copia a `tb_mycoupon_used` y se borra (como `appview/coupon_control.php`) |
| `MycouponUsed` | `tb_mycoupon_used` | `master()`, `customer()`, `shop()` | |
| `PointRule` | `tb_point_rules` | `shop()` | `type_flg` 1–5; `start_time`/`end_time` (HHMM o timestamp según tipo); rangos en texto `cd,cd` |
| `PointLog` | `tb_point_log` | `customer()`, `shop()`, `staff()`, `terminal()`, `push()` | Constantes de `action_flg` (`crm:newRegist`, `mycolle:*`…) |
| `CustomerPoint` | `tb_customer_point` | `customer()` (`tb_customer_cd`) | Saldo `service_point`, `point_yukoukigen` |
| `PointShikkouAlert` | `point_shikkou_alert` | `shop()` | Avisos múltiples |
| `KingakuRankMaster`, `RaitenRankMaster` | `tb_kingaku_rank_master`, `tb_raiten_rank_master` | — | Rangos; las tareas de rango son de S3 |
| `CustomerGroup` | `tb_customer_group` | `customers()` (modelo de S2) | S2 lo usa en 顧客グループ設定 sin cambiarlo |
| `StampConfig` | `tb_stamp_config` | `shop()` | |
| `StampRule` | `tb_stamp_rules` | `shop()` | Mismos tipos que `tb_point_rules` |
| `StampPattern` | `tb_stamp_pattern` | — | 79 diseños |
| `StampLog` | `tb_stamp_log` | `customer()`, `shop()` | |
| `StampOshiraseCoupon` | `tb_stamp_oshirase_coupon` | `coupon()` (`tb_mycoupon_master_cd`, texto) | |
| `KujibikiCount` | `tb_kujibiki_count` | `customer()` | Intentos del día (`int_data`) |
| `KujibikiLog` | `tb_kujibiki_log` | `customer()`, `coupon()` | Tiradas |
| `TouchMode` | `tb_touch_mode` | `shop()` | カードリーダ設定 |
| `SmatopiMessage` | `smatopi_message` | `shop()` | PK `cd` serial (sin `hatuban`) |

### 2.2 Modelos de otros que S3 lee o escribe

| Modelo (dueño) | Tabla | Uso de S3 |
|---|---|---|
| `Customer` (S2) | `tb_customer` | Lee filtros y datos de correo. **Escribe** vía el modelo de S2 (sin cambiarlo): `error_send_cnt`, `mailmaga_flg = '9'` (rebotes), `stamp_count`, `stamp_yukoukigen`, rangos actuales |
| `CustomerSub`, visitas (S2) | `tb_customer_sub`, `tb_raiten_rireki` | Lectura (etiquetas, follow, estadísticas) |
| `Reservation` (S2) | `tb_yoyaku` | Recordatorio: scope `remindable()` |
| Filtros (S2) | `tb_search_form`, `tb_search_cate`, `tb_koumoku` | 発行条件選択 |
| `Shop`, `Staff`, `Terminal`, `Office` (S1) | `tb_shop`, `tb_staff`, `tb_terminal`, `tb_office` | Lectura; ポイント仕様管理 **escribe** columnas de puntos/sellos de `tb_shop` vía el modelo de S1 |
| `AdminMaster`, `ContentsMaster`, `Hatuban`, `BaseSystemChild/Grandchild` (S1) | `admin_master`, `contents_master`, `hatuban`, `tb_base_system_*` | Configuración de puntos/sellos/envío y plantillas de aviso (lectura y escritura vía modelos de S1); numeración; menú dinámico |
| `LotterySetting`/`LotteryPrize` (S4) | `contents_master` (`tb_c6_kujibiki`) | Configuración del sorteo, solo lectura |

---

## 2b. UI: HTML, CSS y JS del legacy

Cada pantalla se pasa a Blade con el mismo HTML (clases, ids, textos japoneses) sobre el layout de S1 (cabecera y `left_navi3`). Estáticos: los comunes (`system/css|js|img`, incluido `Chart.min.js`) los copia S1; los de category3 están en `public/legacy/system/category3/` (copiados por S3: `css/`, `js/`, `img/`, `mail_mag/img|js`, `anketo*/css|js`, `decomail/js/tiny_mce/`, `digital_signage/js`, `mycoupon/js`, `push/push_new.js`, `smatopi/`; sin Flash, sin `excanvas`/`iepngfix`, sin `jquery.gchart`, sin los datos de la librería QR ni subidas). colorbox y smatopi también los usa S2. Los parciales `category3/inc/*_frm.php` pasan a `resources/views/admin/promocion/inc/` y los usan todas las familias.

| Submódulo | CSS (además de los del layout) | JS del legacy | Notas |
|---|---|---|---|
| mail_mag, decomail, auto, follow, reminder, inc | `import.css`, `popInline.css`, `colorbox.css`, `myFlexSelect.css`, `popupwindow.css`, `ui.datepicker.css` | jQuery + jQuery UI (las del layout de S1), `default.js`, `scrollsmoothly.js`, `popInline.js`, `ajaxupload-3.5.js`, `staff_entry.js`, `user.js`, `formToWizard.js`, `auto_mail_select.js`, `reigai_kaiin.js`, `reigai_kaiin2.js`, `testMail.js`, `jquery.colorbox.js`, `jquery.maskedinput-1.2.2.js`, `myFlexSelect.js`, `popupwindow-1.8.js`, `ui.datepicker-ja.js`, `gcalendar-holidays.js`, `circle.js`, `Chart.min.js`; `decomail/js/tiny_mce/` (TinyMCE 3) | Sobran `excanvas.js` e `iepngfix.js` (IE). El SWF de descarga CSV (`csv_download/sample.swf`, `AC_RunActiveContent.js`) se cambia por un enlace normal |
| push | `colorbox.css`, `myFlexSelect.css`, `popupwindow.css`, `ui.datepicker.css` | `push_new.js`, `ajaxupload-3.5.js`, `gcalendar-holidays.js`, `jquery.colorbox.js`, `myFlexSelect.js`, `popupwindow-1.8.js`, `ui.datepicker-ja.js` | `push_new.js` envía `sessId`: se cambia por el token CSRF |
| dm | `import.css` | — | `AC_RunActiveContent.js` (Flash) sobra |
| digital_signage (新着情報) | `import.css` | `ajaxupload-3.5.js`, `digital_signage.js` | |
| anketo, anketo2 | `import.css`, `colorbox.css`, `popupwindow.css` | `default.js`, `circle.js`, `jquery.colorbox.js`, `jquery.maskedinput-1.2.2.js`, `popupwindow-1.8.js`, `scrollsmoothly.js` | |
| mycoupon | `import.css`, `myFlexSelect.css`, `popupwindow.css`, `ui.datepicker.css` | `ajaxupload-3.5.js`, `default.js`, `gcalendar-holidays.js`, `jquery.maskedinput-1.2.2.js`, `myFlexSelect.js`, `popupwindow-1.8.js`, `shopping_entry.js`, `staff_entry.js`, `ui.datepicker-ja.js`, `mycoupon/js/user.js` | |
| point | `import.css`, `jquery.alerts.css`, `popInline.css`, `ui.datepicker.css` | `default.js`, `jquery.alerts.js`, `popInline.js`, `tiny_mce.js`, `ui.datepicker-ja.js` | `point_aggregate.php` lleva su JS en línea (AJAX) |
| stamp | `import.css`, `popInline.css` | `ajaxupload-3.5.js`, `default.js`, `jquery.maskedinput-1.2.2.js`, `popInline.js`, `shopping_entry.js`, `tiny_mce.js` | Imágenes de los 79 diseños: el legacy las lee de `hp/uploads/c4/stamp/<cd>.gif` (no están en `crm2-php8`: llegan con los archivos de producción a Storage, ruta `/hp/uploads/…` de S1) |
| smatopi | `destyle.css`, `common.css`, `all.min.css`, `jquery-ui.min.css` (propios) | jQuery 1.11.3 + `jquery-ui-dialog-compat.js`, `jquery.ui.datepicker-ja.js`, `smatopiCommon.js`, `smatopiMessage.js`, `smatopiSentHistory.js` | Pantallas en pestaña nueva, sin layout |
| visit | `import.css`, `colorbox.css`, `popupwindow.css` | `default.js`, `circle.js`, `jquery.colorbox.js`, `jquery.maskedinput-1.2.2.js`, `popupwindow-1.8.js`, `jquery.gchart.min.js` | `jquery.gchart` usa la API de imágenes de Google Charts (retirada): mismo gráfico con `Chart.min.js` del legacy |

---

## 3. Servicios viejos → Laravel 13

| Servicio viejo | Reemplazo | Notas |
|---|---|---|
| `cSendSchedule` reescribe el crontab con `exec("crontab …")` | `PromocionServiceProvider` registra las tareas en el Scheduler (un solo `schedule:run`). `send_schedule` se sigue escribiendo igual para que la BD quede como la espera el legacy | Provider del stash, reaprovechado |
| — `mail_sche_send.php` | `promocion:send-mail` cada minuto: recorre `send_schedule ⨝ mailmag_master` y, según `type_flg`, decide si toca ahora (único: `配信日時 <= ahora`; follow/yoyaku/remdeco: `HH:MM` diario con el desfase de días evaluado al enviar; auto: meses+días con 32 = fin de mes, o días de la semana). Envía con `MailSender` y pone `配信状況 = '2'` en los únicos | Parser de `send_parameter` del stash |
| — puntos (`sendPointShikkou*`, `setRegisterPoint`, `setPaidMemberPoint`) | `promocion:point-shikkou`, `promocion:point-shikkou-alert`, `promocion:register-point` (5 min), `promocion:paid-member-point` (10:00), a la hora de `admin_master` | |
| — sellos (`sendStampShikkou*`) | `promocion:stamp-shikkou`, `promocion:stamp-shikkou-alert` | |
| — rangos (`setCustomerKingakuRank`, `setCustomerRaitenRank`) | `promocion:customer-rank` a la hora de `admin_master.*_rank_shori_*` | |
| — `push/sendFollowPush.php` (5 min) | `promocion:send-follow-push` cada 5 min si プッシュ通知 está activo | |
| `exec(php mailsoushin.php …)` (envío inmediato en segundo plano) | El registro deja `send_schedule` con la hora actual y el Scheduler lo envía en el minuto siguiente (`QUEUE_CONNECTION=sync`: sin cola ni `exec`) | |
| `cMailsoushin::sendMailFromMailmagMaster` (+ copia en decomail) | `MailSender`: destinatarios con el `振分配信` guardado, filtro `mailmaga_flg`, cupón por destinatario en `tb_mycoupon` (vencimiento por `limit_flg`), `mailmag_sended_log` hasta el tope de `admin_master.mailmag_sended_log`, `error_send_cnt` y baja a los 3 errores, contadores por tipo de dirección, correo resumen メール配信完了メール + copia a `system_mail`, push espejo (`type = mailmaga`) | Un solo motor, Mailables |
| `replaceMessage` / `mailWordsReplace` / `decomailTagReplace` | `MailRenderer` (mismas etiquetas `{ID}`…, misma conversión デコメール) | |
| `addSimpleLogin` (`easy_login`) | Igual que el legacy: el enlace lleva `easy_login` y el contador de Mi página (S4) escribe `tb_mailmag_analyze` | |
| Push: `class/Push.php::exec` → `GET http://{push_api_host}/api/mpa/pushMessage?domain=…&type=…&cd=…` | `GApiPushSender`: escribe `push_master`/`push_notifications` igual que el legacy y llama al mismo endpoint con `Http::get` (host de `admin_master.push_api_host`, solo si `application_use_flg = 1`). **Con la misma BD el contrato con `b.g-api.net` no se rompe**: g-api sigue leyendo `push_master`/`mailmag_master` | Antony solo confirma si se mantiene g-api |
| `Point::setPoint` / `getPointCount` (`system/class/Point.php`) | `PointService`: alta/baja con bloqueo (`lockForUpdate` sobre `tb_customer_point`), `tb_point_log` con `shori_mae_point`/`sounyu_point`/`shouhi_point`/`action_flg`, renovación de `point_yukoukigen`. `PointRuleResolver`: prioridad 5 → 4 → 3 → 2 → 1 (día exacto antes que fin de mes), tienda antes que todas (`tb_shop_cd = -1`), `update_date DESC`, rangos por tipo, valor por defecto 1 | `PointRuleResolver` del stash, cambiando los modelos a `PointRule`/`StampRule` |
| `cKujibiki` (sorteo) | `LotteryService::draw`: intentos del día en `tb_kujibiki_count` (con `FOR UPDATE`), probabilidad y existencias desde `contents_master` (modelos de S4), `tb_kujibiki_log`, cupón en `tb_mycoupon` | Lógica del stash adaptada |
| Canje e impresión de cupón (`appview/coupon_control.php`, `digitalsignage/ajax_kaiin_mycoupon_print_crawl.php`) | `CouponService::markUsed` (copia a `tb_mycoupon_used` y borra de `tb_mycoupon`) y `markPrinted` (`print_flg`); los llaman S4 y S5 | |
| `mycoupon_hakkou.php` con `$_SESSION['wherestr']` | `CouponIssueService`: filtro validado → `tb_mycoupon` por cliente con `time_limit` calculado | |
| `振分配信` / `wherestr` como texto | Se sigue guardando como SQL (lo leen el envío y g-api), pero la arma el servidor desde un filtro validado (`CustomerFilter`); nunca llega texto del usuario | |
| Smatopi (`class/Smatopi.php`) | `SmatopiClient` con `Http::`, mismas llamadas | |
| POS (`pos_search`, `posyoyaku`, `POS_DB_PASSWORD`) | `App\Services\Promotions\PosGateway` con implementación nula enlazada en el provider | Fuera de alcance |
| CSV con Flash / `header('text/x-csv')` | `StreamedResponse` SJIS-win (helper de S1 o propio con la misma firma) | |
| CSV de subida (`csv_grant_points.php`) | Form Request (archivo, tamaño) + lectura SJIS-win como el legacy | |
| QR (`qr_img` + `.dat`) y `qr_print_pdf.php` | `bacon/bacon-qr-code` y el paquete PDF de S1 | |
| Subidas a `uploads/…` y `find -mtime +1` en `index.php` | `Storage` con las mismas rutas relativas (S1 sirve `/hp/uploads/…`) y `promocion:clean-temp` diario | |
| Permisos `myKengen[…]`, `rank == 'master'` | Policies con el nivel de S1 | |
| Menú dinámico `tb_base_system_child/grandchild` | Mismas consultas vía modelos de S1 | |

---

## 4. Orden de implementación y puntos

1 punto ≈ media jornada. Total ≈ **83 puntos** (sin base previa: todo se rehace con la UI del legacy).

### Bloque 0 — Común (4 pts)

| # | Tarea | Depende de | Pts |
|---|---|---|---|
| 0.1 | Modelos §2.1 con relaciones y pruebas de lectura sobre el esquema; `PromocionServiceProvider` (Scheduler, `PosGateway` nulo); rutas y menú; parciales Blade comunes (`inc/`); Policies base | S1: layout, `public/legacy/`, traits `HasHatuban`/`LegacySoftDeletes`/`LegacyTimestamps`, modelos `AdminMaster`/`ContentsMaster`/`Shop`/`Staff` | 4 |

### Bloque M — Mensajería (≈ 37 pts)

| # | Submódulo | Depende de | Pts |
|---|---|---|---|
| M1 | Plantillas + categorías + direcciones de prueba | 0.1 | 3 |
| M2 | メルマガ / デコメール: 発行条件選択 → redacción → confirmación → registro, prueba, imagen, 例外会員, programados, pasados, detalle, destinatarios, CSV, anulación; `MailSender`, `MailRenderer`, `promocion:send-mail` | 0.1; S2 modelos de filtro | 10 |
| M3 | 自動 / フォロー (con confirmación y conteo) | M2 | 5 |
| M4 | クリックカウンタ (gráficos por perfil y hora) y CSV | M2 | 4 |
| M5 | Push: alta, listas, pasados, historial, `GApiPushSender`, `promocion:send-follow-push` | M2, M3 | 7 |
| M6 | リマインダ (`Reservation` de S2) | M3; S2 `Reservation` | 2 |
| M7 | 基本情報 / QR (4 variantes) + impresión PDF | 0.1; S1 PDF | 2 |
| M8 | DM発行 + etiquetas PDF | S1 PDF; confirmación de uso | 1,5 |
| M9 | 新着情報 | confirmación de uso | 1 |
| M10 | Portada + `promocion:clean-temp` | 0.1 | 1,5 |

### Bloque F — Fidelización (≈ 42 pts)

| # | Submódulo | Depende de | Pts |
|---|---|---|---|
| F1 | マイクーポン: alta/edición/lista/detalle/borrado, emisión con filtro, historial, ranking, uso, envío por correo; `CouponService::markUsed/markPrinted` para S4/S5 | 0.1, M2 | 7 |
| F1b | Sorteo: `LotteryService::draw` (para Mi página de S4) | F1; S4 `LotterySetting`/`LotteryPrize` | 1,5 |
| F2 | ポイント仕様管理 (8 formularios), plantillas de aviso, vista previa de afectados | 0.1 | 6 |
| F3 | ポイントルール + `PointService`/`PointRuleResolver` | F2 | 3 |
| F4 | ポイント集計 + 一括付与 CSV + comandos de puntos y rangos | F3 | 5 |
| F5 | Sellos: diseños, 仕様, reglas, クーポンお知らせ, plantillas, comandos | 0.1, F1 | 6 |
| F6 | アンケート y お客様アンケート (dos familias, un juego de controladores): cabecera, ítems, prueba, emisión, análisis, CSV, respuestas por cliente, encuesta activa, 返信メール | 0.1, M2 | 9 |
| F7 | 来店履歴 (lista, día, tienda, CSV) + カードリーダ設定 | 0.1; S2 visitas | 3,5 |
| F8 | スマトピ (opcional) | confirmación de uso | 3 |

Orden con una sola sesión (decisión de S0): 0.1 → M1 → M2 → F1 → F2 → F3 → M3 → M4 → F5 → F6 → F4 → F7 → F1b → M6 → M7 → M10 → M5 → M8 → M9 → F8.

---

## 5. Pedidos

### A S1 (núcleo)
1. Cargar `routes/modulos/promocion.php` en el grupo `admin` y registrar `App\Providers\PromocionServiceProvider` en `bootstrap/providers.php`.
2. Layout con cabecera y `left_navi3` (menú de category3, con las condiciones de `tb_base_system_child`/`grandchild` y `admin_master.smatopi_flg`) y los estáticos de `system/css|js|images` y `category3/*/js|css` en `public/legacy/` (incluidos `decomail/js/tiny_mce/` y las imágenes de los 79 sellos).
3. Traits comunes: `HasHatuban` (numeración por `koumoku`), `LegacySoftDeletes` (`disp_flg = '9'`), `LegacyTimestamps` (UNIX entero o texto).
4. Modelos `AdminMaster`, `ContentsMaster` (por `category`/`field`), `Shop`, `Staff`, `Terminal`, `Office`, `BaseSystemChild/Grandchild`; S3 escribe columnas de puntos/sellos/envío de `admin_master` y `tb_shop` y categorías de `contents_master` a través de ellos.
5. Niveles de permiso (`cKengen`) para las Policies.
6. Paquete PDF (QR印刷, etiquetas DM); mantener `bacon/bacon-qr-code`.
7. Helper CSV SJIS-win; ruta `/hp/uploads/{path}` hacia Storage (imágenes de correos, push y 新着情報).
8. `app.timezone = Asia/Tokyo` (el legacy usa `date.timezone = Asia/Tokyo`; hoy es `UTC`). Mientras tanto, S3 interpreta sus horas con `App\Services\Promotions\LegacyTime::ZONE`; `LegacyTimestamps::legacyDate()` sí depende de `app.timezone`.
9. Guard y login sobre `Staff` (hoy `auth:admin` apunta a un modelo `Admin` que no existe), layouts `layouts.legacy`/`layouts.legacy-popup` y carga de `routes/legacy/*.php` en `routes/web.php`; unir `config/menu/promocion.php` en el menú lateral. Las pruebas de S3 usan un layout mínimo mientras tanto.

### A S2 (clientes / reservas)
1. Modelo `Reservation` (sobre `tb_yoyaku`) con scope `remindable()`.
2. Modelos de `tb_customer`, `tb_customer_sub`, `tb_raiten_rireki`, `tb_search_form`, `tb_search_cate`, `tb_koumoku`, y el armado del filtro de 発行条件選択 si S2 lo comparte con 顧客検索. S3 escribe en `tb_customer` (`error_send_cnt`, `mailmaga_flg`, `stamp_count`, `stamp_yukoukigen`, rangos) a través del modelo de S2.
3. Ficha `admin.clientes.show` para los enlaces.
4. Los bloques de búsqueda comunes de `system/inc` (`search_set1.php`, カスタマイズ検索 con `createform_search_form`, `search_rireki_set1.php`, `search_kaikei_set1.php`, `search_method_set1.php`) como un parcial Blade, y el constructor del WHERE de `search_set2.php` como servicio. S3 los incluye en 発行条件選択 (`admin/promocion/inc/mail_customer_select_frm.blade.php` recibe el nombre del parcial en `$searchFormPartial`). `search_pos_kaikei_set1.php` no: POS fuera de alcance.

### A S4 (Mi página / señalización)
1. Modelos `LotterySetting`/`LotteryPrize` sobre `contents_master tb_c6_kujibiki` (lectura para el sorteo); Mi página llama a `LotteryService::draw`.
2. El reproductor `digitalsignage` llama a `CouponService::markPrinted/markUsed` en vez de escribir `tb_mycoupon`.
3. Mi página sigue escribiendo `tb_mailmag_analyze` y `push_notifications.open_cnt` como el legacy.

### A S5 (integraciones)
1. `appview/coupon_control.php`, `member_regist.php` y `mpa_*` usan `CouponService`, `PointService` y `GApiPushSender` de S3 (misma lógica y mismas tablas).

### A S0 / Antony (confirmaciones)
1. Push: ¿se mantiene `b.g-api.net`? Con la misma BD el contrato funciona sin cambios.
2. ¿Se usan 新着情報 (`category3/digital_signage`), DM発行 y スマトピ? Si no, se descartan (≈ 5,5 pts).

---

## 6. Decisiones

- Modelos en `App\Models\Promotions\` con el nombre de la tabla legacy en PascalCase (`MailmagMaster`, `MycouponMaster`, `AnketoCate`…), para que el dueño sea evidente.
- `anketo` y `anketo2` mantienen sus tablas; un solo juego de controladores con el parámetro `family`.
- `振分配信` se sigue guardando como SQL (lo leen el envío y g-api), generada por el servidor desde un filtro validado.
- `send_schedule` se sigue escribiendo como el legacy aunque lo lea el Scheduler.
- Envío inmediato: en el minuto siguiente por el Scheduler (sin colas ni `exec`).
- El legacy reenvía el WHERE (`post_wherestr`) en campos ocultos del formulario: se quita; el WHERE vive solo en el servidor, en sesión por `search_uniqid`, como el resto del legacy.
- `submitChk2` de las direcciones de prueba usa `document.forms[...]` en vez de `eval(...)` (mismo efecto).
- Los borrados físicos del legacy (`mail_kaijo`, `point_rules_delete`) se mantienen.
- Se corrigen: `aterna_a4_12.php` (`kaiin_master` → `tb_customer`), `daycsv_download.php` vacío, `anketo2` con consultas a `tb_c3_anketo_*`, `jquery.gchart` (API de Google retirada) → `Chart.min.js`.
- Los contadores por tipo de dirección del legacy (`pc配信数`…) se siguen llenando.
- Touchcation fuera de alcance: ninguna vista de este grupo dependía solo del quiosco.
- 0.1 (implementado): 36 modelos en `App\Models\Promotions\`; `mailmag_master` y `tb_point_rules`/`tb_stamp_rules` sin borrado lógico (el legacy los borra físicamente y `mailmag_master.disp_flg` puede venir vacío).
- Sorteo: la configuración y las existencias se leen y escriben en `contents_master tb_c6_kujibiki` con el modelo `ContentsMaster` de S1, como `cKujibiki`; no hacen falta los modelos de S4 para sortear. El cupón del premio se canjea después en Mi página (el sorteo solo deja `tb_kujibiki_log`, como el legacy).
- Sorteo, corrección: el legacy reinicia el día con `mktime(5:00 de hoy)`; antes de las 5:00 eso es una hora futura y deja intentar sin límite. Se usa «las 5:00 más recientes». El contador nuevo de `tb_kujibiki_count` se numera con `hatuban` de la tabla (el legacy usaba `createHatsuban(tienda)`).
- Cupones: el servicio se llama `CouponService` (`issue`, `markUsed`, `markForPrint`, `printableFor`). Imprimir es dejar `print_flg = cd de la tienda` y, al imprimir, `markUsed` (copia a `tb_mycoupon_used` y borra), como el reproductor del legacy; no hay `printed_at`.
- Scheduler: `PromocionServiceProvider::TASKS` registra solo los comandos que ya existen; hoy, `promocion:clean-temp` (subidas en el disco `public` con la ruta del legacy `system/category3/<carpeta>/uploads/temp`).
- M2a (2026-11-26): un envío único que se pierde su minuto sale en la siguiente pasada de `promocion:send-mail` (el legacy, al rehacer el crontab, lo marcaba 配信状況 = 2 sin enviarlo). El aviso a g-api se mantiene con el mismo GET (`GApiPushTrigger`), solo con `application_use_flg = 1`. Las imágenes de los correos se leen del disco `public` (`system/category3/<tipo>/uploads/<tienda>/`); las de tiny_mce, de `public/legacy`. Un fallo de transporte cuenta como error de envío (`error_send_cnt`), como `mail() === false`.
- Texto de formularios (CONVENCIONES §4): los registros de mail_mag (plantillas, categorías, direcciones de prueba) usaban `form2Db()` (KV + recorte, sin entidades), ni `get_form` ni `get_form2`: se portó como `App\Services\Promotions\Form2Db::clean()` y se imprime con `{{ }}`. En 新規配信, 確認 aplica `LegacyText::form()` (tras `<>` → `＜＞`) a todo salvo el HTML de la デコメール, que se guarda tal cual (KV, `'` → `’`, recortado), como `mail_confirm_frm.php`.
- M2b (2026-11-27): 発行条件選択 guarda la búsqueda de `CustomerSearch` en sesión (`MailAudience`, sin SQL) y el `振分配信` se genera al registrar como `WHERE cd IN (<consulta>)`. La カスタマイズ検索 sale de `category1` como en el legacy. Los datos de los parciales salen de `CustomerSearchForm::viewData()` de S2 cuando esté integrado (respaldo mínimo en `SearchFormData`). El selector フッター表記店舗 marca el `tourokusya` elegido (el legacy marcaba por `shop_cd` y lo perdía al volver). Envío inmediato: `SendMailmagJob` tras la respuesta. Imágenes con GD (un GIF animado reducido pierde la animación). Master sin tienda registra `店id = -1`.
- M2c (2026-11-27): la edición de un programado reconstruye la búsqueda desde su `振分配信` guardado (`MailAudience::storeRaw`, quitando ` AND cd NOT IN (<振分条件>)` como el legacy); al registrar no cambia `search_display`. El master sin tienda deja `店id = -1` (el legacy ponía `(int) null = 0`). Las descargas de CSV por Adobe AIR/Flash del legacy no se portan (solo el formulario normal). El selector de 表示件数 ofrece 10/20/30/50/100.
- F1 (2026-11-27): このクーポンを送る usa el flujo común de envío con `mailType = mycoupon` (rutas `coupons.mail.*`, `type_flg = coupon`, cupón por destinatario al enviar); sus programados se listan y editan en mail_mag como en el legacy. `mycoupon_issue`, `_issue_detail`, `_hakkou`, `_create`, `_complete` y `_rireki` quedan **descartados**: solo los enlazaban `mycoupon_mail.php`/`_confirm`/`_send` (versión antigua sin uso) y el enlace del detalle está comentado. El detalle de cupón corrige `kaiseki_disp()` (el legacy fallaba con `pg_query(['connect'], …)` si había usos) y sus gráficas siguen en `/system/inc/graph_pie.php` y `graph_bar.php` (pedido a S1).
- F1 ranking/stats (2026-11-27): el ranking filtra 使用区分 sobre `tb_mycoupon_used.coupon_kubun` y tienda de uso como el legacy; corrige `couponKubunDisp()` (fallaba con `pg_query(['connect'], …)` en cupones de メルマガ配信用). Las estadísticas cuentan el día desde las 5:00. El 会員詳細 abre la ficha del legacy hasta que exista `admin.clientes.show`.
