# Regresión de punta a punta (S1, 2026-11-27)

## Tercera pasada: testeo real por módulo con datos (Antony, 2026-11-27)

Web `http://localhost:8081` (BD `crmsystem2` recargada con `migrate:fresh` + `bd-legacy.sh` + `db:seed`). Cada sesión recorre con Playwright todas sus pantallas como master y como usuario limitado: listar, buscar, alta, edición, borrado, CSV, PDF y paginación. Compara el HTML y el comportamiento con development.crm-s.net, en **solo lectura**.

Cada sesión escribe solo en su subsección. Formato de cada fallo: `| pantalla (URL) | fallo | corrección + prueba Pest | commit |`. Al final de cada subsección va una línea de resumen: pantallas probadas, fallos encontrados y corregidos, y pendientes.

### Núcleo / 基本情報 (S1)

Recorrido con Playwright en `:8081` como master. Lo creado lleva el prefijo «QA-S1» y se borró al terminar; lo cambiado en 機能設定 y 権限管理 se dejó como estaba, comprobado en la BD (admin_master con el mismo md5 que antes). La muestra solo se abrió para comparar.

| Pantalla (URL) | Fallo | Corrección + prueba Pest | Commit |
|---|---|---|---|
| Cabecera y ショートカット管理 (`bookmark*.php`) | Los accesos directos enlazaban la URL legacy de `tb_bookmark`. Las pantallas movidas a `/admin/…` daban 404: スタッフ一覧, クーポン一覧, メルマガ y otras; 27 de los 60 accesos de fábrica | `Bookmark::targetUrl()`: usa la URL legacy si conserva su ruta, si no la de Laravel (`/admin/promocion/<dir>/<archivo>` en category3 y un mapa para スタッフ, マイページ y 求人). Los accesos a pantallas descartadas (携帯HP, トピックス, フォトギャラリー) y a kiosk no se ofrecen. `BookmarkTest`: la cabecera enlaza la ruta nueva y cada URL de `bookmark_reset.sql` resuelve a una ruta existente | (este) |

### Clientes / ventas / reservas (S2)

| pantalla (URL) | fallo | corrección + prueba Pest | commit |
|---|---|---|---|
| ficha `/admin/clientes/{cd}`, 来店処理 `customer_search.php` y 7 vistas más de clientes (lookup, photos, point-history, custom-records, visit-records, processing) | `<title>` «顧客登録情報 - Laravel»: usaban `config('app.name')` en vez del nombre del sitio (`$site_name` del legacy) | `LegacyUi::siteName()` en las 9 vistas de `resources/views/admin/clientes`; `QaClientesTest` («titula la ficha y 来店処理…») | 4df0081 |
| ficha, pestaña 有料会員状況 (`?tab=14`) de un 無料会員 | Error de JS `Cannot read properties of null (reading 'addEventListener')`: sin botón 変更する el script lo usaba igual (bug heredado del legacy) | `tabs/tab14.blade.php` sale si no existe `#paidMemberSendBtn`; `QaClientesTest` («no lanza error de JS…») | 4df0081 |
| CSV `/admin/clientes/export?format=standard o ex02`, 来店履歴 CSV `/admin/clientes/reports/visit-csv/download` | Cuerpo en Shift_JIS pero cabecera `text/x-csv; charset=utf-8` (Laravel añade utf-8 si no hay charset) | `text/x-csv; charset=Shift_JIS` en `CustomerCsvExporter` y `Reports/VisitCsvService`; `QaClientesTest` («declara Shift_JIS…») | 4df0081 |

