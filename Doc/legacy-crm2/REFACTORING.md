# Plan de refactorización — CRM System (crm2-php8)

> **Estado:** Fase 0 en curso (rama `refactor/fase-0`) · **Inicio:** 2026-10-29 · **Herramientas:** [tools/README.md](../tools/README.md)
>
> Regla de oro: **ningún cambio sin dry-run, diff revisado, prueba en navegador y commit pequeño.**

---

## 0. Mapa completo y avance (actualizado 2026-11-11)

> Cómo leerlo: cada bloque tiene un **peso** y un **% medido** con herramientas (no estimado a ojo). El avance de la migración es la suma ponderada. **Seguridad** va aparte: la migración a PHP 8.5 y el endurecimiento son objetivos distintos.
> Qué NO se puede calcular por adelantado: los fallos que solo aparecen al **enviar formularios con datos reales** (POST) o en integraciones externas. Eso se descubre probando, y por eso pesa en el bloque C.

### A. Migración a PHP 8.5.12 — **≈ 94 %: el código está migrado; lo que falta depende del entorno**

> **Cierre (2026-11-11).** Todo lo que se puede ejecutar en Docker está migrado y probado: compila entero, los 10 módulos del sistema del personal se probaron a mano (`MANUAL-TESTS.md`) y las 615 pantallas de `/system` cargan con 0 avisos de PHP. El 6 % que falta no es código pendiente de cambiar, sino cosas que no se pueden ejecutar aquí: cron, servidor POS, sesión de socio, staging y el `php.ini` de producción. Lo aporta Antony o su jefe (`CONTINUE.md`, "Siguiente paso"). **`admin/` queda fuera de alcance** (Antony, 2026-11-11): su BD tampoco existe en desarrollo, y sus 160 páginas cargan sin avisos de PHP (`PENDING.md`, 1.2).

| # | Bloque | Peso | Medido | Hecho | Qué falta |
|---|---|---:|---:|---|---|
| A1 | Entorno y dependencias | 10 % | **100 %** | Docker PHP 8.5.12, Composer (Eloquent 13, JWT 7, Doctrine 3, PHPUnit 12), `php.ini`, `.htaccess`/`.user.ini`, conversión de Doctrine probada | — |
| A2 | Compilación | 10 % | **100 %** | `php -l` en PHP 8.5.12 de **todo** el repo. **Paso 47**: 2,564 de 2,564 archivos versionados sin errores ni deprecaciones, también `Thinreports` | — |
| A3 | APIs eliminadas o cambiadas (detección estática) | 20 % | **99 %** (2026-11-11) | **Paso 47**: funciones eliminadas entre PHP 5 y 8.5.12: solo queda `mysql_*` en `admin/list/rss_list.php` (decisión pendiente, nadie la enlaza). Cambios de comportamiento sin error, corregidos para que den lo mismo que en PHP 5: comparaciones de texto con número (`'' == 0`, 999 sitios), `date($f, null)` (daba hoy en vez de 1970, 1,385 sitios), `switch` con `case 0` (2), `htmlspecialchars()` sin modo (9 llamadas que guardan o envían). `ereg`/`split`, `each`, constructores PHP 4, `$s{}`, `mktime` `is_dst` (220), constantes sin comillas (75), firmas incompatibles, `@pg_*` (2,137), propiedades dinámicas (111 clases), BOM (28) | Paso 17: compilación 66 → 0; conexión `pg_*` 528 de 544. **2026-11-10/11:** `curl_close()` hecho; de los 16 `pg_*` con conexión implícita, 2 explícitos y 14 analizados y dejados a propósito (`PENDING.md` 5); `pg_*` sobre consulta fallida (7,730) o conexión nula (673) como en PHP 7; avisos de variables no definidas: 0 en pantalla. Queda solo `rss_list.php` (`mysql_*`, sin enlaces, decisión de borrarlo) |
| A4 | Ejecución de todas las páginas por GET (barrido) | 25 % | **99 %** (2026-11-13) | 2,257 de 2,449 archivos ejecutados en PHP 8.5; errores reales 18 → 7, y los 7 restantes ya fallaban antes de la migración. **Paso 42**: repetido tras los pasos de seguridad: 198 archivos dejan de fallar y 2 aparecen, ninguno por esos pasos. **2026-11-11:** recorrido con sesión por las 615 pantallas de `/system` (índices y los 140 enlaces de los menús): 0 avisos; 305 páginas de `hp/`, `touchcation/` y `signage`: solo 4 que ya daban 500 en `master` y en desarrollo (`BROKEN-FILES.md`). **`admin/`, 160 de 160 pedidas:** 0 avisos salvo `mail/index.php`, roto también en `master`; 14 fragmentos pasan a 403 por URL directa. Con esas 160, 2,417 de 2,449 archivos ejecutados. **2026-11-13:** lo que el código oculta por IP (`$is_develop`, 20 entradas del menú y partes de 12 pantallas; en Docker nunca se veía) y las **132 rutas sueltas** sin ningún enlace, abiertas una por una: de 41 con avisos quedan 12, todas rotas también en `master` o abiertas sin su `cd` (`MANUAL-TESTS.md`, última sección) | `exp_cmd/verUp` (13); `hp/user/` con sesión de socio. (Las 7 sueltas que necesitaban un `cd` válido, probadas el 2026-11-17) |
| A5 | Flujos con formularios (POST) y negocio | 25 % | **≈ 92 %** (estimación, 2026-11-13) | **Pasos 48-62 (hasta 2026-11-09):** los 10 módulos del personal probados a mano, botón por botón (163 filas en `MANUAL-TESTS.md`); `functional-flows.php` con **48** comprobaciones en verde. **Pasos 40 y 42-46**: `functional-flows.php` con 47 comprobaciones contra la BD; el envío masivo no enviaba nada porque `/usr/bin/php` no existe en la imagen (paso 46); probador de formularios repetido: 551 envíos a 365 scripts; 1,704 sitios protegidos donde un texto vacío o un `null` era fatal en PHP 8.5.12 (`numeric-on-text.php`, `array-fn-guard.php`), con la salida de las páginas comparada antes y después. Probados a mano: login, JWT/API, subida de imágenes, baja de mailmag, auto-login. **Paso 16**: probador automático, 239 envíos a 116 scripts: fatales 24 → 7 → **0**. **Paso 17**: lista ampliada (4,145 pantallas), 598 envíos a ~391 scripts (≈ 56 % de 698); los fatales que quedan son del entorno (POS externo, módulo `category11` sin tablas en el backup, `left_navi.php` inexistente) **2026-11-13:** 45 formularios de ajustes restringidos por IP guardados sin cambios, con la BD comparada tabla por tabla (encontró que guardar los módulos en `system.php` desactivaba 基本情報管理), y altas y bajas reales con datos `TEST` en quiosco, tienda (con borrado y recuperación), ポイント設定, CSV de puntos y importación de clientes | Lo que no se puede ejecutar en Docker: envíos programados (cron), flujos con el POS, zona de socios con sesión, `mycoupon` y encuestas (`admin/`, fuera de alcance) |
| A6 | Infraestructura y paso a producción | 10 % | **≈ 60 %** (estimación: 1 de los 5 pendientes cerrado) | **Paso 15**: FPDF por Composer + extensión japonesa portada a FPDF 1.9 (PDF de etiquetas DM verificado); `Mail_mimeDecode` como copia local corregida (el paquete PEAR está abandonado: `each()` y constructor PHP 4; correo japonés multipart verificado); IMAP sustituido por un cliente POP3 propio (`Pop3Simple`), porque Debian trixie ya no trae `c-client` | **`crontab` no está en la imagen: las tareas programadas no se ejecutan.** `php.ini` de producción, SQL de Doctrine en producción, prueba en un servidor de staging. (BD de `admin/`: fuera de alcance, 2026-11-11) |

Cálculo: 10 + 10 + 19.8 + 24.75 + 23 + 6 = **93.55 ≈ 94 %** (2026-11-13, con lo restringido por IP y las rutas sueltas probadas; antes 93 % con `admin/` fuera de alcance, antes 91 % y antes 85 %).

### B. Seguridad (refactor de vulnerabilidades) — **≈ 80 %**

> Estimación por temas de la tabla, no una medición: Semgrep no reconoce `(int)`, `pg_escape_string` ni `h()`, así que su cifra no refleja el avance. La tabla de temas se actualizó el 2026-10-31; la cifra incluye los pasos posteriores (33, 35, 36, 37, 39) y es la misma que da `CONTINUE.md`. Lo que falta pesa casi todo en dos temas: el XSS almacenado (~4,800 ecos, bloqueado por una decisión) y lo que depende de las integraciones (`PENDING.md` 4a).

