# 08 — Quiosco y señalización (touchcation / signage / digitalsignage)
> Fuente: C:\Users\User\VivaTech\crm2-php8 (rama master = código ya migrado a PHP 8.5.12). Generado el 2026-11-26. Destino: Laravel 13 + PHP 8.5.

Alcance: `public_html/touchcation/` (305 PHP), `public_html/signage/` (92 PHP) y `public_html/digitalsignage/` (28 PHP) = **425 PHP**. Excluidos: `hp/` (obsoleta), `admin/` y `system/category8`. Dependencias con `hp/` (solo mención): los reproductores leen imágenes y XML de `hp/uploads/c7/...` (`nextouch_screen_saver/`, `display/<cd>.xml`) y `member_s`/`member_biz` redirigen a `hp/mailsyori/felica_detail.php`.

El **alta y mantenimiento** de terminales, programas, pantallas y materiales (`tb_terminal`, `tb_nextouch_*`, `tb_display`...) está en `system/category7/` y se documenta en `05-operaciones-terminales-reservas.md`. Este documento cubre el **lado que consume el dispositivo**.

Nota: no se ejecutó nada (solo lectura). Para la APK se leyeron las cadenas de `classes.dex` de las 4 APK distintas de `version/` (sin decompilar ni ejecutar).

## Resumen

| Módulo/submódulo | Carpeta | Nº PHP | Nº vistas reales (pantallas) | Complejidad (S/M/L/XL) | Puntos (1 punto ≈ media jornada de un agente) |
|---|---|---|---|---|---|
| Lanzador y contrato de la APK | `touchcation/launcher/` | 5 | 4 | M | 3 |
| Terminal de puntos/sellos/todo-en-uno, **serie S** (la que lanza el launcher) | `touchcation/common_s_keyboard/` + `point_s/` + `stamp_s/` + `all_in_one_apk_s/` | 30 | 7 | XL | 30 |
| Terminal de puntos/sellos, **serie N y copia `common_s`** (heredada) | `touchcation/common_n/`, `common_s/`, `point/`, `stamp/`, `all_in_one_apk/` | 67 | 0 propias (copias) + `control.php`/`point_appli.php` | M | 2 |
| Visita por GPS e ID | `touchcation/gps_s/`, `id_raiten/` | 13 | 8 | M | 6 |
| Registro de socios por NFC/PtoP | `touchcation/member_s/`, `member_biz/` | 13 | 11 | M | 6 |
| Asistencia (勤怠) en quiosco | `touchcation/kintai_crm_n/`, `kintai_crm_sp/` | 27 | 12 | L | 10 |
| Tarjeta de tiempo (admin de tarjetas del personal) | `touchcation/timecard/` | 8 | 4 | S | 3 |
| Ticket numerado (turnos de espera) | `touchcation/numbered_ticket/` | 9 | 8 | M | 6 |
| App de puntos por beacon (React) y API | `touchcation/point_app/`, `touchcation/api/` | 10 | 9 | M | 6 |
| Señalización Android (con sellos y visita), `signageApp` y `signage_s` | `touchcation/signage/`, `signageApp/`, `signage_s/` | 47 | 20 | L | 12 |
| Señalización Windows (POSViewer) | `touchcation/signage_w/` | 42 | 12 (solo lo que difiere de `signage/`) | M | 6 |
| Señalización con impresora de cupones (clientes tag/warmshop) | `touchcation/tagsignage/` | 34 | 22 | L | 10 |
| **Subtotal touchcation** | `touchcation/` | **305** | **~117** | | **100** |
| Señalización web: gestión (CMS) + reproductor + PDF/correo | `signage/` | 92 | 34 (14 de gestión + 3 del reproductor + 17 diseños) | L | 20 |
| Señalización digital Flash/HTML (cupones, juegos, inmuebles) | `digitalsignage/` | 28 | 22 | M | 9 |
| **Total** | | **425** | **~173** | | **129** |

Los puntos no incluyen el lado de gestión (`category7`) ni el POS, ya contados en otros grupos. Si el cliente confirma que la serie N, `tagsignage`, `signage_w` y `digitalsignage` no se usan (ver Riesgos), el total baja a unos 102 puntos.

---

## Contrato con la APK y con los dispositivos (lo que Laravel debe mantener)

Hay **siete tipos de cliente**. Solo uno (la APK Android) está en el repositorio, y solo compilada (`version/`).

| # | Cliente | Entra por | Identifica al dispositivo con |
|---|---|---|---|
| 1 | APK Android `jp.CrmShWeb` (WebView + NFC, Sharp/Panasonic) | `touchcation/launcher/` | `shop_cd`, `terminal_cd`, `androidId` en la URL |
| 2 | Navegador `MTBROWSER` (señalización) | `touchcation/signageApp/?tid=` | `tid` = AppKey (md5 de host y orden) |
| 3 | Windows con **POSViewer** (señalización y turnos) | `touchcation/signage_w/`, `numbered_ticket/` | Solo `shop_cd` (en `signage_w` ya no se valida el terminal) |
| 4 | App de beacons (React, `bundle.js` compilado) | `touchcation/point_app/?beacon_cd&uuid&major&minor&tb_shop_cd` y `point_app/api/index.php?<nombre>` | `tb_shop_cd`, login de personal |
| 5 | Tablet de `tagsignage` con impresora de tickets | `touchcation/tagsignage/` | `shop_cd`, `terminal_cd` |
| 6 | Pantalla de señalización web (navegador a pantalla completa) | `signage/view/?cd=<terminal>` | `cd` del terminal de `signage` |
| 7 | Reproductor Flash/navegador | `digitalsignage/?shop_cd=&display_cd=` | `shop_cd`, `display_cd` |

### 1. APK Android `jp.CrmShWeb` (hallazgos en las 4 APK distintas de `version/`)

Paquete `jp.CrmShWeb`, actividad `CrmShWebActivity`; permisos: INTERNET, NFC, WIFI, almacenamiento externo. Las 6 APK del repo son 4 binarios distintos (`sharp/2.7.0.1` = `sharp/2.701`; `android3/2.704` = `pos/2.704`; `sharp/2.702`; `sharp/2.703`).

1. **Configuración local**: lee la URL base de un archivo del dispositivo (`/mnt/sdcard/ini.ini`; en 2.704 `/Android/ini.ini` o `/ini.ini`). La APK **2.701–2.703 trae fija la ruta `/@test/launcher/`** (carpeta de desarrollo que no existe en el repo); la **2.704 trae `/touchcation/launcher/`**. Con 2.703 o anterior, el servidor debe seguir sirviendo `/@test/launcher/` si hay terminales con esa versión (pregunta abierta).
2. **Comprobación de red**: `GET <base>check.php?check=1` (espera un cuerpo con el mismo valor que `check`; en el servidor, `print h($_REQUEST['check'])`). Existe una copia idéntica en 15 carpetas.
3. **Versión**: `GET /version/sharp/v.php` devuelve solo el número en texto plano (hoy `2.703` en `sharp/` y `2.704` en `pos/`); si es mayor que su `versionName`, descarga `/version/sharp/<versión>.apk` con `DownloadManager` (tipo `application/vnd.android.package-archive`). **Las 4 APK usan siempre `/version/sharp/`**, nunca `android3/` ni `pos/`. `version/` está fuera de este grupo pero **debe publicarse tal cual** en Laravel (carpeta `public/version/`).
4. **Carga de la pantalla**: `GET <base>index.php?shop_cd=<n>&terminal_cd=<n>&androidId=<id>&orientation=PORTRAIT|LANDSCAPE&key=<texto>` (los nombres salen de las cadenas del binario; el orden exacto no se verificó; el servidor regenera `?key&shop_cd&terminal_cd&orientation&androidId` en `$get_str`). `key` **nunca se valida** (siempre vacío o ignorado).
5. **User-Agent**: 2.703 usa uno fijo `CRMRW107 ... Android 2.3.4`. Ningún PHP de `touchcation/` lo exige salvo `signageApp` (`MTBROWSER`) y el menú de `tagsignage` (`tagshops`).
6. **Puente JavaScript** (`addJavascriptInterface`, objeto `roid`). Métodos **que sí implementa** el APK (los 6 binarios): `getAndroidId`, `startPolling`, `stopPolling`, `startPush(url)`, `stopPush`, `startPtoP(url, doc)`, `stopPtoP`, `checkNfcFlg`, `toast(texto)`, `soundPlay(id)`.
7. **Callbacks que la APK invoca en la página** (`javascript:`): `pollingCallBack(idm, pmm, code, type)`, `pushCallBack(status)`, `startPtoPCallBack(status, idm, result)`, `checkNfcFlgCallback([{pollingFlg, pushFlg, ptopFlg}])` (se pasa por `eval`), `setAndroidId(id)`; 2.704 añade `setWindowData(...)` que ningún PHP define.
8. **Métodos que el PHP llama y la APK del repo NO tiene**: `roid.printData(ip, url)` (5 usos: `tagsignage/*`), `roid.formTextInput(titulo, valor, campo)` (teclado nativo de `common_s_keyboard/inc_login.php`, y la carpeta se llama *keyboard*), `roid.stopNFC()` (3 usos en `tagsignage`), `roid.finishActivity()` (protegido con `typeof`). **Esas funciones requieren una APK distinta que no está en el repo.** Laravel debe mantener el JS que las llama; hay que pedir esa APK al equipo.

### 2. Sesión de quiosco (cookie PHP clásica, sin token)

El WebView mantiene la cookie `PHPSESSID`. No hay token de dispositivo: **la identidad es `androidId` (+ `terminal_cd`, `shop_cd`) en la URL, y la persona es un miembro del personal que inicia sesión en la propia pantalla**.

| Sesión | Quién la crea | Claves (`$_SESSION`) |
|---|---|---|
| Terminal de puntos/sellos (`inc_search.php` → `session.php`) | login de personal (`inc_login.php`: `act=login`, campos `admin_id`, `admin_pass`; compara `tb_staff.login_pass` en claro) | `login_id`, `login_pass` (en claro), `rank`, `tantou`, `user_master_cd`, `shop_cd`, `master_cd`, `kaishamei`, `shop_name`, `shop_email`, `current_mode.process='point'`, `form_checked.*`, y de la URL: `key`, `get_str`, `terminal_cd` (entero), `orientation`, `androidId`. Cada petición vuelve a validar `login_id`/`login_pass` contra `tb_staff`; si falla, destruye la sesión y redirige a `login.php` + `get_str` |
| Señalización (`touchcation/signage*/index.php`, `tagsignage/inc/session.php`) | la URL de entrada | `clients.shop_cd`, `clients.terminal_cd`, `clients.androidId`, `clients.orientation`, `clients.flg_point` (de `tb_nextouch_navi` 'サイネージアプリスタンプ'), `clients.app_check`, `clients.latitude/longitude/gps_taiki_flg/gps_raiten_tb_customer_cd/gps_disp_flg/gps_hyouji_flg`, `clients.mycode_disp_flg`. Las páginas interiores responden `不正なページ移動です` si faltan `shop_cd`, `terminal_cd` o `androidId` |
| `tagsignage` sin parámetros | `inc/session.php` | Si no hay sesión, **crea una de desarrollo** (`shop_cd=1`, `terminal_cd=3`, un `androidId` fijo de prueba). Hay que eliminarlo en Laravel |
| Asistencia (`kintai_crm_*`) | `inc/session.php` | usa `$_SESSION['shop_cd']` |
| `signage/` (web) | `?s=<md5(email01 + '_' + pass)>` de la tabla `shop` de la BD `admin` | `signage_login=1`; nombre de sesión `/signage`; `view/` es **público** |
| `timecard` | login de `tb_staff` | `timecard.admin.cd`, `timecard.shop_cd` |

