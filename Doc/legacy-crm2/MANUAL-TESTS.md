# Pruebas manuales en el navegador

> Recorrido a mano, pantalla por pantalla, del sistema del personal (`/system/`) en PHP 8.5.12, en Chrome. Empezado: 2026-11-03. Rama `refactor/fase-0`.
> Cada fila es una entrada del menú. Se abre la pantalla, se pulsa cada botón y se ejecuta cada función (buscar, alta, confirmar, editar, borrar…) con datos de prueba creados para ello.

**Leyenda:** ✅ funciona · ⚠️ funciona con una observación · ❌ falla (se corrige o se anota por qué no) · 🔧 fallaba y se corrigió en esta pasada · ⏳ pendiente · ➖ no aplica en Docker (POS, servidor externo…).

## Cómo se prueba

- **Errores de PHP visibles, como en producción:** `display_errors = On` en `docker/php.ini`, y `docker/dev-show-errors.php` (solo local, fuera de git) lista al final de cada página todos los avisos y fatales de esa petición. Una pantalla solo cuenta como ✅ si no muestra ninguno.
- **Consola y red:** se revisa la consola de JavaScript y las peticiones (AJAX con error 500 o respuesta rota).
- **Datos de prueba:** se crean con el prefijo `TEST` y se borran al terminar cada módulo, salvo que una pantalla los necesite.
- **Módulos activados para la prueba:** la BD de prueba no tenía contratados la mayoría de submódulos (por ejemplo, todo 端末管理 y マイページ管理). Se activaron los 27 que conocen los menús, más 販売管理 y 女の子管理, y todos los subservicios (`tb_base_system_grandchild`). Copia de la configuración original en las tablas `zz_test_bak_base_system*`.
- **Fuera de alcance:** `admin/` (sin su BD), las páginas que conectan con un POS externo, el módulo `category11` (sin tablas, ver `BROKEN-FILES.md`) y `system/create_customer.php` y `create_shop.php`, que nunca se abren.

## Resumen

| Módulo | Pantallas | ✅ | ⚠️ | ❌ | 🔧 | ➖ | ⏳ |
|---|---:|---:|---:|---:|---:|---:|---:|
| 1. 顧客管理 | 20 | 5 | 1 | 0 | 14 | 0 | 0 |
| 2. 販売管理 | 2 | 0 | 0 | 0 | 2 | 0 | 0 |
| 3. 販売促進管理 | 56 | 41 | 2 | 0 | 10 | 3 | 0 |
| 4. マイページ管理 | 21 | 12 | 1 | 0 | 8 | 0 | 0 |
| 5. ホームページ管理 | 7 | 3 | 0 | 0 | 3 | 1 | 0 |
| 6. 業務支援管理 | 15 | 5 | 2 | 0 | 8 | 0 | 0 |
| 7. 端末管理 | 8 | 1 | 1 | 0 | 5 | 1 | 0 |
| 8. 予約管理 | 25 | 7 | 1 | 0 | 15 | 2 | 0 |
| 9. 女の子管理 | 2 | 0 | 0 | 0 | 0 | 2 | 0 |
| 10. 基本情報管理 | 7 | 4 | 0 | 0 | 3 | 0 | 0 |

**Pasada completa.** Lo que queda para el final está en `PENDING.md`, sección 0.

## Fallos encontrados en esta pasada

Fallos que afectan a muchas pantallas a la vez. Los de una sola pantalla van en su fila.

| # | Fallo | Causa | Alcance | Estado |
|---|---|---|---|---|
| F1 | 住所検索 (dirección por código postal) no hacía nada; las casillas marcadas por el usuario no se leían | `jQuery.attr('value')` y `attr('checked'\|'selected'\|'disabled')`: jQuery 1.3.2 devolvía el valor que el usuario escribió; la 3.7.1 (paso 20d) devuelve el del HTML inicial. Sin error en la consola | ~536 usos en más de 100 archivos | 🔧 Capa `common/js/jquery-compat-legacy.js` (en las 35 copias de jQuery): `attr` vuelve a leer y escribir el estado real, como en 1.3.2 |
| F2 | Respuestas AJAX con `{'clave' : 'valor'}` (comillas simples) se ignoraban | jQuery 1.3.2 las evaluaba; desde la 1.4 usa `JSON.parse` y las rechaza sin avisar | 6 `zip.php`, `ajax_shop_point_config.php`, `ajax_shop_stamp_config.php`, `change_syukkin.php` | 🔧 Esas 9 respuestas pasan a `json_encode`; además la capa acepta el formato viejo (con aviso en la consola) para las que no se hayan encontrado |
| F3 | Con `display_errors = On` (producción) cada pantalla imprime decenas de avisos; dentro de un `<script>` rompen el JavaScript de la página (`Unexpected token '<'`), y dentro de un atributo rompen el HTML (pestañas de la ficha de cliente) | PHP 8 pasó "variable/clave no definida" de *notice* (oculto en PHP 5) a *warning* | 34,678 sitios en el log histórico | 🔧 `tools/scripts/undefined-guard.php`, con el valor que usaba PHP 5 (`null`): 3,300 sitios del log (paso 48); **sin depender del log**: 27,521 lecturas de `$_REQUEST`/`$_POST`/`$_GET`/`$_SESSION`/`$_SERVER`/`$_COOKIE`/`$_FILES` (`--superglobals`), 2,422 acumulaciones `$x .= …` sin valor previo (`--accumulators`) y 245 `list(…) = explode(…)` (`array_pad`) |
| F4 | Con `display_errors = On` también se imprimen las **deprecaciones** de PHP 8.5.12 y los avisos de compilación | `null` como índice de array o como parámetro de texto de una función de PHP; `continue` dentro de `switch` (`admin/api/class/Customer.php`, `appview/class/Customer.php`) | 1,543 + 28 + 4 sitios | 🔧 `--retype`: donde PHP convertía el `null` en `''` o `0`, se pone ese valor; `null-parametros.php` para el resto; `continue;` antes de `break;` quitado (era un `break`). Lint de los 2,568 PHP con los errores visibles: 0 avisos |
| F6 | Con `display_errors = On` los avisos acaban **dentro de los archivos descargados** (CSV) y de las respuestas AJAX, y dentro de `<meta http-equiv="Refresh">`, que deja la página atascada en "更新中です" | Los mismos avisos de F3, impresos en una respuesta que no es una página | CSV de clientes y de correo postal, renombrar categorías, informes por AJAX | 🔧 Con F3; en cada módulo se comprueban también descargas y respuestas AJAX |
| F7 | Con `display_errors = On` los avisos acaban **dentro de los campos ocultos** de los formularios y se envían como datos: un envío programado daba fatal (el texto no cabía en la columna) y una notificación push no se creaba | Mismo origen que F3 | Confirmaciones de お知らせ y push | 🔧 Con F3; en cada formulario se comprueba que ningún campo oculto contenga avisos |
| F8 | Un segundo `session_start()` en la misma petición imprime un *notice* (antes oculto); en una respuesta AJAX rompe el JSON y la pantalla muestra un error aunque el dato se guardó | Páginas que abren la sesión y luego incluyen `session.php` | 223 llamadas en 220 archivos | 🔧 `tools/scripts/session-start-guard.php`: solo abre la sesión si no está abierta |
| F5 | Botones que no hacían nada, sin error visible para el usuario: guardar la programación de rangos (顧客ランク振り分け設定) | Selector `input[name=month[]]` sin comillas: jQuery 1.3.2 lo aceptaba, jQuery 3 lanza un error de sintaxis | 3 en `customer_rank_schedule.php`; los armados con variables no se pueden buscar | 🔧 Comillas en esa pantalla y la capa reintenta con comillas cualquier selector así (aviso en la consola) |
| F10 | Un registro con imagen moría con fatal (店舗詳細管理) | Una función de PHP llamada con argumentos de más o de menos: PHP 7 (producción) avisaba, devolvía `null` y seguía; PHP 8 lanza `ArgumentCountError`. `corregir-argumentos-php8.php` solo cubría una lista fija (`mktime`…) | 11 llamadas en `html/`, 0 en `src/` | 🔧 `chmod($dir, 0777, true)` en `company_register.php`. `tools/scripts/argument-count-check.php` las lista todas con la reflexión de PHP 8.5.12; las otras 10 (`crypt()` de un argumento en `category0`, `pg_field_num()` de tres en `exp_cmd/verUp/`) en `PENDING.md` |
| F9 | Las pasadas que leen el log solo corrigen las pantallas que alguien ha abierto: quedaban avisos en las que no se habían visitado todavía. `$add_scripts` (bloque `$(document).ready` de casi todas las pantallas) y `$page` (dentro de un campo oculto, como F7) salían en pantallas que la prueba a mano iba encontrando de una en una | Mismo origen que F3, pero en variables normales en vez de claves de petición | 5,983 lecturas en 1,149 archivos | 🔧 `undefined-guard.php --locals`, **sin depender del log**: guarda toda lectura de una variable que no asignan ni el archivo, ni los archivos que incluye, ni los que lo incluyen (un parcial como `system/inc/search_set2.php` recibe `$connect` de su página). `--explain=archivo` muestra esa resolución |
| F11 | Los modales de jQuery UI salían al final de la página en vez de sobre el formulario, y se podía hacer clic fuera de ellos | jQuery UI 1.7.2 ponía por JavaScript `position:absolute`, `overflow`, `z-index` en el diálogo y tamaño y `z-index` en el fondo; 1.13.3 (paso 20e) lo deja a una hoja de estilos que estas páginas nunca cargaron. La posición calculada queda en `relative` y el fondo mide 0×0. El paso 20e solo midió errores de JavaScript, acordeón y calendario, así que no se vio | Cada página según la copia que cargaba en `master`. **Comparado con el servidor de desarrollo (`development.crm-s.net`, código de `master` sin migrar, 2026-11-09), modal real abierto en los dos lados, mismas medidas de caja, contenido, zona de botones y botón:** `yoyaku_koumoku_new` (3 diálogos), `card_signage/list`, `customer_list` y los `alertDialog`/`confirmDialog` de `customer_list`, `smatopi_message` y `smatopi_sent_history`. Con un modal de prueba en los dos lados: `push_follow_list`, `digital_signage/movie_program_new`, `kiosk/display_new`. **Ya rotos en `master` y se dejan igual** (cargaban 1.11 sin su CSS: `relative` y el fondo no bloquea): `kiosk/movie_program`, `point_setting`, `mobile_mycolle_edit*`, `kiosk/display_new`. `kiosk/movie_program_new`: en desarrollo su fragmento AJAX da 500, no se pudo comparar; por el código, al cargarlo se activa 1.8rc3. `touchcation/tagsignage`: igual que en `master`. **Sin medir:** `touchcation/timecard` (su BD no responde), `admin/master` (sin BD), `category11` (sin tablas) | 🔧 `system/js/jquery-ui-dialog-compat.js`, cargado desde `Base.php` y en las dos páginas de Smatopi: según el nombre del archivo de jQuery UI que carga la página, repone lo que hacía esa versión. 1.7.2: posición, fondo, altura sin el borde y botones sin `.ui-button`; 1.8rc3: igual menos la posición; 1.11 con su CSS: el texto del botón dentro de `span.ui-button-text`. Las demás no se tocan. Usa los eventos `dialogcreate` y `dialogopen` en `document`, para que aguante que la página cargue jQuery UI otra vez. `digital_signage/movie_program_frm.php`, que recarga también jQuery, carga el archivo otra vez. No toca CSS. Única diferencia que queda: el fondo es `fixed` del tamaño de la ventana y no `absolute` del documento; cubre lo mismo |

