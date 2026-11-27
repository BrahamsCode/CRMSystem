# S1 — Núcleo e integraciones

**Modelo:** Opus 5.5, esfuerzo alto · **Carpeta:** `CRMSystem-nucleo/` · **Rama:** `l13/nucleo`
**Es dueña de:** ver `Doc/contratos/CONVENCIONES.md`, secciones 2 y 3 (S1): `composer.*`, `package*.json`, `config/`, `bootstrap/`, `routes/*.php` de raíz, layouts y componentes, modelos del núcleo.

## Modo autónomo

Trabajas sin pedir confirmación. Al terminar cada tarea: actualizar este archivo y seguir con la siguiente de "Cola". Al terminar todo o bloquearte: `SendMessage` a "S0 Coordinador CRM" (una línea). Cada entregable terminado con pruebas en verde: `git add` de tus rutas, commit (mensaje en español) y `git push origin <tu rama>`; después avisa a S0, que integra en `laravel13`. No haces merge ni cambias de rama; nunca `--force`.

## Próximo paso

A y B hechos (ver "Hecho"). Siguiente, cuando S0 lo confirme: Cola 2 (基本情報管理, category0: configuración, tiendas, asistente de primer acceso `config.php`) y los "Pedidos" que siguen abiertos.

**A) Base legacy** (corto; subir apenas esté): traits `app/Models/Concerns/` `HasHatuban` (numeración por `koumoku`), `LegacySoftDeletes` (scope `disp_flg <> '9'`, `delete()` pone `'9'`), `LegacyTimestamps` (`touroku_date`/`update_date` epoch en texto); modelo base `LegacyModel` (`cd`, no incremental, sin timestamps, `$guarded=[]`); servicio `Hatuban`; modelos `Shop` (`tb_shop`), `Staff` (`tb_staff`), `Terminal` (`tb_terminal`), `AdminMaster`, `ContentsMaster`, con pruebas sobre el esquema legacy; registrar `<Modulo>ServiceProvider` existentes en `bootstrap/providers.php`. Terminado: pruebas en verde, commit, push, aviso.

**B) Login + layout**, en orden: 1) login del personal contra `tb_staff`/`admin_master` (guard propio, mismas credenciales; quitar Fortify/`Admin`); 2) estáticos comunes en `public/legacy/` (`system/css|js|images|plugin`, `common/`); 3) layout Blade idéntico (`inc/head.php`, `header.php`, `left_navi*`, `footer.php`) con contrato de secciones; 4) menú desde `config/menu/*.php`; 5) Gates `<modulo>.<acción>` ≈ `cKengen`; 6) システム選択; 7) `NUCLEO.md`. Agregados de S0: layouts `layouts.legacy` y `layouts.legacy-popup` con secciones `title`, `css`, `js`, `content` y `@section('menu', '<modulo>')` (CONVENCIONES §4, no cambiar nombres); cargar `routes/legacy/*.php` sin prefijo, con `web` + guard del personal, nombre `legacy.<modulo>.*`; CSRF para jQuery con `$.ajaxSetup` + decidir `_token` en `<form>`; decidir URL de estáticos (`/legacy/system/...` vs `/system/...`); `/hp/uploads/{path}` registrada DESPUÉS de `routes/publico/*.php`. Terminado: login/logout contra `bd-legacy.sh` (master del backup) con la misma pantalla, pruebas Pest de login y layout, commit, push, aviso.

## Cola (después de la Fase 1, en orden)

1. **Núcleo** (`Doc/contratos/NUCLEO.md`, lo escribes tú): `.env`/`.env.example` con `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`; **estáticos del legacy** en `public/legacy/` y **layout Blade idéntico** al legacy (cabecera, menú lateral, `mainStyle.css`); menú desde `config/menu/*.php` (la carga de `routes/modulos/*.php` y `routes/publico/*.php` ya está en `routes/web.php`); **login del personal contra las tablas del legacy** (guard propio sobre `tb_staff`/`admin_master`, mismas claves que hoy; reemplaza Fortify+`Admin`), permisos (Policies/Gates equivalentes a `cKengen`), tiendas (`tb_shop`), módulos activables (`tb_base_system*`) y システム選択 (`select.php`) con la UI del legacy. Modelos base: `Shop`, `Staff`, `AdminMaster`, `Hatuban`. Fuente: `Doc/analisis-legacy/00-nucleo-y-base.md`.
2. **基本情報管理** (category0): pantallas de configuración y tiendas.
3. **Grupos de rutas para dispositivos y APIs** (los usan S4 y S5): grupo sin `auth:admin` ni CSRF, con su propia cookie de sesión, para los reproductores de señalización (`routes/modulos/senalizacion.php`) y las integraciones (`routes/modulos/integraciones.php`). Documentarlo en `NUCLEO.md`. Las integraciones las hace S5 y los reproductores S4.
4. **Datos:** ya no hay importador (misma BD). Cuando Antony consiga un volcado de producción o staging, cargarlo con `CRM_LEGACY_BACKUP=<archivo> bash scripts/bd-legacy.sh <bd> --forzar` y comprobar que las pantallas migradas funcionan con datos reales.
5. Paquetes comunes que pidan los módulos (PDF, Excel, FCM…), vía S0.

