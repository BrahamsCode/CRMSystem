# 04 — Web y Mypage (マイページ管理 / ホームページ管理)
> Fuente: C:\Users\User\VivaTech\crm2-php8 (rama master = código ya migrado a PHP 8.5.12). Generado el 2026-11-26. Destino: Laravel 13 + PHP 8.5.

Alcance: `public_html/system/category6/` (マイページ管理, 83 PHP) y `public_html/system/category4/` (ホームページ管理, 91 PHP). Son pantallas del personal que administran contenido. El sitio público viejo `hp/` NO se migra; por eso cada submódulo lleva una marca de **consumidor** (ver la sección "Consumidores del contenido administrado"). Stack legacy detectado: PHP procedural (sin framework, patrón "una página = un script" con `pg_query` directo, `include` de `head/header/left_navi/footer`), PostgreSQL, jQuery/jQuery UI. Sin clases propias salvo `Push`, `hatuban` y `kengen`.

## Resumen

| Módulo/submódulo | Carpeta | Nº PHP | Nº vistas reales (pantallas) | Complejidad | Puntos |
|---|---|---|---|---|---|
| Menú マイページ管理 | `category6/index.php`, `inc/page_control.php` | 2 | 1 | S | 1 |
| マイページ (user_hp) | `category6/user_hp/` | 31 | 12 (5 マイコレ + design + config + topics + recommend + header + 2 freepage) | XL | 10 |
| 携帯HP (shop_hp) | `category6/shop_hp/` | 25 | 9 | L | 8 |
| フォトギャラリー | `category6/digital_catalog/` | 11 | 3 (+4 AJAX) | M | 4 |
| 求人情報 | `category6/recruit/` | 13 | 6 | M | 4 |
| ガチャリン管理 | `category6/kujibiki/` | 1 | 1 | M | 2 |
| **Subtotal category6** | | **83** | **32** | | **29** |
| コンテンツ設定 / サイトマップ | `category4/contents/` | 19 | 10 | L | 8 |
| ページコンテンツ管理 | `category4/page/` | 12 | 8 | M | 6 |
| PCHP (pc_shop_hp) | `category4/pc_shop_hp/` | 11 | 3 (+1 archivo vacío `report_list.php`) | S | 2 |
| Copias antiguas: user_hp, shop_hp, recruit, stamp | `category4/{user_hp,shop_hp,recruit,stamp}/` | 15+16+13+4 = 48 | ~19 (sin enlace en el menú) | — | 1 (solo verificar que `stamp` ya está en promociones) |
| Menú ホームページ管理 | `category4/index.php` | 1 | 1 | S | 1 |
| **Subtotal category4** | | **91** | **~41 (22 con enlace propio)** | | **18** |
| **TOTAL** | | **174** | **~73** | | **47** |

Notas de conteo: las "vistas reales" excluyen registros (`*_register`, `*_delete_register`), AJAX e includes. Las 48 PHP de `category4/{user_hp,shop_hp,recruit,stamp}` son copias antiguas de `category6/*` y de `category3/stamp`: el menú de `category4` (`inc/left_navi4.php`) solo enlaza `pc_shop_hp`, `contents/sitemap`, `contents/contents_list`, `page/page_list` y el enlace externo 不動産管理. No se migran (ver Riesgos).

## Consumidores del contenido administrado (sin `hp/`)

Búsqueda de lectores de cada tabla/campo en todo `crm2-php8` fuera de `hp/`, `admin/` y de las propias carpetas de gestión.