## 1. 顧客管理 (`/system/category1/`)

### 顧客管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 1 | 顧客新規登録 | `/system/category1/customer/customer_new.php` | Alta completa de un cliente `TEST` (todos los campos, campos personalizados obligatorios), 住所検索 con 5300001, confirmar y registrar; luego ficha | 🔧 | Tenía 80 avisos y 住所検索 no funcionaba (F1, F2, F3); ahora 0 avisos y rellena 大阪府 / 大阪市北区 梅田. ⚠️ Un nombre con comilla (`O'Neil`) se guarda como `O’’’’Neil`: el alta escapa dos veces y `get_form()` cambia `'` por `’`. Mismo código y resultado que en PHP 5: no se toca sin decisión (`PENDING.md`) |
| 2 | 顧客検索 | `/system/category1/customer/customer_list.php` | Búsqueda sin filtros (22,323 resultados) y **una búsqueda por cada uno de los 103 campos**; ficha del cliente con sus 18 pestañas; editar y guardar (también los campos personalizados); CSV, CSV de correo postal y 配信; borrar un cliente (`TEST-DEL`). Paginación, 表示件数 y el resto de enlaces: recorrido automático (ver nota del módulo) | 🔧 | Las 103 búsquedas sin avisos tras F3. **Con `display_errors` On los avisos se escribían dentro de los CSV descargados** (el de correo postal empezaba por `<b>Warning</b>`): corregido, comprobado decodificando el Shift_JIS. La paginación no la había recorrido en la primera pasada (Antony encontró `$form_content` en `page=12`); ya estaba corregido por la pasada siguiente. Cada cliente aparece dos veces: copias de `cd` de la BD de prueba. El borrado es lógico (`disp_flg = 9`) |
| 3 | 来店処理 | `/system/category1/customer/customer_search.php` | Registrar una visita del cliente `TEST` por número de socio | 🔧 | "来店処理しました。"; tenía 7 avisos en `cStamp.php` |
| 4 | 登録・検索項目設定 | `/system/category1/customer/form_config.php` | Guardar sin cambios (314 campos idénticos después) y guardar un cambio real (casilla CSV de 管理番号), comprobarlo y restaurarlo | ✅ | — |
| 5 | 顧客情報集計 | `/system/category1/shukei/shukei_list.php` | Abrir 会員登録データ集計 (ventana), los dos tipos de informe (業種別, 県別) | 🔧 | 22,324 clientes por sexo y edad; tenía avisos en `customer_sumup_list.php` y `search_set2_sumup.php` |
| 6 | 顧客グループ設定 | `/system/category1/customer/customer_group_category_new.php` | Crear `TEST グループ`, editar el nombre, subir con ▲, borrar | 🔧 | Tenía 4 avisos al registrar. El borrado es lógico (`disp_flg = 9`) |
| 7 | 初回来店動機項目設定 | `/system/category1/customer/raiten_douki.php` | Crear, editar, ▲ y borrar un motivo `TEST` | ✅ | — |
| 8 | 顧客ランク設定 | `/system/category1/rank/customer_rank_list.php` | Cambiar y restaurar la visibilidad en マイページ; alta, edición y borrado de un rango por importe y otro por visitas; casilla de visibilidad del rango | 🔧 | La casilla usa `attr('checked')`: sin F1 no ocultaba el rango; ahora guarda 9 y 1 correctamente |
| 9 | 顧客ランク振り分け設定 | `/system/category1/rank/customer_rank_schedule.php` | Programar (30 días, 1 de enero a las 03:00), ver ON, desactivar | 🔧 | No se podía guardar (F5). ➖ La tarea programada no corre en Docker: falta `crontab` (`PENDING.md` 1.1) |
| 10 | 顧客追加情報設定 | `/system/category1/customer/search_cate_list.php` | Las 18 subpantallas de las 9 categorías; crear categoría, crear campo, hacerlos visibles (aparecen en el alta y en el buscador), renombrar, borrar campo y categoría | 🔧 | Tras renombrar, la página se quedaba en "更新中です": el aviso caía dentro de `<meta http-equiv="Refresh">` y la redirección no ocurría. Corregido |

### 初期設定管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 11 | カードリーダーモード設定 | `/system/category1/config/felica_mode.php` | Cambiar el modo y el intervalo, comprobar y restaurar | ⚠️ | Guarda en la fila `tb_felica_mode.cd = 2`, que la BD de prueba no tenía (se creó): sin ella la pantalla no guarda nada y no avisa. Comprobar que exista en producción (`PENDING.md`) |
| 12 | カード管理 | `/system/category1/config/card_new.php` | Cambiar a 登録モード y volver a 運用モード | ✅ | ➖ Asignar una tarjeta necesita el lector FeliCa |

### 売上管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 13 | 売上データ作成 | `/system/category1/karute_menu/karute_new.php` | Formulario de venta: buscar socio por AJAX, elegir personal y menú (3,500), descuento manual del 10 % (3,150), confirmar; aparece en el historial del día | ✅ | — |
| 14 | 売上データ削除 | `/system/category1/karute_menu/karute_delete_list.php` | Lista por tienda, filtro por fechas y personal, borrar la venta de prueba | 🔧 | Tenía avisos de `$touroku_date_search` y `$staff_search` dentro de un heredoc |
| 15 | メニュー管理 | `/system/category1/karute_menu/karute_menu.php` | Crear `TEST カット` (3,000), editar (3,500), borrar | 🔧 | Tenía 10 avisos en el formulario de alta |
| 16 | 割引管理 | `/system/category1/karute_menu/karute_waribiki.php` | Crear `TEST 割引`, editar, borrar | 🔧 | Tenía 12 avisos en el formulario de alta |
| 17 | 経費管理 | `/system/category1/karute_menu/keihi.php` | Crear `TEST 経費` (1,234), editar (2,000), borrar | ✅ | — |
| 18 | スタッフ別集計 | `/system/category1/karute_menu/staff_total.php` | Elegir tienda (carga el personal por AJAX), personal y rango de fechas | 🔧 | **Fatal** con un rango de fechas: `strtotime()` recibía el array de `explode()` (en PHP 5 daba `null` y el informe salía). Corregido con el mismo resultado; el límite de 60 días nunca funcionó (`PENDING.md`) |
| 19 | 日報・週報・月報 | `/system/category1/karute_menu/report.php` | Un día, rango de fechas, una tienda y todas | 🔧 | El mismo **fatal** que la fila 18, en `ajax_report.php` |

### 入退室管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 20 | 入退室一覧 | `/system/category1/entrance/working_calendar.php` | Cambiar de mes, abrir la lista de cada día, paginación | 🔧 | ➖ Las entradas y salidas reales llegan desde el lector |

**Recorrido automático del módulo (2026-11-04):** `exercise.js` sigue, con la sesión del personal, todos los enlaces y formularios de lectura del contenido de las 26 pantallas de partida, hasta dos niveles (paginación, 表示件数, pestañas, calendarios, fichas…): **338 peticiones, 0 avisos, 0 fatales** con `display_errors` On. Lo que borra o escribe se probó a mano (filas de arriba). Antes se comprobó que el detector encuentra avisos y deprecaciones en páginas de control.

## 2. 販売管理 (`/system/category2/`)

### 商品情報管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 21 | 商品検索・一覧 | `/system/category2/item/list.php` | Buscar, abrir cada producto, borrar el producto de prueba | 🔧 | Todo el módulo tenía 6 avisos por página en `src/Layout/BaseFrame.php`: las herramientas no cubrían `src/` y ahora sí. El borrado es lógico (`disp_flg = 0`) |
| 22 | 商品新規登録 | `/system/category2/item/edit.php` | Alta con texto enriquecido en los dos CKEditor (el HTML se conserva), edición con canje por puntos, subida de imagen (se generan 800 y 320 px), 画像削除 | 🔧 | `$resize_path` nunca se definió (tampoco en `master`): `writeImage(null)` reescribía el mismo archivo; ahora `writeImage()`, mismo resultado sin aviso. ➖ En Docker `hp/uploads` es de `root` y PHP-FPM corre como `apache`: no se puede crear la carpeta de la imagen (se creó a mano para la prueba). En producción la carpeta debe ser escribible por el servidor web |