Cierre por inactividad: `admin_master.terminal_logout_time` (minutos; **30 por defecto**) lo aplica el JavaScript de la pantalla, no la sesión PHP.

### 3. Registro del terminal (misma lógica copiada en 12 archivos)

Todas las entradas (`launcher`, `inc_search.php`, `member_*`, `gps_s`, `id_raiten`, `signage*`...) hacen lo mismo:

1. Exigen `shop_cd`, `terminal_cd` y `androidId`; si falta alguno responden (200, `text/html; charset=utf-8`): `データエラーが発生しました。アプリケーションを再起動してください。`
2. `terminal_no = androidId` filtrado con `[^\w.:-]`; busca `tb_terminal` con `terminal_no` y `flg = '1'`.
3. **No existe → alta automática** en transacción: nº con `hatuban` (`koumoku='tb_terminal'`), `tb_shop_cd = shop_cd`, `flg='1'`, `rank = max+1`, `touroku_date` y `update_date` = `time()`. Responde `初回起動です。データベースに端末で使用するアプリケーションを登録してください。` y termina.
4. Existe y `application_flg` vacío → `端末で使用するアプリケーションが登録されていません。設定をご確認ください。`
5. Existe, pero `application_flg` (lista separada por comas) no incluye ni `-1`, ni `0`, ni el número de esa app → `この端末で、こちらのアプリケーションはご利用になれません。設定をご確認ください。`
6. Todo bien → actualiza `update_date` (es el **latido** del terminal que se ve en la gestión).

`application_flg`: `-1` depuración (todas + 99), `0` entregado (2,3,5), `1` サイネージ → `signage_s`, `2` ポイント → `point_s`, `3` スタンプ → `stamp_s`, `4` 総合機能 → `all_in_one_apk_s`, `5` 会員登録 → `member_biz`, `6` CRM管理画面 → `/system/login.php`, `7` GPS来店 → `gps_s`, `8` 勤怠管理 → `kintai_crm_sp`, `9` ID来店 → `id_raiten`, `99` desarrollo (`/@android/test.php`, no existe).

El launcher, con **una sola** app, hace `302` a `http://<SERVER_NAME>/touchcation/<app>/?key=&shop_cd=&terminal_cd=&orientation=&androidId=` (esquema `http` fijo); con varias muestra un menú que se desliza (frases en japonés del menú: nombre y descripción de cada app).

### 4. Respuestas: formatos que no se pueden cambiar

- **JSON armado a mano con comillas** (todos los valores salen como cadenas): p. ej. `{ "pValue" : "3", "status" : "max" }`. En Laravel hay que castear a `string` y devolver con `response()->json()` solo si el cliente no depende del orden de claves (el JS usa nombres, no orden).
- **Texto plano**: `Success`, `Error`, `NO_DATA`, `Error1`, `Error2`, `Error0`, el ID del cliente.
- **HTML parcial** (fragmentos que el JS inserta con `.html()`): `start_customer`, `start_point`, `start_stamp`, `start_karte`, etc. (los genera `fnc_createHtml.php`, 5.390 líneas).
- **JSONP**: `callback(<json>)` o `loginSession(<json>)` con `Content-Type: text/javascript` (point_app); con `target=android` devuelve JSON puro.
- **Binario ESC/POS** para impresora de tickets (ver `tagsignage` y `numbered_ticket`), a veces en Shift_JIS.
- **Eventos DOM** hacia POSViewer (Windows), sin red (ver `signage_w`).

---

## Grupos de duplicados (qué se repite y en qué difiere)

| Grupo | Copias | En qué difieren | Qué hacer en Laravel |
|---|---|---|---|
| `check.php` (prueba de conexión) | **15 idénticas** (14 apps + `launcher/checkMenber.php`) | En nada | **1 ruta** `GET check.php` por carpeta de app |
| Registro del terminal (bloque de arriba) | 12 copias pegadas | Solo el número de app permitido (1, 2, 3, 4, 5...) | 1 servicio `KioskTerminalService::register()` + 1 middleware `EnsureAppAllowed:<n>` |
| `point_s`, `stamp_s`, `all_in_one_apk_s` | 3 carpetas × 5 archivos | `login.php`, `index.php`, `check.php` idénticos. `search.php` y `application.php` difieren solo en `$application_number` (2, 3, 4) y `$temp_member_flg` (0, 0, 1: solo la "todo en uno" permite registrar un socio provisional) | **1 controlador** con parámetro `app` (point, stamp, all) |
| `common_s_keyboard` frente a `common_s` | 15 archivos cada una | 67 líneas distintas en `ajax_control`, 377 en `fnc_createHtml`; en `keyboard` el login usa `roid.formTextInput` y corrige el evento `touchend` | **`common_s_keyboard` es la única viva** (la usan las 3 apps `*_s`). `common_s` no la llama nadie: **huérfana** |
| `common_n` frente a `common_s` | 15 archivos cada una; 8 idénticos (`js_*`, `session`, `ajax_couponControl/List`...) | `ajax_control` 1.318 líneas distintas, `fnc_createHtml` 2.926 (diseño y textos viejos); `common_s` añade `submitStop` (evita el cierre forzado de Java al pulsar Enter). Hay una diferencia de **comportamiento**: en `common_n` una actualización usa `WHERE tb_customer_cd = ...` y en `common_s` `WHERE cd = ...` (una de las dos es un bug) | Solo la usan `point/`, `stamp/` y `all_in_one_apk/` (`$application_number` 2, 3, 4). **No migrar** salvo que haya APK antiguas apuntándole |
| `point/`, `stamp/`, `all_in_one_apk/` (serie N) | 3 carpetas, 37 PHP | Wrappers iguales a los `*_s`; además cada una trae su `control.php` (950, 951 y 1.434 líneas; difieren en 521 líneas entre `point` y `stamp`), `ajaxCoupon*` (`point` = `stamp` idénticos) y `point/point_appli.php` (530 líneas, pantalla antigua que hace POST a `control.php`) | Dejar fuera; si hay que mantenerlas, redirigir a la serie S |
| `kintai_crm_n` frente a `kintai_crm_sp` | 14 y 13 PHP | **4 idénticos** (`ajax_kintai`, `ajax_settei`, `settei`, `inc/session`); 9 difieren solo en maquetación/CSS y el `index`. `_n` añade `check.php` y llama `roid.getAndroidId` en cada página | 1 módulo; las vistas Blade comparten lógica, solo cambia la plantilla |
| `member_s` frente a `member_biz` | 5 y 8 PHP | `callback.php` idéntico; `checkPtoP.php` en `biz` añade el caso `type=biz`; `member_biz` tiene además `home`, `attaching` y `ajax` (registro con sellos) | 1 controlador `PtoP` con `type=slim|biz` |
| `signage/` (Android) × orientación | 12 pares `*_x.php` / `*_y.php` (horizontal/vertical) + 8 `x_*`/`y_*` | `_x` = `PORTRAIT`, `_y` = resto (el comentario del código dice lo contrario). Las pares difieren en dimensiones y CSS (`signage_x` frente a `signage_y`: 30 líneas). Varios son idénticos: `thanks`, `stamppoint`, `raiten_id`, `id_search`, `contents`, `attaching`, `complete_attaching` son **iguales entre `_x`, `_y`** y `signage_w` `_y` | **1 vista por pantalla** con variable `layout` x/y. `comlete_attaching_*` (sin `p`) no la referencia nadie: archivo muerto |
| `signage_w` frente a `signage` | 42 y 39 PHP | 8 páginas `_x` difieren (sin `androidId`, sin alta de terminal, sin validación de sesión de terminal; `signage_w` añade `control.php` y `print/`); el resto **idéntico** (`x_*`, `y_*`, `*_y`, `ajax_signage`) | `signage_w` = misma app con flag `windows=true` + `print/coupon.php` |
| `signage_s` frente a `signageApp` | 3 y 5 PHP | `signageApp` exige UA `MTBROWSER`, usa `tid` (AppKey) y añade `ajax_signage`, `ajax_time` y `js/` | 1 reproductor con dos modos de identificación |
| `tagsignage/signage/*` | 4 archivos | Copia de `signage_s`/`signageApp` con menú propio | Unificar con el reproductor |
| `digitalsignage/ajax_*print_coupon_crawl*` | 6 familias × 2 copias (`_2`) | Cambian solo el `coupon_kubun` (1, 7, 8) y detalles de 4 a 15 líneas (`ajax_print_coupon_crawl` = `ajax_print_coupon_craw_2l`, salvo 4 líneas) | 1 endpoint `type=` |
| `digitalsignage/index.php` | 1 envoltorio | `index.php` hace `require index2.php` y `exit` (el resto es código muerto) | Solo migrar `index2` |
| `signage/list/inc`, `inc_flame_cd`, `inc_js` y `signage/view` | 17 + 16 + 17 + 13 = **63 archivos** para **17 diseños** (`h_1_1` ... `v_2_2`, `h_4_2_image`...) | Cada diseño = tamaño de pantalla (horizontal/vertical), nº de marcos (1, 2, 3, 4, 6, 8). Hay archivos idénticos entre diseños: en `inc` 4 pares `h_`/`v_` (`1_1`, `1_2`, `2_1`, `2_2`), en `inc_flame_cd` 3 grupos (`h_1_2`=`h_2_1`=`v_1_2`=`v_2_1`; `h_1_1`=`v_1_1`; `h_2_2`=`v_2_2`; más `h_1_5`=`h_5_1`), en `view` 1 par (`h_2_1`=`v_2_1`), en `inc_js` 1 par | **Un componente Blade parametrizado** por `display_type` (`h_2_2` → filas, columnas) en vez de 63 archivos |

---

## Lanzador (`touchcation/launcher/`)

