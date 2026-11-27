# Dependencias de terceros — inventario y reemplazos

> Versión japonesa: [DEPENDENCIES.ja.md](DEPENDENCIES.ja.md). Resumen de la migración y mapa de documentos: [MIGRATION.md](MIGRATION.md).

Proyecto: CRM System (crm2-php8) · Objetivo: **PHP 8.5.12** · Medido el 2026-10-30 en la rama `refactor/fase-0`.

El repositorio trae muchas librerías **copiadas a mano** hace años. No reciben parches de seguridad y varias no funcionan en PHP 8.5.12. La regla para todas es la misma:

- **PHP** → gestionarlas con **Composer** (`composer.json` de la raíz), como ya se hizo con FPDF en el paso 15.
- **JavaScript/CSS** → **una sola versión fija** de cada librería (npm o CDN con versión exacta), no 17 copias distintas.

Leyenda de estado: ✅ hecho · 🔄 en curso · ⏳ pendiente · 🗑️ código muerto (se puede quitar) · ❓ requiere decisión.

---

## 1. Resumen

| # | Librería | Versión en el repo | Usada por | Problema | Reemplazo | Prioridad | Estado |
|---|---|---|---|---|---|---|---|
| 1 | **mPDF** | 5.7 (2 copias, 168 archivos) | Facturas, recibos, reporte de admin (3 archivos) | No funcionaba desde PHP 7.0; en 8.5.12 compila pero falla al generar | `mpdf/mpdf` 8.x | **Alta** | ✅ código migrado (paso 20a) · ✅ copias viejas borradas (paso 20f) |
| 2 | **Services_JSON** | PEAR (3 copias) | 49 archivos (APIs `mpa_*`, admin, sistema) | Innecesario desde PHP 5.2, lento, crea propiedades dinámicas | `json_encode()` / `json_decode()` nativos | **Alta** | ✅ (paso 20b) · ✅ copias borradas (paso 20f) |
| 3 | **Qdmail** | 1.2.6b (2008, 3 copias) | Envío de correo: 7 archivos (decomail, revista, admin) | Sin mantenimiento; código de la era PHP 5 | `symfony/mailer` o `phpmailer/phpmailer` (ambos soportan ISO-2022-JP) | Media | ✅ Symfony Mailer (pasos 20g y 20h) · 🗑️ 3 copias de Qdmail sin uso, por confirmar su borrado |
| 4 | **jQuery** | 1.3.2 … 3.2.1 (10 versiones, 17 archivos) | 136 páginas cargan **1.3.2** (2009); el resto, otras 9 versiones | XSS conocidos en < 3.5 | Una sola **3.7.x** + `jquery-migrate` mientras se prueba | Media | ✅ paso 20d (34 copias + 3 CDN) · ⏳ quitar Migrate cuando se corrijan los avisos |
| 4b | **jQuery UI** | 1.7.2 (119 páginas), 1.8rc3, 1.10, 1.11 (11 copias) | Acordeón del menú, calendario, diálogos | XSS conocidos en < 1.13 (CVE-2021-41182/41183/41184, CVE-2022-31160); 1.7/1.8 no están hechos para jQuery 3 | **1.13.3**, sin cambiar el nombre de las copias | Media | ✅ paso 20e |
| 5 | **Bootstrap** | 3.3.6 / 3.3.7 | ~35 páginas | XSS conocidos (CVE-2018-14040/41/42, CVE-2019-8331) | **3.4.1** (mismo marcado, solo parches) | Media | ✅ (paso 20c) |
| 6 | **CKEditor** | 4.13.1 | `system/category2/item/edit.php` | Sin soporte desde 2023, XSS conocidos | TinyMCE actual (ya se usa en admin) o CKEditor 5 (revisar licencia) | Media | ❓ pendiente: las versiones actuales son GPL o comerciales (Antony lo consulta con su jefe) |
| 7 | **TinyMCE** | sin versión | 7 archivos de admin | Desactualizado | TinyMCE 7.x | Baja | ⏳ |
| 8 | **Select2** | 4.0.3 | 10 archivos de admin | Desactualizado | 4.1.x | Baja | ⏳ |
| 9 | **CodeMirror** | 5.24.2 | 4 editores de archivos/plantillas en admin | Desactualizado | 5.65.x (misma API) | Baja | ⏳ |
| 10 | **twitteroauth / facebookoauth** | sin versión (2 copias c/u) | Módulo SNS `category12` (6 archivos) | La API v1.1 de Twitter y esas versiones de Graph **ya no funcionan** | Decidir si el módulo SNS sigue; si sigue, `abraham/twitteroauth` | Baja | ❓ pendiente: no funciona; Antony lo consulta con su jefe |
| 11 | **Mail_mimeDecode** | copia corregida a mano (paso 15) | Lectura de correos entrantes (`ReceiptMailDecoder`) | Paquete PEAR abandonado | `zbateson/mail-mime-parser` | Baja | ⏳ |
| 11b | **Adobe Flash** (`.swf`) | 54 archivos, 41 pantallas | **Descarga CSV del personal** (`sample.swf`, 17 pantallas: clientes, encuestas, revista, asistencia, reservas) y juegos o animaciones | Los navegadores lo eliminaron en 2021: la descarga no puede funcionar en un navegador actual, con ninguna versión de PHP | Botón HTML + endpoint PHP con sesión que entregue el CSV | **Alta** | ❓ pendiente de decisión: es el único camino de esas descargas para el personal |
| 12 | **PHPMailer** | 6.0.1 (2017) | **Nadie** (no hay ningún `require`) | Vulnerabilidades conocidas | Quitar | — | ✅ borrado (paso 20f) |
| 13 | **TCPDF / Thinreports** | 6.2.8 | **Nadie** (los `require` están comentados) | — | Quitar | — | ✅ borrado (paso 20f) |
| 14 | **node_modules** versionados | — | `admin/demo2/common/FlexSlider2` (102 archivos) | Dependencias de desarrollo de una demo dentro del repo | Quitar | — | ✅ borrado (paso 20f) |

