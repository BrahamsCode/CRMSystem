# Diseño S5 — Operaciones (業務支援管理) e integraciones externas

> Sesión S5 · rama `l13/operaciones` · 2026-11-26. Sin código: este documento fija qué se construye, con qué tablas y en qué orden.
> Fuentes: `Doc/analisis-legacy/05-operaciones-terminales-reservas.md` (sección category5), `07-pos-api-y-externos.md` (salvo category11, category12, `r/`, `reserve/`), `00-nucleo-y-base.md` §10 (`tool/api` y CTI, que CONVENCIONES asigna a `integraciones`) y el código de `crm2-php8` (solo lectura).
> Fuera de alcance (Antony, 2026-11-26): POS (category11, `pos_session.php`/`CRMPOS-<id>`, servidores POS de tienda → interfaz `PosGateway` sin implementar), SNS (category12) y touchcation (APK `jp.CrmShWeb`, incluida su parte de `version/`).

---

## 1. Tabla de paridad

Estados: `pendiente`, `hecho`, `descartado: motivo`, `diferido: motivo` (fuera de alcance por ahora, se retoma cuando Antony lo decida).
Rutas de pantalla: grupo `admin` (prefijo, `auth:admin`, nombre `admin.`). Rutas de integración: `routes/publico/integraciones.php` (convención S0, 2026-11-26), que S1 carga **fuera** del grupo `admin`, sin `auth:admin`, sin CSRF y sin prefijo, con la URL legacy literal (incluido `.php`) y nombre `publico.integraciones.*`. Dentro, S5 agrupa por middleware propio (`mobileshop.jwt`, `crm.api-key`, sesión para `appview`).
Controladores de pantalla en `App\Http\Controllers\Admin\Operaciones\`; de integración en `App\Http\Controllers\Admin\Integraciones\` (carpeta que CONVENCIONES asigna al módulo, aunque no sean pantallas de administración).

### 1.1 スタッフ管理 (`system/category5/staff/`)

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `staff_entry.php` | スタッフ新規登録 | `admin.operaciones.staff.create` | `StaffController@create` | `staff` (S1), `staff_groups`, `positions` | pendiente |
| `staff_entry_confirm.php` | (confirmación) | `admin.operaciones.staff.confirm` (POST) | `StaffController@confirm` | — | pendiente |
| `staff_entry_register.php` | スタッフ登録情報 | `admin.operaciones.staff.store` / `.update` | `StaffController@store` / `@update` | `staff`, `position_staff` | pendiente (sync POS → `PosGateway::syncStaff()` sin implementar) |
| `staff_entry_complete.php` | スタッフ登録情報 | `admin.operaciones.staff.complete` | `StaffController@complete` | — | pendiente |
| `staff_list.php` | スタッフ一覧 | `admin.operaciones.staff.index` | `StaffController@index` | `staff`, `staff_field_settings` | pendiente |
| `staff_detail.php` | スタッフ登録情報 | `admin.operaciones.staff.show` | `StaffController@show` | — | pendiente |
| `staff_delete_register.php` | スタッフ登録情報 (削除) | `admin.operaciones.staff.destroy` | `StaffController@destroy` | `staff`, `staff_cards` | pendiente |
| `staff_recover_confirm.php` | スタッフ登録情報 (復活) | `admin.operaciones.staff.restore` | `StaffController@restore` | — | pendiente |
| `staff_card_delete.php` | (カード解除) | `admin.operaciones.staff.card.destroy` | `StaffCardController@destroy` | `staff_cards` | pendiente |
| `staff_group.php`, `staff_group_frm.php`, `staff_group_register.php` | スタッフグループ設定 | `admin.operaciones.staff-groups.*` (index, store, update, destroy, `sort`) | `StaffGroupController` | `staff_groups` | pendiente |
| `form_config.php`, `form_config_register.php` | スタッフ検索項目設定 | `admin.operaciones.staff-fields.edit` / `.update` | `StaffFieldSettingController@edit` / `@update` | `staff_field_settings` | pendiente (sin el bug de la primera casilla siempre marcada) |
| `information.php`, `information_register.php`, `information_delete_register.php` | インフォメーション管理 | `admin.operaciones.notices.*` (index, store, destroy) | `StaffNoticeController` | `staff_notices` | pendiente |
| `csv_download.php`, `csv_download/csv_file_out.php`, `csv_download/csv_file_del.php` | (CSV スタッフ) | `admin.operaciones.staff.csv` | `StaffController@csv` | — | pendiente (`StreamedResponse`; `csv_file_del` descartado: no hay archivo temporal) |
| `zip.php` | (código postal → dirección) | reutiliza el endpoint de código postal de S1/S2 | — | — | pendiente (pedido 5.2) |
| `inc/disp_pic.php`, `inc/upload.php` | (foto) | `admin.operaciones.staff.photo` | `StaffController@photo` | — | pendiente (Storage) |
| `inc/search_set1.php`, `inc/search_set2.php` | (formulario de búsqueda) | parcial Blade `admin/operaciones/staff/_search` | — | — | pendiente |

### 1.2 勤怠管理 — terminal de asistencia (`system/category5/kintai/`)

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `index.php` | 日付別勤怠データ一覧 | `admin.operaciones.attendance.index` | `AttendanceController@index` | `attendance_days`, `attendance_punches`, `attendance_terminal_settings` | pendiente |
| `kintai_new.php` | 勤怠データ新規追加 | `admin.operaciones.attendance.create` | `AttendanceController@create` | — | pendiente |
| `kintai_new_up.php` | 勤怠データ新規追加 | `admin.operaciones.attendance.store` | `AttendanceController@store` | `attendance_days`, `attendance_punches` | pendiente |
| `kintai_update.php` | 勤怠データ編集 | `admin.operaciones.attendance.edit` | `AttendanceController@edit` | — | pendiente |
| `kintai_update_up.php` | 勤怠データ編集 | `admin.operaciones.attendance.update` | `AttendanceController@update` | `attendance_punches` | pendiente |
| `kintai_update_del.php` | (marca: borrar) | `admin.operaciones.attendance.punches.destroy` | `AttendancePunchController@destroy` | `attendance_punches` | pendiente |
| `kintai_delete.php` | (día: borrar) | `admin.operaciones.attendance.destroy` | `AttendanceController@destroy` | `attendance_days` | pendiente |
| `password_update.php`, `password_update_complete.php` | 端末パスワード変更 | `admin.operaciones.attendance.password.edit` / `.update` | `AttendanceTerminalController@editPassword` / `@updatePassword` | `attendance_terminal_settings` | pendiente (hash) |
| `config.php` | 各種設定データ | `admin.operaciones.attendance.settings.edit` / `.update` | `AttendanceTerminalController@edit` / `@update` | `attendance_terminal_settings` | pendiente |
| `staff_shift_kind.php`, `_frm.php`, `_register.php` | シフト区分管理 | `admin.operaciones.shift-kinds.*` (index, store, update, destroy, `sort`) | `ShiftKindController` | `shift_kinds` | pendiente |
| `csv_out.php`, `csv_download/csv_out.php` | (CSV diario) | `admin.operaciones.attendance.csv.daily` | `AttendanceCsvController@daily` | — | pendiente (un solo `StreamedResponse` Shift_JIS) |
| `monthly_csv_out.php`, `csv_download/monthly_csv_out.php` | (CSV mensual por persona) | `admin.operaciones.attendance.csv.monthly` | `AttendanceCsvController@monthly` | — | pendiente |
| `csv_download/csv_file_out.php`, `csv_download/csv_file_del.php` | (archivo temporal Flash) | — | — | — | descartado: `StreamedResponse` no usa archivo temporal |
| `ajax_session_update.php` | (tienda seleccionada) | — | — | — | descartado: la tienda va en la query string (`?shop=`) |

### 1.3 勤怠一覧 / 集計 (`system/category5/working/`)

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `working_calendar.php` | 勤怠一覧 (calendario) | `admin.operaciones.timecard.calendar` | `TimecardController@calendar` | `business_days` | pendiente (Carbon, sin 1970) |
| `work_list.php` | 勤怠一覧 (día) | `admin.operaciones.timecard.day` | `TimecardController@day` | `attendance_days`, `attendance_punches` | pendiente |
| `work_list2.php` | 勤怠一覧 (variante) | `admin.operaciones.timecard.day?view=2` | `TimecardController@day` | — | pendiente (un controlador con filtro) |
| `ng_work_list.php` | 勤怠一覧 (NG: sin salida) | `admin.operaciones.timecard.day?only=ng` | `TimecardController@day` | — | pendiente |
| `work_list_update.php` | 勤怠情報 | `admin.operaciones.timecard.update` | `TimecardController@update` | `attendance_punches` | pendiente |
| `change_syukkin.php` | (ajax 出勤区分) | `admin.operaciones.timecard.kind` (PATCH JSON) | `TimecardController@changeKind` | `attendance_days` | pendiente |
| `change_syukkin2.php` | (ajax, sin enlaces) | — | — | — | descartado: duplicado de `change_syukkin` + `work_list_update`, con inyección SQL; su regla del corte a las 5:00 va a `AttendanceService` |
| `working_list.php` | スタッフ一覧 (mensual) | `admin.operaciones.timecard.monthly` | `TimecardController@monthly` | — | pendiente |
| `working_total_list.php` | スタッフ勤怠集計一覧 | `admin.operaciones.timecard.totals` | `TimecardController@totals` | `business_days` | pendiente |
| `working_chouhyou.php` | 日別シフト管理 (帳票管理) | `admin.operaciones.timecard.report` | `TimecardController@report` | — | pendiente (Gate `viewWorkReport` en lugar de `SERVER_ADDR`) |
| `csv_download.php`, `csv_download/csv_file_out.php` | (CSV 勤怠 mensual) | `admin.operaciones.timecard.csv` | `TimecardController@csv` | — | pendiente |
| `csv_download/csv_file_del.php` | — | — | — | — | descartado: sin archivo temporal |

### 1.4 スケジュール管理 (`system/category5/schedule/`)

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `staff_shift.php` | 日別シフト管理 | `admin.operaciones.shifts.day` + `.store`/`.update`/`.destroy` | `ShiftController@day` / `@store` / `@update` / `@destroy` | `shifts`, `shift_kinds` | pendiente (sin el enlace roto a `time_table.php`; comentario con comillas bien guardado) |
| `staff_shift_month.php` | 月別シフト管理 | `admin.operaciones.shifts.month` | `ShiftController@month` | — | pendiente |
| `ajax_staff_shift_hatuban.php` | — | — | — | — | descartado: autoincremental |
| `schedule.php` | 営業日設定 (sin enlace) | — | — | — | descartado: duplicado de `config/shop_work_calendar.php` |
| `shift.php` | 営業日設定 (sin enlace) | — | — | — | descartado: sin lógica, sin enlace |
| `staff_shift.function.php` | (fragmento) | — | — | — | descartado: lo cubre Carbon en `ShiftService` |

### 1.5 初期設定管理 (`system/category5/config/`) y menú

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `felica_mode.php` | カードリーダーモード設定 | `admin.operaciones.card-reader.edit` / `.update` | `CardReaderController@edit` / `@update` | `card_reader_settings` | pendiente |
| `card_new.php` | カード管理 (新規カード管理) | `admin.operaciones.cards.index` / `.assign` | `StaffCardController@index` / `@assign` | `staff_cards` | pendiente |
| `card_staff_select.php` | (fragmento) | parcial Blade | — | — | pendiente |
| `shop_work_calendar.php` | 営業日設定 | `admin.operaciones.business-days.edit` / `.update` | `BusinessDayController@edit` / `@update` | `business_days` | pendiente (botón 更新 dentro del form) |
| `index.php` (category5) | 業務支援管理 (menú) | `config/menu/operaciones.php` | — | — | pendiente |
| `inc/page_control.php` | (paginador) | `paginate()` | — | — | descartado: paginador de Laravel |

### 1.6 API JWT de la tienda móvil (`api/`)

Todas con HTTP 200 también en error, leyendo el **cuerpo JSON incluso en GET**. Detalle del contrato en §6.

| Archivo legacy | URL | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `api/.htaccess`, `api/index.php` | `/api/<ruta>` | rutas explícitas + `Route::fallback` del prefijo `api` | `MobileShop\FallbackController` | — | pendiente (404 JSON en lugar del fatal del legacy) |
| `api/auth.php` | `POST /api/auth` | `publico.integraciones.api.auth` | `MobileShop\AuthController@issue` | — | pendiente |
| `api/customer.php` | `GET /api/customer` | `publico.integraciones.api.customer` | `MobileShop\CustomerController@search` | — (lee `customers`) | pendiente |
| `api/shop.php` | `GET /api/shop` | `publico.integraciones.api.shop` | `MobileShop\ShopController@index` | — | pendiente |
| `api/point.php` | `GET /api/point` | `publico.integraciones.api.point` | `MobileShop\PointController@log` | — (lee `customer_points`) | pendiente |
| `api/mobile_shop/customer.php` | `GET /api/mobile_shop/customer[/<cd>]` | `publico.integraciones.api.ms.customer` | `MobileShop\CustomerController@show` / `@searchDomains` | — | pendiente (multi-BD: pedido 5.1-c) |
| `api/mobile_shop/fetch_paid_member.php` | `POST /api/mobile_shop/fetch_paid_member` | `publico.integraciones.api.ms.fetch-paid-member` | `MobileShop\PaidMemberController@fetch` | — | pendiente |
| `api/mobile_shop/send_unpaid_member_status.php` | `POST /api/mobile_shop/send_unpaid_member_status` | `publico.integraciones.api.ms.send-unpaid` | `MobileShop\PaidMemberController@sendUnpaid` | — | pendiente |
| `api/mobile_shop/passcode.php` | `POST /api/mobile_shop/passcode` | `publico.integraciones.api.ms.passcode` | `MobileShop\PasscodeController@check` | — | pendiente (`hash_equals`) |
| `api/mobile_shop/point.php` | `GET/POST /api/mobile_shop/point` | `publico.integraciones.api.ms.point` | `MobileShop\PointController@balance` | — | pendiente |
| `api/mobile_shop/point_use.php` | `POST /api/mobile_shop/point_use` | `publico.integraciones.api.ms.point-use` | `MobileShop\PointController@use` | — | pendiente |
| `api/mobile_shop/point_exchange_item.php` | `POST /api/mobile_shop/point_exchange_item` | `publico.integraciones.api.ms.point-exchange` | `MobileShop\PointController@exchangeItem` | — | pendiente |
| `api/mobile_shop/sales_items.php` | `GET /api/mobile_shop/sales_items[/<cd>]` | `publico.integraciones.api.ms.sales-items` | `MobileShop\SalesItemController@index` / `@show` | — (lee productos de S2) | pendiente |

### 1.7 API de la app マイページ (`api/mpa_*.php`)

| Archivo legacy | URL | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `mpa_corresponding.php` | `/api/mpa_corresponding.php` | `publico.integraciones.mpa.corresponding` | `MpaController@corresponding` | — | pendiente |
| `mpa_getTopBanner.php` | `/api/mpa_getTopBanner.php` | `publico.integraciones.mpa.top-banner` | `MpaController@topBanner` | — | pendiente (el enlace `md5(login_pass)` se mantiene mientras exista `hp/user`; anotado para Japón) |
| `mpa_getCoverFlow.php` | `/api/mpa_getCoverFlow.php` | `publico.integraciones.mpa.cover-flow` | `MpaController@coverFlow` | — | pendiente |
| `mpa_albumUpdate.php` | `POST /api/mpa_albumUpdate.php` | `publico.integraciones.mpa.album-update` | `MpaController@albumUpdate` | — (fotos del cliente, pedido 5.3) | pendiente (Intervention Image en lugar de `convert`) |
| `mpa_setraiten.php` | `/api/mpa_setraiten.php` | `publico.integraciones.mpa.set-raiten` | `MpaController@setRaiten` | — (visitas S2, beacon S4) | pendiente |
| `mpa_customerSync.php` | `/api/mpa_customerSync.php` | — | — | — | diferido: POS fuera de alcance (`PosGateway::syncCustomers()`); al retomarlo, comando Artisan, no ruta pública |
| `mpa_registCustomer.php` | — | — | — | — | descartado: archivo vacío |

### 1.8 `appview/` (WebView de la app del cliente)

Rutas `appview/<archivo>.php` idénticas, con sesión (`StartSession`; la reserva rápida usa `session()->getId()` como `sess`) y sin CSRF (lo quita S1 para todo `routes/publico/`) — ver pedido 5.1-a. La lista de オススメ y la opción 「アプリで顧客グループを表示」 se leen de `recommendations` y `app_settings` (modelos de S4, solo lectura). Controladores en `Admin\Integraciones\Appview\`.

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `coupon.php` | クーポン | `publico.integraciones.appview.coupon` | `CouponController@index` | — (S3) | pendiente |
| `coupon_detail.php` | クーポン詳細 | `publico.integraciones.appview.coupon-detail` | `CouponController@show` | — | pendiente |
| `coupon_use.php` | クーポン利用 | `publico.integraciones.appview.coupon-use` | `CouponController@confirmUse` | — | pendiente |
| `coupon_control.php` | (AJAX uso) | `publico.integraciones.appview.coupon-control` | `CouponController@use` | — | pendiente |
| `used_coupon_list.php` | 利用済みクーポン | `publico.integraciones.appview.used-coupons` | `CouponController@used` | — | pendiente |
| `stamp.php` | スタンプ | `publico.integraciones.appview.stamp` | `StampController@show` | — (S3) | pendiente |
| `visit_history.php` | 来店履歴 | `publico.integraciones.appview.visit-history` | `MemberController@visitHistory` | — | pendiente (`cutomer_cd` sic) |
| `qr_code.php` | (QR) | `publico.integraciones.appview.qr-code` | `MemberController@qrCode` | — | pendiente |
| `memberId.php` | (会員ID) | `publico.integraciones.appview.member-id` | `MemberController@memberId` | — | pendiente |
| `photo_gallery.php` | フォトギャラリー | `publico.integraciones.appview.photo-gallery` | `MemberController@photoGallery` | — | pendiente |
| `member_login.php` | 会員ページ | `publico.integraciones.appview.member-login` | `MemberController@login` | — | pendiente |
| `member_kiyaku.php` | 顧客登録ページ | `publico.integraciones.appview.member-kiyaku` | `MemberRegistrationController@terms` | — | pendiente |
| `member_regist.php` | 新規会員登録フォーム | `publico.integraciones.appview.member-regist` | `MemberRegistrationController@form` | — | pendiente |
| `member_comfirm.php` | 登録確認ページ | `publico.integraciones.appview.member-comfirm` | `MemberRegistrationController@confirm` | — | pendiente (`comfirm` sic en la URL) |
| `member_complete.php` | 登録完了ページ | `publico.integraciones.appview.member-complete` | `MemberRegistrationController@store` | — (S2 clientes, S3 puntos de alta) | pendiente |
| `member_init.php` | 会員ページ (inicio) | `publico.integraciones.appview.member-init` | `MemberController@init` | — | pendiente |
| `member_comp.php` | 会員ページ (vinculación) | `publico.integraciones.appview.member-comp` | `MemberController@link` | — | pendiente |
| `customerUpdate.php` | 個人情報の登録変更フォーム | `publico.integraciones.appview.customer-update` | `MemberController@updateProfile` | — | pendiente |
| `anketo2.php` | 問診表 | `publico.integraciones.appview.anketo2` | `SurveyController@show` | — (S3) | pendiente |
| `anketo2_register.php` | 問診表 (registro) | `publico.integraciones.appview.anketo2-register` | `SurveyController@store` | — | pendiente |
| `reservation.php` | (予約) | `publico.integraciones.appview.reservation` | `ReservationController@show` | — | pendiente (contenedor; el contenido `r/` es de S2) |
| `quick_reservation.php` | (クイック予約) | `publico.integraciones.appview.quick-reservation` | `QuickReservationController@show` | — (S2) | pendiente |
| `quick_reservation_register.php` | (registro, JSON) | `publico.integraciones.appview.quick-reservation-register` | `QuickReservationController@store` | — | pendiente |
| `privacyPage.php` | プライバシーポリシー | `publico.integraciones.appview.privacy` | `PageController@privacy` | — | pendiente |
| `introduction.php` | プレビュー | `publico.integraciones.appview.introduction` | `PageController@introduction` | — | pendiente |
| `detail_view.php` | プレビュー | `publico.integraciones.appview.detail-view` | `PageController@detailView` | — | pendiente |
| `ms_paid_member_regist.php`, `ms_paid_member_test.php` | (prueba) | — | — | — | descartado: código de prueba con cliente fijo |
| `class/*` | — | servicios | — | — | descartado como archivos: su lógica pasa a servicios (`QuickReservation` → S2) |

### 1.9 `system/tool/api` y CTI (`system/desktop_request`)

`tool/api`: clave `key` comparada con `CRM_API_KEY` (`hash_equals`), middleware `crm.api-key`; sin clave → respuesta vacía como el legacy.

| Archivo legacy | URL | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `tool/api/checkKey.php` | (fragmento) | middleware `CheckCrmApiKey` | — | — | pendiente |
| `tool/api/category0/setPosdomain.php` | `/system/tool/api/category0/setPosdomain.php` | — | — | — | diferido: POS fuera de alcance (escribe `tb_shop.pos_domain`) |
| `tool/api/category1/searchCustomerList.php` | `/system/tool/api/category1/searchCustomerList.php` | `publico.integraciones.tool.search-customer-list` | `ToolApi\CustomerController@search` | — | pendiente |
| `tool/api/category1/getCustomerDetail.php` | `…/getCustomerDetail.php` | `publico.integraciones.tool.customer-detail` | `ToolApi\CustomerController@show` | — | pendiente |
| `tool/api/category1/getSearchCateFormData.php` | `…/getSearchCateFormData.php` | `publico.integraciones.tool.search-form` | `ToolApi\CustomerController@searchForm` | — | pendiente |
| `tool/api/category1/getRaitenList.php` | `…/getRaitenList.php` | `publico.integraciones.tool.raiten-list` | `ToolApi\VisitController@index` | — | pendiente |
| `tool/api/category1/getRaitenDetail.php` | `…/getRaitenDetail.php` | `publico.integraciones.tool.raiten-detail` | `ToolApi\VisitController@show` | — | pendiente |
| `tool/api/category1/addRaitenRecord.php` | `…/addRaitenRecord.php` | `publico.integraciones.tool.raiten-add` | `ToolApi\VisitController@store` | — | pendiente |
| `tool/api/category1/setRaitenDetail.php` | `…/setRaitenDetail.php` | `publico.integraciones.tool.raiten-set` | `ToolApi\VisitController@update` | — | pendiente (708 líneas: puntos y sellos vía servicios de S2/S3) |
| `tool/api/category5/getStaffList.php` | `…/category5/getStaffList.php` | `publico.integraciones.tool.staff-list` | `ToolApi\StaffController@index` | — | pendiente |
| `desktop_request/ctiWindow.php` | `/system/desktop_request/ctiWindow.php?tel=` | `publico.integraciones.cti.window` | `CtiController@window` | — | pendiente (redirige a rutas de S2; exige sesión en el destino) |
| `desktop_request/customerDetailHtml.php` | `/system/desktop_request/customerDetailHtml.php` | `publico.integraciones.cti.customer-html` | `CtiController@customerHtml` | — | pendiente |
| `desktop_request/jGetCustomerData.php` | `/system/desktop_request/jGetCustomerData.php` | `publico.integraciones.cti.customer-json` | `CtiController@customerJson` | — | pendiente |

### 1.10 Utilitarios (`version/`, `air/`, `ptag/`, `exp_cmd/`, `cron/`, `instagram/`, `ipad/`)

| Archivo legacy | URL | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `version/pos/v.php`, `version/android3/v.php`, `version/sharp/v.php` y los 6 `.apk` | `/version/<canal>/v.php` | — | — | — | descartado: touchcation fuera de alcance (las 6 APK son `jp.CrmShWeb`; S0, 2026-11-26) |
| `version/crm_version.php` | `/version/crm_version.php` | `publico.integraciones.version.crm` | `VersionController@crm` | — | pendiente (`text/plain` `2.704`, sin salto de línea; valor en `config('integraciones.crm_version')`) |
| `ptag/.htaccess`, `ptag/index.php` | `/ptag/<ID>` | `publico.integraciones.ptag` | `PtagController@redirect` | `paper_card_accesses` | pendiente |
| `air/update-info.xml`, `air/crossdomain.xml` | `/air/*.xml` | archivos estáticos en `public/air/` | — | — | pendiente (condicional: servir estático mientras exista el cliente AIR) |
| `air/air_info_out.php` | `/air/air_info_out.php` | `publico.integraciones.air.info` | `AirController@info` | — (lee `staff_notices`) | pendiente (condicional, confirmar uso con Japón) |
| `exp_cmd/cron/get_shukei_data.php`, `get_shukei_data2.php` | `/exp_cmd/cron/get_shukei_data*.php` | comando `integraciones:shukei {year?} {month?}` | `App\Console\Commands\Integraciones\ShukeiCommand` | — | pendiente (prioridad baja; llamador desconocido, depende de ventas de S2) |
| `exp_cmd/verUp/*` (11) | CLI | — | — | — | descartado: actualizadores históricos; Laravel usa migraciones |
| `cron/regenerateCronJob.php` | `/cron/regenerateCronJob.php` | — | — | — | descartado: lo reemplaza el Scheduler (cada módulo registra sus tareas en su ServiceProvider) |
| `instagram/index.php` | `/instagram/index.php` | — | — | — | descartado: API de Instagram retirada |
| `ipad/index.php`, `downLoadInfo.php`, `otherBrowser.php` | `/ipad/*` | — | — | — | descartado: guía de web-app iPad obsoleta |

### 1.11 Fuera de alcance de S5 (para que no falten filas)

| Origen | Estado |
|---|---|
| A.1 `system/pos_session.php`, `system/terminal_login.php`, `category11/terminal.php` | diferido: POS fuera de alcance |
| A.5 `r/`, `reserve/`, `getReservationWaitTime.php` | de S2 (reservas) |
| `category11/*`, `category12/*` | diferido: POS / SNS fuera de alcance |

---

## 2. Esquema

Todas las tablas: `id()` (bigserial), `status smallint default 1`, `timestamps()`, `softDeletes()`, texto `text`, banderas `smallint` con comentario y enum. Las migraciones se llaman `AAAA_MM_DD_HHMMSS_operaciones_<accion>.php` / `..._integraciones_<accion>.php`.

**Claves para el importador:** como S4 (diseño aprobado), las tablas de S5 que importa S1 llevan `legacy_cd` (`bigint`, índice único) con el `cd` legacy; donde no hay `cd` propio (días de `tb_work_time`, turnos de `tb_staff_shift`, accesos de `url_transfer_data`) queda nulo.
**Contratos que exponen `cd`:** la API, `tool/api`, CTI y `mpa_*` devuelven el `cd` de tienda, cliente, personal, visita e ítem, y los clientes externos lo vuelven a enviar. Para no romperlos, S5 propone (pedido 5.1-d, decide S0) que en `shops`, `customers`, `staff`, `visits` y productos el importador conserve **`id = cd`** y ajuste la secuencia (`setval` por encima del máximo). Así los registros nuevos tampoco chocan con los viejos. Si S0 prefiere `legacy_cd` también ahí, los recursos de S5 responden `legacy_cd ?? id` y la secuencia debe empezar por encima del máximo `cd` para que un id nuevo nunca coincida con un `legacy_cd`.

**Reutilizado sin cambios:** `shops`, `terminals`, `customers`, `visits`, `customer_points` (dueños S1/S2/S3). **De S1:** la tabla del personal (`staff`, modelo `Staff`). S5 no la crea: pide sus columnas (5.1-b).

### 2.1 Tablas nuevas de `operaciones`

_(mapeo columna a columna en §2.3)_

| Tabla | Reemplaza | Columnas |
|---|---|---|
| `staff_groups` | `tb_staff_group` | `name`, `sort` |
| `positions` | `tb_officialposition` (役職) | `name`, `sort` |
| `departments` | `tb_belongpost` (所属) | `name`, `sort` |
| `employment_types` | `tb_employmentsystem` (雇用形態; solo lectura en el legacy) | `name`, `sort` |
| `staff_notices` | `tb_information` | `shop_id` (null = 全店舗), `department_id`, `employment_type_id` (null = todos), `author_name`, `body` |
| `position_staff_notice` | `tb_information.officialposition` (lista separada por espacios) | `position_id`, `staff_notice_id` |
| `staff_field_settings` | `tb_koumoku` con `tb_name = 'tb_staff'` (41 filas) | `field`, `label`, `form_flg` (mostrar en el formulario), `required_flg`, `search_flg` (búsqueda), `csv_flg` (exportación), `sort` |
| `staff_cards` | `tb_card` | `card_no`, `felica_no` (único), `staff_id` (null = 未割当), `assigned_at` |
| `card_reader_settings` | `tb_felica_mode` fila `cd = 1` | `mode` (enum `CardReaderMode`: 0 運用, 1 出勤強制, 2 退勤強制, 9 登録), `mode_changed_at` — una fila. La fila `cd = 2` (intervalo y textos de correo de entrada/salida) es de category1 → S2 |
| `business_days` | `tb_shop_work` (fila mensual `'YYYY/M'` con 31 columnas `'0'`/`'1'`) | `shop_id`, `date`, `type` (1: 営業日, 0: 休日); único (`shop_id`,`date`) |
| `attendance_terminal_settings` | `kintai_tb_config` | `shop_id` (único), `cutoff_time` (time, `kijun_time`, default 05:00), `start_time`, `end_time`, `password` (hash; default legacy `9999`), `background_image` (ruta Storage) |
| `attendance_days` | `kintai_tb_hiduke` + la parte diaria de `tb_work_time` | `staff_id`, `shop_id`, `work_date` (date), `source` (1: 勤怠端末, 2: タイムカード), `day_type` (enum `AttendanceDayType`), `leave_type` (0: —, 1: 退社, 2: 早退), `felica_no`, `note` |
| `attendance_punches` | `kintai_tb_kintai` + `syukkin/taikin/gaisyutu/kisha_time_N` | `attendance_day_id`, `type` (enum `AttendancePunchType`, mismos valores que `kintai_flg`: 1 出勤, 2 退勤, 3 外出, 4 戻り), `punched_at` (timestamp real), `felica_no` |
| `shift_kinds` | `tb_staff_shift_kind` | `name`, `color`, `sort` |
| `shifts` | `tb_staff_shift` (fila mensual; por día listas paralelas `<>`: varios turnos por día) | `staff_id`, `shop_id`, `date`, `starts_at`, `ends_at` (timestamps: un turno puede pasar de medianoche), `shift_kind_id`, `comment` — **una fila por turno** |

**Enum `AttendanceDayType`** (de `kintai_tb_hiduke.kintai_flg` y `tb_work_time.syukkin_flg_N`): 1 出勤, 2 遅刻, 3 欠勤, 4 有給休暇, 5 半有給, 6 特別有給, 7 忌引休暇, 8 早退, 9 出張, 10 シフト休, 11 直行, 12 直帰, 13 その他勤務.

**Decisión — dos sistemas de asistencia en una tabla:** el legacy tiene dos registros paralelos: el terminal 勤怠 (`kintai_tb_*`, marcas sueltas) y la tarjeta FeliCa (`tb_work_time`, un mes por fila con 31 grupos de columnas). Se normalizan en `attendance_days` + `attendance_punches` con `source` para que cada pantalla muestre lo mismo que antes (kintai filtra `source = 1`, working `source = 2`). Índice único (`staff_id`, `work_date`, `source`). Si Japón confirma que son el mismo dato, basta con quitar el filtro.

**Decisión — corte de jornada:** el terminal 勤怠 usa `kijun_time` por tienda (`05:00` por defecto); la タイムカード FeliCa (touchcation) usa 04:40 fijo; la corrección manual de working usa «hora < 5». En Laravel: `AttendanceService::resolveWorkDate($at, $shop)` con `attendance_terminal_settings.cutoff_time` para todo; el 04:40 de touchcation queda anotado para cuando vuelva al alcance.

### 2.2 Tablas nuevas de `integraciones`

| Tabla | Reemplaza | Columnas |
|---|---|---|
| `paper_card_accesses` | `url_transfer_data` | `paper_card_id` (FK a `paper_cards` de S4), `customer_id` nullable, `accessed_at` |
| `mobile_shop_settings` | `contents_master` `category='mobileshop'`, `field='mobileshop_auth_passcode'`, `int_data` = tienda | `shop_id` (único), `passcode` — configuración propia del módulo (CONVENCIONES §4). La edita la pantalla 機能設定 de S1 (`category0/config/features_config.php`) a través del modelo `MobileShopSetting` de S5 (pedido 5.1-f) |

El resto de `integraciones` no tiene tablas propias: lee y escribe modelos de S1/S2/S3/S4 a través de sus servicios (§5). `mpa_*` y `appview` leen de S4, en solo lectura, `app_images` (+ `app_image_shop`), `app_settings` y `recommendations` (`Doc/diseno/contenido-terminales.md` §2.1) en lugar de `contents_master`/`mypage_config`.

### 2.3 Mapeo columna a columna desde `tb_*`

Deducido del código legacy (no hay `CREATE TABLE` de estas tablas en el repo; tipos según si el valor se escribe con o sin comillas). Reglas comunes del importador:

- `cd` → `legacy_cd` (ver «Claves para el importador»). `disp_flg = '9'` → `deleted_at = update_date` (o `now()` si falta); `'1'` → activo. `hyouji_flg = '9'` → `status = 0`.
- `touroku_date` / `update_date` / `update` (epoch como texto) → `created_at` / `updated_at` en `Asia/Tokyo`. Epoch `0` o vacío → `null`; fechas de 1970 → se informan y no se importan (PENDING 1b.1).
- `rank` (orden) → `sort`. `shop_cd` / `tb_shop_cd` → `shop_id`; `staff_cd` / `tb_staff_cd` → `staff_id`.

**`tb_staff` → `staff` (tabla de S1; propuesta de S5)**

| Legacy | Nuevo | Nota |
|---|---|---|
| `cd` | `id` | |
| `office_cd` | — | siempre 1: se descarta |
| `shop_cd` | `shop_id` | |
| `id` | `code` | nº de empleado visible (`000001`; `master` en `cd = 1`) |
| `login_id`, `login_pass` | `login_id`, `password` | `Hash::make` al importar (S1) |
| `rank_pass` | — (permisos de S1) | matriz `4,4,…<>4,4,…`: la convierte S1 |
| `name`, `name_kana` | `last_name`+`first_name`, `last_name_kana`+`first_name_kana` | se parte por el primer espacio (normal o ancho); sin espacio, todo en `last_name` |
| `sex`, `bloodtype`, `birthday` | `sex` (ISO 5218), `blood_type`, `birth_date` | `age` se descarta (se calcula) |
| `email1`, `email2`, `mmail1`, `mmail2` | `mail1`, `mail2`, `mail3`, `mail4` | |
| `tel`, `mtel1`, `mtel2`, `fax` | `tel1`, `tel2`, `tel3`, `fax` | |
| `zip`, `pref`, `add`, `add_kana`, `add_number`, `add_build`, `add_build_kana` | `zip`, `pref`, `city`, `city_kana`, `street_address`, `building`, `building_kana` | igual que `customers` |
| `near_station1..4` | `near_stations` (json, lista) | |
| `entranceday`, `retirementday` | `hired_on`, `retired_on` (date) | texto legacy → `date` |
| `belongpost`, `employmentsystem`, `officialposition` (texto del maestro) | `department_id`, `employment_type_id`, `position_id` | se busca el id por nombre; si no existe se crea la fila del maestro |
| `salary`, `paidholiday`, `remarks` | `salary` (integer), `paid_holidays` (integer), `note` | |
| `traf_transport`, `traf_section1_1`, `traf_section1_2`, `traf_charge`, `traf_regularfee`, `traf_remarks` | `commute` (json: medio, tramo desde/hasta, importe, abono, nota) | solo se muestra |
| `reserve_flg` | `reservable_flg` | 指名: 0 no, 1 sí (lo lee S2 en reservas) |
| `common_flg` | `shared_account_flg` | `''` → 0 |
| `hyouji_flg`, `photo_hyouji_flg`, `comment` | `status`, `photo_display_flg`, `profile` | `hyouji_flg` → `status` (CONVENCIONES §4); `photo_hyouji_flg` es otra bandera (mostrar la foto): `'9'` → 0 |
| `rank`, `pos_rank` | `sort`, — | `pos_rank` es del POS: diferido |
| `kingaku_buai` | `commission_rate` (numeric(5,2)) | |
| `tb_staff_group_cd` | `staff_group_id` | FK a `staff_groups` (S5) |
| `pic_name` | `photo` | archivo copiado a Storage |
| `felica_no` | `felica_no` | |
| `synchronize_flg` | — | sync POS diferido |

**Maestros y avisos**

| Legacy | Nuevo |
|---|---|
| `tb_staff_group`: `cd`, `name`, `rank`, `disp_flg`, fechas | `staff_groups`: `id`, `name`, `sort`, `deleted_at`, timestamps |
| `tb_officialposition`: `cd`, `officialposition` | `positions`: `id`, `name` (`sort` = `cd`) |
| `tb_belongpost`: `cd`, `belongpost` | `departments`: `id`, `name` |
| `tb_employmentsystem`: `cd`, `employmentsystem` | `employment_types`: `id`, `name` |
| `tb_information`: `cd`, `shop_cd` (NULL = todas), `comment`, `staff_name`, `belongpost`, `employmentsystem`, `officialposition` (varios, por espacio), `update`, `disp_flg` | `staff_notices`: `id`, `shop_id`, `body`, `author_name`, `department_id`, `employment_type_id` (texto → id), `updated_at`; cada cargo → fila en `position_staff_notice` |
| `tb_koumoku` (`tb_name = 'tb_staff'`): `tb_koumoku`, `tb_koumoku_name`, `koumoku_disp`, `koumoku_check`, `mail_mag_select`, `koumoku_select`, `rank` | `staff_field_settings`: `field` (nombre nuevo de la columna según la tabla de `staff`), `label`, `form_flg` (`koumoku_disp`), `required_flg` (`koumoku_check`), `search_flg` (`mail_mag_select`), `csv_flg` (`koumoku_select`), `sort` |

**Tarjetas y lector**

| Legacy | Nuevo |
|---|---|
| `tb_card`: `cd`, `card_no`, `felica_no`, `staff_cd` (texto, `''` = libre), `card_flg` (`'0'` 未割当 / `'1'` 割当済), `update` | `staff_cards`: `id`, `card_no`, `felica_no`, `staff_id` (null si `''`), `assigned_at` (= `update` si `card_flg = '1'`); `card_flg` no se guarda: se deriva de `staff_id` |
| `tb_felica_mode` `cd = 1`: `mode`, `update` | `card_reader_settings`: `mode`, `mode_changed_at` |

**Calendario y asistencia**

| Legacy | Nuevo |
|---|---|
| `tb_shop_work`: `shop_cd`, `syukkinbi` (`'YYYY/M'`), `syukkin_time_1..31` (`'0'` cerrado / `'1'` abierto) | `business_days`: una fila por día existente del mes (`date` = año/mes/N; se ignoran N > días del mes), `type` = valor |
| `kintai_tb_config`: `tb_shop_cd`, `kijun_time` / `start_time` / `end_time` (`'HH<>MM'`), `password`, `pic_name` | `attendance_terminal_settings`: `shop_id`, `cutoff_time` / `start_time` / `end_time` (`time`), `password` (`Hash::make`), `background_image` |
| `kintai_tb_hiduke`: `cd`, `tb_staff_cd`, `hiduke` (epoch de la entrada), `kintai_flg` (texto 出勤, 欠勤…), `felica_no`, `bikou`, `disp_flg`, `hyouji_flg` | `attendance_days` con `source = 1`: `legacy_cd` = `cd`, `staff_id`, `shop_id` (de `tb_staff.shop_cd`), `work_date` = día de `hiduke` aplicando `kijun_time`, `day_type` (texto → enum), `felica_no`, `note` = `bikou` |
| `kintai_tb_kintai`: `kintai_tb_hiduke_cd`, `kintai_time` (epoch entero), `kintai_flg` (`'1'`..`'4'`), `felica_no`, `disp_flg` | `attendance_punches`: `attendance_day_id`, `punched_at`, `type` (mismo valor), `felica_no` |
| `tb_work_time`: `shop_cd`, `staff_cd`, `felica_no`, `syukkinbi` (epoch del día 1), y por N = 1..31 `syukkin_time_N`, `taikin_time_N`, `gaisyutu_time_N` (lista `<>`), `kisha_time_N` (lista `<>`), `syukkin_flg_N` (`出勤`/`遅刻`), `syukkin_kubun_N` (`退社`/`早退`/`''`) | por cada N con algún dato: una fila `attendance_days` con `source = 2`, `work_date` = mes + N, `day_type` = `syukkin_flg_N`, `leave_type` = `syukkin_kubun_N`; y `attendance_punches`: `syukkin_time_N` → tipo 1, `taikin_time_N` → 2, cada `gaisyutu_time_N` → 3, cada `kisha_time_N` → 4 |
| `tb_staff_shift_kind`: `cd`, `name`, `color`, `rank`, fechas, `disp_flg` | `shift_kinds`: `id`, `name`, `color`, `sort` |
| `tb_staff_shift`: `shop_cd`, `staff_cd`, `syukkinbi` (epoch del día 1), por N listas paralelas `gaisyutu_time_N` (inicios), `kisha_time_N` (fines), `syukkin_kubun_N` (cd de 区分), `komment_N` | `shifts`: una fila por posición de las listas de cada día: `date` = mes + N, `starts_at`, `ends_at`, `shift_kind_id`, `comment` |

**Integraciones**

| Legacy | Nuevo |
|---|---|
| `url_transfer_data`: `access` (timestamp), `tb_customer_cd` (NULL = visitante), `data_id` (texto = `tb_url_list.id`) | `paper_card_accesses`: `accessed_at`, `customer_id`, `paper_card_id` (buscado por `paper_cards.code` de S4) |
| `contents_master` `mobileshop_auth_passcode` (última fila por `update_date` para cada `int_data`) | `mobile_shop_settings`: `shop_id` = `int_data`, `passcode` = `text_data` |
| `tb_photo_album` (`cd` = carpeta de las imágenes, `tb_customer_cd`, `comment`, `disp_date`, `pic_flg`) | tabla de S2 (pedido 5.2) |
| `tb_beacon_history` (`cd` siempre 1, `beacon_cd`, `tb_customer_id`, `touroku_date`, `tb_shop_cd`) | tabla de S4 (pedido 5.4); sin PK útil en el legacy: id nuevo |

---

## 3. Servicios viejos → Laravel 13

| Legacy | Dónde | Reemplazo |
|---|---|---|
| `hatuban` + `SELECT … FOR UPDATE` | staff, grupos, 区分, kintai, turnos, album | autoincremental (`id`); el importador conserva `cd` como `id` |
| `array_walk_recursive($_REQUEST,'form2Db')` | todo category5 | Form Requests + Eloquent con bindings (sin doble escape `O''''Neil`) |
| SQL concatenado (`api/customer.php`, `CustomerForMobileShop`, `change_syukkin2`) | api, working | Query Builder con lista blanca de columnas filtrables |
| `mktime(..., is_dst)` (calendarios a 1970) | kintai, working, config, schedule | `CarbonImmutable` (`startOfMonth()`, `addMonthNoOverflow()`), zona `Asia/Tokyo` |
| Corte de jornada a las 5:00 (`+86400`) | `change_syukkin2.php` | `AttendanceService::resolveWorkDate()` con `attendance_terminal_settings.cutoff_time` por tienda; pruebas unitarias |
| Cálculo de horas en PHP/JS (`index.php`, `kintai_update.php`) | kintai, working | `AttendanceCalculator` (horas, descansos, extras, 基準) con pruebas Pest unitarias |
| CSV con Flash + archivo temporal + `csv_file_del` | staff, kintai, working | `StreamedResponse` (Shift_JIS con `mb_convert_encoding`, como el legacy) |
| Fotos e imágenes en `hp/uploads/c5/`, `c1/photoalbum` | staff, kintai config, mpa_albumUpdate | `Storage::disk('public')` + `intervention/image` (pedido de paquete, 5.1-e); ImageMagick `system('convert')` descartado |
| Estado de búsqueda en `$_SESSION` | staff_list, working_list | query string + Form Request (`StaffSearchRequest`) |
| `login_pass` del personal en texto plano | staff | `Hash::make` (lo resuelve el login de S1; la pantalla solo guarda el hash) |
| `kintai_tb_config.password` en texto plano | kintai | `Hash::make`; la usaba el terminal 勤怠 de touchcation (`kintai_crm_n`), hoy fuera de alcance: si vuelve, la verifica con `Hash::check` (pedido 5.5) |
| `db_sync_all2` / `mpa_customerSync` / `setPosdomain` (BD POS por tienda) | staff, api | interfaz `App\Services\Integraciones\PosGateway` (`syncStaff`, `syncCustomers`, `setDomain`) **sin implementación**; binding a una clase nula que registra en log. Fuera de alcance |
| Control por IP / `SERVER_ADDR = 202.218.51.245` | working_chouhyou, schedule | Gate `viewWorkReport` + Policy `AttendancePolicy` según los niveles de S1 |
| `cKengen` / `ACCESS_LEVEL_*` | todo category5 | Policies (`StaffPolicy`, `AttendancePolicy`, `ShiftPolicy`) sobre los niveles de S1 |
| `Firebase\JWT` RS256 con `keys/*.pem` | api | mismo paquete `firebase/php-jwt` (pedido 5.1-e), claves en `storage/keys/` fuera de git, `config('integraciones.mobileshop.*')`; middleware `MobileShopJwt` que responde el JSON de error con HTTP 200 |
| `CRM_API_KEY` en `checkKey.php` | tool/api | middleware `CheckCrmApiKey` (`hash_equals`), `config('crm.api_key')` |
| `file_get_contents`/cURL a extlink (`apiSnderAuth`, `apiJudServiceTgt`, `apiSnderCan`) | mobile_shop | `MobileShopExtlinkClient` con `Http::` (timeout, `Http::fake()` en pruebas) |
| `file_get_contents` a `g-api.net/api/mpa/getComingHistory` | appview/visit_history | `Http::` con `Http::fake()` en pruebas |
| `Point::setPoint()` con `FOR UPDATE` | api point_use, exchange, appview, tool/api | `PointService` de S3 (`DB::transaction` + `lockForUpdate()`), pedido 5.3 |
| `cUtility::raitenFunc` (visita + sellos + puntos) | mpa_setraiten, tool/api | `VisitService` de S2 (pedido 5.3) |
| `contents_master` / `admin_master` (clave-valor; `mypage_config` PHP serializado) | api passcode, mpa, appview | tablas tipadas `<algo>_settings` (CONVENCIONES §4): `mobile_shop_settings` (S5), `app_settings` / `app_images` (S4), configuración global de S1 (`application_use_flg`, privacidad); `unserialize` de `mypage_config` solo en el importador |
| URLs `http://<host>/hp/uploads/...?r=<mtime>` que esperan la app y `mpa_*` | mpa | Storage + la redirección genérica `/hp/uploads/*` → Storage de S1; S5 sigue devolviendo la URL con forma `hp/uploads/...?r=<mtime>` |
| Envío de correo (`crm_send_mail`) | — (S5 no envía correo) | — |
| `crontab` escrito por PHP (`regenerateCronJob`) | cron | Scheduler; S5 solo registra `integraciones:shukei` (mensual) si se confirma, en `App\Providers\IntegracionesServiceProvider` |
| Sesión PHP como identidad (`sess` = `session_id()`) | appview quick_reservation | grupo `web` y `session()->getId()` |

---

## 4. Orden de implementación

1 punto ≈ media jornada. Dependencias: **S1** = `staff`, niveles de permiso, configuración global, carga de `routes/publico/integraciones.php`, redirección `/hp/uploads/*`; **S2** = clientes, visitas, productos, reservas; **S3** = puntos, cupones, sellos, encuestas; **S4** = `paper_cards`, `app_images`, `app_settings`, `recommendations`, beacon; los terminales de asistencia (`touchcation/kintai_crm_n` y `touchcation/timecard`) son touchcation, fuera de alcance: los pasos 4 y 5 se hacen sobre datos importados y correcciones manuales (ver §7, primer punto).

| # | Submódulo | Depende de | Puntos |
|---|---|---|---|
| 1 | Base: migraciones de §2.1, modelos, enums en `app/Enums/Operaciones/` (`CardReaderMode`, `AttendanceSource`, `AttendancePunchType`, `AttendanceDayType`, `AttendanceLeaveType`, `BusinessDayType`), factories, seeder, menú `config/menu/operaciones.php`, `PosGateway` nulo | S1 `staff` (se puede empezar con las tablas propias) | 3 |
| 2 | スタッフ管理: ficha (alta-confirmación-registro-completo, detalle, baja, reactivar), búsqueda + CSV, foto, grupos, 検索項目設定, インフォメーション | S1 `staff` + permisos | 12 |
| 3 | 初期設定: 営業日設定 (`business_days`), カードリーダーモード, カード管理 | 2 | 4 |
| 4 | 勤怠管理 (kintai): lista del día, alta/edición/borrado de marcas, contraseña y 各種設定 del terminal, シフト区分, CSV | 1, 2; `AttendanceCalculator` | 14 |
| 5 | 勤怠一覧 / 集計 (working): calendario, lista del día (+ variantes y NG), corrección, mensual, totales, 帳票, CSV | 3, 4 | 9 |
| 6 | スケジュール管理: turnos diario y mensual | 4 (`shift_kinds`) | 5 |
| 7 | API JWT tienda móvil (13 endpoints + 404) con pruebas de contrato | S1 paquete JWT y config; S2 `customers`, productos canjeables; S3 `PointService` | 8 |
| 8 | `tool/api` (8 endpoints) y CTI (3) | S2 clientes/visitas (`VisitService`), S3 puntos/sellos | 6 |
| 9 | `mpa_*` (5 endpoints) | S1 configuración global y `/hp/uploads`; S2 `VisitService`, fotos de cliente; S4 `app_images`, `app_settings`, beacon | 4 |
| 10 | `appview/*` (26 pantallas) | S2 clientes, reservas rápidas; S3 cupones, sellos, encuestas, puntos | 8 |
| 11 | Utilitarios: `crm_version`, `ptag`, `air` (condicional), `integraciones:shukei` | S4 `paper_cards`; S2 ventas (shukei) | 2 |
| | **Total** | | **75** |

Los pasos 2-6 (operaciones) no dependen de S2/S3/S4 y pueden avanzar en cuanto S1 integre `staff`. Los 7-11 (integraciones) van detrás de los servicios de S2/S3.

---

## 5. Pedidos

### 5.1 A S1 (núcleo)

- **a. Rutas de integración** (ya acordado por S0): cargar `routes/publico/integraciones.php` fuera del grupo `admin`, sin `auth:admin`, sin CSRF y sin prefijo, nombre `publico.integraciones.*`. URL literales: `/api/auth`, `/api/customer`, `/api/shop`, `/api/point`, `/api/mobile_shop/*`, `/api/mpa_*.php`, `/appview/*.php`, `/system/tool/api/category{1,5}/*.php`, `/system/desktop_request/*.php`, `/ptag/{id}`, `/version/crm_version.php`, `/air/air_info_out.php`. Pedido concreto: que el prefijo `api` de `routes/api.php` no capture estas URL antes que las de S5, y que `appview/*` tenga sesión (`StartSession`).
- **b. Tabla `staff`** (modelo `Staff`) con las columnas de la propuesta de §2.3 (`tb_staff` → `staff`): persona, dirección y contacto según el estándar, `code`, `shop_id`, `staff_group_id`, `department_id`, `employment_type_id`, `position_id` (FK a maestros de S5: o la migración de S5 va antes, o S1 agrega las FK en una migración posterior), `hired_on`, `retired_on`, `photo`, `felica_no`, `reservable_flg`, `commission_rate`, `login_id`, `password`. S1 decide; S5 solo hace las pantallas de gestión.
- **c. Configuración (`config/integraciones.php` o `config/services.php`):** `MOBILESHOP_API_USER`, `MOBILESHOP_API_PASS`, `MOBILESHOP_EXTLINK_PASS` y su URL, rutas de `keys/private.pem` / `keys/public.pem`, `CRM_API_KEY`, `crm_version` (`2.704`), lista de dominios (`DOMAIN_LIST`) con una conexión de BD por dominio para `mobile_shop/customer`, URL de `g-api.net`.
- **d. Importador (decide S0):** `legacy_cd` en las tablas de S5; en `shops`, `customers`, `staff`, `visits` y productos, `id = cd` con la secuencia ajustada, porque sus `cd` salen en contratos externos (ver §2, «Contratos que exponen `cd`»); omitir filas de fechas 1970 de `tb_shop_work`/`tb_work_time` o marcarlas (PENDING 1b.1); partir `tb_work_time` y `tb_staff_shift` por día con el mapeo de §2.3.
- **e. Paquetes (composer):** `firebase/php-jwt` y `intervention/image`.
- **f. Configuración:** global de S1 para `application_use_flg` (`admin_master`) y la política de privacidad por tienda (`appview/privacyPage.php`); la pantalla 機能設定 de S1 edita el passcode de la tienda móvil en `mobile_shop_settings` (modelo de S5); el importador mapea `contents_master` `mobileshop_auth_passcode` a esa tabla (§2.3).
- **g. Niveles de permiso** (equivalente a `cKengen`) para las Policies de S5 y el Gate `viewWorkReport`.
- **h. Endpoint de código postal** (`zip.php`) compartido, si S1/S2 ya lo tiene en CRMSystem.

### 5.2 A S2 (clientes, ventas, reservas)

- `VisitService::register(customer, shop, origin)` (lo que hace `raitenFunc`: intervalo mínimo, sellos y puntos), para `mpa_setraiten`, `tool/api` y CTI.
- Búsqueda de clientes reutilizable para `api/customer`, `tool/api/searchCustomerList`, CTI (por teléfono).
- Productos canjeables por puntos (`sales_items`): `exchange_flg`, `exchange_points`, tiendas disponibles (pivote `product_shop`), `description`.
- Fotos de cliente (`tb_photo_album`) como modelo de S2, con método para dar de alta una foto (lo usa `mpa_albumUpdate` y `appview/photo_gallery`).
- Alta de socio desde `appview/member_*` (servicio de registro de cliente) y reserva rápida (`QuickReservation`, `numbered_ticket`).
- `business_days` (営業日) es de S5; S2 la lee para el calendario de reservas.
- **Mapeo inverso `customers` → `tb_customer`** documentado (lo necesita `LegacyCustomerResource`): `email1`→`mail1`, `kojin_mobile`→`tel2`, `kojin_birth`→`birth_date` (¿formato legacy de texto?), `pref`/`add`/`add_build`/`add_number` → `pref`/`city`/`building`/`street_address`, `name`/`name_kana` (un solo campo legacy: ¿con qué separador se une `last_name`+`first_name`?), `touroku_kubun='2'` (socio), `kanri_id` → `management_no`, `touroku_date`/`update_date` (epoch). Sin esto, la API no puede responder igual que el legacy.

### 5.3 A S3 (promoción)

- `PointService` (`add`, `use`, `balance`, historial) con `lockForUpdate()`, y tipos nuevos en `PointMovement`: **uso desde la API de la tienda móvil** y **canje de producto**, para reproducir `terminal_cd = -3` / `staff_cd = -1` / `0` y las notas `api:point_use` y `exchangeSalesItem:<cd>:<nombre>`.
- `GET /api/point` devuelve las filas de `tb_point_log` con sus columnas (`cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, action_flg, touroku_date, shori_mae_point, <campo de puntos>`): `customer_points` necesita `staff_id`, un origen (`terminal_id` o código: API = -3) y el saldo previo (`balance_before`) para reconstruirlas, o S3 conserva esos datos de otra forma. S5 arma la respuesta legacy en un `LegacyPointLogResource`.
- Cupones (`coupon`, uso, usados), sellos y encuestas (`anketo2` con premio) consumibles desde `appview`.

### 5.4 A S4 (contenido)

- `paper_cards` (de `tb_url_list`, ya en su diseño): S5 crea `paper_card_accesses` con FK a ella y `ptag` busca por `code`. Por la regla nueva de CONVENCIONES §4, su `display_flg` pasaría a `status` (`ptag` solo redirige si está activa).
- Historial de beacon (`tb_beacon_history`) para `mpa_setraiten` con `membership=1`.
- Ya acordado: `mpa_*` y `appview` leen `app_images` (+ `app_image_shop`), `app_settings` y `recommendations` en solo lectura. Para responder igual que el legacy S5 necesita del modelo `AppImage` la ruta del archivo y su `mtime` (para `?r=<mtime>`), el hueco (`position`) y `link_url`; con eso arma `imageUrl` como `http://<host>/hp/uploads/c6/<carpeta legacy>/<archivo>?r=<mtime>`, que resuelve la redirección `/hp/uploads/*` de S1. Si la ruta en Storage no conserva el nombre del archivo legacy, S5 devuelve la URL de Storage (cambio aceptable si la app solo carga la imagen: anotado para Japón).

### 5.5 A S0 (terminal de asistencia)

- Los que escriben `tb_work_time` son `touchcation/timecard/control.php` y `hp/card/felica_detail.php`, los dos fuera de alcance: en Laravel nadie generará marcas `source = 2` salvo las correcciones manuales de working. Se migran las pantallas y los datos históricos igual; si touchcation vuelve, registrará con `AttendanceService::punch()` de S5. Confirmar con Japón si el terminal 勤怠 (`touchcation/kintai_crm_n`, también touchcation) sigue en uso: es el que escribe `kintai_tb_*`.

---

## 6. Contratos externos (para las pruebas Pest de contrato)

Regla: misma URL, mismos parámetros, misma respuesta byte a byte (claves, orden, tipos, erratas, HTTP 200 en error). Cada endpoint tiene una prueba en `tests/Feature/Integraciones/Contract/` que compara con una respuesta grabada del legacy (`tests/Fixtures/integraciones/<endpoint>.json`), obtenida del Docker de crm2-php8 con datos de prueba (nunca contra producción ni con `activation=1`).

Las respuestas JSON del legacy se arman **concatenando cadenas** (`tool/api`) o con `json_encode` sin flags (`api/`, `mpa_*`): eso fija el escape (japonés como secuencias `\uXXXX` y `\/` en URLs si no hay flags; a confirmar archivo por archivo al grabar los fixtures) y los números como texto (`"max":"3"`). Laravel debe usar `json_encode` con las mismas flags que cada archivo legacy y cadenas donde el legacy pone cadenas; por eso los controladores devuelven `response($cuerpo, 200)` con un `LegacyJson::encode()` común, no `response()->json()`.

### 6.1 API JWT tienda móvil — `/api/*` (`Content-Type: application/json`, siempre HTTP 200)

| Endpoint | Entrada | Éxito | Errores |
|---|---|---|---|
| `POST /api/auth` | cuerpo JSON `user_id`, `password` | `{"message":"Success.","jwt":"<RS256, claims iat, exp=+30 días, user_id>"}` | `{"message":"Invalid config."}`, `{"message":"Invalid User."}` |
| (todas las demás) | cabecera `Authorization: Bearer <jwt>` | — | token inválido: `{"status":"ng","message":"<mensaje de firebase/php-jwt>"}`; `passcode` sin cabecera: hoy error de índice → responder el mismo JSON de token inválido (anotado) |
| `GET /api/customer` | cuerpo: `id`, `kojin_mobile` (=), `tb_shop_cd` (num.), `user_address` (LIKE sobre dirección), `kojin_birth` (LIKE `%v`), otras claves de una lista blanca (LIKE `%v%`); sin filtros → nada | `{"message":"Success.","user_id":…,"payload":<cuerpo>,"result":[{id,email1,zip,user_address,name,name_kana,kojin_mobile,kojin_birth,tb_shop_cd}]}`; `result:""` si vacío; solo activos, orden `cd` | — |
| `GET /api/shop` | — | `result:[{cd,kaishamei,kaishamei_kana}]` (activas, orden `cd`) | — |
| `GET /api/point` | cuerpo: `user_id` (= `tb_customer.id`, entero > 0), `page`, `limit` (10), `range` | `result:{count,data:[filas con columnas de tb_point_log],limit,page}`, orden fecha DESC | `{"status":"ng","message":"必須情報がありません。"}` |
| `GET /api/mobile_shop/customer/<cd>` | `<cd>` entero | `result:{cd,id,email1,name,name_kana,zip,pref,add,add_build,add_number,kojin_birth,kojin_mobile,touroku_date,update_date}` | — |
| `GET /api/mobile_shop/customer` | cuerpo: `disp_flg`, `user_id` (`<dominio><id lpad 9>`), `id` (varios separados por espacio = OR), `kojin_mobile`; al menos uno | `result:{count,data:[{mycolle_cateogry,id,user_id,email1,zip,user_address,name,name_kana,kojin_mobile,kojin_birth,tb_shop_cd,disp_flg}]}` en N BD, solo socios, orden `id DESC`; sin filtros `result:false` | sin dominios: `ERROR: No domain list.` |
| `POST /api/mobile_shop/fetch_paid_member` | `customer_id` | resultado de extlink (`apiSnderAuth` → `apiJudServiceTgt`) | los de extlink |
| `POST /api/mobile_shop/send_unpaid_member_status` | `customer_id` | resultado de extlink (`apiSnderCan`) | los de extlink |
| `POST /api/mobile_shop/passcode` | `shop_cd`, `passcode` | `{"status":"ok"}` | `{"status":"ng","message":"invalid passcode"}` |
| `GET/POST /api/mobile_shop/point` | `customer_id` (entero > 0) | `result:{service_point,point_yukoukigen}` o `result:false` | `{"status":"ng","message":"IDがありません。"}`, `{"status":"ng","message":"顧客コードが見つかりません。"}` |
| `POST /api/mobile_shop/point_use` | `customer_id`, `shop_cd`, `use_points` (enteros > 0) | resultado de `setPoint` **sin envoltorio** | los de `setPoint` |
| `POST /api/mobile_shop/point_exchange_item` | `customer_id`, `salesItemCd`, `shop_cd` | envoltorio común con el resultado del canje | `{"status":"ng","res":"不正なリクエスト。"}` / `顧客情報がありません。` / `交換可能商品ではありません。` / `使用可能な店舗ではありません。` / `ポイントが不足です。` |
| `GET /api/mobile_shop/sales_items[/<cd>]` | cuerpo: `page`, `limit` (10), `range` (3), `name`, `discription` (sic, ILIKE por palabras con espacio normal o ancho), `available_shop_cds`, `is_exchangeable_of_point`, `number_of_points_exchanged_start/_end`, `hyouji_flg` | `result`: ítem, o `{count,data,limit,page}` (activos) | — |
| `GET /api/<desconocido>` | — | — | legacy: fatal (500). Laravel: `{"message":"Not Found."}` con 404 (decisión: no se reproduce un fatal) |

Contrato que no se rompe: erratas (`discription`, `cutomer_cd`, `mycolle_cateogry`), HTTP 200 en error, GET con cuerpo JSON (`$request->json()`), `user_id`/`customer_id` = id público del cliente (`customers.code`), `terminal_cd = -3` / `staff_cd = -1` (0 en el canje) como origen en el historial de puntos.

### 6.2 `mpa_*` — `/api/mpa_<nombre>.php` (sin autenticación)

| Endpoint | Entrada | Respuesta |
|---|---|---|
| `mpa_corresponding.php` | GET/POST `login_id` (opc.) | `{"status":"ok"\|"ng","version":"0.0.2","application_main_image":{"main","small"},"apli_home_thumbnail":{"main"},"apli_home_comment"}`; URLs absolutas `http://<host>/hp/uploads/c6/...?r=<mtime>` (pedido 5.4) |
| `mpa_getTopBanner.php` | `login_id` (opc.) | `{"status":"ok","errMsg":"","data":[{"imageUrl","url"}]}` |
| `mpa_getCoverFlow.php` | `login_id` (opc.) | `{"status","errMsg","data":[{"imageUrl","url"}]}` |
| `mpa_albumUpdate.php` | POST multipart `domain`, `cd` (id público), `pic_name` (imagen), `comment` | **302** a `http://b-develop.g-api.net/api/mpa/myAlbumPage?domain=…&cd=…&status=ok\|err1\|err2&comment=…` (host en config) |
| `mpa_setraiten.php` | `regist_id` (numérico), `membership` (1 = beacon), `shop_cd`, `beacon_cd` | `{"status":"ok","customer_info":{"cd","id","name"}}` o `{"status":"error","errMsg","errorMsg"}` con `入力される内容は数字のみでお願いいたします。` / `顧客が特定できませんでした。` / `来店処理できませんでした。インターバルなどをご確認ください。` |

### 6.3 `appview/*.php` (HTML para el WebView)

Contrato = URL y parámetros (`customer_cd`, `cutomer_cd` en `visit_history`, `shop_cd`, `staff_cd`, `kensu`, `page`, `demo`, `sess`), no el HTML byte a byte: se compara que la pantalla muestre los mismos datos y enlaces. Excepciones con formato fijo: `memberId.php` (texto `login_id` o `error`) y `quick_reservation_register.php` y `coupon_control.php` (JSON `{status…}`), que sí van byte a byte.

### 6.4 `tool/api` — `/system/tool/api/<categoryN>/<archivo>.php`

Común: `key` (= `CRM_API_KEY`, `hash_equals`; si falta o no coincide, **cuerpo vacío** y 200). `data_type=jsonp` + `callback` → `callback({…})` (con `callback` escapado como `h()`); si no, JSON. Formato común: `{ "status":"<ok/…>", "max":"<n>", "result":[ … ] }` (con espacios tal cual; `max` como texto).

| Endpoint | Parámetros | `result` |
|---|---|---|
| `category1/searchCustomerList.php` | `keyword`, `shop`, `limit`, `offset` | lista de clientes |
| `category1/getCustomerDetail.php` | `cd` | objeto `{ … }` (ficha) |
| `category1/getSearchCateFormData.php` | `shop`, `rank` | lista de campos del buscador |
| `category1/getRaitenList.php` | `cd`, `s_date`, `e_date`, `limit`, `offset` | lista de visitas |
| `category1/getRaitenDetail.php` | `cd` | objeto (visita) |
| `category1/addRaitenRecord.php` | `customer`, `shop`, `staff`, `raiten_log_stamp_flg` | sin `result`: `{ "status":"<…>" }` |
| `category1/setRaitenDetail.php` | `kaiin_cd`, `raiten_cd`, `raiten_y/m/d/h/i`, `kaikei`, `shiharai_houhou`, `shouhi_point`, `sounyu_point`, `sounyu_point_add_remove_flg`, `selected_shop_cd`, `related_features_cd`, `meisai_mail`, `add_record_verify` | `{ "status": "SUCCESS", "result": "<cd>" }` o `{ "status": "error", "result": { <errores> } }` (la variante JSONP del error está rota en el legacy por precedencia de operadores: se emite el formato correcto, anotado) |
| `category5/getStaffList.php` | `cd`, `shop`, `limit`, `offset` | lista del personal |

Las claves exactas de cada `result` se fijan al grabar los fixtures (paso 8).

### 6.5 CTI — `/system/desktop_request/*.php`

| Endpoint | Parámetros | Respuesta |
|---|---|---|
| `ctiWindow.php` | `tel` (solo dígitos), `act` (`new`), `customerCd` | 302: `act=new` → alta de cliente con `tel`; si hay módulo de reservas activo → agenda de reservas (`isCti=1&tel&customerCd`); si no → búsqueda de clientes por teléfono. En Laravel, las URL destino son las rutas nuevas de S2 (el destino exige sesión). |
| `customerDetailHtml.php` | `cd` | fragmento HTML de la ficha del cliente |
| `jGetCustomerData.php` | `cd`, `id` o `tel` (al menos uno) | texto plano: una línea por socio activo `cd<>name<>name_kana<>id<>kanri_id<> \n` (**espacio antes del salto obligatorio**), orden `cd`; sin criterio o sin filas: `NO_DATA` |

### 6.6 Utilitarios

| Endpoint | Respuesta |
|---|---|
| `GET /version/crm_version.php` | `text/plain` `2.704`, sin salto de línea |
| `GET /ptag/<ID>` (cookie `UID`) | 302 a la URL de la tarjeta (`activa` y visible) + registro de acceso; textos `無効なIDです。` / `GETデータがありません` |
| `GET /air/update-info.xml`, `/air/crossdomain.xml` | estáticos, idénticos |
| `GET /air/air_info_out.php?login_id&kensu` | último aviso (`staff_notices`) visible para ese empleado (tienda, cargo; `master` ve todo); formato a grabar del legacy |

---

## 7. Riesgos y notas para Japón

- **Asistencia sin fuente de datos (importante, decide Antony/S0):** los dos registradores de asistencia son touchcation (`touchcation/kintai_crm_n` escribe `kintai_tb_*`; `touchcation/timecard` escribe `tb_work_time` y usa `tb_felica_mode`, `tb_card`), y touchcation está confirmado como **no usado**. Si es así, 勤怠管理 (kintai), 勤怠一覧 (working) y 初期設定 (lector FeliCa y tarjetas) solo muestran datos históricos y correcciones a mano: 27 de los 75 puntos (pasos 3 a 5). Propuesta: hacer primero スタッフ管理, 営業日設定 y turnos (que sí se usan sin terminal) y confirmar antes de empezar el paso 4; si no se usan, pasar esas filas a `descartado: sin terminal (touchcation)` y dejar solo el importador de datos históricos.
- **Dos sistemas de asistencia** (`kintai_tb_*` y `tb_work_time`): ¿son el mismo dato? (§2.1).
- **La baja de un empleado borra su `tb_work_time`** (historial del mes): se reproduce con borrado lógico de sus `attendance_days` del mes en curso, no físico, hasta que Japón confirme.
- **`mobile_shop/customer` consulta N BD** (`DOMAIN_LIST`): hace falta la lista real de dominios y credenciales.
- **Sin autenticación** en `mpa_*`, `appview` (IDOR por `customer_cd`) y CTI (`customerDetailHtml`, `jGetCustomerData`): se mantiene el contrato; propuesta para Japón: token firmado y throttle. Se agrega `throttle` donde no cambia la respuesta normal.
- **`md5(login_pass)` en URL** (`mpa_getTopBanner`) y cookie `UID` de `ptag`: dependen de cómo S2 guarde la contraseña del cliente; si pasa a hash, estos enlaces dejan de funcionar → decisión de Japón.
- **`passcode` de la tienda móvil en texto plano** (`mobile_shop_settings.passcode`): se compara con `hash_equals`; el valor sigue legible hasta que Japón acepte cambiar el flujo.
- **Fechas 1970 guardadas por producción** en 営業日 y horarios (PENDING 1b.1): el importador las separa.