- La APK abre esta pantalla. Si la tablet solo tiene una app asignada, salta directo a ella; si tiene varias, muestra un carrusel (gestos de deslizar, con `gesturestart`) y cada icono envía a su carpeta.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `index.php` | SHARP・Panasonic端末用ランチャー | lista (menú) / registro | Valida parámetros, registra el terminal (bloque 3 arriba), arma el menú con `application_flg` | L/E: `tb_terminal`, `hatuban` | `$_SERVER['SERVER_NAME']`, esquema `http` fijo; jQuery 1.4.4 y `jquery.alerts` |
| `check.php`, `checkMenber.php` | (conexión) | ajax | `print $_REQUEST['check']` | — | Idénticas a 13 copias más |
| `checkPtoP.php` | PtoP | ajax | `type=slim` → `1` | — | Ver `member_s` |
| `inc/head.php` | | fragmento | meta y CSS | — | |

Reglas: `-1` y `0` se expanden (`-1` = todas las apps 1-9 y 99; `0` = 2, 3, 5).
Reemplazo: ruta `touchcation/launcher/index.php` (misma URL) → `LauncherController` + vista Blade; `redirect()` **relativo** en lugar de `http://SERVER_NAME`.

## Terminal de puntos y sellos, serie S (`touchcation/common_s_keyboard/` + `point_s`, `stamp_s`, `all_in_one_apk_s`)

- Es la pantalla de caja táctil: el personal inicia sesión, pasa la tarjeta FeliCa (o escribe ID o kana) del socio y puede ver su ficha, sumar o restar puntos según la cuenta, sumar sellos, ver y emitir cupones, ver visitas, la ficha clínica/ficha de atención (カルテ) y el álbum de fotos. Pueden enviar un correo con el detalle al socio.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `inc_login.php` (vía `login.php`) | ログイン | alta (login) | `act=login`, `admin_id` (lista de `tb_staff` de la tienda), `admin_pass` en teclado numérico propio | L: `tb_staff`, `tb_office`, `tb_shop` | Compara contraseña **en claro**; exige `tb_office.kaishamei` (si no, "初期設定がされていないためログインできません"); abre sesión y redirige a `search.php` + `get_str` |
| `inc_search.php` (vía `index.php`, `search.php`) | 会員検索 | lista | Pantalla de inicio: espera NFC (`startPolling`), búsqueda por ID o kana | L: `admin_master` (`terminal_logout_time`), `tb_terminal`; E: `tb_terminal` (registro), `hatuban` | Contiene el registro del terminal y `pollingCallBack` (**Mifare con 12 ceros se recorta a 8 caracteres**, el `idm` se pasa a mayúsculas) |
| `inc_application.php` (vía `application.php`) | 会員登録 / 仮登録 | alta | Registro de socio desde el quiosco (solo `all_in_one_apk_s`: `temp_member_flg = 1`) | L: `tb_customer`, `tb_customer_point`, `tb_shop`, `tb_base_system_child`, `tb_base_system_grandchild` | 1.320 líneas |
| `ajax_control.php` | (Ajax) | ajax | **38 operaciones** por `type` (tabla abajo) | `tb_customer`, `tb_customer_point`, `tb_customer_card`, `tb_customer_sub`, `tb_point_log`, `tb_stamp_log`, `tb_raiten_rireki`, `tb_felica_log`, `tb_mycoupon*`, `tb_photo_album`, `tb_stamp_*`, `tb_search_*`, `hatuban`, `admin_master` | 3.194 líneas; envía correo (`crm_send_mail`); sube fotos (ver Reglas) |
| `ajax_couponControl.php`, `ajax_couponList.php` | クーポン | ajax | `myCouponList`, `exchangeList`, `hakkouCouponList`; emitir, canjear y usar cupón (`1`–`5`) | L/E: `tb_mycoupon`, `tb_mycoupon_master`, `tb_mycoupon_used`, `tb_customer`, `hatuban` | Idénticas en las 3 series |
| `fnc_createHtml.php`, `fnc_getStampCount_custom.php`, `session.php`, `head.php`, `js_*.php` (6), `css/`, `img/` | | fragmento(include) | Genera los HTML de ficha, puntos, sellos, cupones, karte, álbum; calcula el multiplicador de sellos según la regla vigente | L: ver `ajax_control` + `tb_stamp_rules` | 5.390 líneas de HTML en PHP |
| `check.php` | | ajax | prueba de conexión | — | |

**Operaciones de `ajax_control.php`** (`type=`; todos aceptan GET o POST; respuesta H = HTML parcial, J = JSON a mano, T = texto):

| `type` | Parámetros | Resp. | Qué hace |
|---|---|---|---|
| `start_customer` | `cid` | H | Ficha del socio por ID (activo o con `disp_flg` vacío) |
| `add_check` / `add_terminal` / `release_terminal` | `felica` / `felica`, `cid` / `cd` | T | Comprueba si la tarjeta ya está en uso, asigna la tarjeta al socio **y** registra la visita (`cUtility->raitenFunc`) + `tb_felica_log` (`action_flg=5`), y libera |
| `setRaitenSelect` / `raitenUpdate` | `scd`, `cid`, `log_type` / `raiten_cd`, `log_cd`, `log_type` | H / T | Elegir y reasignar visita para el punto o sello |
| `start_point`, `set_plus_value`, `enter_point` | `cid`; `scd`, `cid`, `payment`, `acVal`, `usedVal`; `scd`, `cid`, `payment`, `currentNum`, `acNum`, `usedNum`, `plusNum`, `inputNum`, `calculation`, `staff`, `tcd`, `sendFlg`, `raitenFlg` | H, J, J | Cálculo y registro de puntos (ver Reglas) |
| `start_stamp`, `set_plus_stamp_value`, `enter_stamp` | `cid`; `scd`, `cid`, `payment`, `acVal`; `... stampMax`, `oshiraseFlg` | H, J, J | Idem para sellos (regla de multiplicador, máximo, cupón de aviso `tb_stamp_oshirase_coupon`) |
| `start_karte`, `karte_detail`, `karte_form`, `add_karte`, `karte_register`, `karte_delete` | `ccd`, `arr`, `relcd`, `kaiin_cd`, `raiten_y/m/d/h/i`, `shiharai_houhou`, `kaikei`, `shouhi_point`, `sounyu_point`... | H / J | Ficha de atención y alta manual de una compra pasada con puntos |
| `start_coupon`, `select_coupon_reload` | `cid`, `scd`; `cd`, `customer_cd`, `list_type` | H | Lista de cupones (`myCouponList`, `exchangeList`, `hakkouCouponList`) |
| `start_visit` | `cid`, `scd` | H | Historial de visitas |
| `start_myalbum`, `album_form`, `album_delete`, `pic_preview`, `album_upload` | `ccd`, `scd`; `album_cd`; `cd`; `tempfilename`, `ext`, `file`; `pic_flg`, `pic_name`, `hiduke_*`, `register_type`, `tb_customer_cd`, `comment` | H / T | Álbum de fotos del socio |
| `felica_search`, `id_search`, `kana_search` | `felica`; `cid`; `kana` | J (`{ "cd", "kubun" }` o `{ "status", "data" }`) | Búsquedas |
| `form_checked` | `input_name`, `input_type`, `input_checked`, `input_value` | T | Guarda en sesión el estado de casillas (`form_checked`) |

**Flujo principal:** `login` → `search` (espera tarjeta) → `start_customer` → pestañas punto / sello / cupón / visita / karte / álbum → `enter_point` o `enter_stamp` → vuelve a la búsqueda.

**Reglas de negocio no obvias**
- **Puntos** (`enter_point`): el servidor **recalcula** y compara con lo que mostró la pantalla; si el saldo cambió desde el subtotal devuelve `err:"Error1"` (alguien tocó puntos en la gestión), si cambió la regla devuelve `Error2`, y `Error0` es fallo de escritura. Tasa: `tb_shop.point_percentage_genkin` (efectivo, `payment="現金"` → 1) o `point_percentage_card` (por defecto 1 %). `keisan_flg`: `1` calcula sobre (cuenta − puntos usados), `2` sobre la cuenta. Tope de uso `shiyou_point_jyougen` (por defecto 1000). `puntos = (int)(base × tasa / 100)`. `calculation=add|sub` suma o resta un ajuste manual (`inputNum`). Saldo = `tb_customer_point.service_point` (con `point_yukoukigen`); antes del 2012-11-09 estaba en `tb_customer`.
- **Sellos** (`enter_stamp`): multiplicador de `tb_stamp_rules` según el momento (`type_flg`, `start_time`, `end_time`, día de la semana); máximo `tb_stamp_config.stamp_max`; intervalo mínimo `limit_flg` (segundos) entre dos sellos (`status` = `max` / `limit` / `invalid` si la tienda tiene la regla en 0); `tb_customer.last_stamp_time`.
- Socio válido: `tb_customer.touroku_kubun = '2'` y `disp_flg = '1'`.
- **Visita**: `cUtility::raitenFunc` (clase compartida con otros grupos) registra en `tb_raiten_rireki`.
- **Álbum**: sube a `system/category1/customer/uploads/` con nombre seguro y extensión en lista blanca (arreglado en el paso 25 de la migración).
- `tb_felica_log.action_flg` identifica el origen (4 = señalización, 5 = quiosco de puntos).

**Servicios viejos y reemplazo**
- Correo: `crm_send_mail` (ya sobre Symfony Mailer) → `Mailable` + cola (`ShouldQueue`).
- Contraseña de personal en claro y guardada en sesión → `Hash` + Guard `kiosk` (ver Propuesta); **mientras tanto la pantalla de login debe seguir recibiendo `admin_id` y `admin_pass`** y devolver 302 a `search.php`.
- Teclado numérico propio, jQuery 1.4, `alerts` → conservar (los dispositivos son Android 2.3.4 en WebView antiguo): **no usar frameworks JS modernos ni ES6 en estas vistas**; copiar CSS/JS como estáticos.
- HTML generado en PHP → vistas Blade parciales con el mismo marcado.
- `sleep`/long-poll: no aquí (ver GPS).

## Serie N y copias heredadas (`touchcation/common_n/`, `common_s/`, `point/`, `stamp/`, `all_in_one_apk/`)

- Misma app en su versión anterior; `common_s` no la llama nadie. **Ningún launcher actual apunta a ellas** (las rutas de producción del launcher son las `*_s`; las de `/@test/...` están comentadas). No se listan vistas: ver "Grupos de duplicados".
- Propias de esta serie: `point/control.php`, `stamp/control.php`, `all_in_one_apk/control.php` (misma lista de operaciones que `ajax_control`, menos karte/álbum/kana) y `point/point_appli.php` (pantalla independiente de puntos para Sharp con `$.post("control.php")`).
- Pregunta pendiente: ¿hay APK o URLs guardadas apuntando a `/touchcation/point/`, `/stamp/`, `/all_in_one_apk/`? Si no, no se migra (0 puntos). Se estiman 2 puntos si se decide mantener redirecciones a la serie S.

## Visita por GPS e ID (`touchcation/gps_s/`, `touchcation/id_raiten/`)