**Recorrido automático del módulo:** 14 peticiones, 0 avisos tras la corrección. **PHPUnit destapó una regresión del paso 17** en `src/MobileShop/CustomerForMobileShop.php`: el escape de las búsquedas múltiples usaba `$this->connect`, que ningún llamador asigna (`setConnect()` no se usa), y daba `TypeError` en la API de la app (`api/mobile_shop/customer.php`) y en la ficha 14 del cliente. Corregido pasando la conexión que abre `fetchList()`; PHPUnit vuelve a la línea base (solo fallan los 3 tests que llaman a APIs externas). Las demás clases donde el paso 17 usó `$this->connect` la asignan en su constructor.

## 3. 販売促進管理 (`/system/category3/`)

### お知らせ配信管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 23 | マイコレ登録用QR | `/system/category3/mail_mag/base_mycolle.php` | Pantalla del QR de マイコレ: 印刷 (vista de impresión) y ダウンロード del PNG | ⚠️ | La descarga entrega un archivo con el aviso de `filesize()` dentro porque **el QR no existe en Docker**: falta `hp/uploads/c3/`. Mismo comportamiento en PHP 5 con `display_errors` On (`PENDING.md`) |
| 24 | メルマガ用QR | `/system/category3/mail_mag/base_mailmaga.php` | Igual que la fila 23, para el QR de メルマガ | ⚠️ | Mismo caso que la fila 23 |
| 25 | 新規お知らせ配信 | `/system/category3/mail_mag/mail_customer_select.php` | Buscar el cliente `TEST`, redactar, prueba a la dirección de prueba (llega a MailHog), programar para 2027/01/01, modificar y anular | 🔧 | La confirmación tenía 17 avisos; uno dentro de un `<script>` borraba la función de enviar prueba (`sendTestMail is not defined`). Al modificar, **fatal**: un aviso dentro del campo oculto `pic_name` viajaba como dato y no cabía en la columna (`varchar(50)`). Corregidos |
| 26 | 予定配信お知らせ一覧 | `/system/category3/mail_mag/mail_future_list.php` | Ver la お知らせ programada y abrir su modificación | ✅ | — |
| 27 | 過去配信お知らせ一覧 | `/system/category3/mail_mag/mail_past.php` | Listado de envíos pasados, con el envío TEST recién hecho | ✅ | — |
| 28 | テンプレート管理 | `/system/category3/mail_mag/mail_template.php` | Crear una plantilla, editarla y borrarla (por AJAX) | 🔧 | El formulario tenía 12 avisos |
| 29 | テストお知らせアドレス管理 | `/system/category3/mail_mag/test_mail_address.php` | Crear, editar y borrar una dirección de prueba | 🔧 | Tenía 4 avisos |
| 30 | クリックカウンタ | `/system/category3/mail_mag/mailmag_analyze.php` | Contador de clics | ✅ | — |

### 来店履歴管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 31 | 来店履歴一覧 | `/system/category3/visit/visit_stats2.php?shop_cd=1&kikan=0` | Historial de visitas con las tres opciones de periodo y con una tienda y todas | ✅ | — |
| 32 | カードリーダ設定 | `/system/category3/visit/touch_config.php` | Cambiar el intervalo del lector (0 → 3600), comprobar y restaurar | ✅ | — |

### 予約管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 33 | 予約新規登録 | `/system/category3/reservation/reserve_customer_search.php` | Buscador de clientes para reservar | ✅ | — |
| 34 | 予約・来店一覧 | `/system/category3/reservation/reserve_calendar.php` | Calendario de reservas y cambio de mes | ✅ | — |
| 35 | 予約カテゴリ登録・変更 | `/system/category3/reservation/category_entry.php` | Alta de `TEST 予約カテゴリ`, edición del nombre y borrado | ✅ | Se guarda en `tb_reserve_name`; borrado comprobado en la lista |
| 36 | 予約項目設定 | `/system/category3/reservation/config.php` | Guardar con una casilla desmarcada, comprobar y restaurar | ✅ | Las casillas se guardan bien gracias a F1 |

### ポイント管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 37 | ポイント仕様管理 | `/system/category3/point/point_config.php` | Los 8 formularios de la pantalla: cambiar y restaurar el plazo de caducidad (180 → 13 → 180) y los puntos por importe (1000/1 → 2000/3 → 1000/1); ventana 通知内容 (plantilla del aviso), guardada y restaurada | ✅ | — |

### スマトピ連携

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 38 | メッセージ作成 | `/system/category3/smatopi/smatopi_message.php` | Solo carga | ➖ | Servicio externo (smatopi.net): no se puede operar desde Docker |
| 39 | メッセージ送信履歴 | `/system/category3/smatopi/smatopi_sent_history.php` | Solo carga | ➖ | Servicio externo |
| 40 | スマトピ管理画面 | `https://smatopi.net/login` | — | ➖ | Fuera del repositorio (smatopi.net) |

### スタンプ管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 41 | スタンプデザイン管理 | `/system/category3/stamp/stamp_pattern.php` | Los 79 diseños: desmarcar uno, guardar, comprobar y restaurar | ✅ | Para volver a marcarlo hubo que usar la BD: con 78 marcados y `stamp_max` en 5 el JS deshabilita los no marcados (correcto, y funciona gracias a F1) |
| 42 | スタンプ仕様管理 | `/system/category3/stamp/stamp_config.php` | Cambiar el máximo de sellos (5 → 30), comprobar y restaurar | ✅ | — |
| 43 | スタンプルール一覧 | `/system/category3/stamp/stamp_rules_list.php` | Listado de reglas | ✅ | — |
| 44 | クーポンお知らせ機能管理 | `/system/category3/stamp/oshirase_coupon.php` | Elegir tienda (carga por AJAX) y número de sellos, 表示 | ✅ | — |

### デコメール管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 45 | 新規デコメール配信 | `/system/category3/decomail/mail_customer_select.php` | Igual que la fila 25 con el editor TinyMCE: prueba HTML a MailHog, programar y anular | ✅ | — |
| 46 | 予定配信デコメール一覧 | `/system/category3/decomail/mail_future_list.php` | Ver y anular la デコメール programada | ✅ | — |
| 47 | 過去配信メール一覧 | `/system/category3/decomail/mail_past.php` | Listado de envíos pasados de デコメール | ✅ | — |
| 48 | テンプレート管理 | `/system/category3/mail_mag/mail_template.php` | Alta de `TEST テンプレート` (categoría メルマガ用), comprobada en la BD, y borrado desde la lista (lógico, `disp_flg = 9`) | ✅ | El desplegable de categoría y el botón ←挿入 insertan la plantilla en el cuerpo del correo |
| 49 | クリックカウンタ | `/system/category3/decomail/mailmag_analyze.php` | Contador de clics de デコメール | ✅ | — |

### 自動お知らせ管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 50 | 自動お知らせ新規作成 | `/system/category3/auto/auto_new.php` | Crear un envío automático (1 de enero, 10:00) para el cliente `TEST` | 🔧 | Avisos en confirmación y envío |
| 51 | 自動お知らせ設定一覧 | `/system/category3/auto/auto_mail_future_list.php` | Ver, pausar (停止) y anular el envío automático | ✅ | — |
| 52 | テンプレート管理 | `/system/category3/mail_mag/mail_template.php` | Igual que la fila 48 | ✅ | — |

### フォローお知らせ管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 53 | フォローお知らせ新規作成 | `/system/category3/follow/follow_new.php` | Crear un envío de seguimiento (3 días tras la visita) | 🔧 | Avisos en confirmación y envío |
| 54 | フォローお知らせ設定一覧 | `/system/category3/follow/follow_mail_future_list.php` | Ver y anular el envío de seguimiento | ✅ | — |
| 55 | テンプレート管理 | `/system/category3/mail_mag/mail_template.php` | Igual que la fila 48 | ✅ | — |

### アンケート管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 56 | アンケート一覧 | `/system/category3/anketo/anketo_cate_list.php` | Alta de `TEST アンケート`, alta de un componente de tipo ラジオボタン con 3 opciones (オプション数をセット), edición, marcar como obligatorio y borrado del componente | 🔧 | `anketo_form_detail.php` tenía `$add_scripts` sin definir y `anketo_form_list.php` metía el aviso de `$page` **dentro del campo oculto** `page` (F7). Corregido con F9 |
| 57 | 過去アンケート配信一覧 | `/system/category3/anketo/anketo_cate_list.php?which=sended` | Listado de encuestas emitidas, con detalle y 回答解析 | ✅ | — |
| 58 | テンプレート管理 | `/system/category3/mail_mag/mail_template.php` | Igual que la fila 48 | ✅ | — |
| 59 | クリックカウンタ | `/system/category3/anketo/anketo_analyze.php` | Contador de clics de アンケート | ✅ | — |

### マイクーポン管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 60 | クーポン新規登録 | `/system/category3/mycoupon/mycoupon_new.php` | Alta de `TEST クーポン` (vigencia 1 semana, nota), edición del título y envío a TEST-001 por el flujo completo (condiciones → contenido → confirmación → 配信) | ✅ | Ningún campo oculto con avisos en la confirmación (F7) |
| 61 | クーポン一覧 | `/system/category3/mycoupon/mycoupon_list.php` | Listado y filtro por tienda y por uso | ✅ | — |
| 62 | 過去クーポン配信一覧 | `/system/category3/mycoupon/mail_past.php` | Listado de envíos pasados de cupones | ✅ | — |
| 63 | テンプレート管理 | `/system/category3/mail_mag/mail_template.php` | Igual que la fila 48 | ✅ | — |
| 64 | 使用ランキング | `/system/category3/mycoupon/mycoupon_ranking.php` | Ranking de uso, con y sin filtros de tienda y uso | ✅ | — |
| 65 | クリックカウンタ | `/system/category3/mycoupon/mycoupon_analyze.php` | Contador de clics de マイクーポン | ✅ | — |
| 66 | クーポン利用数確認 | `/system/category3/mycoupon/mycoupon_stats.php` | Recuento de cupones usados hoy | ✅ | — |

