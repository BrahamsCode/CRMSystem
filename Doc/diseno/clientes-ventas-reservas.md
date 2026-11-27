# Diseño S2 — Clientes (顧客管理), ventas (販売管理) y reservas (予約管理)

> Sesión S2 · rama `l13/clientes` · 2026-11-26 · estado: **borrador para revisión de S0** (sin código).
> Fuentes: `Doc/analisis-legacy/01-clientes-y-ventas.md`, `05-…` (sección 予約管理 `category9/yoyaku/`), `07-…` (A.5 `r/` y `reserve/`); código legacy `crm2-php8/public_html` (solo lectura) y lo que ya existe en `public_html/`.

## 0. Convenciones aplicadas en este grupo

| Qué | Clientes | Ventas | Reservas |
|---|---|---|---|
| `<modulo>` (rutas, vistas, lang, menú) | `clientes` | `ventas` | `reservas` |
| Archivo de rutas | `routes/modulos/clientes.php` | `routes/modulos/ventas.php` | `routes/modulos/reservas.php` (+ grupo público `r/`, ver §1.4) |
| Nombre de ruta / URL | `admin.clientes.*` · `/admin/clientes/…` | `admin.ventas.*` · `/admin/ventas/…` | `admin.reservas.*` · `/admin/reservas/…` |
| Controladores | `Admin/Customers/` (ya existe) | `Admin/Sales/` | `Admin/Reservations/` |
| Form Requests | `Requests/Customers/` | `Requests/Sales/` | `Requests/Reservations/` |
| Vistas · textos | `views/admin/clientes/` · `lang/ja/clientes.php` | `views/admin/ventas/` · `lang/ja/ventas.php` | `views/admin/reservas/` · `lang/ja/reservas.php` |
| Servicios | `app/Services/Customers/` | `app/Services/Sales/` | `app/Services/Reservations/` |

Lo existente (`routes/web.php` grupo `customers`, `views/admin/customers`, `lang/ja/customers.php`) se **mueve** a los nombres de la tabla en la tarea C0 (ver §4): las rutas pasan de `admin.customers.*` a `admin.clientes.*`. El grupo `customers` de `routes/web.php` es de S1: pedido para que lo quite cuando exista `routes/modulos/clientes.php` (§5).