- `id_raiten`: el socio escribe su ID (o `kanri_id` según `admin_master.id_raiten_type`) y se registra la visita y el sello. `gps_s`: el terminal queda esperando a que el teléfono del socio, con GPS, marque la visita.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `id_raiten/index.php` | ID入力で来店処理アプリ | registro | Registro de terminal y redirección a `home.php` | L/E: `tb_terminal`, `hatuban` | |
| `id_raiten/home.php` | CRM ご来店アプリケーション | alta | Teclado para el ID | L: `tb_nextouch_navi` | |
| `id_raiten/get_stamp.php` | スタンプポイント | detalle | Muestra el sello recibido | L: `tb_customer`, `tb_nextouch_display` | |
| `id_raiten/ajax.php` | | ajax | `raiten_id` y similares (ver `signage/ajax.php`) | L/E: `tb_customer`, `tb_felica_log`, `tb_terminal`, `tb_stamp_config`, `admin_master` | |
| `gps_s/index.php` | | registro | Registro y acceso | L/E: `tb_terminal`, `hatuban` | |
| `gps_s/raiten_gps_x.php`, `stamppoint_x.php` | GPS来店処理, スタンプポイント | detalle | Espera la visita por GPS y muestra el sello | L: `tb_terminal`, `tb_shop`, `tb_nextouch_display`, `tb_customer` | Copia de `signage/raiten_gps_x` |
| `gps_s/ajax.php` | | ajax | `gpsStandBy` etc. | L/E: `tb_terminal`, `tb_customer`, `tb_felica_log`, `tb_stamp_config` | |
| `check.php`, `fnc_getStampCount.php`, `inc/head.php` | | fragmento(include) | | L: `tb_stamp_rules` | `fnc_getStampCount` idéntica en 5 carpetas |

**Contrato** (compartido con `signage/ajax.php`; respuestas idénticas): `type=gpsStandBy` (`shopCD`, `terminalCD`) pone `tb_terminal.gps_taiki_flg='1'` y `gps_raiten_tb_customer_cd=null`, **duerme 8 segundos** (`sleep(8)`) y devuelve el cliente que haya marcado visita en ese tiempo; `type=raiten_id` (`shopCD`, `terminalID`, `cid`) con `admin_master.id_raiten_type` (`1` = busca por `kanri_id`, otro = `id`); `type=raiten_qr` (`id`, `terminalCD`): `{ "cd", "scd", "pValue", "status" }`.
Quien marca la visita por GPS escribe `gps_raiten_tb_customer_cd` desde el lado del socio (`hp/`, otro grupo). **Laravel debe mantener este contrato bidireccional.**

## Registro de socios por NFC / PtoP (`touchcation/member_s/`, `touchcation/member_biz/`)

- Sustituyen al lector "ピットタッチ": la tablet muestra un QR y puede "empujar" (PtoP/NFC) una URL al teléfono del socio, que entra a la ficha de registro.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `member_s/index.php` | SHARP端末用 ピットタッチ・スリム代替アプリ | detalle | Registro de terminal; `setPtoP(sec, url, 'checkPtoP.php?type=slim')`; QR con `qr_img.php?d=<hp>/mailsyori/base.php?subject=<shop_cd>` | L/E: `tb_terminal`, `hatuban` | Requiere `application_flg` 5 |
| `member_biz/index.php`, `home.php`, `attaching.php` | CRM 会員用アプリケーション | alta / detalle | Home y pantalla de registro de terminal con QR | L: `tb_terminal`, `tb_nextouch_navi`, `tb_nextouch_display` | |
| `callback.php` | | ajax | Recibe `shop_cd`, `tcd`, `idm` y **redirige** a `/hp/mailsyori/felica_detail.php?shop_cd=&tcd=&b=<idm>` | — | Depende de `hp/` (obsoleta): hay que decidir si el destino se reimplementa |
| `checkPtoP.php` | | ajax | `type=slim` → `1`; `type=biz` con `idm` → `""` si ya es socio (`touroku_kubun='2'`), `1` si no | L: `tb_customer` | Contrato con el teléfono |
| `member_biz/ajax.php` | | ajax | `basePointValue` (`scd`, `cid`, `felica`: `{ "pValue", "status": "invalid|max|limit|err_data|err_request" }`), `stampResult` (`{ "result", "id", "name" }`), `idSearch` | L: `tb_customer`, `tb_stamp_config`, `tb_felica_log` | `fnc_getStampCount` desde `hp/mailsyori` |
| `check.php`, `inc/head.php` | | fragmento(include) | | | |

URL de `callback.php` que la APK da al teléfono: `http://<host>/touchcation/member_s/callback.php?shop_cd=<n>&tcd=<tb_terminal.cd>` (el teléfono añade `idm`).

## Asistencia en quiosco (`touchcation/kintai_crm_n/`, `kintai_crm_sp/`)

- El personal ficha entrada, salida, salida breve y regreso con su tarjeta FeliCa; el responsable gestiona el personal y la clave desde el propio quiosco. La gestión y los informes están en `category5/kintai` (doc 05).

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `index.php` | 勤怠管理 (HOME画面) | lista | Registro de terminal, reloj, espera tarjeta | L/E: `tb_terminal`, `hatuban` | |
| `kintai.php` | 勤怠画面 | detalle | Botones 出勤, 退勤, 外出, 戻り | — | |
| `kintai_list.php` | 設定（勤怠情報） | lista | Marcas del día / del mes | L: `tb_staff`, `kintai_tb_*` | |
| `settei.php`, `settei_menu.php` | 設定（パスワード入力 / メニュー） | config | Acceso al menú con la clave de la tienda | L: `kintai_tb_config` | |
| `password_update.php`, `password_update_complete.php` | 設定（パスワード変更 / 更新完了） | edición / confirmación | Cambia la clave de ajustes | E: `kintai_tb_config` | |
| `staff_list.php`, `staff_new.php` | 登録スタッフ一覧 / 新規スタッフ登録 | lista, alta | Asigna tarjeta, cargo y régimen de empleo | L/E: `tb_staff`, `tb_belongpost`, `tb_employmentsystem` | |
| `ajax_kintai.php` | | ajax | `idm`, `type` (`shukkin`, `taikin`, `gaishutsu`, `modori`), `yy`, `mm`, `dd`, `hh`, `nn`, `ss` | L/E: `tb_staff`, `kintai_tb_hiduke`, `kintai_tb_kintai`, `hatuban` | Ver Reglas |
| `ajax_settei.php` | | ajax | `add_check`, `add_terminal`, `remove_terminal`, `staff_entry`, `server_time` | L/E: `tb_staff` | |
| `check.php`, `inc/session.php`, `inc/head.php` | | fragmento(include) | | | |

**Reglas:** una marca solo es válida si respeta la secuencia (退勤 o 外出 requiere estado previo ≠ 退勤/外出; 戻り solo tras 外出; el estado inicial solo admite 出勤); 出勤 crea un registro en `kintai_tb_hiduke` y las demás cuelgan de él; la tarjeta debe pertenecer a `tb_staff` de **esa** tienda (`shop_cd` de sesión). La hora la manda el dispositivo (`yy`…`ss`), no el servidor (a decidir si se mantiene).
**Reemplazo:** modelo `AttendanceRecord` + endpoint con validación de transición; hora del servidor (`now()`) con el valor del cliente solo como respaldo.

## Tarjeta de tiempo (`touchcation/timecard/`)

- Pantalla de administración para vincular o desvincular la tarjeta FeliCa a un miembro del personal.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `index.php`, `login.php` | タイムカード, ログインしてください | alta (login) | Formulario de acceso | — | |
| `home.php` | スタッフリスト | lista | Personal y tarjeta asignada | L: `tb_staff` | |
| `control.php` | | ajax | `mode=loginControl`, `cardTying` (`uid`, `cd`), `cardDelete` (`cd`): `{"status":"ok"}` o `{"status":"error","errorMsg":"..."}` | L/E: `tb_staff`, `tb_felica_mode`, `tb_work_time`, `tb_shop`, `hatuban` | Impide duplicar `felica_no` |
| `class/Login.php`, `class/HtmlStructure.php`, `inc/base.php`, `inc/config.php` | | fragmento(include) | Login con clave en claro; Bootstrap | L: `tb_staff` | **Se conecta a otra base (`crm2019`)**: ya no responde en el entorno de desarrollo (MISSING-TABLES) |

## Ticket numerado (`touchcation/numbered_ticket/`)

- Quiosco de turnos de restaurante o salón: el cliente toca "受付ボタン" o pasa su QR de socio, indica adultos, niños, fumador y preferencia de personal, y recibe un número impreso. Una pantalla de llamadas (`called_display`) y la lista de recepción lo muestran.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `index.php` | (受付ボタン) | alta | Botón de recepción, QR de socio, "アプリインストールがまだの方", espera estimada | — | `shop_cd` por defecto 1 |
| `form.php` | | alta | Formulario de personas, humo, personal; con `code` cifrado identifica al socio | L: `tb_customer`, `tb_yoyaku` | Usa `appview/class/Password`, `r/class/Reserve` |
| `ticket_list.php` | 受付一覧 | lista | Cola y llamar / terminar | L/E: `numbered_ticket`, `tb_yoyaku`, `tb_yoyaku_uketsuke_day`, `tb_staff`, `tb_customer` | |
| `called_display.php` | 呼び出し板 | detalle | Pantalla de llamadas | — | |
| `ajax_getWaitCount.php`, `ajax_getWaitBoadData.php` | | ajax | `{status:"ok", ...}` con número en espera, tiempo (mínimo 10 min) y números llamados | L: `numbered_ticket`, `tb_yoyaku`, `tb_yoyaku_uketsuke_day`, `tb_staff` | La "fecha" de reserva retrocede 5 horas |
| `print/print.php`, `print/printCommon.php` | | ajax (binario) | Crea la reserva rápida (`QuickReservation::addReservation`) y devuelve comandos de impresora; en `print.php` el archivo **está guardado en Shift_JIS** | L: `tb_staff` | No convertir a ciegas (BROKEN/PENDING); incluye QR a la app |
| `inc/head.php`, `js/appCom.js`, `form.js`, `app.js` | | fragmento(include) | `appCom.js` = puente con POSViewer (Windows) | | |

