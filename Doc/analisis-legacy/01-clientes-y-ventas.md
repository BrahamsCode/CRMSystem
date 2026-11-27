# 01 — Gestión de clientes (顧客管理) y ventas (販売管理)
> Fuente: C:\Users\User\VivaTech\crm2-php8 (rama master = código ya migrado a PHP 8.5.12). Generado el 2026-11-26. Destino: Laravel 13 + PHP 8.5.

Alcance: `public_html/system/category1/` (顧客管理, 212 PHP) y `public_html/system/category2/` (販売管理, 5 PHP). Se excluyen `hp/`, `admin/` y `category8`. Dependencia de `hp/`: solo `category2/item` lee y escribe imágenes en `hp/uploads/c2/item/` (la carpeta de subidas, no el código de `hp/`).

Definición de "vista real": pantalla o endpoint propio. No cuenta los archivos de 3 líneas que solo hacen `require_once('../inc/xxx.php')`, ni las copias duplicadas, ni el SWF/JS/CSS. Las pestañas de la ficha del cliente sí cuentan como vistas.

## Resumen

| Módulo/submódulo | Carpeta | Nº PHP | Nº vistas reales (pantallas) | Complejidad (S/M/L/XL) | Puntos (1 punto ≈ media jornada de un agente) |
|---|---|---|---|---|---|
| Inicio del módulo | `category1/index.php` | 1 | 1 | S | 0,5 |
| Clientes: listas, búsqueda, configuración | `category1/customer/` | 81 | 48 | XL | 16 |
| Ficha, alta, edición, pestañas, visitas, puntos, sellos | `category1/inc/` | 58 | 55 (40 + 15 pestañas) | XL | 18 |
| Ficha de servicios (美容) | `category1/biyou/` | 10 | 4 | S | 1 |
| Configuración inicial (tarjeta, FeliCa, calendario, escuela) | `category1/config/` | 12 | 12 | M | 4 |
| Entrada y salida | `category1/entrance/` | 2 | 2 | M | 2 |
| Ventas internas (売上, menús, descuentos, gastos, informes) | `category1/karute_menu/` | 20 | 20 | L | 8 |
| Rangos de clientes | `category1/rank/` | 12 | 12 | M | 4 |
| Agregaciones (集計) | `category1/shukei/` | 16 | 16 | L | 7 |
| Productos (商品情報管理) | `category2/` (`index.php` + `item/`) | 5 | 5 | M | 3 |
| **Total** | | **217** | **175** | | **63,5** |

Los puntos son el esfuerzo total de reimplementar cada submódulo. Lo que CRMSystem ya tiene (ver "Qué ya cubre CRMSystem") baja el pendiente real a unos 40 puntos.

---

## Inicio del módulo (`category1/index.php`)

Portada del módulo 顧客管理: imagen, contador de altas del día/mes (`inc/entry_counter.php`, con enlaces a `customer_list.php` ya filtrado) y menú lateral `inc/left_navi1.php` con permisos por `$kengen->myKengen[1001]` (clientes) y `[1033]` (ventas). Además limpia por `system("find … -mtime +1 -exec rm")` los archivos temporales de `customer/uploads/` (subidas de CSV).

| Archivo | Título (ja) | Tipo | Qué hace | Tablas BD | Notas |
|---|---|---|---|---|---|
| `index.php` | 顧客管理トップ | detalle | Portada, contador de altas, menú | L `tb_base_system` | Usa `system()` para borrar temporales: reemplazar por tarea programada |

Menú real del módulo (sirve de lista de pantallas de destino): 顧客新規登録, 顧客検索, 来店処理, 登録・検索項目設定, 顧客情報集計, ポイント管理, ポイント履歴, 顧客グループ設定, 初回来店動機項目設定, 顧客ランク設定, 顧客ランク振り分け設定, 顧客追加情報設定, CSVインポート, 入退室一覧, カードリーダーモード設定, カード管理, 売上データ作成, 売上データ削除, メニュー管理, 割引管理, 経費管理, スタッフ別集計, 日報・週報・月報.

---

## Clientes: listas, búsqueda y configuración (`category1/customer/`)

Qué hace: el personal busca clientes con filtros por cada campo (103 campos, incluidos los personalizados), ve la lista paginada, la exporta a CSV o la envía a 配信 (mensajes del módulo category3), y configura qué campos se muestran, buscan y salen en CSV. También administra grupos, motivos de primera visita, categorías y campos adicionales, ajustes de puntos e importación CSV.