### お客様アンケート管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 67 | お客様アンケート新規登録 | `/system/category3/anketo2/anketo_cate_new.php` | Alta de `TEST お客様アンケート`, alta de un item y registro con アンケートを登録する | ✅ | Mientras es borrador el título sale `『』`: la consulta pide `disp_flg = '1'`. Es correcto, no un fallo |
| 68 | お客様アンケート一覧 | `/system/category3/anketo2/anketo_cate_list.php` | Listado, casilla 選択 (guarda por AJAX, comprobada tras recargar), 詳細, 解析 y 回答 | 🔧 | `anketo_answer_analyze.php` tenía `$tb_c3_anketo2_sub_count` sin definir. La casilla usa `attr('checked')`: funciona por F1 |

### プッシュ通知

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 69 | プッシュ通知新規作成 | `/system/category3/push/push_customer_select.php` | Buscar el cliente `TEST`, crear una notificación programada (2027/01/01), anularla | 🔧 | No se creaba: el campo oculto `sendParameter` llevaba el texto de un aviso. Después se guardaba pero la pantalla decía "予期しないエラー": la respuesta AJAX empezaba por el *notice* de un segundo `session_start()` y por el aviso del archivo del cron. Corregidos; en Docker se creó la carpeta `cron_schedule/` (ver `PENDING.md` 1.1). La prueba real a dispositivos (テスト配信) no se ejecutó: llama al servicio externo |
| 70 | 予定通知設定一覧 | `/system/category3/push/push_follow_list.php?type_flg=scheduled` | Ver y anular las notificaciones programadas | ✅ | — |
| 71 | 過去配信内容一覧 | `/system/category3/push/push_past.php` | Listado de envíos pasados de push | ✅ | — |
| 72 | フォロー通知新規作成 | `/system/category3/follow/follow_new.php?kind=followPush` | Alta completa de una notificación de seguimiento (condición → contenido → プレビュー → 配信), creada con ID 31 | 🔧 | `follow_new_confirm.php` tenía `$data_array['mail_type']` sin definir. El `sendParameter` oculto ya sale limpio (F7 del paso 52) |
| 73 | フォロー通知設定一覧 | `/system/category3/push/push_follow_list.php?type_flg=follow` | Listado de notificaciones de seguimiento, con la recién creada | ✅ | — |
| 74 | 自動通知新規作成 | `/system/category3/auto/auto_new.php?kind=autoPush` | Alta completa de una notificación automática (mes y día → cliente → contenido → 配信), creada con ID 32 | ✅ | — |
| 75 | 自動通知設定一覧 | `/system/category3/push/push_follow_list.php?type_flg=auto` | Listado de notificaciones automáticas, con la recién creada | ✅ | — |

### リマインダお知らせ管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 76 | リマインダお知らせ新規作成 | `/system/category3/reminder/reminder_new.php` | Crear un recordatorio (1 día antes de la reserva, 09:00) | 🔧 | Avisos en confirmación y envío |
| 77 | リマインダお知らせ設定一覧 | `/system/category3/reminder/reminder_mail_future_list.php` | Ver y anular el recordatorio | ✅ | — |
| 78 | テンプレート管理 | `/system/category3/mail_mag/mail_template.php` | Igual que la fila 48 | ✅ | — |

## 4. マイページ管理 (`/system/category6/`)

### マイページ管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 79 | マイコレ管理 | `/system/category6/user_hp/mobile_mycolle_edit.php` | Subir una imagen al hueco 0 de la portada, con URL y 全店舗; アップロード; 画像を削除する (diálogo de jQuery UI) | ✅ | — |
| 80 | カバーフロー画像設定管理 | `/system/category6/user_hp/mobile_mycolle_edit_coverflow.php` | Igual que la fila 79 (muestra la misma sección, `aplicover`) | ✅ | — |
| 81 | バナー画像設定管理 | `/system/category6/user_hp/mobile_mycolle_edit_banner.php` | Subir un banner, celda en ﾃﾞﾌｫﾙﾄ選択 y vuelta a 自由選択, URL después de guardar, borrar el banner | 🔧 | **El borrado no hacía nada para el usuario** (F6): el JSON llegaba precedido de 12 avisos de `ajax_apliFileUpload.php:253` y jQuery lo descartaba; el borrado sí ocurría en el servidor. Con una celda en ﾃﾞﾌｫﾙﾄ選択 sin imagen, aviso en la línea 813. Corregidos. Que no aparezca el campo de URL al subir es intencionado (línea comentada en el original) |
| 82 | HOMEメインイメージ管理 | `/system/category6/user_hp/mobile_mycolle_edit_main.php` | Subir, guardar y borrar la imagen 0; desmarcar y volver a marcar `home_image_flg[1]` | 🔧 | Guardar sin imágenes mostraba `parsererror: Unexpected token '<'`: `$res` solo existe si hay imágenes, y el aviso iba delante del JSON (F6). Corregido |
| 83 | サムネイル画像管理 | `/system/category6/user_hp/mobile_mycolle_edit_thumbnail.php` | Igual que la fila 82, con `home_thum_flg[1]` | ✅ | Comparte `ajax_apliFileUpload.php` con la fila 82: se probó ya corregido |
| 84 | マイページデザイン管理 | `/system/category6/user_hp/mobile_hp_edit.php` | Plantilla `chic` → diseño 11 → 設定する, comprobado en `admin_master.mypage_template` y restaurado a 0; las 17 páginas `config_*`; en `config_10002`, subir imagen, 表示する, 設定する, 画像を削除する | 🔧 | Las 17 páginas `config_*` avisaban de `$check1`/`$check2` dentro de un heredoc (la herramienta no entra en heredocs): se inicializan a `null` en `pic_config_function.php`. El borrado solo quita `0_moto.jpg` y deja `pic_flg` vacío: igual que en `master` |
| 85 | トピックス管理 | `/system/category6/user_hp/topics.php` | Alta de `TEST トピック` con imagen y enlace `tel:`, edición con imagen nueva, 表示情報を更新, borrado | ✅ | ➖ En Docker faltaba la carpeta `hp/uploads/c6/user_hp/topics` (`mkdir` no recursivo): se crearon todas las de `c6` (ver `PENDING.md` 2d) |
| 86 | オススメ管理 | `/system/category6/user_hp/recommend.php` | Alta de `TEST オススメ` con imagen y enlace de imagen, edición, 表示情報を更新, borrado. Push en "no" (llama al servicio externo) | ✅ | — |
| 87 | ヘッダ画像管理 | `/system/category6/user_hp/header.php` | Alta para 全店舗 con imagen, edición a 表示しない | ✅ | Esta pantalla no tiene borrar (`header_delete_register.php` solo lo usa `category4`). La fila de prueba se quitó a mano y se repuso el contador `hatuban` |
| 88 | マイフリーページ管理 | `/system/category6/user_hp/my_freepage_list.php` | Página `TEST フリーページ`: edición, ▼▲, borrado. Sus artículos (`my_freepage.php`): alta con imagen, edición con imagen nueva, 画像添付解除, 表示しない, 表示情報を更新, borrado | 🔧 | Avisos al cargar en las dos pantallas y `$pic_suffix`. **F7:** el aviso caía dentro del `value` de `page_title` y el recorrido automático creó una página cuyo título era el texto del aviso. Corregido. El borrado de imagen solo desmarca `pic_flg`: igual que en `master` |

### 携帯HP管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 89 | 携帯HPデザイン管理 | `/system/category6/shop_hp/mobile_hp_edit.php` | Diseño 19 guardado en `admin_master.mobile_hp_template` y restaurado a 0; las páginas `config_*`; en `config_10000`, subir imagen, 表示する y 非表示にする | 🔧 | La copia de `pic_config_function.php` de `shop_hp` tenía el mismo aviso que la fila 84. Esta versión no tiene borrar imagen |
| 90 | 新着情報管理 | `/system/category6/shop_hp/whatsnew.php` | Alta de `TEST 新着` con imagen, edición, 表示情報を更新, borrado | ✅ | — |
| 91 | ヘッダ画像管理 | `/system/category6/shop_hp/header.php` | Subir imagen con 表示する y volver a 表示しない | ✅ | Edita siempre la fila fija `tb_hp_header.cd = 2` y guarda en `hp/uploads/c4/shop_hp/header/` (no `c6`): alta, vista y borrado usan la misma ruta. Restaurada la fecha de la fila |
| 92 | 店舗詳細管理 | `/system/category6/shop_hp/company.php` | Lista, detalle, 修正する, cambiar 追加情報1 y subir imagen, registrar; restaurar con 画像解除 | 🔧 | **Fatal al registrar con imagen:** `chmod($dir, 0777, true)`; PHP 7 avisaba, PHP 8.5.12 lanza `ArgumentCountError` (F10). Aviso de `$$act` en el título (errata de `$act`: ver `PENDING.md`). Corregidos |
| 93 | フリーページ管理 | `/system/category6/shop_hp/freepage.php` | Alta de `TEST フリー` con imagen, ▼▲, edición, 表示情報を更新, borrado | ✅ | — |

### フォトギャラリー管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 94 | 新規登録 | `/system/category6/digital_catalog/digital_catalog_new.php` | Alta de una foto con categoría nueva (カテゴリを追加, escribir, Enter), sugerencia de categorías existentes, comentario | 🔧 | Aviso de `$error` en `digital_catalog_frm.php` durante el envío: no se veía porque la página redirige. Corregido |
| 95 | 掲載一覧 | `/system/category6/digital_catalog/digital_catalog_list.php` | Filtros 全て, オススメ y por categoría; estrella y casilla de visibilidad (AJAX, comprobadas en la BD); 編集 con categoría añadida; 削除 | ✅ | — |
| 96 | カテゴリ管理 | `/system/category6/digital_catalog/digital_catalog_tag.php` | 編集 (AJAX), reordenar (el `stop` del `sortable`, comprobado en `rank`), 削除 de las dos categorías | ✅ | — |

