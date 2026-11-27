# Núcleo (S1): contrato para los módulos

Qué da el núcleo y cómo se usa. Lo mantiene S1; si un módulo necesita algo más, lo pide a S0.
Fuente legacy: `system/login.php`, `session.php`, `select.php`, `inc/head.php`, `header.php`, `left_navi*.php`, `footer.php`, `class/cKengen.php`, `class/cHatuban.php`.

## 1. Modelos base (tablas del legacy)

| Pieza | Para qué |
|---|---|
| `App\Models\LegacyModel` | Base de todo modelo `tb_*`: clave `cd` no incremental, sin `created_at`/`updated_at`, `$guarded = []`, columnas con su nombre original. |
| `Concerns\HasHatuban` | Al crear, `cd` = siguiente número de `hatuban` (contador = nombre de la tabla; otro con `protected string $hatubanKoumoku`). |
| `Concerns\LegacySoftDeletes` | `delete()` pone `disp_flg = '9'`; scope global `COALESCE(disp_flg, '') <> '9'` (NULL es visible). `forceDelete()`, `restore()` (pone `'1'`), `Model::withDeleted()`, `Model::onlyDeleted()`. Otra columna: `protected string $deletedFlagColumn`. |
| `Concerns\LegacyTimestamps` | Rellena `touroku_date`/`update_date` con epoch en texto (`time()`); también en `Model::where()->update()`. Otro formato: `protected string $legacyDateFormat = 'Y/m/d H:i:s'`; otras columnas: `$legacyCreatedColumn`/`$legacyUpdatedColumn` (null = no existe). Se leen como texto; `$model->legacyDate('touroku_date')` da Carbon o null. |
| `App\Services\Nucleo\Hatuban` | `next($koumoku)` (= `set_hatuban`) y `peek($koumoku)` (= `view_hatuban`), con `FOR UPDATE` en transacción. |
| Modelos del núcleo | `Shop` (`tb_shop`), `Staff` (`tb_staff`, usuario del login), `Terminal` (`tb_terminal`), `Office` (`tb_office`, `Office::current()`), `AdminMaster` (`AdminMaster::current()`), `ContentsMaster` (`text/int/put($category, $field)`), `BaseSystem`, `BaseSystemChild`, `BaseSystemGrandchild`, `Bookmark`, `LoginHistory` (`tb_login_rireki`). |

Zona horaria de la app: `Asia/Tokyo`, como el legacy.

### Numeración (`App\Services\Nucleo\Hatuban`)

- `app(Hatuban::class)->next('tb_x')` / `peek()`: contador de la tabla `hatuban` (cHatuban). `HasHatuban` lo usa en los modelos.
- `Hatuban::legacyCd($shopCd = '')`: `createHatsuban()` del legacy (epoch + tienda en 3 cifras + 6 cifras aleatorias, en texto) para las tablas sin contador. **Usar este en vez de copiar `time().sprintf(...).random_int(...)`**: hoy hay copias en `CustomerRegistrationService`, `StaffEntryController`, `CouponController`, `PointConfigController` y `LotteryConfig::legacyCd()`.
- **Pruebas** (inestables por orden): `Hatuban::fake($epoch = 1700000000, $start = 1)` hace `legacyCd()` determinista (epoch fijo y secuencia creciente: los `cd` salen en orden de alta); `tests/TestCase.php` lo deshace al terminar cada prueba. `Hatuban::fixCounters(['tb_x' => 100])` fija el próximo número de `next()` (lo deshace `RefreshDatabase`). Con `RefreshDatabase` la tabla `hatuban` ya empieza vacía en cada prueba (`next()` da 1): el orden de las pruebas solo influye si se usan números aleatorios o la hora.
- Si una consulta ordena por una columna con empates (misma hora, mismo `rank`), añadir un desempate (`cd`) según el orden que resulte del legacy: sin él, PostgreSQL devuelve los empates en cualquier orden (es el caso de `SetRaitenDetailTest`).

## 2. Login del personal