De los 81 archivos, 17 son wrappers de 3 líneas que apuntan a `inc/` (listados en la tabla de `inc/`), 5 son copias de `inc/karute_*` y 5 son fragmentos de `customer/inc/`.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `customer_list.php` | 顧客一覧・検索 | lista | Buscador (`inc/search_set1.php`), lista paginada, 表示件数, enlace a ficha, CSV (3 variantes), 郵便CSV, 配信 (`この条件で配信する`, `LINE配信`) | L `tb_customer`, `tb_search_form`, `tb_search_cate`, `tb_raiten_rireki`, `tb_shop` | 823 líneas. Es el corazón del módulo. Con `display_errors` los avisos caían dentro del CSV (corregido) |
| `customer_search.php` | 来店処理 | alta | Registra una visita por número de socio | L/E `tb_customer`, `tb_raiten_rireki` (vía `inc/raiten_*`) | Mensaje "来店処理しました。" |
| `customer_new.php`, `customer_new_confirm.php`, `customer_new_register.php`, `customer_detail.php`, `customer_recover_confirm.php`, `customer_delete_register.php`, `del_record.php`, `kanren_*` (4), `add_record_*` (3), `photoalbum_*` (3) | (wrappers) | fragmento(include) | 3 líneas: `$window_type="ALL"; require_once('../inc/…')` | — | No se migran como vistas: son la misma lógica de `inc/` (ver esa sección). `kanren_record_confirm.php` exige `inc/kanren_record_confirm.php`, que no existe (roto, ver BROKEN-FILES) |
| `customer_new_complete.php` | 顧客登録情報 | confirmación | Pantalla final del alta con enlace a la ficha | L/E `tb_customer`, `tb_customer_sub`, `tb_search_form`, `tb_search_cate` | 523 líneas, versión paralela del alta; no está en el menú. Verificar si se usa antes de migrar |
| `form_config.php` / `form_config_register.php` | 表示・検索・CSV項目設定 (menú: 登録・検索項目設定) | config / registro(POST) | Matriz de 314 campos con casillas 必須 / 表示 / 検索 / CSV (`tb_search_form_disp_`, `_csv_`, `_search_`, `_hissu_`) | L/E `tb_search_form`; L `tb_search_cate`, `tb_customer`, `tb_koumoku` | Guardar sin cambios deja 314 campos idénticos |
| `search_cate_list.php` | 顧客追加情報設定 | lista | Lista de 9 categorías de información adicional con visibilidad y ▲ orden | L/E `tb_search_cate` | |
| `search_cate_new.php`, `search_cate_new_check.php`, `search_cate_new_up.php` | 顧客追加情報 (categoría) | alta / ajax / registro(POST) | Alta de categoría (con comprobación de nombre repetido) | E `tb_search_cate` | |
| `search_cate_update.php`, `search_cate_update_up.php` | 〃 | edición / registro(POST) | Renombrar, tipo (normal/múltiple), visibilidad | E `tb_search_cate` | Tras guardar usa `<meta Refresh>`; los avisos rompían la redirección |
| `search_cate_delete_up.php` | 〃 | borrado | Borra categoría, sus campos y valores | E `tb_search_cate`, `tb_search_form`, `tb_customer_sub` | Borrado lógico `disp_flg = 9` |
| `search_form_list.php` | 顧客追加情報 (campos) | lista | Campos de una categoría | L `tb_search_form`, `tb_search_cate` | |
| `search_form_new.php`, `search_form_new_check.php`, `search_form_new_up.php` | 〃 | alta / ajax / registro(POST) | Alta de campo, 15 tipos (`form_type`) y 10 tamaños (`style_class`) | E `tb_search_form` | |
| `search_form_update.php`, `search_form_update_up.php` | 〃 | edición / registro(POST) | Edita campo y sus opciones | E `tb_search_form` | |
| `search_form_delete_up.php` | 〃 | borrado | Borra campo y valores `tb_customer_sub` | E `tb_search_form`, `tb_customer_sub` | |
| `customer_group_category_new.php` / `_frm.php` / `_register.php` | 顧客グループ設定 | lista+alta / fragmento / registro(POST) | Alta, edición, ▲ orden y borrado de grupos | E `tb_customer_group`, `hatuban` | Borrado lógico `disp_flg = 9`. Numeración por `hatuban` |
| `customer_level_category_new.php` / `_frm.php` / `_register.php` | 顧客レベル | lista+alta / fragmento / registro(POST) | Misma mecánica para niveles (`tb_customer_level`) | E `tb_customer_level`, `hatuban` | Sin enlace en el menú: probablemente en desuso. Confirmar |
| `raiten_douki.php` / `_frm.php` / `_register.php` | 初回来店動機項目設定 | lista+alta / fragmento / registro(POST) | Alta, edición, ▲ y borrado de motivos | E `tb_raiten_douki_master`, `hatuban` | |
| `point_setting.php` / `point_setting_frm.php` / `point_setting_register.php` | ポイント管理 | config / fragmento / registro(POST) | Configuración de puntos por tienda y grupo, imágenes del マイページ | L/E `tb_point_setting`, `tb_c1_point_setting`, `contents_master` | La subida de imágenes usa `ajax_apliFileUpload.php` |
| `ajax_apliFileUpload.php` | (imágenes マイページ) | ajax | Sube/borra imágenes principales con `type=picMainUpload` | L/E `tb_c1_point_setting`, `contents_master` | Usa `system()`. Reemplazar por `Storage` |
| `point_history.php` | ポイント利用一覧 (menú: ポイント履歴) | lista / csv | Búsqueda por fechas, tienda, personal, terminal; CSV en Shift_JIS | L `tb_point_log`, `tb_customer`, `tb_shop`, `tb_staff`, `tb_terminal` | |
| `csv_import.php` | CSVインポート | alta (masivo) | Sube un CSV, valida y crea clientes con `createID()` y contraseña aleatoria `randomPass(4)` | L/E `tb_customer`; L `tb_staff` | 512 líneas, Shift_JIS. La clave inicial se guarda tal cual (ver Riesgos) |
| `csv_export.php`, `csv_from_js.php`, `user_csv.php` | CSV de clientes | csv | Exportan clientes en Shift_JIS; `csv_from_js` genera para la ventana Flash | L `tb_customer`, `tb_customer_group`, `tb_staff`, `tb_raiten_douki_master`, `tb_koumoku` | `csv_from_js.php` supera 30 s con 22.000 clientes; su comentario de 2013 dice que nadie lo usa. `user_csv.php` es un formulario huérfano que apunta a `csv_download.php` |
| `csv_download.php` + `csv_download/`, `csv_download_ex02.php` + `csv_download_ex02/`, `csv_download_template.php` + `csv_download_template/` | CSV (Flash) | csv / fragmento | Cada uno: wrapper + `csv_file_out.php` (genera) + `csv_file_del.php` (borra) + SWF/`.as`/`caurina` (Flash) | L `tb_customer`, `tb_search_form`, `tb_customer_sub`, `tb_customer_group`, `tb_staff` | Se agrupan: 3 vistas. El Flash ya no corre; reemplazo: StreamedResponse. `csv_file_out.php` supera 30 s |
| `customer_export.php` | (volcado de tabla) | csv | `COPY <tabla> TO` un archivo y lo descarga | L cualquier tabla | **No migrar.** Aunque ya exige sesión y valida el identificador, sigue siendo un volcado arbitrario y usa `host=localhost` con otra contraseña (`SIGNAGE_DB_PASSWORD`) |
| `customer_sumup_list.php`, `customer_sumup_list2.php` | 顧客情報集計 (lista) | lista | Cuadro de clientes por sexo y edad, por 業種 (`tb_job`) o por provincia (`tb_pref`) | L `tb_customer`, `tb_job`, `tb_pref`, `tb_raiten_rireki` | 1.438 y 1.285 líneas: dos versiones casi iguales. Migrar una sola |
| `customer_card_delete.php` | 顧客登録情報 UID関連付け解除 | borrado | Desvincula la tarjeta FeliCa del cliente | L/E `tb_customer`, `tb_customer_card`, `tb_user_card`, `tb_play_time` | Copia de `inc/customer_card_delete.php` |
| `karute_customer_search.php` | 顧客一覧・検索 (selector) | lista | Ventana para elegir cliente al crear una ficha 美容 | L `tb_customer`, `tb_koumoku` | Copia de `biyou/` |
| `karute_frm.php`, `karute_new.php`, `karute_register.php`, `karute_update.php` | カルテ | alta / edición / registro(POST) | Ficha de servicio por cliente | E `tb_karute`; L `tb_sejutsu_cate`, `tb_shop`, `tb_customer` | Copias de `inc/karute_*` y `biyou/karute_*` (3 copias de lo mismo) |
| `stamp_use_register.php` | (usar sello) | registro(POST) | Descuenta sellos del cliente | E `tb_customer_stamp_count` | Copia reducida de `inc/stamp_use_register.php` |
| `ajax_category.php`, `ajax_staff.php`, `zip.php` | (utilidades) | ajax | Combo de categorías por tienda, personal por tienda, búsqueda de dirección por código postal (tabla local `zip`) | L `tb_sejutsu_cate`, `tb_staff`, `zip` | `zip.php` ya lo cubre `PostalCodeController` + zipcloud |
| `inc/page.php`, `inc/raiten_rireki.php`, `inc/search_set1.php`, `inc/search_set1_sumup.php`, `inc/search_set2_sumup.php`, `js/*`, `uploads/` | (fragmentos) | fragmento(include) | Paginador, historial de visitas, formularios de búsqueda reutilizados | L `tb_customer`, `tb_koumoku`, `tb_shop`, `tb_staff`, `tb_reserve`, `tb_reserve_name`, `tb_raiten_rireki` | `search_set1.php` define el buscador de 103 campos |

Flujo principal:
1. Lista: `customer_list.php` (buscar, paginar) → ficha `inc/customer_detail.php`.
2. Alta: `customer_new.php` → `customer_new_confirm.php` → `customer_new_register.php` → ficha `?from=reg`.
3. Configuración: `form_config.php` define qué campos existen en alta, lista y CSV; `search_cate_*` y `search_form_*` crean campos extra que aparecen después en alta y buscador.
4. Salida: CSV (varias variantes) o 配信 (envío a `category3`).

Reglas de negocio no obvias:
- Borrado lógico: `disp_flg = 9` en clientes, grupos, categorías y campos. En `category2/item` el borrado es `disp_flg = 0`.
- Numeración de `cd` por la tabla `hatuban` (`koumoku = '…'`, columna `kazu`) con `SELECT … FOR UPDATE`; en Laravel pasa a `bigserial`/secuencias.
- El ID de socio (`login_id`) lo genera `createID($connect, 'tb_customer', shop_cd)` (definido en `pos_system/inc/inc.php`, fuera de este módulo).
- Los campos personalizados se guardan en `tb_customer_sub` (una fila por valor); `tb_search_cate_cd = -1` son ventas y `-2` gastos (ver `karute_menu`). Las categorías de tipo múltiple usan `category_inner_group`.
- Normalización al guardar: teléfonos y código postal solo dígitos, lecturas en katakana de ancho completo (`mb_convert_kana`), `get_form()` cambia `'` por `’` y borra comillas dobles. Un nombre con comilla se guarda como `O’’’’Neil` (doble escape, mismo resultado que en PHP 5; decisión pendiente en PENDING.md). No replicar el defecto.
- Los CSV salen en Shift_JIS; hay un CSV especial para correo postal con la dirección en tres líneas.
- Con los campos 必須 de `form_config` el alta valida por JavaScript; en Laravel van a `FormRequest`.

