# Matriz de paridad por módulo

S1, 2026-11-27. Resumen de las tablas de paridad de `Doc/diseno/*.md` en `laravel13` (563334a): cada fila con columna **Estado** cuenta una vez (una pantalla, endpoint o script del legacy). "hecho" incluye los que llevan nota (p. ej. "hecho (falta verlo con el layout)"); "pendiente" incluye los ⏸ (bloqueados o en espera de decisión). Se regenera con `Doc/qa/scripts/paridad.py` + `paridad_md.py` (ver al final).

## Lectura para el cierre

- **Promoción (S3)**: su `Doc/diseno/promocion.md` no tiene la columna Estado al día (0 hechos sobre 147). Al menos 29 filas ya tienen ruta en el código; hasta que S3 la actualice, el total del proyecto (52 %) está **subestimado**.
- **Operaciones (S5)**: 26 de sus 30 pendientes son ⏸ de 勤怠 (`kintai/`, `working/`, カードリーダー/カード): esperan la decisión de Antony sobre 勤怠, no trabajo.
- **Contenido y terminales (S4)**: 23 pendientes, 4 bloqueados; 35 de sus hechos llevan la nota «falta verlo con el layout».
- **Clientes, ventas y reservas (S2)**: 28 pendientes sin bloqueo.
- **"Otro"** (17): 9 filas de `category3/reservation/` marcadas «S2» (las lleva reservas), 4 «diferido: POS/SNS fuera de alcance», 1 «de S2», y 3 filas con la tabla descuadrada (la celda Estado tiene otro texto: revisar en `clientes-ventas-reservas.md` y `promocion.md`).

## Resumen

| Módulo | Sesión | Hecho | Pendiente | Descartado | Otro | Total | % hecho (sin descartados) |
|---|---|---:|---:|---:|---:|---:|---:|
| [Núcleo y 基本情報管理 (category0)](../diseno/nucleo-base.md) | S1 | 16 | 0 | 4 | 0 | 20 | 100 % |
| [顧客管理, 売上 y 予約 (category1, ventas, category9)](../diseno/clientes-ventas-reservas.md) | S2 | 119 | 28 | 23 | 2 | 172 | 80 % |
| [販売促進管理 (category3)](../diseno/promocion.md) | S3 | 0 | 137 | 34 | 10 | 181 | 0 % |
| [マイページ, HP y 端末管理 (category6, category7)](../diseno/contenido-terminales.md) | S4 | 51 | 23 | 46 | 0 | 120 | 69 % |
| [業務支援管理 e integraciones (category5, API)](../diseno/operaciones-integraciones.md) | S5 | 72 | 30 | 22 | 5 | 129 | 67 % |
| **Total** | | **258** | **218** | **129** | **17** | **622** | **52 %** |

## Núcleo y 基本情報管理 (category0) — S1

| Sección | Hecho | Pendiente | Descartado | Otro |
|---|---:|---:|---:|---:|
| 1. Tabla de paridad | 16 | 0 | 4 | 0 |

Sin pendientes.

## 顧客管理, 売上 y 予約 (category1, ventas, category9) — S2

| Sección | Hecho | Pendiente | Descartado | Otro |
|---|---:|---:|---:|---:|
| Inicio | 1 | 0 | 0 | 0 |
| `customer/` — listas, búsqueda y configuración | 21 | 0 | 8 | 1 |
| `inc/` — ficha, alta, edición, pestañas, visitas, puntos y sellos | 33 | 0 | 3 | 0 |
| `biyou/` — ficha 美容 (sin enlace en el menú; tiene pantalla porque el legacy la tenía) | 4 | 0 | 1 | 0 |
| `config/` — configuración inicial | 0 | 6 | 1 | 0 |
| `entrance/` — entrada y salida | 0 | 2 | 0 | 0 |
| `rank/` — rangos (modelos de S3, pantallas de S2) | 8 | 0 | 0 | 0 |
| `shukei/` — agregaciones | 7 | 0 | 2 | 0 |
| Ventas internas — `category1/karute_menu/` (módulo `ventas`) | 11 | 0 | 1 | 0 |
| 1.2 販売管理 — `category2/` (módulo `ventas`) | 5 | 0 | 0 | 0 |
| 1.3 予約管理 — `category9/yoyaku/` (módulo `reservas`) | 29 | 2 | 6 | 1 |
| 1.3 bis 予約管理 — `category3/reservation/` (cedido por S3) | 0 | 11 | 0 | 0 |
| 1.4 Reserva web pública `r/` y `reserve/` (`routes/publico/reservas.php`) | 0 | 7 | 1 | 0 |

