# Matriz de paridad por módulo

S1, 2026-11-27. Resumen de las tablas de paridad de `Doc/diseno/*.md` en `laravel13` (8e0f4e2): cada fila con columna **Estado** cuenta una vez (una pantalla, endpoint o script del legacy). "hecho" incluye los que llevan nota (p. ej. "hecho (falta verlo con el layout)"); "pendiente" incluye los ⏸ (bloqueados o en espera de decisión). Se regenera con el script de S1 (`paridad.py`, ver al final).

## Lectura para el cierre

- **Núcleo (S1)** y **clientes, ventas y reservas (S2)**: sin pendientes.
- **Promoción (S3)**: 28 pendientes. 15 de ellos ya tienen ruta en el código (marcados «ruta ya existe»): sobre todo プッシュ通知 (M5, hecho por S4) y QR. Hay que confirmar si están terminados y pasarlos a «hecho» en `promocion.md`. El resto: スタンプ管理 (9), スマトピ連携 (2), 新着情報 de category3 (2), ポイント (2), マイクーポン (1).
- **Operaciones (S5)**: 28 pendientes, 26 de ellos ⏸ de 勤怠 (`kintai/`, `working/`, カードリーダー/カード): esperan la decisión de Antony, no trabajo. 5 «otro» = diferidos por POS/SNS (fuera de alcance) o llevados por S2.
- **Contenido y terminales (S4)**: 23 pendientes, 17 de ellos de los reproductores `senalizacion` (`signage/`) y 5 de 端末管理; 4 bloqueados.

## Resumen

| Módulo | Sesión | Hecho | Pendiente | Descartado | Otro | Total | % hecho (sin descartados) |
|---|---|---:|---:|---:|---:|---:|---:|
| [Núcleo y 基本情報管理 (category0)](../diseno/nucleo-base.md) | S1 | 16 | 0 | 4 | 0 | 20 | 100 % |
| [顧客管理, 売上 y 予約 (category1, ventas, category9)](../diseno/clientes-ventas-reservas.md) | S2 | 148 | 0 | 24 | 0 | 172 | 100 % |
| [販売促進管理 (category3)](../diseno/promocion.md) | S3 | 114 | 28 | 41 | 0 | 183 | 80 % |
| [マイページ, HP y 端末管理 (category6, category7)](../diseno/contenido-terminales.md) | S4 | 51 | 23 | 46 | 0 | 120 | 69 % |
| [業務支援管理 e integraciones (category5, API)](../diseno/operaciones-integraciones.md) | S5 | 74 | 28 | 22 | 5 | 129 | 69 % |
| **Total** | | **403** | **79** | **137** | **5** | **624** | **83 %** |

## Núcleo y 基本情報管理 (category0) — S1

| Sección | Hecho | Pendiente | Descartado | Otro |
|---|---:|---:|---:|---:|
| 1. Tabla de paridad | 16 | 0 | 4 | 0 |

Sin pendientes.

## 顧客管理, 売上 y 予約 (category1, ventas, category9) — S2

| Sección | Hecho | Pendiente | Descartado | Otro |
|---|---:|---:|---:|---:|
| Inicio | 1 | 0 | 0 | 0 |
| `customer/` — listas, búsqueda y configuración | 22 | 0 | 8 | 0 |
| `inc/` — ficha, alta, edición, pestañas, visitas, puntos y sellos | 33 | 0 | 3 | 0 |
| `biyou/` — ficha 美容 (sin enlace en el menú; tiene pantalla porque el legacy la tenía) | 4 | 0 | 1 | 0 |
| `config/` — configuración inicial | 5 | 0 | 2 | 0 |
| `entrance/` — entrada y salida | 2 | 0 | 0 | 0 |
| `rank/` — rangos (modelos de S3, pantallas de S2) | 8 | 0 | 0 | 0 |
| `shukei/` — agregaciones | 7 | 0 | 2 | 0 |
| Ventas internas — `category1/karute_menu/` (módulo `ventas`) | 11 | 0 | 1 | 0 |
| 1.2 販売管理 — `category2/` (módulo `ventas`) | 5 | 0 | 0 | 0 |
| 1.3 予約管理 — `category9/yoyaku/` (módulo `reservas`) | 32 | 0 | 6 | 0 |
| 1.3 bis 予約管理 — `category3/reservation/` (cedido por S3) | 11 | 0 | 0 | 0 |
| 1.4 Reserva web pública `r/` y `reserve/` (`routes/publico/reservas.php`) | 7 | 0 | 1 | 0 |