Servicios viejos y reemplazo:
- CSV con Flash (`sample.swf`, AC_RunActiveContent.js) y archivos temporales en `uploads/` → `StreamedResponse`/`response()->streamDownload()` con `mb_convert_encoding('SJIS-win')` y `Storage::temporary`. Ya existe `App\Services\Customers\CustomerCsvExporter`.
- `system("find … rm")` para limpiar temporales → comando programado `schedule->command(...)` o `Storage` con política de caducidad.
- jQuery UI accordion → componentes del diseño actual (ya resuelto en las vistas Blade existentes).
- `md5`/contraseña de socio en claro → `Hash::make` (ver Riesgos).
- Conexión a POS (`.pos-s.net`) desde `inc/` → ver sección de `inc/`.
- Permisos por `myKengen[1001]` → Policies/Gates.

---

## Ficha, alta, edición, pestañas, visitas, puntos y sellos (`category1/inc/`)

Qué hace: es el motor real del módulo. Contiene todo lo que `customer/` reutiliza por wrapper: alta y edición de un cliente (formulario de 1.187 y 1.809 líneas), la ficha con sus pestañas, historial de visitas, puntos, sellos, álbum, カルテ, consultas a la caja POS, registro de visitas y borrado/recuperación de clientes.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `customer_new.php` | 顧客新規登録 | alta | Formulario completo: datos personales/empresa, dirección con 住所検索, grupo, motivo, personal asignado, campos personalizados | L `tb_customer`, `tb_customer_sub`, `tb_search_form`, `tb_search_cate`, `tb_shop`, `tb_staff`, `contents_master`; E `hatuban` | 1.187 líneas. También sirve de edición |
| `customer_new_confirm.php` | 顧客登録情報 (確認) | confirmación | Valida y muestra para confirmar | L `tb_customer`, `tb_c1_customer`, `tb_search_form`, `tb_staff` | 1.809 líneas, el archivo más grande del módulo |
| `customer_new_register.php` | 顧客登録情報 | registro(POST) | Inserta/actualiza `tb_customer` y `tb_customer_sub`; redirige a la ficha | E `tb_customer`, `tb_customer_sub` | Abre además `pg_connect` propio |
| `customer_detail.php` | 顧客登録情報 | detalle | Ficha con pestañas, ver abajo | L `tb_customer`, `tb_search_cate`, `tb_shop`, `admin_master`, `contents_master` | Permisos por pestaña según `myKengen` |
| `customer_recover_confirm.php` | 顧客登録情報 | confirmación | Recupera un cliente borrado | L/E `tb_customer`, `tb_shop` | |
| `customer_delete_register.php` | 顧客登録情報 | borrado | Borrado lógico del cliente y de sus datos | E `tb_customer`, `tb_customer_sub`, `tb_user_card`, `tb_play_time` | `disp_flg = 9`; libera la tarjeta |
| `customer_card_delete.php` | UID関連付け解除 | borrado | Quita `felica_no` | E `tb_customer`, `tb_customer_card`, `tb_user_card`, `tb_play_time` | |
| `customer_window_close.php` | 顧客登録情報 | detalle | Cierra la ventana emergente tras guardar | — | |
| `add_record_new.php`, `add_record_confirm.php`, `add_record_register.php` | 顧客登録情報 (追加情報) | alta / confirmación / registro(POST) | Alta de una fila de categoría "múltiple" (p. ej. mascota) | L/E `tb_customer_sub`; L `tb_search_form`, `tb_search_cate` | |
| `del_record.php` | 顧客登録情報 | borrado | Borra una fila de categoría múltiple | E `tb_customer_sub` | |
| `kanren_record_new.php`, `kanren_record_register.php`, `kanren_del_record.php` | 顧客登録情報 (来店履歴の追加・修正) | alta+edición / registro(POST) / borrado | Añade, corrige o borra una visita y reajusta puntos, sellos y estadísticas | E `tb_raiten_rireki`, `tb_point_log`, `tb_stamp_log`, `tb_customer_point`, `tb_related_features`, `tb_customer_sub`, `tb_customer`; L `tb_staff`, `tb_shop` | 1.026 y 646 líneas. La lógica más delicada (transaccional con `FOR UPDATE`) |
| `raiten_register.php`, `raiten_function.php` | (registrar visita) | registro(POST) / fragmento | Crea `tb_raiten_rireki`, calcula `shuki_heikin` y `last_raiten_date`; borra visita con `disp_flg = 9` | E `tb_raiten_rireki`, `tb_customer`, `tb_customer_sub`, `hatuban` | Base de la pantalla 来店処理 |
| `point_register.php`, `ajax_point_register.php` | (ポイント) | registro(POST) / ajax | Suma o resta puntos manuales | E `tb_customer_point`, `tb_point_log` | |
| `stamp_register.php`, `stamp_use_register.php` | (スタンプ) | registro(POST) | Suma sellos, emite cupón al llegar a la meta, o usa sellos | E `tb_customer_stamp_count`, `tb_stamp_log`; L `tb_mycoupon_master`, `tb_stamp_oshirase_coupon` | |
| `karute_frm.php`, `karute_new.php`, `karute_register.php`, `karute_update.php` | カルテ | alta / edición / registro(POST) | Ficha por cliente (número `hatuban`, categoría de servicio, personal) | E `tb_karute`, `hatuban`; L `tb_sejutsu_cate`, `tb_staff`, `tb_shop` | Forma parte de la pestaña カルテ |
| `photoalbum_new.php`, `photoalbum_frm.php`, `photoalbum_register.php`, `photoalbum_update.php`, `photoalbum_delete.php` | マイアルバム | alta / edición / borrado | Sube fotos del cliente | E `tb_photo_album`, `hatuban` | Subida con `system()` (en `ajax_picupload.php`) |
| `ajax_picupload.php` | (subida de foto) | ajax | Recibe y redimensiona imagen | — | Usa `system()`. Reemplazar por `Storage` + Intervention/Imagick |
| `ajax_changePaidMemberFlg.php` | (有料会員) | ajax | Cambia el estado de socio de pago llamando a la app móvil | L `tb_customer`; API MobileShop (`fetchToken`, `sendUnpaidMemberStatus`) | Integración externa |
| `ajax_connectPosData.php`, `ajax_connectPosKoubaiData.php`, `ajax_sendPosKoubaiMemoData.php` | (購買履歴) | ajax | Consultan o envían el historial de compras al servidor POS de la tienda por HTTP | — (remoto) | `http://<tienda>.pos-s.net/pos_system/tool/getCustomerKoubaiData/…` sin TLS y sin autenticación; ver Riesgos |
| `ajax_search_kaiin.php`, `ajax_category.php`, `ajax_raiten_list.php`, `ajax_raiten_time_select.php`, `ajax_shop_point_config.php`, `ajax_shop_stamp_config.php` | (utilidades) | ajax | Buscar socio por número/nombre, combos, lista de visitas, configuración de puntos/sellos de la tienda | L `tb_customer`, `tb_raiten_rireki`, `tb_shop`, `tb_sejutsu_cate` | |
| `detail_tab.php` | 顧客情報 (pestaña base) | detalle | Datos básicos, rango actual y anterior | L `tb_kingaku_rank_master`, `tb_raiten_rank_master`, `tb_staff` | |
| `detail_tab2.php` | 個人情報/法人情報 | detalle | Datos de persona o empresa | L `tb_customer` | |
| `detail_tab3.php` | 販促用情報 | detalle | Grupo, motivo de primera visita | L `tb_customer_group`, `tb_raiten_douki_master` | |
| `detail_tab4.php` | 来店履歴 | detalle | Lista de visitas | L `tb_raiten_rireki`, `tb_shop` | |
| `detail_tab5.php` | カルテ | detalle | Lista de カルテ | L `tb_karute`, `tb_sejutsu_cate` | |
| `detail_tab6.php` | マイクーポン | detalle | Cupones del cliente y usados | L `tb_mycoupon`, `tb_mycoupon_master`, `tb_mycoupon_used` | Tablas de promociones: ya existen `coupons` y `coupon_customer` |
| `detail_tab7.php` | マイアルバム | detalle | Álbum con vista previa | L `tb_photo_album` | |
| `detail_tab8.php` | ポイント | detalle | Historial de puntos | L `tb_point_log`, `tb_staff`, `tb_terminal` | |
| `detail_tab9.php` | 関連情報 (visitas con puntos, sellos y campos) | detalle | Visitas con sus puntos, sellos y campos asociados | L `tb_related_features`, `tb_raiten_rireki`, `tb_point_log`, `tb_stamp_log`, `tb_customer_sub` | 688 líneas. Edición desde `kanren_record_*` |
| `detail_tab10.php` | スタンプ | detalle | Historial de sellos | L `tb_stamp_log`, `tb_staff`, `tb_terminal` | |
| `detail_tab11.php` | 購買履歴 | detalle | Compras (de caja y POS) | L `tb_order_sub`, `tb_customer_sub`, `tb_search_form` | |
| `detail_tab12.php` | 予約状況 | detalle | Reservas del cliente | L `tb_yoyaku`, `tb_yoyaku_koumoku_master`, `tb_staff_works` | Depende del módulo de reservas |
| `detail_tab13.php` | 紹介実績 | detalle | Clientes referidos | L `tb_raiten_rireki` | |
| `detail_tab14.php` | 有料会員状況 | detalle | Estado de socio de pago (API móvil) | API MobileShop | |
| `detail_tabcustom.php` | (categoría adicional) | detalle | Una pestaña por categoría de información adicional | L `tb_customer_sub`, `tb_search_form` | |
| `search_set2.php`, `page_control.php` | (buscador de productos, paginador) | fragmento(include) | Filtros de catálogo y paginación | L `tb_c2_*` | `tb_c2_*` fueron borradas en 2020 (MISSING-TABLES) |