- URL del legacy: `GET|POST /system/login.php` (ruta `login`), `GET /system/logout.php` (ruta `logout`, enlace de la cabecera), `GET /system/select.php` (ruta `nucleo.select`, システム選択). `/` y `/system` redirigen. Módulos visibles (システム選択 y pestañas): los de `tb_base_system` activos, sin 女の子管理 (8) ni ホームページ管理 (4, descartada; en el legacy aparecía y aquí daría 404).
- Guard **`admin`** con provider `legacy_staff` (`App\Auth\LegacyStaffUserProvider`): `tb_staff.login_id` + `login_pass` en texto plano, `disp_flg = '1'`. Sin re-cifrado (la app y las integraciones dependen de la clave). Fortify y `users` ya no se usan.
- Mismos mensajes de error que el legacy, mismo historial en `tb_login_rireki` (ログイン / ログアウト, con hatuban), casilla "記憶する" = cookie `admin_login_id` con el usuario (nunca la clave), 90 días.
- Soporte del proveedor: `extsupport` (clave de un solo uso de 6) y `Relation` (clave de la cuenta o de un solo uso de 8) se verifican contra `CRM_SUPPORT_API_HOST` (por defecto `b.g-api.net`) y entran como `master`. Las claves internas `RELATION_LOGIN_PASS`/`SUPPORT_MASTER_PASS` van solo en `.env` y no se aceptan escritas en el formulario.
- Banderas de sesión (antes `$_SESSION[...]`): `session('nucleo.support_login')`, `nucleo.support_master`, `nucleo.relation_login`.
- Claves del legacy en la sesión: `session('shop_cd')` (tienda del staff) y `session('master_cd')` (`tb_shop.master_cd`), puestas al entrar y repuestas en cada petición si cambian, como `session.php`.
- `$siteName` está en **todas** las vistas (`$site_name` de `inc/db.php`: `tb_office` cd=1, `hp_hyouji_name` o `kaishamei`).
- Como `session.php`: el middleware `ValidateStaffSession` expulsa la sesión si cambia `login_pass` o el staff se borra.
- En controladores y vistas: `auth('admin')->user()` (o `$request->user('admin')`) es el `Staff`. Equivalencias: `$_SESSION['user_master_cd']` = `->cd`, `shop_cd` = `->shop_cd`, `rank` = `->legacyRank()`, `login_user_name` = `->loginUserName()`, `tantou` = `->name`, master = `->isMaster()`.
- Pendiente: primer acceso con `tb_office.kaishamei` vacío (el legacy manda al asistente `config.php`): hoy va a システム選択; se hace con 基本情報管理.

## 3. Rutas

Todo en `routes/web.php` (S1), en este orden:

| Archivo | Carga | Nombre |
|---|---|---|
| `routes/modulos/<modulo>.php` | Pantallas nuevas: prefijo `/admin`, `auth:admin` + `ValidateStaffSession` | `admin.<modulo>.*` |
| `routes/legacy/<modulo>.php` | URL literales del legacy (`system/category<N>/…/*.php`, sin barra inicial): sin prefijo, `auth:admin` + `ValidateStaffSession` | `legacy.<modulo>.*` |
| `routes/publico/<modulo>.php` | Dispositivos y contratos externos: sin login y **sin CSRF** (`withoutMiddleware(PreventRequestForgery)`), con sesión de `web` (sirve a `/appview/*`), URL literales. Controladores en `app/Http/Controllers/Publico/<Modulo>/` | `publico.<modulo>.*` |
| `GET /hp/uploads/{path}` | Genérica: archivo del disco `public` de Storage en `hp/uploads/<path>`. Va después de `routes/publico`, así una ruta específica de un módulo gana | `publico.nucleo.hp-uploads` |
| `GET /{path}.(css|js|png|…)` | Estáticos del legacy (sección 6). Siempre la última | `nucleo.legacy-asset` |

Una ruta legacy que en el legacy terminaba en `/` (p. ej. `/system/category1/`) se declara sin la barra: `Route::get('system/category1', …)`. Las portadas (`system/category<N>/` e `index.php`) son del núcleo: category0 (`legacy.base.index`), 1, 3, 5, 6 y 7 (`Nucleo\CategoryTopController`: imagen `coverN.gif`, menú del módulo y, en 1 y 3, los contadores de `inc/entry_counter.php`) y category9 redirige a `yoyaku/time_table.php`, como el legacy. El mapa categoría → clave de `config/menu` está en `CategoryTopController::MENUS`; si un módulo necesita otra portada, se quita su categoría de ese mapa en el mismo commit.