| Tema | Tamaño medido | Estado |
|---|---|---|
| Ejecución remota de código pública (subida de web shell, shell, `require` con `../`) | 4 archivos | ✅ Paso 9 |
| SQLi en contexto numérico | 1,228 valores / 553 archivos | ✅ Paso 10 |
| SQLi entre comillas (zonas públicas e internas) | 94 + 9 + 61 valores | ✅ Pasos 11–13 (incluye el bypass del login de clientes) |
| SQLi en los buscadores (`search_set2.php` y copias: nombre de columna y valores del formulario) | 6 archivos, 222 puntos | ✅ Paso 16 |
| SQLi por variables intermedias o strings interpolados (`$x = $_REQUEST['a']; … "c = " . $x`) | 154 archivos con variables de la petición en SQL | 🔄 Paso 29: `tools/scripts/sql-variables-escapar.php` corrige los casos seguros (solo variables que nunca se reasignan): 111 `(int)` y 29 `pg_escape_string` en 51 archivos, editando solo los bytes de la variable; 5 heredocs y `cYoyaku::getShopYoyakuData` a mano; nombres de tabla con lista blanca de identificador. Ataque real con `pg_sleep(3)` y línea base: 4 endpoints públicos inyectables en HEAD (`digitalsignage/index2.php`, `ajax_xml_check_2.php`, `ajax_kaiin_mycoupon_print_crawl.php`, `touchcation/signage_w/ajax_signage.php`), ninguno después. **`customer_export.php` exportaba cualquier tabla sin login**, con el nombre sin comillas en un `COPY` de `postgres` (permitía `TO PROGRAM`): ahora exige sesión y lista blanca. **Paso 29b**: variables reasignadas pero siempre numéricas (`new_no`, `kensu`, `kongetu_year`, `shop_cd`…, opción `--numericas`): 215 `(int)` y 60 escapes en 125 archivos; este lote no tiene exploit demostrado (las páginas exigen login o son fragmentos), se apoya en el análisis. El detector baja de 154 a 17 archivos: quedan `order_by` (ya con lista blanca) y `table` (con guarda). **Paso 31**: la petición escrita directamente en el SQL (`tools/scripts/sql-peticion-directa.php`): `get_form()` escapa comillas, pero **no protege un valor sin comillas**; 259 casts (`(int)`, o `(float)` en montos) y 173 escapes en 211 archivos. Ataque sistemático por GET a los 80 parámetros corregidos en zonas públicas: `hp/mailsyori/touroku.php?kaiincd=` inyectable sin login en HEAD y corregido; en los otros 79 no se pudo disparar con un solo parámetro (muchos exigen login u otros datos), lo que no prueba que fueran seguros. **Paso 31b**: 81 interpolados y heredocs (en un heredoc el valor sale a `$sql_clave` antes de la sentencia); `touchcation/point_app/api/getLoginSession.php?logintoken=` inyectable sin login en HEAD y corregido (el salto de login no se demostró). **Paso 33**: los 116 restantes revisados uno por uno: 76 casts y 2 escapes más; `mycoupon_hakkou.php` **ejecutaba el `wherestr` del cliente sin login y emitía cupones** (inyectable en HEAD; ahora exige sesión y toma la cláusula de `$_SESSION['wherestr']`), `$_REQUEST['act']` formaba un nombre de columna y `admin/list/database.php` nombres de tabla (lista blanca de identificador), `*_cds` para `IN (…)` entero por entero. Los que quedan en el detector están protegidos: `csv_export.php` (`in_array`), `act` y `database.php` (guarda), `name` (escape global). **Falta**: valores que pasan por funciones o arrays, que estas herramientas no siguen, y lo que `admin/` conecta a `localhost` (no se puede atacar en Docker). **Semgrep no sirve para medir este avance**: no reconoce `(int)` ni `pg_escape_string`, y `tainted-sql-string` sigue en ~2,400 |
| Inyección de cabeceras de correo: formularios públicos (p. ej. `hp/m/contact_send.php`) arman `From:` con el correo del visitante sin quitar saltos de línea | 10 líneas en 7 archivos | ✅ Paso 20i: `crm_strip_newlines()` en el nombre y el correo del visitante; ataque real con `%0D%0ABcc:` verificado: no llega a terceros |
| XSS reflejado **directo** (`echo`/`<?=` de `$_GET`/`$_POST`/`$_REQUEST`/`$_COOKIE`/`$_SERVER[PHP_SELF…]`) | 2,728 ecos en 661 archivos | ✅ Paso 21: `h()` (`inc/h.php`, `htmlspecialchars` con `ENT_QUOTES` y sin doble escape); ataque real verificado antes (se ejecutaba) y después (no). **Paso 21b**: 12 ecos en JavaScript sin comillas (`d.openTo(<?=…?>)`, `var x = <?=…?>;`), donde `h()` no basta, pasan a `(int)`/`(float)`; ataque `pid=1);alert()//` verificado |
| Inclusión de archivos y `eval()` con datos del visitante (Semgrep `file-inclusion` 11, `eval-use` 6) | 11 + 6 | ✅ Paso 22: `versionUpAjax.php` (sin login, con el propio ID de sesión, exportaba la BD e incluía cualquier archivo) exige sesión de personal y solo acepta scripts de `exp_cmd/verUp/`; listas blancas en `customer_detail.php` (tab), `csv_file_out.php` (from_filename) y `admin/api/index.php` (t); los 6 `eval()` de los editores de diseño de admin (ejecución de código por el nombre del campo) reemplazados por asignación con referencias. Ataques verificados antes/después |
| Rutas de archivo con datos del visitante (Semgrep `tainted-filename`) | 42 usos directos | ✅ Paso 24: `csv_file_del.php` (9 copias) borraba **cualquier .csv del servidor sin iniciar sesión** (`age=../../../../x.csv`): ahora exige sesión y solo borra en su carpeta `csv/` (ataque verificado antes/después); `admin/list/url_scraper.php` leía archivos locales (`file:///etc/passwd`, también desde los enlaces de la página remota): solo http/https; copias FTP de admin sin `..`; parámetros de URLs internas con `urlencode()` |
| Subida de archivos ejecutables | 7 endpoints + signage + 12 carpetas | ✅ Paso 25: `system/inc/ajax_picupload.php` (y 6 copias) **sin sesión** guardaban con `tempfilename`/`ext` del navegador: subir `x.php` ejecutaba código (verificado: `CODIGO-42`). Nombre `[A-Za-z0-9_-]` y extensión en lista blanca; `signage.php` solo imágenes/video; `.htaccess` que impide ejecutar scripts en las 12 carpetas de subidas (verificado: 403). **Paso 25b**: los 5 endpoints de `system/` exigen sesión de personal (sin sesión: 302 al login; con sesión: suben igual) |
| Páginas del personal sin control de sesión (`tools/scripts/sin-sesion.php` + petición real sin cookies) | 195 candidatas: 97 responden 200 sin login (7 pantallas completas), 92 dan 500 (fragmentos) | 🔄 Paso 26: `jGetCustomerData.php` entregaba **los 22,322 clientes** sin login (ahora exige cd/id/tel); `smatopi_sent_history.php`, `smatopi_message.php` y `ajax_group_authority.php` exigen sesión. **Decisiones pendientes**: autenticación del programa de escritorio CTI (`desktop_request/`, la búsqueda por teléfono sigue abierta), `get_touroku_data.php` (estadísticas; ningún archivo del repo la llama: ¿sistema externo?), y revisar una por una las restantes. **Paso 26c**: las 12 tareas del cron (`setCustomer*Rank`, `sendPointShikkou*`, `setPaidMemberPoint`, `sendFollowPush`, `mail_sche_send`…) se podían disparar desde el navegador sin login: ahora solo por CLI (`PHP_SAPI`); 18 endpoints AJAX del personal (permisos, reservas, push, fotos) exigen sesión (con sesión responden igual que antes). **Paso 26g**: de las 92 que daban 500 sin sesión, 48 hacían algo antes de fallar: 31 fragmentos solo incluidos → 403 por URL directa; 11 llamados por URL (`register.php` de category11, `sendPosData`, `entry_register`, `change_syukkin*`, `send.php`, `s1.php`…) → sesión; los 2 `mailsoushin.php` (envío masivo de la revista, aceptaba `?sh=&cd=` por web) → solo CLI. Quedan por decidir las APIs que llaman otros servidores (`tool/api/*`, `create_shop.php`, `get_shukei_data*`) y las herramientas de BD de `category0/config` (`db_fix`, `db_export`…) |
| Controles de login que redirigen sin `exit` (Execution After Redirect): el navegador obedece el 302, pero PHP sigue y entrega la página (`tools/scripts/redirige-sin-exit.php`) | 19 controles en 19 archivos; 81 páginas entregaban su cuerpo sin sesión | ✅ Paso 30: incluye `hp/user/inc/session_login.php` y `hp/staff/inc/session_login.php`, de los que dependen las áreas de cliente y de personal de `hp/`, `system/setting/config.php` y comprobaciones de tienda en `*_update.php` de `category7`. Medido sin cookies: 302 con cuerpo 81 → 2 (los 2 son del flujo público `hp/m/reserve`); con sesión de cliente, 8 páginas siguen en 200. `config.php` parecía ejecución de código (escribe `inc/db.php` con el POST), pero su regex busca `system_domain="…"` y hoy no coincide: no se demostró RCE |
| XSS reflejado indirecto (`$x = $_REQUEST['a']`, `get_form()`, `$data_array`, cadenas de URL o HTML armadas con la petición) | 47 páginas y 304 apariciones en `system/`, `admin/` y `signage/` | ✅ Paso 36: `tools/scripts/xss-reflection-scan.php` envía una marca en cada parámetro de 748 páginas, con sesión, y cuenta si vuelve sin escapar. `get_form()` escapa para SQL, no para HTML. De 304 apariciones a 6, y esas 5 páginas pasaron a 0 tras la última corrección. **Falta**: repetir el escaneo completo tras esa última corrección; `hp/`, `touchcation/`, `appview/`, `api/` y `digitalsignage/` sin escanear; 302 páginas que escriben se saltaron. La ejecución real de un script en navegador no se demostró: se mide que la marca vuelve sin escapar |
| Salidas de depuración | `print_r($_SESSION)` en la pantalla de caja, `var_dump` en `admin/api.php` y `appview/ms_paid_member_test.php` | ✅ Paso 36: comentado y 403 por web. En Docker no se vio ningún secreto en esas páginas (0 de 16) |
| XSS almacenado (datos de la BD sin escapar) | ~4,800 ecos sin `h()` según Semgrep | Pendiente: primero averiguar qué campos llevan HTML legítimo |
| Shell con datos de usuario en `system/`/`admin/` | ~550 (Semgrep) | ✅ Paso 23: `escapeshellarg()` en 1,106 variables de 109 archivos (`tools/scripts/shell-escapar.php`, sigue las comillas del shell); los registros `echo -e … >>` pasan a `file_put_contents()`; mensajes de commit y textos de `convert` escapados enteros. `admin/list/git.php` metía `$_REQUEST` directo en `git`. `LC_CTYPE` fijado en UTF-8 (con "C", `escapeshellarg()` borra el japonés). Probado: ImageMagick con ruta con espacios y texto japonés, inyección `; touch` no se ejecuta |
| Cookie "recordarme" con la contraseña en claro (90 días) | `system/login.php` | ✅ Paso 27: solo se recuerda el usuario (HttpOnly, SameSite=Lax, Secure en HTTPS); la cookie vieja con la contraseña se borra al entrar; valores escapados. Además el usuario nunca se rellenaba (la cookie se llamaba `admin_login['id']`, con comillas) |
| Token de terminal POS predecible: `system/pos_session.php` acepta `unique_key == md5(unique_id . "key")`, con la palabra fija en el código | 1 archivo | ❓ Decisión: cualquiera puede calcular el token de un `unique_id` conocido; cambiarlo exige coordinar con las tabletas que lo envían. No se reprodujo un ataque completo: el archivo es un fragmento que da 500 pedido solo |
| Contraseñas en texto plano en la BD (42 comparaciones en SQL en 33 archivos; `session.php` las revalida en cada petición; integraciones con `md5(login_pass)` en URLs) | Todo el sistema | ❓ Decisión: migrar a `password_hash()` rompe integraciones externas |
| CSRF | 301 archivos que escriben, detrás de `system/session.php` o `admin/inc/common.php`; 20 más de socio y personal en `hp/`, solo POST | ✅ Paso 39: guarda central `inc/csrf.php` con `Sec-Fetch-Site`, `Origin` y `Referer`, sin tocar los 704 archivos con formularios ni a los clientes sin navegador, y cookie `SameSite=Lax` en `system/`. Ataque real en Chromium y Firefox, con línea base. **Falta**: `pos_session.php` (decisión del token POS), sesiones de quiosco en `touchcation/`, GET en la zona de socios |
| Contraseñas, tokens y claves en el código | 12 secretos, 89 archivos | ✅ Paso 19 (a `.env`). **Falta rotarlos en producción** (siguen en el historial de git). El webhook de Slack de `src/Util/Slack.php`, que el paso 19 no detectó, pasó a `.env` (`SLACK_WEBHOOK_URL`) el 2026-11-17; también hay que revocarlo. El 2026-11-18, las dos constantes de clase que el paso 19 tampoco buscaba (`const`, no `define`): `MOBILESHOP_EXTLINK_PASS` y `MAIL_ADD_POP3_PASS`. Ya no queda ninguna clave activa en el código. Por decisión de Antony (2026-11-18), los valores van en `.env.example`, que sí se versiona, para que el equipo los reciba con el repositorio |
| Datos personales en git (backup, 24 CSV de exportación) | — | **Riesgo aceptado** por decisión (2026-10-30) |