**Pendientes (28) y estado sin clasificar (2):**

- _`customer/` — listas, búsqueda y configuración_
  - `csv_download.php` (+carpeta), `csv_download_ex02.php` (+), `csv_download_template.php` (+) (CSV (Flash)) — `Cu\CustomerController@export`
- _`config/` — configuración inicial_
  - `felica_mode.php` (カードリーダーモード設定) — pendiente (si falta la fila `cd = 2`, se crea en lugar de no guardar en silencio: ver Deci
  - `card_new.php` (カード管理) — pendiente (necesita lector real para probar del todo)
  - `card_staff_select.php` ((selector de personal))
  - `mail_comment.php`, `mail_comment_register.php` ((コメント設定)) — pendiente (prioridad baja, sin enlace en el menú)
  - `schoolyear_config.php`, `schoolyear_register.php`, `schoolyear_delete.php` (学年設定) — pendiente (prioridad baja)
  - `schoolclass_config.php`, `schoolclass_register.php`, `schoolclass_delete.php` (クラス設定) — pendiente (prioridad baja)
- _`entrance/` — entrada y salida_
  - `working_calendar.php` (入退室一覧 (calendario)) — pendiente (Carbon; sin los botones 「1970年1月へ」)
  - `work_list.php` (入退室一覧)
- _1.3 予約管理 — `category9/yoyaku/` (módulo `reservas`)_
  - `time_table.php` (予約表) — L `tb_base_system_grandchild`, `tb_staff`, `tb_yoyaku`, `tb_yoyaku_config`, `tb_yoyaku_kou
  - `yoyaku_uketsuke_day.php`, `yoyaku_uketsuke_register.php`, `yoyaku_uketsuke_delete.php` (受付日設定 / 予約受付削除)
  - `yoyaku_multi_uketsuke_day.php`, `yoyaku_multi_uketsuke_register.php` (受付日設定 (varios días))
- _1.3 bis 予約管理 — `category3/reservation/` (cedido por S3)_
  - `reserve_customer_search.php` (予約新規登録 / 顧客一覧・検索)
  - `reserve_new.php`, `reserve_new_confirm.php`, `reserve_new_register.php`, `reserve_new_complete.php` (予約新規登録 / 確認 / 完了)
  - `reserve_calendar.php` (予約・来店一覧)
  - `reserve_list.php` (予約一覧)
  - `reserve_detail.php` (予約詳細)
  - `reserve_change_status.php` (予約情報 (状態変更))
  - `reserve_delete.php` (予約情報 (削除))
  - `category_entry.php`, `category_register.php`, `category_delete.php` (予約カテゴリ登録・変更)
  - `config.php`, `config_register.php` (予約項目設定)
  - `ajax_category.php`, `ajax_staff.php`, `inc/check_select.php` ((AJAX))
  - `model_reservation.php` (予約機種一覧) — pendiente (solo lista; nadie del repositorio escribe `tb_change_model`: S0 decidió migrar 
- _1.4 Reserva web pública `r/` y `reserve/` (`routes/publico/reservas.php`)_
  - `r/index.php` (+ `reserve/`) (Reserve)
  - `r/check.php` (+ `reserve/`) (Reserve (確認))
  - `r/ajax_getScheduleData.php` (+ `reserve/`) — pendiente (JSON idéntico)
  - `r/ajax_getChoiceData.php` (+ `reserve/`)
  - `r/ajax_reserveRegister.php` (+ `reserve/`) — pendiente (mismos textos de error)
  - `r/ajax_reserveDelete.php` (+ `reserve/`) — pendiente (rama `tb_c2_*` de `reserve/` descartada)
  - `r/getReservationWaitTime.php`

## 販売促進管理 (category3) — S3

> **Ojo:** la columna Estado de `promocion.md` no está al día (todo figura como pendiente; el avance real está en su registro del final: F1, F2…). Cruzando su columna «Ruta Laravel» con las rutas que existen hoy en el código, **29 de 147** filas no descartadas ya tienen ruta (marcadas abajo con «ruta ya existe»). S3 debe actualizar la columna para el cierre; hasta entonces el % de promoción no es real.

| Sección | Hecho | Pendiente | Descartado | Otro |
|---|---:|---:|---:|---:|
| お知らせ配信管理 — `mail_mag/` | 0 | 22 | 7 | 0 |
| デコメール管理 — `decomail/` | 0 | 6 | 3 | 0 |
| 自動お知らせ管理 — `auto/` | 0 | 7 | 0 | 0 |
| フォローお知らせ管理 — `follow/` | 0 | 6 | 1 | 0 |
| リマインダお知らせ管理 — `reminder/` | 0 | 3 | 1 | 0 |
| プッシュ通知 — `push/` | 0 | 9 | 1 | 1 |
| DM発行 — `dm/` | 0 | 2 | 1 | 0 |
| 新着情報 — `digital_signage/` (category3) | 0 | 2 | 0 | 0 |
| Motor compartido — `inc/`, `class/`, raíz | 0 | 11 | 2 | 0 |
| アンケート管理 — `anketo/` (tablas `tb_c3_anketo_*`) | 0 | 19 | 1 | 0 |
| お客様アンケート管理 — `anketo2/` (tablas `tb_c3_anketo2_*`) | 0 | 7 | 0 | 0 |
| クーポン — `coupon/` (cupón-imagen antiguo) | 0 | 0 | 8 | 0 |
| マイクーポン管理 — `mycoupon/` | 0 | 17 | 1 | 0 |
| ポイント管理 — `point/` + `class/Point.php` | 0 | 10 | 3 | 0 |
| スタンプ管理 — `stamp/` | 0 | 9 | 2 | 0 |
| スマトピ連携 — `smatopi/` | 0 | 2 | 0 | 0 |
| 来店履歴管理 — `visit/` | 0 | 5 | 3 | 0 |
| 予約管理 — `reservation/` (category3) | 0 | 0 | 0 | 9 |

**Pendientes (137) y estado sin clasificar (10):**

- _お知らせ配信管理 — `mail_mag/`_
  - `mail_customer_select.php` (発行条件選択 (メール)) — **ruta ya existe** (`admin.promocion.mail.select`)
  - `mail_new.php` (新規メール配信) — **ruta ya existe** (`admin.promocion.mail.create`)
  - `mail_new_confirm.php` ((確認)) — **ruta ya existe** (`admin.promocion.mail.confirm`)
  - `mail_new_send.php` ((登録)) — **ruta ya existe** (`admin.promocion.mail.store`)
  - `mail_future_list.php` (予定配信お知らせ一覧) — **ruta ya existe** (`admin.promocion.mail.future`)
  - `mail_future.php` (予定配信メール編集) — **ruta ya existe** (`admin.promocion.mail.edit`)
  - `mail_future_confirm.php` ((確認))
  - `mail_future_send.php` ((登録)) — **ruta ya existe** (`admin.promocion.mail.update`)
  - `mail_kaijo.php` (予約配信メール 配信解除) — **ruta ya existe** (`admin.promocion.mail.cancel`)
  - `mail_past.php` (過去配信お知らせ一覧) — **ruta ya existe** (`admin.promocion.mail.past`)
  - `mail_past_shousai.php` (過去配信詳細) — **ruta ya existe** (`admin.promocion.mail.show`)
  - `mail_sended_list.php` (+ CSV) (配信先一覧) — **ruta ya existe** (`admin.promocion.mail.recipients`)
  - `mailmag_analyze.php` (クリックカウンタ)
  - `mail_template.php` (テンプレート管理) — **ruta ya existe** (`admin.promocion.templates.index`)
  - `mail_template_entry.php`, `mail_template_register.php` (テンプレート登録)
  - `mail_template_category.php`, `_category_register.php` (テンプレートカテゴリ管理)
  - `ajax_mail_template.php`, `ajax_mail_template_category.php` ((AJAX))
  - `test_mail_address.php`, `test_mail_address_register.php` (テストお知らせアドレス管理)
  - `ajax_test_mail_address_new.php`, `ajax_test_mail_address_staff.php` ((AJAX))
  - `base.php`, `base_mailmaga.php`, `base_mycolle.php`, `base_point.php` (メールマガジン基本情報 / マイコレ登録用QR / メルマガ用QR)
  - `qr_img.php`, `qr_print_pdf.php` (QR / QR印刷)
  - `inc/disp_pic.php`, `inc/upload.php`, `inc/furiwake_disp.php` ((fragmentos)) — **ruta ya existe** (`admin.promocion.mail.upload`)
- _デコメール管理 — `decomail/`_
  - `mail_customer_select.php` (新規デコメール配信 (発行条件選択)) — **ruta ya existe** (`admin.promocion.deco.select`)
  - `mail_new.php`, `mail_new_confirm.php`, `mail_new_send.php` (新規デコメール) — **ruta ya existe** (`admin.promocion.deco.create`)
  - `mail_future_list.php`, `mail_future.php`, `mail_future_confirm.php`, `mail_future_send.php` (予定配信デコメール一覧) — **ruta ya existe** (`admin.promocion.deco.future`)
  - `mail_past.php`, `mail_past_shousai.php`, `mail_sended_list.php` (過去配信メール一覧) — **ruta ya existe** (`admin.promocion.deco.past`)
  - `mailmag_analyze.php` (クリックカウンタ)
  - `mail_kaijo.php` (配信解除) — **ruta ya existe** (`admin.promocion.deco.cancel`)
- _自動お知らせ管理 — `auto/`_
  - `auto_new.php` (自動お知らせ新規作成)
  - `auto_new_confirm.php` ((確認))
  - `auto_mail_customer_select.php`, `auto_mail_new.php`, `auto_mail_new_confirm.php`, `auto_mail_new_send.php` (自動 / 配信条件選択)
  - `auto_mail_future_list.php` (自動お知らせ設定一覧)
  - `auto_mail_future.php`, `_confirm.php`, `_send.php` ((編集))
  - `mail_kaijo.php` (配信解除)
  - `inc/disp_pic.php`, `upload.php` — **ruta ya existe** (`admin.promocion.mail.upload`)
- _フォローお知らせ管理 — `follow/`_
  - `follow_new.php` (フォローお知らせ新規作成)
  - `follow_new_confirm.php` ((確認: clientes que encajan hoy))
  - `follow_mail_*` (select, new, confirm, send) (フォロー)
  - `follow_mail_future_list.php` (フォローお知らせ設定一覧)
  - `follow_mail_future*.php` (3) ((編集))
  - `mail_kaijo.php`, `inc/*` (配信解除)
- _リマインダお知らせ管理 — `reminder/`_
  - `reminder_new.php` (リマインダーメール新規作成)
  - `reminder_new_confirm.php` ((確認: reservas afectadas))
  - `reminder_mail_new*.php` (3), `reminder_mail_future*.php` (4), `mail_kaijo.php` (リマインダお知らせ設定一覧 / 編集 / 解除)
- _プッシュ通知 — `push/`_
  - `push_customer_select.php` (プッシュ通知 / 発行条件選択)
  - `push_new.php` + `push_new.js` (プッシュ通知新規作成)
  - `ajaxSavePush.php` ((AJAX guardar))
  - `ajaxChangeStatus.php` ((AJAX pausar/anular))
  - `ajaxSearchMember.php` ((AJAX socio de prueba))
  - `push_complete.php` (プッシュ通知 (完了))
  - `push_follow_list.php?type_flg=scheduled\ (follow\) — `PushController@followList`
  - `push_past.php` (過去配信内容一覧)
  - `push_past_shousai.php` (過去配信詳細)
  - `push_history.php`, `push_history_detail.php` (プッシュ履歴)
- _DM発行 — `dm/`_
  - `customer_list.php` (DM発行) — pendiente (uso por confirmar)
  - `aterna_a4_12.php` ((宛名ラベル A4×12)) — pendiente (uso por confirmar)
- _新着情報 — `digital_signage/` (category3)_
  - `digital_signage_entry.php` (新着情報管理) — pendiente (uso por confirmar)
  - `digital_signage_register.php` (新着情報登録) — pendiente (uso por confirmar)
- _Motor compartido — `inc/`, `class/`, raíz_
  - `inc/ajax_get_template_titles.php`, `ajax_get_template_body.php` (ajax)
  - `inc/ajax_search_kaiin.php`, `ajax_reigai_kaiin.php` (ajax)
  - `inc/ajax_search_coupon.php` (ajax)
  - `inc/ajax_sendTestMail.php`, `ajax_testMailList.php` (ajax)
  - `inc/ajax_mail_delete.php`, `ajax_change_status.php`, `ajax_get_shop_data.php` (ajax)
  - `inc/csv_download.php`, `csv_shousai_download.php`, `csv_download/csv_*.php` (+ SWF) (csv)
  - `inc/customer_list.php`, `inc/customer_detail.php` (popup) — pendiente (lista); ficha: S2 — **ruta ya existe** (`admin.promocion.mail.customers`)
  - `inc/mail_customer_select_frm.php`, `mail_frm.php`, `mail_confirm_frm.php`, `mail_send_frm.php`, `mail_future_ (fragmentos) — pendiente (dentro de sus vistas)
  - `inc/mailWordsReplace.php`, `decomailTagReplace.php`, `regextest.php` (fragmento)
  - `class/cSendSchedule.php` (cron)
  - `index.php` (portada)
- _アンケート管理 — `anketo/` (tablas `tb_c3_anketo_*`)_
  - `anketo_cate_list.php` (アンケート一覧)
  - `anketo_cate_list.php?which=sended` (過去アンケート配信一覧)
  - `anketo_cate_new.php`, `_frm`, `_errchk`, `_register` (アンケート新規登録)
  - `anketo_cate_update.php` (アンケート修正)
  - `anketo_cate_action.php` ((表示切替・並び替え・削除))
  - `anketo_cate_detail.php` (アンケート詳細)
  - `anketo_form_list.php` (項目一覧)
  - `anketo_form_new.php`, `anketo_form_update.php`, `_frm`, `_errchk`, `_register` (項目新規 / 修正)
  - `anketo_form_delete.php` ((削除))
  - `anketo_form_detail.php` (項目詳細)
  - `anketo_form_issue.php` (アンケート発行 (発行条件選択))
  - `anketo_mail.php`, `anketo_mail_confirm.php`, `anketo_mail_send.php` + envoltorios `anketo_mail_*` (新規メール配信)
  - `ajax_search_coupon.php` ((AJAX))
  - `anketo_test.php`, `anketo_test_register.php` (アンケートテスト)
  - `anketo_answer_analyze.php` (回答解析)
  - `anketo_answer_shousai.php` (回答詳細)
  - `csv_answer_download.php` (回答CSV)
  - `anketo_analyze.php` (クリックカウンタ)
  - `inc/anketo_function.php` (fragmento (clases de tamaño de ítem)) — pendiente (dentro de 項目新規 / 修正)
- _お客様アンケート管理 — `anketo2/` (tablas `tb_c3_anketo2_*`)_
  - Vistas comunes (`anketo_cate_*`, `anketo_form_*`, `anketo_mail*`, `anketo_test*`, `anketo_answer_shousai`, `cs (お客様アンケート新規登録 / 修正 / 項目…)
  - `anketo_cate_list.php` (casilla 選択) (お客様アンケート一覧)
  - `ajax_set_anketo.php` ((AJAX encuesta activa))
  - `anketo_answer_list.php` (お客様アンケート回答一覧)
  - `anketo_answer_detail.php` (回答詳細 (por cliente))
  - `anketo_reply_customer_select.php`, `anketo_reply_new.php`, `_confirm.php`, `_send.php` (返信メール)
  - `anketo_answer_analyze.php` (回答解析) — pendiente (el legacy mezcla `tb_c3_anketo_*` en alguna consulta: se usa siempre la familia
- _マイクーポン管理 — `mycoupon/`_
  - `mycoupon_new.php`, `mycoupon_update.php`, `_frm`, `_errchk` (クーポン新規登録 / 修正) — **ruta ya existe** (`admin.promocion.coupons.create`)
  - `mycoupon_register.php` ((登録))
  - `mycoupon_list.php` (クーポン一覧) — **ruta ya existe** (`admin.promocion.coupons.index`)
  - `mycoupon_detail.php` (クーポン詳細) — **ruta ya existe** (`admin.promocion.coupons.show`)
  - `mycoupon_delete.php` ((削除)) — **ruta ya existe** (`admin.promocion.coupons.destroy`)
  - `mycoupon_issue.php` (クーポン発行 (発行条件選択))
  - `mycoupon_issue_detail.php` (発行内容確認)
  - `customer_list.php` (顧客一覧 (popup))
  - `mycoupon_hakkou.php` (クーポン発行 (実行)) — pendiente (filtro validado en vez de `$_SESSION['wherestr']`)
  - `mycoupon_create.php`, `mycoupon_complete.php` (クーポン発行完了)
  - `mycoupon_rireki.php` (クーポン履歴)
  - `mycoupon_ranking.php` (使用ランキング) — **ruta ya existe** (`admin.promocion.coupons.ranking`)
  - `mycoupon_stats.php` (クーポン利用数確認) — **ruta ya existe** (`admin.promocion.coupons.stats`)
  - `mycoupon_stats_detail.php` (クーポン利用詳細) — **ruta ya existe** (`admin.promocion.coupons.stats.show`)
  - `mycoupon_analyze.php` (クリックカウンタ)
  - `mycoupon_mail*.php` (7) (新規メール配信 (cupón))
  - `mail_past.php`, `mail_past_shousai.php`, `mail_sended_list.php` (過去クーポン配信一覧 / 詳細) — **ruta ya existe** (`admin.promocion.coupons.mail.past`)
- _ポイント管理 — `point/` + `class/Point.php`_
  - `point_config.php` (8 formularios) (ポイント仕様管理) — **ruta ya existe** (`admin.promocion.points.config`)
  - `ajax_point_yukoukigen_kakunin.php` ((AJAX afectados))
  - `point_rules_list.php` (ポイントルール一覧)
  - `point_rules_new.php`, `point_rules_update.php`, `_frm`, `_errchk`, `_register` (ポイントルール新規 / 修正)
  - `point_rules_delete.php` ((削除))
  - `point_aggregate.php`, `ajax_point_aggregate.php` (ポイント集計)
  - `csv_grant_points.php` (ポイント一括付与)
  - `point_alert_mail_template_entry.php`, `_register.php` (ポイント有効期限切れ前通知 テンプレート)
  - `point_shikkou_mail_template_entry.php`, `_register.php` (ポイント失効通知 テンプレート)
  - `point_shikkou_alert_mail_template_entry.php`, `_register.php` (失効前通知 テンプレート (múltiple))
- _スタンプ管理 — `stamp/`_
  - `stamp_pattern.php`, `stamp_pattern_register.php` (スタンプデザイン管理)
  - `stamp_config.php`, `stamp_config_register.php` (スタンプ仕様管理)
  - `ajax_stamp_shikkou_kakunin.php` ((AJAX afectados))
  - `stamp_rules_list.php` (スタンプルール一覧)
  - `stamp_rules_new.php`, `stamp_rules_update.php`, `_frm`, `_errchk`, `_register` (スタンプルール新規 / 修正)
  - `stamp_rules_delete.php` ((削除))
  - `oshirase_coupon.php`, `ajax_oshirase_coupon.php`, `oshirase_coupon_register.php` (クーポンお知らせ機能管理)
  - `stamp_alert_mail_template_entry/_register.php` (スタンプ失効前通知 テンプレート)
  - `stamp_shikkou_mail_template_entry/_register.php` (スタンプ失効通知 テンプレート)
- _スマトピ連携 — `smatopi/`_
  - `smatopi_message.php`, `ajax_smatopiMessage.php`, `ajax_smatopiLine.php` (スマトピ / メッセージ作成) — pendiente (uso por confirmar)
  - `smatopi_sent_history.php`, `ajax_smatopiSentHistory.php` (スマトピ / メッセージ送信履歴) — pendiente (uso por confirmar)
- _来店履歴管理 — `visit/`_
  - `visit_stats2.php` (来店履歴一覧)
  - `day_visit_list.php` (来店履歴一覧 (日別))
  - `store_visit_list.php` (個別来店履歴)
  - `csv_download.php`, `storecsv_download.php`, `daycsv_download.php` (CSV de visitas) — pendiente (`daycsv_download.php` está vacío en el legacy: se implementa igual que los otro
  - `touch_config.php`, `touch_config_register.php` (カードリーダ設定) — pendiente (intervalo del lector de tarjetas de visita)
- _予約管理 — `reservation/` (category3)_
  - `reserve_customer_search.php` (予約新規登録 / 顧客一覧・検索) — S2
  - `reserve_new.php`, `reserve_new_confirm.php`, `reserve_new_register.php`, `reserve_new_complete.php` (予約新規登録 / 確認 / 完了) — S2
  - `reserve_calendar.php` (予約・来店一覧) — S2
  - `reserve_list.php` (予約一覧) — S2
  - `reserve_detail.php`, `reserve_change_status.php`, `reserve_delete.php` (予約詳細 / 予約情報) — S2
  - `category_entry.php`, `category_register.php`, `category_delete.php` (予約カテゴリ登録・変更) — S2
  - `config.php`, `config_register.php` (予約項目設定) — S2
  - `ajax_category.php`, `ajax_staff.php`, `inc/check_select.php` ((AJAX)) — S2
  - `model_reservation.php` (予約機種一覧) — S2

## マイページ, HP y 端末管理 (category6, category7) — S4

| Sección | Hecho | Pendiente | Descartado | Otro |
|---|---:|---:|---:|---:|
| 1.1 マイページ管理 — `mypage` (category6) | 14 | 1 | 15 | 0 |
| 1.2 ホームページ管理 — `web` (category4) | 0 | 0 | 20 | 0 |
| 1.3 端末管理 — `terminales` (category7) | 24 | 5 | 7 | 0 |
| 1.4 Reproductores — `senalizacion` (`signage/`, `digitalsignage/`) | 13 | 17 | 4 | 0 |

**Pendientes (23):**

- _1.1 マイページ管理 — `mypage` (category6)_
  - `category6/index.php`, `inc/page_control.php`, `inc/left_navi6.php` (マイページ管理) — pendiente (menú en `config/menu/mypage.php`)
- _1.3 端末管理 — `terminales` (category7)_
  - `category7/index.php`, `inc/*` (端末管理)
  - `beacon/beacon_list.php` (ビーコン管理) — pendiente — **bloqueado**: BD externa `b-develop.g-api.net` no responde
  - `beacon/beacon_detail.php` + `ajax_beaconSave.php` (ビーコン詳細) — pendiente (bloqueado, ídem)
  - `beacon/terminal_list.php` (受信端末一覧) — pendiente (bloqueado, ídem)
  - `beacon/terminal_detail.php` + `ajax_terminalSave.php` (受信端末詳細) — pendiente (bloqueado, ídem)
- _1.4 Reproductores — `senalizacion` (`signage/`, `digitalsignage/`)_
  - `signage/mail/index.php` (GoTel管理 (お問い合わせ)) — pendiente (confirmar uso; Mailable en cola)
  - `signage/pdf/invoice.php` (請求書) — pendiente (confirmar uso; PDF del paquete de S1)
  - `signage/pdf/receipt.php` (領収書) — pendiente (ídem)
  - `digitalsignage/index.php` → `index2.php` (digital signage) — pendiente (reproductor HTML5 que lee el mismo XML; orientación por `terminal_type`)
  - `hp/uploads/c7/display/<cd>.xml` (generado por `xml_up.php`) ((programa XML)) — pendiente (misma forma XML `<p cd time title …>`)
  - `ajax_xml_check.php`, `ajax_xml_check_2.php` ((sondeo XML)) — pendiente (responde `<script>` igual que hoy)
  - `ajax_display_config_check.php`, `_2.php` ((sondeo config))
  - `ajax_network_check.php` ((red))
  - `ajax_print_coupon_crawl.php`, `ajax_print_coupon_craw_2l.php` ((cupón impreso por visita))
  - `ajax_touch_print_coupon_crawl*.php` ((cupón táctil))
  - `ajax_shinkyaku_print_coupon_crawl*.php` ((cupón cliente nuevo))
  - `ajax_kaiin_raiten_print_coupon_crawl.php` ((cupón socio por visita))
  - `ajax_Mujyouken_print_coupon_crawl_2.php` ((cupón sin condición))
  - `ajax_kaiin_mycoupon_print_crawl.php` ((cupón del socio, marca impreso/usado))
  - `roulette.php` (+ `.swf`, `tig_game/`) (ルーレット) — pendiente (rehacer en HTML5/Canvas)
  - `slot.php` (+ `.swf`) (スロット) — pendiente (ídem)
  - `fla/floormap/ajax_print_crawl.php` ((mapa de planta + 求人))

## 業務支援管理 e integraciones (category5, API) — S5

| Sección | Hecho | Pendiente | Descartado | Otro |
|---|---:|---:|---:|---:|
| 1.1 スタッフ管理 (`system/category5/staff/`) | 16 | 0 | 1 | 0 |
| 1.2 勤怠管理 — terminal de asistencia (`system/category5/kintai/`) | 1 | 12 | 1 | 0 |
| 1.3 勤怠一覧 / 集計 (`system/category5/working/`) | 0 | 10 | 2 | 0 |
| 1.4 スケジュール管理 (`system/category5/schedule/`) | 2 | 0 | 4 | 0 |
| 1.5 初期設定管理 (`system/category5/config/`) y menú | 1 | 5 | 0 | 0 |
| 1.6 API JWT de la tienda móvil (`api/`) | 13 | 0 | 0 | 0 |
| 1.7 API de la app マイページ (`api/mpa_*.php`) | 5 | 0 | 1 | 1 |
| 1.8 `appview/` (WebView de la app del cliente) | 18 | 2 | 8 | 0 |
| 1.9 `system/tool/api` y CTI (`system/desktop_request`) | 12 | 0 | 0 | 1 |
| 1.10 Utilitarios (`version/`, `air/`, `ptag/`, `exp_cmd/`, `cron/`, `instagram/`, `ipad/`) | 4 | 1 | 5 | 0 |
| 1.11 Fuera de alcance de S5 (para que no falten filas) | 0 | 0 | 0 | 3 |

**Pendientes (30) y estado sin clasificar (5):**

- _1.2 勤怠管理 — terminal de asistencia (`system/category5/kintai/`)_
  - `index.php` (日付別勤怠データ一覧) — pendiente ⏸ (Carbon, sin 1970)
  - `kintai_new.php` (勤怠データ新規追加) — pendiente ⏸
  - `kintai_new_up.php` (勤怠データ新規追加) — pendiente ⏸ (transacción; sin el INSERT+UPDATE en una sola consulta)
  - `kintai_update.php` (勤怠データ編集) — pendiente ⏸
  - `kintai_update_up.php` (勤怠データ編集) — pendiente ⏸
  - `kintai_update_del.php` ((marca: borrar)) — pendiente ⏸
  - `kintai_delete.php` ((día: borrar)) — pendiente ⏸
  - `password_update.php`, `password_update_complete.php` (端末パスワード変更) — pendiente ⏸ (texto plano: lo lee el terminal, §7)
  - `config.php` (各種設定データ) — pendiente ⏸
  - `csv_out.php`, `csv_download/csv_out.php` ((CSV diario)) — pendiente ⏸
  - `monthly_csv_out.php`, `csv_download/monthly_csv_out.php` ((CSV mensual por persona)) — pendiente ⏸
  - `ajax_session_update.php` ((tienda seleccionada)) — pendiente ⏸ (misma sesión que el legacy)
- _1.3 勤怠一覧 / 集計 (`system/category5/working/`)_
  - `working_calendar.php` (勤怠一覧 (calendario)) — pendiente ⏸ (Carbon, sin 1970)
  - `work_list.php` (勤怠一覧 (día)) — pendiente ⏸
  - `work_list2.php` (勤怠一覧 (variante)) — pendiente ⏸ (comparte servicio con `day`)
  - `ng_work_list.php` (勤怠一覧 (NG: sin salida)) — pendiente ⏸
  - `work_list_update.php` (勤怠情報) — pendiente ⏸ (columna del día por lista blanca 1..31)
  - `change_syukkin.php` ((ajax 出勤区分)) — pendiente ⏸ (misma respuesta que el legacy)
  - `working_list.php` (スタッフ一覧 (mensual)) — pendiente ⏸
  - `working_total_list.php` (スタッフ勤怠集計一覧) — pendiente ⏸
  - `working_chouhyou.php` (日別シフト管理 (帳票管理)) — pendiente ⏸ (Gate `viewWorkReport` en lugar de `SERVER_ADDR`)
  - `csv_download.php`, `csv_download/csv_file_out.php` ((CSV 勤怠 mensual)) — pendiente ⏸
- _1.5 初期設定管理 (`system/category5/config/`) y menú_
  - `felica_mode.php` (カードリーダーモード設定) — pendiente ⏸
  - `card_new.php` (カード管理 (新規カード管理)) — pendiente ⏸
  - `card_staff_select.php` ((fragmento)) — pendiente ⏸
  - `index.php` (category5) (業務支援管理 (menú))
  - `inc/page_control.php` ((paginador))
- _1.7 API de la app マイページ (`api/mpa_*.php`)_
  - `mpa_customerSync.php` (`/api/mpa_customerSync.php`) — diferido: POS fuera de alcance (`PosGateway::syncCustomers()`); al retomarlo, comando Arti
- _1.8 `appview/` (WebView de la app del cliente)_
  - `member_regist.php` (新規会員登録フォーム)
  - `customerUpdate.php` (個人情報の登録変更フォーム)
- _1.9 `system/tool/api` y CTI (`system/desktop_request`)_
  - `tool/api/category0/setPosdomain.php` (`/system/tool/api/category0/setPosdomain.php`) — diferido: POS fuera de alcance (escribe `tb_shop.pos_domain`; `PosGateway::setShopDomain()
- _1.10 Utilitarios (`version/`, `air/`, `ptag/`, `exp_cmd/`, `cron/`, `instagram/`, `ipad/`)_
  - `exp_cmd/cron/get_shukei_data.php`, `get_shukei_data2.php` (`/exp_cmd/cron/get_shukei_data*.php`) — pendiente ⏸ (escribe en otro servidor, 203.183.91.218, con la clave del POS, y no se sabe 
- _1.11 Fuera de alcance de S5 (para que no falten filas)_
  - A.1 `system/pos_session.php`, `system/terminal_login.php`, `category11/terminal.php` — diferido: POS fuera de alcance
  - A.5 `r/`, `reserve/`, `getReservationWaitTime.php` — de S2 (reservas)
  - `category11/*`, `category12/*` — diferido: POS / SNS fuera de alcance

## Cómo se obtuvo

`git show origin/laravel13:Doc/diseno/<doc>.md` de los cinco documentos y un script que recorre cada tabla Markdown cuya cabecera tiene la columna `Estado`, toma el título `##`/`###` como sección y clasifica la celda por su primera palabra (`hecho…`, `pendiente…`/`⏸…`, `descartado…`; el resto, "otro"). Las filas de otras tablas (contratos, decisiones, pruebas) no cuentan.

Para regenerar: copiar los cinco `Doc/diseno/*.md` de `laravel13` y `php artisan route:list --json > routes.json` a una carpeta temporal, y ejecutar `python Doc/qa/scripts/paridad.py <carpeta>` y después `python Doc/qa/scripts/paridad_md.py <carpeta> Doc/qa/paridad.md <commit>`.