Clientes (master y umeda01): lista y paginación (10/50 por página, primera/última), búsqueda con filtros combinados AND/OR/NOT (tienda + sexo + edad, 来店回数, 会計金額, 予測来店日, 削除済; recuentos cotejados con la BD), alta y edición (master y umeda01, con 住所検索), borrado y 削除済, ficha con todas sus pestañas, 来店処理 (ficha y `customer_search.php`, インターバル), puntos (añadir/restar), sellos, 集計 (`customer_sumup_list` 業種別/県別, point1/point2/stamp1, 来店履歴 CSV), CSV estándar y DM, configuración (form_config, グループ, 来店動機, ランク, 振り分け, 追加情報, 入退室, カード). umeda01: solo 梅田店 (76), sin 削除 ni CSV (403). Sin 500 ni SQLSTATE. 3 fallos corregidos. Para S0/S1: en `crmsystem2` la contraseña de master no es `master` como dice el brief (ver `tb_staff.login_pass`); お問合せ de la cabecera abre `/system/contact/?wintype=no_headers` (404); `favicon.png`/`favicon.ico` 404 en el login. Datos demo: ningún `tb_stamp_log` tiene `kaikei`, así que el 集計 stamp1 sale a 0 (correcto según el SQL legacy). CSVインポート y table-export dan 403 a master (`nucleo.maintenance`, ya anotado). Ventas y reservas: pendientes (otra ronda).

### Promoción (S3)

Datos (fase A, `PromocionDemoSeeder`, marca `[demo-promocion-limites]`): 25 cupones más (caducado, 31/12, 29/02/2028, fin de mes, título de 100, oculto, 2 de baja, 0 y 999999 puntos) repartidos por tienda; 38 envíos más (fin de mes de 12 meses, 1/1 0:00, 29/02/2024, asunto de 100 y cuerpo largo, sin destinatarios, con 不達 —6 socios con `error_send_cnt`, 4 en `mailmaga_flg` 9—, 2 de baja, 16 デコメール, 3 de cupón) y 3 programados (fin de mes, 31/12, 29/02/2028), con 登録者 por tienda y 本部; 23 push (fin de mes, largo, 0 y 99999 puntos, sin destinatarios, de baja); 6 encuestas sin respuestas; rango oculto y rango de baja asignados a socios demo (los 4 niveles por tipo los crea S2); 新着情報 largo y de baja.

| pantalla (URL) | fallo | corrección + prueba Pest | commit |
|---|---|---|---|
| Datos demo: 過去配信 de メルマガ/デコメール como usuario de tienda (umeda01) | No ve ningún envío: los demo tenían `登録者` = cd del staff, y la lista de tienda filtra por 登録者 = tienda (フッター表記店舗) | `登録者` = cd de tienda (o -1) en `PromocionDemoSeeder` · `DemoSeederTest` (`登録者`, reparto por tienda) | ver abajo |
| テンプレート管理, テストメールアドレス, アンケートテスト, 過去配信詳細… (`/admin/promocion/*`) | 404 de los 絵文字: las plantillas del backup guardan `<img src="../decomail/js/tiny_mce/plugins/emoji…">`, relativo a `system/category3/<carpeta>/` | Ruta `admin/promocion/decomail/js/tiny_mce/…` → 301 a `legacy/system/category3/decomail/js/…` · `QaTerceraPasadaTest` | ver abajo |
| DM発行 (`/admin/promocion/dm/customer_list`) | Error de JS `myFlexSelect is not a function` (el buscador de S2 sin su plugin ni el datepicker) | La vista carga `myFlexSelect.js` y `ui.datepicker` · `QaTerceraPasadaTest` | ver abajo |
| クーポン一覧, 過去配信, 予定配信, クーポン利用数確認 (vuelta de 削除/修正 con `kensu=`) | 1 fila por página (`input('kensu', 20)` con '' → null → 1) | `(int) ($request->input('kensu') ?: 20)` · `QaTerceraPasadaTest` | ver abajo |
| Envíos de メルマガ en :8081 | Cada envío de texto avisaba a **b.g-api.net** real (BD con `push_api_host = 1`, `domain = localhost`) | `config/promocion.php` `push_trigger`: en `APP_ENV=local` → `LogPushTrigger` (solo log); `CRM_PUSH_TRIGGER=gapi` lo fuerza · `QaTerceraPasadaTest` | ver abajo |

Probado en :8081 hasta ahora: recorrido de 145 pantallas como master y 131 como umeda01 (sin 500 ni 403 inesperados); cupón QA-S3 alta → lista (梅田店) → 修正 → 削除; CSV de クリックカウンタ (SJIS, `"	"` como el legacy); PDF de DM (300 socios). Sin fallo de código: imágenes de スタンプ `hp/uploads/c4/stamp/*.gif` (archivos subidos de producción, tampoco están en el repo legacy). スマトピ (404) espera F8.