**Contrato con POSViewer** (`js/appCom.js`, también en `signage_w`): `document.dispatchEvent(MessageEvent)` con los nombres `csPrint`, `csGetNFCData`, `csBeep`, `csSendVFD`, `csSetDefaultPrinter`, `csSetPrinterMargins`, `csSetPrinterOrientation`, `csSetPrinterHeaderStr`, `csSetPrinterFooterStr`, `csSetPrinterPaperType`, `csGetPrinterList`, `csReboot`, `csPowerOff`, `csBackupDb`, `csRestoreDb`, `csGetFileList` (y `csUpdateLauncher` solo en `numbered_ticket`). Impresora de `signage_w`: `usbprn:SII RP-D10`; el ejecutable de impresión es `C:\POSSystem\bin\printData.exe`. Hay que **copiar `appCom.js` sin cambios**.
Nota: el formulario envía a `/print/print.php` (ruta de la raíz, no `/touchcation/numbered_ticket/print/`): lo resuelve la reescritura del servidor o JS; hay que confirmarlo.
**Reemplazo:** tabla `numbered_ticket` con modelo; `EscPosBuilder` (clase que genera los bytes, con juegos Star/Primex/Epson y conversión a SJIS) devuelto como `Response` binaria.

## App de puntos por beacon y API (`touchcation/point_app/`, `touchcation/api/`)

- App React (solo existe `bundle.js` compilado, sin fuentes) para que el personal vea qué socios llegaron por beacon en el último minuto, o buscar socio, sumar puntos y cobrar.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `point_app/index.php` | ポイントアプリ | detalle | Exige `beacon_cd`, `uuid`, `major`, `minor`, `tb_shop_cd` (si no: `パラメータが不正です。`); carga `bundle.js?<time>` | — | Contiene un dominio de desarrollo fijo en JS |
| `point_app/api/index.php` | (enrutador) | ajax | `?AuthLogin`, `?getUserList`... carga `<nombre>.php`; si no existe `404.php` (que no existe) → fatal | — | Sin parámetro, 500 (BROKEN-FILES) |
| `api/AuthLogin.php` | | ajax | `id`, `password` (tb_staff, clave en claro) → `{status, data:[{cd,id,name,logintoken}], coming_use_beacon}` | L: `tb_staff`, `admin_master` | `logintoken = md5(id || '-salt-' || login_pass)` (sal fija en el SQL) |
| `api/getLoginSession.php` | | ajax | `logintoken` → `loginSession({status, data:[{count}]})` (JSONP) | L: `tb_staff` | |
| `api/getStaffAccountList.php` | | ajax | `shop_cd` → personal activo (el `id = '000001'` se devuelve como `master`) | L: `tb_staff` | |
| `api/getUserList.php` | | ajax | Socios del día o `searchwords` (nombre, teléfono, ID, correo, nacimiento) o por beacon (`type=customerIdShow`, último minuto) | L: `tb_customer`, `tb_shop`, `tb_raiten_rireki`, `tb_beacon_history`, `admin_master` | Respeta `shop_category_flg` |
| `api/getPointUsedList.php` | | ajax | `tb_customer_cd`, `shop_cd` → últimos 100 movimientos, saldo y config de tienda | L: `tb_point_log`, `tb_customer`, `tb_customer_point`, `tb_shop` | |
| `api/setAccountingPoint.php`, `setUserPoint.php` | | ajax | Cobro con puntos (`account_amount`, `points_used`, `manual_points`, `payment`, `method`) y ajuste manual (`point`, `point_action_type`) | E: vía clase `Point` (`tb_point_log`, `tb_customer_point`) | Clase `class/Point.php` compartida |
| `touchcation/api/getNextouchBasicInfo.php` | | ajax | **Sin autenticación**: devuelve `{status:"ok", data:{shop:[{cd,name}], program:[{cd,name}]}}` (tiendas activas y programas de quiosco) | L: `tb_shop`, `tb_nextouch_movie_program` | Lo usa la gestión o un dispositivo (no localizado) |

Formato de salida: si `target=android` → JSON puro; si no → `callback(<json>)` con `Content-Type: text/javascript; charset=utf-8`.
**Reemplazo:** `routes/dispositivos.php` → `Route::get('touchcation/point_app/api/index.php')` que despacha por la clave de la query (`?AuthLogin`); token con `Sanctum` o `hash_hmac` sin romper `logintoken` mientras haya clientes viejos; servir `bundle.js` desde `public/`.

## Señalización Android (`touchcation/signage/`, `signageApp/`, `signage_s/`)

- Las tablets de tienda muestran, a pantalla completa, el pase de diapositivas del programa asignado a su terminal, y al tocar el cliente puede registrar visita (por QR, ID o GPS), ver su sello, ver contenido, recibir el agradecimiento, o cumplimentar la inscripción como socio.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `signage/index.php` | (entrada) | registro / detalle | Registro de terminal, carga `tb_nextouch_navi` ('サイネージアプリスタンプ'), crea `clients.*`, redirige a `home_x.php` (PORTRAIT) o `home_y.php` | L/E: `tb_terminal`, `hatuban`; L: `tb_nextouch_navi` | `longitude ` y `gps_taiki_flg ` se leen con **espacio al final** en la clave → siempre `null` (bug) |
| `signage_x/y.php`, `x_signage/y_signage.php` | CRM Signage | detalle | Pase de diapositivas con transición; recarga si cambia `update_date`; toca para ir a `contents` | L: `tb_nextouch_display`, `_movie_program`, `_screensaver`, `tb_terminal` | 871 líneas. Archivos con `x_` / `y_` son partes (include) |
| `home_x/y.php` | (inicio) | detalle | Menú: contenidos, sello, visita | — | |
| `contents_x/y.php` | | detalle | Contenidos y cupones | L: `tb_nextouch_display` | |
| `raiten_qr_x/y.php`, `raiten_id_x/y.php`, `raiten_gps_x/y.php` | (visita por QR / ID / GPS) | alta | Tres formas de marcar visita | L/E: vía `ajax.php` | `raiten_gps` idénticos entre carpetas |
| `id_search_x/y.php` | | detalle | Busca ID de socio | L: `tb_customer` | |
| `attaching_x/y.php`, `complete_attaching_x/y.php` | (asociar tarjeta) | alta / confirmación | Vincula tarjeta FeliCa (`addTerminal`) al ID del socio | E: `tb_customer.felica_no` | `comlete_*` sin referencias |
| `stamppoint_x/y.php`, `thanks_x/y.php` | スタンプポイント, (gracias) | detalle | Resultado del sello / gracias | L: `tb_customer`, `tb_nextouch_display` | |
| `ajax.php` | | ajax | `basePointValue`, `raiten_qr`, `raiten_id`, `gpsStandBy`, `idSearch`, `addCheck`, `addTerminal`, `sessionReload` | L/E: `tb_customer`, `tb_felica_log`, `tb_terminal`, `tb_stamp_config`, `tb_stamp_rules`, `hatuban`, `admin_master` | Ver contrato abajo |
| `ajax_signage.php` | | ajax | Devuelve un **número**: `max(update_date)` del programa y sus materiales; la página lo compara cada 60 s y recarga si es mayor; si falta algo, devuelve texto de error ("放映プログラム/ファイルが未設定です error1..4", "画像が未登録です error4") | L: `tb_nextouch_*` | |
| `ajax_time.php` | | ajax | `date("Y/m/d H:i:s")` | — | Reloj para las franjas horarias |
| `check.php`, `fnc_getStampCount.php`, `inc/head.php` | | fragmento(include) | | L: `tb_stamp_rules` | |
| `signage_s/index.php`, `signage.php` | (サイネージ) | detalle | Reproductor simple: sesión, registro y pase | L/E: `tb_terminal`; L: `tb_nextouch_*` | `signage.php` directo da 500 (sin `db.php`) pero **funciona vía `index.php`** (lo carga) |
| `signageApp/index.php`, `signage.php`, `ajax_signage.php`, `ajax_time.php` | | detalle | Igual que `signage_s`, pero identifica con `tid` y exige UA `MTBROWSER` (`Aplication Error : 001` si no) | L/E: `tb_terminal`; L: `tb_nextouch_*` | `tid` = md5(`HTTP_HOST` + `<>` + `tb_nextouch_display.rank`) (mismo valor que **AppKey** que ve la gestión en `kiosk/display_detail`) |

**Contrato de `signage/ajax.php`** (POST salvo indicación; `type` en la query):
- `basePointValue` → `{ "pValue", "status": "" | "max" | "limit" | "invalid" | "err_data" }` (sello actual y estado).
- `raiten_qr` (`id`, `terminalCD`), `raiten_id` (`shopCD`, `terminalID`, `cid`), `gpsStandBy` (`shopCD`, `terminalCD`) → `{ "cd", "scd", "pValue", "status" }`; registran visita y sello.
- `idSearch` (`cid`) → el ID o `NO_DATA`.
- `addCheck` (`felica`) → `Error` si la tarjeta ya está registrada (vacío si no); `addTerminal` (`felica`, `cid`) → `Success` / `Error` (transacción: actualiza `tb_customer.felica_no`, registra visita y `tb_felica_log` con `action_flg=4`).
- `sessionReload` (`shopCd`, `terminalCd`, `androidId`, `orientation`, `flgPoint`) → `Success` / `Error`; el JS lo llama **cada 5 minutos** para que la sesión PHP no expire.
- Las pantallas leen por `rank = terminal_cd` en `tb_nextouch_display`, es decir, **`terminal_cd` es el orden (`rank`) del display, no el `cd` de `tb_terminal`** en la señalización (en las apps de puntos `terminal_cd` se guarda tal cual en sesión y `tcd` se usa para el log).

**Programa y materiales:** `tb_nextouch_display.tb_c7_movie_program_cds` → `tb_nextouch_movie_program` (`tb_screensaver_cds` con `<>` como separador, `kirikae_time` en segundos, `pic_flg`/`pic_extension`/`obi_flg` para la banda) → `tb_nextouch_screensaver` (`pic_flg` y `pic_extension` con `<>`); imágenes en `hp/uploads/c7/nextouch_screen_saver/<cd_material>/<índice><extensión>`. Los registros con `disp_flg = '9'` se ignoran (borrado lógico).

## Señalización Windows (`touchcation/signage_w/`)

- Mismo flujo que la Android para la caja de un comercio con PC (POSViewer): visita por ID/QR, sello, **impresión de cupón en una impresora de recibos USB** y lectura de tarjeta con el lector NFC del PC.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| (los 39 de `signage/`) | | | Ver arriba; 8 páginas `*_x` difieren | | Solo exige `shop_cd` y **no registra terminal** |
| `control.php` | | ajax | `mode=customerRegisteredCheck`, `uid` → `{"status":"ok","cd":<cd>}` o `{"status":"ok","cd":"-1","message":"該当顧客はありません。"}`; sin parámetros `{"status":"error","message":"必要変数がありません。"}` | L: `tb_customer` (`felica_no`, `touroku_kubun='2'`) | Sin autenticación; `uid` sin comillas escapadas en el SQL (revisado: pasa por `form2Db`) |
| `print/coupon.php`, `print/printCommon.php` | | ajax (binario) | Cupón `coupon_kubun='6'` vigente (`limit_flg` 1-5, ver Reglas), con tipo de impresora (`printerType`, por defecto SII/epson_a) | L: `tb_mycoupon_master` | |
| `js/appCom.js`, `js/base.js`, `jquery-2.1.4`, `jquery-ui`, `jquery.alerts` | | fragmento(include) | Puente con POSViewer | | |