`routes/api.php` no se carga: las URL `/api/...` del legacy van en `routes/publico/integraciones.php`.

## 4. Permisos (cKengen → Gates)

- Matriz del legacy: `tb_kengen` (grupo × contenido) del grupo del staff, solo grupos con `disp_flg = '1'` y contenidos con `hyouji_flg = '1'`; funciones 1 閲覧, 2 新規, 3 編集, 4 削除 (`App\Services\Nucleo\Kengen`). Igual que el legacy, **un staff sin ninguna fila tiene todos los permisos** (cambiarlo es decisión pendiente).
- `master` pasa todos los Gates (`Gate::before`), salvo las banderas `nucleo.*`.
- Formas de preguntar (en Blade `@can`, en código `Gate::allows`, en rutas `->can()`):
  - Con nombre `<modulo>.<acción>`: los de `config/kengen.php` (`clientes.view|create|update|delete` = 1001, `ventas.*` = 1033, `promocion.coupon` = 1009, `sns.configure` = 1034/1…). Un módulo que necesite otro: `NucleoServiceProvider::defineKengenGate('reservas.staff', 1028, 1)` en su `ServiceProvider`, o pedirlo a S1.
  - Por código: `@can('kengen.1001.2')`.
  - Con lista (basta uno): `Gate::allows('kengen', [1001, 2])`, `Gate::allows('kengen', [[1001, 2], [1033, 2]])`.
- **Permisos vs condiciones.** `master` pasa todo Gate de PERMISO, pero no las CONDICIONES (módulo o función activa: app encendida, UI CRM2, beacon…), igual que el legacy. Convención: un Gate de condición se llama `<modulo>.<algo>.enabled` (o `nucleo.*`) y `Gate::before` no lo resuelve para master: se evalúa su función. Si la condición además pide permiso, lo consulta dentro: `Gate::define('mypage.mycolle.enabled', fn ($s) => AdminMaster::current()->application_use_flg == 1 && Gate::forUser($s)->allows('kengen.1035.1'))` (master pasa el `kengen`, no la app apagada). `NucleoServiceProvider::isCondition($nombre)` dice cuál es cuál.
- Banderas (no las pasa master por sí solo, como el legacy): `nucleo.master`, `nucleo.developer` (`STAGE=docker` o IP en `CRM_DEVELOPER_IPS`), `nucleo.support`, `nucleo.support-master`, `nucleo.maintenance` ((IP de desarrollo o soporte) y no Relation: menús de mantenimiento), `nucleo.shop-create` (IP de desarrollo o `support_master`).

- 基本情報管理 (`system/category0/*`) exige `nucleo.master` en el servidor (el legacy solo lo ocultaba); queda abierto el asistente de primer acceso `system/config.php`.

## 5. Layout (CONVENCIONES §4)

```blade
@extends('layouts.legacy')
@section('title', '顧客検索 -総合業務管理システム-')  {{-- <title> completo, como el legacy --}}
@section('menu', 'clientes')                        {{-- config/menu/clientes.php; sin menú = una columna (wrap oneColumn) --}}
@section('css') <link …> <style>…</style> @endsection
@section('js') <script>…</script> @endsection        {{-- después de jQuery, jQuery UI y el CSRF --}}
@section('content') … @endsection                   {{-- el interior de <div class="main"> --}}
```

