# Regresión de punta a punta (S1, 2026-11-27)

## Tercera pasada: testeo real por módulo con datos (Antony, 2026-11-27)

Web `http://localhost:8081` (BD `crmsystem2` recargada con `migrate:fresh` + `bd-legacy.sh` + `db:seed`). Cada sesión recorre con Playwright todas sus pantallas como master y como usuario limitado: listar, buscar, alta, edición, borrado, CSV, PDF y paginación. Compara el HTML y el comportamiento con development.crm-s.net, en **solo lectura**.

Cada sesión escribe solo en su subsección. Formato de cada fallo: `| pantalla (URL) | fallo | corrección + prueba Pest | commit |`. Al final de cada subsección va una línea de resumen: pantallas probadas, fallos encontrados y corregidos, y pendientes.

### Núcleo / 基本情報 (S1)

Recorrido con Playwright en `:8081` como master. Lo creado lleva el prefijo «QA-S1» y se borró al terminar; lo cambiado en 機能設定 y 権限管理 se dejó como estaba, comprobado en la BD (admin_master con el mismo md5 que antes). La muestra solo se abrió para comparar.

| Pantalla (URL) | Fallo | Corrección + prueba Pest | Commit |
|---|---|---|---|
| Cabecera y ショートカット管理 (`bookmark*.php`) | Los accesos directos enlazaban la URL legacy de `tb_bookmark`. Las pantallas movidas a `/admin/…` daban 404: スタッフ一覧, クーポン一覧, メルマガ y otras; 40 de los 63 accesos de fábrica no tenían ruta (12 de ellos son pantallas descartadas o de kiosk) | `Bookmark::targetUrl()`: usa la URL legacy si conserva su ruta, si no la de Laravel (`/admin/promocion/<dir>/<archivo>` en category3 y un mapa para スタッフ, マイページ y 求人). Los accesos a pantallas descartadas (携帯HP, トピックス, フォトギャラリー) y a kiosk no se ofrecen. `BookmarkTest`: la cabecera enlaza la ruta nueva y cada URL de `bookmark_reset.sql` resuelve a una ruta existente | 4cd2fa7 (verificado en `:8081` tras fusionar: los 5 accesos probados dan 200) |
| お問合せ de la cabecera (`/system/contact/?wintype=no_headers`), hallado por S2 | 404: 要望・お問い合わせ (`system/contact/index.php`, `contact_confirm.php`, `contact_complete.php`) no estaba migrada | Migrada en la ventana emergente: formulario, confirmación y envío con `ContactMail` en cola a `crm.notify.contact` (`crm@extlink.co.jp`, como el legacy). master elige tienda; los demás envían la suya. `verify` evita el doble envío. `ContactTest` | (este) |
| `<head>` común (login y todas las pantallas), hallado por S2 | `/system/img/favicon.ico` y `favicon.png` daban 404. El legacy tampoco los tiene (la muestra también da 404) | Favicon nuevo con la marca «CRM» del logo de la cabecera, en `public/legacy/system/img/`. `ContactTest` comprueba que se sirven | (este) |

### Clientes / ventas / reservas (S2)

### Promoción (S3)

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