Flujo principal:
1. Alta: `customer_new` → `customer_new_confirm` → `customer_new_register` → `customer_detail?from=reg`.
2. Ficha: `customer_detail` carga 18 pestañas por `send_tab(n)`; cada pestaña es un `detail_tabN.php` incluido según permisos.
3. Visita: `customer_search` (来店処理) → `raiten_register` → actualiza `tb_raiten_rireki`, puntos y sellos → `shuki_heikin`, `last_raiten_date`.
4. Corrección de una visita: pestaña 9 → `kanren_record_new` → `kanren_record_register` (recalcula todo en una transacción).
5. Borrado: `customer_delete_register` marca `disp_flg = 9` y libera la tarjeta; `customer_recover_confirm` la revierte.

Reglas de negocio no obvias:
- Cada visita (`tb_raiten_rireki`) puede enlazar con un movimiento de puntos (`tb_point_log_cd`) y uno de sellos (`tb_stamp_log_cd`) mediante `tb_related_features`; al corregir o borrar la visita se corrigen los tres.
- `tb_customer_point` guarda el saldo y la caducidad (`point_yukoukigen`); los sellos tienen `tb_customer_stamp_count` y, al llegar a la meta, emiten un cupón (`tb_stamp_oshirase_coupon`).
- `shuki_heikin` (ciclo medio de visita) y `last_raiten_date` se recalculan tras cada alta, edición o borrado de visita.
- El día de negocio: `BUSINESS_START_TIME = 5` (antes de las 5 h cuenta como el día anterior; hoy comentado en `karute_new_register`, y `raiten_time_select` lo usa).
- El doble clic se evita con `$_SESSION['add_record_verify']` (token de envío): en Laravel, ya lo cubre el token CSRF más `Idempotency`/redirect-after-post.
- `tb_customer_sub.category_inner_group` agrupa filas de una misma "ficha" de categoría múltiple.

Servicios viejos y reemplazo:
- Consultas HTTP a `*.pos-s.net` con `fopen` sin TLS → `Http::withToken()->timeout()->retry()` contra HTTPS, con la URL por tienda en config (`shops.pos_host`). Cola (`Job`) para `sendPosKoubaiMemoData`.
- API MobileShop (`CustomerForMobileShop`, `Constant::DOMAIN_LIST`) → cliente HTTP propio en `app/Services/MobileShop` con `.env`.
- `system()` en subida de fotos → `Storage::putFile` + `Intervention\Image` (o Imagick).
- Cierres de ventana por JS (`customer_window_close`) → redirección normal; el diseño nuevo no usa ventanas emergentes.
- Permisos por pestaña (`myKengen`) → Policy `CustomerPolicy` con métodos por sección.

---

## Ficha de servicios 美容 (`category1/biyou/`)

Qué hace: variante de カルテ para salones de belleza: categorías de servicio (施術カテゴリー, 予約カテゴリー管理) y ficha del cliente. Casi todo (`karute_*`, `ajax_category`, `karute_customer_search`) es copia exacta de `inc/` y `customer/`.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD | Notas |
|---|---|---|---|---|---|
| `sejutsu_cate.php` | 予約カテゴリー管理 | lista | Lista y formulario de categorías por tienda | L/E `tb_sejutsu_cate`, `tb_shop` | No está en el menú |
| `sejutsu_cate_register.php` | 施術カテゴリー登録 | registro(POST) | Alta/edición | E `tb_sejutsu_cate`, `hatuban` | |
| `sejutsu_cate_delete.php` | 施術カテゴリー削除 | borrado | Borrado lógico | E `tb_sejutsu_cate` | |
| `karute_detail.php` | カルテ詳細 | detalle | Detalle de una ficha | L `tb_karute`, `tb_customer`, `tb_sejutsu_cate` | Único `karute_*` que no se repite |
| `karute_frm.php`, `karute_new.php`, `karute_register.php`, `karute_update.php`, `karute_customer_search.php`, `ajax_category.php` | (copias) | fragmento(include) | Idénticos a `inc/` y `customer/` | — | No migrar: se usa la versión de `inc/` |

Flujo: lista de categorías → alta → registro. Sin enlace en el menú: confirmar si el cliente de Japón usa este submódulo antes de migrarlo.

---

## Configuración inicial (`category1/config/`)

Qué hace: parámetros de lector de tarjetas FeliCa (modo de operación, intervalo), asignación de tarjetas a clientes o personal, calendario de días laborables por tienda y listas de curso y clase (para negocios tipo escuela) con un texto de correo.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD | Notas |
|---|---|---|---|---|---|
| `felica_mode.php` | カードリーダーモード設定 (初期設定管理) | config | Modo (運用 / 登録) e intervalo mínimo entre lecturas | L/E `tb_felica_mode` (fila `cd = 2`), `tb_customer`, `tb_user_card` | Si la fila `cd = 2` no existe no guarda ni avisa (ver Riesgos). En CRMSystem es `shops.visit_interval_seconds` |
| `card_new.php` | カード管理 | config | Pasa a modo registro, asigna una tarjeta a un cliente o personal | L/E `tb_user_card`, `tb_customer`, `tb_play_time`, `tb_felica_mode` | 450 líneas. Necesita el lector real |
| `card_staff_select.php` | (selector de personal) | ajax | Combo de personal para asignar tarjeta | L `tb_staff` | |
| `shop_work_calendar.php` | 営業日設定 | config | Calendario de días abiertos por tienda | L/E `tb_shop_work`, `hatuban` | Comparte tabla con `entrance/working_calendar`. Usa `mktime()` con 7 argumentos (PHP85-REPLACEMENTS) |
| `mail_comment.php`, `mail_comment_register.php` | (comentario de correo) | config / registro(POST) | Texto de correo por curso | L/E `tb_felica_mode` (columna de texto), L `tb_schoolyear` | Sin enlace en el menú |
| `schoolyear_config.php`, `schoolyear_register.php`, `schoolyear_delete.php` | (学年設定) | lista+alta / registro(POST) / borrado | Alta, edición y borrado de cursos | E `tb_schoolyear_config`, `hatuban` | Para academias; sin enlace en el menú |
| `schoolclass_config.php`, `schoolclass_register.php`, `schoolclass_delete.php` | (クラス設定) | lista+alta / registro(POST) / borrado | Ídem para clases | E `tb_schoolclass_config`, `hatuban` | Sin enlace en el menú |