### 求人情報管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 97 | 求人情報新規登録 | `/system/category6/recruit/recruit_new.php` | Envío vacío (mensajes de validación); alta completa con 職種, todos los campos y dos imágenes | ⚠️ | Aviso de `$error` corregido. ⚠️ **Con una sola tienda el desplegable de 職種 sale con opciones vacías y no se puede dar de alta:** el bucle lee `$row` y escribe `$data_array` (igual en `master`); con varias tiendas lo rellena el AJAX. Se probó llamando a ese AJAX. Ver `PENDING.md`. ➖ En Docker faltaba `recruit/uploads/temp/` (se creó) |
| 98 | 求人情報一覧 | `/system/category6/recruit/recruit_list.php` | Lista, detalle, 修正する con dos imágenes nuevas, 削除する | 🔧 | `recruit_update.php` tenía 79 avisos al abrirse (`$error_bg`, `$error_disp`, `$add_scripts`) y el de `$$act`. Corregidos |
| 99 | 求人職種管理 | `/system/category6/recruit/recruit_category.php` | Alta de `TEST 職種`, edición, borrado | ✅ | `submitChk2()` recibe el número de fila, no el `cd` (correcto en la pantalla; en la prueba se borró por error la fila de al lado y se repuso) |

**Recorrido automático del módulo (2026-11-05):** `exercise.js` desde las 21 pantallas, dos niveles: **95 peticiones, 0 avisos** (antes de corregir: 21 con avisos, todas de フリーページ). Las 41 pantallas de lectura del módulo (con `cd` y las `config_*`) comparadas con el código anterior y el nuevo sobre los mismos datos: 33 idénticas y las 8 que cambian solas (marcas de tiempo en las URLs de imagen) con la misma longitud. Configuración de `contents_master` y de las plantillas devuelta a su estado (copia previa comparada fila a fila) y las imágenes de prueba borradas.

## 5. ホームページ管理 (`/system/category4/`)

### 不動産管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 100 | 不動産管理 | `/blue-system/immovables/kanri` | Abrir el enlace del menú | ➖ | Es otro sistema, fuera del repositorio (como `azas_night`): en Docker da 404. `system_file_export.php` le copia `db.php` y `menu.html` por FTP |
| 100.1 | ページコンテンツ管理 | `/system/category4/page/page_list.php` | Contenido de tipo 記事: alta de un artículo con imagen, edición, visibilidad (チェックした情報を更新する), 表示件数, borrado. Contenido de tipo テキストリスト (2×2): alta, edición, borrado | 🔧 | **F7:** los avisos caían dentro de `name`, `comment` y el oculto `rep_cd` del alta de artículos y listas, y se habrían guardado. También avisos en la lista (`$i`, `$contents_title`) y en las confirmaciones (no se veían: redirigen solas). Corregidos. Sin un `cont_cd` válido, `entry_list.php` y sus hermanas mandan a `logout.php`: es su protección, no un fallo |

**Solo con IP de desarrollo** (`isDevelopmentAuthorityFromIP`): el bloque PCHP管理 del menú. El personal no lo ve, pero las pantallas responden a cualquiera con sesión.

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 100.2 | PCホームページ設定 | `/system/category4/pc_shop_hp/config.php` | Título, contenido e imagen; restaurado desde copia de `tb_hp_data` | ✅ | ➖ En Docker faltaban `hp/uploads/c4/pc_shop_hp/` y `pc_shop_hp/uploads/temp/` (`PENDING.md` 2d) |
| 100.3 | ヘッダ管理 | `/system/category4/pc_shop_hp/header.php` | H1, comentario e imagen | ✅ | — |
| 100.4 | フッタ管理 | `/system/category4/pc_shop_hp/footer.php` | Comentario, copyright, texto del enlace e imagen | ✅ | — |
| 100.5 | サイトマップ管理 | `/system/category4/contents/sitemap.php` | Alta de `TEST サイトマップ` (confirmación y registro), edición, ▲▼, borrado | 🔧 | Avisos en edición y confirmación, uno en `function.php:77` (validación compartida por todo el sistema). Una comilla se guarda como `O’’’’Neil`, igual que en el alta de clientes (`PENDING.md`) |
| 100.6 | コンテンツ設定 | `/system/category4/contents/contents_list.php` | Búsqueda, detalle, alta de un contenido 記事 con imagen y otro テキストリスト, edición (la validación pide los títulos de las filas), borrado; 検索項目設定 (`form_config.php`) guardado y restaurado | 🔧 | `contents_entry.php` tenía 21 avisos. `$GLOBALS['cate_session']` sin definir en 6 funciones de `function.php`, también de `category2`: `undefined-guard` no reconoce el aviso "Undefined global variable", se guardaron a mano |

**Recorrido automático del módulo (2026-11-05):** 24 pantallas de lectura de `category4` (incluidas las copias antiguas sin enlaces) y `exercise.js` desde las 7 del menú: 22 peticiones, 0 avisos. 547 páginas (las 524 estables y las del módulo) con el código anterior y el nuevo sobre los mismos datos (`--strip-php-messages`): 545 idénticas y las 2 que cambian solas con la misma longitud. Las copias antiguas de `recruit`, `shop_hp` y `stamp` cargan `session.php` antes que `db.php`: en producción cerraban la sesión y ahora dan fatal (`BROKEN-FILES.md`).

## 6. 業務支援管理 (`/system/category5/`)

### スタッフ管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 101 | スタッフ新規登録 | `/system/category5/staff/staff_entry.php` | Alta de `TEST スタッフ` con 住所検索 (5300001 → 大阪府 大阪市北区 梅田), foto, validación del correo, confirmación y registro | 🔧 | La pantalla final (`staff_entry_complete.php`) tenía `null` como índice (deprecación de PHP 8.5). ➖ En Docker `hp/uploads` es de `root` y faltaba `hp/uploads/c5/` (`PENDING.md` 2d). Una comilla se duplica en cada vuelta por la validación (`O''''Neil`), igual que en el alta de clientes |
| 102 | スタッフ検索・一覧 | `/system/category5/staff/staff_list.php` | Búsqueda por 名前, detalle con sus 4 pestañas, 修正する con foto nueva, 削除する | 🔧 | **"Array to string conversion"** dentro del oculto `post_wherestr` (F7): la sesión de búsqueda es un array. PHP 7 imprimía `Array` y el campo se ignora al enviar; ahora imprime lo mismo sin aviso. También `$input_value` en el buscador y el índice `null` del detalle |
| 103 | スタッフグループ設定 | `/system/category5/staff/staff_group.php` | Alta de `TEST グループ`, edición, ▲▼, borrado | ✅ | `submitChk2()` recibe el número de fila: se borró buscando el formulario por su `cd` |
| 104 | スタッフ検索項目設定 | `/system/category5/staff/form_config.php` | Desmarcar una casilla de 検索, comprobar en `tb_koumoku` y restaurar (copia comparada fila a fila) | ⚠️ | Guarda bien, pero la casilla del primer campo se **pinta** con `koumoku_select` en vez de `mail_mag_select` y sale siempre marcada. Igual en `master` (`PENDING.md` 2c) |
| 105 | インフォメーション管理 | `/system/category5/staff/information.php` | Alta para 全店舗 con un cargo, edición, borrado | 🔧 | Las filas de 全店舗 usaban `null` como índice (deprecación en la propia lista). El alta se bloquea en JavaScript si el mensaje está vacío (correcto) |

### 勤怠管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 106 | 勤怠一覧 | `/system/category5/working/working_calendar.php` | 先月/翌月 cruzando el año (2026/12 → 2027/1 → 2026/1), lista de un día con 表示件数 2 y su página 2, 更新する, CSV del día (Shift_JIS, sin avisos) | 🔧 | `$selected` en `inc/page_control.php` y avisos en las listas `work_list*`. **Es una de las pantallas de `mktime` con `is_dst` (`PENDING.md` 1b.1): en producción, con PHP 7, 先月/翌月 llevaban a 1970; ahora cambian de mes bien** |
| 107 | 勤怠データ一覧 | `/system/category5/kintai/index.php` | 勤怠データを追加する (09:00–18:00 con pausa), lista del día, 編集 (salida a 19:00), CSV diario y 削除 (`jConfirm`) | 🔧 | `$error` en alta y edición. CSV decodificado: la fila y los totales (10:00, 600 minutos) correctos |
| 108 | 端末パスワード変更 | `/system/category5/kintai/password_update.php` | Contraseña actual errónea (rechazada) y correcta (cambiada), con una contraseña de prueba puesta antes; tabla restaurada desde copia | ✅ | La contraseña real no se leyó ni se imprimió |
| 109 | 各種設定 | `/system/category5/kintai/config.php` | Horarios (基準, 始業, 終業) e imagen de fondo del terminal (se genera la miniatura de 150 px); CSV mensual por persona; シフト区分管理: alta, edición, ▲▼, borrado; tabla restaurada desde copia | 🔧 | `$pic_error` se imprimía antes del `<html>`. ➖ Faltaba `hp/uploads/c5/kintai/` (`mkdir` no recursivo) |

### 初期設定管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 110 | カードリーダーモード設定 | `/system/category5/config/felica_mode.php` | Cambiar a 運用出勤強制モード y volver a 運用モード; fecha de la fila restaurada | ✅ | — |
| 111 | 新規カード管理 | `/system/category5/config/card_new.php?card_flg=0` | 登録モード y vuelta a 運用モード | 🔧 | Sin `card_flg` avisaba (`$card_flg`). ➖ Asignar tarjetas necesita el lector FeliCa |
| 112 | 営業日設定 | `/system/category5/config/shop_work_calendar.php` | Marcar días como 休日, 更新 (営業日数 baja), desmarcar; diciembre enlaza a 2026/11 y 2027/1 | ✅ | Otra pantalla de `mktime` con `is_dst` (1b.1). En producción la navegación iba a 1970. El botón 更新 queda fuera del `<form>` en el HTML, pero el navegador lo asocia y funciona |

### スケジュール管理

Sin enlaces: el único (`シフト管理`, `schedule/shift.php`) está comentado en `left_navi5.php` ("ドラッグ方式のため").

