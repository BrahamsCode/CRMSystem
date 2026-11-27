# Tablas, datos y servidores que faltan en el entorno local

> Versión japonesa: [MISSING-TABLES.ja.md](MISSING-TABLES.ja.md). Resumen de la migración y mapa de documentos: [MIGRATION.md](MIGRATION.md).

> Lo que una pantalla necesita y no está en la base de datos que nos pasaron (`db/crm2019.backup`) o en la red de Docker.
> Actualizado: 2026-11-11.
>
> **Criterio (Antony, 2026-11-10):** la pantalla no debe mostrar errores por algo que falta. Donde falta una tabla o un dato, la pantalla carga vacía, como en producción, y aquí se dice qué saldrá cuando exista.
>
> **Ampliado (Antony, 2026-11-11, por indicación de su jefe):** tampoco se muestran los avisos de conexión con servidores o bases que ya no existen o no se alcanzan. Algunas bases ya no funcionan y otras están en otro servidor; lo que importa es que la pantalla no enseñe avisos. Todas las llamadas `pg_connect` llevan `@` (`tools/scripts/silenciar-conexion.php`, 60 llamadas en 52 archivos). La conexión sigue dando `false` como antes, y cada pantalla hace lo mismo que hacía: carga vacía o muestra su propio mensaje en japonés (`データベースサーバに接続できませんでした`). Con `@`, el fallo de conexión ya no se escribe en el log de PHP.

## 1. Tablas `tb_c2_*`: las borró el propio equipo en 2020

La migración `Migrations/Version20200615093300.php` (不必要な旧category2テーブルを削除, "borrar las tablas viejas de category2 que ya no hacen falta") eliminó 18 tablas: `tb_c2_card_master`, `tb_c2_catalog`, `tb_c2_catalog_categorys`, `tb_c2_catalog_data`, `tb_c2_catalog_search_cate`, `tb_c2_catalog_search_form`, `tb_c2_catalog_sub`, `tb_c2_category`, `tb_c2_how_to_sell`, `tb_c2_item`, `tb_c2_item_sub`, `tb_c2_item_sub_categorize`, `tb_c2_pay_else_master`, `tb_c2_shopping`, `tb_c2_stock`, `tb_c2_stock_count`, `tb_c2_sum_master` y `tb_c2_sum_master_pkey`. En `doctrine_migration_versions` consta como ejecutada el 2020-06-28, así que `db/crm2019.backup` ya se sacó sin ellas. No se vuelve a ejecutar: `doctrine-migrations status` da 15 ejecutadas, 0 nuevas, "Already at latest version". Su `down()` las volvería a crear, pero vacías. No están en otra base de datos: casi seguro que faltan también en producción, y desarrollo no lo muestra porque oculta los avisos.

Las consultas a esas tablas se silencian con `@` y un comentario que remite aquí. En `category11/inc/page_control.php` solo se silencia si la consulta nombra `tb_c2_`; con otras tablas el aviso se mantiene.

`category11/item/register.php` lo comparten `tb_c2_category`, `tb_c2_catalog` y `tb_waribiki`, que sí existe: `$droppedTable` silencia solo si `$tableName` empieza por `tb_c2_` (2026-11-20; antes, guardar un artículo o una categoría de 販売管理 mostraba 3 errores de SQL).

| Pantalla | Tabla | Qué sale ahora | Qué saldrá si la tabla existe |
|---|---|---|---|
| `category11/config/index.php` (レジ設定) | `tb_c2_shopping` | 起算日, impuesto y textos de recibo (レシート上段 / 下段) vacíos | La configuración de caja de la tienda |
| `category11/sumup/index.php` (売上集計) | `tb_c2_shopping` | El menú; el cierre mensual empieza el día 1 | El cierre empieza en el 起算日 de la tienda |
| `category11/sumup/shop_sum_news.php` (店舗別売上速報) | `tb_c2_sum_master` | El buscador de tiendas, sin datos (igual que desarrollo) | Las ventas del día por tienda |
| `category11/sumup/sumup_list_pc.php` (月間売上集計) | `tb_c2_shopping`, `tb_c2_sum_master` | 売上データがありません。, igual que desarrollo | La lista mensual de ventas |
| `category11/sumup/sumup_graph.php` (年間売上集計) | `tb_c2_shopping`, `tb_c2_sum_master` | 12 meses con 集計データがありません | El gráfico anual. En `master` este archivo no compila y da 500 (ver `PENDING.md`, 2b) |
| `category11/item/item_category_list.php`, `item_list.php`, `item_entry_new.php`, `item_entry_edit.php`, `item_list_search.php` (商品管理) | `tb_c2_category`, `tb_c2_catalog` | Listas y desplegables de categoría vacíos | Las categorías y el catálogo de productos |