Flujo: `felica_mode` y `card_new` solo se usan al instalar un lector; el resto no está en el menú: confirmar con Japón si `school*` y `mail_comment` están en uso.

Servicios viejos: lector FeliCa (hardware y `hp/mailsyori/felica_detail.php`, fuera de este grupo, sin autenticación de terminal; PENDING 4.4). Reemplazo: ya existe `Terminal` y `Visit`; las lecturas llegan por API con token por terminal (Sanctum) en lugar de GET sin autenticar.

---

## Entrada y salida (`category1/entrance/`)

Qué hace: calendario mensual de entrada y salida (入退室) y lista de cada día, alimentada por el lector FeliCa. Muestra el nombre y las horas por persona.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD | Notas |
|---|---|---|---|---|---|
| `working_calendar.php` | 営業日設定 / 入退室一覧 (calendario) | detalle | Calendario por mes, enlace a la lista de cada día | L `tb_shop`, `tb_shop_work`; E `hatuban` | Menú: 入退室一覧. Sin datos en local (las lecturas llegan del lector). Los botones "1970年1月へ" son una salida rota de `master` (PENDING 10) |
| `work_list.php` | 入退室一覧 | lista | Lista de un día con entrada, salida y paginación | L `tb_play_time`, `tb_customer`, `tb_shop` | 741 líneas |

Flujo: calendario → día → lista. Reemplazo: tabla `attendances`/`visits` (según el diseño del módulo de personal) y calendario en Blade; `mktime` de 7 argumentos se sustituye por Carbon.

---

## Ventas internas (`category1/karute_menu/`)

Qué hace: el personal registra una venta por cliente con menú, personal y descuento, administra el catálogo de menús y descuentos, anota gastos y consulta informes por personal y por periodo. Complementa a `category2/item` (productos).

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `karute_new.php` | 売上データ作成 | alta | Pantalla de inicio: tabla de menús y búsqueda del socio | L `tb_karute_menu` | |
| `karute_new_frm.php` | 売上データ作成 | alta | Formulario: socio (AJAX), personal, menús, descuento manual (p. ej. 10 %), total, comisión | L `tb_customer`, `tb_karute_menu`, `tb_karute_waribiki`, `tb_staff` | 1.152 líneas |
| `karute_new_register.php` | 売上データ作成 | registro(POST) | Guarda la venta en `tb_customer_sub` con `tb_search_cate_cd = -1` y calcula la comisión | E `tb_customer_sub`; `hatuban` | Evita doble envío con `add_record_verify` |
| `karute_delete_list.php` | 売上追加・修正・削除 (menú: 売上データ削除) | lista | Lista por tienda, fechas, personal; botón borrar | L `tb_customer_sub`, `tb_customer`, `tb_staff`, `tb_karute_waribiki` | 456 líneas |
| `karute_delete_register.php` | 売上追加・修正・削除 | borrado | Borra la venta | E `tb_customer_sub` (`tb_search_cate_cd = -1`) | Borrado lógico |
| `karute_menu.php`, `karute_menu_register.php` | メニュー管理 | lista+alta / registro(POST) | Alta, edición y borrado de menús | E `tb_karute_menu`, `hatuban` | Precios por tienda |
| `karute_waribiki.php`, `karute_waribiki_register.php` | 割引管理 | lista+alta / registro(POST) | Alta, edición y borrado de descuentos | E `tb_karute_waribiki`, `hatuban` | |
| `keihi.php`, `keihi_register.php`, `keihi_delete.php` | 経費管理 | lista+alta / registro(POST) / borrado | Gastos en `tb_customer_sub` con `tb_search_cate_cd = -2` | E `tb_customer_sub` | Se reutiliza la tabla de campos personalizados |
| `staff_total.php`, `ajax_staffTotal.php` | スタッフ別集計 | lista / ajax | Elige tienda (personal por AJAX), personal y rango de fechas; total por persona | L `tb_customer_sub`, `tb_staff`, `tb_customer` | El límite "最大60日分" nunca se aplicó (PENDING) |
| `report.php`, `ajax_report.php` | 日報・週報・月報 | lista / ajax | Informe por día, semana o mes, una tienda o todas | L `tb_customer_sub`, `tb_shop`, `tb_staff` | Mismo defecto del límite de 60 días |
| `ajax_karute_menu_list.php`, `ajax_karute_waribiki_list.php`, `ajax_search_kaiin.php`, `ajax_staff.php` | (utilidades) | ajax | Menús y descuentos por tienda, buscar socio, personal | L `tb_karute_menu`, `tb_karute_waribiki`, `tb_customer`, `tb_staff` | |
| `css/*` | | fragmento(include) | Estilos | | |

Flujo principal: `karute_new` → `karute_new_frm` → `karute_new_register` → aparece en el historial del día; corrección: `karute_delete_list` → `karute_delete_register`.

Reglas de negocio no obvias:
- Las ventas y los gastos viven en `tb_customer_sub`, mezcladas con los campos personalizados, distinguidas por `tb_search_cate_cd = -1` (venta) y `-2` (gasto). En Laravel es mejor una tabla `sales` y otra `expenses` (migrar los datos al cargar).
- `category_inner_group` = número de cuenta (請求番号): el siguiente es el máximo + 1 por cliente.
- Comisión (歩合): `commission = (int)(totalCommissionPrice * kingaku_buai / 100)`.
- Fecha de la venta = `update_date` enviada por el formulario (el cálculo con `BUSINESS_START_TIME` quedó comentado).
- Los descuentos se pueden aplicar manualmente (en %) o con un `tb_karute_waribiki`.

Servicios viejos y reemplazo: sin servicios externos. Todo el listado se resuelve con Eloquent y agregaciones SQL; los informes pasan a `Collection`/query builder con exportación CSV por `StreamedResponse`.

---

## Rangos de clientes (`category1/rank/`)

Qué hace: define rangos por importe acumulado (金額) y por número de visitas (来店), decide si son visibles en el マイページ del cliente y programa la tarea que reasigna rangos a todos los clientes.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD | Notas |
|---|---|---|---|---|---|
| `customer_rank_list.php` | 顧客ランク設定 | lista | Dos listas (金額, 来店), casilla de visibilidad en マイページ | L `tb_kingaku_rank_master`, `tb_raiten_rank_master` | La casilla usaba `attr('checked')`; ya corregido |
| `kingaku_rank_new.php`, `kingaku_rank_update.php` | 顧客ランク設定 (金額) | alta / edición | Alta, edición, borrado y ▲ de rangos por importe | E `tb_kingaku_rank_master` | `jyouken1`/`jyouken2` = mínimo y máximo |
| `raiten_rank_new.php`, `raiten_rank_update.php` | 顧客ランク設定 (来店) | alta / edición | Ídem por visitas | E `tb_raiten_rank_master` | |
| `customer_rank_frm.php`, `customer_rank_frm_register.php` | (formulario de rango) | fragmento / registro(POST) | Formulario compartido y su registro | E ambas tablas, `hatuban` | |
| `ajax_customer_rank_list.php` | (lista AJAX) | ajax | Recarga la lista de rangos | L ambas tablas | |
| `customer_rank_schedule.php`, `customer_rank_schedule_register.php` | 顧客ランク振り分け設定 | config / registro(POST) | Programa la tarea: periodo en días, día/hora de ejecución, ON/OFF | L/E `admin_master` (`kingaku_rank_shori_*`, `raiten_rank_shori_*`) | Escribe `crontab` mediante `cSendSchedule` (en `category3`). No corre en Docker |
| `setCustomerKingakuRank.php` | (tarea) | cron/CLI | Para cada cliente suma `kaikei` del periodo y asigna el rango más alto que cumpla; guarda el anterior en `kingaku_rank_pre` | L/E `tb_customer`; L `tb_kingaku_rank_master`, `tb_point_log`, `tb_stamp_log`, `admin_master` | Ejecutado por cron |
| `setCustomerRaitenRank.php` | (tarea) | cron/CLI | Igual por número de visitas | L/E `tb_customer`; L `tb_raiten_rank_master`, `tb_raiten_rireki` | Ídem |