**Solo en el servidor `202.218.51.245`** (condición por `SERVER_ADDR` en el menú de 勤怠管理): 帳票管理, 日別シフト管理 y 月別シフト管理.

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 112.1 | 帳票管理 | `/system/category5/working/working_chouhyou.php` | Carga y 確認する | 🔧 | 60 avisos (`$syukkin_time`, `$taikin_time`) |
| 112.2 | 日別シフト管理 | `/system/category5/schedule/staff_shift.php` | Turno 10:00–18:00 para `TEST スタッフ` desde el modal y 更新; filas de prueba retiradas | ⚠️ | Guarda bien, pero: un comentario se corta en la comilla (el JS arma el campo con comillas simples), al guardar crea una fila mensual para **cada** persona de la tienda, y un enlace lleva a `schedule/time_table.php`, que no existe (404). Todo igual en `master` (`PENDING.md` 2c) |
| 112.3 | 月別シフト管理 | `/system/category5/schedule/staff_shift_month.php` | Vista del mes con el turno de prueba | ✅ | — |

**Recorrido automático del módulo (2026-11-05):** 28 pantallas de lectura y `exercise.js` desde las 15 del menú: 287 peticiones, 0 avisos (antes, cientos en las listas de 勤怠); queda el 404 de `time_table.php`. 547 páginas (las 524 estables y las del módulo) con el código anterior y el nuevo: 546 idénticas y 1 que cambia sola con la misma longitud. Las tablas que se tocaron (`kintai_tb_config`, `tb_koumoku`, `tb_shop_work`, `tb_felica_mode`) se devolvieron a su copia previa, y las fichas, grupos, avisos, asistencias y turnos de prueba se borraron por la pantalla (lógico) o se retiraron.

## 7. 端末管理 (`/system/category7/`)

### ビーコン管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 113 | ビーコン管理 | `/system/category7/beacon/beacon_list.php` | Carga de lista, detalle y terminales | ➖ | Conecta con una BD externa (`b-develop.g-api.net`) que no responde desde Docker. Los avisos de variables de su detalle se guardaron igual |

### ペーパーサイネージ

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 114 | カード管理 | `/system/category7/card_signage/list.php` | Alta de `TEST カード` (AJAX), 詳細: 確認, edición, 停止 y 削除; 集計 y su CSV; descarga del QR | ⚠️ | **Un título con comilla no se guarda:** `cUrl.php` mete `$_POST['title']` en el SQL sin escapar (el `form2Db` de `control.php` recorre `$_REQUEST`, no `$_POST`). Es además una inyección SQL con sesión del personal; igual en `master`. Anotado en seguridad (`CONTINUE.md`), sin tocar. `aggregate.php` tenía 588 avisos y `csv_output.php` leía `$subSql` sin definir en un heredoc: corregidos. ➖ La descarga del QR pide `http://localhost/…` desde el contenedor `app`, que no llega al servidor web: el aviso acaba dentro del JPG (como las filas 23 y 24) |

### 電子看板管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 115 | 放映プログラム管理 | `/system/category7/digital_signage/movie_program_list.php` | Alta de `TEST プログラム`, detalle, edición ("この内容を登録し、選択中のディスプレイに反映する", escribe el XML de las pantallas), borrado | 🔧 | Avisos en registro y detalle (`$terminal_type_total`, `$child_in`, `explode(null)`). ➖ Faltaba `hp/uploads/c7/display/` para los XML |
| 116 | ディスプレイ管理 | `/system/category7/digital_signage/display_list.php` | Alta de `TEST ディスプレイ` con el programa de prueba, detalle, edición, borrado | 🔧 | Avisos en la carga del detalle |

### 情報端末管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 117 | 映像管理 | `/system/category7/kiosk/movie_program_list.php` | Alta con imagen de banda (帯画像), detalle, editor (`movie_program.php`: una diapositiva del stock a la zona izquierda y 登録 por AJAX), borrado de las tres altas de prueba | 🔧 | `ajax_movie_program.php` respondía con un aviso delante del JSON (F6), `$clickEvent` sin definir en un heredoc y avisos en alta, detalle y borrado. ➖ Faltaban `kiosk/uploads/temp/` y `hp/uploads/c7/kiosk/obi/` |
| 118 | 映像振り分け管理 | `/system/category7/kiosk/display_list.php` | Alta de `TEST 振り分け` con programa y zona táctil, detalle (muestra la `AppKey` del terminal), edición, borrado | 🔧 | El botón 削除 solo existe con IP de desarrollo: se probó enviando su formulario. El aviso de `display_delete.php` caía dentro de la URL de redirección. Corregidos |

### ICカード管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 119 | 端末管理 | `/system/category7/felica/terminal_list.php` | Lista, ▼▲ (orden restaurado), 表示件数 2 y su página 2 | ✅ | Los terminales los da de alta el propio dispositivo |
| 120 | カード管理 | `/system/category7/felica/card_list.php?sesClear=card` | Búsqueda con todas las opciones (全/未/済, AND/OR, por ID y nombre) | 🔧 | **Fatal en cualquier búsqueda:** sin resultados, la página pasaba a 0 y el `OFFSET` salía negativo. En PHP 7 la consulta fallaba en silencio y la lista salía vacía; en PHP 8.5.12, `pg_num_rows(false)` es un `TypeError`. La pasada "página 0" del catálogo no cubrió tres clases de paginación (`maClass_sysParts.php`, su `_v0.1` y `card_signage/cPageControl.php`): corregidas con el mismo `max(0, …)`. ➖ Asignar tarjetas necesita el lector |

**Recorrido automático del módulo (2026-11-05):** 29 pantallas de lectura y `exercise.js` desde las 7 del menú (sin ビーコン): 45 peticiones, 0 avisos. 549 páginas (las 524 estables y las del módulo) con el código anterior y el nuevo: 538 idénticas y las 11 que cambian solas con la misma longitud, 0 fatales. Programas, pantallas, 映像, 振り分け y la tarjeta de prueba borrados por la pantalla (lógico); imágenes y XML de prueba retirados.

## 8. 予約管理 (`/system/category9/yoyaku/time_table.php`)

Interfaz propia, sin menú lateral. Rutas relativas a `/system/category9/yoyaku/`.

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 8.1 | 予約表 | `time_table.php` | Día, 月間, 週間, 新規予約リスト, 来店リスト; popup de la reserva: 更新, 削除; 状況変更; cambiar hora; mover a otro empleado; descanso pintado | ✅ | Arrastrar con el ratón no se probó: se llamó a `ajax_move_objs.php`, el mismo destino |
| 8.2 | スタッフ別予約表 | `time_table_staff.php` | Carga del mes y vuelta tras guardar varios días | ✅ | — |
| 8.3 | 予約表 (nuevo) | `new_time_table.php` | Solo carga | ➖ | Nada la enlaza. Llena `$menuData` con otra estructura, así que el nombre del menú sale vacío, igual en `master` |
| 8.4 | 予約 (datos y menú) | `yoyaku.php` | Datos del cliente, elección de empleado y menú, 確定 | 🔧 | **El aviso de `$menu` caía dentro de una cadena de JavaScript y rompía el script: sin empleados ni menú no se podía reservar** |
| 8.5 | 時間選択 | `yoyaku_calendar.php` | Franjas sin día configurado, con día, con menú opcional | 🔧 | Sin día configurado, un aviso de `$n` caía dentro del enlace `新規設定を行う` |
| 8.6 | 顧客情報入力 y registro | `yoyaku_customer_select.php`, `yoyaku_register.php` | Reserva con correo (llegó a MailHog), con menú opcional y campos libres | 🔧 | Sin duplicar al recargar la página |
| 8.7 | 受付日設定 | `yoyaku_uketsuke_day.php`, `_register`, `_delete` | Alta, edición, descanso, borrado | 🔧 | Avisos en `$tb_yoyaku_koumoku_array` y en las horas |
| 8.8 | 受付日設定 (varios días) | `yoyaku_multi_uketsuke_day.php`, `_register` | Alta de dos días | 🔧 | — |
| 8.9 | 予約項目管理 | `yoyaku_koumoku_new.php`, `_register`, `ajax_yoyaku_koumoku_new.php` | Alta, edición, borrado, ▲▼, base y opcional | 🔧 | F11, avisos con `?shop=` |
| 8.10 | 予約項目カテゴリ | `yoyaku_koumoku_category.php`, `_register`, `ajax_…` | Alta, edición, borrado, ▲▼, 表示 | 🔧 | `$rank_kakunin_ctn == 0;` era una asignación mal escrita: ahora `=`, con el mismo resultado |
| 8.11 | スタッフ割当 | `staff_works.php`, `ajax_menu.php` | Asignar y quitar menú, tiempo propio | 🔧 | Abierta fuera de su popup se recarga en bucle tras guardar (`parent.location.reload()`): dentro del popup no ocurre |
| 8.12 | 設備管理 | `setsubi_new.php`, `setsubi_register.php` | Alta, edición, borrado | 🔧 | **F7: el aviso caía en el campo oculto `cd` y el alta entraba por la rama de actualización, sin crear nada** |
| 8.13 | フリー予約 | `free_yoyaku.php`, `_register`, `ajax_free_yoyaku_rank.php` | Guardar y reponer, ▲▼ de prioridad | 🔧 | — |
| 8.14 | 各店舗設定 | `yoyaku_config.php`, `ajax_yoyaku_config.php` | Guardar horario | 🔧 | — |
| 8.15 | 予約フォーム設定 | `yoyaku_form_config.php` | Guardar sin cambios: `tb_koumoku` idéntica | 🔧 | — |
| 8.16 | 機能設定 | `yoyaku_kinou_config.php` | Guardar sin cambios | ✅ | — |
| 8.17 | 注釈設定 | `yoyaku_comment_config.php` | Guardar sin cambios: mismo texto salvo CRLF en una fila | ✅ | Un `<textarea>` envía los saltos como CRLF, también en `master`. Repuesto |
| 8.18 | 予約カレンダー | `yoyaku_list_calendar.php` | Mes y navegación por URL | 🔧 | — |
| 8.19 | 一覧 | `yoyaku_list_shop.php`, `yoyaku_list_time_table.php`, `view_shop.php`, `yoyaku_revice.php` | Solo carga, con datos | ✅ | — |
| 8.20 | 年間カレンダー | `pop_calendar.php` | Carga y año | 🔧 | — |
| 8.21 | 当日予約一覧出力 | `print/yoyaku_list_print.php`, `csv_download.php` | Lista con la reserva y CSV sin avisos (F6) | ✅ | — |
| 8.22 | リアルタイム印刷 | `yoyakuPrint.php`, `ajaxYoyakuPrint.php` | Respuesta sin avisos, `print_flg` marcado | 🔧 | La impresora no se probó |
| 8.23 | Flujo `yoyaku_new_*` | `yoyaku_new.php`, `yoyaku_new_select_*`, `yoyaku_new_customer_*`, `yoyaku_new_register.php` | Formulario y envío | ⚠️ | Ya fallaba: columna y `yoyaku_new_calendar.php` inexistentes, ver `BROKEN-FILES.md` |
| 8.24 | 関連顧客 y búsqueda de socios | `related_customer.php`, `ajax_related_customer.php`, `ajax_search_kaiin.php` | Carga, búsqueda y vincular un socio: `tb_customer_cd` guardado y repuesto | ✅ | El vínculo se probó llamando a `ajax_related_customer.php`, sin pulsar la fila de la lista |
| 8.25 | Datos de reserva para POS | `ajax_getReservationData.php` | Da fatal | ➖ | Necesita el POS externo, ver `BROKEN-FILES.md` |