Pendiente para Japón (`PENDING.md`, 2b): confirmar si category11 se sigue usando. El texto `tb_c2_` aparece aún en 100 archivos (`category11` 38, `hp/inc_m` 22).

## 2. Datos que faltan en el backup

| Qué falta | Pantallas | Qué sale ahora | Qué saldrá cuando exista |
|---|---|---|---|
| Fila 12 de `tb_base_system` (módulo SNS). No está en el backup original; en desarrollo sí | `category12/facebook/index.php`, `category12/twitter/index.php`, `category12/connect.php` | El formulario completo, igual que desarrollo, pero sin el menú lateral del módulo ni el nombre del módulo en el título | El menú lateral (`inc/left_navi12.php`) y el título. Antes la pantalla se cortaba: buscaba `inc/left_navi.php`, que no existe |
| Terminal POS registrada (el navegador debe enviar `CRMPOS-<id>`, ver `INTEGRATIONS.md`) | `category11/opening`, `pos`, `placeview`, `checkout`, `terminal.php` | 端末が認証されていません。(terminal sin autenticar) | La pantalla de caja de esa terminal |
| En el backup original, ninguna categoría de `tb_search_cate` tiene `hyouji_flg = '1'` (en local, solo las de prueba 4 y 10) | 顧客追加情報 (`category1/customer/add_record_new.php`) | Con una categoría oculta, sin campos extra | Los campos de esa categoría |

## 3. Servidores externos que no se alcanzan desde Docker

Desde el 2026-11-11 ninguno muestra aviso (ver el criterio arriba). La pantalla carga como cuando el servidor no responde, y se llenará sola cuando el servidor sea accesible.

| Servidor | Pantallas | Qué sale ahora | Qué hace falta |
|---|---|---|---|
| Servidor POS (`$posConnect`) | `category11/yoyaku/*` y sus AJAX | El aviso Y0001, igual que desarrollo. Las AJAX (`ajax_staff`, `ajax_menu`, `ajax_yoyaku_config`…) responden lo mismo que desarrollo: 登録されていません, メニューデータはありません, la configuración vacía | Un POS de prueba (`PENDING.md`, 1.3) |
| BD de beacon `b-develop.g-api.net` | `category7/beacon/beacon_list.php`, `beacon_detail.php` | La pantalla sin datos de beacon y sin aviso. El servidor rechaza la conexión (2026-11-10: `no pg_hba.conf entry`; 2026-11-11: el puerto 5432 ni responde, tampoco desde Windows) | Acceso desde el servidor nuevo, o un volcado |
| API de Twitter | `category12/twitter/twitterLogin.php`, `callback.php` | `twitterLogin.php`: エラー発生, sin aviso | Nada: según Antony (2026-11-10), la integración con Twitter ya no existe. Pendiente decidir si se retira como 女の子管理 |
| BD de `admin/` | `admin/` (160 archivos) | データベースサーバに接続できませんでした, el mensaje propio de `admin/inc/db.php`, igual que desarrollo | Nada: en desarrollo tampoco existe. Fuera de alcance (Antony, 2026-11-11; `PENDING.md`, 1.2) |
| BD de `touchcation/timecard` | `touchcation/timecard` | データベースサーバに接続できませんでした | Un volcado |
| BD `azas_night` | 女の子管理 (category8) | Retirado: 403 | Nada, ya no se usa |

## 4. Fallos de `master` que dependen del esquema

| Qué | Dónde | Efecto | Qué se hizo |
|---|---|---|---|
| `tb_screensaver` no tiene la columna `tb_c7_movie_program_cd`, tampoco en el backup original | `category7/digital_signage/movie_program_delete.php:54` | Al borrar un programa no se borran los archivos de su protector de pantalla, igual que en `master` | Solo se silencia el aviso |
| `qr_img.php` sin el parámetro `d` | `category3/mail_mag/qr_img.php` | Desarrollo: 500 sin cuerpo. Local: el texto del error, solo porque Docker tiene `display_errors=1`; con el `php.ini` de producción (`PENDING.md`, 1.7) sería el mismo 500 vacío. Con datos (`?d=http://yahoo.co.jp`, como `appview/memberId.php`) local y desarrollo dan el mismo PNG, byte a byte | Nada: es la imagen del QR y siempre se pide con datos |
| `mail_kaijo.php` abierta sin código de revista | `category3/mail_mag/mail_kaijo.php` | La consulta con `cd = ''` fallaba y avisaba | Ya no se lanza la consulta: mismo resultado (`false`), sin aviso |
