# Diseño S5 — Operaciones (業務支援管理) e integraciones externas

> Sesión S5 · rama `l13/operaciones` · rehecho el 2026-11-27 sobre **la misma BD y la misma UI que el legacy** (`Doc/contratos/CONVENCIONES.md` §0). La versión con tablas nuevas (aprobada el 2026-11-26) quedó obsoleta: está en git (commit 84f6590) y su código, en el stash «obsoleto: tablas nuevas (S5 operaciones)».
> Fuentes: `Doc/analisis-legacy/05-operaciones-terminales-reservas.md` (sección category5), `07-pos-api-y-externos.md` (salvo category11, category12, `r/`, `reserve/`), `00-nucleo-y-base.md` §10 (`tool/api` y CTI, que CONVENCIONES §3 asigna a `integraciones`), el esquema `public_html/database/schema/pgsql-schema.sql` y el código de `crm2-php8` (solo lectura).
> Fuera de alcance (Antony, 2026-11-26): POS (category11, `pos_session.php`/`CRMPOS-<id>`, servidores POS de tienda → `PosGateway` sin implementar), SNS (category12) y touchcation completo (incluida su parte de `version/`).

**⏸ 勤怠 en espera (S0, 2026-11-26):** los dos registradores de asistencia del legacy son touchcation (`touchcation/kintai_crm_n` escribe `kintai_tb_*`; `touchcation/timecard` escribe `tb_work_time` y usa `tb_felica_mode`/`tb_card`), y touchcation no se usa. Hasta que Antony confirme si 勤怠 sigue en uso, las filas marcadas **⏸** están diseñadas pero no se implementan (§4).

---

## 1. Tabla de paridad