## Señalización con cupones impresos (`touchcation/tagsignage/`)

- Variante hecha a medida para clientes (hosts `tagshops.jp`, `crm-tis.jp`): el cliente toca la tablet, ve contenidos y puede **imprimir cupones** de cumpleaños, de nivel de compra y privilegios de una encuesta, además del sello. Los 4 archivos de entrada (`index`, `attach`, `complete_attach`, `id_search`) **no cargan** (fatal "Cannot redeclare class Base"; BROKEN-FILES).

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `index.php` | Signage | detalle | Menú con botones de cupones (誕生月ﾌﾟﾚｾﾞﾝﾄ, 見返りクーポン, 特典), consulta `admin_master.crm_coupon_settings` | L: `admin_master` | Reglas por host (ver abajo); IP de impresora fija |
| `home_x.php`, `home_y.php`, `customer.php`, `coupon.php`, `thanks.php`, `stamppoint.php`, `contents.php`, `privilege_contents.php`, `attach.php`, `complete_attach.php`, `id_search.php` | CRM Signage, スタンプポイント | detalle / alta | Pantallas del flujo; `contents.php` (690 líneas): `rankCoupon`, `birthdayCoupon`, `getStamp` | L: `tb_nextouch_display`, `tb_customer`, `tb_kingaku_rank_master*`, `admin_master`, `tb_anketo_present` | |
| `ajax.php` | | ajax | Mismos tipos que `signage/ajax.php` + `qrCode` | L/E: `tb_customer`, `tb_felica_log`, `tb_terminal`, `tb_stamp_config`, `tb_kingaku_rank_master`, `tb_anketo_present`, `admin_master` | 842 líneas |
| `ajax_getCouponList.php` | | ajax | `shop_cd` → `{ "status":"ok", "data":[{cd,title,comment,attention,...}] }` con textos en `rawurlencode`; cupones `coupon_kubun='8'` vigentes (`limit_flg` 1=7 d, 2=30 d, 3=90 d, 4=`time_limit` días, 5=fecha) | L: `tb_mycoupon_master` | `{status:"error", errorMsg:"データがありません。"}` si no hay |
| `ajax_addIssuedCoupon.php`, `ajax_addIssuedPrivilegeCoupon.php`, `ajax_setCouponCount.php` | | ajax | `couponType`, `customerCd`, `terminalCd` → `{ "status":"ok" }` o `{ "status":"error", "errorMsg":"書き込みに失敗しました" }` | E: vía `class/Coupon.php` (`tb_anketo_present`, `tb_customer`) | |
| `print/coupon.php`, `birthDayCoupon.php`, `privilegeCoupon.php`, `printCommon.php`, `samples.php` | | ajax (binario) | Genera ESC/POS: `printerType` `star_a` o `primex_a` (fijo `primex_a`); `couponType` `birthday`, `sales_rank`, `privilege` o `cd` | L: `tb_shop`, `tb_customer`, `tb_kingaku_rank_master`, `tb_mycoupon_master`, `tb_c3_anketo2_cate`, `admin_master` | `birthDayCoupon.php` = `privilegeCoupon.php` (idénticos) |
| `signage/index.php`, `index_for_app.php`, `signage.php`, `ajax_time.php` | CRM Digital Signage | detalle | Reproductor de programa | L: `tb_nextouch_*`, `tb_shop`, `tb_terminal` | `signage.php` sin `db.php` (BROKEN) |
| `class/Base.php`, `Coupon.php`, `Functions.php` | | fragmento(include) | Clase `Base` **duplicada** con la de `system/class/Base.php` (causa el fatal) | | |
| `fnc_getStampCount.php`, `inc/*` | | fragmento(include) | | | |

**Reglas por cliente (host):** el día de inicio para emitir cupones de **nivel** y de **cumpleaños** es **10 y 5** en producción; **1 y 1** en `crm-develop.tagshops.jp`, `mm.tagshops.jp`, `develop-warmshop.crm-tis.jp`; **5 y 5** en `bunkado.tagshops.jp`. El menú de desarrollo solo sale en `crm-develop.tagshops.jp`. Llevar a `config/signage.php` por cliente, **no por `HTTP_HOST` en el código**.
**Contrato de impresión:** la APK (con `printData`, no incluida) pide `GET /touchcation/tagsignage/print/coupon.php?shop_cd=&…&couponType=&customerCd=` (parámetro `cd` para cupón normal) y manda el cuerpo a la impresora en `printerIP` (`192.168.128.50` fijo en 3 pantallas). Cuerpo: texto con códigos ESC/POS (`ESC RS F`...) y corte parcial (`PARTIAL_CUT`).

## Señalización web (`signage/`)

- Sistema independiente (BD `admin`, no `crm2019`) para gestionar pantallas de señalización por navegador: se registran **terminales** (pantallas), **paquetes** (qué marcos ve cada terminal), **ciclos** (imágenes y franjas horarias) y **contenidos**. La pantalla final abre `view/?cd=<terminal>`. Se entra con un enlace desde el sistema (`?s=<hash>`).

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `index.php` | ホーム | lista | "現在表示中のサイネージ": terminales y su `iframe` de vista previa | L: `terminal` | URL del terminal `view/?cd=` |
| `list/terminal.php` | 端末一覧 | lista, alta, edición, borrado | CRUD de terminal (`name`, `packages_cd`, `display_direction`, `display_number`, `display_type`, `flg`) | L/E: `terminal`; L: `packages` | Borra de verdad (sin `disp_flg`) |
| `list/packages.php` | パッケージ一覧 | lista, alta, edición, borrado | Elige orientación (縦/横), nº de pantallas (1, 2, 3, 4, 6, 8) y diseño; crea los ciclos asociados | L/E: `packages`, `cycle` | 761 líneas, mucho JavaScript |
| `list/cycle.php` | サイネージ管理 | edición | Por marco: imágenes (`img_path_ary`), acciones (`action_ary`), repetición `1` siempre, `2` franja horaria diaria, `3` rango de fechas | L/E: `cycle`, `contents`, `contents_category`, `packages` | 665 líneas; guarda **`serialize()`** de PHP |
| `list/contents_category.php` | 画像サイズ別一覧 | lista, alta, borrado | Categorías por tamaño (`width` × `height`) | L/E: `contents_category` | Carpetas `upload/1080x1920`, `1920x1080`, `1920x2160`, `3840x1080`, `3840x2160` |
| `list/contents.php`, `list/contents_detail.php` | サイネージ管理 | lista, alta, edición, borrado | Sube imágenes del tamaño; la imagen se guarda como `<cd>.<ext>` | L/E: `contents`, `contents_category` | `contents_detail` es el popup |
| `list/movie_contents.php`, `movie_contents_detail.php`, `movie_contents_category.php` | サイネージ管理 | lista, alta, edición, borrado | Igual, para vídeo | L/E: `movie_contents`, `contents_category` | `movie_contents_category` borra de `contents_category` (¿error?) |
| `list/signage.php` | サイネージ管理 | alta, borrado | Sube un archivo (`jpg`, `png`, `gif`, `bmp`, `webp`, `mp4`, `webm`, `mov`, `swf`) | E: `signage` | Con lista blanca desde el paso 25 |
| `list/inc/*`, `inc_flame_cd/*`, `inc_js/*` | | fragmento(include) | Marcos de cada diseño (63 archivos, 17 diseños, ver duplicados) | — | |
| `view/index.php` | (pantalla) | detalle | Carga terminal → paquete → ciclos activos; incluye el diseño `view/<display_type>.php` | L: `terminal`, `packages`, `cycle`, `contents`, `contents_category` | **Sin sesión** |
| `view/ajax.php` | | ajax | `packages_cd` (en realidad el `cd` del terminal) → **JSON** con `update_date` de los ciclos activos; la pantalla lo consulta y recarga si cambia | L: `cycle`, `terminal` | |
| `view/packages.php` | | detalle | Vista previa por `cd` de paquete | L: `packages`, `cycle`, `contents*` | |
| `view/h_*`, `v_*` (13) | | fragmento(include) | Diseños | — | |
| `mail/index.php` | GoTel管理 | alta | Formulario "contacto": con `customer_cd` arma un texto de solicitud de líneas ("内線追加") | L: `customer`; E: `contact` | `$_POST['send']` sin `isset` |
| `pdf/invoice.php`, `pdf/receipt.php` | 請求書, 領収書 | pdf | Pide su propia versión HTML (`SIGNAGE_PDF_BASE_URL`, variable de entorno) y la convierte con mPDF 8 (`Mpdf`, modo `ja`, A4) para descarga | L: `customer` | La URL del HTML usa autenticación básica |
| `inc/files.php`, `db.php`, `function.php`, `head.php`, `side.php`, `footer.php`, `basic_authentication.php` | | fragmento(include) | Núcleo: sesión `/signage`, conexión a `admin` (`ADMIN_DB_PASSWORD`), `insertSQL`, `updateSQL`, `deleteSQL` | | `basic_authentication.php` tiene usuario y contraseña **en el código** (hoy comentado) |
| `_tool.php` | | cron/CLI | `chmod -R 777` de la carpeta, **sin sesión** | — | No migrar |
| `sample.php`...`sample4.php` | | detalle | Pruebas de color / iframes | — | No migrar |

**Flujo principal:** `terminal` → `packages` (diseño) → `cycle` (por marco) → `view/?cd=`.
**Reglas no obvias**
- Franjas: tipo `2` compara `to_char(hora, 'hh24:mm')` — el formato correcto sería `hh24:mi`: **`mm` es el mes**, por lo que la franja diaria se comporta mal (se detecta solo en meses distintos). Tipo `3` compara fechas completas con `NOW()`. Reproducir el comportamiento correcto (`hh24:mi`) y avisarlo.
- Las imágenes del ciclo se guardan con `serialize()`: al migrar, convertir a JSON o pivote `cycle_frames` y **no** usar `unserialize` sobre datos externos.
- Inserción de ciclos solo en `packages.php`.
**Reemplazo:** módulo `senalizacion` con modelos `SignageTerminal`, `SignagePackage`, `SignageCycle`, `SignageContent`; `Storage::disk('public')` para las imágenes en carpetas por tamaño; vista Blade de diseño parametrizada; mPDF → `mpdf/mpdf` (ya por Composer) o DomPDF con fuente japonesa; correo/contacto → `Mailable`.
**Nota de BD:** la base `admin` es **distinta** de `crm2019`; en Laravel, conexión adicional o importar a esquema `signage`.

## Señalización digital Flash / HTML (`digitalsignage/`)