Ya gestionadas con Composer (no hacer nada): `illuminate/database` 13, `firebase/php-jwt` 7, `doctrine/migrations` 3, `vlucas/phpdotenv` 5, `vlucas/valitron` 1.4.7, `setasign/fpdf` 1.9 (paso 15), `pear/pear-core-minimal`, `phpunit/phpunit` 12 (desarrollo).

---

## 2. Detalle por librería

### 1. mPDF 5.7 → `mpdf/mpdf` 8.x — prioridad alta

- **Dónde**: `public_html/admin/lib/mpdf/` y `public_html/signage/inc/mpdf/` (copias idénticas).
- **Quién la usa**: `admin/pdf/report.php`, `signage/pdf/invoice.php` (facturas), `signage/pdf/receipt.php` (recibos).
- **Situación**: tenía un `switch` con dos `default:`, que es **error fatal desde PHP 7.0**. Esos PDF ya no funcionaban antes de la migración. En el paso 17 se corrigió para que compile, pero al generar un PDF falla (`count(null)` y otros cambios de PHP 8).
- **Cambio de API** (mínimo):
  ```php
  // antes
  $mpdf = new mPDF('ja', 'A4');
  // después
  $mpdf = new \Mpdf\Mpdf(['mode' => 'ja', 'format' => 'A4', 'autoLangToFont' => true, 'autoScriptToLang' => true]);
  ```
  `WriteHTML()` y `Output()` se mantienen.
- **Al terminar**: borrar las 2 carpetas copiadas (168 archivos).

### 2. Services_JSON → JSON nativo — prioridad alta