### マイページ / 端末 / señalización (S4)

### Operaciones / 勤怠 / integraciones (S5)

Web local `http://localhost:8081` (checkout principal en `laravel13`, BD `crmsystem2` con el esquema `signage`). La muestra no se tocó.

## Segunda pasada: `laravel13` 1c56ac2 (2026-11-27)

El mismo script, sobre `:8081` con `laravel13` en 1c56ac2 (más a7d724b, que solo toca documentación). Se hizo `composer install` y no había migraciones pendientes. La lista de URL de `config/menu` se regeneró: ahora son 128 e incluyen 勤怠 (`/admin/operaciones/kintai*`, `working/calendar`, `working/report`).

| Usuario | Pantallas abiertas | 500 | En blanco | 404 | Error de JS | 403 |
|---|---:|---:|---:|---:|---:|---:|
| master | 134 | 0 | 0 | 3 | 0 | 7: condiciones que el menú oculta, `nucleo.maintenance` (5, entre ellas CSVインポート), `clientes.ext-shop.enabled` (ポイント管理) y `operaciones.working-report.enabled` (帳票管理, apagado por defecto) |
| qa_partial | 131 | 0 | 0 | 3 | 0 | 28: las 7 de master más las de sin permiso (category0, 顧客新規, マイコレ, 求人新規/職種, スタッフ新規/グループ, シフト区分, 勤怠 パスワード/各種設定) |

`storage/logs/laravel.log` no registró ninguna línea durante las dos pasadas.

Estado de los hallazgos de la primera pasada:

| Hallazgo | Estado |
|---|---|
| ホームページ管理 y データベース管理 (núcleo) | Corregidos |
| プッシュ通知 フォロー/自動通知新規作成 (404) | Corregidos: el menú apunta a `/admin/promocion/{follow,auto}/…_new?kind=…` (200) |
| 顧客ランク設定, error de JS de `@json` | Corregido: 0 errores de JS |
| ポイント管理 `point_setting.php` (404) | Tiene ruta. Da 403 aquí porque esta BD no tiene `clientes.ext-shop.enabled`, la misma condición que oculta la entrada del menú |
| スマトピ連携 `smatopi_message.php`, `smatopi_sent_history.php` (404) | Siguen en 404: pendientes de la decisión F8 (¿se usa スマトピ?) |
| ビーコン管理 `beacon_list.php` (404) | Sigue en 404: ビーコン en pausa, solo modelos, sin pantallas (provisional) |

Las tres 404 que quedan son las dos pantallas de スマトピ y la de ビーコン, las mismas que figuran como pendientes en [paridad.md](paridad.md). Mientras esperan una decisión, siguen en el menú. El enlace externo `https://smatopi.net/login` no se abre.

## Primera pasada (`laravel13` anterior a la integración de la regresión)

**Método** (`Doc/qa/scripts/regresion.js` con Playwright: plantilla con `__USER__`/`__PASS__`):

1. Se entra como `master` y como `qa_partial`. Para `qa_partial` se copiaron a `crmsystem2` el staff 900, el grupo 900 y sus 9 filas de `tb_kengen` desde `crmsystem2_nucleo` (ver [permisos.md](permisos.md)).
2. Se recorren por anchura todos los enlaces de la cabecera, de システム選択, del menú lateral (con submenús) y de la navegación propia de 予約管理 (`#yoyakuNavi` y sus ventanas `openPopWindow`), empezando por las portadas `system/category0|1|3|5|6|7|9`.
3. Después se abren directamente las 125 URL de `config/menu/*.php`, incluidas las que el menú oculta por permiso o por bandera de módulo.
4. En cada pantalla se anota el estado HTTP, el texto visible (página en blanco si tiene menos de 30 caracteres), los textos de error (`Server Error`, `SQLSTATE`, `Undefined`…), las peticiones XHR, JS o CSS que fallan y los errores de JS.
5. Para no escribir nada se saltan los enlaces de borrar, reiniciar y logout, y `alert`/`confirm` no se ejecutan (`confirm` responde «no»).

**Resultado:**