Sin pendientes.

## 販売促進管理 (category3) — S3

| Sección | Hecho | Pendiente | Descartado | Otro |
|---|---:|---:|---:|---:|
| お知らせ配信管理 — `mail_mag/` | 20 | 2 | 7 | 0 |
| デコメール管理 — `decomail/` | 6 | 0 | 3 | 0 |
| 自動お知らせ管理 — `auto/` | 7 | 0 | 0 | 0 |
| フォローお知らせ管理 — `follow/` | 6 | 0 | 1 | 0 |
| リマインダお知らせ管理 — `reminder/` | 3 | 0 | 1 | 0 |
| プッシュ通知 — `push/` | 0 | 10 | 1 | 0 |
| DM発行 — `dm/` | 2 | 0 | 1 | 0 |
| 新着情報 — `digital_signage/` (category3) | 0 | 2 | 0 | 0 |
| Motor compartido — `inc/`, `class/`, raíz | 11 | 0 | 2 | 0 |
| アンケート管理 — `anketo/` (tablas `tb_c3_anketo_*`) | 18 | 0 | 2 | 0 |
| お客様アンケート管理 — `anketo2/` (tablas `tb_c3_anketo2_*`) | 8 | 0 | 1 | 0 |
| クーポン — `coupon/` (cupón-imagen antiguo) | 0 | 0 | 8 | 0 |
| マイクーポン管理 — `mycoupon/` | 11 | 1 | 6 | 0 |
| ポイント管理 — `point/` + `class/Point.php` | 8 | 2 | 3 | 0 |
| スタンプ管理 — `stamp/` | 0 | 9 | 2 | 0 |
| スマトピ連携 — `smatopi/` | 0 | 2 | 0 | 0 |
| 来店履歴管理 — `visit/` | 5 | 0 | 3 | 0 |
| 予約管理 — `reservation/` (category3) | 9 | 0 | 0 | 0 |

**Pendientes (28):**

- _お知らせ配信管理 — `mail_mag/`_
  - `base.php`, `base_mailmaga.php`, `base_mycolle.php`, `base_point.php` (メールマガジン基本情報 / マイコレ登録用QR / メルマガ用QR)
  - `qr_img.php`, `qr_print_pdf.php` (QR / QR印刷) — **ruta ya existe** (`admin.promocion.base.qr`)
- _プッシュ通知 — `push/`_
  - `push_customer_select.php` (プッシュ通知 / 発行条件選択) — pendiente (M5, S4) — **ruta ya existe** (`admin.promocion.push.select`)
  - `push_new.php` + `push_new.js` (プッシュ通知新規作成) — pendiente (M5, S4) — **ruta ya existe** (`admin.promocion.push.create`)
  - `ajaxSavePush.php` ((AJAX guardar)) — pendiente (M5, S4) — **ruta ya existe** (`legacy.promocion.push.ajax-save`)
  - `ajaxChangeStatus.php` ((AJAX pausar/anular)) — pendiente (M5, S4) — **ruta ya existe** (`legacy.promocion.push.ajax-status`)
  - `ajaxSearchMember.php` ((AJAX socio de prueba)) — pendiente (M5, S4) — **ruta ya existe** (`legacy.promocion.push.ajax-member`)
  - `push_complete.php` (プッシュ通知 (完了)) — pendiente (M5, S4) — **ruta ya existe** (`admin.promocion.push.complete`)
  - `push_follow_list.php?type_flg=scheduled/follow/auto` (予定 / フォロー / 自動通知設定一覧) — pendiente (M5, S4)
  - `push_past.php` (過去配信内容一覧) — pendiente (M5, S4) — **ruta ya existe** (`admin.promocion.push.past`)
  - `push_past_shousai.php` (過去配信詳細) — pendiente (M5, S4) — **ruta ya existe** (`admin.promocion.push.show`)
  - `push_history.php`, `push_history_detail.php` (プッシュ履歴) — pendiente (M5, S4) — **ruta ya existe** (`admin.promocion.push.history`)