- **Dónde**: `system/class/lib/JSON.php`, `admin/lib/JSON.php`, `system/tool/json/JSON.php`.
- **Quién la usa**: 49 archivos (APIs `api/mpa_*.php` de la app móvil, admin, sistema).
- **Cambio**: `$json->encode($x)` → `json_encode($x)`, `$json->decode($s)` → `json_decode($s)`. Cuidado con `SERVICES_JSON_LOOSE_TYPE`, que devuelve arreglos: equivale a `json_decode($s, true)`.
- **Verificación**: comparar la salida JSON de cada API antes y después (mismas claves, tipos y caracteres japoneses: `JSON_UNESCAPED_UNICODE` si el cliente lo espera).
- **Hecho (paso 20b)**: de los 49 archivos, 26 solo hacían el `require` sin usar la clase y 14 tenían un *polyfill* que en PHP 8.5.12 nunca se ejecuta. Las llamadas directas usan `json_encode($x, JSON_INVALID_UTF8_SUBSTITUTE)` (Services_JSON nunca devolvía `false`) y `json_decode($s, true)` donde había `SERVICES_JSON_LOOSE_TYPE`. APIs de la app (`api/mpa_*`, `touchcation/point_app/api/*`) verificadas: HTTP 200 y JSON/JSONP válido.

### 3. Qdmail 1.2.6b → `symfony/mailer` o PHPMailer actual — requiere decisión

- **Dónde**: `inc/qdmail.php`, `system/category3/decomail/inc/qdmail.php`, `hp/inc_m/test/qdmail.php`.
- **Quién la usa**: decomail, revista (mail_mag), recepción de correos en admin (7 archivos). Además hay **66 archivos con `mb_send_mail()`** y 8 con `mail()`.
- **Decisión**: unificar todo el envío en una sola librería con SMTP autenticado (hoy depende del `sendmail` del servidor). Es el cambio con más impacto en negocio (revistas, cupones, recordatorios): conviene hacerlo con pruebas en MailHog (ya incluido en Docker, puerto 8025).

### 4–9. Librerías de frontend (jQuery, Bootstrap, CKEditor, TinyMCE, Select2, CodeMirror)

- **jQuery** (paso 20d, en curso): 34 copias, de la 1.2.3 a la 3.1.1. La más usada es `/system/js/jquery-1.3.2.min.js` (127 páginas del sistema del personal).
  - **Estrategia**: cada copia vieja conserva su nombre (no se tocan las páginas), pero su contenido pasa a ser **jQuery 3.7.1 + jQuery Migrate 3.5.2 + `common/js/jquery-compat-legacy.js`**, generado con `tools/scripts/jquery-compuesto.php`. Los originales quedan en git para volver atrás.
  - **Capa de compatibilidad** (`jquery-compat-legacy.js`): Migrate 3 solo restaura lo quitado en 3.0 (`.size()`, `.andSelf()`, `.load(fn)`…); la capa restaura lo quitado en 1.9 que todavía usan las páginas y **33 plugins**: `$.browser`, `.live()`/`.die()`, `.selector`, `$.curCSS`, `$.boxModel`. Cada uso deja un aviso `JQMIGRATE` en la consola para corregirlo en el origen.
  - **Verificación en navegador**: `tools/navegador/consola.mjs` (Chromium headless con Playwright, en Docker) recorre las pantallas con sesión iniciada y registra excepciones y `console.error`; `tools/navegador/comparar.php` compara antes/después. Línea base (jQuery viejo): 1,999 pantallas de `/system/`, **0 errores** al cargar.
  - **Por grupos**: 1) las 16 copias de `system/` (se verifican con el recorrido del sistema); 2) `hp/` (sitio público); 3) `admin/`, `appview/`, `signage/`, `touchcation/` (necesitan su propio recorrido; `admin/` además la BD `admin`).
  - ~~**Pendiente**: jQuery UI 1.7.2 y 1.8rc3 no están hechos para jQuery 3~~ **Hecho:** todas las copias contienen 1.13.3 desde el 2026-10-30 (paso 20e), con el parche de modales del 2026-11-09 (tabla de cambios, abajo).