**Recorrido del módulo (2026-11-06):** 64 peticiones con parámetros y datos reales, y flujo de reserva completo. 541 páginas (las 524 estables y las del módulo) con el código anterior y el nuevo: 536 idénticas; de las 5 distintas, 4 cambian solas con el mismo código (llevan la hora) y 1 difiere en un tabulador de una línea en blanco; 0 fatales. `functional-flows` 48 de 48. Filas `TEST` en borrado lógico; horario, `FREE`, `reserve_flg` y `tb_koumoku` comparados con el backup y repuestos.

## 9. 女の子管理 (`/system/category8/`)

### 女の子管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 121 | 女の子情報管理 | `/system/category8/gal/gal_list.php` | Carga | ➖ | Conecta con la BD `azas_night`, que no existe en Docker: fatal al primer `pg_query`. Sin probar. En la portada (`select.php`) su botón sale gris y sin icono: no existe `img/v2/menu_icon_8.png` y la regla `#functionSelect_8 a` está vacía, igual en `master` (en el servidor de desarrollo el icono da 404) |
| 122 | 出勤表管理 | `/system/category8/gal/sche.php` | Carga | ➖ | Igual que la 121 |

## 10. 基本情報管理 (`/system/category0/`)

### 基本情報管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 123 | 基本情報確認・変更 | `/system/category0/config/config.php` | Confirmación y registro con la clave de prueba; `tb_shop` y `tb_staff` iguales | ✅ | **Cuidado:** el formulario trae `tb_office.pass` y al guardar lo escribe como clave del usuario `master`, igual en `master`. En esta BD son distintas: guardar sin mirar deja fuera a las herramientas |
| 124 | 機能設定 | `/system/category0/config/features_config.php` | 13 formularios con sus valores actuales: solo cambian las fechas y `sound_select[2]`, campo oculto a propósito | ✅ | `不達配信数をリセット` no se pulsó: cambia `tb_customer` de golpe. Su primer `UPDATE` se ejecutó en una transacción deshecha (22,324 filas) |
| 125 | 権限管理 | `/system/category0/config/authority.php`, `authority_contents.php`, `authority_contents_list.php` | Marcar un permiso y conservarlo; alta y edición de un contenido; guardar la lista | 🔧 | Avisos en el formulario de alta y en la lista |
| 126 | ショートカット管理 | `/system/category0/config/bookmark.php`, `bookmark_list.php` | Guardar y `reset`: 63 filas | ✅ | — |
| 127 | エリア管理 | `/system/category0/config/area.php`, `area_edit.php`, `area_confirm.php`, `area_register.php` | Alta, edición, hija, orden, flecha, borrado | 🔧 | Avisos de `$mail_error` y `$cateDel_error` en cada confirmación |

### 店舗情報管理

| # | Pantalla | Ruta | Qué se probó | Estado | Observación |
|---|---|---|---|---|---|
| 128 | 店舗検索・一覧 | `/system/category0/shop/shop_list.php`, `shop_detail.php`, `shop_new.php`, `shop_new_confirm.php`, `shop_new_register.php`, `shop_delete_register.php` | Búsqueda, detalle, alta, edición, borrado y recuperación | 🔧 | **Probado con `application_use_flg` a 0 en local:** con 1, guardar llama a la API de producción `b.g-api.net`. El aviso del alta a `support@glory.ne.jp` quedó en MailHog. Al editar se guarda `update_date` vacío, igual en `master` |
| 129 | 店舗グループ | `/system/category0/shop/shop_category.php` (incluye `shop_category_frm.php`) | Alta, edición, flechas, borrado | ✅ | — |

**Recorrido de los módulos 9 y 10 (2026-11-06):** 530 páginas con el código anterior y el nuevo: 530 idénticas, 0 fatales. `functional-flows` 48 de 48. Tablas de `category0` restauradas desde la copia y comparadas. **No se abrieron** los scripts de mantenimiento de `config/`: `db_fix*`, `test_fix`, `change_html_*`, `create_immovables`, `slim_*`, `upfile_fix`, `dbphp_fix`, `db_export*`, `export_all`, `backup`, `system*`, `versionUpAjax`, `db_config`.

## Pantallas restringidas por IP, por bandera o por servidor, y rutas sueltas (2026-11-13)

Encargo de Antony: tocar las vistas en las que no se había entrado. En Docker `$is_develop` siempre era `false` (PHP ve una IP `192.168.x.x` y `STAGE` no estaba definida), así que todo lo que el código restringe por IP quedó oculto en la prueba a mano. Para esta pasada, **temporalmente**: `STAGE=docker` en `.env` (el propio `Common::isDevelopmentAuthorityFromIP()` lo prevé), `contents_master.app_layout_mode` = `1` y `admin_master.beacon_use_flg` = `1`. Restaurar al terminar: ver `CONTINUE.md`, «Estado de la BD de prueba».

**Entradas del menú lateral que no se habían probado a mano (20):** IP o sesión de soporte (権限コンテンツ, システム設定, `slim_list`, DB設定, 店舗新規, CSVインポート); bandera `app_layout_mode` (ポイント管理, ポイント履歴, `base_point`, `csv_grant_points`, `push_history`, 予約機種一覧); `beacon_use_flg` (`point_rules_list`, `point_aggregate`); solo el smoke (`working_list`, `working_total_list`, `shop_hp/config`, `kujibiki_config`, `screen_saver_list`). Recorridas con `exercise.js` y abriendo sus ventanas emergentes: ~90 peticiones y 99 ventanas, 0 problemas.

| Qué se probó | Resultado |
|---|---|
| 45 formularios de ajustes guardados sin cambios (`features_config` 15, `system.php` 14, `stamp_config` 8, `kujibiki_config` 4, `shop_hp/config` 2, `slim_list`, `db_config`) con la BD comparada tabla por tabla antes y después | 🔧 **`system.php`, formulario de módulos: desactivaba 基本情報管理.** Las filas 8 y 10 no están en el formulario; sin su `cd`, `master` generaba `WHERE cd = ` (error de SQL, no cambiaba nada) y el `(int)` del paso 49 lo convirtió en `WHERE cd = 0`. Ahora, sin `cd`, no se actualiza, como en `master` (también en hijos y nietos). Mismo patrón en `stamp_pattern_register.php` (2 copias). El resto de cambios eran fechas de actualización y valores que la pantalla muestra por defecto (`stamp_percentage_*` 1000, `raiten_stamp_flg` "1" → "" que significa lo mismo, `sound_select`, ya documentado). BD repuesta y comprobada |
| Quiosco (`kiosk/display_*`, partes solo por IP): reordenar arriba y abajo, alta de `TEST-IP 振り分け` con selector de tienda, baja desde el detalle | ✅ Orden restaurado; baja lógica (cd 4) |
| 店舗新規 (`shop_new`, IP): alta de `TEST-IP 店舗` con `application_use_flg` a 0 (si no, llama a `b.g-api.net`); 削除 y 削除を取り消す en el detalle (IP); 検索対象 en el listado (IP) | ✅ Alta, baja y recuperación. 🔧 `$page_control` sin resultados. ⚠️ La opción 全て de 検索対象 no muestra nada: `Shop.php` busca `disp_flg = 'all'`, igual en `master` (`BROKEN-FILES.md`). Tienda retirada y `hatuban` repuesto |
| 不達配信数をリセット (`features_config`) | ✅ Pone `error_send_cnt = 0` a los 22,325 clientes (también donde era `NULL`). `tb_customer` repuesta |
| ポイント設定 (`point_setting`): alta `TEST-PT` y baja | ✅ |
| QR印刷 (`base_point` → `qr_print_pdf.php`) | ✅ Sin tiendas, su propio aviso; con una tienda, la página y el PNG del QR |
| CSVでポイント一括付与 (1 punto a TEST-001) y CSVインポート (1 cliente `TEST-IMP`) | ✅ 🔧 `$error` sin definir al ir bien (2 y 5 lecturas). Tablas repuestas |
| No se pulsan | Inicializar la BD, sincronizar BD o archivos con otro servidor (`system.php`), `upfile_fix` (borra `hp/uploads`), `backup`, `db_export2` y `export_all` (servidores externos), los `related_form*` (連結機能) |