## Lee solo

- `CLAUDE.md`, `Doc/contratos/CONVENCIONES.md`
- `Doc/MIGRACION-LARAVEL13.md`, secciones 4 y 6
- Del análisis: `Doc/analisis-legacy/00-nucleo-y-base.md` (al empezar el núcleo), por secciones; `Doc/analisis-legacy/README.md` (hallazgos transversales)

## Pedidos

- **Hechos (2026-11-27):** condiciones `*.enabled` que master no salta; `label_from`/`title_from` en el menú; `system/img` ya estaba completo (480 archivos).
- **基本情報管理 pendiente:** ショートカット管理, エリア管理, 機能設定 + plantillas de caducidad, mantenimiento (システム管理, 権限項目管理, CRM端末管理). Ver `Doc/diseno/nucleo-base.md`.

Recibidos de S0 el 2026-11-26 (para la Cola, después de la Fase 1; S0 los asigna al integrar):

- **Rutas públicas** `routes/publico/<modulo>.php`: fuera del grupo `admin`, sin `auth:admin`, sin CSRF, sin prefijo, nombre `publico.<modulo>.*` (reemplaza el punto 3 de la Cola). `routes/api.php` no debe capturar `/api/auth`, `/api/customer`, `/api/shop`, `/api/point`, `/api/mobile_shop/*`, `/api/mpa_*.php` antes que `routes/publico/integraciones.php`. `/appview/*` con `StartSession`. `/p/c/{uid}` (S3) con `signed`. Documentar en NUCLEO.md.
- `GET /hp/uploads/{path}` → Storage (genérica; las rutas específicas de módulos tienen prioridad).
- Registrar `<Modulo>ServiceProvider` en `bootstrap/providers.php` (ya: `PromocionServiceProvider`, y quitar sus líneas de `routes/console.php`).
- `terminals`: `sort` (de `rank`), `terminal_no`, `model`, `type`, `comment` + mapeo desde `tb_terminal` (S4 §5).
- Tabla `staff` (modelo `Staff`) con columnas de S5 (operaciones-integraciones.md §2.3); FK a maestros de S5 en migración posterior a las suyas.
- Paquetes: `firebase/php-jwt`, `intervention/image`, PDF con fuente japonesa, editor HTML npm (TipTap u otro); servicio común de QR opcional (`bacon/bacon-qr-code` ya está).
- Config: `services.push.host`, conexión `beacon` (`DB_BEACON_*`), integraciones (`MOBILESHOP_*`, `keys/*.pem`, `CRM_API_KEY`, `crm_version` 2.704, `DOMAIN_LIST`, URL g-api), sin valores en git.
- Configuración global (`admin_master`): `system_mail`, `mail_footer`, `mail_send_filter`, `application_use_flg`, `smatopi_flg`, `domain_smatopi_id`, política de privacidad por tienda. Categorías de `contents_master` de un solo módulo → `<algo>_settings` del módulo.
- Gates/Policies: `kengen` 1005/1009/1011/1014–1017/1023/1032/1035/1036, `rank=master`, `manageSignage`, `viewWorkReport`.
- Módulos activables: プッシュ通知, ガチャリン管理, スマトピ連携, ポイント交換クーポン, 印刷, アンケート, スタンプ管理.
- 機能設定 (category0): passcode de la tienda móvil vía `MobileShopSetting` (S5).
- Importador: `id = cd` en `shops`, `customers`, `staff`, `visits`, productos; `legacy_cd` en tablas fundidas; resto en `legacy_id_map`; helper CSV SJIS-win; mapeo de S4 §2 (BD `admin` de `signage/`, archivos `hp/uploads/c6|c7`, `signage/upload/`).
- `zip.php` compartido si ya existe en CRMSystem.
- De S2 (clientes-ventas-reservas.md §5): **el núcleo empieza por `staff` + Gates**. `staff` con `commission_rate` numeric(5,2) (`tb_staff.kingaku_buai`), `id = cd`. Gates con patrón `<modulo>.<acción>` documentado en NUCLEO.md: `clientes.view`, `clientes.manage`, `clientes.tab.<pestaña>` (`myKengen[1001]`), `ventas.view`, `ventas.delete` (`[1033]`), `reservas.view`, `reservas.manage`. Quitar el grupo `customers` de `routes/web.php` cuando exista `routes/modulos/clientes.php` (C0 de S2). Cargar `routes/publico/reservas.php`. Tabla estándar `taxes` (ESTANDARES_BD §7). Paquete `mews/purifier` + decisión del editor enriquecido (PENDING 3.1). `PosGateway`: cada módulo declara la suya (no hay interfaz común).