Estados: `pendiente`, `pendiente ⏸` (espera a Antony, ver arriba), `hecho`, `descartado: motivo`, `diferido: motivo` (fuera de alcance por ahora).
Tablas: **L** = lee, **E** = escribe. Todas son las del legacy, sin tablas nuevas (§2).
Pantallas: grupo `admin` (`routes/modulos/operaciones.php`, nombre `admin.operaciones.*`), controladores en `App\Http\Controllers\Admin\Operaciones\`, vistas Blade con el HTML del legacy (§2b) sobre `layouts.legacy`. Las URL literales que llaman los JS del legacy (`/system/category5/…/change_syukkin.php`, `ajax_session_update.php`, formularios con `action` relativo) van en `routes/legacy/operaciones.php`, nombre `legacy.operaciones.*`, con el guard del personal.
Integraciones: `routes/publico/integraciones.php`, que S1 carga fuera del grupo `admin`, sin `auth:admin`, sin CSRF y sin prefijo, con la URL legacy literal (incluido `.php`) y nombre `publico.integraciones.*`. Controladores en `App\Http\Controllers\Publico\Integraciones\` (CONVENCIONES §2).

### 1.1 スタッフ管理 (`system/category5/staff/`)

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas legacy | Estado |
|---|---|---|---|---|---|
| `staff_entry.php` | スタッフ新規登録 | `admin.operaciones.staff.create` | `StaffEntryController@create` / `@edit` / `@rewrite` | L `tb_staff`, `tb_shop`, `tb_staff_group`, `tb_officialposition`, `tb_belongpost`, `tb_employmentsystem`, `tb_base_system_grandchild` | hecho |
| `staff_entry_confirm.php` | (confirmación) | `admin.operaciones.staff.confirm` (POST) | `StaffEntryController@confirm` | L las mismas | hecho (`LegacyCheck`; datos en crudo, sin doble escape) |
| `staff_entry_register.php` | スタッフ登録情報 | `admin.operaciones.staff.store` | `StaffEntryController@store` | E `tb_staff`, `tb_belongpost`, `hatuban` | hecho (cd con createHatsuban, ID con createID; `PosGateway::syncStaff()` nulo) |
| `staff_entry_complete.php` | スタッフ登録情報 | `admin.operaciones.staff.complete` | `StaffEntryController@complete` | L `tb_staff`, `tb_shop`, `tb_staff_group` | hecho |
| `staff_list.php` | スタッフ一覧 | `admin.operaciones.staff.index` | `StaffController@index` (+ `@search`) | L `tb_staff`, `tb_koumoku` | hecho (`StaffSearch`: condiciones validadas en sesión, columnas en lista blanca) |
| `staff_detail.php` | スタッフ登録情報 | `admin.operaciones.staff.show` | `StaffController@show` | L `tb_staff`, `tb_shop`, `tb_base_system_grandchild` | hecho (pestañas por GET `?tab=`) |
| `staff_delete_register.php` | スタッフ登録情報 (削除) | `admin.operaciones.staff.destroy` | `StaffController@destroy` | E `tb_staff` (`disp_flg='9'`), `tb_card`, `tb_work_time` | hecho (no borra `tb_work_time`: solo vacía su `felica_no`, §7) |
| `staff_recover_confirm.php` | スタッフ登録情報 (復活) | `admin.operaciones.staff.restore` | `StaffController@restoreConfirm` / `@restore` | L/E `tb_staff` | hecho (vuelve a comprobar ID/メール antes de restaurar) |
| `staff_card_delete.php` | (カード解除) | `admin.operaciones.staff.card.destroy` | `StaffController@releaseCard` | E `tb_card`, `tb_staff`, `tb_work_time` | hecho (DB pura; permiso 1024/3) |
| `staff_group.php`, `staff_group_frm.php`, `staff_group_register.php` | スタッフグループ設定 | `admin.operaciones.staff-groups.*` (index, store, update, destroy, `sort`) | `StaffGroupController` | L/E `tb_staff_group`, `hatuban` | hecho (`/admin/operaciones/staff-groups`, `/staff-fields`; pruebas en `StaffScreensTest`) |
| `form_config.php`, `form_config_register.php` | スタッフ検索項目設定 | `admin.operaciones.staff-fields.edit` / `.update` | `StaffFieldController@edit` / `@update` | L/E `tb_koumoku` (`tb_name='tb_staff'`) | hecho (`/admin/operaciones/staff-groups`, `/staff-fields`; pruebas en `StaffScreensTest`) |
| `information.php`, `information_register.php`, `information_delete_register.php` | インフォメーション管理 | `admin.operaciones.information.*` (index, store, destroy) | `InformationController` | L/E `tb_information`, `hatuban`; L `tb_officialposition`, `tb_shop` | hecho (`/admin/operaciones/information`; pruebas en `StaffScreensTest`) |
| `csv_download.php`, `csv_download/csv_file_out.php` | (CSV スタッフ) | `admin.operaciones.staff.csv` | `StaffController@csv` | L `tb_staff`, `tb_koumoku`, `tb_shop` | hecho (`StreamedResponse` SJIS como el legacy, `text/x-csv; charset=Shift_JIS`) |
| `csv_download/csv_file_del.php` | — | — | — | — | descartado: sin archivo temporal |
| `zip.php` | (código postal → dirección) | endpoint común de S1 sobre la tabla `zip` | — | L `zip` | hecho por S1 (`/system/category5/staff/zip.php`) |
| `inc/disp_pic.php`, `inc/upload.php` | (foto) | `admin.operaciones.staff.photo.upload` / `.temp` | `StaffEntryController@uploadPhoto` / `@tempPhoto` | E `tb_staff.pic_name` | hecho (`StaffPhotoStore`: GD, EXIF, 200 px; final en `hp/uploads/c5/staff/<cd>/<cd>.jpg` del disco public) |
| `inc/search_set1.php`, `inc/search_set2.php` | (formulario de búsqueda) | parcial Blade `admin/operaciones/staff/partials/search-fields` + `StaffSearch` | — | L `tb_koumoku`, `tb_shop` | hecho |

### 1.2 勤怠管理 — terminal de asistencia (`system/category5/kintai/`)

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas legacy | Estado |
|---|---|---|---|---|---|
| `index.php` | 日付別勤怠データ一覧 | `admin.operaciones.kintai.index` | `KintaiController@index` | L `kintai_tb_config`, `kintai_tb_hiduke`, `kintai_tb_kintai`, `tb_staff`; E `kintai_tb_config` (crea la fila de la tienda) | pendiente ⏸ (Carbon, sin 1970) |
| `kintai_new.php` | 勤怠データ新規追加 | `admin.operaciones.kintai.create` | `KintaiController@create` | L `kintai_tb_config`, `tb_staff` | pendiente ⏸ |
| `kintai_new_up.php` | 勤怠データ新規追加 | `admin.operaciones.kintai.store` | `KintaiController@store` | E `kintai_tb_hiduke`, `kintai_tb_kintai`, `hatuban` | pendiente ⏸ (transacción; sin el INSERT+UPDATE en una sola consulta) |
| `kintai_update.php` | 勤怠データ編集 | `admin.operaciones.kintai.edit` | `KintaiController@edit` | L `kintai_tb_config`, `kintai_tb_hiduke`, `kintai_tb_kintai`, `tb_staff` | pendiente ⏸ |
| `kintai_update_up.php` | 勤怠データ編集 | `admin.operaciones.kintai.update` | `KintaiController@update` | E `kintai_tb_hiduke`, `kintai_tb_kintai`, `hatuban` | pendiente ⏸ |
| `kintai_update_del.php` | (marca: borrar) | `admin.operaciones.kintai.records.destroy` | `KintaiRecordController@destroy` | E `kintai_tb_kintai` | pendiente ⏸ |
| `kintai_delete.php` | (día: borrar) | `admin.operaciones.kintai.destroy` | `KintaiController@destroy` | E `kintai_tb_hiduke`, `kintai_tb_kintai` | pendiente ⏸ |
| `password_update.php`, `password_update_complete.php` | 端末パスワード変更 | `admin.operaciones.kintai.password.edit` / `.update` | `KintaiConfigController@editPassword` / `@updatePassword` | L/E `kintai_tb_config.password` | pendiente ⏸ (texto plano: lo lee el terminal, §7) |
| `config.php` | 各種設定データ | `admin.operaciones.kintai.config.edit` / `.update` | `KintaiConfigController@edit` / `@update` | L/E `kintai_tb_config`; L `tb_staff`, `tb_staff_shift_kind` | pendiente ⏸ |
| `staff_shift_kind.php`, `_frm.php`, `_register.php` | シフト区分管理 | `admin.operaciones.shift-kinds.*` (index, store, update, destroy, `sort`) | `StaffShiftKindController` | L/E `tb_staff_shift_kind`, `hatuban` | pendiente (lo usan los turnos: no espera a Antony) |
| `csv_out.php`, `csv_download/csv_out.php` | (CSV diario) | `admin.operaciones.kintai.csv.daily` | `KintaiCsvController@daily` | L `kintai_tb_config`, `kintai_tb_kintai`, `tb_staff` | pendiente ⏸ |
| `monthly_csv_out.php`, `csv_download/monthly_csv_out.php` | (CSV mensual por persona) | `admin.operaciones.kintai.csv.monthly` | `KintaiCsvController@monthly` | L las mismas | pendiente ⏸ |
| `csv_download/csv_file_out.php`, `csv_download/csv_file_del.php` | (archivo temporal Flash) | — | — | — | descartado: `StreamedResponse` sin archivo temporal |
| `ajax_session_update.php` | (tienda seleccionada) | `admin.operaciones.kintai.shop` (POST JSON) | `KintaiController@selectShop` | — (sesión) | pendiente ⏸ (misma sesión que el legacy) |

### 1.3 勤怠一覧 / 集計 (`system/category5/working/`)

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas legacy | Estado |
|---|---|---|---|---|---|
| `working_calendar.php` | 勤怠一覧 (calendario) | `admin.operaciones.working.calendar` | `WorkingController@calendar` | L `tb_shop`, L/E `tb_shop_work`, `hatuban` | pendiente ⏸ (Carbon, sin 1970) |
| `work_list.php` | 勤怠一覧 (día) | `admin.operaciones.working.day` | `WorkingController@day` | L `tb_shop`, `tb_staff`, `tb_work_time` | pendiente ⏸ |
| `work_list2.php` | 勤怠一覧 (variante) | `admin.operaciones.working.day2` | `WorkingController@day2` | L las mismas | pendiente ⏸ (comparte servicio con `day`) |
| `ng_work_list.php` | 勤怠一覧 (NG: sin salida) | `admin.operaciones.working.ng` | `WorkingController@ng` | L las mismas | pendiente ⏸ |
| `work_list_update.php` | 勤怠情報 | `admin.operaciones.working.update` | `WorkingController@update` | L/E `tb_work_time` | pendiente ⏸ (columna del día por lista blanca 1..31) |
| `change_syukkin.php` | (ajax 出勤区分) | `admin.operaciones.working.change` (POST) | `WorkingController@change` | L/E `tb_work_time` | pendiente ⏸ (misma respuesta que el legacy) |
| `change_syukkin2.php` | (ajax, sin enlaces) | — | — | — | descartado: sin enlaces y con inyección SQL; su regla «hora < 5 → día anterior» va a `WorkTimeService` |
| `working_list.php` | スタッフ一覧 (mensual) | `admin.operaciones.working.monthly` | `WorkingController@monthly` | L `tb_shop`, `tb_staff`, `tb_work_time` | pendiente ⏸ |
| `working_total_list.php` | スタッフ勤怠集計一覧 | `admin.operaciones.working.totals` | `WorkingController@totals` | L `tb_shop`, `tb_shop_work`, `tb_staff`, `tb_work_time` | pendiente ⏸ |
| `working_chouhyou.php` | 日別シフト管理 (帳票管理) | `admin.operaciones.working.report` | `WorkingController@report` | L `tb_shop`, `tb_staff`, `tb_work_time` | pendiente ⏸ (Gate `viewWorkReport` en lugar de `SERVER_ADDR`) |
| `csv_download.php`, `csv_download/csv_file_out.php` | (CSV 勤怠 mensual) | `admin.operaciones.working.csv` | `WorkingController@csv` | L `tb_shop`, `tb_staff`, `tb_work_time` | pendiente ⏸ |
| `csv_download/csv_file_del.php` | — | — | — | — | descartado: sin archivo temporal |

### 1.4 スケジュール管理 (`system/category5/schedule/`)

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas legacy | Estado |
|---|---|---|---|---|---|
| `staff_shift.php` | 日別シフト管理 | `admin.operaciones.shifts.day` + `.save` (POST) | `StaffShiftController@day` / `@save` | L `kintai_tb_config`, `tb_shop`, `tb_staff`, `tb_staff_shift_kind`; L/E `tb_staff_shift`, `hatuban` | pendiente (sin el enlace roto a `time_table.php`; comentario con comillas bien guardado; solo crea la fila del mes del empleado editado) |
| `staff_shift_month.php` | 月別シフト管理 | `admin.operaciones.shifts.month` | `StaffShiftController@month` | L las mismas | pendiente |
| `ajax_staff_shift_hatuban.php` | — | — | — | — | descartado: el `cd` se numera al guardar (`hatuban` en el servicio de S1) |
| `schedule.php` | 営業日設定 (sin enlace) | — | — | — | descartado: duplicado de `config/shop_work_calendar.php` |
| `shift.php` | 営業日設定 (sin enlace) | — | — | — | descartado: sin lógica, sin enlace |
| `staff_shift.function.php` | (fragmento) | — | — | — | descartado: lo cubre `LegacyMonth` (Carbon) |

### 1.5 初期設定管理 (`system/category5/config/`) y menú

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas legacy | Estado |
|---|---|---|---|---|---|
| `felica_mode.php` | カードリーダーモード設定 | `admin.operaciones.felica-mode.edit` / `.update` | `FelicaModeController@edit` / `@update` | L/E `tb_felica_mode` (fila `cd=1`) | pendiente ⏸ |
| `card_new.php` | カード管理 (新規カード管理) | `admin.operaciones.cards.index` / `.assign` | `StaffCardController@index` / `@assign` | L/E `tb_card`, `tb_felica_mode`, `tb_staff`, `tb_work_time` | pendiente ⏸ |
| `card_staff_select.php` | (fragmento) | parcial Blade | — | L `tb_staff` | pendiente ⏸ |
| `shop_work_calendar.php` | 営業日設定 | `admin.operaciones.shop-work.edit` / `.update` | `ShopWorkController@edit` / `@update` | L/E `tb_shop_work`, `hatuban` | pendiente (no depende del terminal; botón 更新 dentro del form; también lo enlaza el menú de S2) |
| `index.php` (category5) | 業務支援管理 (menú) | `config/menu/operaciones.php` | — | L `tb_base_system_child` | pendiente |
| `inc/page_control.php` | (paginador) | paginador de Laravel con el HTML del legacy | — | — | pendiente |

### 1.6 API JWT de la tienda móvil (`api/`)

Todas con HTTP 200 también en error y leyendo el **cuerpo JSON incluso en GET**. Detalle en §6.

| Archivo legacy | URL | Ruta Laravel | Controlador@método | Tablas legacy | Estado |
|---|---|---|---|---|---|
| `api/.htaccess`, `api/index.php` | `/api/<ruta>` | rutas explícitas + fallback del prefijo | `MobileShop\FallbackController` | — | hecho (contrato byte a byte con las fixtures, `MobileShopContractTest`) |
| `api/auth.php` | `POST /api/auth` | `publico.integraciones.api.auth` | `MobileShop\AuthController@issue` | — | hecho (contrato byte a byte con las fixtures, `MobileShopContractTest`) |
| `api/customer.php` | `GET /api/customer` | `publico.integraciones.api.customer` | `MobileShop\CustomerController@search` | L `tb_customer` | hecho (contrato byte a byte con las fixtures, `MobileShopContractTest`) |
| `api/shop.php` | `GET /api/shop` | `publico.integraciones.api.shop` | `MobileShop\ShopController@index` | L `tb_shop` | hecho (contrato byte a byte con las fixtures, `MobileShopContractTest`) |
| `api/point.php` | `GET /api/point` | `publico.integraciones.api.point` | `MobileShop\PointController@log` | L `tb_point_log`, `tb_customer` | hecho (contrato byte a byte con las fixtures, `MobileShopContractTest`) |
| `api/mobile_shop/customer.php` | `GET /api/mobile_shop/customer[/<cd>]` | `publico.integraciones.api.ms.customer` | `MobileShop\CustomerController@show` / `@searchDomains` | L `tb_customer` (N BD) | hecho (contrato byte a byte con las fixtures, `MobileShopContractTest`) |
| `api/mobile_shop/fetch_paid_member.php` | `POST /api/mobile_shop/fetch_paid_member` | `publico.integraciones.api.ms.fetch-paid-member` | `MobileShop\PaidMemberController@fetch` | salida HTTP (extlink) | hecho (contrato byte a byte con las fixtures, `MobileShopContractTest`) |
| `api/mobile_shop/send_unpaid_member_status.php` | `POST /api/mobile_shop/send_unpaid_member_status` | `publico.integraciones.api.ms.send-unpaid` | `MobileShop\PaidMemberController@sendUnpaid` | salida HTTP (extlink) | hecho (contrato byte a byte con las fixtures, `MobileShopContractTest`) |
| `api/mobile_shop/passcode.php` | `POST /api/mobile_shop/passcode` | `publico.integraciones.api.ms.passcode` | `MobileShop\PasscodeController@check` | L `contents_master` (`mobileshop`) | hecho (contrato byte a byte con las fixtures, `MobileShopContractTest`) |
| `api/mobile_shop/point.php` | `GET/POST /api/mobile_shop/point` | `publico.integraciones.api.ms.point` | `MobileShop\PointController@balance` | L `tb_customer`, `tb_customer_point` | hecho (contrato byte a byte con las fixtures, `MobileShopContractTest`) |
| `api/mobile_shop/point_use.php` | `POST /api/mobile_shop/point_use` | `publico.integraciones.api.ms.point-use` | `MobileShop\PointController@use` | E `tb_customer_point`, `tb_point_log`, `hatuban` | hecho (con `PointService::setPoint` de S3; contrato byte a byte, `MobileShopContractTest`) |
| `api/mobile_shop/point_exchange_item.php` | `POST /api/mobile_shop/point_exchange_item` | `publico.integraciones.api.ms.point-exchange` | `MobileShop\PointController@exchangeItem` | L `sales_items`; E `tb_customer_point`, `tb_point_log` | hecho (con `PointService::exchangeSalesItem` de S3; contrato byte a byte, `MobileShopContractTest`) |
| `api/mobile_shop/sales_items.php` | `GET /api/mobile_shop/sales_items[/<cd>]` | `publico.integraciones.api.ms.sales-items` | `MobileShop\SalesItemController@index` / `@show` | L `sales_items` | hecho (contrato byte a byte con las fixtures, `MobileShopContractTest`) |

### 1.7 API de la app マイページ (`api/mpa_*.php`)

| Archivo legacy | URL | Ruta Laravel | Controlador@método | Tablas legacy | Estado |
|---|---|---|---|---|---|
| `mpa_corresponding.php` | `/api/mpa_corresponding.php` | `publico.integraciones.mpa.corresponding` | `MpaController@corresponding` | L `admin_master`, `tb_customer`, `contents_master` (`mypage_config`) + archivos `hp/uploads/c6/` | pendiente |
| `mpa_getTopBanner.php` | `/api/mpa_getTopBanner.php` | `publico.integraciones.mpa.top-banner` | `MpaController@topBanner` | L `tb_customer`, `contents_master` + `hp/uploads/c6/aplibanner/` | pendiente (enlace con `md5(login_pass)` igual que hoy; anotado para Japón) |
| `mpa_getCoverFlow.php` | `/api/mpa_getCoverFlow.php` | `publico.integraciones.mpa.cover-flow` | `MpaController@coverFlow` | L `tb_customer`, `contents_master` (`aplicover`) + `hp/uploads/c6/aplicover/` | pendiente |
| `mpa_albumUpdate.php` | `POST /api/mpa_albumUpdate.php` | `publico.integraciones.mpa.album-update` | `MpaController@albumUpdate` | E `tb_photo_album`, `hatuban` + `hp/uploads/c1/photoalbum/<cd>/` | pendiente (Intervention Image o Imagick en lugar de `system('convert')`) |
| `mpa_setraiten.php` | `/api/mpa_setraiten.php` | `publico.integraciones.mpa.set-raiten` | `MpaController@setRaiten` | E `tb_raiten_rireki` y lo que haga `raitenFunc` (sellos, puntos) o `tb_beacon_history` | pendiente |
| `mpa_customerSync.php` | `/api/mpa_customerSync.php` | — | — | — | diferido: POS fuera de alcance (`PosGateway::syncCustomers()`); al retomarlo, comando Artisan, no ruta pública |
| `mpa_registCustomer.php` | — | — | — | — | descartado: archivo vacío |

### 1.8 `appview/` (WebView de la app del cliente)

Rutas `appview/<archivo>.php` idénticas, con sesión (como el legacy). Controladores en `Publico\Integraciones\Appview\`.

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas legacy | Estado |
|---|---|---|---|---|---|
| `coupon.php` | クーポン | `publico.integraciones.appview.coupon` | `CouponController@index` | L `tb_customer`, `tb_mycoupon`, `tb_mycoupon_master`, `tb_shop` | pendiente |
| `coupon_detail.php` | クーポン詳細 | `publico.integraciones.appview.coupon-detail` | `CouponController@show` | L `tb_mycoupon(_master)`, `tb_shop`, `tb_base_system_grandchild` | pendiente |
| `coupon_use.php` | クーポン利用 | `publico.integraciones.appview.coupon-use` | `CouponController@confirmUse` | L `tb_customer`, `tb_mycoupon_master`, `tb_mycoupon_used` | pendiente |
| `coupon_control.php` | (AJAX uso) | `publico.integraciones.appview.coupon-control` | `CouponController@use` | E `tb_mycoupon`, `tb_mycoupon_used` | pendiente |
| `used_coupon_list.php` | 利用済みクーポン | `publico.integraciones.appview.used-coupons` | `CouponController@used` | L `tb_mycoupon_used`… | pendiente |
| `stamp.php` | スタンプ | `publico.integraciones.appview.stamp` | `StampController@show` | L `tb_customer`, `tb_stamp_config`, `admin_master` | pendiente |
| `visit_history.php` | 来店履歴 | `publico.integraciones.appview.visit-history` | `MemberController@visitHistory` | L `admin_master` + HTTP `g-api.net` | pendiente (`cutomer_cd` sic) |
| `qr_code.php` | (QR) | `publico.integraciones.appview.qr-code` | `MemberController@qrCode` | L `tb_customer` | pendiente |
| `memberId.php` | (会員ID) | `publico.integraciones.appview.member-id` | `MemberController@memberId` | L `tb_customer` | pendiente |
| `photo_gallery.php` | フォトギャラリー | `publico.integraciones.appview.photo-gallery` | `MemberController@photoGallery` | L `admin_master`, `tb_photo_album` | pendiente |
| `member_login.php` | 会員ページ | `publico.integraciones.appview.member-login` | `MemberController@login` | L `tb_customer` | pendiente (compara `login_pass` como hoy) |
| `member_kiyaku.php` | 顧客登録ページ | `publico.integraciones.appview.member-kiyaku` | `MemberRegistrationController@terms` | L `contents_master` | pendiente |
| `member_regist.php` | 新規会員登録フォーム | `publico.integraciones.appview.member-regist` | `MemberRegistrationController@form` | L `admin_master`, `tb_shop`, `tb_koumoku` | pendiente |
| `member_comfirm.php` | 登録確認ページ | `publico.integraciones.appview.member-comfirm` | `MemberRegistrationController@confirm` | — | pendiente (`comfirm` sic en la URL) |
| `member_complete.php` | 登録完了ページ | `publico.integraciones.appview.member-complete` | `MemberRegistrationController@store` | E `tb_customer`, `tb_customer_sub`, `tb_customer_point`, `tb_point_log`, `hatuban` | pendiente |
| `member_init.php` | 会員ページ (inicio) | `publico.integraciones.appview.member-init` | `MemberController@init` | L `tb_customer` | pendiente |
| `member_comp.php` | 会員ページ (vinculación) | `publico.integraciones.appview.member-comp` | `MemberController@link` | E `tb_customer` | pendiente |
| `customerUpdate.php` | 個人情報の登録変更フォーム | `publico.integraciones.appview.customer-update` | `MemberController@updateProfile` | L/E `tb_customer`; L `tb_shop`, `admin_master` | pendiente |
| `anketo2.php` | 問診表 | `publico.integraciones.appview.anketo2` | `SurveyController@show` | L `tb_c3_anketo2*` | pendiente |
| `anketo2_register.php` | 問診表 (registro) | `publico.integraciones.appview.anketo2-register` | `SurveyController@store` | E `tb_c3_anketo2_sub`, `tb_anketo_present`, `tb_mycoupon`, `tb_customer`, `hatuban` | pendiente |
| `reservation.php` | (予約) | `publico.integraciones.appview.reservation` | `ReservationController@show` | — (contenedor de `r/`, de S2) | pendiente |
| `quick_reservation.php` | (クイック予約) | — | — | — | descartado: クイック予約 es de touchcation y `numbered_ticket` no está en el backup (S0, 2026-11-27) |
| `quick_reservation_register.php` | (registro, JSON) | — | — | — | descartado: ídem |
| `privacyPage.php` | プライバシーポリシー | `publico.integraciones.appview.privacy` | `PageController@privacy` | L `contents_master`, `tb_shop` | pendiente |
| `introduction.php` | プレビュー | `publico.integraciones.appview.introduction` | `PageController@introduction` | — | pendiente |
| `detail_view.php` | プレビュー | `publico.integraciones.appview.detail-view` | `PageController@detailView` | — | pendiente |
| `ms_paid_member_regist.php`, `ms_paid_member_test.php` | (prueba) | — | — | — | descartado: código de prueba con cliente fijo |
| `class/*` | — | servicios | — | — | descartado como archivos: su lógica pasa a servicios (``Util::` de オススメ) |

### 1.9 `system/tool/api` y CTI (`system/desktop_request`)

`tool/api`: clave `key` comparada con `CRM_API_KEY` (`hash_equals`), middleware `crm.api-key`; sin clave → respuesta vacía como el legacy.

| Archivo legacy | URL | Ruta Laravel | Controlador@método | Tablas legacy | Estado |
|---|---|---|---|---|---|
| `tool/api/checkKey.php` | (fragmento) | middleware `Integraciones\CheckCrmApiKey` | — | — | hecho |
| `tool/api/category0/setPosdomain.php` | `/system/tool/api/category0/setPosdomain.php` | — | — | — | diferido: POS fuera de alcance (escribe `tb_shop.pos_domain`; `PosGateway::setShopDomain()`) |
| `tool/api/category1/searchCustomerList.php` | `/system/tool/api/category1/searchCustomerList.php` | `publico.integraciones.tool.search-customer-list` | `ToolApi\CustomerController@search` | L `tb_customer(_sub)` | hecho (contrato byte a byte) |
| `tool/api/category1/getCustomerDetail.php` | `…/getCustomerDetail.php` | `publico.integraciones.tool.customer-detail` | `ToolApi\CustomerController@show` | L `tb_customer(_sub)` | hecho (contrato byte a byte) |
| `tool/api/category1/getSearchCateFormData.php` | `…/getSearchCateFormData.php` | `publico.integraciones.tool.search-form` | `ToolApi\CustomerController@searchForm` | L `tb_search_cate`, `tb_search_form` | hecho (contrato byte a byte) |
| `tool/api/category1/getRaitenList.php` | `…/getRaitenList.php` | `publico.integraciones.tool.raiten-list` | `ToolApi\RaitenController@index` | L `tb_raiten_rireki` | hecho (contrato byte a byte) |
| `tool/api/category1/getRaitenDetail.php` | `…/getRaitenDetail.php` | `publico.integraciones.tool.raiten-detail` | `ToolApi\RaitenController@show` | L `tb_raiten_rireki`, `tb_staff`, `tb_shop` | hecho (contrato byte a byte) |
| `tool/api/category1/addRaitenRecord.php` | `…/addRaitenRecord.php` | `publico.integraciones.tool.raiten-add` | `ToolApi\RaitenController@store` | E `tb_raiten_rireki`, `tb_stamp_log`, `hatuban` | hecho (contrato byte a byte) |
| `tool/api/category1/setRaitenDetail.php` | `…/setRaitenDetail.php` | `publico.integraciones.tool.raiten-set` | `ToolApi\RaitenController@update` | E `tb_raiten_rireki`, `tb_point_log`, `tb_customer_point`, `hatuban`; L `admin_master` | pendiente: espera el servicio de S2 de `kanren_record_register` (来店履歴の追加・修正) |
| `tool/api/category5/getStaffList.php` | `…/category5/getStaffList.php` | `publico.integraciones.tool.staff-list` | `ToolApi\StaffController@index` | L `tb_staff` | hecho (contrato byte a byte) |
| `desktop_request/ctiWindow.php` | `/system/desktop_request/ctiWindow.php?tel=` | `publico.integraciones.cti.window` | `CtiController@window` | L `tb_base_system` | hecho (302 a las mismas URL del legacy, contrato byte a byte) |
| `desktop_request/customerDetailHtml.php` | `/system/desktop_request/customerDetailHtml.php` | `publico.integraciones.cti.customer-html` | `CtiController@customerHtml` | L `tb_customer`, `tb_raiten_rireki`, `tb_shop`, `tb_staff` | hecho (contrato byte a byte) |
| `desktop_request/jGetCustomerData.php` | `/system/desktop_request/jGetCustomerData.php` | `publico.integraciones.cti.customer-json` | `CtiController@customerJson` | L `tb_customer` | hecho (contrato byte a byte) |

### 1.10 Utilitarios (`version/`, `air/`, `ptag/`, `exp_cmd/`, `cron/`, `instagram/`, `ipad/`)

| Archivo legacy | URL | Ruta Laravel | Controlador@método | Tablas legacy | Estado |
|---|---|---|---|---|---|
| `version/pos/v.php`, `version/android3/v.php`, `version/sharp/v.php` y los 6 `.apk` | `/version/<canal>/v.php` | — | — | — | descartado: touchcation fuera de alcance (las 6 APK son `jp.CrmShWeb`; S0, 2026-11-26) |
| `version/crm_version.php` | `/version/crm_version.php` | `publico.integraciones.version.crm` | `VersionController@crm` | — | pendiente (`text/plain` `2.704`, sin salto de línea) |
| `ptag/.htaccess`, `ptag/index.php` | `/ptag/<ID>` | `publico.integraciones.ptag` | `PtagController@redirect` | L `tb_url_list`, `tb_customer`; E `url_transfer_data` | pendiente |
| `air/update-info.xml`, `air/crossdomain.xml` | `/air/*.xml` | archivos estáticos en `public/air/` | — | — | pendiente (condicional: mientras exista el cliente AIR) |
| `air/air_info_out.php` | `/air/air_info_out.php` | `publico.integraciones.air.info` | `AirController@info` | L `tb_information`, `tb_staff` | pendiente (condicional, confirmar uso con Japón) |
| `exp_cmd/cron/get_shukei_data.php`, `get_shukei_data2.php` | `/exp_cmd/cron/get_shukei_data*.php` | comando `integraciones:shukei {year?} {month?}` | `App\Console\Commands\Integraciones\ShukeiCommand` | L `tb_hp_domain`, `tb_shop` y ventas | pendiente (prioridad baja; llamador desconocido) |
| `exp_cmd/verUp/*` (11) | CLI | — | — | — | descartado: actualizadores históricos de esquema |
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

## 2. Modelos sobre la BD del legacy

**Sin tablas nuevas.** Todo se resuelve con las tablas del legacy tal cual, sin migraciones propias.

Reglas comunes (CONVENCIONES §0 y §4), que aplican todos los modelos de S5:
- `protected $table = '<tb_*>'`, `protected $primaryKey = 'cd'` (salvo donde se indica), columnas con su nombre legacy.
- **Numeración:** el `cd` nuevo sale de `hatuban` (`koumoku` = nombre de la tabla) con el servicio común de S1, dentro de la misma transacción que el INSERT (`SELECT … FOR UPDATE`, como `cHatuban`). `$incrementing = false`.
- **Borrado lógico:** `disp_flg = '9'` con el scope global/trait común de S1 (no SoftDeletes). `hyouji_flg` `'1'` visible / `'9'` oculto tal cual.
- **Fechas:** `touroku_date`/`update_date` (o `update`) son epoch en texto: `const CREATED_AT = 'touroku_date'`, `const UPDATED_AT = 'update_date'` y `protected $dateFormat = 'U'`; donde solo existe `update`, `CREATED_AT = null`. Si la tabla no tiene ninguna, `$timestamps = false`.
- **Tipos:** los textos quedan como texto (`'1'`, `'9'`, epoch); constantes o enums PHP nombran los valores (`App\Enums\Operaciones\*`), sin cambiar lo guardado.
- **Columnas por día** (`tb_work_time`, `tb_staff_shift`, `tb_shop_work`): nunca se arma el nombre de columna con la entrada del usuario: el día se valida contra `1..días del mes` y se usa una lista blanca (`syukkin_time_{1..31}`…). Lo encapsula `LegacyMonth`/`WorkTimeService` (§3).

### 2.1 Modelos de S5 (`app/Models/`)

| Modelo | Tabla | Columnas (clave en negrita) | Relaciones | Notas |
|---|---|---|---|---|
| `StaffGroup` | `tb_staff_group` | **`cd`**, `name`, `rank` (orden), `disp_flg`, `touroku_date`, `update_date` | `hasMany(Staff, 'tb_staff_group_cd')` | orden ▲▼ intercambia `rank` |
| `OfficialPosition` | `tb_officialposition` (役職) | **`cd`**, `officialposition` | — | `$timestamps = false`; `tb_staff.officialposition` guarda el **texto**, no el `cd` |
| `BelongPost` | `tb_belongpost` (所属) | **`cd`**, `belongpost` | — | el alta de personal agrega el texto nuevo si no existe (como el legacy) |
| `EmploymentSystem` | `tb_employmentsystem` (雇用形態) | **`cd`**, `employmentsystem` | — | solo lectura (nadie escribe en el legacy) |
| `Information` | `tb_information` | **`cd`**, `shop_cd` (NULL/0 = 全店舗), `title`, `comment`, `belongpost`, `employmentsystem`, `officialposition` (varios, separados por espacio), `update`, `staff_name`, `disp_flg` | `belongsTo(Shop, 'shop_cd')` | `UPDATED_AT = 'update'`; accesor `officialPositions()` que parte por espacio |
| `StaffCard` | `tb_card` | **`cd`**, `card_no`, `felica_no`, `staff_cd` (texto, `''` = libre), `update`, `card_flg` (`'0'` 未割当, `'1'` 割当済) | `belongsTo(Staff, 'staff_cd')` | ⏸ |
| `FelicaMode` | `tb_felica_mode` | **`cd`**, `mode` (`'0'` 運用, `'1'` 出勤強制, `'2'` 退勤強制, `'9'` 登録), `update`, `nyushitsu_komento`, `taishitsu_komento`, `interval_time` | — | fila `cd=1` = modo del lector (S5, ⏸); fila `cd=2` = intervalo y textos de correo de entrada/salida (la edita S2 desde category1) |
| `ShopWork` | `tb_shop_work` | **`cd`**, `shop_cd`, `syukkinbi` (**`'YYYY/M'`**), `syukkin_time_1..31` (`'0'` cerrado / `'1'` abierto) | `belongsTo(Shop, 'shop_cd')` | lo lee también S2 (calendario de reservas) |
| `KintaiConfig` | `kintai_tb_config` | **`cd`**, `tb_shop_cd`, `kijun_time` / `start_time` / `end_time` (`'HH<>MM'`), `password`, `pic_name` | `belongsTo(Shop, 'tb_shop_cd')` | ⏸ (lo usan también los turnos: horario de la tabla diaria) |
| `KintaiHiduke` | `kintai_tb_hiduke` | **`cd`**, `hiduke` (epoch de la entrada), `kintai_flg` (texto: 出勤, 欠勤, 有給休暇…), `felica_no`, `tb_staff_cd`, `disp_flg`, `hyouji_flg`, `bikou` | `hasMany(KintaiRecord, 'kintai_tb_hiduke_cd')`, `belongsTo(Staff, 'tb_staff_cd')` | ⏸ |
| `KintaiRecord` | `kintai_tb_kintai` | **`cd`**, `kintai_flg` (`'1'` 出勤, `'2'` 退勤, `'3'` 外出, `'4'` 戻り), `kintai_time` (epoch entero), `felica_no`, `tb_staff_cd`, `disp_flg`, `hyouji_flg`, `kintai_tb_hiduke_cd` | `belongsTo(KintaiHiduke)` | ⏸ |
| `WorkTime` | `tb_work_time` | **`cd`**, `shop_cd`, `staff_cd`, `felica_no`, `syukkinbi` (epoch del día 1, 0:00 JST) y por N = 1..31: `syukkin_time_N`, `taikin_time_N`, `gaisyutu_time_N` (lista `<>`), `kisha_time_N` (lista `<>`), `syukkin_flg_N` (出勤/遅刻), `syukkin_kubun_N` (退社/早退/''), `komment_N`, `etc1_N` | `belongsTo(Staff, 'staff_cd')`, `belongsTo(Shop, 'shop_cd')` | ⏸; `$timestamps = false`; `day(int $n)` devuelve un objeto con las 8 columnas del día |
| `StaffShift` | `tb_staff_shift` | misma forma que `tb_work_time`: **`cd`**, `shop_cd`, `staff_cd`, `syukkinbi`; por día `gaisyutu_time_N` (inicios), `kisha_time_N` (fines), `syukkin_kubun_N` (cd de 区分), `komment_N` como listas paralelas `<>` | `belongsTo(Staff, 'staff_cd')`, `belongsTo(Shop, 'shop_cd')` | `shifts(int $n)` devuelve la lista de turnos del día |
| `StaffShiftKind` | `tb_staff_shift_kind` | **`cd`**, `name`, `rank`, `disp_flg`, `touroku_date`, `update_date`, `color` | — | |
| `UrlTransferData` | `url_transfer_data` | `access` (timestamp), `tb_customer_cd` (NULL = visitante), `data_id` (= `tb_url_list.id`) | `belongsTo(Customer, 'tb_customer_cd')` | **sin PK** en el legacy: `$primaryKey = null`, `$incrementing = false`, `$timestamps = false`; solo inserta (`ptag`) y lo lee S4 para su 集計 |

### 2.2 Modelos de otros que S5 usa (lee siempre; escribe solo con el servicio del dueño)

| Tabla | Modelo (dueño, CONVENCIONES §3) | Uso de S5 |
|---|---|---|
| `tb_staff` | `Staff` (S1) | Pantallas de gestión: S5 escribe la ficha (alta, edición, baja `disp_flg='9'`, foto, `felica_no`) con el modelo de S1; el login y `rank_pass` (permisos) los gestiona S1 |
| `tb_shop` | `Shop` (S1) | Selectores de tienda, `/api/shop` |
| `hatuban` | `Hatuban` (S1) | Numeración de todos los `cd` |
| `admin_master`, `contents_master` | `AdminMaster`, `ContentsMaster` (S1) | `application_use_flg`, passcode de la tienda móvil (`category='mobileshop'`), `mypage_config`, `aplicover`, privacidad |
| `tb_base_system*` | (S1) | Menú y `ctiWindow` (módulos 9/11 activos) |
| `tb_koumoku` | (S2, campos configurables) | S5 lee y escribe las filas `tb_name = 'tb_staff'` (スタッフ検索項目設定) |
| `zip` | (S1) | Código postal → dirección |
| `tb_customer`, `tb_customer_sub` | `Customer`, `CustomerSub` (S2) | API, `tool/api`, CTI, `appview`, `mpa_*` |
| `tb_raiten_rireki`, `tb_search_cate`, `tb_search_form` | (S2) | `tool/api`, CTI, `mpa_setraiten` |
| `sales_items` | (S2, ya Eloquent en el legacy) | API de canje |
| `tb_photo_album` | (S2) | `mpa_albumUpdate`, `appview/photo_gallery` |
| `tb_customer_point`, `tb_point_log`, `tb_stamp_log`, `tb_stamp_config` | (S3) | API de puntos, `tool/api`, `appview/stamp` |
| `tb_mycoupon`, `tb_mycoupon_master`, `tb_mycoupon_used`, `tb_c3_anketo2*`, `tb_anketo_present` | (S3) | `appview` |
| `tb_url_list`, `tb_beacon_history` | (S4) | `ptag`, `mpa_setraiten` (`membership=1`) |

### 2.3 Archivos (uploads)

Las rutas guardadas en la BD (`tb_staff.pic_name`, `kintai_tb_config.pic_name`, `tb_photo_album` + carpeta `<cd>`, imágenes de mypage) apuntan a `hp/uploads/c5/`, `c1/photoalbum/`, `c6/…`. Se usa un disco de Storage cuya raíz es esa misma carpeta de uploads (lo define S1 junto con la redirección `/hp/uploads/*`), de modo que el legacy y Laravel lean y escriban los mismos archivos durante la convivencia.

---

## 2b. UI (HTML y CSS del legacy)

Cada pantalla de `admin.operaciones.*` es el HTML del archivo legacy pasado a Blade, con las mismas clases, ids y textos japoneses, dentro del layout de S1 (cabecera, menú lateral, `mainStyle.css`, jQuery 3.7.1 + compat). Cambios obligados: los botones Flash de CSV (`AC_RunActiveContent.js` + `.swf`) pasan a un botón HTML con la misma imagen que llama a la ruta CSV; los formularios llevan `@csrf`; los `<script>` de jQuery 1.3.2/1.4.4/1.11 de cada pantalla se quitan (usa el del layout).

**Estáticos de category5.** Varios JS/CSS no están en `system/js` ni `system/css` (lo que copia S1) sino dentro de `system/category5/**`. S5 los copia a `public/legacy/system/category5/` conservando la ruta relativa (**hecho**, confirmado por S0; sin `AC_RunActiveContent.js` ni lo de `csv_download/`, que es Flash):

| Submódulo | CSS | JS (además del layout) | Notas |
|---|---|---|---|
| スタッフ管理 (`staff_entry*`, `staff_recover_confirm`, `work_list_update`) | — | `system/js/jquery.maskedinput-1.2.2.js`, `system/js/ajaxupload-3.5.js`, `category5/staff/js/staff_entry.js` | subida de foto por AJAX |
| `staff_list`, `staff_detail`, `staff_group`, `form_config`, `information` | layout | layout | CSV con botón HTML |
| 勤怠 `kintai/index`, `kintai_new*`, `kintai_update*` ⏸ | `category5/kintai/css/{default,layout,main,import}.css`, `jquery.alerts.css` | `jquery.alerts.js` (`jConfirm`/`jAlert`), `system/js/default.js`, `system/js/scrollsmoothly.js`; `jquery-ui` del layout | el legacy pide `../css/jquery.alerts.css` y `../js/jquery.alerts.js` en `system/category5/`, que **no existen** (404): el Blade usa `system/css/jquery.alerts.css` y `system/js/jquery.alerts.js` (estáticos comunes de S1) |
| `kintai/config` ⏸ | `category5/kintai/css/colorpicker.css` | `category5/kintai/js/colorpicker.js`, `jquery.alerts.js` | imágenes en `category5/kintai/images/` (26) |
| スケジュール `staff_shift`, `staff_shift_month` | `category5/schedule/css/{time_table,add.style,bootstrap.min,bootstrap-modal,bootstrap-modal-bs3patch}.css` | `category5/schedule/js/{add.shift,bootstrap.min,bootstrap-modal,bootstrap-modalmanager,html2canvas}.js` | Bootstrap 3 solo en estas pantallas, como el legacy |
| `working/working_chouhyou` ⏸ | lo mismo que スケジュール + `datepicker.css` | lo mismo + `bootstrap-datepicker.js`, `bootstrap-datepicker.ja.min.js` | |
| `working/*` resto, `config/*` | layout | layout (`working/js/` 1 archivo) | |

**`appview/`** (fuera del layout del admin, páginas de WebView) referencia sus estáticos con rutas absolutas `/appview/css|js|img|images/…` y `/appview/bower_components/…` (Bootstrap, Bootswatch, bootstrap-validator, jQuery), más `/admin/css/font-awesome(.min).css`. S5 copió `appview/{css,js,img,images}` y solo los archivos `dist` usados de `bower_components` (más las fuentes de Bootswatch) a `public/appview/` (mismas URL), y `admin/css/font-awesome(.min).css` con `admin/fonts/` a `public/legacy/admin/` (**hecho**; no en `public/admin/`, que taparía el prefijo `/admin` de Laravel). Los Blade de `appview` cambian `/admin/css/font-awesome.min.css` por `/legacy/admin/css/font-awesome.min.css`. `appview/css/font-awesome.css` pide `../fonts/`, que tampoco existe en el legacy. JS propios: `jquery-2.0.3.min.js` (se mantiene en estas páginas: el WebView no carga el layout), `lightbox.js`, `jquery.qrcode.min.js`, `jquery.jpostal.js`, `common.js`, `base.js`.

---

## 3. Servicios viejos → Laravel 13

| Legacy | Dónde | Reemplazo |
|---|---|---|
| `hatuban` + `SELECT … FOR UPDATE` (`cHatuban`) | todos los altas | servicio común de S1 (`Hatuban`), en la misma transacción que el INSERT |
| `array_walk_recursive($_REQUEST,'form2Db')` | todo category5 | Form Requests + bindings (sin doble escape); la normalización de kana de ancho medio → completo de `form2Db` se mantiene en un cast/mutator común (pedido 5.1) |
| SQL concatenado (`api/customer.php`, `CustomerForMobileShop`, `change_syukkin2`) | api, working | Query Builder con lista blanca de columnas filtrables / de día |
| `mktime(..., is_dst)` (calendarios a 1970) | kintai, working, config, schedule | `LegacyMonth` (CarbonImmutable, `Asia/Tokyo`): `syukkinbi` epoch ↔ mes, `'YYYY/M'` ↔ mes, `addMonthNoOverflow()` |
| Corte de jornada: «hora < 5 → día anterior» (`change_syukkin`), `kijun_time` (terminal) | working, kintai | `WorkTimeService::resolveWorkDate()` con pruebas unitarias; mismos valores que el legacy en cada pantalla |
| Cálculo de horas en PHP/JS (`index.php`, `kintai_update.php`, `working_total_list`) | kintai, working | `KintaiCalculator` / `WorkTimeService` con pruebas Pest; mismo resultado que el legacy |
| CSV con Flash + archivo temporal + `csv_file_del` | staff, kintai, working | `StreamedResponse` SJIS-win (helper de S1; hasta entonces uno propio con la misma firma) |
| Fotos en `hp/uploads/c5/`, `c1/photoalbum` | staff, kintai config, mpa_albumUpdate | Storage sobre la misma carpeta (§2.3); `system('convert')` → Intervention Image o Imagick |
| Estado de búsqueda en `$_SESSION` | staff_list, working_list | se mantiene en sesión (misma conducta que el legacy: volver a la lista conserva el filtro) |
| `login_pass` del personal en texto plano | staff | el formulario lo guarda donde y como lo hace hoy el legacy; el cambio a hash es decisión de S1/Japón (login compartido con el legacy durante la convivencia) |
| `kintai_tb_config.password` en texto plano | kintai | igual que hoy (lo lee el terminal físico) ⏸ |
| `db_sync_all2` / `mpa_customerSync` / `setPosdomain` (BD POS por tienda) | staff, api | `App\Services\Operaciones\PosGateway` (`syncStaff`, `removeStaff`) y `App\Services\Integraciones\PosGateway` (`syncCustomers`, `setShopDomain`) con implementación nula en `OperacionesServiceProvider` / `IntegracionesServiceProvider` (ya escritas en el stash, se recuperan) |
| Control por IP / `SERVER_ADDR = 202.218.51.245` | working_chouhyou, menú de schedule | Gate `viewWorkReport` según los niveles de S1 |
| `cKengen` / `ACCESS_LEVEL_*` | todo category5 | Policies sobre los niveles de S1 |
| `Firebase\JWT` RS256 con `keys/*.pem` | api | mismo paquete `firebase/php-jwt` (pedido 5.1), mismas claves fuera de git; middleware `MobileShopJwt` con el JSON de error y HTTP 200 |
| `CRM_API_KEY` en `checkKey.php` | tool/api | middleware `CheckCrmApiKey` (`hash_equals`) |
| HTTP a extlink (`apiSnderAuth`, `apiJudServiceTgt`, `apiSnderCan`) | mobile_shop; 有料会員 de S2 | `MobileShopClient` (`Http::`, `Http::fake()` en pruebas); S2 lo usa desde su Job `SyncPaidMemberStatus` |
| `file_get_contents` a `g-api.net/api/mpa/getComingHistory` | appview/visit_history | `Http::` con `Http::fake()` en pruebas |
| `Point::setPoint()` con `FOR UPDATE` | api point_use, exchange, appview, tool/api | servicio de puntos de S3 sobre `tb_customer_point`/`tb_point_log` (mismos campos: `tb_terminal_cd = -3`, `tb_staff_cd = -1`/`0`, `action_flg`, `shori_mae_point`) |
| `cUtility::raitenFunc` (visita + sellos + puntos) | mpa_setraiten, tool/api | servicio de visitas de S2 |
| `json_encode` sin flags / filas de `pg_fetch_assoc` como texto | api, mpa | `App\Services\Integraciones\LegacyJson` (**hecho**, con pruebas) |
| `crontab` escrito por PHP (`regenerateCronJob`) | cron | Scheduler; S5 solo registra `integraciones:shukei` (mensual) si se confirma |

---

## 4. Orden de implementación

1 punto ≈ media jornada. Copiar el HTML del legacy simplifica la UI y usar las mismas tablas quita el mapeo de datos, así que las cifras bajan respecto de la versión anterior (75 → 63).

| # | Submódulo | Depende de | Puntos |
|---|---|---|---|
| 1 | Base: modelos de §2.1, enums de valores legacy, `LegacyMonth`, `PosGateway` nulos (del stash), `config/menu/operaciones.php`, estáticos de category5 y `appview` | S1: layout, `Staff`, `Shop`, `Hatuban`, trait de `disp_flg` | 2 |
| 2 | スタッフ管理: ficha (alta-confirmación-registro-completo, detalle, baja, reactivar), búsqueda + CSV, foto, grupos, 検索項目設定, インフォメーション | 1; S1 permisos | 10 |
| 3 | 営業日設定 (`tb_shop_work`) | 1 | 2 |
| 4 | スケジュール管理: シフト区分 + turnos diario y mensual | 1 (`kintai_tb_config` solo lectura para el horario) | 4 |
| 5 ⏸ | カードリーダーモード + カード管理 | 2; respuesta de Antony | 2 |
| 6 ⏸ | 勤怠管理 (kintai): lista del día, alta/edición/borrado, contraseña y 各種設定 del terminal, CSV | 2; respuesta de Antony | 12 |
| 7 ⏸ | 勤怠一覧 / 集計 (working): calendario, lista del día (+ variantes y NG), corrección, mensual, totales, 帳票, CSV | 3, 6; respuesta de Antony | 8 |
| 8 | API JWT tienda móvil (13 endpoints + 404) con pruebas de contrato | S1 paquete JWT y config; S2 `tb_customer`, `sales_items`; S3 puntos | 6 |
| 9 | `tool/api` (8 endpoints) y CTI (3) | S2 clientes y visitas; S3 puntos y sellos | 5 |
| 10 | `mpa_*` (5 endpoints) | S1 `contents_master` y `/hp/uploads`; S2 visitas y fotos; S4 beacon | 3 |
| 11 | `appview/*` (24 pantallas) | S2 clientes; S3 cupones, sellos, encuestas, puntos | 7 |
| 12 | Utilitarios: `crm_version`, `ptag`, `air` (condicional), `integraciones:shukei` | S4 `tb_url_list` | 2 |
| | **Total** (⏸ = 22 de 63) | | **63** |

Sin respuesta de Antony se hace 1-4 y 8-12 (41 puntos). Si 勤怠 no se usa, las filas ⏸ pasan a `descartado: sin terminal (touchcation)`.

---

## 5. Pedidos

### 5.1 A S1 (núcleo)

- **Modelos y servicios base:** `Staff` (`tb_staff`), `Shop`, `Hatuban` (numeración en transacción), `AdminMaster`, `ContentsMaster`, el trait/scope de `disp_flg = '9'` y el cast/mutator que reproduce `form2Db` (kana de ancho medio → completo, recorte).
- **Rutas:** `routes/modulos/operaciones.php` dentro del grupo `admin` y `routes/publico/integraciones.php` fuera, sin CSRF y sin prefijo, con sesión para `appview/*`; que el prefijo `api` de `routes/api.php` no capture `/api/*` legacy.
- **Providers y seeders:** registrar `OperacionesServiceProvider` e `IntegracionesServiceProvider` en `bootstrap/providers.php`.
- **Estáticos:** hechos por S5 (category5, `appview` y `public/legacy/admin/{css,fonts}` con el font-awesome que usa `appview`); S1 copia los comunes (`system/css`, `system/js`…), de los que salen `jquery.alerts.*`.
- **Uploads:** disco de Storage sobre la carpeta `hp/uploads` del legacy y la redirección `/hp/uploads/*`.
- **Configuración y paquetes:** `firebase/php-jwt`, `intervention/image` (o Imagick); `MOBILESHOP_API_USER`, `MOBILESHOP_API_PASS`, `MOBILESHOP_EXTLINK_PASS` y su URL, `keys/private.pem`/`public.pem`, `CRM_API_KEY`, `DOMAIN_LIST` con una conexión de BD por dominio para `mobile_shop/customer`, host de `g-api.net`.
- **Permisos:** niveles equivalentes a `cKengen` para las Policies de S5 y el Gate `viewWorkReport`.
- **Comunes:** endpoint de código postal sobre la tabla `zip`; helper CSV SJIS-win.

### 5.2 A S2 (clientes, ventas, reservas)

- Modelos sobre `tb_customer`, `tb_customer_sub`, `tb_raiten_rireki`, `tb_koumoku`, `tb_search_cate/form`, `sales_items`, `tb_photo_album`.
- Servicio de visitas (lo que hace `raitenFunc`: intervalo mínimo, sellos y puntos) para `mpa_setraiten`, `tool/api` y CTI; búsqueda de clientes por teléfono (CTI); alta de socio desde `appview/member_*`.
- Las rutas nuevas de alta y búsqueda de clientes y de la agenda de reservas, para las redirecciones de `ctiWindow.php`.
- `tb_felica_mode` fila `cd=2` (intervalo y textos de entrada/salida de category1) la edita S2 con el modelo `FelicaMode` de S5.
- S5 publica `MobileShopClient` (有料会員) para el Job de S2, y `ShopWork` (`tb_shop_work`) para su calendario.

### 5.3 A S3 (promoción)

- Servicio de puntos sobre `tb_customer_point`/`tb_point_log` con los mismos campos que `Point::setPoint()` (`tb_terminal_cd = -3`, `tb_staff_cd = -1`/`0`, `action_flg`, `shori_mae_point`, nota `api:point_use` / `exchangeSalesItem:<cd>:<nombre>`).
- Modelos de cupones (`tb_mycoupon*`), sellos (`tb_stamp_config`, `tb_stamp_log`) y encuestas (`tb_c3_anketo2*`, `tb_anketo_present`) para `appview`.

### 5.4 A S4 (contenido)

- Modelos `tb_url_list` (`ptag` busca por `id` con `disp_flg='1'` y `hyouji_flg='1'`) y `tb_beacon_history`. `url_transfer_data` es de S5 (lo escribe `ptag`); S4 lo lee para su 集計.
- Imágenes y `mypage_config` de mypage (`contents_master` `tb_c6_user_hp`): `mpa_*` y la lista de オススメ de `appview` los leen en solo lectura.

### 5.5 A S0 / Antony

- **⏸ 勤怠:** ¿se usa el 勤怠 (terminal `kintai_crm_n` y タイムカード FeliCa) sin touchcation? Bloquea los pasos 5-7 (22 puntos).

---

## 6. Contratos externos (para las pruebas Pest de contrato)

Regla: misma URL, mismos parámetros, misma respuesta byte a byte (claves, orden, tipos, erratas, HTTP 200 en error). Cada endpoint tiene una prueba en `tests/Feature/Integraciones/Contract/` que compara con una respuesta grabada del legacy (`tests/Fixtures/integraciones/<endpoint>.*`), obtenida del Docker de crm2-php8 con datos de prueba (nunca contra producción ni con `activation=1`).

**Con la BD del legacy los `cd` y las columnas son los mismos:** la API lee las mismas tablas y columnas que el legacy y no hay mapeo de claves ni de nombres (`email1`, `kojin_mobile`, `add`, `touroku_date`… salen tal cual). `/api/point` devuelve las filas de `tb_point_log` con sus columnas en el orden de la tabla. Lo único que hay que reproducir es la serialización: el legacy hace `json_encode` sin flags de filas de `pg_fetch_assoc` (todo texto), mientras que PDO entrega enteros como `int`. Lo cubre `LegacyJson::pgText()` + `LegacyJson::encode()` (japonés como secuencias de escape unicode, `\/` en URLs, `1.0` como `1`, cuerpo vacío si falla la codificación). Algunas ramas de error hacen `echo` antes de `header()`: Content-Type por defecto de PHP (`LegacyJson::response(..., jsonHeader: false)`).

**Fixtures grabadas (2026-11-27):** `public_html/tests/Fixtures/integraciones/` — 53 casos de `/api/*` y `mpa_*` con su README (cómo se grabaron y lo que mostraron). `LegacyJson::encode()` reproduce byte a byte todos los cuerpos JSON. Tipos: `/api/shop`, `/api/customer` y `mobile_shop/point` salen de `pg_fetch_*` (texto, `pgText`); `/api/point`, `mobile_shop/customer/<id>` y `sales_items` salen de Eloquent/PDO (números).

### 6.1 API JWT tienda móvil — `/api/*` (`Content-Type: application/json; charset=UTF-8`, siempre HTTP 200)

| Endpoint | Entrada | Éxito | Errores |
|---|---|---|---|
| `POST /api/auth` | cuerpo JSON `user_id`, `password` | `{"message":"Success.","jwt":"<RS256, claims iat, exp=+30 días, user_id>"}` | `{"message":"Invalid config."}`, `{"message":"Invalid User."}` |
| (todas las demás) | cabecera `Authorization: Bearer <jwt>` | — | token inválido: `{"status":"ng","message":"<mensaje de firebase/php-jwt>"}`; **sin cabecera: fatal, HTTP 500 sin cuerpo** (fixture); Laravel responde lo mismo (500 vacío) para no cambiar lo que ve el cliente |
| `GET /api/customer` | cuerpo: `id`, `kojin_mobile` (=), `tb_shop_cd` (num.), `user_address` (LIKE sobre `add`), `kojin_birth` (LIKE `%v`), otras claves de una lista blanca de columnas de `tb_customer` (LIKE `%v%`); **sin filtros → 200 con cuerpo vacío** | `{"message":"Success.","user_id":…,"payload":<cuerpo>,"result":[{id,email1,zip,user_address,name,name_kana,kojin_mobile,kojin_birth,tb_shop_cd}]}`; `result:""` si vacío; `disp_flg='1'`, orden `cd` | — |
| `GET /api/shop` | — | `result:[{cd,kaishamei,kaishamei_kana}]` (`disp_flg='1'`, orden `cd`) | — |
| `GET /api/point` | cuerpo: `user_id` (= `tb_customer.id`, entero > 0), `page`, `limit` (10), `range` | `result:{count,data:[filas de tb_point_log],limit,page}`, orden `touroku_date DESC` | `{"status":"ng","message":"必須情報がありません。"}` |
| `GET /api/mobile_shop/customer/<id>` | `<id>` = **`tb_customer.id`** (no `cd`: con un `cd` todas las claves salen `null`) | `result:{cd,id,email1,name,name_kana,zip,pref,add,add_build,add_number,kojin_birth,kojin_mobile,touroku_date,update_date}` | — |
| `GET /api/mobile_shop/customer` | cuerpo: `disp_flg`, `user_id` (`<dominio><id lpad 9>`), `id` (varios separados por espacio = OR), `kojin_mobile`; al menos uno | `result:{count,data:[{mycolle_cateogry,id,user_id,email1,zip,user_address,name,name_kana,kojin_mobile,kojin_birth,tb_shop_cd,disp_flg}]}` en N BD, `touroku_kubun='2'`, orden `id DESC`; sin filtros `result:false` | sin dominios: `ERROR: No domain list.` |
| `POST /api/mobile_shop/fetch_paid_member` | `customer_id` | resultado de extlink (`apiSnderAuth` → `apiJudServiceTgt`) | los de extlink |
| `POST /api/mobile_shop/send_unpaid_member_status` | `customer_id` | resultado de extlink (`apiSnderCan`) | los de extlink |
| `POST /api/mobile_shop/passcode` | `shop_cd`, `passcode` | `{"status":"ok"}` (compara con `==`: `"04321"` coincide con `"4321"`) | `{"status":"ng","message":"invalid passcode"}` |
| `GET/POST /api/mobile_shop/point` | `customer_id` (entero > 0) | `result:{service_point,point_yukoukigen}` o `result:false` | `{"status":"ng","message":"IDがありません。"}`, `{"status":"ng","message":"顧客コードが見つかりません。"}` |
| `POST /api/mobile_shop/point_use` | `customer_id`, `shop_cd`, `use_points` (enteros > 0) | **sin envoltorio**: `{"status":"ok","res":"<n>ポイントを削減しました。"}` | `{"status":"ng","message":"<n>ポイントを削減することは出来ません"}` y los de validación |
| `POST /api/mobile_shop/point_exchange_item` | `customer_id`, `salesItemCd`, `shop_cd` | resultado del canje sin envoltorio | `{"status":"ng","res":"不正なリクエスト。"}` / `顧客情報がありません。` / `交換可能商品ではありません。` / `使用可能な店舗ではありません。` / `ポイントが不足です。` |
| `GET /api/mobile_shop/sales_items[/<cd>]` | cuerpo: `page`, `limit` (10), `range` (3), `name`, `discription` (sic, ILIKE por palabras con espacio normal o ancho), `available_shop_cds`, `is_exchangeable_of_point`, `number_of_points_exchanged_start/_end`, `hyouji_flg` | `result`: ítem, o `{count,data,limit,page}` (`disp_flg=1`); `sales_items` ya es Eloquent en el legacy: tipos de Eloquent, sin `pgText` | — |
| `GET /api/<desconocido>` | — | — | legacy: fatal (500 sin cuerpo). Laravel: `{"message":"Not Found."}` con 404 (no se reproduce un fatal) |

Contrato que no se rompe: erratas (`discription`, `cutomer_cd`, `mycolle_cateogry`), HTTP 200 en error, GET con cuerpo JSON, `user_id`/`customer_id` = `tb_customer.id` (no `cd`), `tb_terminal_cd = -3` / `tb_staff_cd = -1` (0 en el canje) en `tb_point_log`.

### 6.2 `mpa_*` — `/api/mpa_<nombre>.php` (sin autenticación)

| Endpoint | Entrada | Respuesta |
|---|---|---|
| `mpa_corresponding.php` | GET/POST `login_id` (opc.) | `{"status":"ok"\|"ng","version":"0.0.2","application_main_image":{"main","small"},"apli_home_thumbnail":{"main"},"apli_home_comment"}`; URLs `http://<host>/hp/uploads/c6/...?r=<filemtime>` |
| `mpa_getTopBanner.php` | `login_id` (opc.) | `{"status":"ok","errMsg":"","data":[{"imageUrl","url"}]}` |
| `mpa_getCoverFlow.php` | `login_id` (opc.) | `{"status","errMsg","data":[{"imageUrl","url"}]}` |
| `mpa_albumUpdate.php` | POST multipart `domain`, `cd` (`tb_customer.id`), `pic_name` (imagen), `comment` | **302** a `http://b-develop.g-api.net/api/mpa/myAlbumPage?domain=…&cd=…&status=ok\|err1\|err2&comment=…` (host en config) |
| `mpa_setraiten.php` | `regist_id` (numérico), `membership` (1 = beacon), `shop_cd`, `beacon_cd`; con un socio válido el legacy registra la visita y luego da **fatal 500** (línea 43): se reproduce el registro y se responde el JSON `ok` (anotado para Japón) | `{"status":"ok","customer_info":{"cd","id","name"}}` o `{"status":"error","errMsg","errorMsg"}` con `入力される内容は数字のみでお願いいたします。` / `顧客が特定できませんでした。` / `来店処理できませんでした。インターバルなどをご確認ください。` |

### 6.3 `appview/*.php` (HTML para el WebView)

Contrato = URL y parámetros (`customer_cd`, `cutomer_cd` en `visit_history`, `shop_cd`, `staff_cd`, `kensu`, `page`, `demo`, `sess`) y el mismo HTML del legacy (§2b). Con formato fijo byte a byte: `memberId.php` (texto `login_id` o `error`), `coupon_control.php` (JSON `{status…}`).

### 6.4 `tool/api` — `/system/tool/api/<categoryN>/<archivo>.php`

Común: `key` (= `CRM_API_KEY`, `hash_equals`; si falta o no coincide, **cuerpo vacío** y 200). `data_type=jsonp` + `callback` → `callback({…})` (con `callback` escapado como `h()`); si no, JSON. Formato **armado como texto** por el legacy (no `json_encode`): `{ "status":"<…>", "max":"<n>", "result":[ … ] }`, con esos espacios y `max` como texto; se reproduce con plantillas de texto, no con `LegacyJson`.

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

Las claves exactas de cada `result` se fijan al grabar los fixtures (paso 9).

### 6.5 CTI — `/system/desktop_request/*.php`

| Endpoint | Parámetros | Respuesta |
|---|---|---|
| `ctiWindow.php` | `tel` (solo dígitos), `act` (`new`), `customerCd` | 302: `act=new` → alta de cliente con `tel`; si el módulo de reservas (9 u 11) está activo → agenda de reservas (`isCti=1&tel&customerCd`); si no → búsqueda de clientes por teléfono. Las URL destino son las rutas de S2 (exigen sesión). |
| `customerDetailHtml.php` | `cd` | fragmento HTML de la ficha del cliente |
| `jGetCustomerData.php` | `cd`, `id` o `tel` (al menos uno) | texto plano: una línea por socio activo `cd<>name<>name_kana<>id<>kanri_id<> \n` (**espacio antes del salto obligatorio**), orden `cd`; sin criterio o sin filas: `NO_DATA` |

### 6.6 Utilitarios

| Endpoint | Respuesta |
|---|---|
| `GET /version/crm_version.php` | `text/plain` `2.704`, sin salto de línea |
| `GET /ptag/<ID>` (cookie `UID`) | 302 a `tb_url_list.url` (`disp_flg='1'` y `hyouji_flg='1'`) + fila en `url_transfer_data`; textos `無効なIDです。` / `GETデータがありません` |
| `GET /air/update-info.xml`, `/air/crossdomain.xml` | estáticos, idénticos |
| `GET /air/air_info_out.php?login_id&kensu` | último `tb_information` visible para ese empleado (tienda, 所属, 雇用形態, 役職; `master` ve todo); formato a grabar del legacy |

---

## 7. Riesgos y notas para Japón

- **⏸ 勤怠 sin fuente de datos:** ver el aviso del principio y §5.5.
- **La baja de un empleado libera su tarjeta** (`staff_delete_register.php`): borra su `tb_card` y vacía `felica_no` en `tb_staff` y en todos sus `tb_work_time` (la asistencia se conserva; corregido al implementarlo, 2026-11-27).
- **Convivencia con el legacy sobre la misma BD:** las dos aplicaciones numeran con `hatuban`; el servicio de S1 debe bloquear la fila igual que `cHatuban` para no repetir `cd`.
- **`mobile_shop/customer` consulta N BD** (`DOMAIN_LIST`): hace falta la lista real de dominios y credenciales.
- **Sin autenticación** en `mpa_*`, `appview` (IDOR por `customer_cd`) y CTI (`customerDetailHtml`, `jGetCustomerData`): se mantiene el contrato; propuesta para Japón: token firmado y throttle.
- **Contraseñas en texto plano** (`tb_staff.login_pass`, `kintai_tb_config.password`, `tb_customer.login_pass`, passcode de la tienda móvil) y **`md5(login_pass)` en URL** (`mpa_getTopBanner`) y cookie `UID` de `ptag`: siguen igual mientras el legacy comparta la BD; pasar a hash es una decisión conjunta (S1/Japón).
- **Fechas 1970 guardadas por producción** en `tb_shop_work`, `tb_work_time` y `tb_staff_shift` (PENDING 1b.1): `LegacyMonth` las lee sin fallar y las pantallas las muestran como el legacy corregido.
- **Estáticos rotos en el legacy:** `kintai/index.php` pide `jquery.alerts.*` en una ruta que no existe (§2b).