- Reproductor para monitores en tienda (Flash `.swf` sobre un XML) con extras: ruleta (`roulette.php`) y tragaperras (`slot.php`) para sorteos de cupones, cupones impresos por "rastreo" (consulta periódica de nuevas visitas) y dos micro-sitios (inmuebles, recetas).

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `index.php` → `index2.php` | digital signage | detalle | Con `shop_cd` (por defecto 1) y `display_cd` carga `tb_display`, el XML del programa (`hp/uploads/c7/display/<cd>.xml`) y el `.swf` según `terminal_type` (`digitalsignage.swf`, `_landscape`, `_portrait`); vigila cambios y salta a ruleta o tragaperras | L: `tb_display`, `tb_c7_movie_program` | `index.php` solo hace `require index2.php` |
| `ajax_xml_check.php`, `ajax_xml_check_2.php` | | ajax | `xml_mod`, `display_cd` o `tb_c7_movie_program_cd`: si el XML o `update_date` es más nuevo devuelve `<script>` que recarga la página | L: `tb_c7_movie_program`, `tb_display` | Devuelve **JavaScript**, no JSON |
| `ajax_display_config_check.php`, `_2.php` | | ajax | `display_check_time`, `display_cd` → recarga si `tb_display.update_date` cambió | L: `tb_display` | |
| `ajax_network_check.php` | | ajax | Texto `確認できました` | — | |
| `ajax_print_coupon_crawl.php`, `ajax_print_coupon_craw_2l.php` | | ajax | `kanshi_time`, `shop_cd`: si hay visitas nuevas devuelve HTML de un cupón al azar (`coupon_kubun`) con datos de la tienda y `<script>imprime();</script>`; devuelve `kanshi_time` oculto para el próximo ciclo | L: `tb_raiten_rireki`, `tb_mycoupon_master`, `tb_shop` | La pantalla imprime con el navegador |
| `ajax_touch_print_coupon_crawl*.php`, `ajax_shinkyaku_print_coupon_crawl*.php`, `ajax_kaiin_raiten_print_coupon_crawl.php`, `ajax_Mujyouken_print_coupon_crawl_2.php`, `ajax_kaiin_mycoupon_print_crawl.php` | | ajax | Variantes (táctil, nuevo cliente, socio por visita, sin condición, cupón del socio) | L: `tb_mycoupon_master`, `tb_customer`, `tb_raiten_rireki`, `tb_shop`; `ajax_kaiin_mycoupon_print_crawl` E: `tb_mycoupon`, `tb_mycoupon_print`, `tb_mycoupon_used` | Era inyectable (corregido en el paso 29) |
| `roulette.php`, `slot.php` (+ `.swf`, `tig_game/`) | | detalle | Juegos con Flash | — | `roulette.php` no está en UTF-8 |
| `fla/floormap/ajax_print_crawl.php` | | ajax | Mapa de planta con reclutamiento | L: `tb_mycoupon_master`, `tb_recruit`, `tb_recruit_category`, `tb_shop` | |
| `fla/immovables/getFlash.php` | | ajax | Datos para el Flash de inmuebles | L: `tb_area2`, `tb_category2`, `tb_item` | |
| `html/immovables/*` (6 PHP) | | lista / detalle | Búsqueda de inmuebles (`tb_madori`) y cupón | L: `tb_madori` | `index.php` exige un `db.php` que no existe (BROKEN) |
| `html/recipe/index.php`, `recipe_detail.php` | | lista / detalle | Recetas (contenido estático) | — | |

**Reglas:** el `terminal_type` es una lista separada por `<>`; la pantalla pregunta cada pocos segundos si cambió el XML o la configuración (polling por JS).
**Reemplazo:** **Flash ya no se ejecuta en ningún navegador moderno**. Propuesta: un reproductor HTML5 (el de `signage/view`) que lea el mismo programa por JSON/XML desde Laravel; los juegos y micro-sitios solo si el cliente los pide.

---

## Tablas de BD del grupo

Base `crm2019` salvo indicación.

| Tabla | Para qué | Submódulos que la usan | Notas (columnas clave) |
|---|---|---|---|
| `tb_terminal` | Terminales (tablet, PC) | launcher, todas las apps de touchcation | `cd`, `terminal_no` (= `androidId`), `tb_shop_cd`, `application_flg` (lista `,`), `model`, `type`, `flg`, `rank`, `touroku_date` y `update_date` (epoch en texto), `latitude`, `longitude`, `gps_taiki_flg`, `gps_raiten_tb_customer_cd`, `gps_disp_flg`, `gps_hyouji_flg`, `mycode_disp_flg` |
| `hatuban` | Numerador | todos los registros | `koumoku`, `kazu` (**actualización sin bloqueo en estos archivos**; en la gestión se usa `FOR UPDATE`) |
| `admin_master` | Ajustes globales (fila `cd=1`) | casi todos | `terminal_logout_time`, `id_raiten_type`, `coming_use_beacon`, `crm_coupon_settings`, `shop_category_flg` |
| `tb_shop` | Tienda | casi todos | `kaishamei`, `keisan_flg`, `point_percentage_genkin`, `point_percentage_card`, `shiyou_point_jyougen` |
| `tb_staff`, `tb_office` | Personal y empresa (login) | serie S, point_app, kintai, timecard, numbered_ticket | `login_id`, `login_pass` (claro), `felica_no`, `shop_cd`, `rank_pass`, `disp_flg`; `tb_office.kaishamei` |
| `tb_customer`, `tb_customer_point`, `tb_customer_sub`, `tb_customer_card` | Socios y saldo | serie S, signage*, member*, id_raiten, point_app | `id`, `felica_no`, `kanri_id`, `touroku_kubun='2'`, `disp_flg`, `stamp_count`, `last_stamp_time`; `service_point`, `point_yukoukigen` |
| `tb_point_log`, `tb_stamp_log`, `tb_raiten_rireki`, `tb_felica_log`, `tb_beacon_history` | Movimientos y visitas | serie S, signage*, point_app, digitalsignage | `sounyu_point`, `jidou_point`, `shouhi_point`, `touroku_date`; `user_cd`, `raiten_time`, `shop_cd`; `action_flg`, `action_sub_flg`, `felica_no`, `terminal_no`, `url` |
| `tb_stamp_rules`, `tb_stamp_config`, `tb_stamp_oshirase_coupon` | Reglas de sello | serie S, signage*, gps, id_raiten, member_biz | `type_flg`, `start_time`, `end_time`, `stamp_count`, `hyouji_flg`; `stamp_max`, `limit_flg`, `tb_shop_cd` |
| `tb_mycoupon_master`, `tb_mycoupon`, `tb_mycoupon_used`, `tb_mycoupon_print` | Cupones | serie S, tagsignage, signage_w, digitalsignage | `coupon_kubun` (1, 6, 7, 8), `limit_flg` (1-5), `time_limit`, `tb_shop_cd` (-1 = todas), `attention` con `<>` |
| `tb_nextouch_display`, `tb_nextouch_movie_program`, `tb_nextouch_screensaver`, `tb_nextouch_navi` | Programas y materiales del quiosco | signage* | `rank` (= `terminal_cd`), `tb_c7_movie_program_cds`, `touch_area`, `sound_flg`, `wait_time`; `tb_screensaver_cds` (`<>`), `kirikae_time`, `pic_*`, `obi_flg`, `update_date`; `tb_nextouch_navi.name`, `hyouji_flg` |
| `tb_display`, `tb_c7_movie_program`, `tb_screensaver`, `tb_dispaly_systems` | Programas y pantallas de señalización digital | digitalsignage | `terminal_type` (`<>`), `update_date`. `tb_screensaver` **no tiene** `tb_c7_movie_program_cd` (MISSING-TABLES) |
| `kintai_tb_hiduke`, `kintai_tb_kintai`, `kintai_tb_config` | Marcas de asistencia | kintai_crm_* | `tb_staff_cd`, `kintai_flg`, `felica_no`, `kintai_time`, `password` |
| `tb_work_time`, `tb_felica_mode`, `tb_belongpost`, `tb_employmentsystem` | Asistencia mensual y catálogos | timecard, kintai | |
| `numbered_ticket` (sin prefijo), `tb_yoyaku`, `tb_yoyaku_uketsuke_day` | Turnos | numbered_ticket | `tb_yoyaku_cd`, `issued_number`, `issued_date`, `called_flg` (1 espera, 2 llamado), `called_date` |
| `tb_kingaku_rank_master*`, `tb_anketo_present`, `tb_c3_anketo2_cate` | Niveles por compra y regalos de encuesta | tagsignage | |
| `tb_photo_album`, `tb_play_time`, `tb_search_cate`, `tb_search_form`, `tb_base_system_child`, `tb_base_system_grandchild` | Álbum, buscador y menú | serie S | |
| `tb_recruit`, `tb_recruit_category`, `tb_madori`, `tb_area2`, `tb_category2`, `tb_item` | Contenido de micro-sitios | digitalsignage | De otros módulos (`category4`) |
| BD `admin`: `terminal`, `packages`, `cycle`, `contents`, `contents_category`, `movie_contents`, `signage`, `customer`, `shop`, `contact` | Todo `signage/` | signage/ | `cycle`: `schedule_repeat_type`, `schedule_start_time`, `schedule_end_time`, `flame_cd`, `img_path_ary`, `action_ary` (serializados), `status`, `flg`; `shop.email01` y `shop.pass` forman el hash de acceso |
| BD `crm2019` desde `timecard` | (se configura con constantes propias) | timecard | Conexión que no responde (MISSING-TABLES) |

---

## Propuesta Laravel 13

### Principios del contrato
1. **Las URL no cambian**: `/touchcation/<carpeta>/<archivo>.php` (con `.php`) se declaran como rutas literales; los archivos estáticos (`css/`, `js/`, `img/`, `bundle.js`, `*.apk`, `*.swf`) se copian a `public/touchcation/`, `public/signage/`, `public/digitalsignage/` y `public/version/` sin renombrar.
2. **Los dispositivos no inician sesión de administrador**: las rutas de quiosco **no** pueden vivir dentro del grupo `admin` ni con `auth:admin` (la convención del proyecto carga `routes/modulos/*.php` dentro de ese grupo).
3. **Formato exacto de las respuestas** (sección de contrato): cadenas, JSON a mano, JSONP, binario ESC/POS, `<script>`.
4. **Sin cambios en la APK**: mientras no haya una APK nueva, no se puede añadir autenticación por cabecera ni forzar HTTPS (la APK carga `http://` y los redirects relativos son la salida segura).