| Contenido administrado | Tabla / campo | Consumidor fuera de `hp/` | Veredicto |
|---|---|---|---|
| マイコレ: portada (カバーフロー), banner superior, imagen HOME principal, miniaturas, comentario HOME | `contents_master` con `category='tb_c6_user_hp'`, `field` = `aplicover`, `aplibanner`, `apli_home_image` / `mainimage`, `apli_home_thumbnail`, `apli_home_comment`, `mypage_config` | `api/mpa_getCoverFlow.php`, `api/mpa_getTopBanner.php`, `api/mpa_corresponding.php` (app móvil) | **VIVO** (app). Las URL devueltas apuntan a `/hp/uploads/c6/...`: el almacenamiento físico está dentro de `hp/`, hay que moverlo a Storage y reescribir las URL. El enlace del banner apunta a `/hp/user/control.php` (login al sitio viejo): hay que cambiarlo por la mypage nueva. |
| Opción "mostrar grupo de cliente en la app" | `contents_master` `apli_customer_group_disp` (se edita en `mobile_hp_edit.php`/mycolle) | `appview/class/Util.php` | **VIVO** (appview) |
| オススメ (recommend) + push | `tb_hp_recommend` | `appview/class/Util.php` (últimos 20, paquete `crm`; imágenes en `/hp/uploads/c6/user_hp/recommend/{cd}/`) | **VIVO** (appview). Push: `Push::exec` GET a servidor externo. |
| スタンプ柄 / 動作設定 | `tb_stamp_pattern`, `tb_stamp_config` | `appview/stamp.php`, `touchcation/*` | **VIVO**, pero la pantalla de edición vigente es `category3/stamp/` (grupo promociones); la de `category4/stamp/` es copia. |
| 求人情報 + 職種 | `tb_recruit`, `tb_recruit_category` | `system/category7/kiosk/xml_info_up.php` y `digital_signage/xml_info_up.php` (carteles/kiosco, grupo terminales) | **VIVO parcial** (solo señalización). La página pública de empleo estaba en `hp/m`. |
| ガチャリン設定 | `contents_master` `category='tb_c6_kujibiki'` | `system/class/cKujibiki.php` y `category3/mycoupon/*` (interno); sorteo público en `hp/user/sc/kujibiki` | **VIVO interno** (cupones); el sorteo de cara al cliente quedaría sin pantalla hasta que la mypage nueva lo reimplemente. |
| トピックス, 新着情報, ヘッダ画像, フリーページ (ショップ y マイ), 店舗詳細 (company), diseño de plantilla (`tb_mobile_template`, `admin_master.mypage_template` / `mobile_hp_template`), colores (`tb_hp_color`), textos (`tb_hp_words`, `tb_manage_words`) | `tb_hp_topics`, `tb_hp_news`, `tb_hp_header`, `tb_hp_freepage`, `tb_my_freepage`, `tb_my_freepage_master`, `tb_hp_color`, `tb_mobile_*` | Solo `hp/user`, `hp/m` (excluidos) | **HUÉRFANO sin `hp/`**, salvo que la mypage nueva (web) o la app los muestre. Decisión de producto pendiente. |
| フォトギャラリー | `tb_digital_catalog`, `tb_digital_catalog_tag`, `tb_digital_catalog_tag_items` | Solo `hp/user/sc/digital_catalog`; `appview/photo_gallery.php` no consulta estas tablas | **HUÉRFANO** (candidato a la mypage nueva) |
| コンテンツ / ページ / サイトマップ del PCHP | `tb_c4_contents`, `tb_c4_report`, `tb_c4_sitemap`, `tb_c4_stock*`, `tb_hp_data`, `tb_hp_domain` | Solo `hp/` (sitio PC) y el árbol del propio admin (`system/inc/function.php`) | **HUÉRFANO total**: no migrar (ver propuesta). |

## マイページ — user_hp (`category6/user_hp/`)