| Usuario | Pantallas abiertas | 500 | En blanco | 404 | Error de JS | 403 |
|---|---:|---:|---:|---:|---:|---:|
| master | 132 | 0 | 0 | 8 | 1 | 5 (condiciones `nucleo.maintenance` / `nucleo.shop-create`: sin IP de desarrollo, como el legacy) |
| qa_partial | 129 | 0 | 0 | 8 | 1 | 24 (sin permiso: category0, 顧客新規, マイコレ, 求人新規/職種, スタッフ新規/グループ, シフト区分) |

`storage/logs/laravel.log` no registró ningún error durante las dos pasadas: los 404 son URL sin ruta y no se registran.

## Núcleo: corregido en `l13/nucleo`

| URL | Qué pasaba | Arreglo |
|---|---|---|
| `/system/category4/` (pestaña y tarjeta de システム選択 ホームページ管理) | **404**. La pestaña sale porque `tb_base_system` cd 4 está activo, pero category4 se descartó entera (S4: sin consumidor) | `LegacyUi::visibleModules()` oculta el cd 4 igual que el 8 (`BaseSystem::DISCARDED_CD`); NUCLEO.md §2 y `LayoutTest` |
| `/system/category0/config/db_config.php` (menú データベース管理, con `nucleo.maintenance`) | **404** con IP de desarrollo o login de soporte. La pantalla está descartada (editor de BD crudo) pero seguía en el menú | Se quita la entrada de `config/menu/base.php` |

## Módulos: para S0

| Módulo | URL / menú | Qué pasa | Arreglo propuesto |
|---|---|---|---|
| promocion (S3/S4) | `/system/category3/follow/follow_new.php?kind=followPush` (プッシュ通知 › フォロー通知新規作成) | **404**: la URL del legacy no tiene ruta | En `config/menu/promocion.php` usar `/admin/promocion/follow/follow_new?kind=followPush`, que ya responde 200 («フォロー通知登録情報»), o redirigir la URL legacy |
| promocion (S3/S4) | `/system/category3/auto/auto_new.php?kind=autoPush` (自動通知新規作成) | **404**, mismo motivo | `/admin/promocion/auto/auto_new?kind=autoPush` (200, «自動通知登録情報») |
| promocion (S3) | `/system/category3/smatopi/smatopi_message.php`, `smatopi_sent_history.php` (スマトピ連携) | **404**: pendientes en la paridad, «uso por confirmar», pero visibles en el menú en esta BD | Migrarlas o quitarlas del menú hasta decidir |
| clientes (S2) | `/system/category1/rank/customer_rank_list.php` (顧客ランク設定) | **Error de JS** `Syntax error, unrecognized expression: # raitenRankList`: no se puede ordenar la lista de 来店ランク. `ranks/index.blade.php:152` `@json(implode(',', $types))` se compila como `json_encode(implode(', ', $types))`, porque Blade parte los argumentos de `@json` por las comas, así que sale `"kingaku, raiten"` | `{{ Js::from(implode(',', $types)) }}` o `{!! json_encode(implode(',', $types)) !!}`. Es la única `@json` con una coma dentro de una cadena |
| clientes (S2) | `/system/category1/customer/point_setting.php` (ポイント管理, solo con `clientes.ext-shop.enabled`) | **404**: sin ruta | Migrarla o confirmar que es descartada y quitarla del menú |
| terminales (S4) | `/system/category7/beacon/beacon_list.php` (ビーコン管理) | **404**: pendiente (el menú lo avisa: «aún no migradas») | Pendiente de S4 |

Fuera de esta lista: el enlace externo `https://smatopi.net/login` (スマトピ管理画面), que no se abrió.

## Notas

- En esta BD los menús de 端末管理 y 予約管理 salen vacíos para master por las banderas de módulo (igual que en la muestra, ver [diferencias-terminales.md](diferencias-terminales.md)). Por eso se abrieron también las URL de `config/menu` directamente: todas responden 200 salvo las de las tablas anteriores.
- Con `qa_partial` se abren con 200 pantallas cuyo menú se oculta, por ejemplo `karute_new.php` sin permiso de alta. Eso es de permisos, no de esta regresión: ver [permisos.md](permisos.md) y `permisos-*.md`.