Estados de la tabla: `hecho` (cumple paridad), `parcial` (existe pero falta lo anotado; cuenta como pendiente), `pendiente`, `descartado: motivo`.
Abreviaturas de controladores: `Cu\` = `Admin\Customers\`, `Sa\` = `Admin\Sales\`, `Re\` = `Admin\Reservations\`.

---

## 1. Tabla de paridad

### 1.1 顧客管理 — `category1/`

#### Inicio

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `index.php` | 顧客管理トップ | `admin.clientes.home` | `Cu\ModuleController@index` | `customers` (contador de altas) | parcial: falta contador de altas del día/mes con enlace a la lista filtrada; la limpieza de temporales pasa al Scheduler (§3) |

#### `customer/` — listas, búsqueda y configuración

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `customer_list.php` | 顧客一覧・検索 | `admin.clientes.index`, `.search` | `Cu\CustomerController@index/search` + `CustomerSearch` | `customers`, `custom_values`, `visits` | parcial: verificar los 103 filtros, 表示件数, 郵便CSV y botones 配信/LINE配信 (este último enlaza a S3) |
| `customer_search.php` | 来店処理 | `admin.clientes.visits`, `.visits.store` | `Cu\VisitController@index/store` + `VisitService` | `visits`, `customer_points`, `customer_stamps` | parcial: falta `staff_id` y la regla de día de negocio (5:00) |
| wrappers `customer_new*.php`, `customer_detail.php`, `customer_recover_confirm.php`, `customer_delete_register.php`, `del_record.php`, `kanren_*` (4), `add_record_*` (3), `photoalbum_*` (3) | (wrappers) | — | — | — | descartado: wrappers de 3 líneas de `inc/` (sus filas están en `inc/`); `kanren_record_confirm.php` está roto (exige un archivo inexistente) |
| `customer_new_complete.php` | 顧客登録情報 | — | — | — | descartado: versión paralela del alta sin enlace en el menú; el alta de `inc/` la cubre |
| `form_config.php`, `form_config_register.php` | 表示・検索・CSV項目設定 | `admin.clientes.field-settings`, `.field-settings.update` | `Cu\FieldSettingController@index/update` | `field_settings` | parcial: hay `index`; falta guardar (必須/表示/検索/CSV) |
| `search_cate_list.php` | 顧客追加情報設定 | `admin.clientes.custom-categories` (+`.flags`) | `Cu\CustomCategoryController@index/updateFlags` | `custom_categories` | hecho |
| `search_cate_new.php`, `_new_check.php`, `_new_up.php` | 顧客追加情報 (categoría) | `.custom-categories.create/.store` | `Cu\CustomCategoryController@create/store` | `custom_categories` | hecho (nombre repetido en `StoreCustomCategoryRequest`) |
| `search_cate_update.php`, `_update_up.php` | 〃 | `.custom-categories.edit/.update` | `@edit/update` | `custom_categories` | hecho |
| `search_cate_delete_up.php` | 〃 | `.custom-categories.destroy` | `@destroy` | `custom_categories`, `custom_fields`, `custom_values` | hecho (SoftDeletes en cascada: verificar valores) |
| `search_form_list.php` | 顧客追加情報 (campos) | `.custom-fields` | `Cu\CustomFieldController@index` | `custom_fields` | hecho |
| `search_form_new.php`, `_new_check.php`, `_new_up.php` | 〃 | `.custom-fields.create/.store` | `@create/store` | `custom_fields`, `custom_field_options` | hecho |
| `search_form_update.php`, `_update_up.php` | 〃 | `.custom-fields.edit/.update` | `@edit/update` | idem | hecho |
| `search_form_delete_up.php` | 〃 | `.custom-fields.destroy` | `@destroy` | `custom_fields`, `custom_values` | hecho |
| `customer_group_category_new.php`, `_frm.php`, `_register.php` | 顧客グループ設定 | `admin.clientes.groups` (resource sin `show`) + `.groups.sort` | `Cu\CustomerGroupController` | `customer_groups` (modelo de S3, aquí solo datos) | parcial: solo `index`; faltan alta, edición, ▲ orden y borrado |
| `customer_level_category_new.php`, `_frm.php`, `_register.php` | 顧客レベル | — | — | — | descartado: sin enlace en el menú (desuso); se reabre si Japón lo pide |
| `raiten_douki.php`, `_frm.php`, `_register.php` | 初回来店動機項目設定 | `admin.clientes.visit-motives` (resource) + `.sort` | `Cu\VisitMotiveController` | `visit_motives` | parcial: solo `index` |
| `point_setting.php`, `_frm.php`, `_register.php` | ポイント管理 | (S3) `admin.promocion.points.*` | `Promotions\PointController@edit/update` | `point_settings`, `customer_group_point_rules` | descartado: lo cubre S3 (Point*); el menú de clientes enlaza a esa ruta |
| `ajax_apliFileUpload.php` | (imágenes マイページ) | (S3/S4) | — | — | descartado: imágenes del マイページ de puntos, las sirve S3 con Storage |
| `point_history.php` | ポイント利用一覧 (ポイント履歴) | `admin.clientes.point-history`, `.point-history.csv` | `Cu\PointHistoryController@index/csv` | lee `customer_points` (S3) | pendiente (CSV Shift_JIS) |
| `csv_import.php` | CSVインポート | `admin.clientes.import`, `.import.store` | `Cu\CustomerImportController@create/store` + Job `ImportCustomersCsv` | `customers`, `customer_imports` | pendiente |
| `csv_export.php`, `csv_from_js.php`, `user_csv.php` | CSV de clientes | `admin.clientes.export` | `Cu\CustomerController@export` + `CustomerCsvExporter` | — | parcial: falta Shift_JIS y generación por bloques (`lazy()`); `csv_from_js`/`user_csv` descartados (Flash/huérfano) |
| `csv_download.php` (+carpeta), `csv_download_ex02.php` (+), `csv_download_template.php` (+) | CSV (Flash) | `admin.clientes.export` (`?format=standard|ex02|template`) | `Cu\CustomerController@export` | — | pendiente: 3 formatos como variantes del exportador; sin Flash ni temporales |
| `customer_export.php` | (volcado de tabla) | — | — | — | descartado: volcado arbitrario de tablas (riesgo), no migrar |
| `customer_sumup_list.php`, `customer_sumup_list2.php` | 顧客情報集計 | `admin.clientes.statistics` | `Cu\StatisticController@index` | `customers`, `visits` | parcial: hay sexo/edad; faltan cortes por 業種 y por prefectura. Se migra una sola versión |
| `customer_card_delete.php` | UID関連付け解除 | `admin.clientes.felica.destroy` | `Cu\CustomerFelicaController@destroy` → `FelicaCardService` (S4) | `customers.felica_no`, `felica_cards` (S4) | pendiente (copia de `inc/`, una sola ruta) |
| `karute_customer_search.php` | 顧客一覧・検索 (selector) | `admin.clientes.lookup` (JSON) | `Cu\CustomerLookupController` | `customers` | pendiente (selector común: カルテ, ventas, reservas) |
| `karute_frm.php`, `karute_new.php`, `karute_register.php`, `karute_update.php` | カルテ | — | — | — | descartado: copia de `inc/karute_*` |
| `stamp_use_register.php` | (usar sello) | — | — | — | descartado: copia reducida de `inc/stamp_use_register.php` |
| `ajax_category.php`, `ajax_staff.php`, `zip.php` | (utilidades) | `admin.clientes.postal-code` · `admin.clientes.options.*` | `Cu\PostalCodeController` · `Cu\OptionController` | — | parcial: `zip.php` hecho (zipcloud); combos por tienda pendientes |
| `inc/page.php`, `inc/raiten_rireki.php`, `inc/search_set1*.php`, `inc/search_set2_sumup.php`, `js/*`, `uploads/` | (fragmentos) | — | — | — | descartado como vista: paginador de Laravel, parciales Blade y `CustomerSearch` |

#### `inc/` — ficha, alta, edición, pestañas, visitas, puntos y sellos

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `customer_new.php` | 顧客新規登録 | `admin.clientes.create`, `.edit` | `Cu\CustomerController@create/edit` | `customers`, `customer_companies`, `custom_values` | parcial: alta hecha; falta edición y `staff_id` (personal asignado) |
| `customer_new_confirm.php` | 顧客登録情報 (確認) | (misma pantalla) | `StoreCustomerRequest` / `UpdateCustomerRequest` | — | parcial: la confirmación se sustituye por validación en servidor; reproducir los 必須 de `field_settings` |
| `customer_new_register.php` | 顧客登録情報 | `.store`, `.update` | `@store/update` + `CustomerNumberGenerator` | idem | parcial: falta `update` |
| `customer_detail.php` | 顧客登録情報 | `admin.clientes.show` (`?tab=`) | `Cu\CustomerController@show` + `CustomerPolicy` por pestaña | — | parcial: pestañas vacías (ver `detail_tab*`) |
| `customer_recover_confirm.php` | 顧客登録情報 (復元) | `admin.clientes.restore` (POST) | `@restore` | `customers` | pendiente |
| `customer_delete_register.php` | 顧客登録情報 (削除) | `admin.clientes.destroy` | `@destroy` | `customers`, `custom_values`, `felica_cards` (S4), `customer_entries` | pendiente (SoftDeletes y liberar la tarjeta vía S4) |
| `customer_card_delete.php` | UID関連付け解除 | `admin.clientes.felica.destroy` | `Cu\CustomerFelicaController@destroy` → `FelicaCardService` (S4) | `customers.felica_no`, `felica_cards` (S4) | pendiente |
| `customer_window_close.php` | 顧客登録情報 | — | — | — | descartado: cierre de popup; el diseño nuevo redirige |
| `add_record_new.php`, `add_record_confirm.php`, `add_record_register.php` | 顧客登録情報 (追加情報) | `admin.clientes.custom-records.create/.store/.edit/.update` | `Cu\CustomRecordController` | `custom_values` (`row_no`) | pendiente |
| `del_record.php` | 顧客登録情報 | `.custom-records.destroy` | `Cu\CustomRecordController@destroy` | `custom_values` | pendiente |
| `kanren_record_new.php`, `kanren_record_register.php`, `kanren_del_record.php` | 来店履歴の追加・修正 | `admin.clientes.visit-records.create/.store/.edit/.update/.destroy` | `Cu\VisitRecordController` + `VisitService` | `visits`, `customer_points`, `customer_stamps`, `custom_values` | pendiente (lo más delicado: una transacción) |
| `raiten_register.php`, `raiten_function.php` | (registrar visita) | `admin.clientes.visits.store` | `Cu\VisitController@store` + `VisitService::register` | `visits`, `customers` (stats) | parcial: falta recálculo de `average_visit_cycle` y `last_visit_date` en borrado/corrección |
| `point_register.php`, `ajax_point_register.php` | (ポイント) | `admin.clientes.points.store` | `Cu\CustomerPointController@store` → `LoyaltyService` (S3) | `customer_points` | pendiente |
| `stamp_register.php`, `stamp_use_register.php` | (スタンプ) | `admin.clientes.stamps.store`, `.stamps.use` | `Cu\CustomerStampController@store/use` → `LoyaltyService` (S3) | `customer_stamps`, `coupon_customer` | pendiente |
| `karute_frm.php`, `karute_new.php`, `karute_register.php`, `karute_update.php` | カルテ | `admin.clientes.kartes.create/.store/.edit/.update/.destroy` | `Cu\KarteController` | `kartes` | pendiente |
| `photoalbum_new.php`, `_frm.php`, `_register.php`, `_update.php`, `_delete.php` | マイアルバム | `admin.clientes.photos.*` | `Cu\CustomerPhotoController` | `customer_photos` | pendiente |
| `ajax_picupload.php` | (subida de foto) | (dentro de `photos.store`) | `CustomerPhotoController@store` + `Storage` | `customer_photos` | pendiente |
| `ajax_changePaidMemberFlg.php` | (有料会員) | `admin.clientes.paid-member.update` | `Cu\PaidMemberController@update` → `MobileShopClient` (S5) | — | pendiente (depende del cliente HTTP de S5) |
| `ajax_connectPosData.php`, `ajax_connectPosKoubaiData.php`, `ajax_sendPosKoubaiMemoData.php` | (購買履歴 POS) | — | `PosGateway` sin implementar | — | descartado: POS fuera de alcance (2026-11-26); queda la interfaz `PosGateway` (§3) |
| `ajax_search_kaiin.php`, `ajax_category.php`, `ajax_raiten_list.php`, `ajax_raiten_time_select.php`, `ajax_shop_point_config.php`, `ajax_shop_stamp_config.php` | (utilidades) | `admin.clientes.lookup`, `admin.clientes.options.*` | `Cu\CustomerLookupController`, `Cu\OptionController` | — | pendiente (JSON) |
| `detail_tab.php` | 顧客情報 | `.show?tab=summary` | `@show` | `customers`, `ranks` | hecho (verificar rango anterior) |
| `detail_tab2.php` | 個人情報/法人情報 | `.show?tab=personal` | `@show` | `customers`, `customer_companies` | hecho |
| `detail_tab3.php` | 販促用情報 | `.show?tab=promotion` | `@show` | `customer_groups`, `visit_motives` | hecho |
| `detail_tab4.php` | 来店履歴 | `.show?tab=visits` | `@show` | `visits` | parcial: lista sin enlaces de corrección |
| `detail_tab5.php` | カルテ | `.show?tab=kartes` | `@show` | `kartes` | pendiente |
| `detail_tab6.php` | マイクーポン | `.show?tab=coupons` | `@show` | `coupon_customer` (S3) | pendiente (solo lectura) |
| `detail_tab7.php` | マイアルバム | `.show?tab=photos` | `@show` | `customer_photos` | pendiente |
| `detail_tab8.php` | ポイント | `.show?tab=points` | `@show` | `customer_points` (S3) | pendiente |
| `detail_tab9.php` | 関連情報 | `.show?tab=related` | `@show` | `visits` + `customer_points`/`customer_stamps`/`custom_values` por `visit_id` | pendiente |
| `detail_tab10.php` | スタンプ | `.show?tab=stamps` | `@show` | `customer_stamps` (S3) | pendiente |
| `detail_tab11.php` | 購買履歴 | `.show?tab=purchases` | `@show` | `sales`, `sale_lines` | pendiente: muestra las ventas internas; la parte POS queda tras `PosGateway` (vacía) |
| `detail_tab12.php` | 予約状況 | `.show?tab=reservations` | `@show` | `reservations` | pendiente (tras R1) |
| `detail_tab13.php` | 紹介実績 | `.show?tab=referrals` | `@show` | `customers.referrer_id` | pendiente |
| `detail_tab14.php` | 有料会員状況 | `.show?tab=paid-member` | `@show` → `MobileShopClient` (S5) | — | pendiente |
| `detail_tabcustom.php` | (categoría adicional) | `.show?tab=custom-{slug}` | `@show` | `custom_values` | parcial: verificar categorías múltiples |
| `search_set2.php`, `page_control.php` | (buscador de productos, paginador) | — | — | — | descartado: `tb_c2_*` borradas en 2020; paginador de Laravel |

#### `biyou/` — ficha 美容

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `sejutsu_cate.php` | 予約カテゴリー管理 | `admin.clientes.service-categories.index` | `Cu\ServiceCategoryController@index` | `service_categories` | pendiente (catálogo que usa カルテ) |
| `sejutsu_cate_register.php` | 施術カテゴリー登録 | `.service-categories.store/.update` | `@store/update` | `service_categories` | pendiente |
| `sejutsu_cate_delete.php` | 施術カテゴリー削除 | `.service-categories.destroy` | `@destroy` | `service_categories` | pendiente |
| `karute_detail.php` | カルテ詳細 | `admin.clientes.kartes.show` | `Cu\KarteController@show` | `kartes` | pendiente |
| `karute_frm.php`, `karute_new.php`, `karute_register.php`, `karute_update.php`, `karute_customer_search.php`, `ajax_category.php` | (copias) | — | — | — | descartado: copias de `inc/` y `customer/` |

#### `config/` — configuración inicial

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `felica_mode.php` | カードリーダーモード設定 | `admin.clientes.visit-interval` (+ modo y mensajes) | `Cu\VisitIntervalController@index/update` | `shops.visit_interval_seconds`, `customer_reader_settings` | parcial: falta el modo y los mensajes de entrada/salida |
| `card_new.php` | カード管理 | (S4) `admin.terminales.felica-cards.*` | `Terminales\FelicaCardController` | `felica_cards` (S4), `customers.felica_no` | descartado: lo cubre S4 (ICカード管理, misma tabla `tb_customer_card`); el menú de clientes enlaza a esa ruta |
| `card_staff_select.php` | (selector de personal) | `admin.clientes.options.staff` | `Cu\OptionController@staff` | lee `staff` (S1) | pendiente |
| `shop_work_calendar.php` | 営業日設定 | (S5) `admin.operaciones.business-days.edit` | `BusinessDayController` (S5) | `business_days` (S5) | descartado: misma tabla y pantalla que el 営業日設定 de S5; el menú enlaza a su ruta |
| `mail_comment.php`, `mail_comment_register.php` | (comentario de correo) | — | — | — | descartado: sin enlace en el menú (escuelas); se reabre si Japón confirma uso |
| `schoolyear_config.php`, `schoolyear_register.php`, `schoolyear_delete.php` | (学年設定) | — | — | — | descartado: ídem |
| `schoolclass_config.php`, `schoolclass_register.php`, `schoolclass_delete.php` | (クラス設定) | — | — | — | descartado: ídem |

#### `entrance/` — entrada y salida

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `working_calendar.php` | 入退室一覧 (calendario) | `admin.clientes.entries.calendar` | `Cu\CustomerEntryController@calendar` | `customer_entries` | pendiente (Carbon; sin botones 1970) |
| `work_list.php` | 入退室一覧 | `admin.clientes.entries.index` (`?date=`) | `Cu\CustomerEntryController@index` | `customer_entries` | pendiente |

#### `rank/` — rangos

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `customer_rank_list.php` | 顧客ランク設定 | `admin.clientes.ranks` (+ `.ranks.status`) | `Cu\RankController@index/updateStatus` | `ranks` (`hyouji_flg` → `status`) | parcial: hay `index`; falta la casilla de visibilidad |
| `kingaku_rank_new.php`, `kingaku_rank_update.php` | 顧客ランク設定 (金額) | `.ranks.create/.store/.edit/.update/.destroy/.sort` (`type=1`) | `Cu\RankController` | `ranks` | pendiente |
| `raiten_rank_new.php`, `raiten_rank_update.php` | 顧客ランク設定 (来店) | idem (`type=2`) | `Cu\RankController` | `ranks` | pendiente |
| `customer_rank_frm.php`, `customer_rank_frm_register.php` | (formulario de rango) | (idem) | `StoreRankRequest` | `ranks` | pendiente |
| `ajax_customer_rank_list.php` | (lista AJAX) | — | — | — | descartado: recarga normal de la lista |
| `customer_rank_schedule.php`, `_register.php` | 顧客ランク振り分け設定 | `admin.clientes.rank-schedules`, `.store` | `Cu\RankScheduleController@index/store` | `rank_schedules` | hecho (pantalla); el comando va en la fila siguiente |
| `setCustomerKingakuRank.php` | (tarea) | — | `customers:assign-ranks --type=amount` (Scheduler) + `RankAssigner` | `customers`, `ranks`, `visits` | pendiente |
| `setCustomerRaitenRank.php` | (tarea) | — | `customers:assign-ranks --type=visits` | idem | pendiente |

#### `shukei/` — agregaciones

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `shukei_list.php` | 集計ツール | `admin.clientes.reports.index` | `Cu\ReportController@index` | `report_links` | pendiente |
| `model1.php`, `model1_search.php`, `model1_select.php` | (モデル集計 1) | `admin.clientes.reports.visits` | `Cu\ReportController@visits` + `CustomerReportQuery` | `visits` (edad/sexo/ocupación al momento de la visita) | pendiente |
| `model2.php`, `model2_search.php` | (モデル集計 2) | `admin.clientes.reports.visits` (`?segment=`) | idem | `visits` | pendiente |
| `point1.php`, `point1_search.php` | 【会計金額】集計 | `admin.clientes.reports.amounts` | `Cu\ReportController@amounts` | `visits`, `customer_points` | pendiente |
| `point2.php`, `point2_search.php` | 【ポイント】集計 | `admin.clientes.reports.points` | `Cu\ReportController@points` | `customer_points` | pendiente |
| `stamp1.php`, `stamp1_search.php` | 【会計金額】集計 (スタンプ) | `admin.clientes.reports.stamps` | `Cu\ReportController@stamps` | `customer_stamps`, `visits` | pendiente |
| `customerDataOutPut.php` | csv出力管理画面 (来店履歴抽出) | `admin.clientes.reports.visit-csv`, `.visit-csv.download` | `Cu\VisitCsvController@create/download` | `customers`, `visits`, `customer_points`, `custom_values` | pendiente (StreamedResponse Shift_JIS) |
| `shukei_mail_new.php`, `_confirm.php`, `_send.php` | (配信) | (S3) | `Promotions\MessageController` | — | descartado: envío de S3; el informe pasa el filtro a su pantalla |
| `customer_sumup_list*.php` | (ver `customer/`) | — | — | — | descartado: fila en `customer/` |

#### Ventas internas — `category1/karute_menu/` (módulo `ventas`)

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `karute_new.php` | 売上データ作成 | `admin.ventas.sales.create` | `Sa\SaleController@create` | `service_menus` | pendiente |
| `karute_new_frm.php` | 売上データ作成 (formulario) | (misma pantalla) | `Sa\SaleController@create` | `customers`, `service_menus`, `discounts`, `staff` | pendiente |
| `karute_new_register.php` | 売上データ作成 | `admin.ventas.sales.store` | `Sa\SaleController@store` + `SaleService` | `sales`, `sale_lines` | pendiente (comisión, nº de cuenta) |
| `karute_delete_list.php` | 売上追加・修正・削除 | `admin.ventas.sales.index` | `Sa\SaleController@index` | `sales` | pendiente |
| `karute_delete_register.php` | 売上追加・修正・削除 | `admin.ventas.sales.destroy` | `Sa\SaleController@destroy` | `sales` | pendiente |
| `karute_menu.php`, `karute_menu_register.php` | メニュー管理 | `admin.ventas.menus.*` | `Sa\ServiceMenuController` | `service_menus` | pendiente |
| `karute_waribiki.php`, `karute_waribiki_register.php` | 割引管理 | `admin.ventas.discounts.*` | `Sa\DiscountController` | `discounts` | pendiente |
| `keihi.php`, `keihi_register.php`, `keihi_delete.php` | 経費管理 | `admin.ventas.expenses.*` | `Sa\ExpenseController` | `expenses` | pendiente |
| `staff_total.php`, `ajax_staffTotal.php` | スタッフ別集計 | `admin.ventas.reports.staff` | `Sa\SalesReportController@staff` | `sales` | pendiente |
| `report.php`, `ajax_report.php` | 日報・週報・月報 | `admin.ventas.reports.period` | `Sa\SalesReportController@period` | `sales`, `expenses` | pendiente |
| `ajax_karute_menu_list.php`, `ajax_karute_waribiki_list.php`, `ajax_search_kaiin.php`, `ajax_staff.php` | (utilidades) | `admin.ventas.options.*`, `admin.clientes.lookup` | `Sa\OptionController`, `Cu\CustomerLookupController` | — | pendiente (JSON) |
| `css/*` | | — | — | — | descartado: estilos (Tailwind) |

### 1.2 販売管理 — `category2/` (módulo `ventas`)

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `index.php` | 販売管理トップ | `admin.ventas.home` | `Sa\ModuleController@index` | — | pendiente |
| `item/list.php` | 商品管理 (商品検索・一覧) | `admin.ventas.products.index` | `Sa\ProductController@index` | `products`, `product_shop` | pendiente |
| `item/edit.php` | 商品新規登録 / 編集 | `.products.create/.store/.edit/.update` | `Sa\ProductController` + `StoreProductRequest` | `products`, `product_shop` | pendiente (editor enriquecido según S1/PENDING 3.1) |
| `item/ajax_edit.php` | (validación) | — | `StoreProductRequest` | — | descartado como vista: validación en Form Request |
| `item/ajax_list_delete.php` | (borrado) | `.products.destroy` | `Sa\ProductController@destroy` | `products` | pendiente |

### 1.3 予約管理 — `category9/yoyaku/` (módulo `reservas`)

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `time_table.php` | 予約表 | `admin.reservas.timetable` (`?view=day|week|month`) | `Re\TimetableController@index` | `reservations`, `reservation_slots`, `reservation_settings` | pendiente |
| `time_table_staff.php` | スタッフ別予約表 | `admin.reservas.timetable.staff` | `Re\TimetableController@staff` | idem | pendiente |
| `new_time_table.php` | 予約表 (nuevo) | — | — | — | descartado: sin enlace, solo carga |
| `ajax_time_table.php` | — | `admin.reservas.timetable.data` (JSON) | `Re\TimetableController@data` + `TimetableService` | idem | pendiente |
| `ajax_move_objs.php` | — | `admin.reservas.reservations.move` | `Re\ReservationController@move` + `ReservationService::move` | `reservations`, `reservation_slots` | pendiente |
| `ajax_uketsuke_status.php` | — | `admin.reservas.reservations.status` | `Re\ReservationController@status` | `reservation_slots.accept_state` + `reservation_state_logs` | pendiente (el 状況 es de la franja) |
| `ajax_loadNewYoyakuList.php`, `ajax_loadNewRaitenList.php` | — | `admin.reservas.timetable.recent` | `Re\TimetableController@recent` | `reservations`, `visits` | pendiente |
| `ajax_getYoyakuDatetimeForm.php` | — | (parcial Blade del formulario) | `Re\ReservationController@edit` | — | pendiente |
| `time_table_function.php`, `sample_array.php` | — | — | — | — | descartado: fragmentos (`TimetableService`) y datos de ejemplo |
| `yoyaku.php` | 予約 | `admin.reservas.reservations.create` | `Re\ReservationController@create` | — | pendiente |
| `yoyaku_calendar.php` | 時間選択 | (paso 2 de `create`) | `Re\ReservationController@create` + `AvailabilityService` | `reservation_slots`, `reservation_menus`, `facilities`, `business_days` (S5) | pendiente |
| `yoyaku_customer_select.php` | 顧客情報入力 | (paso 3 de `create`) | idem + `admin.clientes.lookup` | `customers` | pendiente |
| `yoyaku_register.php` | (registro de reserva) | `admin.reservas.reservations.store/.update` | `Re\ReservationController@store/update` + `ReservationService` + Mailable `ReservationConfirmed` (cola) | `reservations`, `reservation_items` | pendiente |
| `ajax_yoyaku_register.php` | — | `.reservations.store/.update` (JSON) | idem (llamada directa al servicio, sin HTTP a sí mismo) | idem | pendiente |
| `ajax_yoyaku_menu.php`, `ajax_menu.php`, `ajax_staff.php`, `ajax_search_kaiin.php`, `ajax_yoyaku_koumoku_category.php` | — | `admin.reservas.options.*`, `admin.clientes.lookup` | `Re\OptionController` | — | pendiente (JSON) |
| `ajax_free_yoyaku_rank.php` | — | `admin.reservas.free.ranks` | `Re\FreeReservationController@ranks` | `free_reservation_ranks` | pendiente |
| `ajax_getReservationData.php` | — | — | `PosGateway` | — | descartado: datos para el POS (fuera de alcance) |
| `yoyaku_new.php`, `yoyaku_new_select_staff.php`, `yoyaku_new_select_menu.php`, `yoyaku_new_customer_select.php`, `yoyaku_new_customer_form.php`, `yoyaku_new_register.php`, `yoyaku_new_complete.php` | (flujo `yoyaku_new_*`) | — | — | — | descartado: roto en master (columna y archivo inexistentes); se usa el flujo `yoyaku.php` |
| `yoyaku_uketsuke_day.php`, `yoyaku_uketsuke_register.php`, `yoyaku_uketsuke_delete.php` | 受付日設定 / 予約受付削除 | `admin.reservas.slots.*` | `Re\ReservationSlotController` | `reservation_slots` | pendiente |
| `yoyaku_multi_uketsuke_day.php`, `yoyaku_multi_uketsuke_register.php` | 受付日設定 (varios días) | `admin.reservas.slots.bulk`, `.bulk.store` | `Re\ReservationSlotController@bulkCreate/bulkStore` | `reservation_slots` | pendiente |
| `yoyaku_koumoku_new.php`, `yoyaku_koumoku_register.php`, `ajax_yoyaku_koumoku_new.php` | 予約項目管理 | `admin.reservas.menus.*` (+ `.sort`) | `Re\ReservationMenuController` | `reservation_menus` | pendiente |
| `yoyaku_koumoku_category.php`, `_register.php` | 予約項目カテゴリ管理 | `admin.reservas.menu-categories.*` | `Re\ReservationMenuCategoryController` | `reservation_menu_categories` | pendiente |
| `setsubi_new.php`, `setsubi_register.php` | 設備管理 | `admin.reservas.facilities.*` | `Re\FacilityController` | `facilities` | pendiente |
| `staff_works.php` | スタッフ施術管理 (スタッフ割当) | `admin.reservas.staff-menus.edit/.update` | `Re\StaffMenuController` | `reservation_menu_staff` | pendiente |
| `free_yoyaku.php`, `free_yoyaku_register.php` | フリー予約設定 | `admin.reservas.free.edit/.update` | `Re\FreeReservationController` | `reservation_shop_settings`, `free_reservation_ranks` | pendiente |
| `yoyaku_config.php`, `ajax_yoyaku_config.php` | 予約設定 (各店舗設定) | `admin.reservas.settings.shop` | `Re\ReservationSettingController@shop/updateShop` | `reservation_shop_settings` | pendiente |
| `yoyaku_form_config.php` | 予約設定 (フォーム) | `admin.reservas.settings.form` | `Re\ReservationSettingController@form/updateForm` | `reservation_settings.form_fields` (`yoyaku`) | pendiente |
| `yoyaku_kinou_config.php` | 予約設定 (機能) | `admin.reservas.settings.features` | `Re\ReservationSettingController@features/updateFeatures` | `reservation_settings` | pendiente |
| `yoyaku_comment_config.php` | 予約設定 (注釈) | `admin.reservas.settings.comment` | `Re\ReservationSettingController@comment/updateComment` | `reservation_settings.comment` | pendiente |
| `inc/yoyaku_control.php`, `inc/yoyaku_navi.php` | — | — | layout `reservas` (navegación propia) | — | descartado como vista: parcial Blade de navegación |
| `view_shop.php` | 予約一覧 (店舗毎予約数) | `admin.reservas.by-shop` | `Re\ReservationListController@byShop` | `reservations` | pendiente |
| `yoyaku_list_calendar.php` | 予約一覧 | `admin.reservas.calendar` | `Re\ReservationListController@calendar` | `reservations` (incluye las de category3, `channel = 300`); `business_days` (S5) | pendiente |
| `yoyaku_list_shop.php`, `yoyaku_list_time_table.php`, `yoyaku_revice.php` | (一覧) | `admin.reservas.by-shop`, `.timetable` | idem | — | descartado: variantes que solo leen `tb_base_system`; las cubren las dos listas anteriores |
| `pop_calendar.php` | 年間カレンダー | — | — | — | descartado: selector de fecha nativo / componente |
| `print/yoyaku_list_print.php` | 予約一覧出力 | `admin.reservas.print` | `Re\ReservationListController@print` | `reservations` | pendiente (HTML imprimible) |
| `print/csv_download.php`, `csv_file_out.php`, `csv_file_del.php` | (CSV 予約) | `admin.reservas.csv` | `Re\ReservationListController@csv` | `reservations` | pendiente (StreamedResponse Shift_JIS) |
| `yoyakuPrint.php`, `ajaxYoyakuPrint.php` | 予約プリント | `admin.reservas.printer` + `reservas.printer.poll` (token de impresora) | `Re\PrinterController@show/poll` | `reservations.print_flg` | pendiente (no probado en legacy; mantener sondeo con token) |
| `related_customer.php`, `ajax_related_customer.php` | 顧客関連づけ | `admin.reservas.reservations.customer` | `Re\ReservationController@linkCustomer` | `reservations.customer_id` | pendiente |

### 1.3 bis 予約管理 — `category3/reservation/` (pasa de S3 a S2, decisión de S0)

Reserva simple (`tb_reserve`) por categoría de la tienda, sin franjas ni menús. Comparte `reservations` con category9 (`channel = 300`); ver §2.4.

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `reserve_customer_search.php` | 予約新規登録 / 顧客一覧・検索 | `admin.reservas.simple.create` (paso 1) | `Re\SimpleReservationController@create` + `CustomerSearch` | `customers` | pendiente |
| `reserve_new.php`, `reserve_new_confirm.php`, `reserve_new_register.php`, `reserve_new_complete.php` | 予約新規登録 / 確認 / 完了 | `admin.reservas.simple.create/.store` | `Re\SimpleReservationController@create/store` | `reservations` (`channel = 300`) | pendiente (la confirmación pasa a validación + pantalla de completado) |
| `reserve_calendar.php` | 予約・来店一覧 | `admin.reservas.calendar` | `Re\ReservationListController@calendar` | `reservations`, `business_days` (S5) | pendiente (misma vista que `yoyaku_list_calendar`) |
| `reserve_list.php` | 予約一覧 | `admin.reservas.list` | `Re\ReservationListController@index` | `reservations` | pendiente (filtro por canal) |
| `reserve_detail.php` | 予約詳細 | `admin.reservas.simple.show` | `Re\SimpleReservationController@show` | `reservations` | pendiente |
| `reserve_change_status.php` | 予約情報 (状態変更) | `admin.reservas.simple.state` | `@updateState` (キャンセル 9, 確定 2, 来店 → `visited_flg`) | `reservations`, `reservation_state_logs` | pendiente |
| `reserve_delete.php` | 予約情報 (削除) | `admin.reservas.simple.destroy` | `@destroy` | `reservations` | pendiente |
| `category_entry.php`, `category_register.php`, `category_delete.php` | 予約カテゴリ登録・変更 | `admin.reservas.simple-categories.*` | `Re\SimpleCategoryController` | `reservation_menu_categories` (`shop_id`) | pendiente |
| `config.php`, `config_register.php` | 予約項目設定 | `admin.reservas.settings.form` (`?form=reserve`) | `Re\ReservationSettingController@form/updateForm` | `reservation_settings.form_fields` (`reserve`) | pendiente |
| `ajax_category.php`, `ajax_staff.php`, `inc/check_select.php` | (AJAX) | `admin.reservas.options.*` | `Re\OptionController` | — | pendiente (JSON) |
| `model_reservation.php` | 予約機種一覧 | — | — | — | descartado: lista `tb_change_model` (reservas de cambio de modelo de móvil), que ningún código del repositorio escribe (su origen era `hp/` o externo); se reabre si Japón confirma uso |

### 1.4 Reserva web pública `r/` y `reserve/` (módulo `reservas`, sin `auth:admin`)

**Diferencias** (comparado archivo a archivo): `r/` es la versión **CRM** (lee `tb_yoyaku*` en la BD del CRM). `reserve/` es la versión **POS**: abre `$posConnect` por tienda (`Util::getPosConnection`), lee menús y categorías de `tb_c2_catalog`/`tb_c2_category` del servidor POS y responde `DBサーバーに接続できません。` si no hay POS; además valida con `cYoyaku::yoyakuFormKoumokuCheck()` y `admin_master`. `r/` tiene además `getReservationWaitTime.php`. **Decisión:** se migra el contrato de `r/`; `reserve/*` es un alias de las mismas rutas (mismas claves JSON). La rama POS de `reserve/` queda tras `PosGateway` sin implementar (POS fuera de alcance).

Rutas: `routes/publico/reservas.php` (convención nueva): URL literales `/r/*.php` y `/reserve/*.php`, sin `auth:admin` ni CSRF, nombres `publico.reservas.*`. El control de `sess` sustituye al CSRF como en el legacy; `throttle` en los POST.

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `r/index.php` (+ `reserve/index.php`) | Reserve | `GET /r/index.php`, `/reserve/index.php` · `publico.reservas.index` | `Re\PublicReservationController@index` | `reservation_menus`, `reservation_settings` | pendiente |
| `r/check.php` (+ `reserve/`) | Reserve (確認) | `POST /r/check.php` · `publico.reservas.check` | `@check` | — | pendiente |
| `r/ajax_getScheduleData.php` (+ `reserve/`) | — | `GET /r/ajax_getScheduleData.php` | `@schedule` + `AvailabilityService` | `reservation_shop_settings`, `business_days` (S5) | pendiente (JSON idéntico) |
| `r/ajax_getChoiceData.php` (+ `reserve/`) | — | `GET /r/ajax_getChoiceData.php` | `@choices` + `AvailabilityService` | `reservation_slots`, `reservations` | pendiente |
| `r/ajax_reserveRegister.php` (+ `reserve/`) | — | `POST /r/ajax_reserveRegister.php` | `@register` + `ReservationService` (bloqueo por franja) + Mailable | `reservations`, `reservation_items` | pendiente (textos de error idénticos) |
| `r/ajax_reserveDelete.php` (+ `reserve/`) | — | `POST /r/ajax_reserveDelete.php` | `@cancel` | `reservations` | pendiente (rama `tb_c2_*` de `reserve/` descartada) |
| `r/getReservationWaitTime.php` | — | `GET /r/getReservationWaitTime.php` | `@waitTime` + `ReservationService::waitTime` | `reservations`, `reservation_settings` | pendiente |
| `r/class/Reserve.php`, `Timetable.php`, `reserve/class/Util.php`, `reserve/memo.md`, `css/js/img/plugin` | — | — | `ReservationService`, `AvailabilityService`, `TimetableService` | — | descartado como vista: clases de apoyo y estáticos (Vite) |

> `appview/reservation.php` y `appview/quick_reservation*.php` son de S5 (appview); usan `ReservationService` de S2 (ver §5).

---

## 2. Esquema

Reglas: `ESTANDARES_BD.md` (inglés plural, `text`, sin booleanos, `status`, timestamps, SoftDeletes, FK `bigint`). Lo existente se reutiliza; las columnas nuevas en tablas existentes van en migraciones nuevas `AAAA_MM_DD_HHMMSS_clientes_<accion>.php` (nunca se edita una migración ya publicada).

### 2.1 Tablas que ya existen (S2) y cambios

| Tabla | Cambio | Motivo |
|---|---|---|
| `customers` | + `staff_id` (FK `staff`, null) | 担当スタッフ (falta en el CRMSystem) |
| `customers` | + `felica_no` text null (índice) | IDm de la tarjeta FeliCa (`tb_customer.felica_no` / `tb_customer_card.tid`); lo escribe y lo pone a null S4 con `CustomerFelicaService::attach/detach` de S2 (pedido S4) |
| `customers` | + `paid_member_flg` smallint default 0 (0 no, 1 sí) | 有料会員 (pestaña 14, se sincroniza con MobileShop) |
| `customers` | + `line_id` text null, `line_blocked_flg` smallint default 0 | LINE (legacy `line_id`, `line_blocked_flg`); los usa S3 |
| `customers`, `visits` | `id = cd` del legacy | CONVENCIONES §4.1 (salen en contratos externos) |
| `visits` | + `staff_id`, `terminal_id` (null), `manual_flg` smallint (0 lector/terminal, 1 manual), `customer_age` smallint, `customer_sex` smallint, `customer_occupation` smallint, `business_date` date | edad/sexo/ocupación al momento de la visita (`tb_raiten_rireki.customer_age/sex/job`, pedido S3) y día de negocio con corte a las 5:00 |
| `custom_values` | + `visit_id` (FK `visits`, null) | sustituye a `tb_related_features` para campos ligados a una visita |

Tablas de otros dueños que S2 **lee**: `shops`, `terminals`, `staff` (S1); `customer_groups`, `customer_points`, `customer_stamps`, `coupon_customer`, `point_settings` (S3); `business_days` (S5).

### 2.2 Tablas nuevas — clientes

| Tabla (modelo) | Columnas | Desde |
|---|---|---|
| `kartes` (`Karte`) | `id`, `uid` único, `customer_id`, `shop_id`, `staff_id` null, `service_category_id` null, `record_no` integer, `treated_on` date, `content` text, `note` text, `status`, timestamps, softDeletes | `tb_karute` |
| `service_categories` (`ServiceCategory`) | `id`, `shop_id`, `name`, `sort`, `status`, timestamps, softDeletes | `tb_sejutsu_cate` |
| `customer_photos` (`CustomerPhoto`) | `id`, `uid` único, `customer_id`, `comment`, `shown_on` date (`disp_date`), `images` json (rutas de Storage; una entrada del álbum tiene varias fotos, `pic_flg`), `status` (`hyouji_flg`: visible en Mi página), timestamps, softDeletes | `tb_photo_album` |
| `customer_reader_settings` (`CustomerReaderSetting`, fila única) | `id`, `mode` smallint (`tb_felica_mode.mode`: 0 運用, 1/2 登録, 9 alta **(?)**), `entry_message`, `exit_message`, `status`, timestamps, softDeletes | `tb_felica_mode` fila `cd = 2` (category1) |
| `customer_entries` (`CustomerEntry`) | `id`, `customer_id`, `shop_id`, `terminal_id` null, `entered_at` timestamp, `exited_at` timestamp null, `status`, timestamps, softDeletes | `tb_play_time` |
| `customer_imports` (`CustomerImport`) | `id`, `shop_id`, `admin_id`, `path`, `total_rows`, `imported_rows`, `errors` json, `state` smallint (1 en cola, 2 procesando, 3 terminado, 4 error), `status`, timestamps, softDeletes | (nuevo: seguimiento de la cola de importación) |
| `report_links` (`ReportLink`) | `id`, `name`, `route` text, `sort`, `status`, timestamps, softDeletes | `tb_shukei_link` |

### 2.3 Tablas nuevas — ventas

| Tabla (modelo) | Columnas | Desde |
|---|---|---|
| `products` (`Product`) | estándar (`name`, `name_kana`, `price`, `sub_price`, `tax_id` null, `code`, `colors` json, `sort`, `uid`) + `display_name`, `title`, `description` text (HTML saneado), `detail` text (HTML saneado), `exchange_flg` smallint (0 no, 1 canjeable por puntos), `exchange_points` integer null, `image_path` text null, `status` (表示/非表示; `disp_flg = 0` → `deleted_at`), timestamps, softDeletes | `sales_items` |
| `product_shop` | `id`, `product_id`, `shop_id`, timestamps | `sales_items.available_shop_cds` |
| `service_menus` (`ServiceMenu`) | `id`, `shop_id`, `name`, `price` integer, `type` smallint (1 技術, 2 物販, 3 その他), `sort`, `status`, timestamps, softDeletes | `tb_karute_menu` |
| `discounts` (`Discount`) | `id`, `shop_id`, `name`, `type` smallint (1 porcentaje, 2 importe fijo, como `coupons`), `value` integer, `commission_flg` smallint (1 cuenta para la comisión, 0 no; `buai_flg`), `sort`, `status`, timestamps, softDeletes | `tb_karute_waribiki` |
| `sales` (`Sale`) | `id`, `uid` único, `shop_id`, `customer_id`, `staff_id` null, `sold_on` date, `invoice_no` integer (nº de cuenta por cliente), `subtotal`, `discount_id` null, `discount_rate` numeric(5,2) null, `discount_amount`, `total`, `commission`, `note`, `status`, timestamps, softDeletes | `tb_customer_sub` con `tb_search_cate_cd = -1` |
| `sale_lines` (`SaleLine`) | `id`, `sale_id`, `service_menu_id` null, `name`, `type` (copia de `service_menus.type`), `price`, `quantity` integer default 1, `sort`, timestamps, softDeletes, `status` | ídem (una fila por menú) |
| `expenses` (`Expense`) | `id`, `shop_id`, `staff_id` null, `spent_on` date, `name`, `amount` integer, `note`, `status`, timestamps, softDeletes | `tb_customer_sub` con `tb_search_cate_cd = -2` |

Dinero en `integer` (yen). Comisión: `commission = (int)(importe base de comisión × staff.commission_rate / 100)`, con el descuento restado de la base solo si `discounts.commission_flg = 1` (regla exacta de `karute_new_frm.php` a confirmar en V1); `kingaku_buai` (tasa del personal) es columna de `staff` (pedido S1).

### 2.4 Tablas nuevas — reservas (modelo `Reservation` publicado para S3)

Un solo modelo de reservas para las tres fuentes legacy: `category9/yoyaku` (`tb_yoyaku`, reserva sobre una franja de un empleado), `r/`·`reserve/` (escriben `tb_yoyaku` con `entry_type` 100/101) y `category3/reservation` (`tb_reserve`, reserva simple por categoría de la tienda, con estados 受付/確定/キャンセル y 来店). Verificado en el código legacy: el **estado de atención** (受付中, 電話受付, 締切間近, 受付終了) es de la franja, no de la reserva; `tb_yoyaku` no tiene estado propio (cancelar = `disp_flg = 9`); `tb_reserve` sí (`reserve_flg`, `raiten_flg`).

```
reservations (Reservation)                       ← tb_yoyaku, tb_reserve
  id                 bigserial (legacy_id_map: dos tablas de origen)
  uid                text único (enlaces de cliente)
  shop_id            FK shops            (tb_yoyaku: vía tb_staff.shop_cd de la franja; tb_reserve.shop_cd)
  staff_id           FK staff, null      (null = フリー予約 / sin nominación)
  customer_id        FK customers, null  (no socio; se enlaza luego: 顧客関連づけ)
  reservation_slot_id FK reservation_slots, null (solo category9 / r/)
  reservation_menu_category_id FK, null  (category3: tb_reserve.reserve_cate)
  reserved_at        timestamp Asia/Tokyo (inicio)  ← moushikomi_date + start_time (seg.) · tb_reserve.reserve_time
  ends_at            timestamp null                 ← moushikomi_date + end_time (puede pasar de 24 h)
  people             smallint default 1
  last_name, first_name, last_name_kana, first_name_kana   ← name/name_kana partidos por el primer espacio (normal o ancho)
  tel, mail          text
  mark               text null           (tb_yoyaku.mark)
  form_values        jsonb               (free_koumoku_1..5)
  comment            text null
  channel            smallint  ReservationChannel = entry_type legacy tal cual:
                     0 管理画面, 1 フォーム, 2 web timeline, 3 web PC, 4 web POS, 5 フリー予約, 6 フリー予約 POS,
                     100 r/ normal, 101 r/ フリー, 201 クイック予約 (appview), 300 category3 簡易予約
  state              smallint  ReservationState: 1 受付 (pendiente de confirmar), 2 確定 (confirmada), 9 キャンセル
                     (tb_yoyaku → 2, o 9 si disp_flg = 9; tb_reserve.reserve_flg '' → 1, '2' → 2, '9' → 9)
  visited_flg        smallint default 0  (tb_reserve.raiten_flg = '1'; category9 lo deduce de visits)
  print_flg          smallint 0 no, 1 pendiente de imprimir, 2 impreso
  status (hyouji_flg), timestamps, softDeletes (disp_flg = 9 → state 9 + deleted_at)
  índices: (shop_id, reserved_at), (staff_id, reserved_at), (customer_id, reserved_at), (state, reserved_at)

reservation_items (ReservationItem)              ← tb_yoyaku.base_tb_yoyaku_koumoku_master_cd / option_…_cd (lista)
  id, reservation_id, reservation_menu_id null, type smallint (1 base, 2 opción),
  name, price integer, minutes integer, sort, status, timestamps, softDeletes  (copia del menú al reservar)

reservation_slots (ReservationSlot)              ← tb_yoyaku_uketsuke_day (turno de un empleado por día)
  id, shop_id (de tb_staff.shop_cd), staff_id, date (moushikomi_date 'Y/m/d'),
  start_seconds / end_seconds integer (segundos desde 00:00, como el legacy: pueden pasar de 86400),
  break_start_seconds / break_end_seconds integer null,
  capacity smallint (people), accept_state smallint SlotAcceptState: 0 受付中, 1 電話受付, 2 受付終了, 3 締切間近 (status_flg),
  status (hyouji_flg), timestamps, softDeletes

reservation_menus (ReservationMenu)              ← tb_yoyaku_koumoku_master
  id, shop_id, reservation_menu_category_id null, facility_id null (tb_setsubi_cd),
  name, description (naiyou), type smallint (1 base, 2 opción), minutes (shoyou_time), price integer null,
  last_order_flg smallint + last_order_seconds integer null (menu_end_time_flg / menu_end_time),
  sort (rank), status (hyouji_flg), timestamps, softDeletes

reservation_menu_categories (ReservationMenuCategory) ← tb_yoyaku_koumoku_category (global) y tb_reserve_name (por tienda)
  id, shop_id null (null = global de category9; con valor = 予約カテゴリ de category3), name, sort, status, timestamps, softDeletes

reservation_menu_staff                           ← tb_staff_works (pivote con datos; se reescribe entera al guardar)
  id, reservation_menu_id, staff_id, minutes integer null (shoyou_time propio),
  mypage_hidden_flg smallint default 0 (mypage_flg '1' = no se muestra en Mi página), timestamps

facilities (Facility)                            ← tb_setsubi
  id, shop_id, name, capacity smallint (quantity), status, timestamps, softDeletes

free_reservation_ranks (FreeReservationRank)     ← tb_free_yoyaku_rank
  id, shop_id, staff_id, sort (rank), status, timestamps, softDeletes

reservation_settings (ReservationSetting) — fila única (CONVENCIONES §4)
  ← admin_master.yoyaku_time_interval, meyasu_jikan_flg, yoyaku_setsubi_flg, free_yoyaku_flg,
    tb_manage_words (comentarios de yoyaku_comment_config), tb_koumoku (tb_name 'tb_yoyaku' y 'tb_reserve': formularios)
  id, interval_minutes smallint, estimated_time_flg smallint (meyasu: 1 muestra, 0 oculta),
  facility_control_flg smallint, free_reservation_flg smallint, comments json (clave → texto),
  form_fields json ({"yoyaku": {campo: {display, required}}, "reserve": {…}}), status, timestamps, softDeletes

reservation_shop_settings (ReservationShopSetting) — una fila por tienda
  ← tb_yoyaku_config + contents_master (category 'tb_c9_yoyaku', int_data = tienda)
  id, shop_id único, opens_at time / closes_at time (start_time/end_time 'HH:MM'),
  deadline_days smallint (shimekiri 0..30), deadline_hours smallint (shimekiri_time),
  form_accept_type smallint (uketuke_type: 0 todas las horas, 1 no acepta por formulario en horario de apertura),
  timeline_sort_type smallint (1 personal con turno arriba), facility_capacity smallint null, hide_price_flg smallint,
  free_display_flg, free_name text, free_staff_select_flg, free_timeline_staff_flg (contents_master free_yoyaku_*),
  status, timestamps, softDeletes
  (automail/automail_time: ocultos desde 2013 → no se migran; el recordatorio lo hace S3)

reservation_state_logs (ReservationStateLog)     ← Log de ajax_uketsuke_status / reserve_change_status
  id, loggable_type/loggable_id (franja o reserva), admin_id null, from_state, to_state, timestamps

numbered_tickets (NumberedTicket)                ← numbered_ticket (クイック予約; S5 llama a ReservationService)
  id, shop_id, reservation_id null, issued_at timestamp (issued_date), number integer (issued_number, diario),
  call_state smallint (called_flg: 0 terminado, 1 en espera, 2 llamado, 3 en atención), called_at timestamp null,
  adults smallint, children smallint, smoking text null, status, timestamps, softDeletes
```

Los tiempos de franja y menú se guardan en segundos desde las 00:00, como el legacy, porque una franja puede terminar después de medianoche (`end_time > 86400`) y `r/` devuelve esos valores; el modelo expone accesores Carbon con `Asia/Tokyo`.

**Contrato para S3 (recordatorio `trigger` 8)** — se publica en la tarea R1, la primera de reservas:
- `App\Models\Reservation`: `customer_id`, `shop_id`, `staff_id`, `reserved_at`, `ends_at`, `state` (`App\Enums\Reservations\ReservationState`), relaciones `customer()`, `shop()`, `staff()`, `items()`.
- Scope `Reservation::remindable(CarbonInterface $from, CarbonInterface $to)`: `state` 1 o 2, `reserved_at` en el rango, sin `deleted_at`, `customer_id` no nulo y `customers.reservation_reminder_flg = 1` (legacy `stop_yoyaku_reminder_mail_flg` invertido).
- Accesores `display_name` y `contact_mail` (de la reserva o, si falta, del cliente).

**`category3/reservation` frente a category9 y `r/`:** en el legacy son dos sistemas independientes (tablas distintas, sin cruce). En Laravel comparten `reservations`: las pantallas de category3 son el flujo simple (sin franjas ni menús; categoría, nominación, personas, 来店) y crean `channel = 300`; sus listas muestran todas las reservas de la tienda con filtro por canal. Su 予約カテゴリ vive en `reservation_menu_categories` con `shop_id`.

### 2.5 Mapeo `tb_*` → tablas nuevas (para el importador de S1)

Reglas generales (CONVENCIONES §4): `id = cd` en `customers`, `visits` y `products`; el resto por `legacy_id_map` de S1 (sin columna `legacy_cd`); `tb_X_cd` → `X_id`; `disp_flg = 9` (y `0` en `sales_items`) → `deleted_at = update_date`; `insert_date`/`touroku_date` → `created_at`, `update_date` → `updated_at`; textos con `’` doble escapado → normalizar a `'`; fechas de 1970 o vacías → `null`; `hatuban` no se migra (secuencias reajustadas tras importar). El detalle columna a columna está en el **Anexo A**.

---

## 3. Servicios viejos → Laravel 13

| Legacy | Dónde | Reemplazo |
|---|---|---|
| `hatuban` + `SELECT … FOR UPDATE` | casi todos | `bigserial`; números visibles (`record_no`, `invoice_no`) con `max()+1` dentro de `DB::transaction` + `lockForUpdate` |
| `createID()` (`login_id` de socio) | alta, CSV import | `CustomerNumberGenerator` (mismo formato por tienda) |
| `randomPass(4)` y `login_pass` en claro | alta, CSV import | `Hash::make`; contraseña inicial por enlace firmado de restablecimiento (Mi página, S4) |
| Validación JS + `get_form()` | alta, formularios | Form Requests; normalización (`mb_convert_kana`, solo dígitos) en `prepareForValidation` |
| Token `add_record_verify` / `register_verify` en sesión | ventas, reservas | CSRF + redirect-after-post; en reservas, bloqueo `lockForUpdate` por franja |
| CSV con Flash y temporales en `uploads/` | `customer/`, `print/` | `StreamedResponse` (`streamDownload`) + `mb_convert_encoding('SJIS-win')`, `lazy()` por bloques |
| `system("find … rm")` | `category1/index.php` | `Schedule::command('model:prune')`/limpieza de `Storage` registrada en `ClientesServiceProvider` |
| `system()`/Imagick en subidas (`ajax_picupload`, `item/edit`) | fotos, productos | `Storage::disk('public')` + `intervention/image` (pedido S1) |
| CKEditor 4 | productos | editor que defina S1 (PENDING 3.1) + `mews/purifier` |
| `crontab` vía `cSendSchedule` (rangos) | `rank/` | `customers:assign-ranks` en el Scheduler, leído de `rank_schedules` |
| Recalculo de `shuki_heikin`/`last_raiten_date` | visitas | `VisitService` (transacción) + `customers:refresh-visit-stats` para reparar |
| `tb_related_features` | corrección de visitas | `visit_id` en `customer_points`, `customer_stamps` (S3) y `custom_values` |
| `crm_send_mail` / `cYoyaku::sendYoyakuMail` | reservas | Mailables `ReservationConfirmed`, `ReservationCanceled`, `ReservationReceivedNotice` en cola (`QUEUE_CONNECTION=database`) |
| Enlace `md5(login_id)`/`md5(login_pass)` en el correo | reservas | `URL::temporarySignedRoute` hacia Mi página |
| `mktime(…, is_dst)` / calendarios 1970 | 予約, 入退室 | Carbon con `Asia/Tokyo` explícito |
| `file_get_contents` a sí mismo (`ajax_yoyaku_register` → `ajax_move_objs`) | reservas | llamada directa a `ReservationService::move` |
| `LOCK TABLE tb_yoyaku` (`r/`) | reserva web | `DB::transaction` + `lockForUpdate` sobre las reservas del rango |
| Popups de 600 px y `parent.location.reload()` | 予約, ficha | páginas completas o modales Blade |
| Impresora por sondeo (`ajaxYoyakuPrint`) | 予約プリント | endpoint JSON con token por impresora (`throttle`), marca `print_flg = 2` |
| `$posConnect` / `*.pos-s.net` (`ajax_connectPos*`, `ajax_getReservationData`, `reserve/`) | ficha, reservas | interfaz `PosGateway` **sin implementación** (POS fuera de alcance): `purchaseHistory(Customer)`, `sendPurchaseMemo(Customer, string)`, `reservationCatalog(Shop)`; binding nulo que devuelve vacío |
| API MobileShop (`CustomerForMobileShop`) | 有料会員 | `MobileShopClient` de S5 (`Http::` + `.env`); S2 lo invoca en un Job `SyncPaidMemberStatus` |
| Permisos `myKengen[1001]`, `[1033]`, pestañas | todo | Policies `CustomerPolicy`, `SalePolicy`, `ReservationPolicy` sobre los Gates de S1 |
| `customer_export.php` (`COPY TO`) | — | no se migra |
| `cUtility::raitenFunc` (visita + intervalo + sellos + puntos) | 来店処理, `tool/api`, `mpa_setraiten`, CTI (S5) | `VisitService::register(Customer $customer, Shop $shop, VisitOrigin $origin)`: intervalo mínimo (`shops.visit_interval_seconds`), día de negocio, edad/sexo/ocupación, sellos y puntos vía `LoyaltyService` (S3) |
| Alta de socio de `appview/member_*` | S5 | `CustomerRegistrationService::register(array $data, Shop $shop, origin)` (número, contraseña hash, campos personalizados, puntos de alta vía S3) |
| Búsquedas `ajax_search_kaiin`, CTI por teléfono | S2, S3, S5 | `CustomerSearch` (estable: filtros estructurados, `byPhone(string)` normaliza a dígitos y busca en `tel1`/`tel2`/`tel3`) |
| Fotos del cliente (`mpa_albumUpdate`) | S5 | `CustomerPhotoService::store(Customer, array $files, ?string $comment)` |

---

## 4. Orden de implementación (1 punto ≈ media jornada)

| # | Tarea | Contenido | Depende de | Puntos |
|---|---|---|---|---|
| C0 | Mudanza a convenciones | `routes/modulos/clientes.php`, vistas `admin/clientes`, `lang/ja/clientes.php`, `config/menu/clientes.php`, nombres `admin.clientes.*`; pruebas existentes en verde | núcleo S1 (carga de `routes/modulos`, menú, layout) | 1 |
| C1 | Ficha completa del cliente | edición, borrado lógico, recuperación, `staff_id`, `CustomerPolicy`, contador de altas, combos; `CustomerSearch::byPhone`, `CustomerRegistrationService`, `VisitService::register` publicados para S5 | `staff` y Gates (S1) | 4 |
| C2 | Configuración | grupos, motivos, rangos (CRUD + ▲ + マイページ), guardar `field_settings`, comando `customers:assign-ranks` | — | 4 |
| C3 | Visitas, puntos y sellos | `VisitService` (alta, corrección, borrado en transacción), pestañas 4/8/9/10, `point-history` (+CSV), registro de puntos y sellos | `LoyaltyService` y `visit_id` (S3) | 7 |
| C4 | Información adicional múltiple, カルテ, 施術カテゴリー, álbum | `custom-records`, `kartes`, `service_categories`, `customer_photos` (Storage), pestañas 5/6/7/13/custom | intervention/image (S1) | 4 |
| C5 | CSV e importación | Shift_JIS, 3 formatos, 郵便CSV, importación por cola | — | 4 |
| C6 | Agregaciones | 集計ツール, model1/2, point1/2, stamp1, `customerDataOutPut`, cortes 業種/prefectura | C3 | 5 |
| C7 | Tarjetas y 入退室 | `felica_no` + `CustomerFelicaService`, `customer_reader_settings`, `customer_entries` (calendario y lista), 有料会員 | lecturas por API de terminal (S4/S5), `MobileShopClient` (S5) | 4 |
| V1 | Ventas internas | menús, descuentos, ventas (comisión, nº de cuenta), gastos, pestaña 11 | `staff` + `commission_rate` (S1) | 5 |
| V2 | Informes de ventas | スタッフ別集計, 日報・週報・月報 (+CSV) | V1 | 2 |
| V3 | Productos (category2) | `products`, `product_shop`, imagen, editor | editor y purifier (S1), `taxes` (S1) | 3 |
| R1 | Modelo de reservas + configuración | migraciones de §2.4, `Reservation` + enum (**publicar a S3**), 予約設定 (4 pantallas), 予約項目, カテゴリ, 設備, スタッフ割当, フリー予約 | `staff` (S1) | 6 |
| R2 | 受付日 y disponibilidad | `reservation_slots` (uno y varios días), `AvailabilityService` | R1; `business_days` (S5) | 4 |
| R3 | Flujo de reserva + correo | `yoyaku` 3 pasos, `ReservationService` (también para クイック予約 de S5), Mailables en cola, 顧客関連づけ, pestaña 12; reserva simple de category3 (alta, detalle, estado, categorías) | R2 | 7 |
| R4 | 予約表 | día/semana/mes, por empleado, mover/arrastrar, estado, listas laterales | R3 | 7 |
| R5 | Listados, impresión y CSV | 店舗毎予約数, 予約一覧 (calendario, lista; incluye category3), impresión, CSV, impresora | R3; `business_days` (S5) | 4 |
| R6 | Reserva web `r/` + alias `reserve/` | 7 endpoints con JSON idéntico, `waitTime`, pruebas de contrato | R3; grupo público (S1) | 5 |
| | **Total** | | | **76** |

Orden propuesto: C0 → R1 (desbloquea a S3) → C1 → C2 → C3 → R2 → R3 → V1 → C4 → R4 → R6 → C5 → V2 → V3 → R5 → C6 → C7. Mientras S0 revisa: factories y seeders del grupo.

---

## 5. Pedidos

**A S1 (núcleo):**
1. Tabla/modelo `Staff` (tabla `staff`, FK `staff_id`) con `shop_id`, nombre estándar, `commission_rate` numeric(5,2) (`tb_staff.kingaku_buai`), con `id = cd` (CONVENCIONES §4). S2 la necesita para C1, V1 y R1.
2. Gates/abilities por módulo: `clientes.view`, `clientes.manage`, `clientes.tab.<pestaña>` (equivalente a `myKengen[1001]` y permisos por pestaña), `ventas.view`, `ventas.delete` (`[1033]`), `reservas.view`, `reservas.manage`.
3. Cargar `routes/modulos/*.php` y `routes/publico/reservas.php`, y quitar de `routes/web.php` el grupo `customers` cuando exista `routes/modulos/clientes.php` (C0).
5. Tabla estándar `taxes` (si S1 la publica; si no, `products.tax_id` queda null).
6. Paquetes: `intervention/image`, `mews/purifier`, y la decisión del editor enriquecido (PENDING 3.1).
7. Interfaz común `App\Contracts\PosGateway` (o la ubicación que elija S0) con binding nulo; S2 aporta los métodos de §3. Mientras tanto S2 la declara en `app/Services/Customers/PosGateway.php`.
8. Importador: usar el Anexo A (`id = cd` en `customers`, `visits`, `products`; el resto por `legacy_id_map`). Helper común de CSV `SJIS-win` (CONVENCIONES §4); hasta entonces S2 usa uno propio con la misma firma.

**A S3 (promoción):**
1. `LoyaltyService`: alta/anulación de movimientos de puntos y sellos ligados a `visit_id` (para la corrección de visitas) y lectura de saldos.
2. Publicado el contrato de `Reservation` (§2.4: `customer_id`, `shop_id`, `reserved_at`, `state`, scope `remindable()`); el modelo real sale en R1, la primera tarea de reservas. `tb_reserve` (category3/reservation) pasa a S2 (decisión de S0).
4. Edad, sexo y ocupación en `visits` al momento de la visita: incluidos (§2.1). `CustomerSearch` se mantiene estable (firma pública documentada en C1). La ruta de la ficha pasa a ser `admin.clientes.show` (CONVENCIONES: `admin.<modulo>.*`); `CustomerDevice` sigue siendo de S2 y S3 lo lee.
3. S2 escribe datos de `customer_groups` (pantalla 顧客グループ設定) sin tocar el modelo; si S3 prefiere llevar esa pantalla, avisar.

**A S4 (contenido/terminales):** (respuesta a su pedido) `customers.felica_no` añadido; S2 expone `App\Services\Customers\CustomerFelicaService::attach(Customer, string $idm)` y `::detach(Customer)` (pone `felica_no = null`); `felica_cards` y la pantalla カード管理 son de S4 y el UID解除 de la ficha llama a su `FelicaCardService`. **Tarjeta de papel:** el legacy no guarda ninguna referencia en `tb_customer`; la relación cliente ↔ tarjeta de papel solo existe en el registro de accesos `url_transfer_data(access, tb_customer_cd, data_id)` que escribe `ptag/` (el cliente se reconoce por la cookie `UID`). Por eso no se añade `paper_card_id` a `customers`: el 集計 de S4 debe unir su tabla de accesos (`customer_id` FK a `customers`) con `customers`. Las lecturas del lector que crean visitas/entradas llaman a `VisitService` y `CustomerEntry` de S2. `ranks.status` y `customer_photos` los lee Mi página.

**A S5 (operaciones/integraciones):** `business_days` (営業日) es de S5; S2 lo lee en reservas. `MobileShopClient` (有料会員) de S5. Respuesta a sus pedidos: `VisitService::register(customer, shop, origin)`, `CustomerSearch` (incluido `byPhone` para CTI), `CustomerRegistrationService` (alta desde `appview`), `CustomerPhotoService::store`, `ReservationService` + `NumberedTicket` (クイック予約), productos canjeables (`products.exchange_flg`, `exchange_points`, `description`, `product_shop`) y el mapeo inverso `customers` → `tb_customer` (Anexo A.2: `name` = apellido + espacio de medio ancho + nombre; `kojin_birth` = `Y/m/d`, vacío `'//'`).

**A Antony / Japón (no bloquea):** confirmar uso de `customer_level`, `school*`, `mail_comment`, `biyou` (se migra solo 施術カテゴリー y カルテ詳細); límite de 60 días en informes de ventas (se deja sin aplicar, como el legacy); si las ventas viejas de `tb_customer_sub` (-1/-2) se importan a `sales`/`expenses` (propuesta: sí).

---

## Anexo A — Mapeo columna a columna (para el importador de S1)

Fuente: columnas deducidas del código legacy (INSERT/UPDATE/SELECT, migraciones Doctrine y `tb_koumoku` de `db_fix.php`); no se leyó el backup. **(?)** = significado no confirmado.

**Reglas comunes.** `cd`: `id = cd` en `customers`, `visits` y `products` (CONVENCIONES §4.1, salen en contratos externos; el importador ajusta la secuencia con `setval`); en el resto, `legacy_id_map`. `tb_X_cd` → `X_id` resuelto por esa regla. `touroku_date` → `created_at`, `update_date` → `updated_at` (epoch → `timestamp` Asia/Tokyo; 0, vacío o 1970 → `null`). `disp_flg = '9'` (y `0` en `sales_items`) → `deleted_at = updated_at`. `hyouji_flg` → `status` (`'1'` → 1, resto → 0). `rank` (orden) → `sort`. `synchronize_flg`, `local_sync_flg`, `local_dl_sync_flg` (sincronización con POS) → no se migran. `hatuban` → no se migra. Textos con `’` doble escapado → `'`. `sex` `'男性'` → 1, `'女性'` → 2, vacío → 0 (empresas: 9).

### A.1 `tb_customer` → `customers` (+ `customer_companies`, `customer_devices`)

Solo se importan los socios (`touroku_kubun = '2'`); los provisionales (`'0'`/`'1'`) **(?)** se importan con `status = 0` para no perder historial.

| Legacy | Nuevo | Transformación |
|---|---|---|
| `cd` | `customers.id` | `id = cd` |
| `id` (顧客番号) | `code` | tal cual |
| `kanri_id` | `management_no` | |
| `tb_shop_cd` | `shop_id` | |
| `type` `'個人'`/`'法人'` | `type` 1 / 2 | |
| `name`, `name_kana` | `last_name`+`first_name`, `last_name_kana`+`first_name_kana` | partir por el primer espacio (半角 o 全角, `mb_ereg("(.*?)[ 　]+(.*)")` como `customer_new.php`); sin espacio, todo en `last_name` |
| `kojin_birth` (`'Y/m/d'`, vacío `'//'`) | `birth_date` | `checkdate`; inválida → null |
| `kojin_sex` | `sex` | ver reglas comunes |
| `kojin_bloodtype` | `blood_type` | |
| `job` | `occupation` | texto → `App\Enums\Occupation` por etiqueta; sin coincidencia → null y nota |
| `zip` | `zip` | solo dígitos |
| `pref`, `add`, `add_number`, `add_build` | `pref`, `city`, `street_address`, `building` | |
| `add_kana`, `add_build_kana` | `city_kana`, `building_kana` (migración `2026_10_06_120001`) | |
| `tel`, `kojin_mobile`, `fax` | `tel1`, `tel2`, `tel3` | solo dígitos |
| `email1`, `email2`, `kojin_mail` | `mail1`, `mail2`, `mail3` | |
| `mailmaga_flg` `'1'`/`'0'`/`'9'` | `mail_magazine` 1 / 2 / 3 | |
| `mailmaga_email_type` | — | descartado: el envío usa `mail1` (S3 decide si lo necesita) |
| `error_send_cnt` | `bounce_count` | |
| `stop_yoyaku_reminder_mail_flg` `''`/`'1'` | `reservation_reminder_flg` 1 / 0 | invertido |
| `bikou` | `note` | |
| `login_pass` | `password` | `Hash::make(login_pass)` (texto plano en legacy) |
| `login_id` | — | igual a `code` al registrarse; si difiere, se anota en el informe del importador |
| `easy_login`, `mobile_type` | `easy_login`, `device_type` | |
| `last_login_time` | `last_login_at` | epoch |
| `felica_no` | `felica_no` | (pedido S4) |
| `terminal_no` | `terminal_id` | por `terminals` de la tienda **(?)** |
| `tb_customer_group_cd` | `customer_group_id` | |
| `tb_raiten_douki_master_cd` | `visit_motive_id` | |
| `tb_staff_cd` (指名担当者) | `staff_id` | |
| `shoukai_tb_customer_cd` | `referrer_id` | segunda pasada (autorreferencia) |
| `kingaku_rank_now`/`_pre` | `amount_rank_id` / `prev_amount_rank_id` | |
| `raiten_rank_now`/`_pre` | `visit_rank_id` / `prev_visit_rank_id` | |
| `shuki_heikin` | `average_visit_cycle` | días (entero) |
| `last_raiten_date` | `last_visit_date` | epoch → date |
| `paid_member_flg` | `paid_member_flg` | (columna nueva) |
| `line_id`, `line_blocked_flg` | `line_id`, `line_blocked_flg` | columnas nuevas en `customers` (las lee S3) |
| `registration_id` (JSON push), `imei` | `customer_devices` | una fila por token |
| `houjin_*`, `daihyousha_*`, `tantou_*`, `kinmusaki_*` | `customer_companies` | `houjin_setsuritsu` → `founded_on`; `houjin_shihon` → `capital`; `houjin_busho` → `department`; `houjin_gyoushu`/`kinmusaki_gyoushu` → `industry`; `daihyousha_*` → `representative_*`; `tantou_*` → `contact_*`; `kinmusaki_name*`/`_tel`/`_fax` → `name*`/`tel1`/`tel3` |
| `married` | `spouse_flg` | **(?)** valores |
| `touroku_date`, `update_date` | `created_at`, `updated_at` | |
| `disp_flg` `'9'` | `deleted_at` | |
| `service_point`, `point_yukoukigen`, `stamp_count`, `stamp_yukoukigen`, `last_stamp_time`, `tb_stamp_pattern_cd` | — | saldos de puntos y sellos: los importa S3 (`customer_points`, `customer_stamps`) |
| `tb_customer_level_cd` | — | descartado (`customer_level` sin uso) |
| `touroku`, `agency`, `hobby`, `haishin_error`, `login_rank`, `touroku_flg`, `mobile_id`, `mobile_kishu`, `shopping_point`, `kojin_birth_date`, `synchronize_flg`, `local_*`, `sairai_non_flg`, `ec_member_cd`, `family_membership_id`, `visit_time_required`, `family_size`, `register_type`, `year_purchase_rank`, `pos_member_cd`, `paid_member_flg_confirmation_time`, `paid_member_service_start_date`, `app_staff_flg`, `mycolle_touroku_date` | — | no se migran (obsoletos, calculados, POS o MyColle); `kojin_birth_date` se recalcula de `birth_date`. Si Japón usa alguno, se añade columna |

### A.2 Mapeo inverso `customers` → `tb_customer` (para las API de S5)

`LegacyCustomerResource` responde con los nombres legacy:

| Clave legacy | Valor desde Laravel |
|---|---|
| `cd` | `id` |
| `id`, `login_id` | `code` |
| `kanri_id` | `management_no` |
| `name` | `trim(last_name . ' ' . first_name)` — **espacio de medio ancho** (`customer_new_confirm.php`: `name_sei." ".name_mei`) |
| `name_kana` | `trim(last_name_kana . ' ' . first_name_kana)` |
| `kojin_birth` | `birth_date->format('Y/m/d')`; null → `'//'` (lo que guardaba el legacy con la fecha vacía) |
| `kojin_sex` | 1 → `'男性'`, 2 → `'女性'`, resto → `''` |
| `type` | 1 → `'個人'`, 2 → `'法人'` |
| `zip`, `pref` | iguales |
| `add`, `add_number`, `add_build` | `city`, `street_address`, `building` |
| `user_address` (API) | `pref . city . street_address . building` sin separador **(?)** (verificar contra una respuesta real) |
| `tel`, `kojin_mobile`, `fax` | `tel1`, `tel2`, `tel3` |
| `email1`, `email2`, `kojin_mail` | `mail1`, `mail2`, `mail3` |
| `tb_shop_cd` | `shop_id` |
| `touroku_kubun` | `'2'` para todo socio activo |
| `disp_flg` | `deleted_at` null → `'1'`, si no `'9'` |
| `touroku_date`, `update_date` | epoch (`created_at->timestamp`, `updated_at->timestamp`) |

### A.3 Otras tablas de clientes

| Legacy | Nuevo | Notas |
|---|---|---|
| `tb_raiten_rireki`: `cd`, `user_cd`, `shop_cd`, `raiten_time`, `raiten_hiduke`, `customer_age`, `customer_sex`, `customer_job`, `manual_flg`, `disp_flg` | `visits`: `id = cd`, `customer_id`, `shop_id`, `visited_at`, `business_date`, `customer_age`, `customer_sex`, `customer_occupation`, `manual_flg`, `deleted_at` | `raiten_time` epoch (a veces texto); `stamp`, `tb_point_log_cd`, `tb_stamp_log_cd` → `visit_id` en los movimientos de S3; `tb_search_cate_cd` + `tb_customer_sub_inner_group` → `custom_values.visit_id`; `user_id` se descarta; `amount` sale de `tb_point_log.kaikei` del movimiento ligado |
| `tb_related_features` | — | obsoleta (su escritura está comentada); la relación vive en `tb_raiten_rireki` |
| `tb_customer_sub` (`tb_search_cate_cd > 0`) | `customers.custom_data` (categorías de un registro) / `custom_values` (`record_type = '1'`, una fila por `category_inner_group` → `row_no`) | valor: `float_data` (numérico), `text_data` (casillas `'a<>b<>'` → array; fechas `'Y'`, `'Y/m'`, `'Y/m/d'`, `'m/d'`); clave = `slug` del campo |
| `tb_customer_sub` (`tb_search_cate_cd = -1`) | `sales` + `sale_lines` | agrupar por (`tb_customer_cd`, `category_inner_group` → `invoice_no`); el campo de cada fila (`tb_search_form_cd`/`form_title`) dice si es menú, personal, descuento, total o comisión: **se detalla al implementar V1** leyendo `karute_new_register.php` |
| `tb_customer_sub` (`tb_search_cate_cd = -2`) | `expenses` | ídem con `keihi_register.php` |
| `tb_search_cate`: `name`, `record_type`, `disp_column`, `serach_flg`, `hyouji_flg`, `rank`, `category` | `custom_categories`: `name`, `type` (`''` → 1, `'1'` → 2), `columns`, `search_flg`, `status`, `sort` | solo `category = 'category1'`; `slug` generado |
| `tb_search_form`: `name`, `form_type`, `titles`/`data`, `unit`, `style_class`, `comment`, `hissu`, `serach_flg`, `csv_flg`, `hyouji_flg`, `mail_word_flg`, `rank` | `custom_fields`: `name`, `type` (`CustomFieldType` por etiqueta), `custom_field_options` (`titles`↔`data` por `<>`), `unit`, `size` (`FieldSize`), `note`, `required_flg`, `search_flg`, `field_settings.csv_flg`, `display_scope` (`'1'` → 1, `'2'` → 2, `''` → `status = 0`), `mail_magazine_flg`, `sort` | `flds_row`/`flds_col` → `config` |
| `tb_customer_group`: `name`, `default_flg`, `rank` | `customer_groups`: `name`, `default_flg`, `sort` | `keisan_flg`, `shiyou_point_jyougen`, `point_percentage_*` → `customer_group_point_rules` (S3) |
| `tb_raiten_douki_master`: `name`, `rank` | `visit_motives` | |
| `tb_kingaku_rank_master`: `name`, `jyouken1`, `jyouken2`, `rank`, `hyouji_flg` | `ranks` `type = 1`: `name`, `min_value`, `max_value`, `sort`, `status` | `coupon`, `free_area`, `birthday_coupon_flg` (touchcation) → descartados |
| `tb_raiten_rank_master` | `ranks` `type = 2` | ídem |
| `admin_master.kingaku_rank_shori_date/_time/_kikan`, `raiten_…` | `rank_schedules` (`type` 1/2): `period_days`, `run_hour`/`run_minute`, `days`/`weekdays` (día 32 → `"fin"`) | una fila por tienda con los mismos valores **(?)** (el legacy es global) |
| `tb_karute`: `tb_customer_cd`, `tb_shop_cd`, `tb_staff_cd`, `tb_sejutsu_cate_cd`, `comment`, `uketsuke_date` | `kartes`: `customer_id`, `shop_id`, `staff_id`, `service_category_id`, `content`, `treated_on` | `record_no` = orden por cliente; las fotos de `uploads/` → Storage |
| `tb_sejutsu_cate`: `tb_shop_cd` (null = todas), `name` | `service_categories`: `shop_id` null = todas | |
| `tb_photo_album`: `tb_customer_cd`, `comment`, `disp_date`, `pic_flg`, `hyouji_flg` | `customer_photos`: `customer_id`, `comment`, `shown_on`, `images` json (rutas Storage según `pic_flg`), `status` | |
| `tb_play_time` (filas de clientes) | `customer_entries` | fila mensual con 31×(`syukkin_time_N`, `taikin_time_N`) → una fila por día con dato; las filas con `staff_cd` son de S5 |
| `tb_felica_mode` (`cd = 2`): `mode`, `interval_time`, `nyushitsu_komento`, `taishitsu_komento` | `customer_reader_settings` (fila única): `mode`, `entry_message`, `exit_message`; `interval_time` → `shops.visit_interval_seconds` de todas las tiendas | |
| `tb_customer_card`, `tb_user_card` | — | de S4 (`felica_cards`) y S5 (personal) |
| `tb_shukei_link` | `report_links` | |

### A.4 Ventas

| Legacy | Nuevo | Notas |
|---|---|---|
| `sales_items`: `cd`, `name`, `display_name`, `display_title`, `discription`, `discription_detail`, `is_exchangeable_of_point`, `number_of_points_exchanged`, `available_shop_cds` (int[]), `hyouji_flg`, `disp_flg = 0` | `products`: `id = cd`, `name`, `display_name`, `title`, `description`, `detail`, `exchange_flg`, `exchange_points`, `product_shop`, `status`, `deleted_at` | imagen `hp/uploads/c2/item/<cd>/<cd>.jpg` (+`_big`, `_small`) → Storage `products/<id>/` |
| `tb_karute_menu`: `name`, `kingaku`, `type` (1 技術, 2 物販, 3 その他), `tb_shop_cd`, `rank`, `hyouji_flg` | `service_menus`: `name`, `price`, `type`, `shop_id`, `sort`, `status` | |
| `tb_karute_waribiki`: `name`, `value`, `type` (1 %, 2 円), `buai_flg` (1 cuenta para comisión, 2 no) | `discounts`: `name`, `value`, `type`, `commission_flg` (1 / 0) | |

### A.5 Reservas

| Legacy | Nuevo |
|---|---|
| `tb_yoyaku` | `reservations` (ver §2.4): `tb_yoyaku_uketsuke_day_cd` → `reservation_slot_id` (+ `staff_id`, `shop_id`, fecha); `start_time`/`end_time` + fecha → `reserved_at`/`ends_at`; `entry_type` → `channel` (`''`/null → 0); `free_koumoku` (`rawurlencode` unidos por `<>`) → `form_values` `{free_koumoku_1..5}`; `base_…_cd` (`'0'` = ninguno) y `option_…_cd` → `reservation_items`; `pos_menu*` → descartados (POS); `moushikomi_time`, `staff_shimei_flg` → no existen/comentados |
| `tb_reserve`: `reserve_time`, `reserve_cate`, `reserve_people`, `reserve_nomination`, `user_cd`, `user_name`, `user_mail`, `user_tel`, `shop_cd`, `reserve_flg`, `raiten_flg`, `update` | `reservations` `channel = 300`: `reserved_at`, `reservation_menu_category_id`, `people`, `staff_id`, `customer_id`, nombre partido, `mail`, `tel`, `shop_id`, `state`, `visited_flg`, `updated_at` |
| `tb_reserve_name`: `reserve_cate_name`, tienda | `reservation_menu_categories` con `shop_id` |
| `tb_yoyaku_uketsuke_day` | `reservation_slots`: `start_time`, `end_time`, `start_break_time`, `end_break_time` → `*_seconds`; `people` → `capacity`; `status_flg` (`''` → 0) → `accept_state` |
| `tb_yoyaku_koumoku_master` | `reservation_menus` (`naiyou` → `description`, `shoyou_time` → `minutes`, `type_flg` → `type`, `menu_end_time*` → `last_order_*`) |
| `tb_yoyaku_koumoku_category` | `reservation_menu_categories` (`shop_id` null) |
| `tb_staff_works` | `reservation_menu_staff` (`shoyou_time` → `minutes`, `mypage_flg` → `mypage_hidden_flg`) |
| `tb_setsubi` (`quantity`) | `facilities` (`capacity`) |
| `tb_free_yoyaku_rank` | `free_reservation_ranks` |
| `tb_yoyaku_config` | `reservation_shop_settings` (`shimekiri` → `deadline_days`, `shimekiri_time` → `deadline_hours`, `uketuke_type` → `form_accept_type`, `setsubi_capacity` → `facility_capacity`; `automail*` descartados) |
| `contents_master` (`category = 'tb_c9_yoyaku'`, `int_data` = tienda): `free_yoyaku_disp_flg`, `free_yoyaku_name`, `free_yoyaku_staff_select`, `free_yoyaku_timeline_staff` | `reservation_shop_settings.free_*`; `mail_send_status` → no se migra (estado interno) |
| `admin_master`: `yoyaku_time_interval`, `meyasu_jikan_flg` (`''` → 1), `yoyaku_setsubi_flg`, `free_yoyaku_flg` | `reservation_settings` |
| `tb_manage_words` (claves de `yoyaku_comment_config.php`) | `reservation_settings.comments` |
| `tb_koumoku` (`tb_name` `'tb_yoyaku'`, `'tb_reserve'`): `koumoku_disp`, `koumoku_check` | `reservation_settings.form_fields` |
| `numbered_ticket` | `numbered_tickets` (`issued_date` → `issued_at`, `issued_number` → `number`, `called_flg` → `call_state`, `adult`/`child`, `smoking_select`, `tb_yoyaku_cd` → `reservation_id`) |