Configuración de lo que ve el cliente final en la mypage/app: imágenes del マイコレ (portada, banner, imagen principal, miniaturas), plantilla de diseño, colores, トピックス, オススメ, ヘッダ y páginas libres propias. Todo se filtra por tienda (`tb_shop_cd`; vacío o `null` = 全店舗). Menú condicionado por permisos `kengen` 1014-1017, 1032, 1035 y por paquetes en `tb_base_system_grandchild`.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD | Notas |
|---|---|---|---|---|---|
| `mobile_mycolle_edit.php` | マイコレ管理 | edición | Sube imagen por hueco, URL, 全店舗/tienda, アップロード, 画像を削除する (diálogo jQuery UI). Sección portada | `contents_master` L/E, `admin_master` L | 1680 líneas. Las 5 pantallas mycolle son el mismo script con otra sección |
| `mobile_mycolle_edit_coverflow.php` | カバーフロー画像設定管理 | edición | Igual (sección `aplicover`) | ídem | consumido por `mpa_getCoverFlow` |
| `mobile_mycolle_edit_banner.php` | バナー画像設定管理 | edición | Banners en celdas, ﾃﾞﾌｫﾙﾄ選択/自由選択, URL, borrar | ídem | consumido por `mpa_getTopBanner` |
| `mobile_mycolle_edit_main.php` | HOMEメインイメージ管理 | edición | Imagen principal, bandera `home_image_flg[]` | ídem | `mpa_corresponding` |
| `mobile_mycolle_edit_thumbnail.php` | サムネイル画像管理 | edición | Miniaturas, `home_thum_flg[]` | ídem | `mpa_corresponding` |
| `ajax_apliFileUpload.php` | (subida) | ajax | Subida/borrado/guardado de imágenes mycolle, devuelve JSON | `contents_master` L/E | JSON se rompía con avisos PHP (F6). 479 líneas |
| `mobile_hp_edit.php` | マイページデザイン管理 | edición | Elige plantilla (1-46, `img/template*.gif`), ve páginas `config_*` (17), sube imagen, 設定する | `admin_master` L/E, `contents_master`, `tb_mobile_template`, `tb_hp_words`, `tb_manage_words` | Guarda en `admin_master.mypage_template` |
| `ajax_change_pattern.php`, `ajax_change_pic.php`, `ajax_picupload.php` | — | ajax | Cambia patrón, imagen, sube temporal | `admin_master`, `tb_mobile_template` | |
| `pic_config_function.php`, `base_color_function.php` | — | fragmento | Funciones de las `config_*` y de colores | `tb_mobile_template` L | Variables sin inicializar dentro de heredoc |
| `config.php` / `config_register.php` | ユーザーマイページ設定 | config / registro | Colores y opciones de la mypage | `tb_hp_color` L/E | UPDATE en transacción |
| `topics.php` | トピックス管理 | lista+alta+edición | Alta/edición con imagen y enlace (`tel:` permitido), 表示情報を更新, 削除; orden `disp_date DESC, update DESC` | `tb_hp_topics` L/E, `tb_shop` L | Fecha `disp_date` como texto `Y/m/d`; `hyouji_flg` |
| `topics_register.php`, `topics_delete_register.php`, `inc/upload_topics.php`, `js/topics_entry.js` | トピックス登録/削除 | registro / borrado | `hatuban` para el cd; borrado lógico `disp_flg` | `tb_hp_topics` E, `hatuban` E | |
| `recommend.php` | オススメ管理 | lista+alta+edición | Alta con imagen, enlace, `push` sí/no, 表示情報を更新 | `tb_hp_recommend` L/E, `tb_mobile_navi`, `tb_shop` | Nombre del menú viene de `tb_mobile_navi` |
| `recommend_register.php`, `recommend_delete_register.php` | お勧め登録/削除 | registro / borrado | Si `push=1` y `application_use_flg=1` llama `Push::exec(domain,'recommend',cd)` | `tb_hp_recommend`, `hatuban`, `admin_master` | Servicio externo |
| `header.php`, `header_register.php`, `header_delete_register.php`, `inc/upload_header.php`, `js/header_entry.js` | ヘッダ画像管理 | lista+alta / registro | Alta por tienda con imagen, 表示する/しない | `tb_hp_header` L/E, `tb_shop` | Esta versión no tiene botón de borrar (el borrado lo usa `category4`) |
| `my_freepage_list.php` | フリーページ管理 | lista | Páginas del マイ, edición, ▼▲ (`rank`), borrado | `tb_my_freepage_master` L/E, `tb_shop`, `hatuban` | Los avisos caían en `page_title` (F7) |
| `my_freepage.php` | フリーページ記事管理 | alta/edición | Artículos con imagen, 画像添付解除, 表示しない | `tb_my_freepage` | |
| `my_freepage_register.php`, `my_freepage_delete_register.php` | フリーページ記事登録/削除 | registro / borrado | `rank = rank+1` al alta | `tb_my_freepage`, `hatuban` | |
| `inc/{disp_pic,upload}.php`, `js/{imageselect,pic_upload,staff_entry,whatsnew}.js`, `img/*.gif`, `uploads/.htaccess` | — | fragmento/estático | Auxiliares | — | Imágenes en `hp/uploads/c6/user_hp/...` |

Flujo principal: menú → lista (con filtro de tienda) → alta/edición en la misma página → `*_register` (POST, redirige tras 2 s con `<meta refresh>`) → vuelta a la lista. El borrado pasa por un `*_delete_register`.

Reglas no obvias:
- Numeración con tabla `hatuban` (`SELECT ... FOR UPDATE` + `UPDATE kazu+1`; si no existe fila inserta `kazu=2`): reemplazar por secuencias PostgreSQL/PK autoincremental (conservar `cd` existentes).
- `disp_flg='1'` visible, `'9'` borrado lógico; `hyouji_flg` controla 表示/非表示 (y `'9'` en recommend se excluye de la app).
- `tb_shop_cd` nulo o 0 = 全店舗; la app (`appview/Util`) usa `tb_shop_cd IN (tienda del cliente) OR 0`.
- Configuración mycolle es "una fila por campo" en `contents_master` (`category`, `field`, `text_data`, `int_data`, `float_data`, `disp_flg`, `update_date`): modelo EAV.
- Borrar imagen solo quita `0_moto.jpg` o desmarca `pic_flg`, no los derivados (igual que master).

Servicios viejos y reemplazo:
- Subida a carpetas bajo `hp/uploads/c6/...` + `temp/` limpiado con `system("find ... -mtime +1 -exec rm")` → `Storage::disk('public')` + comando programado (Scheduler) para temporales.
- `Push` (socket `fsockopen` a `b.g-api.net` / `b-develop.g-api.net`, selector `admin_master.push_api_host`) → Job en cola con `Http::get`, host en `config/services.php`.
- jQuery UI (diálogos, sortable), JSON por AJAX → componentes React; endpoints JSON con Form Request.
- Permisos `kengen->myKengen[código][accion]` y `rank == 'master'` → Policies/Gates.