## Decisiones

- Base legacy (A): `LegacySoftDeletes` filtra `disp_flg <> '9'` literal (como el SQL del legacy: un `disp_flg` NULL también queda fuera); `restore()` pone `'1'`; `forceDelete()` borra la fila. `Staff::active()` exige `disp_flg = '1'` (lo que pide el login legacy).
- `LegacyTimestamps`: epoch en texto por defecto (`time()`), formato cambiable con `$legacyDateFormat` (p. ej. `tb_shop_category` usa `Y/m/d H:i:s`); las columnas no se castean a Carbon al leer (hay `update_date` vacíos), se usa `legacyDate('col')`. También cubre `Model::where()->update()`.
- `Hatuban::next()` = `set_hatuban` (sin fila: inserta kazu=2 y devuelve 1); `peek()` = `view_hatuban`.
- `Terminal` sin borrado lógico (el legacy hace DELETE). `ContentsMaster` solo lee `disp_flg = '1'` (como `Base.php`).
- `LegacySoftDeletes`: corregido a `COALESCE(disp_flg, '') <> '9'` (NULL visible), pedido de S0/S2.
- Login (B): guard `admin` → provider `legacy_staff` (tb_staff, clave en texto, sin re-cifrar); Fortify quitado (composer, Actions, provider, config). Logout por GET como el enlace del legacy. Throttle de 20 intentos/min por usuario+IP (mejora). Primer acceso sin `kaishamei` → por ahora a システム選択 (el asistente `config.php` va con 基本情報管理).
- Revalidación de sesión como `session.php`: huella HMAC de `login_pass` en la sesión (no la clave en claro, que el legacy sí guardaba).
- `select.php`: sin efectos secundarios (sync POS, normalizar fechas, insertar tb_koumoku/tb_kengen_contents) ni las お知らせ de `b.g-api.net` (llamada externa en cada carga): anotado.
- CSRF: Laravel 13 acepta same-origin por `Sec-Fetch-Site`; además `$.ajaxSetup` con `X-CSRF-TOKEN` y `_token` automático en todo form POST (WebView viejos). Los Blade no necesitan `@csrf`.
- Estáticos: en `public/legacy/` pero servidos en su URL absoluta (`/system/...`) por `.htaccess` + ruta de respaldo `LegacyAssetController`; no se reescriben rutas. Se borraron los `.php` de `system/js/freerte_v1` al copiar.
- `config/crm.php`: quitadas las claves de los módulos borrados (modulos, menús, campos); quedan prefecturas y lo del núcleo.
- Acordeones del menú: se abre el bloque que contiene la URL actual (aproximación a los `accordion('option','active',N)` de cada página).
- `bootstrap/providers.php` registra solo cualquier `app/Providers/*ServiceProvider.php`: los módulos no editan ese archivo.

- Fase 1: `config/session.php` → `serialization = json` y `config/cache.php` → `serializable_classes = false` (valores del skeleton 13; si chocan con la base de Antony con `file`, prevalece la suya). `lock_path` de la caché de archivos → `framework/cache/locks`; canal `daily` de logging `days` → `max_files` (skeleton 13).
- `Dockerfile` (Render): `php:8.5-apache-trixie`, Node 24, sin instalar `opcache` (ya viene en el núcleo de 8.5). Se corrige `npm ci --only=production`, que omitía Vite (devDependency) y rompía el build.
- `CSRF`: no había referencias a `VerifyCsrfToken`/`ValidateCsrfToken` en el código; nada que renombrar a `PreventRequestForgery`.