- `content` es exactamente lo que en el legacy va dentro de `<div class="main">` (después de `main1`…`main7`); normalmente uno o varios `<div class="contents">`.
- Opcionales: `@section('wrapClass', 'customerDetail')` (se agrega a `.wrap`), `@section('body_class', '…')` (se agrega a `pageCategory<N>`), `@section('jquery', 'jquery-1.11.2.min.js')` y `@section('jquery_ui', 'jquery-ui-1.11.4.min.js')` (como `createHead([...])` de las pantallas que usan un jQuery más nuevo). `@section('ui_version', '1')` (o `'2'`) fuerza la versión de UI solo en esa pantalla (head con `import.css` o `mainStyle.css`, logo, botones de cabecera y títulos del menú, vía `LegacyUi::uiVersion()`), para las páginas que en el legacy cargan su propio `<head>` (p. ej. `felica/terminal_list.php`, UI clásica); los módulos visibles siguen `admin_master.base_system_version_flg` (`versionFlg()`).
- `layouts.legacy-popup`: mismas secciones, sin cabecera, menú ni pie (ventanas emergentes).
- El layout pone: `<head>` de `Base::createHead` según `admin_master.base_system_version_flg` (2 = CRM2 con `mainStyle.css`, 1 = clásica con `import.css`), jQuery 1.3.2 + compat + jQuery UI 1.7.2 (dialog, accordion, tabs, datepicker, sortable, draggable), la cabecera (logo, お問合せ, サポート en dominios crm-s, 総合トップ, ログアウト, pestañas de módulos con el actual seleccionado, 仕入れ, accesos directos `tb_bookmark` con su permiso), el menú lateral con LOGIN NAME y el pie. `body` = `pageCategory<N>`, con `<N>` sacado de la URL `system/category<N>` o del `category` del menú.
- Acordeones del menú: se inicializan cerrados (como `inc/ac.php`) y se abre el bloque que contiene un enlace a la URL actual. Si la pantalla necesita abrir otro, lo hace en su `js` (`$(".ac2").accordion('option', 'active', 0);`).
- No replicado a propósito: la "ページカスタマイズ" de `tb_base_system.sys_*` (scripts inyectados en las portadas de categoría) queda pendiente para cuando una portada la necesite.

- La clase del `<body>` es `pageCategory` + el segundo tramo de la URL sin "category" (`Base::getSelectCategoryNo`: `/system/category1/…` → `pageCategory1`, `/system/select.php` → `pageCategoryselect.php`); en las URL `/admin/…`, la categoría del menú (`config/menu/<clave>.php` → `category`, o la de `CategoryTopController::MENUS` si falta).

## 6. Menú lateral: `config/menu/<modulo>.php`

```php
return [
    'category' => 1,                       // número de categoryN (pestaña seleccionada y clase loginName1)
    'login_class' => 'loginName1',         // opcional; por defecto loginName<category>
    'blocks' => [
        [
            'title' => '顧客管理',          // texto en la UI 2 / alt en la UI 1
            'image' => 'ln_t1_1.gif',       // imagen de /system/img en la UI 1
            'ac' => 'ac1',                  // clase del acordeón (por defecto ac<posición>)
            'child' => '顧客管理',           // opcional: solo si ese tb_base_system_child está activo (y en su orden rank, cd)
            'can' => 'clientes.view',       // opcional: Gate o lista de Gates (basta uno)
            'image_only' => false,          // opcional: imagen también en la UI 2 (bloques que el legacy dibuja siempre con <img>)
            'title_from' => [Clase::class, 'metodo'], // opcional: título calculado (vacío = el fijo)
            'links' => [
                ['label' => '顧客新規登録', 'url' => '/system/category1/customer/customer_new.php', 'can' => 'clientes.create'],
                ['label' => '顧客ランク設定', 'url' => '/system/category1/rank/customer_rank_list.php', 'grandchild' => '顧客ランク%'],
                ['label' => 'CSVインポート', 'url' => '/system/category1/customer/csv_import.php', 'can' => 'nucleo.maintenance'],
                ['label' => 'オススメ管理', 'url' => '…', 'label_from' => [MobileNavi::class, 'recommendTitle']], // texto calculado (vacío = el fijo)
                // 'target' => '_blank' opcional
            ],
        ],
    ],
];
```

Las condiciones que no encajen (dominio, `admin_master.*_use_flg`…) se expresan como un Gate propio del módulo `<modulo>.<algo>.enabled`, definido en su `ServiceProvider`, y se usan en `can` (master no las salta). `label_from`/`title_from` llevan `[Clase::class, 'metodo']` estático (sin closures, para `config:cache`). Ejemplo hecho: `config/menu/base.php` (left_navi0).

## 7. CSRF