## 携帯HP — shop_hp (`category6/shop_hp/`)

Contenido de la "página de la tienda" (móvil): diseño, 新着情報, ヘッダ, ficha de tienda y フリーページ. Hoy lo consume solo `hp/m`.

| Archivo | Título (ja) | Tipo | Qué hace | Tablas BD | Notas |
|---|---|---|---|---|---|
| `mobile_hp_edit.php` | 携帯HPデザイン管理 | edición | Plantilla y páginas `config_*`, sube imagen, 表示する/非表示にする | `admin_master` (`mobile_hp_template`), `tb_mobile_template`, `tb_manage_words` | Sin borrar imagen |
| `config.php` / `config_register.php` | ショップページ設定 | config / registro | Colores y textos | `tb_hp_color`, `tb_manage_words` | |
| `whatsnew.php` | 新着情報管理 | lista+alta+edición | Alta con imagen, edición, 表示情報を更新 | `tb_hp_news`, `tb_mobile_navi` | |
| `whatsnew_register.php`, `whatsnew_delete_register.php`, `js/whatsnew.js` | 新着情報登録/削除 | registro / borrado | `hatuban` | `tb_hp_news`, `hatuban` | |
| `header.php`, `header_register.php`, `header_delete_register.php`, `inc/upload_header.php`, `js/header_entry.js` | ヘッダ画像管理 | lista / registro / borrado | Edita siempre la fila fija `tb_hp_header.cd = 2`; guarda en `hp/uploads/c4/shop_hp/header/` (no `c6`) | `tb_hp_header` | |
| `company.php` | 店舗詳細管理 | lista | Lista de tiendas con campos extra | `tb_shop`, `tb_mobile_navi` | |
| `company_detail.php`, `company_update.php`, `company_register.php`, `company_complete.php`, `js/company_entry.js`, `inc/upload.php` | 店舗詳細 | detalle / edición / registro / confirmación | Edita **la fila de `tb_shop`** (descripción, 追加情報1, imágenes) y 画像解除 | `tb_shop` L/E, `tb_recruit_category`, `tb_base_system_child` | `chmod($dir,0777,true)` rompía en PHP 8.5 (F10); `$$act` por `$act` |
| `freepage.php` | フリーページ管理 | lista+alta+edición | Páginas con imagen, ▼▲, 表示情報を更新 | `tb_hp_freepage`, `tb_mobile_navi`, `tb_officialposition`, `tb_shop` | 633 líneas |
| `freepage_register.php`, `freepage_delete_register.php` | 情報登録/削除 | registro / borrado | `hatuban` | `tb_hp_freepage` | |
| `ajax_change_pattern.php`, `ajax_change_pic.php`, `ajax_picupload.php`, `pic_config_function.php`, `base_color_function.php`, `inc/{disp_pic,upload}.php`, `js/pic_upload.js`, `uploads/.htaccess` | — | ajax / fragmento | Auxiliares de diseño | `admin_master`, `tb_mobile_template` | |

Flujo: igual que user_hp. Regla: `company_*` modifica `tb_shop` (tabla del grupo tiendas): coordinar con ese grupo. Servicios: subida a disco (→ Storage), sin envío de correos.

## フォトギャラリー (`category6/digital_catalog/`)

Galería de fotos con categorías (etiquetas), favorito (estrella) y visibilidad. Consumo: solo `hp/user/sc/digital_catalog`.

| Archivo | Título (ja) | Tipo | Qué hace | Tablas BD | Notas |
|---|---|---|---|---|---|
| `digital_catalog_new.php` + `digital_catalog_frm.php` | 新規登録 | alta (+fragmento) | Foto, comentario, カテゴリを追加 (texto libre + Enter, sugerencias) | `tb_digital_catalog_tag` L | `frm` = formulario compartido con edición |
| `digital_catalog_register.php` | — | registro | `hatuban` o `INSERT cd`, UPDATE, crea etiquetas nuevas, reescribe `tb_digital_catalog_tag_items` | `tb_digital_catalog` E, `_tag` E, `_tag_items` E, `hatuban` | Numeración dual (FOR UPDATE o helper) |
| `digital_catalog_list.php` | 掲載一覧 | lista | Filtros 全て / オススメ / por categoría; estrella y visibilidad por AJAX; 編集, 削除 | `tb_digital_catalog` L, `_tag`, `_tag_items` | Orden `hyouji_date DESC, cd DESC` |
| `digital_catalog_update.php` | — | edición/registro | Edita foto y etiquetas | tablas del catálogo | |
| `digital_catalog_delete.php` | — | borrado | `disp_flg` lógico | `tb_digital_catalog` E | |
| `ajax_check_star.php`, `ajax_check_hyouji.php` | — | ajax | Alterna `star` y `hyouji_flg` | `tb_digital_catalog` E | |
| `digital_catalog_tag.php` + `ajax_tag.php`, `ajax_search_tags.php` | カテゴリ管理 | lista + ajax | 編集, reordenar (jQuery UI `sortable` → `rank`), 削除 | `tb_digital_catalog_tag` L/E, `_tag_items` | |