- _新着情報 — `digital_signage/` (category3)_
  - `digital_signage_entry.php` (新着情報管理) — pendiente (uso por confirmar)
  - `digital_signage_register.php` (新着情報登録) — pendiente (uso por confirmar)
- _マイクーポン管理 — `mycoupon/`_
  - `customer_list.php` (顧客一覧 (popup))
- _ポイント管理 — `point/` + `class/Point.php`_
  - `point_aggregate.php`, `ajax_point_aggregate.php` (ポイント集計) — pendiente (F4, S2) — **ruta ya existe** (`admin.promocion.points.aggregate`)
  - `csv_grant_points.php` (ポイント一括付与) — pendiente (F4, S2) — **ruta ya existe** (`admin.promocion.points.grant-csv`)
- _スタンプ管理 — `stamp/`_
  - `stamp_pattern.php`, `stamp_pattern_register.php` (スタンプデザイン管理) — pendiente (F5, S2) — **ruta ya existe** (`admin.promocion.stamps.pattern`)
  - `stamp_config.php`, `stamp_config_register.php` (スタンプ仕様管理) — pendiente (F5, S2) — **ruta ya existe** (`admin.promocion.stamps.config`)
  - `ajax_stamp_shikkou_kakunin.php` ((AJAX afectados)) — pendiente (F5, S2)
  - `stamp_rules_list.php` (スタンプルール一覧) — pendiente (F5, S2)
  - `stamp_rules_new.php`, `stamp_rules_update.php`, `_frm`, `_errchk`, `_register` (スタンプルール新規 / 修正) — pendiente (F5, S2)
  - `stamp_rules_delete.php` ((削除)) — pendiente (F5, S2)
  - `oshirase_coupon.php`, `ajax_oshirase_coupon.php`, `oshirase_coupon_register.php` (クーポンお知らせ機能管理) — pendiente (F5, S2) — **ruta ya existe** (`admin.promocion.stamps.oshirase`)
  - `stamp_alert_mail_template_entry/_register.php` (スタンプ失効前通知 テンプレート) — pendiente (F5, S2)
  - `stamp_shikkou_mail_template_entry/_register.php` (スタンプ失効通知 テンプレート) — pendiente (F5, S2)
- _スマトピ連携 — `smatopi/`_
  - `smatopi_message.php`, `ajax_smatopiMessage.php`, `ajax_smatopiLine.php` (スマトピ / メッセージ作成) — pendiente (uso por confirmar)
  - `smatopi_sent_history.php`, `ajax_smatopiSentHistory.php` (スマトピ / メッセージ送信履歴) — pendiente (uso por confirmar)

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
| 1.8 `appview/` (WebView de la app del cliente) | 20 | 0 | 8 | 0 |
| 1.9 `system/tool/api` y CTI (`system/desktop_request`) | 12 | 0 | 0 | 1 |
| 1.10 Utilitarios (`version/`, `air/`, `ptag/`, `exp_cmd/`, `cron/`, `instagram/`, `ipad/`) | 4 | 1 | 5 | 0 |
| 1.11 Fuera de alcance de S5 (para que no falten filas) | 0 | 0 | 0 | 3 |

**Pendientes (28) y estado sin clasificar (5):**

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