- Laravel 13 (`PreventRequestForgery`) acepta sin token toda petición con `Sec-Fetch-Site: same-origin`, que mandan todos los navegadores actuales: es la misma idea que el `csrf_guard()` del legacy. Para los WebView viejos de las tablets, el layout además:
  - pone la cabecera `X-CSRF-TOKEN` en todo jQuery (`$.ajaxSetup({beforeSend})`, vale desde jQuery 1.3.2);
  - agrega un `<input type="hidden" name="_token">` a todo `<form method="post">` al cargar, en el evento `submit` y en `form.submit()` llamado desde JS.
- Por eso los Blade de los módulos **no necesitan `@csrf`** (ponerlo no molesta). Las rutas de `routes/publico` no tienen CSRF.

## 8. Estáticos del legacy

- Viven en `public/legacy/` con la estructura del legacy (`public/legacy/system/css`, `…/js`, `…/img`, `…/images`, `…/plugin`, `…/fonts`, `public/legacy/common/`, `public/legacy/hp/img/banner/`; los de cada categoría, del módulo dueño, en `public/legacy/system/category<N>/`).
- Se piden con **su URL original absoluta**: `/system/css/mainStyle.css`, `/system/category1/js/x.js`, `/common/…`. No hace falta reescribir rutas en el HTML ni en los JS copiados. Funciona así:
  - Apache: `public/.htaccess` reescribe a `public/legacy/<uri>` si el archivo existe (sin pasar por PHP).
  - Sin Apache (`php artisan serve`, pruebas): la ruta final `nucleo.legacy-asset` (`LegacyAssetController`) sirve el mismo archivo.
- Las URL relativas de los CSS (`url(../images/…)`) siguen funcionando porque la estructura es la misma.
- No copiar `.php` dentro de `public/legacy/` (Apache los ejecutaría).

## 9. Configuración y paquetes

- `.env`: `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`; `STAGE` vacío en producción; `CRM_DEVELOPER_IPS`; `RELATION_LOGIN_PASS`, `SUPPORT_MASTER_PASS`, `CRM_SUPPORT_API_HOST`.
- `config('services.mobileshop.*')` y `config('services.crm.api_key')` (S5). `firebase/php-jwt` 7.2.
- `bootstrap/providers.php` registra solo cualquier `app/Providers/*ServiceProvider.php`.
- Conexión `signage` (`config/database.php`): `pgsql` con `DB_SIGNAGE_HOST/PORT/DATABASE/USERNAME/PASSWORD` (en producción, la BD `admin` del legacy); si faltan, toma los de la conexión principal. `search_path` = `DB_SIGNAGE_SCHEMA` (por defecto `signage`): en desarrollo y pruebas las tablas viven en el esquema `signage` de la misma BD (las crea `database/schema/signage-admin.sql`, de S4). Uso: `DB::connection('signage')` o `protected $connection = 'signage'`.
- Conexión `beacon` (`config/database.php`): `pgsql` con `DB_BEACON_HOST/PORT/DATABASE/USERNAME/PASSWORD`, la BD externa de ビーコン (`category7/beacon/beacon_db.php` del legacy). Vacías en `.env.example` y sin valores por defecto de la principal; los reales solo en el `.env` local o de producción. Perezosa: Laravel conecta en la primera consulta, así la app y las pruebas arrancan aunque la BD no responda (`DB_BEACON_TIMEOUT`, 5 s por defecto, corta el intento). ビーコン en pausa (provisional): solo modelos de S4 con `protected $connection = 'beacon'`, sin pantallas.

## 10. CSV de descarga: `App\Support\LegacyCsv`

SJIS-win, `text/x-csv`, CRLF, `StreamedResponse` que convierte y envía por bloques (CONVENCIONES §4). Los helpers propios de los módulos deben pasar a esta firma:

```php
LegacyCsv::download(string $filename, iterable|Closure $rows, ?array $header = null, array $options = []): StreamedResponse
LegacyCsv::line(array $cells, array $options = []): string   // una línea UTF-8 con su fin de línea
LegacyCsv::encode(string $utf8): string                       // UTF-8 → SJIS-win
```