Flujo: 新規登録 → list (filtrar/star) → 編集 → tag. Reglas: relación N:M foto-etiqueta (`tb_digital_catalog_tag_items`); etiquetas nuevas se crean al registrar; `disp_flg='9'` borrado. Permiso `kengen` 1036.

## 求人情報 (`category6/recruit/`)

Ofertas de empleo por tienda y su catálogo de 職種. Consumo: carteles/kiosco (`category7`) y `hp/m`.

| Archivo | Título (ja) | Tipo | Qué hace | Tablas BD | Notas |
|---|---|---|---|---|---|
| `recruit_new.php` | 求人情報新規登録 | alta | Formulario completo con 職種 (AJAX `ajax_category.php`) y 2 imágenes | `tb_recruit_category` L, `tb_shop` L | Bug heredado: con una sola tienda el desplegable de 職種 sale vacío (PENDING.md) |
| `recruit_register.php` | — | registro | `hatuban`, INSERT/UPDATE | `tb_recruit` E, `tb_base_system_child` L, `hatuban` | |
| `recruit_list.php` | 求人情報一覧 | lista | Lista y filtro | `tb_recruit` L, `tb_shop` | |
| `recruit_detail.php`, `recruit_update.php`, `recruit_complete.php`, `recruit_delete.php` | 求人 詳細/修正/完了/削除 | detalle / edición / confirmación / borrado | 修正する con imágenes; 削除する | `tb_recruit`, `tb_recruit_category`, `tb_shop` | `$$act` |
| `recruit_category.php`, `recruit_category_register.php`, `recruit_category_delete.php` | 求人職種管理 | lista+alta+edición / registro / borrado | ABM de 職種 | `tb_recruit_category` E, `hatuban` | |
| `ajax_category.php`, `inc/{upload,disp_pic}.php`, `js/item_entry.js` | — | ajax / fragmento | | `tb_recruit_category` L | |

Permisos `kengen` 1023 (columnas distintas por acción: lista [2], alta [1], 職種 [4]).

## ガチャリン管理 (`category6/kujibiki/`)

| Archivo | Título (ja) | Tipo | Qué hace | Tablas BD | Notas |
|---|---|---|---|---|---|
| `kujibiki_config.php` | ガチャリン管理 各種設定 | config (alta+registro en la misma página) | Activa el sorteo, máximo de intentos, límite, y cantidad/probabilidad de premios diamante, oro, plata, bronce; probabilidad de "hazure" (pérdida) | `contents_master` (`category='tb_c6_kujibiki'`) L/E | 832 líneas; `insertNewRecord` crea la fila si falta y luego UPDATE por campo (`kujibiki_disp_flg`, `_max`, `_limit_flg`, `_dia/gold/silver/bronze_num|rate`, `prize_count`). Probabilidad de hazure = complemento |

## Menú (category6)
`index.php` lista los hijos de `tb_base_system` (L) y `inc/page_control.php` pagina; fragmentos de menú en `system/inc/left_navi6.php` (permisos `kengen`, paquetes `tb_base_system_grandchild`). En Laravel: reemplazar por navegación del frontend + Policies; la selección de módulos ya existe ("システム選択").

## コンテンツ設定 / サイトマップ (`category4/contents/`)

Gestiona el árbol de secciones del sitio PC (サイトマップ) y los "contenidos" que se cuelgan de él (tipos 記事 y テキストリスト). Consumo: solo `hp/` PC → **huérfano**.

