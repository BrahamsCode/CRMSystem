# Diseño S2 — Clientes (顧客管理), ventas (販売管理) y reservas (予約管理)

> Sesión S2 · rama `l13/clientes` · 2026-11-26 · **v2 sobre la BD y la UI del legacy** (CONVENCIONES §0 y §4). Estado: para revisión de S0 (sin código).
> Fuentes: `Doc/analisis-legacy/01-clientes-y-ventas.md`, `05-…` (予約管理 `category9/yoyaku/`), `07-…` (A.5 `r/` y `reserve/`), `03b-…` (`category3/reservation/`, cedido por S3); esquema real `public_html/database/schema/pgsql-schema.sql`; código legacy `crm2-php8/public_html` (solo lectura).
> La v1 (tablas nuevas) queda en git (`917be77`) y en el stash `obsoleto: tablas nuevas (S2 R1 reservas)` solo como referencia de lógica.

## 0. Convenciones aplicadas en este grupo

| Qué | Clientes | Ventas | Reservas |
|---|---|---|---|
| `<modulo>` (rutas, vistas, menú) | `clientes` | `ventas` | `reservas` |
| Rutas de administración | `routes/modulos/clientes.php` · `admin.clientes.*` | `routes/modulos/ventas.php` · `admin.ventas.*` | `routes/modulos/reservas.php` · `admin.reservas.*` |
| Rutas públicas (URL literal del legacy) | — | — | `routes/publico/reservas.php` · `publico.reservas.*` (`/r/*.php`, `/reserve/*.php`) |
| Controladores · Form Requests | `Admin/Customers/` · `Requests/Customers/` | `Admin/Sales/` · `Requests/Sales/` | `Admin/Reservations/` · `Requests/Reservations/` |
| Vistas Blade (HTML del legacy) | `views/admin/clientes/` | `views/admin/ventas/` | `views/admin/reservas/` (+ `views/publico/reservas/` para `r/`) |
| Servicios | `app/Services/Customers/` | `app/Services/Sales/` | `app/Services/Reservations/` |
| Proveedor (Scheduler, PosGateway) | `app/Providers/ClientesServiceProvider.php` | — | — |

- **BD:** solo tablas `tb_*` del legacy (y `sales_items`, `hatuban`, `admin_master`, `contents_master`). Un modelo Eloquent por tabla: `$table`, `$primaryKey = 'cd'`, `public $timestamps = false`, columnas tal cual, numeración `cd` con el servicio `hatuban` de S1, borrado lógico `disp_flg = '9'` con el trait/scope de S1 (no SoftDeletes). Las fechas del legacy son epoch en `text`/`integer` (`touroku_date`, `update_date`, `raiten_time`…): accesores Carbon con `Asia/Tokyo`, nunca `mktime()`.
- **UI:** cada pantalla es el HTML del legacy pasado a Blade con las mismas clases, ids y textos japoneses, dentro del layout de S1, con los CSS/JS de `public/legacy/` (S1). Los popups de 600 px del legacy se mantienen como estaban (misma UI); el responsive va al final.
- No había código migrado de clientes en `laravel13` (se borró): **todas las filas están `pendiente`** salvo los descartes.