- **Bootstrap 3.3.x → 3.4.1**: solo parches de seguridad, sin cambios de marcado.
- **CKEditor 4 → TinyMCE**: TinyMCE ya está en admin; usar el mismo editor en todo el sistema.
- Se prueban **pantalla por pantalla en el navegador** (el smoke y el probador de formularios no ejecutan JavaScript).

### 10. twitteroauth / facebookoauth — requiere decisión

El módulo SNS (`system/category12`) publica en Twitter y Facebook con APIs que ya no existen. Opciones: retirarlo, o rehacerlo con la API actual (Twitter/X exige plan de pago).

### 11. Mail_mimeDecode → `zbateson/mail-mime-parser`

La copia actual funciona en PHP 8.5.12 (corregida en el paso 15), pero es código abandonado. Reemplazo en un solo archivo: `system/class/ReceiptMailDecoder.class.php`.

### 11b. Adobe Flash → botón HTML con descarga por PHP

`csv_download/sample.swf` llama a `csv_file_out.php`, que escribe el CSV en `csv/` y responde `generation=<archivo>`; el `.swf` lo descarga y luego llama a `csv_file_del.php`. Sin Flash ese flujo no arranca. Además, para descargar, la carpeta debía servir archivos sin control de acceso: el paso 32b la cerró con 403.

Propuesta: un endpoint con sesión que genere el CSV y lo entregue directamente con `header()` + salida, sin dejar el archivo en disco, y un botón HTML en las 17 pantallas. El resto de los `.swf` (juegos y animaciones) hay que decidir si se quitan.

**Sin confirmar:** que el personal no tenga hoy otra forma de descargar (por ejemplo, un navegador antiguo o un emulador de Flash). Si la usa, el 403 también la corta.

### 12–14. Código muerto

PHPMailer 6.0.1, TCPDF/Thinreports y los `node_modules` de la demo de FlexSlider no los usa nadie. Quitarlos reduce ~280 archivos y elimina código con vulnerabilidades conocidas. **No se borran sin confirmación.** (Borrados con confirmación en el paso 20f.)

---

## 3. Orden propuesto

1. **mPDF 8** (facturas y recibos rotos) y **Services_JSON → nativo** (49 archivos, mecánico)
2. **Quitar código muerto** (12–14), con confirmación
3. **Bootstrap 3.4.1** (sin riesgo) y **jQuery 3.7 + migrate**, pantalla por pantalla
4. **Correo**: decidir librería y migrar Qdmail + `mb_send_mail()`
5. Editores (CKEditor → TinyMCE, CodeMirror, Select2)
6. Módulo SNS: decidir si sigue

## 4. Registro