| Archivo | Título (ja) | Tipo | Qué hace | Tablas BD | Notas |
|---|---|---|---|---|---|
| `sitemap.php`, `sitemap_edit.php`, `sitemap_confirm.php`, `sitemap_register.php` | サイトマップ管理 | lista+alta / edición / confirmación / registro | Árbol dtree, alta con confirmación, ▲▼ (`row_lis`), borrado | `tb_c4_sitemap` L/E, `tb_c4_contents`, `tb_c4_report`, `tb_c4_stock`, `hatuban` | Comilla se duplica en el registro |
| `contents_list.php` | コンテンツ一覧・検索 | lista | Búsqueda, detalle | `tb_c4_contents`, `tb_c4_sitemap`, `tb_c4_stock`, `tb_c4_stock_count` | |
| `contents_detail.php` | コンテンツ登録情報 | detalle | | `tb_c4_contents`, `tb_c4_report`, `tb_c4_sitemap` | |
| `contents_entry.php`, `contents_entry_confirm.php`, `contents_entry_register.php`, `contents_entry_complete.php` | コンテンツ登録 | alta → confirmación → registro → confirmación final | Alta/edición de 記事 o テキストリスト con imagen | `tb_c4_contents`, `tb_c4_sitemap`, `hatuban` | 620 líneas en confirm |
| `contents_change_status.php`, `contents_delete_register.php` | コンテンツ情報 / 登録情報 | registro / borrado | Cambia estado, borra | `tb_c4_contents` E | |
| `contents_recover_confirm.php` | コンテンツ登録情報 | confirmación | Recuperar | `tb_c4_contents`, `tb_shop` | **Roto:** busca `id`/`email1` en una tabla sin esas columnas (BROKEN-FILES.md) |
| `form_config.php`, `form_config_register.php`, `inc/search_set1.php`, `inc/search_set2.php` | コンテンツ検索項目設定 | config / registro | Define campos del formulario de búsqueda | `tb_koumoku` L/E, `kaiin_master`, `tb_shop` | `tb_koumoku` es compartida con clientes |
| `inc/{disp_pic,upload}.php`, `js/*`, `css/*`, `img/*` (dtree, jQuery 1.3.2) | — | fragmento/estático | | — | jQuery 1.3.2 obsoleto |

## ページコンテンツ管理 (`category4/page/`)

| Archivo | Título (ja) | Tipo | Qué hace | Tablas BD | Notas |
|---|---|---|---|---|---|
| `page_list.php` | ページ管理 | lista | Contenidos por página | `tb_c4_contents`, `tb_c4_sitemap` | Punto de entrada del menú |
| `entry_list.php` | ページ管理 | lista | Artículos de un `cont_cd`; sin `cont_cd` válido redirige a `logout.php` | `tb_c4_contents`, `tb_c4_report` | |
| `report.php` | ページ管理 | detalle | | `tb_c4_contents`, `tb_c4_report` | Carga `session.php` antes que `db.php` (roto) |
| `report_entry.php`, `report_entry2.php`, `report_entry_confirm.php` | — | alta / confirmación | Artículo (記事) con imagen | `tb_c4_report`, `tb_c4_contents` | `entry_register.php` hace el INSERT |
| `textlist_entry.php`, `textlist_entry_confirm.php` | — | alta / confirmación | テキストリスト 2×2 | `tb_c4_report` | |
| `entry_register.php`, `entry_delete.php` | — | registro / borrado | `hatuban` | `tb_c4_report` | |
| `inc/*`, `js/item_entry*.js` | — | fragmento | | | |

## PCHP (`category4/pc_shop_hp/`) — solo con IP de desarrollo en el menú

| Archivo | Título (ja) | Tipo | Qué hace | Tablas BD | Notas |
|---|---|---|---|---|---|
| `config.php` / `config_register.php` | PCホームページ設定 | config / registro | Título, contenido, imagen | `tb_hp_data` L/E | |
| `header.php` / `header_register.php` | ヘッダ管理 | config / registro | H1, comentario, imagen | `tb_hp_data` | |
| `footer.php` / `footer_register.php` | フッタ管理 | config / registro | Comentario, copyright, enlace, imagen | `tb_hp_data` | |
| `report_list.php` | — | — | Archivo vacío (0 líneas) | — | Descartar |
| `inc/*`, `js/*` | — | fragmento | | `tb_hp_data` | |

El menú lo muestra solo si `Common::isDevelopmentAuthorityFromIP()` (IP restringida); las pantallas responden a cualquier sesión.

## Copias antiguas en category4 (`user_hp/`, `shop_hp/`, `recruit/`, `stamp/`)

Sin enlace en el menú. Duplican `category6/user_hp` (topics, recommend, header, config), `category6/shop_hp`, `category6/recruit` y `category3/stamp` con versión más vieja. `stamp/` (`stamp_config`, `stamp_config_register`, `stamp_pattern`, `stamp_pattern_register`; tablas `tb_stamp_config`, `tb_stamp_pattern`, `tb_shop`, `hatuban`) edita: máximo de sellos (`stamp_max`, fila `tb_shop_cd=1`), `limit_flg`/`bonus_flg` por tienda, y selección de diseños (`pic_shop_flg`, `pic_default`). `recruit/` y `shop_hp/company_*` y `stamp/` cargan `session.php` antes que `db.php`: dan fatal. **Recomendación: no migrar**; el único contenido vivo (stamp) pertenece al grupo de promociones.

## Tablas de BD del grupo