Flujo: se crean los rangos → se programa el reparto → cada noche el cron ejecuta `setCustomer*Rank.php` (copia el rango actual a `*_pre` y reasigna).

Reglas de negocio:
- Solo cuentan rangos con `disp_flg = '1'` y `hyouji_flg = '1'`, ordenados por `rank DESC`.
- Rango por importe: suma `kaikei` de las visitas con `touroku_date` dentro de los últimos `kingaku_rank_shori_kikan` días y sin `disp_flg = '9'`; `jyouken1` ≤ suma < `jyouken2`.
- Se guarda el rango anterior en `kingaku_rank_pre`/`raiten_rank_pre` antes de reasignar (ver `customers.arp`/`vrp` en CRMSystem).
- La fecha de proceso admite el día 32 como "último día del mes" y un día de la semana.

Reemplazo: `Schedule::command('customers:assign-ranks')->dailyAt()` leyendo la hora y el periodo de `rank_schedules`; sin `crontab` ni `exec`. El modelo `RankSchedule` y `RankScheduleMode` ya existen.

---

## Agregaciones (`category1/shukei/`)

Qué hace: informes de la ventana "集計ツール": enlaces configurables a herramientas de agregación (`tb_shukei_link`), tablas de clientes por visitas, puntos y sellos agrupadas por edad/sexo/ocupación, y un generador de CSV de visitas.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD | Notas |
|---|---|---|---|---|---|
| `shukei_list.php` | 集計ツール | lista | Menú de herramientas desde `tb_shukei_link` | L `tb_shukei_link` | Menú: 顧客情報集計 |
| `model1.php`, `model1_search.php`, `model1_select.php` | (モデル集計 1) | lista / ajax / fragmento | Visitas por segmento (edad, sexo) | L `tb_raiten_rireki`, `tb_customer`, `tb_customer_sub` | `model1_select` es un selector |
| `model2.php`, `model2_search.php` | (モデル集計 2) | lista / ajax | Variante por otro segmento | Igual | |
| `point1.php`, `point1_search.php` | 【会計金額】集計 | lista / ajax | Importe de compra por segmento | L `tb_point_log`, `tb_raiten_rireki`, `tb_customer` | |
| `point2.php`, `point2_search.php` | 【ポイント】集計 | lista / ajax | Puntos por segmento | L `tb_point_log`, `tb_customer`, `tb_shop` | |
| `stamp1.php`, `stamp1_search.php` | 【会計金額】集計 (スタンプ) | lista / ajax | Sellos por segmento | L `tb_stamp_log`, `tb_raiten_rireki`, `tb_customer` | |
| `customerDataOutPut.php` | csv出力管理画面 (来店履歴抽出) | csv | Elige columnas y filtros, genera un CSV de clientes y visitas en Shift_JIS | L `tb_customer`, `tb_customer_point`, `tb_raiten_rireki`, `tb_search_form`, `tb_yoyaku_koumoku_master` | 1.802 líneas |
| `shukei_mail_new.php`, `shukei_mail_new_confirm.php`, `shukei_mail_new_send.php` | (配信) | alta / confirmación / correo | Delegan a `category3/inc/mail_frm.php` para enviar un correo a un grupo de la agregación | — | Pertenece al módulo de promociones (ya migrado) |
| `customer_sumup_list*.php` | (ver `customer/`) | | | | |

Flujo: `shukei_list` → herramienta → buscar → tabla; opcional 配信 hacia category3.

Reglas de negocio: las tablas agrupan por edad (`customer_age`, `customer_sex`, `customer_job` guardados en la visita `tb_raiten_rireki`, es decir, la edad al momento de la visita). Las búsquedas se limitan por tienda y por fecha.

Reemplazo: ya existe `StatisticController` (pantalla Estadísticas) que cubre sexo y edad. Para el resto, consultas con `GROUP BY` sobre `visits`, `point_movements`, `stamp_movements` y CSV con `CustomerCsvExporter`. 配信 ya lo cubre el módulo Promociones (`MessageController`) reutilizando `CustomerSearch`.

---

## Productos (`category2/`)

Qué hace: catálogo de productos de la tienda (商品情報管理): buscar, crear, editar con texto enriquecido e imagen, y decidir si se puede canjear por puntos y en qué tiendas se ofrece.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD | Notas |
|---|---|---|---|---|---|
| `index.php` | 販売管理トップ | detalle | Portada con el menú lateral `LeftNavi(2)` | L `tb_base_system` | |
| `item/list.php` | 商品管理 (商品検索・一覧) | lista | Buscador por 商品名 y 説明, lista paginada con miniatura, botón de borrar | L `sales_items`, `tb_shop` | `SalesItem::getList()`; filtra `disp_flg = 1` |
| `item/edit.php` | 商品新規登録 / 編集 | alta+edición | Nombre, nombre y título para mostrar, descripción y detalle (2 CKEditor), canje por puntos y cantidad, tiendas, imagen, 表示/非表示 | E `sales_items` (campos de `SalesItem`); L `tb_shop` | Sube imagen y crea versiones de 800 y 320 px con Imagick |
| `item/ajax_edit.php` | (validación) | ajax | Valida el formulario con `Valitron` | — | Reglas: obligatorios, `numeric` en `hyouji_flg`, `number_of_points_exchanged` solo si canjeable |
| `item/ajax_list_delete.php` | (borrado) | ajax | Marca `disp_flg = 0` | E `sales_items` | Borrado lógico |

Flujo: lista → alta o edición (validación AJAX) → registro → lista. El borrado es un botón de la lista.

Reglas de negocio:
- Borrado lógico con `disp_flg = 0` (no 9 como en category1).
- Si `is_exchangeable_of_point = 1`, `number_of_points_exchanged` es obligatorio y numérico.
- `available_shop_cds` (lista de tiendas) se guarda como texto con separadores y `SalesItem::makeArrAvailableShopCds()` lo convierte en array.
- Imagen en `hp/uploads/c2/item/<cd>/<cd>.jpg` (+ `_big`, `_small`).

Servicios viejos y reemplazo:
- CKEditor 4 (sin soporte) → TipTap/Quill/Trix (PENDING 3.1, licencia por decidir). Sanear el HTML con `mews/purifier`.
- Imagick con carpeta en `hp/uploads` → `Storage::disk('public')` con `Intervention\Image`; en producción la carpeta debe ser escribible por el servidor web.
- Valitron → `FormRequest`.
- Tabla `sales_items`: ya es un nombre estándar, solo hace falta pasarla a `snake_case` de CRMSystem (`products` ya existe en el estándar, ver abajo).

---

## Tablas de BD del grupo

Nota: el legacy usa el prefijo `tb_` y claves `cd`; la lista no incluye alias de variables (`tb_shop_array`, `tb_max`, etc., que son variables PHP y no tablas).