- `$rows`: filas (listas de celdas) o un closure que las devuelve (p. ej. `fn () => $query->cursor()->map(...)`), para que la consulta corra al enviar.
- `$options`: `quote` = `'all'` (por defecto: cada celda entre comillas, `"` → `""`), `'minimal'` (solo si lleva `,` `"` o salto) o `'none'` (tal cual); `eol` (`"\r\n"`); `content_type` (`'text/x-csv'`; algunos legacy usan `application/octet-stream`); `chunk` (500 filas).
- Formatos especiales del legacy (tabulador inicial para que Excel no convierta números, líneas de título antes de la cabecera): se arman en la celda o con `line()` + `encode()` dentro de un `streamDownload` propio.


## 11. PDF: `App\Support\LegacyPdf` (mPDF 8.3)

El mismo paquete que el legacy (`signage/pdf/invoice.php`). Por defecto `mode => 'ja'` + autoScriptToLang/autoLangToFont: el japonés sale con la fuente CJK incluida en mPDF (Sun-ExtA, incrustada), sin instalar fuentes.

```php
LegacyPdf::make(array $config = []): \Mpdf\Mpdf                       // instancia configurada (maquetar a mano: etiquetas DM)
LegacyPdf::fromHtml(string $html, array $config = []): string          // bytes del PDF
LegacyPdf::fromView(string $view, array $data = [], array $config = []): string
LegacyPdf::download(string $filename, string $html, array $config = [], bool $inline = false): StreamedResponse
LegacyPdf::downloadView(string $filename, string $view, array $data = [], array $config = [], bool $inline = false): StreamedResponse
```

- `$config` pisa las opciones de mPDF (`format` 'A4-L' o `[ancho, alto]` en mm, `margin_*`…). `inline: true` = `Output(..., 'I')`; por defecto descarga (`'D'`).
- Fuentes propias (IPAex, Noto): dejar los `.ttf` en `config('crm.pdf.font_dir')` (`CRM_PDF_FONT_DIR`, por defecto `storage/fonts`), declararlos en `crm.pdf.fonts` (ya están `ipaexg`/`ipaexm`) y elegir con `CRM_PDF_DEFAULT_FONT`. Solo se registran si el archivo existe; los archivos no van en git.
- **IPAex para nombres** (decisión S0, 2026-11-27): la fuente por defecto (Sun-ExtA) sirve para todo el texto, pero en caracteres de nombres como 髙 (U+9AD9) o 﨑 no se distingue bien de 高/崎 (ver `Doc/qa/README.md`, «PDF con japonés»). Para etiquetas DM, 請求書/領収書 u otros PDF con nombres de clientes, instalar IPAex (ゴシック `ipaexg.ttf` / 明朝 `ipaexm.ttf`, licencia IPA, descarga en moji.or.jp) en `storage/fonts` del servidor y poner `CRM_PDF_DEFAULT_FONT=ipaexm` (o `ipaexg`) en su `.env`. El valor por defecto no cambia.
- Temporales en `storage/framework/cache/mpdf`.

## 12. Subida temporal de imágenes: `system/inc/ajax_picupload.php` y `system/uploads/`

Común del núcleo (decisión S0, antes en S4): `Nucleo\PicUploadController` + `App\Services\Nucleo\TempUploads`. Lo llaman los `pic_upload.js` (AjaxUpload) de push (category3), category6 (`shop_hp`, `user_hp`, `digital_catalog`) y `system/js/pic_upload.js`.

- `POST /system/inc/ajax_picupload.php?up=true` (campo `file` = `picfile_<n>`, `tempfilename`, `ext`): guarda en el disco `public` `system/uploads/<temp>.jpg` (o `.gif`) y la miniatura `<temp>th.<ext>` de 240 px; responde el nombre o `error:<mensaje>` (mismos textos que el legacy). `GET …?f=<archivo>&h=<alto>`: responde el `<img>` de la miniatura. Solo con sesión del personal.
- `GET /system/uploads/<archivo>`: sirve el temporal (`[A-Za-z0-9_-]+.(jpg|gif)`).
- Para pasar un temporal a la carpeta definitiva del módulo: `app(TempUploads::class)->path($file)` / `absolutePath($file)` y los ayudantes GD `TempUploads::load()`, `shrink($img, $ancho, $siempre)`, `save($img, $ruta, $ext)`.
- Limpieza: tarea `nucleo:limpiar-subidas` (cada hora) borra los de más de un día, como hacía `login.php`.