| Tabla | Para qué | Submódulos | Notas (columnas clave) |
|---|---|---|---|
| `contents_master` | Config clave/valor (EAV) de mycolle, kujibiki, flags de la app | user_hp, kujibiki | `category`, `field`, `text_data`, `int_data`, `float_data`, `disp_flg`, `update_date`, `hyouji_flg` |
| `admin_master` | Ajustes globales: `mypage_template`, `mobile_hp_template`, `application_use_flg`, `push_api_host` | user_hp, shop_hp | fila `cd=1` |
| `tb_mobile_template`, `tb_mobile_navi`, `tb_manage_words`, `tb_hp_words`, `tb_hp_color` | Plantillas de diseño, nombres de menú, textos y colores | user_hp, shop_hp | catálogo semilla; ver `Initialize.php` en `src/Util` |
| `tb_hp_topics`, `tb_hp_recommend`, `tb_hp_news`, `tb_hp_header`, `tb_hp_freepage` | Contenido publicado | user_hp, shop_hp | `disp_flg`, `hyouji_flg`, `disp_date`, `tb_shop_cd`, `update`, `pic_flg` |
| `tb_my_freepage_master`, `tb_my_freepage` | Páginas libres del マイページ y sus artículos | user_hp | `rank`, `tb_shop_cd`, `pic_flg` |
| `tb_digital_catalog`, `tb_digital_catalog_tag`, `tb_digital_catalog_tag_items` | Galería, categorías y relación N:M | digital_catalog | `star`, `hyouji_flg`, `hyouji_date`, `rank` |
| `tb_recruit`, `tb_recruit_category` | Ofertas y 職種 | recruit | `boshu_shokushu` |
| `tb_shop` | Ficha de tienda (company) | shop_hp (E), todos (L) | grupo tiendas |
| `tb_c4_sitemap`, `tb_c4_contents`, `tb_c4_report`, `tb_c4_stock`, `tb_c4_stock_count`, `tb_hp_data`, `tb_hp_domain` | Sitio PC | contents, page, pc_shop_hp | `row_lis`; `domain_cd` en sesión |
| `tb_koumoku`, `kaiin_master` | Campos configurables de búsqueda | contents/form_config | compartida con clientes |
| `tb_stamp_config`, `tb_stamp_pattern` | Sellos | category4/stamp (copia) | `tb_shop_cd`, `stamp_max`, `limit_flg`, `bonus_flg`, `pic_shop_flg`, `pic_default`, `narabi` |
| `hatuban` | Contadores de código (`koumoku`, `kazu`) | casi todos | reemplazar por secuencias |
| `tb_base_system`, `tb_base_system_child`, `tb_base_system_grandchild` | Menús y paquetes habilitados | menús | |
| `tb_officialposition`, `tb_customer`, `tb_shop` (L) | Auxiliares | shop_hp, API | |

## Propuesta Laravel 13

- **Modelos Eloquent** (prefijo `Mypage*`, nombres en inglés como los existentes): `AppSetting` (sobre `contents_master`, con scope por `category` y casts), `MypageTopic`, `MypageRecommendation`, `MypageNews`, `MypageHeaderImage`, `MypageFreePage`/`MypageFreePageArticle`, `PhotoGalleryItem`/`PhotoGalleryCategory`, `JobPosting`/`JobCategory`, `KujibikiSetting` (o claves en `AppSetting`). Tablas de diseño (`tb_mobile_template`, colores, palabras) como seeders. Decidir si se normaliza el EAV `contents_master` en tablas tipadas (recomendado: `app_home_images`, `app_banners`, `kujibiki_settings`) manteniendo una vista de compatibilidad para la API de la app.
- **Rutas** (prefijo `/staff/mypage/*` y `/staff/recruit`, `/staff/gallery`, `/staff/kujibiki`): `Route::resource` con FormRequest; AJAX de estrella/visibilidad/orden como endpoints JSON; subidas con `Storage` (disco `public`), conversión de miniaturas con Intervention Image/GD en lugar de las carpetas `temp`.
- **Controladores**: `Mypage\MycolleController` (una sola implementación parametrizada por sección: cover, banner, main, thumbnail en lugar de 5 scripts de 1700 líneas), `TopicController`, `RecommendationController` (con Job `SendRecommendationPush`), `HeaderImageController`, `FreePageController`, `ShopPageController` (shop_hp, `company` delega en el módulo Tiendas), `PhotoGalleryController`, `JobPostingController`, `KujibikiSettingController`.
- **Jobs/Scheduler**: `SendPushJob` (reemplaza `Push::exec`), comando `uploads:prune-temp` diario (reemplaza `find ... -mtime +1 -exec rm`).
- **Policies/Gates**: mapear `kengen` 1014-1023, 1032, 1035, 1036 a permisos del staff ya existentes (`Admin`) y `rank == master` a un Gate `master`; IP de desarrollo de PCHP como middleware solo si PCHP se conserva.
- **API para la app**: reescribir `mpa_getCoverFlow`, `mpa_getTopBanner`, `mpa_corresponding` y la lista de オススメ de `appview/Util` como endpoints `routes/api.php` (Resources). Mantener URL y forma JSON mientras haya apps instaladas; quitar el hash MD5 de `login_pass` en el enlace del banner (usar token Sanctum).
- **Reutilizable en CRMSystem** (`public_html/app`): `BaseModel`, `Shop` (para `company` y filtro por tienda), `Admin` y sus permisos, `Enums/DisplayScope` (probablemente 全店舗/tienda), `StampSetting` + `Enums/StampDisplayMode` + `StampMovement` (cubren `tb_stamp_config`; no recrear en este grupo), `LotteryRank` (relacionado con ガチャリン), `Concerns` y `Doc/database/TABLAS_MODULO_PROMOCIONES.md`. No existe aún nada de mypage, recruit, galería ni contenidos PC (no hay `TABLAS_MODULO_*` para ellos).
- **Qué NO migrar**: `category4/{contents,page,pc_shop_hp}` (sitio PC, `tb_c4_*`, `tb_hp_data`, `tb_hp_domain`), copias `category4/{user_hp,shop_hp,recruit,stamp}`, `report_list.php` vacío, 不動産管理 (otro sistema), `shop_hp` y `user_hp` de diseño de plantillas si no hay mypage web que las use.
- **Orden recomendado**:
  1. (S) `AppSetting` + Storage + política de permisos (dependencia: staff/Admin).
  2. (M) マイコレ + API de la app (cover/banner/home): único contenido con consumidor real hoy.
  3. (M) `Recommendation` + push + endpoint de appview.
  4. (M) Reclutamiento (alimenta carteles de terminales).
  5. (S/M) ガチャリン config (depende de cupones/ガチャ del grupo promociones).
  6. (M) Topics, News, Header, FreePage, Galería: solo si la mypage nueva los muestra; si no, posponer.
  7. Contenidos PC: no.