| Fecha | Librería | Cambio | Commit |
|---|---|---|---|
| 2026-10-29 | FPDF | Copia local → `setasign/fpdf` 1.9 por Composer (paso 15) | `75ddbae` |
| 2026-10-29 | Mail_mimeDecode | Copia local corregida para PHP 8.5.12 (paso 15) | `75ddbae` |
| 2026-10-30 | mPDF, TCPDF | Compilan en PHP 8.5.12 (`$s{0}`, `each()`, `default` duplicado) — paso 17 | `8ed31c2` |
| 2026-10-30 | mPDF | `mpdf/mpdf` 8.3.1 por Composer en facturas, recibos y reporte de admin; URL con Basic Auth a `.env` (`SIGNAGE_PDF_BASE_URL`). PDF con japonés verificado — paso 20a | `0861366` |
| 2026-10-30 | Correo (2/2) | Qdmail → `CrmDecomail` (en `inc/mail.php`, misma interfaz: `mtaOption()`, `easyDeco()`): decomail HTML con imágenes incrustadas por Symfony Mailer en `cMailsoushin.php`, `decomail/mailsoushin.php` y `mail_sche_send.php`. Probado en MailHog: `multipart/related`, `cid:` enlazado al Content-ID, remitente de sobre `mail_err@` para rebotes. Las 3 copias de Qdmail quedan sin uso (solo una página de prueba vieja) — paso 20h | (este commit) |
| 2026-10-30 | Correo (1/2) | `symfony/mailer` 8.1 por Composer. `public_html/inc/mail.php`: `crm_send_mail()` con la misma firma que `mb_send_mail()`; transporte por `MAILER_DSN` (por defecto `native://default` = `sendmail_path` de php.ini; en Docker `smtp://mailhog:1025`), UTF-8 o ISO-2022-JP (`MAIL_CHARSET`). **97 llamadas en 58 archivos** migradas (`tools/scripts/correo-a-symfony.php`). Probado en MailHog: japonés en asunto, remitente y cuerpo, HTML, Bcc, Reply-To, ISO-2022-JP; y el formulario de contacto real (`hp/m/contact_send.php`). Falta: Qdmail (decomail con imágenes) — paso 20g | (este commit) |
| 2026-10-30 | Código muerto | Confirmado por Antony: borradas las 2 copias de mPDF 5.7, TCPDF/Thinreports, PHPMailer 6.0.1, 4 copias de Services_JSON, `node_modules` de la demo FlexSlider y los samples de CKEditor: **405 archivos, ~48 MB** (quedan en el historial de git). Smoke 2,000 páginas sin errores — paso 20f | (este commit) |
| 2026-10-30 | jQuery UI | 11 copias (1.7.2, 1.8rc3, 1.10, 1.11) → **1.13.3** (hash oficial verificado), sin cambiar el nombre. `.accordion('activate', N)` (quitado en 1.10) → `.accordion('option', 'active', N)` en 498 archivos (`tools/scripts/acordeon-activate.php`). Textos del calendario japonés en texto plano (1.13 ya no interpreta HTML en `prevText`/`nextText`). Chromium: 0 errores nuevos en 2,472 pantallas; acordeón y calendario probados con clics — paso 20e | (este commit) |
| 2026-11-09 | jQuery UI (modales) | La 1.13.3 se carga **sin su CSS**, y `jquery-ui-dialog-compat.js` detecta por el nombre del archivo qué versión cargaba cada página (1.7.2, 1.8rc3 o 1.11 con su CSS). Reproduce solo lo que hacía esa versión: paridad visual con `master`, medida al píxel contra el servidor de desarrollo en 10 diálogos. Los modales que ya estaban rotos en `master` siguen igual (`PENDING.md` 0.14) | `a0a2a6fb` |
| 2026-10-30 | jQuery | Las 34 copias (1.2.3 … 3.1.1) contienen ahora jQuery 3.7.1 + Migrate 3.5.2 + capa propia, sin cambiar su nombre; 3 páginas que lo cargaban de un CDN (una por http://) usan `common/jquery/jquery-legacy.min.js`. Verificado en Chromium: 1,999 pantallas de `/system/`, 162 públicas y 311 de las demás zonas, 0 errores de JavaScript nuevos; acordeón y calendario (jQuery UI 1.7.2) probados con clics — paso 20d | `aa9f323`, `09bf5e5`, `4108298` |
| 2026-10-30 | Bootstrap | 31 archivos 3.3.5/3.3.6/3.3.7 (originales sin modificar, verificado por hash) → **3.4.1** (SRI oficial verificado). El CSS de Bootswatch (tema compilado) se conserva; su JS sí se actualizó. Ninguna pantalla usa tooltip/popover de Bootstrap, así que la sanitización nueva no cambia nada. Las carpetas conservan su nombre (`bootstrap-3.3.6-dist`…) para no tocar las páginas — paso 20c | (este commit) |
| 2026-10-30 | Services_JSON | 23 archivos: llamadas directas → `json_encode`/`json_decode`; polyfills de PHP 5.1 (`if (!function_exists('json_encode'))`) eliminados; 49 `require` de `JSON.php` quitados. Ya nadie usa la clase — paso 20b (`tools/scripts/services-json-a-nativo.php`) | (este commit) |