**Rutas sueltas: 132 pantallas de `/system` a las que no lleva ningún enlace, formulario, redirección ni AJAX del proyecto.** Detector en el scratchpad de la sesión (busca el nombre del archivo en el mismo módulo y los nombres armados con `$menu`). Abiertas por GET con sesión, sin parámetros:

- **Pantallas sin enlace (60)**
  - 0 avisos (47): `category1/customer/user_csv.php`, `category11/yoyaku/yoyakuPrint.php`, `category3/coupon/pit_coupon.php`, `category3/visit/daycsv_download.php`, `category4/pc_shop_hp/report_list.php`, `category1/biyou/sejutsu_cate.php`, `category1/config/card_staff_select.php`, `category1/config/mail_comment.php`, `category1/customer/csv_export.php`, `category1/customer/csv_from_js.php`, `category1/customer/customer_sumup_list2.php`, `category1/rank/kingaku_rank_new.php`, `category1/rank/kingaku_rank_update.php`, `category1/rank/raiten_rank_new.php`, `category1/rank/raiten_rank_update.php`, `category1/shukei/customerDataOutPut.php`, `category1/shukei/model1.php`, `category1/shukei/model2.php`, `category1/shukei/point1.php`, `category1/shukei/point2.php`, `category1/shukei/shukei_mail_new_confirm.php`, `category1/shukei/shukei_mail_new_send.php`, `category1/shukei/stamp1.php`, `category11/item/master.php`, `category11/yoyaku/yoyaku_list_shop.php`, `category11/yoyaku/yoyaku_new_complete.php`, `category3/anketo2/anketo_analyze.php`, `category3/coupon/coupon_bg_new.php`, `category3/mail_mag/base2.php`, `category3/mycoupon/mycoupon_rireki.php`, `category3/visit/new_visit_stats.php`, `category3/visit/store_visit_list.php`, `category3/visit/visit_stats.php`, `category5/config/card_staff_select.php`, `category5/kintai/staff_shift_kind.php`, `category5/schedule/ajax_staff_shift_hatuban.php`, `category5/working/ng_work_list.php`, `category5/working/work_list2.php`, `category9/yoyaku/new_time_table.php`, `category9/yoyaku/yoyaku_list_shop.php`, `category9/yoyaku/yoyaku_new_complete.php`, `category1/customer/customer_export.php`, `category3/mail_mag/send.php`, `category3/mail_mag/mail_new1.php`, `category11/opening/reset.php`, `category5/schedule/schedule.php`, `category6/shop_hp/header_delete_register.php`
  - avisos que se explican abajo (8): `category1/customer/customer_recover_confirm.php`, `category1/customer/kanren_record_confirm.php`, `category3/mail_mag/mail_schedule.php`, `category3/coupon/coupon_send.php`, `category3/coupon/pit_coupon1.php`, `category3/mail_mag/mail_past1.php`, `category4/contents/contents_recover_confirm.php`, `category5/working/change_syukkin2.php`
  - redirigen a `logout.php` sin un `cd` válido (como en `master`) (3): `category4/page/report_entry.php`, `category4/page/textlist_entry.php`, `category4/page/report_entry2.php`
  - cierran la sesión (cargan `session.php` antes que `db.php`, igual que PHP 7) (1): `category4/page/report.php`
  - 403 por web desde el 2026-11-16, reescribía todo `tb_raiten_rireki` (1): `category3/visit/fix_visit_stats.php`
- **Portadas de carpeta (7)**
  - 0 avisos (7): `setting/index.php`, `category0/index.php`, `category12/index.php`, `category12/twitter/index.php`, `category2/index.php`, `category4/index.php`, `category12/facebook/index.php`
- **Páginas de prueba o muestra (18)**
  - 0 avisos (13): `category0/config/test.php`, `category0/config/test/test.php`, `category11/yoyaku/sample_array.php`, `category12/autoload.php`, `category9/yoyaku/sample_array.php`, `category11/test.php`, `category11/test2.php`, `category3/mail_mag/ajax_test_mail_address_staff.php`, `category3/mail_mag/mail_future_test.php`, `category3/visit/csv_test.php`, `category3/visit/csv_test2.php`, `category3/visit/stats_test.php`, `category3/visit/stats_test2.php`
  - avisos que se explican abajo (2): `category12/test.php`, `category3/anketo2/anketo_test.php`
  - cierran la sesión (cargan `session.php` antes que `db.php`, igual que PHP 7) (1): `category3/anketo/anketo_test.php`
  - 403 (módulo retirado) (1): `category8/gal/moto.php`
  - 403 por web desde el 2026-11-16, rehacía `tb_base_system_child` (1): `category0/config/test_fix.php`
- **Scripts de mantenimiento de `category0/config` (11)**
  - 0 avisos (11): `category0/config/dbphp_fix.php`, `category0/config/backup.php`, `category0/config/config_complete.php`, `category0/config/system_option.php`, `category0/config/change_html_initialize.php`, `category0/config/change_html_juku.php`, `category0/config/db_fix_light.php`, `category0/config/slim_del_href.php`, `category0/config/slim_href.php`, `category0/config/db_export2.php`, `category0/config/db_bigint.php`
- **Descargas CSV que solo llama el `.swf` (16)**
  - no se abrieron a propósito (16): `category1/customer/csv_download_template/csv_file_out.php`, `category11/yoyaku/print/csv_download/csv_file_out.php`, `category5/kintai/csv_download/csv_file_out.php`, `category9/yoyaku/print/csv_download/csv_file_out.php`, `category5/staff/csv_download/csv_file_out.php`, `category5/working/csv_download/csv_file_out.php`, `category1/customer/csv_download/csv_file_del.php`, `category1/customer/csv_download_ex02/csv_file_del.php`, `category1/customer/csv_download_template/csv_file_del.php`, `category11/yoyaku/print/csv_download/csv_file_del.php`, `category5/kintai/csv_download/csv_file_del.php`, `category5/staff/csv_download/csv_file_del.php`, `category5/working/csv_download/csv_file_del.php`, `category9/yoyaku/print/csv_download/csv_file_del.php`, `category1/customer/csv_download/csv_file_out.php`, `category1/customer/csv_download_ex02/csv_file_out.php`
- **APIs que llaman otros servidores (`tool/api`, CTI, `get_shukei_data`, `create_*`…) (20)**
  - no se abrieron a propósito (20): `desktop_request/ctiWindow.php`, `desktop_request/customerDetailHtml.php`, `desktop_request/jGetCustomerData.php`, `get_touroku_data.php`, `local_settings.php`, `tool/api/category1/addRaitenRecord.php`, `tool/api/category1/getCustomerDetail.php`, `tool/api/category1/getRaitenDetail.php`, `tool/api/category1/getRaitenList.php`, `tool/api/category1/getSearchCateFormData.php`, `tool/api/category1/searchCustomerList.php`, `tool/api/category5/getStaffList.php`, `news.php`, `create_customer.php`, `create_shop.php`, `tool/api/category0/setPosdomain.php`, `tool/api/category1/setRaitenDetail.php`, `s1.php`, `get_shukei_data.php`, `get_shukei_data2.php`

Corregido en las sueltas (con `undefined-guard.php` y `null-parametros.php` sobre el log del recorrido, más 6 a mano): 41 páginas daban avisos o fatales y quedan 12. Fatales corregidos con el resultado de PHP 7: `export_all.php` (conexión a `vizan.azas24.com`, `proteger-pg-conexion.php` ampliada con 8 variables más: 14 llamadas en 6 archivos), `kihon.php` sin conexión (`mail.php`, `mail_schedule.php`), `count(null)` en `customerDataOutPut.php`, `mt_rand(0, -1)` en `anketo_test.php`, `session_cache_limiter()` con la sesión abierta (`coupon/inc/kihon.php`). Las salidas de las 39 páginas comparadas con `HEAD`: 31 iguales, 4 que antes morían con un fatal, 3 con contenido dinámico y `csv_from_js.php`, que en `HEAD` escribía 5.8 MB de avisos y se cortaba a los 90 s.

Quedan con avisos, todos ya en `master` o por abrirlas sin parámetros: rotas en `master` (`mail.php`, `mail_schedule.php`, `kanren_record_confirm.php`, `category12/test.php` dan 500 también en desarrollo; `pit_coupon1.php` consulta `shop_master`); `contents_recover_confirm.php` busca columnas que `tb_c4_contents` no tiene; y sin su `cd` (`customer_recover_confirm`, `coupon_send`, `mail_past1`, `report_entry`, `textlist_entry`, `change_syukkin2`, `anketo2/anketo_test`). **Abiertas con un `cd` válido el 2026-11-17:** `contents_recover_confirm?cd=1`, `coupon_send?itemcd=1`, `mail_past1?mailmagcd=89`, `report_entry` y `report_entry2` (alta con `cont_cd=2`, y edición del informe 2 poniendo un momento `flg = '1'`, porque ninguno lo tenía), `textlist_entry?cont_cd=2`, `customer_recover_confirm` por POST con `TEST-DEL` (muestra el cliente y 「復元する」) y `change_syukkin2` (`tb_work_time` vacía: no cambia nada). Quedan en 0 avisos: 🔧 `$row_title[$row]` en `report_entry` y `report_entry2`, `$data_array['id']` (columna que `tb_c4_contents` no tiene) en `contents_recover_confirm`, `nl2br(null)` en `mail_past1`, y el `list()` de `pdf_flg` con `array_pad` en `report_entry2`. Las dos consultas por `id` y `email1` de `contents_recover_confirm` siguen fallando como en `master` (`BROKEN-FILES.md`). Salida igual a `HEAD` en las 8 URL (salvo `coupon_send`, que cambia sola entre peticiones). En desarrollo, `mail_past1` y `coupon_send` tienen la misma estructura que en local; category4 no tiene contenidos allí. **No se abrieron a propósito:** `category0/config/test_fix.php` (sin ninguna condición vacía y rehace `tb_base_system_child`) y `category3/visit/fix_visit_stats.php` (reescribe todo `tb_raiten_rireki`).