| Tabla | Para qué | Submódulos que la usan | Notas (columnas clave) |
|---|---|---|---|
| `tb_customer` | Clientes (~22.000 filas) | customer, inc, biyou, config, entrance, rank, shukei | `cd`, `login_id`, `login_pass`, `felica_no`, `kingaku_rank_now/pre`, `raiten_rank_now/pre`, `shuki_heikin`, `last_raiten_date`, `disp_flg` (9 = borrado). Sin restricciones de unicidad |
| `tb_customer_sub` | Valores de campos personalizados, ventas y gastos | customer, inc, karute_menu, shukei | `tb_customer_cd`, `tb_search_cate_cd` (-1 venta, -2 gasto), `tb_search_form_cd`, `category_inner_group` |
| `tb_search_cate` | Categorías de información adicional | customer, inc | `hyouji_flg`, tipo normal/múltiple |
| `tb_search_form` | Campos de cada categoría y de la tabla de clientes (314 en el modelo base) | customer, inc, shukei | `form_type` (15 tipos), `style_class`, flags 表示/検索/CSV/必須 |
| `tb_customer_group` | Grupos de clientes | customer, shukei | `disp_flg`, `rank` |
| `tb_customer_level` | Niveles de cliente (posible desuso) | customer | |
| `tb_raiten_douki_master` | Motivos de la primera visita | customer, inc | |
| `tb_raiten_rireki` | Visitas (historial) | customer, inc, shukei, rank | `raiten_time`, `raiten_hiduke`, `customer_age/sex/job`, `manual_flg`, `tb_point_log_cd`, `tb_stamp_log_cd`, `stamp` |
| `tb_related_features` | Enlace visita ↔ puntos ↔ sellos ↔ campos | inc | |
| `tb_point_setting`, `tb_c1_point_setting` | Configuración de puntos y del マイページ | customer | |
| `tb_customer_point` | Saldo de puntos y caducidad | inc, shukei | |
| `tb_point_log` | Movimientos de puntos | customer, inc, shukei, rank | |
| `tb_customer_stamp_count`, `tb_stamp_log`, `tb_stamp_oshirase_coupon` | Sellos y cupón de meta | inc, customer, shukei | |
| `tb_mycoupon`, `tb_mycoupon_master`, `tb_mycoupon_used` | Cupones | inc | Existen `coupons` y `coupon_customer` en CRMSystem |
| `tb_photo_album` | Álbum de fotos | inc | |
| `tb_karute`, `tb_sejutsu_cate` | Ficha de servicio y categorías | inc, biyou, customer | |
| `tb_kingaku_rank_master`, `tb_raiten_rank_master` | Rangos por importe y por visitas | rank, inc | `jyouken1`, `jyouken2`, `rank`, `hyouji_flg` |
| `tb_karute_menu`, `tb_karute_waribiki` | Menús y descuentos de ventas | karute_menu | |
| `tb_user_card`, `tb_customer_card`, `tb_felica_mode`, `tb_play_time` | Tarjetas FeliCa y entradas/salidas | config, entrance, inc | `tb_felica_mode.cd = 2` debe existir |
| `tb_shop`, `tb_shop_work` | Tiendas y días laborables | todos | |
| `tb_staff`, `tb_staff_group`, `tb_staff_works` | Personal | karute_menu, inc, customer | Compartida con personal/reservas (falta `employees` en CRMSystem) |
| `tb_terminal` | Terminales | inc, customer | Existe `terminals` |
| `tb_schoolyear_config`, `tb_schoolclass_config`, `tb_schoolyear` | Cursos y clases | config | Posible desuso |
| `tb_shukei_link` | Enlaces de la ventana de agregación | shukei | |
| `tb_yoyaku`, `tb_yoyaku_koumoku_master`, `tb_yoyaku_koumoku_category` | Reservas (solo lectura) | inc, shukei | Módulo de reservas |
| `tb_order_sub` | Compras de caja | inc | |
| `sales_items` | Productos | category2 | Modelo `CrmSystem\Sales\SalesItem` (Eloquent, clave `cd`) |
| `hatuban` | Contador de numeración (`koumoku`, `kazu`) | casi todos | Se reemplaza por `bigserial` |
| `admin_master` | Configuración global (rango, dominio) | rank, inc | Una fila (`cd = 1`) |
| `contents_master` | Imágenes y textos del マイページ | customer, inc | |
| `zip`, `tb_pref`, `tb_job`, `tb_base_system(_child/_grandchild)`, `tb_koumoku` | Tablas de apoyo (códigos postales, provincias, ocupaciones, menú, nombres de campos) | customer, inc | `zip` ya lo cubre zipcloud |
| `tb_c2_*` | Catálogo viejo | inc (`search_set2`) | **Borradas en 2020** (MISSING-TABLES): no migrar |

---

## Propuesta Laravel 13

### Qué ya cubre CRMSystem

El diseño de BD está en `Doc/database/TABLAS_MODULO_CLIENTES.md` (13 tablas más `customer_companies`, ya como migraciones) y hay controladores y vistas Blade. Cobertura de las vistas del legacy:

| Vista del legacy | Ya cubierta por | Estado |
|---|---|---|
| 顧客検索 (`customer_list`) | `CustomerController@index/search` + `Services\Customers\CustomerSearch`, vistas `index` y `search` | Cubierta (falta paginación por `表示件数` y los 103 filtros completos: verificar) |
| 顧客新規登録 (`inc/customer_new*`) | `create`/`store` (vista `create`), `PostalCodeController` | Cubierta; falta edición, confirmación y borrado (`update`, `destroy`, recuperar) |
| 顧客詳細 (`customer_detail`) | `CustomerController@show` (vista `show`) | Parcial: pestañas Puntos, Sellos, Álbum, Reservas, Referidos, Cupones quedan vacías |
| 登録・検索項目設定 (`form_config`) | `FieldSettingController` (vista `field-settings`) | Cubierta |
| 顧客追加情報設定 (`search_cate_*`, `search_form_*`) | `CustomCategoryController` y `CustomFieldController` (6 vistas) | Cubierta |
| 顧客グループ設定 | `CustomerGroupController` (`groups`) | Cubierta (solo `index` en las rutas; faltan alta, edición y borrado) |
| 初回来店動機項目設定 | `VisitMotiveController` (`visit-motives`) | Cubierta (ídem) |
| 顧客ランク設定 | `RankController` (`ranks`) | Cubierta (solo `index`) |
| 顧客ランク振り分け設定 | `RankScheduleController` (`rank-schedules`) | Cubierta; falta el comando programado que ejecuta la reasignación |
| 来店処理 | `VisitController` (`visits`) | Cubierta |
| 顧客情報集計 (parte sexo/edad) | `StatisticController` (`statistics`) | Parcial |
| カードリーダーモード設定 (intervalo) | `VisitIntervalController` (`shops.visit_interval_seconds`) | Cubierta (solo el intervalo; falta el modo 運用/登録) |
| CSV (`csv_export`, `csv_download*`) | `CustomerController@export` + `CustomerCsvExporter` | Cubierta (falta Shift_JIS y el CSV de correo postal: verificar) |
| 配信 desde la lista | Módulo Promociones (`MessageController`) | Cubierta fuera del grupo |
| ポイント管理, ポイント履歴, マイクーポン, スタンプ | Migraciones `point_tables`, `stamp_tables`, `coupons`; módulo Promociones | Parcial: faltan las pantallas de historial de puntos y la integración con la ficha |
| 売上管理 (`karute_menu`), 商品 (`category2`), 入退室, カード管理, `kanren_record_*`, `biyou`, `config/school*`, `shukei/*` (salvo estadísticas) | — | **No cubiertas** |

Modelos y enums reutilizables: `Customer`, `CustomerCompany`, `CustomerGroup`, `Rank`, `RankSchedule`, `VisitMotive`, `Visit`, `FieldSetting`, `CustomCategory`, `CustomField`, `CustomFieldOption`, `CustomValue`, `Shop`, `Terminal`, `PointSetting`, `CustomerPoint`, `CustomerStamp`, `StampRule`, `Coupon`, `CouponCustomer`; enums `Sex`, `CustomerType`, `CustomerStatus`, `AddressType`, `CustomFieldType`, `CustomCategoryType`, `FieldSize`, `RankType`, `RankScheduleMode`, `PointMovement`, `StampMovement`, `Industry`, `Occupation`.