Abreviaturas: `Cu\` = `Admin\Customers\`, `Sa\` = `Admin\Sales\`, `Re\` = `Admin\Reservations\`. Tablas: **L** = lee, **E** = escribe.

---

## 1. Tabla de paridad

### 1.1 顧客管理 — `category1/`

#### Inicio

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas `tb_*` | Estado |
|---|---|---|---|---|---|
| `index.php` | 顧客管理トップ | `admin.clientes.home` | `Cu\ModuleController@index` | L `tb_customer` (contador `inc/entry_counter.php`), `tb_base_system` (S1) | pendiente (la limpieza `system("find … rm")` pasa al Scheduler) |

#### `customer/` — listas, búsqueda y configuración

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas `tb_*` | Estado |
|---|---|---|---|---|---|
| `customer_list.php` | 顧客一覧・検索 | `admin.clientes.index`, `.search` | `Cu\CustomerController@index/search` + `CustomerSearch` | L `tb_customer`, `tb_customer_sub`, `tb_search_form`, `tb_search_cate`, `tb_raiten_rireki`, `tb_shop`, `tb_koumoku` | pendiente (103 filtros de `inc/search_set1.php`, 表示件数, 3 CSV, 郵便CSV, 配信/LINE配信 → S3) |
| `customer_search.php` | 来店処理 | `admin.clientes.visits`, `.visits.store` | `Cu\VisitController@index/store` + `VisitService::register` | L/E `tb_customer`, `tb_raiten_rireki`, `hatuban`; puntos y sellos vía S3 | pendiente |
| wrappers `customer_new*.php`, `customer_detail.php`, `customer_recover_confirm.php`, `customer_delete_register.php`, `del_record.php`, `kanren_*` (4), `add_record_*` (3), `photoalbum_*` (3) | (wrappers) | — | — | — | descartado: wrappers de 3 líneas de `inc/` (filas en `inc/`); `kanren_record_confirm.php` roto |
| `customer_new_complete.php` | 顧客登録情報 | — | — | — | descartado: versión paralela del alta sin enlace; el alta de `inc/` la cubre |
| `form_config.php`, `form_config_register.php` | 表示・検索・CSV項目設定 | `admin.clientes.field-settings`, `.field-settings.update` | `Cu\FieldSettingController@index/update` | L/E `tb_search_form` (`hissu`, `hyouji_flg`, `serach_flg`, `csv_flg`), `tb_koumoku`; L `tb_search_cate` | pendiente |
| `search_cate_list.php` | 顧客追加情報設定 | `admin.clientes.custom-categories` (+`.sort`, `.flags`) | `Cu\CustomCategoryController@index/sort/updateFlags` | L/E `tb_search_cate` | pendiente |
| `search_cate_new.php`, `_new_check.php`, `_new_up.php` | 顧客追加情報 (categoría) | `.custom-categories.create/.store` (+`.check` JSON) | `@create/store/check` | E `tb_search_cate`, `hatuban` | pendiente |
| `search_cate_update.php`, `_update_up.php` | 〃 | `.custom-categories.edit/.update` | `@edit/update` | E `tb_search_cate` | pendiente |
| `search_cate_delete_up.php` | 〃 | `.custom-categories.destroy` | `@destroy` | E `tb_search_cate`, `tb_search_form`, `tb_customer_sub` (`disp_flg = 9`) | pendiente |
| `search_form_list.php` | 顧客追加情報 (campos) | `admin.clientes.custom-fields` | `Cu\CustomFieldController@index` | L `tb_search_form`, `tb_search_cate` | pendiente |
| `search_form_new.php`, `_new_check.php`, `_new_up.php` | 〃 | `.custom-fields.create/.store` (+`.check`) | `@create/store/check` | E `tb_search_form`, `hatuban` | pendiente |
| `search_form_update.php`, `_update_up.php` | 〃 | `.custom-fields.edit/.update` | `@edit/update` | E `tb_search_form` | pendiente |
| `search_form_delete_up.php` | 〃 | `.custom-fields.destroy` | `@destroy` | E `tb_search_form`, `tb_customer_sub` | pendiente |
| `customer_group_category_new.php`, `_frm.php`, `_register.php` | 顧客グループ設定 | `admin.clientes.groups` (+`.store/.update/.sort/.destroy`) | `Cu\CustomerGroupController` | L/E `tb_customer_group` (modelo de S3), `hatuban` | pendiente |
| `customer_level_category_new.php`, `_frm.php`, `_register.php` | 顧客レベル | `admin.clientes.levels` (sin entrada de menú, como el legacy) | `Cu\CustomerLevelController` | L/E `tb_customer_level`, `hatuban` | pendiente (prioridad baja; S0: tiene pantalla porque el legacy la tenía) |
| `raiten_douki.php`, `_frm.php`, `_register.php` | 初回来店動機項目設定 | `admin.clientes.visit-motives` (+`.store/.update/.sort/.destroy`) | `Cu\VisitMotiveController` | L/E `tb_raiten_douki_master`, `hatuban` | pendiente |
| `point_setting.php`, `_frm.php`, `_register.php` | ポイント管理 | (S3) | — | `tb_point_setting`, `tb_c1_point_setting`, `contents_master` | descartado: lo cubre S3 (Point*); el menú de clientes enlaza a su ruta |
| `ajax_apliFileUpload.php` | (imágenes マイページ) | (S3) | — | — | descartado: parte de ポイント管理 (S3) |
| `point_history.php` | ポイント利用一覧 | `admin.clientes.point-history`, `.point-history.csv` | `Cu\PointHistoryController@index/csv` | L `tb_point_log` (S3), `tb_customer`, `tb_shop`, `tb_staff`, `tb_terminal` | pendiente (CSV SJIS-win) |
| `csv_import.php` | CSVインポート | `admin.clientes.import`, `.import.store` | `Cu\CustomerImportController@create/store` + `CustomerCsvImporter` | E `tb_customer`, `hatuban`; L `tb_staff` | pendiente (`createID`, `randomPass(4)` como el legacy: ver Decisiones) |
| `csv_export.php`, `csv_from_js.php`, `user_csv.php` | CSV de clientes | `admin.clientes.export` | `Cu\CustomerController@export` + `CustomerCsvExporter` | L `tb_customer`, `tb_customer_group`, `tb_staff`, `tb_raiten_douki_master`, `tb_koumoku` | pendiente (`csv_from_js` y `user_csv` descartados: Flash / huérfano) |
| `csv_download.php` (+carpeta), `csv_download_ex02.php` (+), `csv_download_template.php` (+) | CSV (Flash) | `admin.clientes.export?format=standard\|ex02\|template` | `Cu\CustomerController@export` | L `tb_customer`, `tb_search_form`, `tb_customer_sub`, `tb_customer_group`, `tb_staff` | pendiente (StreamedResponse por bloques, sin Flash) |
| `customer_export.php` | (volcado de tabla) | — | — | — | descartado: volcado arbitrario de tablas (riesgo) |
| `customer_sumup_list.php`, `customer_sumup_list2.php` | 顧客情報集計 | `admin.clientes.statistics` | `Cu\StatisticController@index` | L `tb_customer`, `tb_job`, `tb_pref`, `tb_raiten_rireki` | pendiente (una sola versión) |
| `customer_card_delete.php` | UID関連付け解除 | `admin.clientes.felica.destroy` | `Cu\CustomerFelicaController@destroy` | E `tb_customer.felica_no`, `tb_customer_card` (S4), `tb_user_card`, `tb_play_time` | pendiente (copia de `inc/`, una ruta) |
| `karute_customer_search.php` | 顧客一覧・検索 (selector) | `admin.clientes.lookup` | `Cu\CustomerLookupController` | L `tb_customer`, `tb_koumoku` | pendiente (popup selector común) |
| `karute_frm.php`, `karute_new.php`, `karute_register.php`, `karute_update.php` | カルテ | — | — | — | descartado: copia de `inc/karute_*` |
| `stamp_use_register.php` | (usar sello) | — | — | — | descartado: copia de `inc/stamp_use_register.php` |
| `ajax_category.php`, `ajax_staff.php`, `zip.php` | (utilidades) | `admin.clientes.options.categories/.staff`, `admin.clientes.zip` | `Cu\OptionController`, `Cu\ZipController` | L `tb_sejutsu_cate`, `tb_staff`, `zip` | pendiente (`zip` local, como el legacy) |
| `inc/page.php`, `inc/raiten_rireki.php`, `inc/search_set1.php`, `inc/search_set1_sumup.php`, `inc/search_set2_sumup.php`, `js/*`, `uploads/` | (fragmentos) | — | — | — | descartado como vista: parciales Blade (`_search_set1.blade.php`, paginador) y `CustomerSearch` |

#### `inc/` — ficha, alta, edición, pestañas, visitas, puntos y sellos

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas `tb_*` | Estado |
|---|---|---|---|---|---|
| `customer_new.php` | 顧客新規登録 | `admin.clientes.create`, `.edit` | `Cu\CustomerController@create/edit` | L `tb_customer`, `tb_customer_sub`, `tb_search_form`, `tb_search_cate`, `tb_shop`, `tb_staff`, `contents_master` (`name_input_mode_flg`), `tb_koumoku` | pendiente |
| `customer_new_confirm.php` | 顧客登録情報 (確認) | `admin.clientes.confirm` (POST) | `Cu\CustomerController@confirm` + `StoreCustomerRequest` | L `tb_customer`, `tb_search_form`, `tb_staff` | pendiente (misma pantalla de confirmación que el legacy) |
| `customer_new_register.php` | 顧客登録情報 | `.store`, `.update` | `@store/update` + `CustomerRegistrationService` | E `tb_customer`, `tb_customer_sub`, `hatuban` | pendiente |
| `customer_detail.php` | 顧客登録情報 | `admin.clientes.show` (`?tab=N`) | `Cu\CustomerController@show` + `CustomerPolicy` | L `tb_customer`, `tb_search_cate`, `tb_shop`, `admin_master`, `contents_master`, `tb_manage_words` | pendiente |
| `customer_recover_confirm.php` | 顧客登録情報 (復元) | `admin.clientes.restore` | `@restore` | E `tb_customer` (`disp_flg` 9 → 1) | pendiente |
| `customer_delete_register.php` | 顧客登録情報 (削除) | `admin.clientes.destroy` | `@destroy` | E `tb_customer`, `tb_customer_sub`, `tb_user_card`, `tb_play_time` (`disp_flg = 9`, libera la tarjeta) | pendiente |
| `customer_card_delete.php` | UID関連付け解除 | `admin.clientes.felica.destroy` | `Cu\CustomerFelicaController@destroy` | E `tb_customer`, `tb_customer_card`, `tb_user_card`, `tb_play_time` | pendiente |
| `customer_window_close.php` | 顧客登録情報 | `admin.clientes.window-close` | (vista Blade) | — | pendiente (misma UI: cierra el popup y recarga el padre) |
| `add_record_new.php`, `add_record_confirm.php`, `add_record_register.php` | 顧客登録情報 (追加情報) | `admin.clientes.custom-records.create/.confirm/.store` | `Cu\CustomRecordController` | L/E `tb_customer_sub` (`category_inner_group`); L `tb_search_form`, `tb_search_cate` | pendiente |
| `del_record.php` | 顧客登録情報 | `.custom-records.destroy` | `@destroy` | E `tb_customer_sub` | pendiente |
| `kanren_record_new.php`, `kanren_record_register.php`, `kanren_del_record.php` | 来店履歴の追加・修正 | `admin.clientes.visit-records.create/.store/.destroy` | `Cu\VisitRecordController` + `VisitService` | E `tb_raiten_rireki`, `tb_point_log`, `tb_stamp_log`, `tb_customer_point`, `tb_customer_sub`, `tb_customer`; L `tb_staff`, `tb_shop` | pendiente (transacción con `FOR UPDATE`) |
| `raiten_register.php`, `raiten_function.php` | (registrar visita) | `admin.clientes.visits.store` | `VisitService::register` | E `tb_raiten_rireki`, `tb_customer` (`shuki_heikin`, `last_raiten_date`), `tb_customer_sub`, `hatuban` | pendiente |
| `point_register.php`, `ajax_point_register.php` | (ポイント) | `admin.clientes.points.store` | `Cu\CustomerPointController@store` → servicio de puntos de S3 | E `tb_customer_point`, `tb_point_log` | pendiente |
| `stamp_register.php`, `stamp_use_register.php` | (スタンプ) | `admin.clientes.stamps.store/.use` | `Cu\CustomerStampController` → servicio de sellos de S3 | E `tb_customer.stamp_count`, `tb_stamp_log`; L `tb_mycoupon_master`, `tb_stamp_oshirase_coupon` | pendiente |
| `karute_frm.php`, `karute_new.php`, `karute_register.php`, `karute_update.php` | カルテ | `admin.clientes.kartes.create/.store/.edit/.update` | `Cu\KarteController` | E `tb_karute`, `hatuban`; L `tb_sejutsu_cate`, `tb_staff`, `tb_shop` | pendiente |
| `photoalbum_new.php`, `_frm.php`, `_register.php`, `_update.php`, `_delete.php` | マイアルバム | `admin.clientes.photos.*` | `Cu\CustomerPhotoController` + `CustomerPhotoService` | E `tb_photo_album`, `hatuban` | pendiente (archivos en Storage, mismas URL `hp/uploads/...`) |
| `ajax_picupload.php` | (subida de foto) | `admin.clientes.photos.upload` | `CustomerPhotoController@upload` | — | pendiente (Storage + Intervention en lugar de `system()`) |
| `ajax_changePaidMemberFlg.php` | (有料会員) | `admin.clientes.paid-member.update` | `Cu\PaidMemberController@update` → `MobileShopClient` (S5) | L/E `tb_customer.paid_member_flg*` | pendiente |
| `ajax_connectPosData.php`, `ajax_connectPosKoubaiData.php`, `ajax_sendPosKoubaiMemoData.php` | (購買履歴 POS) | — | `App\Services\Customers\PosGateway` (nulo) | — | descartado: POS fuera de alcance; queda la interfaz |
| `ajax_search_kaiin.php`, `ajax_category.php`, `ajax_raiten_list.php`, `ajax_raiten_time_select.php`, `ajax_shop_point_config.php`, `ajax_shop_stamp_config.php` | (utilidades) | `admin.clientes.lookup`, `admin.clientes.options.*` | `Cu\CustomerLookupController`, `Cu\OptionController` | L `tb_customer`, `tb_raiten_rireki`, `tb_shop`, `tb_sejutsu_cate` | pendiente (mismas respuestas que esperan los JS del legacy) |
| `detail_tab.php` | 顧客情報 | `.show?tab=1` | `@show` | L `tb_customer`, `tb_kingaku_rank_master`, `tb_raiten_rank_master` (S3), `tb_staff` | pendiente |
| `detail_tab2.php` | 個人情報/法人情報 | `.show?tab=2` | `@show` | L `tb_customer` | pendiente |
| `detail_tab3.php` | 販促用情報 | `.show?tab=3` | `@show` | L `tb_customer_group`, `tb_raiten_douki_master` | pendiente |
| `detail_tab4.php` | 来店履歴 | `.show?tab=4` | `@show` | L `tb_raiten_rireki`, `tb_shop` | pendiente |
| `detail_tab5.php` | カルテ | `.show?tab=5` | `@show` | L `tb_karute`, `tb_sejutsu_cate` | pendiente |
| `detail_tab6.php` | マイクーポン | `.show?tab=6` | `@show` | L `tb_mycoupon`, `tb_mycoupon_master`, `tb_mycoupon_used` (S3) | pendiente |
| `detail_tab7.php` | マイアルバム | `.show?tab=7` | `@show` | L `tb_photo_album` | pendiente |
| `detail_tab8.php` | ポイント | `.show?tab=8` | `@show` | L `tb_point_log`, `tb_customer_point` (S3), `tb_staff`, `tb_terminal` | pendiente |
| `detail_tab9.php` | 関連情報 | `.show?tab=9` | `@show` | L `tb_raiten_rireki` (`tb_point_log_cd`, `tb_stamp_log_cd`, `tb_search_cate_cd`, `tb_customer_sub_inner_group`), `tb_point_log`, `tb_stamp_log`, `tb_customer_sub`, `admin_master.related_features` | pendiente (`tb_related_features` no existe en el backup: la relación vive en `tb_raiten_rireki`) |
| `detail_tab10.php` | スタンプ | `.show?tab=10` | `@show` | L `tb_stamp_log`, `tb_staff`, `tb_terminal` | pendiente |
| `detail_tab11.php` | 購買履歴 | `.show?tab=11` | `@show` | L `tb_order_sub`, `tb_customer_sub`, `tb_search_form` | pendiente (la parte POS queda vacía tras `PosGateway`) |
| `detail_tab12.php` | 予約状況 | `.show?tab=12` | `@show` | L `tb_yoyaku`, `tb_yoyaku_uketsuke_day`, `tb_yoyaku_koumoku_master`, `tb_staff_works` | pendiente |
| `detail_tab13.php` | 紹介実績 | `.show?tab=13` | `@show` | L `tb_customer` (`shoukai_tb_customer_cd`), `tb_raiten_rireki` | pendiente |
| `detail_tab14.php` | 有料会員状況 | `.show?tab=14` | `@show` → `MobileShopClient` (S5) | L `tb_customer` | pendiente |
| `detail_tabcustom.php` | (categoría adicional) | `.show?tab=custom-{cd}` | `@show` | L `tb_customer_sub`, `tb_search_form` | pendiente |
| `search_set2.php`, `page_control.php` | (buscador de productos, paginador) | — | — | — | descartado: `tb_c2_*` borradas en 2020; paginador común |

#### `biyou/` — ficha 美容 (sin enlace en el menú; tiene pantalla porque el legacy la tenía)

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas `tb_*` | Estado |
|---|---|---|---|---|---|
| `sejutsu_cate.php` | 予約カテゴリー管理 | `admin.clientes.service-categories.index` | `Cu\ServiceCategoryController@index` | L/E `tb_sejutsu_cate`, L `tb_shop` | pendiente |
| `sejutsu_cate_register.php` | 施術カテゴリー登録 | `.service-categories.store/.update` | `@store/update` | E `tb_sejutsu_cate`, `hatuban` | pendiente |
| `sejutsu_cate_delete.php` | 施術カテゴリー削除 | `.service-categories.destroy` | `@destroy` | E `tb_sejutsu_cate` | pendiente |
| `karute_detail.php` | カルテ詳細 | `admin.clientes.kartes.show` | `Cu\KarteController@show` | L `tb_karute`, `tb_customer`, `tb_sejutsu_cate` | pendiente |
| `karute_frm.php`, `karute_new.php`, `karute_register.php`, `karute_update.php`, `karute_customer_search.php`, `ajax_category.php` | (copias) | — | — | — | descartado: copias de `inc/` y `customer/` |

#### `config/` — configuración inicial

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas `tb_*` | Estado |
|---|---|---|---|---|---|
| `felica_mode.php` | カードリーダーモード設定 | `admin.clientes.reader-mode` | `Cu\ReaderModeController@edit/update` | L/E `tb_felica_mode` (fila `cd = 2`: `mode`, `interval_time`, `nyushitsu_komento`, `taishitsu_komento`); L `tb_customer`, `tb_user_card` | pendiente (si falta la fila `cd = 2`, se crea en lugar de no guardar en silencio: ver Decisiones) |
| `card_new.php` | カード管理 | `admin.clientes.cards` | `Cu\CardController@index/store` | L/E `tb_user_card`, `tb_customer`, `tb_play_time`, `tb_felica_mode` | pendiente (necesita lector real para probar del todo) |
| `card_staff_select.php` | (selector de personal) | `admin.clientes.options.staff` | `Cu\OptionController@staff` | L `tb_staff` | pendiente |
| `shop_work_calendar.php` | 営業日設定 | (S5) `admin.operaciones.business-days.*` | (S5) | `tb_shop_work` (S5) | descartado: la misma pantalla y tabla la hace S5; el menú de clientes enlaza a su ruta |
| `mail_comment.php`, `mail_comment_register.php` | (コメント設定) | `admin.clientes.mail-comment` | `Cu\MailCommentController@edit/update` | L/E `tb_felica_mode` (`nyushitsu_komento`/`taishitsu_komento`) **(?)**; L `tb_schoolyear_config` | pendiente (prioridad baja, sin enlace en el menú) |
| `schoolyear_config.php`, `schoolyear_register.php`, `schoolyear_delete.php` | 学年設定 | `admin.clientes.school-years.*` | `Cu\SchoolYearController` | L/E `tb_schoolyear_config` (`cate_name`, `cate_order`), `hatuban` | pendiente (prioridad baja) |
| `schoolclass_config.php`, `schoolclass_register.php`, `schoolclass_delete.php` | クラス設定 | `admin.clientes.school-classes.*` | `Cu\SchoolClassController` | L/E `tb_schoolclass_config`, `hatuban` | pendiente (prioridad baja) |

#### `entrance/` — entrada y salida

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas `tb_*` | Estado |
|---|---|---|---|---|---|
| `working_calendar.php` | 入退室一覧 (calendario) | `admin.clientes.entries.calendar` | `Cu\EntryController@calendar` | L `tb_shop`, `tb_shop_work` (S5) | pendiente (Carbon; sin los botones 「1970年1月へ」) |
| `work_list.php` | 入退室一覧 | `admin.clientes.entries.index?date=` | `Cu\EntryController@index` | L `tb_play_time` (S5), `tb_customer`, `tb_shop` | pendiente |

#### `rank/` — rangos (modelos de S3, pantallas de S2)

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas `tb_*` | Estado |
|---|---|---|---|---|---|
| `customer_rank_list.php` | 顧客ランク設定 | `admin.clientes.ranks` (+`.status`) | `Cu\RankController@index/updateStatus` | L/E `tb_kingaku_rank_master`, `tb_raiten_rank_master` (`hyouji_flg`) | pendiente |
| `kingaku_rank_new.php`, `kingaku_rank_update.php` | 顧客ランク設定 (金額) | `.ranks.create/.store/.edit/.update/.destroy/.sort?type=kingaku` | `Cu\RankController` | E `tb_kingaku_rank_master`, `hatuban` | pendiente |
| `raiten_rank_new.php`, `raiten_rank_update.php` | 顧客ランク設定 (来店) | ídem `?type=raiten` | `Cu\RankController` | E `tb_raiten_rank_master`, `hatuban` | pendiente |
| `customer_rank_frm.php`, `customer_rank_frm_register.php` | (formulario de rango) | (ídem) | `StoreRankRequest` | E ambas tablas | pendiente |
| `ajax_customer_rank_list.php` | (lista AJAX) | `admin.clientes.ranks.list` | `@list` | L ambas tablas | pendiente (el JS del legacy la recarga por AJAX) |
| `customer_rank_schedule.php`, `_register.php` | 顧客ランク振り分け設定 | `admin.clientes.rank-schedules` | `Cu\RankScheduleController@edit/update` | L/E `admin_master` (`kingaku_rank_shori_date/_time/_kikan`, `raiten_rank_shori_*`) | pendiente (el Scheduler lee estas columnas; sin `crontab`) |
| `setCustomerKingakuRank.php` | (tarea) | — | `clientes:asignar-rangos --tipo=kingaku` | L/E `tb_customer` (`kingaku_rank_now/pre`); L `tb_kingaku_rank_master`, `tb_point_log`, `admin_master` | pendiente |
| `setCustomerRaitenRank.php` | (tarea) | — | `clientes:asignar-rangos --tipo=raiten` | L/E `tb_customer` (`raiten_rank_now/pre`); L `tb_raiten_rank_master`, `tb_raiten_rireki` | pendiente |

#### `shukei/` — agregaciones

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas `tb_*` | Estado |
|---|---|---|---|---|---|
| `shukei_list.php` | 集計ツール | `admin.clientes.reports.index` | `Cu\ReportController@index` | L `tb_shukei_link` | pendiente |
| `model1.php`, `model1_search.php`, `model1_select.php` | モデル集計 1 | `admin.clientes.reports.model1` (+`.search`, `.select`) | `Cu\ReportController@model1` | L `tb_raiten_rireki`, `tb_customer`, `tb_customer_sub` | pendiente |
| `model2.php`, `model2_search.php` | モデル集計 2 | `admin.clientes.reports.model2` (+`.search`) | `@model2` | ídem | pendiente |
| `point1.php`, `point1_search.php` | 【会計金額】集計 | `admin.clientes.reports.point1` | `@point1` | L `tb_point_log`, `tb_raiten_rireki`, `tb_customer` | pendiente |
| `point2.php`, `point2_search.php` | 【ポイント】集計 | `admin.clientes.reports.point2` | `@point2` | L `tb_point_log`, `tb_customer`, `tb_shop` | pendiente |
| `stamp1.php`, `stamp1_search.php` | 【会計金額】集計 (スタンプ) | `admin.clientes.reports.stamp1` | `@stamp1` | L `tb_stamp_log`, `tb_raiten_rireki`, `tb_customer` | pendiente |
| `customerDataOutPut.php` | csv出力管理画面 | `admin.clientes.reports.visit-csv` (+`.download`) | `Cu\VisitCsvController` | L `tb_customer`, `tb_customer_point`, `tb_raiten_rireki`, `tb_search_form`, `tb_yoyaku_koumoku_master` | pendiente (SJIS-win) |
| `shukei_mail_new.php`, `_confirm.php`, `_send.php` | (配信) | (S3) | — | — | descartado: envío de S3 (`category3/inc/mail_frm.php`); el informe pasa el filtro a su pantalla |
| `customer_sumup_list*.php` | (ver `customer/`) | — | — | — | descartado: fila en `customer/` |

#### Ventas internas — `category1/karute_menu/` (módulo `ventas`)

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas `tb_*` | Estado |
|---|---|---|---|---|---|
| `karute_new.php` | 売上データ作成 | `admin.ventas.sales.create` | `Sa\SaleController@create` | L `tb_karute_menu` | pendiente |
| `karute_new_frm.php` | 売上データ作成 (formulario) | `admin.ventas.sales.form` | `Sa\SaleController@form` | L `tb_customer`, `tb_karute_menu`, `tb_karute_waribiki`, `tb_staff` | pendiente |
| `karute_new_register.php` | 売上データ作成 | `admin.ventas.sales.store` | `Sa\SaleController@store` + `SaleService` | E `tb_customer_sub` (`tb_search_cate_cd = -1`, `category_inner_group` = nº de cuenta), `hatuban` | pendiente (comisión `(int)(base × kingaku_buai / 100)`) |
| `karute_delete_list.php` | 売上追加・修正・削除 | `admin.ventas.sales.index` | `Sa\SaleController@index` | L `tb_customer_sub`, `tb_customer`, `tb_staff`, `tb_karute_waribiki` | pendiente |
| `karute_delete_register.php` | 売上追加・修正・削除 | `admin.ventas.sales.destroy` | `@destroy` | E `tb_customer_sub` (`disp_flg = 9`) | pendiente |
| `karute_menu.php`, `karute_menu_register.php` | メニュー管理 | `admin.ventas.menus.*` | `Sa\ServiceMenuController` | E `tb_karute_menu`, `hatuban` | pendiente |
| `karute_waribiki.php`, `karute_waribiki_register.php` | 割引管理 | `admin.ventas.discounts.*` | `Sa\DiscountController` | E `tb_karute_waribiki`, `hatuban` | pendiente |
| `keihi.php`, `keihi_register.php`, `keihi_delete.php` | 経費管理 | `admin.ventas.expenses.*` | `Sa\ExpenseController` | E `tb_customer_sub` (`tb_search_cate_cd = -2`) | pendiente |
| `staff_total.php`, `ajax_staffTotal.php` | スタッフ別集計 | `admin.ventas.reports.staff` (+`.data`) | `Sa\SalesReportController@staff` | L `tb_customer_sub`, `tb_staff`, `tb_customer` | pendiente (sin límite de 60 días, como el legacy) |
| `report.php`, `ajax_report.php` | 日報・週報・月報 | `admin.ventas.reports.period` (+`.data`) | `@period` | L `tb_customer_sub`, `tb_shop`, `tb_staff` | pendiente |
| `ajax_karute_menu_list.php`, `ajax_karute_waribiki_list.php`, `ajax_search_kaiin.php`, `ajax_staff.php` | (utilidades) | `admin.ventas.options.*`, `admin.clientes.lookup` | `Sa\OptionController` | L `tb_karute_menu`, `tb_karute_waribiki`, `tb_customer`, `tb_staff` | pendiente |
| `css/*` | | — | — | — | descartado como vista: `karute_frm.css`, `report.css`, `stafftotal.css` van a `public/legacy/` (pedido S1) |

### 1.2 販売管理 — `category2/` (módulo `ventas`)

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas `tb_*` | Estado |
|---|---|---|---|---|---|
| `index.php` | 販売管理トップ | `admin.ventas.home` | `Sa\ModuleController@index` | L `tb_base_system` (S1) | pendiente |
| `item/list.php` | 商品管理 (商品検索・一覧) | `admin.ventas.products.index` | `Sa\ProductController@index` | L `sales_items` (`disp_flg = 1`), `tb_shop` | pendiente |
| `item/edit.php` | 商品新規登録 / 編集 | `.products.create/.store/.edit/.update` | `Sa\ProductController` + `StoreProductRequest` | E `sales_items` (`cd` serial propio); L `tb_shop` | pendiente (imágenes 800/320 px en `hp/uploads/c2/item/<cd>/`; editor: el del legacy o el que decida S1) |
| `item/ajax_edit.php` | (validación) | `admin.ventas.products.validate` (JSON) | `StoreProductRequest` | — | pendiente (el JS del legacy espera esta validación AJAX) |
| `item/ajax_list_delete.php` | (borrado) | `.products.destroy` | `@destroy` | E `sales_items.disp_flg = 0` | pendiente |

### 1.3 予約管理 — `category9/yoyaku/` (módulo `reservas`)

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas `tb_*` | Estado |
|---|---|---|---|---|---|
| `time_table.php` | 予約表 | `admin.reservas.timetable` (`?view=day\|week\|month`) | `Re\TimetableController@index` | L `tb_base_system_grandchild`, `tb_staff`, `tb_yoyaku`, `tb_yoyaku_config`, `tb_yoyaku_koumoku_master`, `tb_yoyaku_uketsuke_day` | pendiente |
| `time_table_staff.php` | スタッフ別予約表 | `admin.reservas.timetable.staff` | `@staff` | + `tb_staff_works` | pendiente |
| `new_time_table.php` | 予約表 (nuevo) | — | — | — | descartado: sin enlace, solo carga |
| `ajax_time_table.php` | — | `admin.reservas.timetable.data` | `@data` + `TimetableService` | L `tb_customer`, `tb_raiten_rireki`, `tb_staff`, `tb_staff_works`, `tb_yoyaku*` | pendiente (misma respuesta HTML/JSON que espera `timetable.js`) |
| `ajax_move_objs.php` | — | `admin.reservas.move` | `Re\ReservationController@move` + `ReservationService::move` | E `tb_yoyaku`, `tb_yoyaku_uketsuke_day` | pendiente |
| `ajax_uketsuke_status.php` | — | `admin.reservas.slots.status` | `Re\ReservationSlotController@status` | E `tb_yoyaku_uketsuke_day.status_flg` + log | pendiente |
| `ajax_loadNewYoyakuList.php`, `ajax_loadNewRaitenList.php` | — | `admin.reservas.timetable.recent` | `@recent` | L `tb_customer`, `tb_staff`, `tb_yoyaku`, `tb_raiten_rireki` | pendiente |
| `ajax_getYoyakuDatetimeForm.php` | — | `admin.reservas.datetime-form` | `Re\ReservationController@datetimeForm` | L `tb_staff`, `tb_yoyaku`, `tb_yoyaku_config`, `tb_yoyaku_uketsuke_day` | pendiente |
| `time_table_function.php`, `sample_array.php` | — | — | — | — | descartado: funciones (`TimetableService`) y datos de ejemplo |
| `yoyaku.php` | 予約 | `admin.reservas.create` | `Re\ReservationController@create` | L `tb_customer` | pendiente |
| `yoyaku_calendar.php` | 時間選択 | `admin.reservas.calendar-select` | `@calendar` + `AvailabilityService` | L `tb_setsubi`, `tb_shop`, `tb_staff`, `tb_staff_works`, `tb_yoyaku`, `tb_yoyaku_config`, `tb_yoyaku_koumoku_master`, `tb_yoyaku_uketsuke_day` | pendiente |
| `yoyaku_customer_select.php` | 顧客情報入力 | `admin.reservas.customer-select` | `@customerSelect` | L `tb_customer`, `tb_staff`, `tb_staff_works`, `tb_yoyaku_koumoku_master` | pendiente |
| `yoyaku_register.php` | (registro de reserva) | `admin.reservas.store/.update` | `@store/update` + `ReservationService` + Mailable `ReservationMail` | E `tb_yoyaku`, `hatuban`; L `tb_customer`, `tb_koumoku`, `tb_shop`, `tb_staff`, `tb_staff_works`, `tb_yoyaku_koumoku_*` | pendiente (correo sin `md5(login_pass)`: enlace firmado) |
| `ajax_yoyaku_register.php` | — | `admin.reservas.store/.update` (JSON) | ídem, llamada directa a `ReservationService::move` | L/E `tb_yoyaku`, `tb_yoyaku_uketsuke_day` | pendiente |
| `ajax_yoyaku_menu.php`, `ajax_menu.php`, `ajax_staff.php`, `ajax_search_kaiin.php`, `ajax_yoyaku_koumoku_category.php` | — | `admin.reservas.options.*`, `admin.clientes.lookup` | `Re\OptionController` | L `tb_staff`, `tb_staff_works`, `tb_yoyaku_koumoku_*`, `tb_customer` | pendiente |
| `ajax_free_yoyaku_rank.php` | — | `admin.reservas.free.ranks` | `Re\FreeReservationController@ranks` | L/E `tb_free_yoyaku_rank` | pendiente |
| `ajax_getReservationData.php` | — | — | `App\Services\Customers\PosGateway` | — | descartado: datos para el POS (fuera de alcance) |
| `yoyaku_new*.php` (7) | (flujo `yoyaku_new_*`) | — | — | — | descartado: roto en master (columna y archivo inexistentes) |
| `yoyaku_uketsuke_day.php`, `yoyaku_uketsuke_register.php`, `yoyaku_uketsuke_delete.php` | 受付日設定 / 予約受付削除 | `admin.reservas.slots.*` | `Re\ReservationSlotController` | L `tb_customer`, `tb_staff`, `tb_yoyaku`, `tb_yoyaku_config`; E `tb_yoyaku_uketsuke_day`, `hatuban` | pendiente |
| `yoyaku_multi_uketsuke_day.php`, `yoyaku_multi_uketsuke_register.php` | 受付日設定 (varios días) | `admin.reservas.slots.bulk` (+`.store`) | `@bulkCreate/bulkStore` | L `tb_yoyaku_config`; E `tb_yoyaku_uketsuke_day` | pendiente |
| `yoyaku_koumoku_new.php`, `yoyaku_koumoku_register.php`, `ajax_yoyaku_koumoku_new.php` | 予約項目管理 | `admin.reservas.menus.*` (+`.sort`) | `Re\ReservationMenuController` | L `tb_setsubi`, `tb_yoyaku_koumoku_category`; E `tb_yoyaku_koumoku_master`, `hatuban` | pendiente |
| `yoyaku_koumoku_category.php`, `_register.php` | 予約項目カテゴリ管理 | `admin.reservas.menu-categories.*` | `Re\ReservationMenuCategoryController` | L/E `tb_yoyaku_koumoku_category` | pendiente |
| `setsubi_new.php`, `setsubi_register.php` | 設備管理 | `admin.reservas.facilities.*` | `Re\FacilityController` | L/E `tb_setsubi`, `hatuban` | pendiente |
| `staff_works.php` | スタッフ施術管理 | `admin.reservas.staff-works` | `Re\StaffWorkController@edit/update` | L/E `tb_staff_works`, `tb_staff` | pendiente (sin el bucle de `parent.location.reload()` fuera del popup) |
| `free_yoyaku.php`, `free_yoyaku_register.php` | フリー予約設定 | `admin.reservas.free` | `Re\FreeReservationController@edit/update` | L/E `contents_master` (`category = 'tb_c9_yoyaku'`, `free_yoyaku_*`), `admin_master.free_yoyaku_flg` | pendiente |
| `yoyaku_config.php`, `ajax_yoyaku_config.php` | 予約設定 (各店舗設定) | `admin.reservas.settings.shop` | `Re\ReservationSettingController@shop/updateShop` | L/E `tb_yoyaku_config`, `mailmag_master`, `hatuban` | pendiente |
| `yoyaku_form_config.php` | 予約設定 (フォーム) | `admin.reservas.settings.form` | `@form/updateForm` | L/E `tb_koumoku` (`tb_name = 'tb_yoyaku'`) | pendiente |
| `yoyaku_kinou_config.php` | 予約設定 (機能) | `admin.reservas.settings.features` | `@features/updateFeatures` | L/E `admin_master` (`yoyaku_time_interval`, `meyasu_jikan_flg`, `yoyaku_setsubi_flg`) | pendiente |
| `yoyaku_comment_config.php` | 予約設定 (注釈) | `admin.reservas.settings.comment` | `@comment/updateComment` | L/E `tb_manage_words`, `tb_koumoku` | pendiente |
| `inc/yoyaku_control.php`, `inc/yoyaku_navi.php` | — | — | layout de reservas (`yoyaku_navi` sin menú lateral) | L `tb_base_system_child`, `tb_base_system_grandchild` | descartado como vista: parcial Blade de navegación |
| `view_shop.php` | 予約一覧 (店舗毎予約数) | `admin.reservas.by-shop` | `Re\ReservationListController@byShop` | L `tb_shop`, `tb_staff`, `tb_yoyaku`, `tb_yoyaku_uketsuke_day` | pendiente |
| `yoyaku_list_calendar.php` | 予約一覧 | `admin.reservas.list-calendar` | `@calendar` | L `tb_reserve`, `tb_shop`, `tb_shop_work` | pendiente (lee `tb_reserve`, como el legacy) |
| `yoyaku_list_shop.php`, `yoyaku_list_time_table.php`, `yoyaku_revice.php` | (一覧) | — | — | — | descartado: variantes que solo leen `tb_base_system` |
| `pop_calendar.php` | 年間カレンダー | `admin.reservas.pop-calendar` | `Re\ReservationListController@popCalendar` | — | pendiente (popup del legacy; Carbon) |
| `print/yoyaku_list_print.php` | 予約一覧出力 | `admin.reservas.print` | `@print` | L `tb_customer`, `tb_staff`, `tb_yoyaku`, `tb_yoyaku_koumoku_master`, `tb_yoyaku_uketsuke_day` | pendiente |
| `print/csv_download.php`, `csv_file_out.php`, `csv_file_del.php` | (CSV 予約) | `admin.reservas.csv` | `@csv` | L `tb_customer`, `tb_staff`, `tb_yoyaku_koumoku_master`, `tb_yoyaku` | pendiente (StreamedResponse SJIS-win, sin Flash) |
| `yoyakuPrint.php`, `ajaxYoyakuPrint.php` | 予約プリント | `admin.reservas.printer` (+`.poll`) | `Re\PrinterController` | L `tb_customer`, `tb_staff`, `tb_yoyaku*`; E `tb_yoyaku.print_flg` (`'1'` → `'2'`) | pendiente (sondeo como el legacy) |
| `related_customer.php`, `ajax_related_customer.php` | 顧客関連づけ | `admin.reservas.related-customer` (+`.store`) | `Re\ReservationController@relatedCustomer` | L `tb_yoyaku`, `tb_yoyaku_uketsuke_day`; E `tb_yoyaku.tb_customer_cd` | pendiente |

### 1.3 bis 予約管理 — `category3/reservation/` (cedido por S3)

Sistema de reserva simple, **independiente** de category9 en el legacy: tabla `tb_reserve` (sin franjas ni menús) con categorías por tienda `tb_reserve_name`. Se mantiene igual: mismas tablas y pantallas. Comparte con category9 solo la vista 予約一覧 (`yoyaku_list_calendar.php` lee `tb_reserve`).

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas `tb_*` | Estado |
|---|---|---|---|---|---|
| `reserve_customer_search.php` | 予約新規登録 / 顧客一覧・検索 | `admin.reservas.simple.customer-search` | `Re\SimpleReservationController@customerSearch` | L `tb_customer`, `tb_koumoku` | pendiente |
| `reserve_new.php`, `reserve_new_confirm.php`, `reserve_new_register.php`, `reserve_new_complete.php` | 予約新規登録 / 確認 / 完了 | `admin.reservas.simple.create/.confirm/.store/.complete` | `Re\SimpleReservationController` | L `tb_customer`, `tb_koumoku`, `tb_reserve_name`, `tb_shop`, `tb_staff`; E `tb_reserve`, `hatuban` | pendiente |
| `reserve_calendar.php` | 予約・来店一覧 | `admin.reservas.simple.calendar` | `@calendar` | L `tb_reserve`, `tb_shop`, `tb_shop_work` | pendiente |
| `reserve_list.php` | 予約一覧 | `admin.reservas.simple.index` | `@index` | L `tb_reserve`, `tb_customer`, `tb_shop` | pendiente |
| `reserve_detail.php` | 予約詳細 | `admin.reservas.simple.show` | `@show` | L `tb_reserve`, `tb_customer`, `tb_reserve_name`, `tb_shop`, `tb_staff` | pendiente |
| `reserve_change_status.php` | 予約情報 (状態変更) | `admin.reservas.simple.status` | `@status` | E `tb_reserve` (`reserve_flg` `'9'` cancelar / `'2'` confirmar, `raiten_flg = '1'`, `update`) | pendiente |
| `reserve_delete.php` | 予約情報 (削除) | `admin.reservas.simple.destroy` | `@destroy` | E `tb_reserve` | pendiente |
| `category_entry.php`, `category_register.php`, `category_delete.php` | 予約カテゴリ登録・変更 | `admin.reservas.simple-categories.*` | `Re\SimpleCategoryController` | L/E `tb_reserve_name`, `hatuban` | pendiente |
| `config.php`, `config_register.php` | 予約項目設定 | `admin.reservas.simple.config` | `Re\SimpleReservationController@config/updateConfig` | L/E `tb_koumoku` (`tb_name = 'tb_reserve'`) | pendiente |
| `ajax_category.php`, `ajax_staff.php`, `inc/check_select.php` | (AJAX) | `admin.reservas.simple.options.*` | `Re\OptionController` | L `tb_reserve_name`, `tb_staff` | pendiente |
| `model_reservation.php` | 予約機種一覧 | `admin.reservas.model-changes` | `Re\ModelChangeController@index` | L `tb_change_model`, `tb_customer`, `tb_shop`, `tb_customer_point` | pendiente (solo lista; nadie del repositorio escribe `tb_change_model`: S0 decidió migrar todo lo que tenía pantalla) |

### 1.4 Reserva web pública `r/` y `reserve/` (`routes/publico/reservas.php`)

`r/` es la versión **CRM** (lee `tb_yoyaku*` en la BD del CRM); `reserve/` es la versión **POS**: abre `$posConnect` por tienda, lee `tb_c2_catalog`/`tb_c2_category` del servidor POS y responde `DBサーバーに接続できません。` sin POS. Se migra el contrato de `r/` con su HTML, CSS (`css/main.css`, `css/loading.css`) y JS (`js/common.js`, `js/main.js`, `js/check.js`, Bootstrap 3.3.6 + bootstrap-dialog, iCheck, jQuery 1.11.3 de `/common/`). `reserve/*.php` es alias de las mismas rutas; la rama POS queda tras `PosGateway` sin implementar. URL literales con `.php`, sin `auth:admin` ni CSRF (el parámetro `sess` hace de control como en el legacy), `throttle` en los POST.

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas `tb_*` | Estado |
|---|---|---|---|---|---|
| `r/index.php` (+ `reserve/`) | Reserve | `GET /r/index.php` · `publico.reservas.index` | `Re\PublicReservationController@index` | L `tb_yoyaku_koumoku_master`, `tb_shop`, `admin_master` (`application_use_flg`, `shop_category_flg`), `contents_master`, `tb_manage_words` | pendiente |
| `r/check.php` (+ `reserve/`) | Reserve (確認) | `POST /r/check.php` | `@check` | L `tb_yoyaku_koumoku_master`, `tb_koumoku` | pendiente |
| `r/ajax_getScheduleData.php` (+ `reserve/`) | — | `GET /r/ajax_getScheduleData.php` | `@schedule` + `AvailabilityService` | L `tb_yoyaku_config`, `tb_shop_work`, `tb_yoyaku_uketsuke_day` | pendiente (JSON idéntico) |
| `r/ajax_getChoiceData.php` (+ `reserve/`) | — | `GET /r/ajax_getChoiceData.php` | `@choices` | L `tb_yoyaku_uketsuke_day`, `tb_yoyaku`, `tb_staff_works`, `tb_yoyaku_koumoku_master` | pendiente |
| `r/ajax_reserveRegister.php` (+ `reserve/`) | — | `POST /r/ajax_reserveRegister.php` | `@register` + `ReservationService` (`LOCK TABLE tb_yoyaku` → `lockForUpdate`) | E `tb_yoyaku` (`entry_type` `'100'`/`'101'`), `hatuban`; L `tb_customer` | pendiente (mismos textos de error) |
| `r/ajax_reserveDelete.php` (+ `reserve/`) | — | `POST /r/ajax_reserveDelete.php` | `@cancel` | E `tb_yoyaku.disp_flg = '9'` | pendiente (rama `tb_c2_*` de `reserve/` descartada) |
| `r/getReservationWaitTime.php` | — | `GET /r/getReservationWaitTime.php` | `@waitTime` | L `tb_yoyaku`, `tb_yoyaku_config` | pendiente |
| `r/class/Reserve.php`, `Timetable.php`, `reserve/class/Util.php`, `reserve/memo.md`, `css/js/img/plugin` | — | — | `ReservationService`, `AvailabilityService`, `TimetableService`; estáticos a `public/legacy/r/` | — | descartado como vista |

> `appview/reservation.php` y `appview/quick_reservation*.php` son de S5; usan `ReservationService` de S2. `numbered_ticket` **no existe en el backup** (`pgsql-schema.sql`): si S5 mantiene クイック予約, hace falta una migración `reservas_create_numbered_ticket` con las columnas del legacy (`cd`, `issued_date`, `issued_number`, `called_flg`, `called_date`, `adult`, `child`, `smoking_select`, `tb_yoyaku_cd`). Ver §5.

---

## 2. Datos: modelos sobre las tablas del legacy

Base común (de S1): `BaseModel` legacy (`$primaryKey = 'cd'`, `$timestamps = false`, `$guarded = []`), trait de borrado `disp_flg = '9'` con scope global, servicio `Hatuban::next('<koumoku>')` y modelos `Shop` (`tb_shop`), `Staff` (`tb_staff`), `Terminal` (`tb_terminal`), `AdminMaster` (`admin_master`), `ContentsMaster` (`contents_master`). Mientras S1 no los publica, S2 usa stubs en sus pruebas (sin crear tablas).

Las columnas `touroku_date`/`update_date` son epoch en texto: los modelos de S2 las rellenan con `time()` al crear/guardar (como el legacy) mediante un trait propio `App\Models\Concerns\LegacyTimestamps` si S1 no lo publica (pedido §5).

### 2.1 Modelos de S2 (clientes)

| Modelo | Tabla | Claves y columnas relevantes | Relaciones | Borrado | `hatuban` |
|---|---|---|---|---|---|
| `Customer` | `tb_customer` | `cd`; `id` (顧客番号, `createID`), `login_id`/`login_pass`, `tb_shop_cd`, `name`/`name_kana` (un campo, apellido y nombre separados por espacio), `kojin_*`, `houjin_*`, `kinmusaki_*`, `tantou_*`, `email1/2`, `tel`, `tel2`, `kojin_mobile`, `felica_no`, `touroku_kubun` (`'2'` socio), `mailmaga_flg` (`1`/`0`/`9`), `stop_yoyaku_reminder_mail_flg`, `shuki_heikin`, `last_raiten_date`, `kingaku_rank_now/pre`, `raiten_rank_now/pre`, `paid_member_flg*`, `line_id`, `line_blocked_flg`, `push_device_*` | `shop` (`tb_shop_cd`), `staff` (`tb_staff_cd`), `group` (`tb_customer_group_cd`, S3), `visitMotive` (`tb_raiten_douki_master_cd`), `referrer` (`shoukai_tb_customer_cd`), `subs`, `visits`, `kartes`, `photos`, `reservations` | `disp_flg = '9'` | `tb_customer` (según `createHatsuban`) |
| `CustomerSub` | `tb_customer_sub` | `tb_customer_cd`, `tb_search_cate_cd` (`-1` venta, `-2` gasto), `tb_search_form_cd`, `text_data` (`a<>b<>`), `float_data`, `int_data`, `form_title`, `category_inner_group` | `customer`, `category`, `field` | `disp_flg = 9` | `tb_customer_sub` |
| `CustomCategory` | `tb_search_cate` | `name`, `record_type` (`''` uno, `'1'` múltiple), `disp_column`, `serach_flg`, `hyouji_flg`, `category` (`'category1'`), `rank` | `fields` | `disp_flg = 9` | `tb_search_cate` |
| `CustomField` | `tb_search_form` | `tb_search_cate_cd`, `name`, `form_type` (15 tipos en japonés), `titles`/`data` (`<>`), `flds_row`, `flds_col`, `unit`, `style_class`, `comment`, `hissu`, `serach_flg`, `csv_flg`, `hyouji_flg` (`'1'`/`'2'`/`''`), `mail_word_flg`, `rank` | `category` | `disp_flg = 9` | `tb_search_form` |
| `Visit` | `tb_raiten_rireki` | `user_cd` (= `tb_customer.cd`), `user_id`, `shop_cd`, `raiten_time` (epoch), `raiten_hiduke` (`Y/m/d`), `stamp`, `customer_age`, `customer_sex`, `customer_job`, `manual_flg`, `tb_point_log_cd`, `tb_stamp_log_cd`, `tb_search_cate_cd`, `tb_customer_sub_inner_group` | `customer`, `shop`, `pointLog` (S3), `stampLog` (S3) | `disp_flg = 9` (null/''/1 = visible: scope con `coalesce`) | `raiten_rireki` |
| `VisitMotive` | `tb_raiten_douki_master` | `name`, `rank` | — | `disp_flg = 9` | `tb_raiten_douki_master` |
| `CustomerLevel` | `tb_customer_level` | `name`, `rank` | — | `disp_flg = 9` | `tb_customer_level` |
| `Karte` | `tb_karute` | `tb_customer_cd`, `tb_staff_cd`, `tb_shop_cd`, `tb_sejutsu_cate_cd`, `comment`, `pic_flg`, `uketsuke_date` (epoch) | `customer`, `serviceCategory` | `disp_flg = 9` | `tb_karute` |
| `ServiceCategory` | `tb_sejutsu_cate` | `name`, `tb_shop_cd` (null = todas) | — | `disp_flg = 9` | `tb_sejutsu_cate` |
| `CustomerPhoto` | `tb_photo_album` | `tb_customer_cd`, `comment`, `disp_date` (epoch), `pic_flg`, `hyouji_flg` | `customer` | `disp_flg = 9` | `tb_photo_album` |
| `ReportLink` | `tb_shukei_link` | `url`, `title`, `naiyou`, `rank` | — | `disp_flg` | — |
| `SchoolYearConfig`, `SchoolClassConfig` | `tb_schoolyear_config`, `tb_schoolclass_config` | `cate_name`, `cate_order` | — | `disp_flg = 9` | sí |
| `Job` | `tb_job` (solo lectura) | `name`, `rank` | — | — | — |
| `Prefecture` | `tb_pref` (solo lectura) | `area`, `chiiki`, `rank` | — | — | — |
| `Zip` | `zip` (solo lectura) | `zip`, `pref`, `city`, `town` (+ kana) | — | — | — |
| `ChangeModel` | `tb_change_model` (solo lectura) | `tb_customer_cd` (texto), `next_model`, `resevation_type` | `customer` | `disp_flg` | — |

Leídos de otros dueños: `CustomerGroup` (`tb_customer_group`), `AmountRank` (`tb_kingaku_rank_master`), `VisitRank` (`tb_raiten_rank_master`), `PointLog` (`tb_point_log`), `CustomerPoint` (`tb_customer_point`), `StampLog` (`tb_stamp_log`), `MyCoupon*` → **S3**; `FelicaCard` (`tb_customer_card`), `FelicaMode` (`tb_felica_mode`) → **S4** (S2 escribe la fila `cd = 2` desde カードリーダーモード設定); `UserCard` (`tb_user_card`), `PlayTime` (`tb_play_time`), `ShopWork` (`tb_shop_work`) → **S5**; `Koumoku` (`tb_koumoku`), `ManageWord` (`tb_manage_words`), `AdminMaster`, `ContentsMaster` → **S1**.

### 2.2 Modelos de S2 (ventas)

| Modelo | Tabla | Columnas relevantes | Borrado | `hatuban` |
|---|---|---|---|---|
| `SalesItem` | `sales_items` | `cd` (serial propio), `name`, `display_name`, `display_title`, `discription`, `discription_detail`, `is_exchangeable_of_point`, `number_of_points_exchanged`, `available_shop_cds` (`int[]`), `hyouji_flg`, `touroku_date`/`update_date` (int) | `disp_flg = 0` (no 9) | no (serial) |
| `ServiceMenu` | `tb_karute_menu` | `tb_shop_cd`, `name`, `kingaku`, `type` (`1` 技術, `2` 物販, `3` その他), `rank`, `hyouji_flg` | `disp_flg = 9` | `tb_karute_menu` |
| `Discount` | `tb_karute_waribiki` | `tb_shop_cd`, `name`, `value`, `type` (`1` %, `2` 円), `buai_flg` (`1` cuenta para comisión, `2` no), `rank`, `hyouji_flg` | `disp_flg = 9` | `tb_karute_waribiki` |
| `Sale` / `Expense` | `tb_customer_sub` con scope `tb_search_cate_cd = -1` / `-2` | filas por campo agrupadas en `category_inner_group` (nº de cuenta: máximo + 1 por cliente); la correspondencia campo ↔ significado se fija al implementar V1 leyendo `karute_new_register.php`/`keihi_register.php` | `disp_flg = 9` | `tb_customer_sub` |

### 2.3 Modelos de S2 (reservas) — contrato `Reservation` para S3

| Modelo | Tabla | Columnas relevantes | Relaciones | Borrado | `hatuban` |
|---|---|---|---|---|---|
| **`Reservation`** | `tb_yoyaku` | `cd`, `tb_yoyaku_uketsuke_day_cd`, `tb_customer_cd` (null = no socio), `start_time`/`end_time` (integer, segundos desde las 00:00 del día de la franja; pueden pasar de 86400), `name`, `name_kana`, `tel`, `mail`, `people`, `base_tb_yoyaku_koumoku_master_cd` (`'0'` = ninguno), `option_tb_yoyaku_koumoku_master_cd` (lista), `free_koumoku` (`rawurlencode` unidos por `<>`), `comment`, `mark`, `entry_type` (texto: `''` admin, `'1'`…`'6'`, `'100'`/`'101'` r/, `'201'` クイック予約), `print_flg` (`'1'` pendiente, `'2'` impreso), `pos_menu*` (POS, solo lectura), `hyouji_flg`, `touroku_date`/`update_date` (texto epoch) | `slot` (`tb_yoyaku_uketsuke_day_cd`) → `staff`, fecha y tienda vía `tb_staff.shop_cd`; `customer` | `disp_flg = '9'` (= cancelada) | `tb_yoyaku` |
| `ReservationSlot` | `tb_yoyaku_uketsuke_day` | `tb_staff_cd`, `moushikomi_date` (texto `Y/m/d`), `start_time`/`end_time` (int, seg.), `start_break_time`/`end_break_time` (texto, seg.), `people`, `status_flg` (`''`/`0` 受付中, `1` 電話受付, `2` 受付終了, `3` 締切間近), `hyouji_flg` | `staff`, `reservations` | `disp_flg = 9` | `tb_yoyaku_uketsuke_day` |
| `ReservationMenu` | `tb_yoyaku_koumoku_master` | `tb_shop_cd`, `name`, `naiyou`, `shoyou_time`, `type_flg` (`1` base, `2` opción), `tb_yoyaku_koumoku_category_cd`, `tb_setsubi_cd`, `menu_end_time_flg`, `menu_end_time`, `rank`, `hyouji_flg` | `category`, `facility`, `staffWorks` | `disp_flg = 9` | `tb_yoyaku_koumoku_master` |
| `ReservationMenuCategory` | `tb_yoyaku_koumoku_category` | `name`, `rank`, `hyouji_flg` (global, sin tienda) | `menus` | `disp_flg = 9` | sí |
| `StaffWork` | `tb_staff_works` | `tb_staff_cd`, `tb_yoyaku_koumoku_master_cd`, `shoyou_time`, `mypage_flg` (`'1'` = oculto en Mi página), `hyouji_flg` | `menu`, `staff` | se reescribe entera (`MAX(cd)+1` como el legacy) | no |
| `Facility` | `tb_setsubi` | `tb_shop_cd`, `name`, `quantity` | — | `disp_flg = 9` | `tb_setsubi` |
| `FreeReservationRank` | `tb_free_yoyaku_rank` | `tb_shop_cd`, `tb_staff_cd`, `rank`, `hyouji_flg` | — | `disp_flg = 9` | sí |
| `ReservationConfig` | `tb_yoyaku_config` | `tb_shop_cd`, `start_time`/`end_time` (`HH:MM`), `shimekiri`, `shimekiri_time`, `uketuke_type`, `timeline_sort_type`, `automail`, `automail_time`, `setsubi_capacity`, `hide_price_flg` | `shop` | — | `tb_yoyaku_config` |
| **`SimpleReservation`** | `tb_reserve` | `reserve_time` (texto epoch), `reserve_cate` (→ `tb_reserve_name.cd`), `reserve_people`, `reserve_nomination` (staff), `user_cd` (cliente), `user_name`, `user_mail`, `user_tel`, `shop_cd`, `reserve_flg` (`''` 受付, `'2'` 確定, `'9'` キャンセル), `raiten_flg` (`'1'` 来店), `update` (epoch; columna `update`) | `category`, `customer`, `staff` | estado `'9'` | `tb_reserve` |
| `SimpleReservationCategory` | `tb_reserve_name` | `reserve_cate_name`, `tb_shop_cd` | — | `disp_flg = 9` | sí |

Configuración de reservas en tablas del legacy: `admin_master` (`yoyaku_time_interval`, `meyasu_jikan_flg`, `yoyaku_setsubi_flg`, `free_yoyaku_flg`), `contents_master` (`category = 'tb_c9_yoyaku'`, `field = free_yoyaku_*`, `int_data` = tienda), `tb_manage_words` (注釈) y `tb_koumoku` (formularios), con los modelos de S1.

**Contrato para S3 (recordatorio `trigger` 8 y `follow`):**
- `App\Models\Reservation` sobre `tb_yoyaku`, `$primaryKey = 'cd'`; columnas tal cual (tabla de arriba).
- Accesores: `reserved_at` (Carbon `Asia/Tokyo` = `slot.moushikomi_date` + `start_time` segundos), `ends_at`, `staff_cd` y `shop_cd` (de la franja / `tb_staff.shop_cd`), `display_name` (`name` o el del socio), `contact_mail` (`mail` o `tb_customer.email1`).
- Scope `Reservation::remindable(CarbonInterface $from, CarbonInterface $to)`: `disp_flg` distinto de `'9'`, `tb_customer_cd > 0`, socio con `coalesce(stop_yoyaku_reminder_mail_flg, '') <> '1'` y `disp_flg <> '9'`, unido a `tb_yoyaku_uketsuke_day` con `moushikomi_date::date` entre las fechas y `start_time` dentro del rango horario. Es la misma consulta que `category3/reminder/reminder_new_confirm.php` (`tb_yoyaku s1 JOIN tb_yoyaku_uketsuke_day s3`).
- Scope `Reservation::upcomingForCustomer()` (lo usa `follow_new_confirm.php`: socios con reserva desde hoy).
- `SimpleReservation` (`tb_reserve`) **no** entra en el recordatorio: el legacy no lo hace.

### 2.4 Tablas nuevas

Ninguna para las pantallas de S2. Única excepción posible: `numbered_ticket` (no está en el backup), solo si S5 mantiene クイック予約 (§1.4 y §5). `tb_related_features` tampoco existe, pero es obsoleta: no se crea.

---

## 2b. UI: HTML, CSS y JS del legacy por pantalla

Todas las pantallas de administración usan el layout de S1 (`inc/head.php` + `inc/header.php` + `left_navi1.php`/`left_navi2.php` + `inc/footer.php`, con `/system/css/import.css`, `/system/js/default.js`, `scrollsmoothly.js`, `iepngfix.js`). Cada Blade copia el `<body>` del archivo legacy con las mismas clases e ids y los textos japoneses literales; los `action`/`href` apuntan a las rutas Laravel y los `ajax_*` responden lo mismo que el legacy para no tocar sus JS.

| Grupo de pantallas | CSS propios | JS (legacy, desde `public/legacy/`) | Notas |
|---|---|---|---|
| Lista y búsqueda (`customer_list`, `search_set1`) | `/common/jquery-ui-1.11.4/jquery-ui.min.css`, `/system/category3/css/colorbox.css`, `/system/plugin/ui-datepicker/ui.datepicker.css`, `/system/plugin/myflexselect/myFlexSelect.css` | jQuery 1.11.2 (`$1112` en `noConflict`) + 1.3.2 del head, `jquery.colorbox.js` (category3), `ui.datepicker-ja.js`, `gcalendar-holidays.js`, `myFlexSelect.js`, `smatopiCommon.js`, `smatopiLine.js` (LINE配信, S3) | `AC_RunActiveContent.js` (Flash) **no**: los CSV pasan a enlaces de descarga |
| Alta, edición, confirmación (`inc/customer_new*`) | `/system/css/colorbox.css` | `jquery-1.3.2.min.js`, `jquery-ui-1.7.2.custom.min.js`, `jquery.maskedinput-1.2.2.js`, `jquery.colorbox.js`, `inc/js/user.js`, `inc/js/search_kaiin.js`, `jquery.alerts.js` | 住所検索 contra `admin.clientes.zip` (tabla `zip`) |
| Ficha y pestañas (`customer_detail`, `detail_tab*`) | `/system/css/colorbox.css` | ídem + `inc/js/raiten_list.js`, `inc/js/pic_upload.js`, `ajaxupload-3.5.js` | `send_tab(n)` carga cada pestaña por AJAX: ruta `admin.clientes.show.tab` que devuelve el parcial |
| Configuración (`form_config`, `search_cate_*`, `search_form_*`, grupos, motivos, rangos) | — | `jquery-1.3.2`, `jquery-ui-1.7.2`, `jquery.alerts.js`, `customer/js/user.js` | ▲ orden por POST como el legacy |
| ポイント履歴, 集計 (`shukei/*`), CSV de visitas | `ui.datepicker.css`, `myFlexSelect.css`, `category3/css/colorbox.css` | `ui.datepicker-ja.js`, `gcalendar-holidays.js`, `myFlexSelect.js`, `formToWizard.js`, `category3/js/jquery.colorbox.js` | |
| 美容 / カルテ / álbum | `myFlexSelect.css` | `ajaxupload-3.5.js`, `biyou/js/shopping_entry.js`, `myFlexSelect.js`, `jquery.maskedinput` | |
| `config/` (lector, tarjetas, escuela) | — | `ajaxupload-3.5.js`, `config/js/topics_entry.js`, jQuery UI 1.7.2 | |
| 入退室 (`entrance/`) | — | (solo el layout) | |
| Ventas (`karute_menu/*`) | `karute_menu/css/karute_frm.css`, `report.css`, `stafftotal.css`, `jquery.alerts.css`, `colorbox.css`, `ui.datepicker.css` | `jquery.alerts.js`, `ui.datepicker-ja.js`, `ajaxupload-3.5.js`, `karute_menu/js/user.js` | |
| Productos (`category2/item`) | — | jQuery del layout; editor CKEditor 4 del legacy hasta que S1 decida (PENDING 3.1); validación AJAX `ajax_edit` | |
| 予約表 y reservas (`category9/yoyaku/*`) | `yoyaku/css/time_table.css`, `css/popInline.css`, `colorbox.css` | `jquery-1.3.2`, `jquery-ui-1.7.2`, `yoyaku/js/timetable.js`, `yoyaku/js/search_kaiin.js`, `popInline.js`, `toast.js`, `jquery.colorbox.js`, `jquery.alerts.js`, `ajaxupload-3.5.js` | navegación propia `yoyaku_navi` (sin menú lateral); popups de 600 px como el legacy |
| Reserva simple (`category3/reservation/*`) | — | `jquery-1.3.2`, `jquery-ui-1.7.2`, `ui.datepicker-ja.js`, `popupwindow-1.8.js`, `ajaxupload-3.5.js`, `reservation/js/staff_entry.js` | |
| `r/` y `reserve/` (público) | `r/css/main.css`, `r/css/loading.css`, Bootstrap 3.3.6 (+ dialog), iCheck `skins/all.css` | jQuery 1.11.3, `bootstrap.min.js`, `bootstrap-dialog.min.js`, `icheck.min.js`, `r/js/common.js`, `r/js/main.js`, `r/js/check.js` | sin layout de administración |

Pedido a S1: que `public/legacy/` incluya además las carpetas de JS/CSS propias de los módulos (`system/category1/*/js|css`, `system/category9/yoyaku/js|css`, `system/category3/reservation/js`, `system/category3/{js,css}` (colorbox), `system/category3/smatopi/js`, `system/plugin/*`, `/common/*`, `r/{css,js,img}`), con la misma estructura relativa.

---

## 3. Servicios viejos → Laravel 13

| Legacy | Dónde | Reemplazo |
|---|---|---|
| `hatuban` + `SELECT … FOR UPDATE` | casi todos | servicio `Hatuban` de S1 (misma tabla, misma lógica, en `DB::transaction`) |
| `createID()` / `createHatsuban(shop_cd)` (`tb_customer.id`) | alta, CSV import | `CustomerNumberGenerator` que reproduce `createID` de `pos_system/inc/inc.php` (mismo formato) |
| `login_pass` en claro, `randomPass(4)` | alta, CSV import | se mantiene **como el legacy** (misma BD que producción, y `appview/member_login.php`, `ptag/`, `mpa_*` comparan en claro o con `md5`); ver Decisiones |
| `get_form()`, validación JS | formularios | Form Requests + la misma normalización (`mb_convert_kana`, solo dígitos); el JS del legacy se conserva |
| Token `add_record_verify` / `register_verify` | ventas, reservas | CSRF + redirect-after-post |
| CSV Flash y temporales en `uploads/` | `customer/`, `print/` | `StreamedResponse` SJIS-win por bloques (`lazy()`) |
| `system("find … rm")` | `category1/index.php` | tarea del Scheduler en `ClientesServiceProvider` sobre el Storage de subidas |
| `system()`/Imagick en `ajax_picupload`, `item/edit` | fotos, productos | `Storage` + `intervention/image`, mismas rutas `hp/uploads/...` (redirección genérica de S1) |
| `crontab` vía `cSendSchedule` | rangos | `clientes:asignar-rangos` en el Scheduler, leyendo `admin_master.*_rank_shori_*` |
| `raitenFunc` (`cUtility`) | 来店処理, `tool/api`, `mpa_setraiten`, CTI | `VisitService::register(Customer $customer, Shop $shop, string $origin)`: intervalo (`tb_felica_mode.interval_time` / configuración de la tienda), `tb_raiten_rireki` con edad/sexo/ocupación, `shuki_heikin`, `last_raiten_date`, y puntos/sellos con el servicio de S3 |
| Alta de socio (`appview/member_*`, `customer_new_register`) | S2, S5 | `CustomerRegistrationService::register(array $data, Shop $shop, string $origin)` |
| Búsqueda de socio (`ajax_search_kaiin`, CTI) | S2, S3, S5 | `CustomerSearch` (filtros de `search_set1` + `byPhone(string)` sobre `tel`, `tel2`, `kojin_mobile`, `kinmusaki_tel`) |
| Fotos (`mpa_albumUpdate`) | S5 | `CustomerPhotoService::store(Customer $customer, array $files, ?string $comment)` |
| `felica_no` | S4 | `CustomerFelicaService::attach(Customer, string $idm)` / `detach(Customer)` |
| `crm_send_mail` / `cYoyaku::sendYoyakuMail` | reservas | Mailable `ReservationMail` (síncrono, `QUEUE_CONNECTION=sync`); enlace firmado en lugar de `md5(login_id)`/`md5(login_pass)` |
| `mktime(…, is_dst)` | 予約, 入退室 | Carbon `Asia/Tokyo` |
| `file_get_contents` a sí mismo | `ajax_yoyaku_register` | llamada directa a `ReservationService::move` |
| `LOCK TABLE tb_yoyaku` | `r/` | `DB::transaction` + `lockForUpdate` sobre las reservas de la franja |
| `$posConnect`, `*.pos-s.net` | ficha, reservas, `reserve/` | `App\Services\Customers\PosGateway` (nulo, enlazado en `ClientesServiceProvider`) |
| API MobileShop | 有料会員 | `MobileShopClient` de S5 |
| `myKengen[1001]`, `[1033]`, pestañas | todo | Policies sobre los Gates de S1 |

---

## 4. Orden de implementación (1 punto ≈ media jornada)

| # | Tarea | Contenido | Depende de | Puntos |
|---|---|---|---|---|
| D1 | Modelos y servicios publicados | modelos de §2 con relaciones y scopes, `Reservation` + `remindable()` (**publicar a S3**), `VisitService::register`, `CustomerSearch::byPhone`, `CustomerFelicaService`, `CustomerRegistrationService`, `CustomerPhotoService`; factories sobre `tb_*` y pruebas | `BaseModel`/`Hatuban`/`Staff`/`Shop` de S1 (stubs mientras tanto) | 5 |
| C1 | Lista, búsqueda, alta/edición, ficha base | `customer_list`, `inc/customer_new*`, `customer_detail` + pestañas 1–4, borrar/recuperar | layout y estáticos (S1), Gates | 8 |
| C2 | Configuración de clientes | `form_config`, `search_cate_*`, `search_form_*`, grupos, motivos, rangos + `clientes:asignar-rangos` | C1 | 5 |
| C3 | Visitas, puntos y sellos | 来店処理, `kanren_record_*`, pestañas 8/9/10, ポイント履歴 | servicio de puntos/sellos (S3) | 6 |
| C4 | 追加情報 múltiple, カルテ, 美容, álbum, pestañas 5/6/7/11–14/custom | | C1 | 5 |
| C5 | CSV e importación | 3 formatos + 郵便CSV, `csv_import` | C1 | 4 |
| C6 | 集計 | `shukei/*`, 顧客情報集計, `customerDataOutPut` | C3 | 5 |
| C7 | Lector, tarjetas, 入退室, 有料会員, escuela, 顧客レベル | `config/*`, `entrance/*`, `customer_level_*` | S4 (FeliCa), S5 (`tb_play_time`, `MobileShopClient`) | 4 |
| V1 | Ventas internas | menús, descuentos, ventas, gastos | C1 | 5 |
| V2 | Informes de ventas | スタッフ別集計, 日報・週報・月報 | V1 | 2 |
| V3 | Productos (category2) | `sales_items` + imágenes | editor (S1) | 3 |
| R1 | Configuración de reservas | 予約設定 (4), 予約項目, カテゴリ, 設備, スタッフ施術管理, フリー予約 | layout (S1) | 5 |
| R2 | 受付日 | franjas (uno y varios días), estado | R1 | 3 |
| R3 | Flujo de reserva + correo + 顧客関連づけ + pestaña 12 | | R2 | 5 |
| R4 | 予約表 | día/semana/mes, por empleado, mover, listas laterales | R3 | 7 |
| R5 | Listados, impresión, CSV, impresora | | R3 | 3 |
| R6 | Reserva simple (category3) + 予約機種一覧 | | R1 | 4 |
| R7 | `r/` + alias `reserve/` | 7 endpoints, JSON idéntico, pruebas de contrato | R3 | 5 |
| | **Total** | | | **84** |

Orden: D1 → C1 → R1 → C2 → C3 → R2 → R3 → V1 → C4 → R4 → R7 → R6 → C5 → V2 → V3 → R5 → C6 → C7.

---

## 5. Pedidos

**A S1 (núcleo):**
1. `BaseModel` legacy (`cd`, sin timestamps de Laravel), trait/scope de `disp_flg = '9'`, servicio `Hatuban`, y un trait para `touroku_date`/`update_date` en epoch (si no, S2 lo declara en `app/Models/Concerns/` con permiso de S0).
2. Modelos `Shop` (`tb_shop`), `Staff` (`tb_staff`, con `kingaku_buai`), `Terminal`, `AdminMaster`, `ContentsMaster`, `Koumoku` (`tb_koumoku`), `ManageWord` (`tb_manage_words`).
3. Layout idéntico con `left_navi1`/`left_navi2` y la variante sin menú de `yoyaku_navi`; estáticos de §2b en `public/legacy/`.
4. Gates equivalentes a `myKengen[1001]` (clientes), `[1033]` (ventas), permisos por pestaña de la ficha y de reservas.
5. Helper CSV SJIS-win; paquete `intervention/image`; decisión sobre CKEditor 4.
6. Redirección `GET /hp/uploads/{path}` a Storage (fotos de clientes y productos).

**A S3 (promoción):** servicio de puntos y sellos sobre `tb_point_log`/`tb_customer_point`/`tb_stamp_log`/`tb_customer.stamp_count` que `VisitService` llama (equivalente a la parte de puntos y sellos de `raitenFunc`) y que permita anular movimientos ligados a una visita (`kanren_record_*`); modelos `CustomerGroup`, `AmountRank`, `VisitRank`, `PointLog`, `StampLog`, `MyCoupon*`. Contrato de `Reservation` publicado en §2.3 (columnas reales de `tb_yoyaku`).

**A S4 (terminales):** modelos `FelicaCard` (`tb_customer_card`) y `FelicaMode` (`tb_felica_mode`); S2 escribe la fila `cd = 2` y expone `CustomerFelicaService` sobre `tb_customer.felica_no`. Tarjeta de papel: la relación vive solo en `url_transfer_data` (`ptag/`).

**A S5 (operaciones/integraciones):** modelos `PlayTime` (`tb_play_time`), `UserCard` (`tb_user_card`), `ShopWork` (`tb_shop_work`) y `MobileShopClient`. Decidir si se mantiene クイック予約: si sí, S2 crea `numbered_ticket` con las columnas del legacy (no está en el backup). Ya no hace falta mapeo inverso: S5 lee `tb_customer` tal cual.

**A S0:** confirmar que `CustomerFelicaService`, `VisitService`, `CustomerSearch`, `CustomerRegistrationService`, `CustomerPhotoService` y `ReservationService` son la API pública de S2 para los demás.