### Pedidos a S0 (fuera de mi propiedad de carpetas)
- Un segundo archivo de rutas (`routes/dispositivos.php`) cargado con un grupo `kiosk` **sin** `auth:admin`, prefijo vacío y sin `/admin`.
- **Exclusión de la verificación CSRF** (en Laravel 13 es `PreventRequestForgery`/`VerifyCsrfToken`: comprobar el nombre en el esqueleto) para `touchcation/*`, `signage/*` y `digitalsignage/*`: los dispositivos publican sin token.
- **Cookie de sesión propia** del grupo `kiosk` (`config('session.cookie')` distinta; `lifetime` mayor que `terminal_logout_time`) y `SameSite=Lax`; nombre de sesión separado para `signage` (el legacy usa `/signage`).
- **Migración de `terminals`**: la tabla actual (`shop_id`, `name`, `uid`, `status`) no tiene `terminal_no` (androidId), `applications` (lista de apps permitidas), `latitude`/`longitude`, banderas GPS, `last_seen_at` ni `rank` (necesario porque la señalización busca por `rank`). Propuesta: `uid` ← `androidId`; nuevas columnas o pivote `terminal_applications`; `last_seen_at` ← `update_date`.
- Facilitar un `Guard` `kiosk` sobre el modelo de personal de S1 (con contraseña `Hash`; mientras no migren las contraseñas, un `Hash::driver` de compatibilidad).
- La **AppKey** del `signageApp` es `md5(HTTP_HOST + '<>' + rank)`: depende del dominio. Mantener `config('signage.app_key_host')` con el host heredado, o regenerar las claves y actualizar los dispositivos. **No cambiar de dominio sin esto.**

### Modelos Eloquent (propios y reutilizados)
- Reutilizar: `Terminal`, `Shop`, `Customer` (con `felica_no`, `kanri_id`, `touroku_kubun`), `CustomerPoint`, `CustomerStamp`, `StampRule`, `StampSetting`, `PointSetting`, `Coupon`, `CouponCustomer`, `Visit` (en `app/Models`), y los `Enums` `PointMovement`, `StampMovement`, `CouponValidity`, `CouponUsage`, `StampDisplayMode`.
- Nuevos (módulo `quiosco`): `KioskDisplay`, `KioskProgram`, `KioskMaterial` (con tablas pivote en vez de listas `<>`), `KioskNavi`, `FelicaLog`, `AttendanceRecord` (+ `AttendanceDay`, `AttendanceConfig`), `NumberedTicket`, `BeaconHistory`.
- Nuevos (módulo `senalizacion`): `SignageTerminal`, `SignagePackage`, `SignageCycle` (JSON en vez de `serialize`), `SignageContent`, `SignageContentCategory`, `SignageContact`.
- Compartidos con S5 (`terminales`) para evitar duplicar: `KioskDisplay`, `KioskProgram`, `KioskMaterial`, `Display`, `Program`, `Screensaver` (ver doc 05, "Duplicación": un solo módulo con campo `type`).

### Rutas (`routes/dispositivos.php`, grupo `kiosk`)
- `GET|POST touchcation/launcher/index.php`, `touchcation/check.php`-equivalentes (`GET touchcation/{app}/check.php` ×15).
- `GET|POST touchcation/{point_s|stamp_s|all_in_one_apk_s}/{login|search|application|index}.php` → **un** `KioskPosController` con `app` = point/stamp/all; `touchcation/common_s_keyboard/ajax_control.php` (y `ajax_couponControl.php`, `ajax_couponList.php`) → `KioskPosAjaxController@__invoke` (despacho por `type`).
- `touchcation/gps_s/*`, `id_raiten/*`, `member_s/*`, `member_biz/*`, `kintai_crm_sp/*` (+ `kintai_crm_n/*`), `timecard/*`, `numbered_ticket/*`, `point_app/*` y `point_app/api/index.php?<nombre>`, `touchcation/api/getNextouchBasicInfo.php`.
- `touchcation/signage/*` (+ `signage_w/*`, `signageApp/*`, `signage_s/*`, `tagsignage/*`): un `SignageKioskController` con `layout` x/y y `windows` true/false.
- `signage/*` (gestión: grupo con `auth` propio por `?s=`/sesión `signage_login`), `signage/view/*` **pública**, `signage/pdf/*`.
- `digitalsignage/*` (público).
- `public/version/sharp/v.php` como ruta o archivo estático que devuelva el número de versión en texto.

### Controladores, servicios, jobs y comandos
- `KioskTerminalService` (registro de terminal, aplicaciones permitidas, `touch()` del latido), `KioskSession` (claves de sesión de arriba), `PointCalculator` y `StampCalculator` (puro, probado con casos de `enter_point`/`enter_stamp`, incluye `Error1/2`), `VisitRegistrar` (equivalente de `cUtility::raitenFunc`; compartido con S2/S3), `EscPosBuilder`, `AttendanceTransitionValidator`, `SignageScheduleResolver` (franjas, con `hh24:mi`).
- `Mailable` en cola para el correo de detalle de puntos y sellos.
- `gpsStandBy`: reproducir la espera con un bucle de `usleep` de hasta 8 s (consultando `tb_terminal`) o mejor con `Cache::get`/Redis; **mantener el contrato** (la petición debe volver ≤ 8 s con el cliente si llega).
- Scheduler: no hay cron en este grupo (el "rastreo" lo hace el navegador); solo se sugiere un comando `signage:prune-orphans` para imágenes huérfanas.

### Policies / Gates
- Gate `useKiosk` por aplicación (equivale a `application_flg`), Policy `KioskStaff` (mismo `shop_cd` que el terminal), y un Gate `manageSignage` para `signage/` (el acceso actual es un hash en URL).

### Orden recomendado de implementación
1. **Contrato base** (S0/S1): grupo `kiosk`, CSRF, cookie de sesión, migración de `terminals`, `Guard kiosk`, `public/version/`, `check.php`, `launcher`. *Dependencia: S1 (Terminal, Shop, Staff, hatuban).*
2. **Señalización Android** (`signage_s` + `signageApp` + `signage/*`) y sus dos endpoints (`ajax_signage`, `ajax_time`): son solo lectura y los dispositivos ya instalados dependen de ellos. *Dependencia: S5 (CRUD de `tb_nextouch_*`) o importación de datos.*
3. **Terminal de puntos/sellos serie S** (`common_s_keyboard`) con `PointCalculator`/`StampCalculator`. *Dependencia: S2 (clientes, visitas), S3 (reglas de punto, sello y cupón).*
4. **Visita por GPS/ID, registro PtoP, ticket numerado, asistencia, timecard, point_app + API.** *Dependencia: S5 (kintai y reservas), `appview/` y `r/` (04 y reservas).*
5. **`signage/` web** (CMS + reproductor + PDF).
6. **`signage_w`, `tagsignage`, `digitalsignage`** solo con confirmación de uso (ver Riesgos).

### Pruebas
Un caso de contrato por endpoint: **mismos parámetros → misma respuesta (cadena exacta)** contra la salida actual (capturas del legacy); y pruebas de sesión que imiten la APK (cookie, redirect relativo, `Content-Type`).

---

## Riesgos y dudas

1. **APK sin código fuente y con funciones faltantes.** Las 4 APK del repo no implementan `printData`, `formTextInput`, `stopNFC` ni `finishActivity`, pero los PHP las llaman (`tagsignage`, `common_s_keyboard`). Hay una APK más reciente fuera del repo. *Pedir al equipo la APK vigente y su `ini.ini`.* Cualquier cambio de contrato obliga a publicar una APK nueva (INTEGRATIONS.md).
2. **Las APK 2.701–2.703 llaman a `/@test/launcher/`** (no existe) y la 2.704 a `/touchcation/launcher/`; todas buscan la versión en `/version/sharp/v.php` (hoy `2.703`, pero la 2.704 está en `pos/` y `android3/`). Hay que decidir qué responde `v.php` y cuántos terminales usan cada versión.
3. **Sin autenticación de dispositivo.** `key` no se valida; `androidId` se autorregistra; los endpoints de señalización/visita (`signage/ajax.php`, `signage_w/control.php`, `getNextouchBasicInfo.php`, `member_biz/checkPtoP.php`) no exigen nada y devuelven datos de socios (nombre, estado). Contraseñas de personal **en claro** (`tb_staff.login_pass`) y copiadas a la sesión; token de `point_app` con sal fija; `md5(login_pass)` en URL (INTEGRATIONS: bloquea migrar a `password_hash`). *Decisión: ¿se acepta mantener este contrato en la primera versión de Laravel y endurecer con la APK nueva?*
4. **AppKey ligada al dominio** (`md5(HTTP_HOST...)`): cambiar de dominio rompe `signageApp`.
5. **Dominios y clientes fijos en el código**: `crm-develop.tagshops.jp`, `mm.tagshops.jp`, `bunkado.tagshops.jp`, `develop-warmshop.crm-tis.jp`, `develop.crm-s.net`, IP de impresora `192.168.128.50`. ¿Siguen activos esos clientes?
6. **Pantallas rotas** (BROKEN-FILES): `tagsignage/index|attach|complete_attach|id_search` (fatal por `Base` duplicada); `signage_s/signage.php`, `signageApp/signage.php`, `tagsignage/signage/signage.php` (sin `db.php`, solo fallan si se piden directo); `digitalsignage/html/immovables/index.php` (falta `db.php`); `point_app/api/index.php` sin parámetro (falta `404.php`). Confirmar si `tagsignage`, `signage_w` y las señalizaciones de touchcation siguen en uso (PENDING 0.5, MIGRATION).
7. **`touchcation/timecard`** se conecta a otra base que ya no existe en desarrollo; sin ella no se puede probar (MISSING-TABLES).
8. **Archivos no UTF-8**: `numbered_ticket/print/print.php` (Shift_JIS, genera ticket), `digitalsignage/roulette.php` y 2 más (PENDING). Revisar uno a uno.
9. **Flash** (`digitalsignage`): sin soporte en navegadores; ¿hay monitores que aún lo ejecutan (kiosco con navegador viejo)?
10. **Dependencias con `hp/` (obsoleta)**: `member_*` redirigen a `hp/mailsyori/felica_detail.php`; los reproductores leen `hp/uploads/c7/...`; GPS escribe `gps_raiten_tb_customer_cd` desde `hp/`. Hay que decidir dónde vive ese lado en Laravel.
11. **Errores de lógica heredados** (anotar y decidir): `hh24:mm` (mes) en `signage`; claves `'longitude '` y `'gps_taiki_flg '` con espacio; `UPDATE ... WHERE tb_customer_cd` frente a `WHERE cd` entre `common_n` y `common_s`; `signage/_tool.php` (chmod 777 sin sesión); `signage/list/movie_contents_category.php` borra de `contents_category`; `serialize()`/`unserialize()` de datos de BD.
12. **Concurrencia**: `gpsStandBy` bloquea un proceso PHP 8 s por petición (varios terminales saturan FPM); `hatuban` sin bloqueo en los registros de terminal (dos terminales nuevos a la vez pueden compartir número).
13. **Decisiones pendientes** (PENDING.md): 7 — ¿se usan `category11`, `touchcation/tagsignage`, las señalizaciones de `touchcation` y el módulo SNS?; CSRF sin cubrir en `touchcation/` (sesiones de quiosco).