## Hecho

- **基本情報管理: システム管理 (2026-11-27), category0 completo:** bloques de ajustes de `system.php` con `nucleo.maintenance` (sin 初期化 ni エクスポート), igual a la muestra con Playwright; `ContentsMaster::putLegacy()`, `crm.version`. `tests/Feature/Base/SystemTest.php`. Suite: 581 en verde.
- **基本情報管理: CRM端末管理 (2026-11-27):** `slim_list.php` (mapeo シリアル → tienda, 設定する que sí guarda) y la vuelta del popup de ac.crm-s.jp (`slim_href.php`, `slim_del_href.php`), con `nucleo.maintenance`. `tests/Feature/Base/AccessTerminalTest.php`. Suite: 572 en verde.
- **基本情報管理: 権限項目管理 (2026-11-27):** lista con 表示, popup de alta/edición (cd por rango de タイプ) y su AJAX, con `nucleo.maintenance`. `tests/Feature/Base/AuthorityContentsTest.php`. Suite: 509 en verde.
- **Pedidos S4 (2026-11-27):** conexión `signage` (DB_SIGNAGE_*, por defecto la principal con esquema `signage`) y `App\Support\LegacyCsv` (SJIS-win por bloques), firma en NUCLEO.md §10. Suite: 506 en verde.
- **基本情報管理: 機能設定 (2026-11-27):** los 16 bloques de `features_config.php` (admin_master, contents_master, 不達配信 y パスコード de モバイルショップ), igual a la muestra con Playwright; `LegacyText::db()`. Plantillas de caducidad y ajax de vigencia de category0 descartados (sin entrada). `tests/Feature/Base/FeaturesTest.php`. Suite: 444 en verde.
- **基本情報管理: ショートカット管理 (2026-11-27):** registradas ordenables (AJAX `ajax_bookmark.php`), funciones registrables por categoría con 更新 e 初期化 (SQL literal del legacy). `tests/Feature/Base/BookmarkTest.php`. Suite: 395 en verde.
- **基本情報管理 1/5 (2026-11-27):** 店舗情報管理 (lista, detalle, alta/edición con 確認, borrar/recuperar) y 店舗グループ en las URL del legacy, verificados con Playwright contra la muestra; comunes `AreaTree`, `LegacyHtml`, `LegacyCheck`, `LegacyText`, `ZipController` (todas las URL zip.php). Pruebas `tests/Feature/Base/`. Suite: 301 en verde.

- **B) Login + layout (2026-11-26):** login/logout/システム選択 en las URL del legacy contra `tb_staff`, `layouts.legacy` y `layouts.legacy-popup` (contrato §4), menú desde `config/menu/*.php` (`base.php` = left_navi0), portada `system/category0`, Gates (`config/kengen.php`, `kengen.<cd>.<fn>`, `kengen` con lista, banderas `nucleo.*`), estáticos comunes en `public/legacy/` (+ `hp/img/banner`), carga de `routes/legacy/*.php`, `/hp/uploads/{path}`, `Doc/contratos/NUCLEO.md`. Verificado con Playwright contra el backup (master): login, システム選択 y category0 idénticos a la muestra; logout OK. Pruebas: `tests/Feature/Nucleo/{LoginTest,LayoutTest}.php`. Suite: 200 en verde.
- **Pedido S5:** `firebase/php-jwt` 7.2.2, `services.mobileshop.*`, `services.crm.api_key`.

- **A) Base legacy (2026-11-26):** `app/Models/LegacyModel.php`, `Concerns/{HasHatuban,LegacySoftDeletes,LegacySoftDeletingScope,LegacyTimestamps}.php`, `app/Services/Nucleo/Hatuban.php`, modelos `Shop`, `Staff`, `Terminal`, `AdminMaster` (`current()`), `ContentsMaster` (`text/int/put`). Pruebas `tests/Feature/Nucleo/LegacyBaseTest.php` (7). Suite: 29 en verde.

- **Fase 1 (2026-11-26):** Laravel 12.69.3 → **13.44.1** en PHP 8.5.13. Línea base L12: 86 pruebas en verde (238 aserciones); con L13: las mismas 86 en verde. `npm ci && npm run build` OK (Vite 7). Paquetes: fortify 1.44, tinker 3.2, pest 4.7, pest-plugin-laravel 4.1, pail 1.3, pint 1.32, sail 1.71, collision 8.9.