### B1. Documentos relacionados

- [BROKEN-FILES.md](BROKEN-FILES.md): archivos rotos o que no funcionan, para revisar al final.
- [INTEGRATIONS.md](INTEGRATIONS.md): dispositivos y servidores que dependen de este código desde fuera del repo.
- [CLAUDE.md](../CLAUDE.md): reglas del proyecto. Paso 34: el código nuevo va en inglés; `tools/scripts/check-english.php` lo comprueba como pre-commit.

### B2. Mapa de páginas de `system/` sin control de sesión (2026-10-31)

Medido con `tools/scripts/sin-sesion.php` (lee el código) y `tools/scripts/sondeo-sin-sesion.php` (petición real por GET, sin cookies ni parámetros; con parámetros podría responder otra cosa). De 123 candidatas, 58 ya tienen guarda (`SCRIPT_FILENAME` o `PHP_SAPI`); quedan 65 en la tabla.

> **Corrección (paso 32):** `sin-sesion.php`, `clasificar-uso.php` y `exigir-sesion.php` contaban como protegido un `require` de `session.php` **comentado**. Al ignorar los comentarios aparecieron **13 páginas más**, ya corregidas y fuera de la tabla. La peor: `category3/dm/aterna_a4_12.php`, que sin login ni parámetros entregaba un PDF de 1,091,726 bytes con etiquetas de `tb_customer` y aceptaba SQL arbitrario en `wherestr`. Ahora exige sesión y toma la cláusula de `$_SESSION['wherestr']`. 6 pasan a exigir sesión, 6 a 403 por acceso directo (fragmentos, y `create_immovables.php`, sin llamadores y con sus `require` dentro de un comentario), y las 2 copias de `aterna_a4_12.php` que consultan `kaiin_master` también exigen sesión.
>
> **Exportaciones CSV descargables sin login (paso 32b):** los generadores dejan los archivos en `csv_download/csv/`, sin control de acceso y con la hora Unix como nombre. Medido antes de cerrar: un CSV de clientes de 6,841,868 bytes respondía HTTP 200 sin cookies. Ese archivo lo generó el propio sondeo anónimo, antes de exigir sesión a `csv_file_out.php`. Ahora un `.htaccess` con `Require all denied` en las 5 carpetas y en `public_html/csv/` (exportaciones de `admin/`) da 403; PHP las sigue leyendo y escribiendo. **En producción puede haber exportaciones antiguas que hasta hoy se podían descargar: conviene revisar esas carpetas y los logs de acceso.**
>
> **Bug previo, no corregido:** `category3/process-sortable.php` exige `./inc/kihon.php`, que no existe en esa carpeta: da 500 con y sin sesión, y `coupon_list.php` y `coupon_bg_list.php` lo llaman para ordenar.

| Resultado sin cookies | Páginas | Qué son | Decisión |
|---|---:|---|---|
| 500 (no llegan a responder) | 34 | Fragmentos que necesitan a quien los incluye (`*_frm.php`, `ajax_*` de category9/11, etc.) | Pendiente confirmar que ninguno actúa antes de fallar (el paso 26g lo hizo con los anteriores) |
| 200 vacío | 14 | 8 de `tool/api/*` (sin `key` válida salen sin nada) y 6 archivos de funciones | `tool/api`: ver abajo |
| 200 con contenido | 11 | `zip.php` ×5 (buscador de códigos postales, dato público) · `get_touroku_data.php` y `get_shukei_data*.php` (estadísticas del negocio) · `desktop_request/*` (CTI) | ❓ Estadísticas y CTI: decisión del dueño, ningún archivo del repo las llama |
| 302 | 2 | `setting/config.php` (corregido en el paso 30) y `customer_rank_frm_register.php` (se corta por el control de datos) | — |
| No sondeadas | 2 | `ajax_postFacebook.php`, `ajax_postTwitter.php`: pueden publicar | ❓ Revisar leyendo el código |
| Generadores de datos sin login | 2 | `create_shop.php`, `create_customer.php`: una petición anónima sobrescribe clientes y pone `login_pass = '0000'`. Ver `INTEGRATIONS.md` | ❓ Decisión de Antony. **Nunca sondearlas** |

Pendientes de decisión relacionados:
- **`tool/api/checkKey.php`**: ✅ paso 35. La "clave" era `md5("VIZAN" . HTTP_HOST . "GLORY")`, calculable por cualquiera: `searchCustomerList.php?keyword=1` entregaba 195,283 bytes de clientes sin login. Ahora es `CRM_API_KEY` en `.env`. **Al desplegar hay que seguir el plan de [INTEGRATIONS.md](INTEGRATIONS.md)**: los servidores que llaman a la API siguen enviando la clave antigua.
- **Token POS (`pos_session.php`)**: no se toca hasta saber qué dispositivos lo usan; preguntas y propuesta en [INTEGRATIONS.md](INTEGRATIONS.md).
- **`hp/yoyaku_timeline_azp` y `_spp`**: tienen el control de login comentado a propósito (reservas públicas).

### C. Lista de tareas pendientes, en orden recomendado

1. **A5: pruebas funcionales por módulo**, con datos de prueba: clientes, reservas, puntos/sellos, correo, POS, señalización. Es lo que más falta y no se puede automatizar del todo
2. ~~A6: dependencias (IMAP, Mail_mimeDecode, FPDF)~~ ✅ paso 15. Falta la BD de `admin/` para poder probarlo
3. **A6: staging**: desplegar en un servidor de prueba con PHP 8.5, ejecutar el SQL de Doctrine y repetir el smoke y el barrido allí
4. **B: XSS** con un helper `h()`, empezando por las zonas públicas
5. **B: SQLi restante y shell interno**
6. **A3: deprecations** para PHP 9 (conexión implícita `pg_*`, ~47k warnings de variables no definidas): no bloquean 8.5

### D. Dependencias de terceros

Inventario completo, prioridades y estado de cada librería copiada en el repo (mPDF, Services_JSON, Qdmail, jQuery, Bootstrap, CKEditor…): **[DEPENDENCIES.md](DEPENDENCIES.md)**.

---
## 1. Diagnóstico (medido el 2026-10-29)

### Stack real