- **Dependencias con otros grupos**: tiendas (`tb_shop`, `company_*` edita la tienda), staff/permisos (`kengen`), promociones (stamp, mycoupon, cKujibiki), terminales (`category7/kiosk` y `digital_signage` leen `tb_recruit`), clientes (`tb_customer` para filtrar por tienda del cliente en la API; `tb_koumoku`), API/app (`api/mpa_*`, `appview`).

## Riesgos y dudas

1. **Almacenamiento físico dentro de `hp/`**: las imágenes publicadas viven en `hp/uploads/c6/...` y `hp/uploads/c4/...` y las APIs de la app devuelven URL absolutas `http://dominio/hp/uploads/...`. Al retirar `hp/` hay que migrar esos archivos y conservar/redirigir las URL, o las apps instaladas pierden imágenes (cover, banner, recommend). Las carpetas no las crea el código (PENDING.md 2d).
2. **Decisión de producto**: topics, news, header, freepage, galería, ficha de tienda y plantillas no tienen consumidor sin `hp/`; confirmar con el negocio si la mypage nueva los mostrará. Hasta entonces migrar solo mycolle, recommend, recruit y kujibiki config. Además el banner de la app enlaza a `/hp/user/control.php?log_chk=...` (hash MD5 de `login_id` y `login_pass`), un login que desaparece y que hay que sustituir por un token seguro.
3. **Integraciones externas**: push a `b.g-api.net`/`b-develop.g-api.net` por HTTP/80 con `fsockopen` (ver INTEGRATIONS.md) y `Push::exec` solo con `admin_master.application_use_flg=1`; modelo EAV `contents_master` sin esquema y sin constraint (riesgo de campos huérfanos, duplicados por `ORDER BY cd DESC LIMIT 1`).
4. **Pantallas rotas o con errores heredados** (BROKEN-FILES.md, PENDING.md): `recruit_new.php:77-79` (desplegable de 職種 vacío con una tienda), `contents_recover_confirm.php:58/68` (consulta columnas inexistentes), copias de `category4` con `session.php` antes de `db.php`, `$$act` en `company_update`/`recruit_update`, borrado de imagen que no elimina derivados, modales sin CSS de jQuery UI (PENDING 0.14), SQL por concatenación sin parámetros (reemplazar por Eloquent/bindings).
5. **Tablas que faltan / compartidas**: `tb_koumoku` y `tb_shop` son de otros grupos; revisar `MISSING-TABLES.md` para tablas del bloque `category4` y `tb_hp_domain` (multidominio por sesión `category4.domain_cd`, que no tiene equivalente en Laravel 13 y probablemente se descarta).
6. **Decisiones pendientes**: si PCHP/不動産管理 se retira formalmente; si el sorteo ガチャリン para el cliente se reimplementa en la mypage; si se normaliza `contents_master`; y si `category4/stamp` se descarta a favor de `category3/stamp`.