### Modelos nuevos

`SalesItem` (o `Product`, ver `products` del estándar), `Sale` y `SaleLine` (reemplazan las ventas -1 de `tb_customer_sub`), `Expense` (reemplaza -2), `ServiceMenu` (`tb_karute_menu`), `Discount` (`tb_karute_waribiki`), `Karute` y `ServiceCategory` (`tb_karute`, `tb_sejutsu_cate`), `PhotoAlbum`, `Attendance` (`tb_play_time`), `ShopWorkDay` (`tb_shop_work`), `FelicaCard` (`tb_user_card`), `Employee` (`tb_staff`, pendiente en el estándar), `CustomerReferral`. No crear: `tb_customer_level`, `tb_c2_*`, `school*` (a confirmar con Japón).

### Rutas, controladores y servicios

Prefijo `admin/customers` (ya existente) y nuevos: `admin/sales` (`SaleController`, `ServiceMenuController`, `DiscountController`, `ExpenseController`, `SalesReportController` para staff/día/semana/mes), `admin/products` (`ProductController`), `admin/attendance` (`AttendanceController`), `admin/settings/cards` (`FelicaCardController`).

- En `Customers\`: completar `update`, `destroy`, `restore`, `import` (`CustomerImportController` con cola), `PointHistoryController`, `PointSettingController`, `AlbumController`, `KarteController`, `VisitHistoryController` (equivale a `kanren_record_*`: alta, edición y borrado de visitas ajustando puntos y sellos).
- Servicios: `VisitService` (registro y corrección de visita con puntos, sellos y estadísticas en `DB::transaction`), `LoyaltyService` (ya existe en Promotions: reutilizar), `CustomerNumberGenerator` (para `login_id`), `CustomerCsvExporter` (añadir Shift_JIS), `RankAssigner`.
- Comandos y scheduler: `customers:assign-ranks` (importe y visitas), `customers:refresh-visit-stats` (`shuki_heikin`, `last_raiten_date`), limpieza de temporales.
- Policies: `CustomerPolicy`, `SalePolicy`, con los permisos de `myKengen[1001]` y `[1033]` mapeados a Gates (`customers.manage`, `sales.view`, `sales.delete`).
- Jobs: `SyncPosPurchaseHistory` (consulta al POS), `SyncMobileShopPaidStatus` (API móvil), importación CSV por lotes.

### Orden recomendado

1. Completar `customers` (edición, borrado lógico, recuperación) y el comando de rangos. Dependen de: `shops`, `terminals` (ya existen).
2. `Employee` (tabla de personal): bloquea ventas, asistencia, `visits.employee_id` y `customers.staff`. **Depende del grupo de personal/staff.**
3. Visitas, puntos y sellos en la ficha (`VisitService`, pestañas 4, 8, 9, 10). **Depende del grupo Promociones/puntos** (`LoyaltyService`).
4. Ventas: `ServiceMenu`, `Discount`, `Sale`, `Expense`, informes. **Depende de tiendas y personal.**
5. Productos (`category2`): independiente, puede ir en paralelo con (4).
6. Importación CSV, 集計 restantes y asistencia con FeliCa. **Depende de la API de terminales.**
7. Ficha 美容, cursos y 学年: solo si Japón confirma que se usan.

---

## Riesgos y dudas

Integraciones externas (`INTEGRATIONS.md`):
- Servidores POS por tienda (`*.pos-s.net`, `fopen` por HTTP sin TLS ni autenticación, URL armada con `$url` de entrada): `inc/ajax_connectPosData.php`, `ajax_connectPosKoubaiData.php`, `ajax_sendPosKoubaiMemoData.php`. Sin POS de prueba no se pueden probar (PENDING 1.3). Hay que decidir si la pestaña 購買履歴 se mantiene.
- API MobileShop (`CustomerForMobileShop`, `sendUnpaidMemberStatus`): requiere `MOBILESHOP_EXTLINK_PASS` en `.env` y su servidor. Afecta a `ajax_changePaidMemberFlg.php` y a la pestaña 14.
- Lector FeliCa: `config/felica_mode.php`, `config/card_new.php`, `entrance/*`. No se prueba sin hardware.
- Cron (`crontab`) para el reparto de rangos (`cSendSchedule`): no corre en Docker; en producción corre por cron (PENDING 1.1).
- Búsqueda por código postal con tabla local `zip`; el nuevo sistema usa zipcloud.

Pantallas rotas o dudosas (`BROKEN-FILES.md`):
- `customer/kanren_record_confirm.php`: exige un archivo que no existe, sin enlaces. Borrar.
- `customer/csv_from_js.php` y `csv_download/csv_file_out.php`: superan 30 s con 22.000 clientes; el CSV debe generarse por bloques (`chunk`/cursor).
- `customer/customer_export.php`: volcado arbitrario de tablas con otra contraseña. No migrar.
- `customer/user_csv.php`, `customer_new_complete.php`, `customer_level_category_*`, `biyou/*`, `config/school*`, `config/mail_comment*`: sin enlace en el menú; confirmar si siguen en uso.
- `inc/search_set2.php` y `category2` vecino: dependen de `tb_c2_*`, borradas en 2020.

Tablas que faltan (`MISSING-TABLES.md`):
- `tb_c2_*` (18 tablas, borradas en 2020): `search_set2.php` las consulta con `@`. No migrar.
- `tb_felica_mode.cd = 2` puede no existir en producción.
- En el backup original ninguna categoría de `tb_search_cate` tenía `hyouji_flg = '1'`.

Decisiones pendientes (`PENDING.md`):
- Comillas en nombres (`O'Neil` → `O’’’’Neil`): decidir si se imita o se corrige. Recomendado corregir en la migración.
- Límite de 60 días en `staff_total` y `report`: nunca se aplicó. ¿Se aplica ahora (cambia el comportamiento)?
- Configuración por tienda o global (grupos, rangos, motivos): ver pendiente 1 de `TABLAS_MODULO_CLIENTES.md`.
- Falta `employees` en el estándar de BD; bloquea ventas y asistencia.
- Editor de texto enriquecido que sustituya a CKEditor 4 y su licencia (PENDING 3.1).
- ¿`category2/item` se sigue usando? (la parte de ventas de `category11/item` depende de `tb_c2_*` y está rota). Si el catálogo vive en `sales_items`, confirmar el nombre final de la tabla.
- Tabla `tb_customer_sub` mezcla campos personalizados, ventas y gastos: ¿se migran los datos viejos a `sales`/`expenses` o solo se parte de cero?

Seguridad (a corregir en la migración, no a copiar):
- Contraseña del socio (`login_pass`) y la inicial aleatoria (`randomPass(4)`, 4 caracteres) se guardan tal cual, sin hash. En Laravel: `Hash::make` y un flujo de restablecimiento. Habrá que migrar o forzar el cambio.
- Credenciales de BD embebidas por `pg_connect` en `customer_export.php`, `inc/customer_new_register.php`, `inc/customer_delete_register.php`, `customer_group_category_register.php`, `raiten_douki_register.php` y `customer_list.php` (cada una abre su propia conexión): en Laravel una sola conexión y `.env` (variable `SIGNAGE_DB_PASSWORD`, no copiar su valor).
- Los `ajax_*` de `inc/` y `karute_menu/` y los `setCustomer*Rank.php` se invocan por URL: deben quedar protegidos por `auth` y `throttle`, y los de cron convertirse en comandos Artisan sin ruta web.
- No abrir `system/login.php?activation=1`, `create_customer.php` ni `create_shop.php` (riesgo crítico, ver PENDING 4.0 y 4.1): sobrescriben datos de clientes.