| Componente | Versión | Nota |
|---|---|---|
| PHP en Docker (`app`) | **8.5** (`php:8.5-fpm-trixie`) | Tag flotante: la versión de parche depende de cuándo se construyó la imagen |
| PHP local (Windows) | 8.2.12 | Solo para correr herramientas. Vulnerable a PHP2Shell, no se usa en producción |
| Servidor web | Apache (`debian:trixie-slim`) → PHP-FPM | `proxy_fcgi`, sin `mod_php` |
| Base de datos | PostgreSQL 18 | Backup: `db/crm2019.backup` |
| Arquitectura | **PHP procedural** | Páginas `.php` sueltas + `require_once` + `pg_query`. Sin framework |
| Código "moderno" | `src/` (21 clases, PSR-4 `CrmSystem\`) + `tests/` (14) | Usa Eloquent (`illuminate/database`), Dotenv, JWT |

### Tamaño

| | |
|---|---|
| Archivos PHP en `public_html/` | 2,568 (`system/` 1,369 · `hp/` 387 · `touchcation/` 305 · `admin/` 194 · `signage/` 182) |
| Archivos PHP propios (sin librerías ni uploads) | **2,449** |
| Líneas PHP totales | ~880,000 (incluye librerías copiadas: mPDF ×2, TCPDF, PHPMailer, JSON.php) |
| Codificación | UTF-8: 2,534 · UTF-8 con BOM: 28 · Shift_JIS: 2 · EUC-JP: 2 |

### Dependencias Composer: no instalan en PHP 8.5

Todo el `composer.lock` raíz exige **PHP ^7** (`illuminate/database` 7.15, `doctrine/migrations` 2.2, `phpunit` 8.5, `symfony/console` 5.0, `phpstan` 0.12…). `composer install` falla en el contenedor.

Impacto: `public_html/inc/db.php` hace `require vendor/autoload.php` para Eloquent y Dotenv. Los 12 archivos que cargan `vendor/` (incluidos `api/auth.php` y `api/mobile_shop/*`) dependen de esto.

### Métricas de deuda técnica (línea base)

Generadas con `composer metricas` (excluye librerías de terceros).

| Indicador | Archivos | Ocurrencias | Objetivo |
|---|---:|---:|---|
| **Rompe en PHP 8.5.12** | | | |
| Error de sintaxis (no compila en PHP 8.5.12) | 8 | 14 | 0 |
| `ereg`/`split` (removido en 7.0) | 27 | 96 | 0 |
| `mysql_*` (removido en 7.0) | 1 | 11 | 0 |
| `utf8_encode/decode` (deprecado 8.2) | 1 | 2 | 0 |
| `"${var}"` (deprecado 8.2) | 1 | 4 | 0 |
| **Seguridad** | | | |
| SQL con `$variable` interpolada (riesgo SQLi) | 1,380 | 4,925 | ↓ |
| `pg_query` crudo | 1,696 | 9,193 | ↓ |
| `pg_query_params` / `pg_prepare` | 11 | 16 | ↑ |
| `echo`/`<?=` directo de `$_GET/$_POST` (XSS) | 531 | 2,221 | 0 |
| `htmlspecialchars` / `htmlentities` | 65 | 110 | ↑ |
| `exec`/`system`/`shell_exec`… | 136 | 574 | ↓ |
| `unserialize(` | 46 | 89 | ↓ |
| `eval($…)` | 6 | 6 | 0 |
| `md5(` | 120 | 245 | revisar |
| `password_hash` / `password_verify` | 0 | 0 | ↑ |
| Protección CSRF | 0 | Guarda central (paso 39) | ↑ |
| `@` supresión de errores | 1,131 | 5,623 | ↓ |
| **Estructura** | | | |
| Ruta absoluta `/home/system/` | 1,475 | 1,624 | 1 (config) |
| `session_start(` | 221 | 226 | 1 (bootstrap) |

> Las cifras se toman con patrones de texto: sirven para **medir tendencia**, no son una auditoría exacta.

### Archivos rotos por el reemplazo con regex del README — ✅ corregidos el 2026-10-29

El "refactor" del README raíz (poner comillas a las claves de array con `\[([^0-9<\$'"\]].[^\]'"]*)\]` → `['$1']`) se ejecutó en una shell que interpretó `$array`, `$_REQUEST`, `$row`… como variables vacías. Dejó dos tipos de daño:

1. **Errores de sintaxis** (`['clave]']`): la página no carga. PHPStan no completaba el análisis.
2. **Variable borrada** (`<?=['name']?>` en vez de `<?=$array['name']?>`): compila, pero imprime `Array`, compara un array con `''` (la validación nunca se ejecuta) o lanza `TypeError` en PHP 8.

Aunque las 3 de la tabla 1b no venían del regex, también eran fatales y aparecieron cuando PHPStan pudo completar el análisis.

**1a. Error de sintaxis**

| Archivo | Línea | Problema |
|---|---|---|
| [system/category3/inc/customer_detail.php](../public_html/system/category3/inc/customer_detail.php#L225) | 225 | `['tb_shop_cd]']` (49 archivos lo referencian) |
| [system/category3/visit/customer_detail.php](../public_html/system/category3/visit/customer_detail.php#L219) | 219 | `['tb_shop_cd]']` |
| [system/category4/user_hp/topics.php](../public_html/system/category4/user_hp/topics.php#L182) | 182 | `['tb_shop_cd]']` |
| [system/category3/visit/visit_stats.php](../public_html/system/category3/visit/visit_stats.php#L734) | 734 | `['user_cd]?>']` (dentro de un comentario HTML, PHP igual lo interpreta) |
| [system/category3/visit/new_visit_stats.php](../public_html/system/category3/visit/new_visit_stats.php#L717) | 717 | ídem |
| [system/category3/visit/stats_test.php](../public_html/system/category3/visit/stats_test.php#L633) | 633 | ídem (archivo de prueba, sin referencias) |
| [system/category3/visit/stats_test2.php](../public_html/system/category3/visit/stats_test2.php#L642) | 642 | ídem (archivo de prueba, sin referencias) |
| [system/category3/dm/japanese.php](../public_html/system/category3/dm/japanese.php#L72) | 72+ | `$s{$i}` → `$s[$i]` (sintaxis eliminada en PHP 8.5.12) |

**1b. Errores fatales de compilación (no venían del regex)**

| Archivo | Problema | Corrección |
|---|---|---|
| [system/category11/placeview/index.php](../public_html/system/category11/placeview/index.php#L106) | 18 líneas `['BOX_FLG_X'] = …` sin variable | `$boxColorClass[BOX_FLG_X]` / `$boxComment[BOX_FLG_X]` (se leen en las líneas 215 y 217) |
| [hp/user/sc/kujibiki/kujibiki_coupon_control.php](../public_html/hp/user/sc/kujibiki/kujibiki_coupon_control.php#L39) | `break` fuera de un bucle después de `header()` | `exit;` (sin él, el código seguía ejecutándose después de la redirección) |
| [hp/yoyaku_timeline_azp/complete.php](../public_html/hp/yoyaku_timeline_azp/complete.php#L171) | `break` tras ROLLBACK sin bucle (el bucle original se perdió) | Restaurado como `do { … } while (false);` |
| [system/category11/sumup/sumup_graph.php](../public_html/system/category11/sumup/sumup_graph.php#L299) | `['count_regi_receipt_…'][$m] = …` sin variable (escritura y lectura en la gráfica) | `$graphMonthArray[…]` |

**2. Variable borrada (compilaban, pero funcionaban mal)**

| Archivo | Variable restaurada | Síntoma que tenía |
|---|---|---|
| [system/category3/inc/ajax_search_kaiin.php](../public_html/system/category3/inc/ajax_search_kaiin.php#L84) | `$array` | La búsqueda de socios mostraba `Array` en cada columna |
| [system/category7/felica/ajax_search_kaiin.php](../public_html/system/category7/felica/ajax_search_kaiin.php#L40) | `$array` | ídem (FeliCa) |
| [system/category3/reservation/ajax_category.php](../public_html/system/category3/reservation/ajax_category.php#L16), [ajax_staff.php](../public_html/system/category3/reservation/ajax_staff.php#L18) | `$array` | Los selects de reservas mostraban `Array` |
| [system/category4/recruit/ajax_category.php](../public_html/system/category4/recruit/ajax_category.php#L17) | `$array` | ídem (reclutamiento) |
| `recruit_new.php`, `recruit_update.php` (cat. 4 y 6), `company_update.php` (cat. 4 y 6) | `$row` | Select de puesto con `value="{['cd']}"` literal y sin selección |
| [system/category7/kiosk/movie_program.php](../public_html/system/category7/kiosk/movie_program.php#L438) | `$_REQUEST` | La validación de campos obligatorios nunca se ejecutaba. `pic_extension` ahora se escapa con `htmlspecialchars` |
| [hp/user/sc/weather/index.php](../public_html/hp/user/sc/weather/index.php#L181) | `$area` | `urlencode(array)` → `TypeError` en PHP 8.5.12 (página pública) |

Verificado: `php -l` sobre los 2,449 archivos propios → 0 errores. Rector ya analiza todo sin errores de parseo. PHPStan completa el análisis.

### Hallazgos críticos de seguridad

| # | Hallazgo | Dónde |
|---|---|---|
| S1 | Contraseñas guardadas y comparadas **en texto plano**. No existe `password_hash` en el proyecto | [login.php:164](../public_html/system/login.php#L164) |
| S2 | La contraseña se guarda **en una cookie durante 90 días** | [login.php:197](../public_html/system/login.php#L197) |
| S3 | Contraseñas de soporte fijas (`[.env:RELATION_LOGIN_PASS]`, `[.env:SUPPORT_MASTER_PASS]`) validadas contra una API externa por **HTTP sin TLS, con la contraseña en la URL** | [login.php:105-140](../public_html/system/login.php#L105-L140) |
| S4 | Cada carga de la página de login ejecuta `system("find … -exec rm {}")` | [login.php:47](../public_html/system/login.php#L47) |
| S5 | `security.php` público: XSS reflejado (`<?=$domain?>` sobre `$_REQUEST`). `test.php` también en la raíz web | [public_html/security.php](../public_html/security.php) |
| S6 | Secretos y datos en git: `my.env`, `docker/my.key`, `db/crm2019.backup` (datos de clientes), `*.key` | raíz, `docker/`, `db/` |
| S7 | `exp_cmd/` y `cron/` (scripts de mantenimiento) dentro de la raíz web, sin autenticación | `public_html/exp_cmd`, `public_html/cron` |
| S8 | Sin CSRF en formularios; cookies de sesión sin `httponly`/`samesite`; `expose_php=On`, `display_errors=On` | [docker/php.ini](../docker/php.ini) |
| S9 | PHP2Shell (CVE-2026-109618/109619, CVSS 9.2): corregido en **8.5.12**. Verificar la imagen | `composer check-php` |
| S10 | **Contraseña de la BD del POS escrita en el código** (`pg_connect(... password=...)`) | [complete.php:132](../public_html/hp/yoyaku_timeline_azp/complete.php#L132) |
| S11 | Página pública de reservas con SQL armado directamente con `$_REQUEST` (`staff`, `name`, `tel`, `customer`…) | [complete.php:392-560](../public_html/hp/yoyaku_timeline_azp/complete.php#L392) |

### Otros hallazgos (requieren decisión o verificación)

| # | Hallazgo | Acción |
|---|---|---|
| D1 | `BOX_FLG_COMPLETE` se define 3 veces (8, 9, 10) y `BOX_FLG_CALLING`/`BOX_FLG_PAYING` no existen. Hoy vale **"8"**. Corregirlo a "10" cambiaría el significado de los `status_flg` ya guardados en la BD | **Decisión de negocio**. Revisar los datos de `tb_information_place.status_flg` antes de tocar [inc.php:67-69](../public_html/system/category11/inc/inc.php#L67-L69) |
| D2 | `japanese.php` hace `require('fpdf.php')`, pero FPDF no está en el repo | Verificar el `include_path` del servidor. Si no está, instalar `setasign/fpdf` por Composer |
| D3 | `continue` dentro de `switch` (equivale a `break`) en `admin/api/class/Customer.php:1707,2025` y `appview/class/Customer.php:1720,2028` | Revisar si la intención era `continue 2` |
| D4 | Regex inválida en PCRE2 (`\U`, `\L`…) | [system_file_export.php:95-96](../public_html/system/category0/config/system_file_export.php#L95) |
| D5 | Cast `(double)` deprecado en PHP 8.5 | [mm_add2.php:23](../public_html/system/category3/mail_mag/mm_add2.php#L23) → `(float)` |
| D6 | `updateSQL()` se llama con menos argumentos de los requeridos (122 llamadas) | Ver si hay varias definiciones con firmas distintas. En PHP 8, faltar argumentos es un error fatal |

### Problemas de entorno (Docker)

| # | Problema |
|---|---|
| E1 | El código usa `/home/system/public_html/…` (1,475 archivos), pero Docker monta el código en `/var/www/html` |
| E2 | `inc/db.php` busca `vendor/` y `.env` en la raíz del proyecto, que **no está montada** en el contenedor |
| E3 | `.htaccess` usa `php_value`/`php_flag` (`admin/`, …). Solo funcionan con `mod_php`; con PHP-FPM, Apache devuelve **500** en esas carpetas. Hay que pasarlos a `.user.ini` |
| E4 | [docker/README.md](../docker/README.md) describe otro proyecto (Laravel, `artisan`, `/admin/login`). Está desactualizado |

---

## 2. Principios

1. **Incremental, nunca reescritura total.** El sistema sigue funcionando después de cada commit.
2. **Seguir el patrón existente (procedural)** hasta decidir explícitamente la Fase 5.
3. **Rector solo con reglas explícitas y por carpeta.** Nunca `withPhpSets()` sobre todo el árbol de una vez.
4. **Medir antes y después** de cada fase (`composer metricas`, baseline de PHPStan).
5. **No editar librerías de terceros** (mPDF, TCPDF, PHPMailer, JSON.php): se reemplazan por Composer.
6. **Prioridad por exposición:** primero lo accesible sin login (login, `api/`, `hp/`, `reserve/`, `r/`), después el panel interno.

---

## 3. Fases

### Fase 0 — Red de seguridad (antes de tocar código)

- [x] Rama de trabajo: `git checkout -b refactor/fase-0`
- [x] Arreglar los archivos rotos por el regex y los fatales de compilación (24 archivos, ver tablas 1a/1b/2)
- [x] Baseline de PHPStan: **709 errores congelados**. `composer phpstan` en verde. Qdmail excluido del análisis; stubs de FPDF/Eloquent en `tools/stubs/`
- [x] Commit de lo anterior (`a70f54d`)
- [x] **Probar en el navegador** las pantallas corregidas: búsqueda de socios, reservas (`hp/yoyaku_timeline_azp`), kiosk/movie_program, placeview, sumup_graph, reclutamiento. **Paso 47**: 12 pantallas en Chromium; `karute_customer_search` (2 copias) no cargaba el plugin `myFlexSelect`, ya en `master`: corregido; `sumup_graph` da 500 porque `category11` no tiene tablas ni en el esquema de producción
- [x] Crear `docker/.env` y `.env` raíz (desde `my.env`; ambos en `.gitignore`)
- [x] Resolver E1 y E2 (paso 18, definitivo): el código usa **rutas dinámicas** y Docker vuelve a la estructura original (`DocumentRoot /var/www/html`, `working_dir /var/www`). Solo se agrega el montaje de la raíz del repo en `/var/www` para que `vendor/`, `src/`, `.env` y `tools/` existan junto a `html/`. (Antes, como solución temporal, se montaba todo en `/home/system`)
- [x] Resolver E3: `php_value`/`php_flag` envueltos en `<IfModule mod_php.c>`/`<IfModule mod_php7.c>` (siguen valiendo con mod_php) + `.user.ini` equivalente para PHP-FPM (`admin/`, `admin/page/`, `signage/`). Apache bloquea la descarga de `.user.ini`
- [x] **Adelantado de la Fase 2B (bloqueante):** sin `vendor/` instalable en PHP 8.5 ninguna página carga, porque `inc/db.php` usa Eloquent y Dotenv
- [x] Restaurar la BD (175 tablas, PostgreSQL 18). También `crm2019_test` para PHPUnit:
      `docker compose exec -T db pg_restore -U $POSTGRES_USER -d $POSTGRES_DB --no-owner < ../db/crm2019.backup`
- [x] Verificar el login en `http://localhost/system/login.php` con el usuario `master`
- [x] **Smoke test automático** (`tools/scripts/smoke.php`): login + recorrido de 322 pantallas de `/system/` siguiendo enlaces (sin acciones destructivas). Atribuye cada error del log de PHP a su página. Línea base: **15 páginas con 500**. Pendiente además: checklist manual de definir 15–20 pantallas críticas (login, alta de cliente, venta, puntos, reservas, API móvil…) y recorrerlas. Se repite después de cada fase
- [x] Línea base local: `composer metricas`, `composer phpstan:baseline`
- [x] `composer check-php`: contenedor en **PHP 8.5.12** (parche PHP2Shell)
- [ ] Línea base con Docker: `composer psalm:taint`, `composer semgrep`

**Hecho cuando:** el sistema corre en Docker PHP 8.5 con datos reales y el checklist de humo está documentado.

### Fase 1 — Seguridad urgente (días)

> **Decisión del 2026-10-30 (Antony): por ahora NO se tocan** D1 (`BOX_FLG_COMPLETE` se queda en "8"), S7 (`cron/` sin protección), S6 (secretos y backup en git) ni S5 (`security.php`/`test.php` en la raíz web). Quedan como **riesgos aceptados** y se retoman cuando se decida.

- [x] **S9:** imagen reconstruida con `--pull` → PHP 8.5.12. Se mantiene el tag `8.5-fpm-trixie` (recibe parches) y `composer check-php` exige el mínimo. Descartado fijar a `php:8.5.12-fpm-trixie` o superior → `docker compose build --pull app` → `composer check-php` en verde
- [ ] **S6:** sacar `my.env`, `docker/my.key` y `db/crm2019.backup` del repo (`git rm --cached` + `.gitignore`) y **rotar** las credenciales y la clave. Limpiar el historial (`git filter-repo`) solo con acuerdo del equipo
- [ ] **S5:** retirar `security.php` y `test.php` de la raíz web (mover, no borrar, con confirmación)
- [ ] **S7 (decisión):** `cron/regenerateCronJob.php` **se llama por HTTP desde otro servidor** ([db_export.php:265](../public_html/system/category0/config/db_export.php#L265)), así que no se puede bloquear sin más: restringir por IP o por token. `exp_cmd/verUp/*` (scripts que alteran la BD) no tiene referencias por HTTP: bloquear si se ejecutan por CLI
- [ ] **S4:** mover la limpieza de `uploads/` de `login.php` a un cron
- [ ] **S8:** `php.ini` de producción: `expose_php=Off`, `display_errors=Off`, `log_errors=On`, `session.cookie_httponly=1`, `session.cookie_secure=1`, `session.cookie_samesite=Lax`
- [ ] Semgrep y Psalm: revisar hallazgos **críticos** en login, `api/` y zonas públicas

### Fase 2 — Compatibilidad total con PHP 8.5 (1–2 semanas)

**2A. Funciones removidas con Rector** (`tools/rector.php` ya configurado solo con estas reglas)

```bash
cd tools
vendor/bin/rector process ../public_html/<carpeta> --config=rector.php --dry-run   # revisar
vendor/bin/rector process ../public_html/<carpeta> --config=rector.php            # aplicar
```

Dry-run del 2026-10-29: Rector modifica **20 archivos**. Lo que no cubre se hace a mano.

- [x] `ereg`/`split` → `preg_*` (18 archivos con Rector; el resto estaba en comentarios, verificado con el tokenizer de PHP). **Rector convirtió mal** `hp/mailsyori/mm_sche.php` y `mm_send.php`: `preg_split` sin delimitadores con boundary MIME dinámico, `[[:digit:]]`→`[d]`, `[[:space:]]`→`[013\s]` y `m` en un patrón con `$`. Corregido a mano y probado con 8 casos (split MIME, teléfonos, appledouble, punto final SMTP)
- [x] `utf8_encode/decode` → `mb_convert_encoding` (Rector, 1 archivo)
- [x] `"${var}"` → `"{$var}"` (Rector, 1 archivo)
- [x] `get/set_magic_quotes_runtime()` (eliminadas en PHP 8.5.12, fatal al imprimir etiquetas DM) quitadas de `japanese.php`
- [x] Alias `pg_*` deprecados (`pg_freeresult` ×260, `pg_result` ×3) → `pg_free_result`/`pg_fetch_result` con `RenameFunctionRector` (120 archivos, cambio mecánico)
- [ ] **(decisión)** `mysql_*` solo en `admin/list/rss_list.php`: lee una BD MySQL externa (`FEEDENTRYCONTENTS`), no hay `mysql_connect` en todo el proyecto y nadie enlaza la página. Probablemente es código muerto: confirmar antes de migrar a PDO o eliminar

**2B. Dependencias Composer raíz**

- [x] Actualizar a versiones que soporten PHP 8.5 (instalado: illuminate/database 13.39, php-jwt 7.2, doctrine/migrations 3.9, phpdotenv 5.8, PHPUnit 12.5). **valitron fijado en 1.4.7**: la 1.4.11 añade un segundo mensaje de error en campos vacíos y cambia la respuesta de la API móvil: `illuminate/database`, `vlucas/phpdotenv` (4 → 5), `firebase/php-jwt` (5 → actual), `doctrine/migrations` (2 → 3), `vlucas/valitron`
  - Dotenv 5: `createImmutable` → `createUnsafeImmutable` (7 sitios), porque el código lee con `getenv()` y v5 ya no llama a `putenv()` por defecto
  - JWT: `JWT::decode($jwt, new Key($public_key, 'RS256'))` en [JwtAuth.php](../src/Util/JwtAuth.php)
  - Doctrine 3: nuevo formato en [migrations.php](../migrations.php) y [cli-config.php](../cli-config.php). **Paso único por entorno (incluida producción), antes del primer `migrate`:**
    ```sql
    -- v3 guarda la clase completa; sin esto volvería a ejecutar TODAS las migraciones
    ALTER TABLE doctrine_migration_versions ALTER COLUMN version TYPE varchar(191);
    UPDATE doctrine_migration_versions SET version = 'CrmSystem\Migrations\Version' || version WHERE version NOT LIKE 'CrmSystem%';
    ```
    y después `vendor/bin/doctrine-migrations sync-metadata-storage` + `status` (deben aparecer todas como ejecutadas)
- [x] Mover `phpunit` a `require-dev` y quitar `phpstan` del raíz (vive en `tools/`)
- [x] Añadir `"php": "^8.5"` en `require`
- [x] `composer install` en el contenedor sin `--ignore-platform-reqs` (sin avisos de seguridad)
- [x] Tests: **49/53 OK**, 1 omitido (host externo). Los 3 que fallan llaman a APIs HTTP externas o a `http://localhost` desde el contenedor (`JwtAuthTest::testAuth`, `CustomerForMobileShopTest::testFetchToken/testFetchPaidMemberFlg`). JWT verificado a mano de extremo a extremo (emite, valida y rechaza firma alterada). `DbConnectTest` actualizado: desde PHP 8.1 `pg_connect()` devuelve `PgSql\Connection`
- [x] Doctrine 3 verificado en local: tras el SQL de conversión, `status` = 15 ejecutadas / 0 nuevas, y `migrate --dry-run` = sin cambios. Sin conversión, v3 se niega a ejecutar (MetadataStorageError)

**2C. Errores y deprecations**

> **Versión de origen (corregido):** el `composer.lock` original exige **PHP ^7.2.5** (Eloquent 7.15, 2020) e `inc/db.php` lo carga en cada página, así que producción corría muy probablemente **PHP 7.2–7.4**. Pero gran parte del código se escribió para **PHP 5** (`ereg`, `mktime` con `is_dst`, `$x = ""; $x[] =`): esas pantallas probablemente **ya fallaban en producción** (fatal desde 7.0/7.1, o fechas de 1970 por `mktime()` devolviendo null). **Pendiente: confirmar con `php -v` en el servidor.** (El commit del paso 5c dice "PHP 5.x": queda corregido aquí.)

Errores de PHP 8.5.12 que el smoke test sacó a la luz, corregidos en orden (cada uno con su commit):

| Paso | Causa | Alcance | Corrección (mismo resultado que PHP 5) |
|---|---|---|---|
| 5a | Constructor con el nombre de la clase (PHP 4): PHP 8 no lo ejecuta en `new` y **falla en silencio** | 10 clases + Services_JSON (`ReceiptMailDecoder`, `orClass_shopping`, Qdmail…) | `Php4ConstructorRector` |
| 5a | `$str{$i}` en Services_JSON (3 copias) | 120 offsets | `tools/scripts/llaves-a-corchetes.php` (tokenizer) |
| 5a | `count(null)`, `$where = ""` + `$where[]`, `mktime(date('m,d,y'))`, `pg_fetch_result(..., '', ...)` | 4 archivos | A mano |
| 5b | `@pg_num_rows(false)`: PHP 5 devolvía `null` en silencio y PHP 8 lanza `TypeError`. Tumbaba la página con cualquier consulta fallida | **2,137 llamadas en 737 archivos** | `($r ? @pg_xxx($r, …) : null)` con `tools/scripts/proteger-pg-silenciado.php` |
| 5c | `mktime()` con `is_dst` (7.º argumento) | **220 llamadas** (calendarios, horarios, informes, CSV) | Quitado (siempre era 0; Asia/Tokyo no tiene horario de verano) |
| 5c | `pg_fetch_result($r, '', 'campo')` | 24 llamadas | Fila `0` |
| 5d | `$x = ""` y luego `$x["clave"]` / `$x[] =` | 7 archivos (asistencia, FeliCa, SNS) | `[]` |
| 5d | `staff_shift_kind.php` cargaba `staff_group_frm.php` (de otra carpeta) | 1 | Su formulario real `staff_shift_kind_frm.php` (bug previo) |

Smoke: 15 páginas con error en 322 → **0 en 2,000 páginas**.

Pendiente en 2C:

- [ ] `composer phpstan` sin errores nuevos. Bajar el baseline carpeta por carpeta
- [ ] Recorrer el checklist con `error_reporting=E_ALL` y revisar el log: en PHP 8.5.12, `@` ya no oculta errores fatales
- [x] BOM quitado de los 28 archivos (paso 8; `tools/scripts/quitar-bom.php`). En Docker no se notaba por `output_buffering=4096`, pero sin buffer rompe `logout.php` y los redirects; `zip.php` (AJAX) devolvía 3 bytes extra
- [ ] **No convertir a ciegas** los 4 archivos Shift_JIS/EUC-JP: `touchcation/numbered_ticket/print/print.php` imprime tickets y probablemente espera Shift_JIS. Revisar uno a uno
- [ ] `Automatic fetching of PostgreSQL connection` (544 llamadas en 348 archivos): **no es mecánico**. 35 archivos abren otra conexión (POS) y PHP usa "la última abierta". Requiere analizar el alcance de cada caso; no rompe en 8.5

**Barrido de ejecución (2026-10-30):** el smoke solo ejecutaba como página principal 128 de 2,449 archivos (5%). `tools/scripts/barrido.php` pide **los 2,257 archivos** (todo menos `admin/` y `exp_cmd/verUp/`) con sesión de staff, dentro de Docker con **red interna sin internet** (`docker/docker-compose.barrido.yml`), y atribuye cada fatal del log a su archivo. Después se restaura la BD del backup.

- Primer barrido: 547 archivos con fatal, 404 causas. Casi todo son **artefactos**: 646 archivos son fragmentos (`inc/footer.php`, `session.php`…) que fallan al pedirlos solos (`emoji()` o `$connect` no definidos), y páginas pedidas sin sus parámetros (`pg_*()` sobre `false`/`null`: 358 páginas)
- **Paso 14 — incompatibilidades reales de PHP 8.5.12 encontradas por el barrido:**
  - 69 claves `$a[clave]` y 6 `define(NOMBRE, …)` sin comillas → "Undefined constant" (fatal en 8). Incluye cupones y sellos de `hp/user` (área de clientes) y `card_list.php`. `tools/scripts/constantes-sueltas.php` (tokenizer; no toca `"…$a[clave]…"`, que es válido)
  - 4 `<?php//` sin espacio (PHP 8 lo lee como la constante `php`)
  - Qdmail (`inc/qdmail.php` y la copia de test): `smtpObject()` con firma incompatible con la del padre → fatal al cargar la clase
  - 10 `$x = ""` + `$x[] =` (fatal desde 7.1): 3 copias de `search_set2` (+ `!= ""` → `!empty()`) y CSV de clientes y personal
  - División por cero: PHP 8 lanza `DivisionByZeroError` aunque haya `@` (PHP 7 devolvía INF con un aviso). Informes de clientes sin datos (`customer_sumup_list*`: 76 divisiones entre `$one_p`) y, en todo el proyecto, `ceil()`/`floor()` de paginación, columnas, intervalos y porcentajes con divisor variable que puede llegar a 0 (`?kensu=0`, `?limit=0`, porcentaje de puntos 0): `tools/scripts/division-protegida.php` → `($d ? ceil($a / $d) : 0)`, **228 divisiones en 108 archivos**
  - `item_list.php`: `$_SESSION[$menu]` puede ser string
- **Ya rotos antes de la migración** (fallan en cualquier PHP; no se tocan sin decidir): `touchcation/tagsignage` declara una segunda clase `Base`; includes que no existen en el repo (`mailsyori/config.php`, `../db.php`, `inc/head.php`…); `appview/ms_paid_member_regist.php` (página de prueba); `local_settings.php`
- **Dependencias del entorno que faltan** (decisión de infraestructura):
  - `imap_open()` (`hp/mailsyori/mm_err.php`): la extensión IMAP salió del core en PHP 8.4 → PECL `imap` o reescribir
  - `Mail/mimeDecode.php` (PEAR) para `ReceiptMailDecoder` (lectura de correos entrantes) → `pear/mail_mime-decode` por Composer
  - `fpdf.php` (impresión de etiquetas DM) → `setasign/fpdf` por Composer
  - `admin/` usa otra BD (`admin` en `localhost`, contraseña escrita en `admin/inc/db.php`) que no viene en el backup: solo verificado estáticamente
- **Paso 16 — fallos que solo aparecen al enviar formularios (POST):** `tools/scripts/formularios.php` recorre las pantallas, rellena cada `<form>` según el tipo de campo, lo envía y sigue la respuesta (confirmar → registrar) hasta 2 niveles; atribuye cada fatal del log al formulario. Mismo aislamiento que el barrido (red interna + restaurar la BD). Primera pasada: 24 formularios con fatal, 7 causas. Tras las correcciones de abajo: segunda pasada 7 fatales (3 causas), **tercera pasada 239 envíos a 116 scripts, 0 fatales**
  - Funciones internas que en PHP 7 devolvían `null`/`false` con un aviso y en PHP 8.5.12 lanzan `TypeError` (`tools/scripts/tipos-php85.php`, AST): `checkdate()` con texto → `(int)` (54), `implode()` de algo que no es arreglo → `null` (802), `fwrite`/`fclose`/`fgets`/`feof`… sobre un `fopen()` fallido → `false` (162; `feof` → `true` para no colgar `while(!feof())`). 410 archivos
  - Paginación con `?page=0`: `OFFSET` negativo → error SQL y luego `pg_*()` sobre `false`. `$from = max(0, ($page - 1) * $kensu)` en 81 archivos
  - `get_form()` con un arreglo (`campo[]=`) y `date("Y") - $val` con texto en el buscador de personal
  - `${l}` fuera de un string (`hp/m/sale_list.php`, paginación del área de clientes): PHP 7 lo leía como `$l` con un aviso; en PHP 8.5.12 es la constante `l` no definida → fatal. El paso 14 no lo cubría y el barrido no pasó por esa rama (solo con varias páginas de resultados). `tools/scripts/variable-llaves-constante.php` (tokenizer) confirma que era el único caso del proyecto
  - **SQLi en los constructores de búsqueda** (`search_set2.php` y sus 5 copias): arman el WHERE con `foreach ($_REQUEST as $key => $val)` y pegan `$key` como nombre de columna y `$val` sin escapar (escribir «prueba» en un campo numérico daba `column "prueba" does not exist`). `tools/scripts/sql-where-buscador.php` (AST): la clave solo pasa si es un identificador (`^[A-Za-z0-9_]+$`, 7 guardas); los valores entre comillas van con `pg_escape_string($connect, …)` (174) y los numéricos sin comillas con `(int)`/`(float)` (38). A mano: lista de tiendas e `IN` de menús de reserva con `intval`
- **Paso 17 — deprecaciones de PHP 8.5.12 y fatales de la pasada 4 del probador** (lista ampliada a 4,145 pantallas: 353 scripts con formulario, antes 116):
  - Deprecaciones de **compilación** medidas con `php -l` del propio PHP 8.5.12 en los 2,623 archivos: **66 → 0**. `tools/scripts/deprecaciones-php85.php` (AST + tokens): parámetro opcional antes de uno obligatorio (38; PHP ya lo trataba como obligatorio), nulo implícito `Tipo $x = null` → `?Tipo` (9), casts `(integer)`/`(double)` → `(int)`/`(float)` (7). A mano: `case X;` → `case X:` (6) y `$http_response_header` → `http_get_last_response_headers()` (6)
  - **Conexión implícita `pg_*()`** (la más frecuente en ejecución): `tools/scripts/pg-conexion-explicita.php` agrega la conexión solo cuando se puede demostrar cuál es (`$connect` global o `global $connect`, `$this->connect`, la conexión que ya usa el archivo; `$GLOBALS['connect']` para escapes dentro de funciones en las zonas que cargan `inc/db.php`). **528 llamadas**; 16 quedan para revisión manual (archivos que abren otra conexión POS o sin conexión demostrable)
  - **`null` a parámetros de funciones internas** (deprecado desde 8.1, `TypeError` en PHP 9): `tools/scripts/null-parametros.php`, guiada por el log: envuelve solo el argumento que PHP reportó con `?? ''`/`?? 0` (86). Índices `null` en arreglos → `?? ''` (8)
  - Fatales: códigos de sesión en SQL sin comillas (`tb_shop_cd = ".$_SESSION['shop_cd']`: con sesión sin tienda la SQL queda inválida y el `pg_*()` siguiente es fatal) → `(int)`, **285 en 167 archivos** (`tools/scripts/sql-sesion-int.php`; además cierra la inyección por sesión). `count($_POST['x'])` de campos ausentes → como PHP 7 (0 si falta, 1 si es texto), **73** (`tipos-php85.php`). Puntuales: `date()` con texto, paginación con `$_REQUEST['page']` directo, consultas fallidas en `mail_kaijo` (5) y `mail_new2`
  - Pasada 5 (en 5 lotes, porque Docker Desktop se reinició dos veces a mitad de pasada): 598 envíos, fatales de código corregidos: `$_SESSION[$menu] = ""` + claves (fatal), `date()` de reservas con `(int)` (18), `implode()` de elementos de arreglo (63, `tipos-php85.php` ampliado)
  - **Librerías PDF incluidas en el repo** (`admin/lib/mpdf`, `signage/inc/mpdf`, `signage/inc/Thinreports` = TCPDF): 17 archivos **no compilaban** en PHP 8.5.12 y estaban fuera de las mediciones (igual que en Rector). `$s{0}` → `$s[0]` (484 offsets; `llaves-a-corchetes.php` ampliado a propiedades `$obj->prop{0}`), `each()` → `foreach` (9), parámetros opcionales (38). Las 2 copias de `mpdf.php` tenían **dos `default:`** en un `switch` (fatal desde PHP 7.0: ya no funcionaban antes de la migración); se quitó el primero, porque PHP 5 usaba el último
  - `srand()`/`mt_srand()` con `microtime()` (4): deprecado en PHP 8.5.12 y además empeoraba el azar de contraseñas aleatorias (re-sembrar con la hora en cada vuelta). Desde PHP 7.1 el generador se siembra solo: se comentaron
  - Fuera de la migración (no se tocan): conexiones a servidores POS externos (`$posConnect`, `$connect_hon`) que no existen en la red aislada; módulo `category11/item` (las tablas `tb_c2_category`/`tb_c2_item` no están en el backup); `left_navi.php`, que no está en el repo; fragmentos pedidos solos
- **Paso 19 — secretos fuera del código** (pedido por Antony el 2026-10-30; antes era "riesgo aceptado"):
  - `tools/scripts/secretos.php` inventaria contraseñas, tokens y claves (muestra los valores enmascarados): 12 secretos reales en 89 archivos. La contraseña de los servidores POS (usuario `crm-s`) estaba escrita **77 veces**
  - `public_html/inc/env.php`: `env_value('NOMBRE')` carga `.env` una sola vez (mismo `vlucas/phpdotenv` que `inc/db.php`)
  - `tools/scripts/secretos-a-env.php` (AST + tokens): lee los valores del `.env` local y reemplaza cada aparición por `env_value('VAR')` (strings de PHP), `<?= env_value('VAR') ?>` (HTML) o `[.env:VAR]` (código comentado), e inserta el `require_once` de `inc/env.php` con ruta dinámica. **114 reemplazos en 85 archivos**; verificado que **ningún archivo versionado** contiene ya un secreto
  - Variables nuevas (ver `.env.example`): `POS_DB_PASSWORD`, `SIGNAGE_DB_PASSWORD`, `ADMIN_DB_PASSWORD`, `AZASNIGHT_DB_PASSWORD`, `BEACON_DB_PASSWORD`, `TWITTER_CONSUMER_KEY/SECRET`, `GOOGLE_API_KEY`, `GOOGLE_API_KEY_ADMIN`, `RELATION_LOGIN_PASS` y `SUPPORT_MASTER_PASS` (contraseñas de **soporte** que permiten entrar como master: 13 usos en `login.php` y la configuración del sistema)
  - `my.env` sale de git (sigue en disco, ahora en `.gitignore`). **Los valores siguen en el historial de git y en el remoto: hay que cambiar esas contraseñas y claves en producción** (rotarlas); mover el código no basta
  - Pendiente de decidir: `docker/my.key` (clave del certificado local de Docker) sigue versionado porque Docker la necesita al clonar
- Decisión: **no** se envuelven las ~9,000 llamadas `pg_*()` sin `@`. En PHP 7 una consulta fallida dejaba la página a medias con un warning; en PHP 8.5.12 da 500. En uso normal las consultas no fallan, y envolverlas ocultaría errores reales
**Hecho cuando:** 0 funciones removidas en `composer metricas`, `composer install` funciona en 8.5 y el checklist pasa sin errores en el log.

### Fase 3 — Seguridad del código (4–8 semanas, por zonas)

**Línea base de Semgrep (2026-10-30): 11,434 hallazgos.** `echoed-request` (XSS) 6,853 · `tainted-sql-string` (SQLi) 2,400 · `tainted-filename` 558 · `tainted-exec` 552 · `exec-use` 477 · `weak-crypto` 343 · `printed-request` 129 · `tainted-url-host` (SSRF) 61 · `ftp-use` 40 · `file-inclusion` 11 · `eval-use` 6 · `curl-ssl-verifypeer-off` 3. Reporte: `tools/reportes/semgrep.sarif`.

**Nueva medición (2026-10-31, tras el paso 30): 10,648 hallazgos (-786).** Baja lo que Semgrep sabe reconocer: `tainted-exec` 552 → 0, `weak-crypto` 343 → 157, `eval-use` 6 → 0. **No baja lo que corrigen `(int)`, `pg_escape_string` y `h()`, porque no los reconoce como protección**: `tainted-sql-string` 2,400 → 2,402 y `echoed-request` 6,853 → 6,806, de los que 1,951 ya pasan por `h()`. Por eso `tools/scripts/semgrep-sql-residual.php` mira cada línea señalada: 253 aún tenían la petición en el SQL antes del paso 31, y algunas son falsos positivos (`api/mpa_getCoverFlow.php` escapa toda la petición al inicio). Estas cifras son un filtro de texto por línea, no una auditoría.

**Paso 9 — ejecución remota de código (RCE) en zonas públicas, sin login — ✅ corregido:**

| Archivo | Vulnerabilidad | Corrección |
|---|---|---|
| `api/mpa_albumUpdate.php` | **Sin autenticación.** Permitía subir un `.php` a `hp/uploads/` (accesible por web → web shell) y la extensión del archivo llegaba sin escapar a `system("convert ...")` | Lista blanca de extensiones de imagen (si no, el `err2` que ya existía) + `escapeshellarg()`. Probado: `shell.php` → `err2`; `foto.gif` → `ok` |
| `hp/mailsyori/mm_send1.php` | `$_REQUEST['shopid']`/`mailmagcd` directo en `system()` (página pública) | `escapeshellarg()` |
| `appview/member_regist.php` | Header `Host` y `$domain` directo en `exec()` | `escapeshellarg()` |
| `api/index.php` | `require` de una ruta armada con la query string (`../` incluía cualquier `.php`) | Solo segmentos `[A-Za-z0-9_-]`. Nota: `api/404.php` no existe (rutas desconocidas ya daban 500) |

**Paso 10 — SQLi en contexto numérico (todo el proyecto) — ✅ corregido:**

Psalm (taint): 10,657 flujos (TaintedHtml 3,700 · TaintedTextWithQuotes 3,690 · **TaintedSql 2,477** · TaintedFile 333 · TaintedShell 308 · TaintedHeader 92 · TaintedSSRF 49 · TaintedInclude 6). Reporte: `tools/reportes/psalm-taint.sarif`.

461 archivos ya escapan `$_REQUEST` con `get_form()`/`set_form()` (quitan comillas y aplican `pg_escape_string`): eso protege los valores **entre comillas**, pero **no los numéricos** (`WHERE cd = ".$_REQUEST['cd']` se explota con `1 OR 1=1`, que no lleva comillas).

- `tools/scripts/sql-numerico-int.php` (AST): `"... <where|and|or|set|on> <columna> <op> " . $_REQUEST[x]` → `(int) $_REQUEST[x]`. **1,228 valores en 553 archivos**, 127 parámetros distintos, todos ids o enteros (revisados `price`=integer y `day`)
- Prueba real (`hp/user/mm_kaijo.php`, pública): `kaiincd=1;SELECT pg_sleep(3)--` → **antes 6.24 s** (el SQL inyectado se ejecutaba 2 veces), **ahora 0.23 s**; uso legítimo igual

Pendiente de SQLi:
- [ ] Valores **entre comillas** sin escape (p. ej. `hp/user/mm_kaijo.php`: `cd = '".$_REQUEST['shopid']."'`). **No es masivo:** se escapa de 6 formas distintas sobre las superglobales (`array_walk`): `form2Db` ×431 (`inc/db.php`: `pg_escape_string` **sin** quitar comillas → `O'Brien` queda `O''Brien`), `set_form` ×28 y `get_form` ×2 (quitan comillas → `’`), `sanitize_and_pg_escape` ×8, `fnc_set_form` ×4 y `escapeString` ×2; además 460 archivos asignan directamente a `$_REQUEST[...]`. Escapar otra vez donde ya pasó `form2Db` **corrompe datos** y el escape puede venir de un include. Estrategia: archivo por archivo empezando por zonas públicas, migrando a `pg_query_params()` y probando cada pantalla
- [x] PHP 8.1 cambió el valor por defecto de `htmlspecialchars()` a `ENT_QUOTES`: `get_form2()` guardaba `O&#039;&#039;Neil` en vez de `O'Neil`. **Paso 47**: `ENT_COMPAT | ENT_HTML401` en las funciones de `kihon.php` (2 copias) y en lo que se guarda o se envía por correo; las llamadas que solo pintan HTML se quedan como están
- [ ] Variables intermedias (`$cd = $_REQUEST['cd']; ... "cd = ".$cd`) y strings interpolados (`"cd = {$_REQUEST['cd']}"`): el transformador solo cubre el uso directo
- [ ] `IN (...)` con listas recibidas por parámetro

**Pasos 11–12 — SQLi entre comillas en zonas públicas — ✅ corregido:**

- Paso 11: 19 archivos sin ningún escape → `pg_escape_string($connect, (string) …)` con AST (94 valores: terminales `touchcation`, `hp/card`, `hp/user`, `api/mpa_*`). `api/mpa_corresponding.php`: `set_error_handler(errorHundler)` sin comillas (fatal en PHP 8.5.12) y `$errContext` opcional
- Paso 12 (revisión manual con patrón amplio):
  - **Bypass de login**: `hp/user/login.php` y `ptag/index.php` metían la cookie `UID` en el SQL del auto-login. Con `UID=' OR '1'='1` se entraba como un cliente sin contraseña (demostrado con un cliente temporal: antes 302 = dentro, ahora 200 = rechazado; la cookie legítima sigue funcionando)
  - `search_set1_check1/2.php` (registro público: email y móvil solo se escapaban si llegaban vacíos), `news_img.php`, `api/mpa_setraiten.php`, `hp/user/control.php`
  - Revisados y **no tocados** (ya escapan toda la entrada con `get_form2`): `tomodachi_coupon_control`, `mpa_getTopBanner`, `mpa_getCoverFlow`, `hp/user/mail.php`. `hp/m/sale_list.php` fija `category = 1`
- Zonas públicas: los 44 archivos con valores entre comillas quedan escapados o verificados
- [ ] Pendiente: el mismo trabajo en `system/` y `admin/` (requieren login de staff)
> Pendiente: `api/mpa_*` no exige autenticación y el comentario dice que solo debe aceptar POST de `b.g-api.net`. Validar el origen (token o IP) es una **decisión de integración**.

Orden: **login/auth → `api/` → `hp/`, `reserve/`, `r/` → `admin/` → `system/` → `touchcation/`, `signage/`.**
Usar `reportes/psalm-taint.sarif` para priorizar (indica el camino real input → SQL/HTML).

Crear un archivo de helpers **siguiendo el patrón actual** (funciones procedurales, como `inc/kihon.php`):

| Helper | Reemplaza | Ocurrencias |
|---|---|---:|
| `db_query($sql, array $params)` sobre `pg_query_params` | SQL con variables interpoladas | 4,925 |
| `h($valor)` = `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')` | `echo $_GET[...]` sin escapar | 2,221 |
| `csrf_token()` / `csrf_check()` | — (no existe) | todos los `<form>` POST |

- [ ] **S1/S2 contraseñas:** `password_hash` con **migración transparente**: al iniciar sesión, si el valor guardado no es un hash y coincide en texto plano, se guarda el hash. Eliminar la cookie con contraseña y usar un token de "recordarme"
- [ ] **S3:** usar HTTPS para la API de soporte, enviar la contraseña por POST y nunca en la URL
- [ ] Shell (574): `escapeshellarg()` o sustituir por funciones nativas (la extensión Imagick ya está instalada)
- [ ] `unserialize` (89): `json_decode` o `['allowed_classes' => false]`
- [ ] `eval` (6): eliminar
- [ ] `md5` (245): separar contraseñas (→ `password_hash`) de claves de caché o nombres (sin cambio)

**Hecho cuando:** Psalm taint y Semgrep sin hallazgos críticos en las zonas migradas y las métricas de SQLi/XSS en tendencia a 0.

### Fase 4 — Estructura (continuo)

- [x] Rutas (paso 18): `'/home/system/public_html/...'` → `dirname(__DIR__, N) . '/...'` calculado desde la ubicación de cada archivo, con la **raíz web** como base (así un archivo nunca se incluye por dos rutas distintas). `tools/scripts/rutas-dinamicas.php` (AST): 1,588 rutas en 1,470 archivos, incluidas las que van a mitad de texto (`find …` y las líneas de crontab que genera `cSendSchedule.php`). Solo quedan en comentarios
- [ ] Librerías copiadas → Composer: mPDF (2 copias), TCPDF (`signage/inc/Thinreports`, no compila en PHP 8.5.12), PHPMailer (`system/class/localMail`), `JSON.php` (Services_JSON → `json_encode` nativo)
- [ ] Rector Fase 2B (`withPhpSets(php85: true)`) carpeta por carpeta
- [ ] PHPStan: subir de nivel 0 a 3 progresivamente
- [ ] Tests PHPUnit para la lógica que se mueva a `src/`

### Fase 5 — Decisión: ¿migrar a Laravel? (no empezar sin acuerdo)

Solo después de la Fase 3. Si se decide, usar el **patrón strangler fig**: Laravel corre al lado del legacy, comparte BD y sesión, y se migra módulo por módulo empezando por `api/`. `docker/README.md` ya menciona un setup de Laravel: **confirmar si existe otro esfuerzo en paralelo.**

---

## 4. Receta para cada cambio

```bash
git checkout -b refactor/<fase>-<carpeta>
cd tools && composer metricas                       # antes
vendor/bin/rector process ../public_html/<carpeta> --config=rector.php --dry-run
vendor/bin/rector process ../public_html/<carpeta> --config=rector.php
vendor/bin/phpstan analyse -c phpstan.neon ../public_html/<carpeta>
git diff --name-only -- '*.php' | xargs -n1 php -l  # sintaxis (idealmente dentro del contenedor 8.5)
# checklist de humo en el navegador + revisar log de errores
composer metricas                                   # después
git commit -m "refactor(<carpeta>): <qué cambió>"
```

---

## 5. Riesgos

| Riesgo | Mitigación |
|---|---|
| `ereg` → `preg` cambia el significado de algunos patrones POSIX | Revisar cada diff de 2A, no aplicar a ciegas |
| Escapar con `h()` texto que ya estaba escapado (doble escape) | Probar pantallas con caracteres japoneses y `& < > "` |
| La migración de contraseñas rompe el acceso de soporte (`[.env:RELATION_LOGIN_PASS]`) | Mantener ese flujo separado y probarlo explícitamente |
| Rutas duplicadas (`/var/www/html` vs `/home/system`) provocan "Cannot redeclare" | Rutas dinámicas con la raíz web como base (paso 18): el mismo archivo siempre resuelve a la misma ruta |
| Fin de línea: `core.autocrlf=true` y 2,552 archivos con CRLF en disco | Git normaliza al commitear. No mezclar commits de formato con commits de lógica |
| Los 4 archivos no UTF-8 se corrompen si una herramienta los reescribe | Excluidos en `tools/`. Convertir a mano en 2C |

---

## 6. Registro de avance

| Fecha | Fase | SQLi interpolado | XSS directo | Removidas PHP 8 | Baseline PHPStan | Nota |
|---|---|---:|---:|---:|---:|---|
| 2026-10-29 | 0 | 4,925 | 2,221 | 113 + 8 rotos | incompleto (8 rotos) | Línea base inicial |
| 2026-10-29 | 0 | 4,925 | 2,221 | 113 | 709 | 24 archivos rotos corregidos. `php -l` y PHPStan en verde |
| 2026-10-30 | 0 | 4,925 | 2,221 | 113 | 709 | Corre en Docker PHP 8.5.12. Smoke: 322 páginas, **15 con 500**. PHPUnit 49/53 |
| 2026-10-30 | 2 | 4,925 | 2,221 | 11 (solo `rss_list.php`) | 597 | Pasos 2A y 5a–5d. Smoke: **2,000 páginas, 0 con error grave** (quedan 2,434 warnings y 245 deprecations únicos) |
