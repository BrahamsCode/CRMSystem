# Cómo continuar el refactor (leer primero)

> Traspaso entre chats. Solo lo necesario para seguir: el detalle está en los documentos enlazados y no hace falta leerlos enteros.
> Actualizado: 2026-11-19 · Rama `refactor/fase-0` · Objetivo: **PHP 8.5.12**.
> Al terminar cada paso, actualiza este archivo: "Siguiente paso" y "Estado".

## Estado

| Frente | Avance | Base |
|---|---|---|
| Migración a PHP 8.5.12 | **≈ 94 %: código migrado (2026-11-13)** | Suma ponderada en `REFACTORING.md`, sección A. Todo lo ejecutable en Docker está migrado y probado, incluido lo que el código oculta por IP y las 132 rutas sueltas. El resto depende del entorno (cron, POS, sesión de socio, staging, `php.ini` de producción), no de código pendiente. `admin/` fuera de alcance: su BD tampoco existe en desarrollo |
| Seguridad | ≈ 80 % | Estimación, no medición: Semgrep no reconoce `(int)`, `pg_escape_string` ni `h()`. Misma cifra en `REFACTORING.md`, sección B |

Historial completo, un commit por fila: `CHANGELOG.md` (se regenera con `php tools/scripts/bitacora.php`). No hace falta leerla para continuar.

## Dónde estamos (2026-11-11)

**Migración cerrada a nivel de código.** Antony la da por terminada: el CRM está en PHP 8.5.12 y las pantallas no muestran avisos. Lo que queda abierto son decisiones (`PENDING.md`; borrar archivos de `BROKEN-FILES.md`; la pregunta 7.10 para Japón) y lo que tiene que aportar el entorno (`MISSING-TABLES.md` y "Siguiente paso", punto 1). **No hay trabajo de código pendiente que se pueda hacer sin una de esas decisiones.** En un chat nuevo: preguntar a Antony qué decisión toma y seguir desde ahí.

**2026-11-13, vistas que no se habían tocado:** lo restringido por IP (en Docker nunca se veía), por las banderas `app_layout_mode` y `beacon_use_flg`, y 132 rutas sueltas sin enlaces. Resumen y listado en `MANUAL-TESTS.md`, última sección. **2026-11-17:** las sueltas que necesitaban un `cd` válido, abiertas con datos: 0 avisos (4 lecturas protegidas y un `list()` con `array_pad`), salida igual a `HEAD` y, donde desarrollo tiene datos (`mail_past1`, `coupon_send`), la misma estructura que allí. Lo temporal de la prueba por IP ya está deshecho (`PENDING.md` 0.16).

**2026-11-19, 1b.2 y 1b.4 probados** (`PENDING.md`): las 74 pantallas que leen `kensu` de la petición, con `?kensu=0` y sin él, dan lo mismo y ninguna se cuelga. Esa pasada abrió las pantallas sin su `cd` y encontró 5 con un aviso **en pantalla**, que PHP 7 daba como `Notice`: `category1/config/schoolclass_config` y `schoolyear_config` (dentro del `value` de 区分名, así que se habría enviado al guardar), `category3/coupon/coupon_list` (`$last_page`), `category3/digital_signage/digital_signage_entry` y `category3/visit/day_visit_list` (`$tb_raiten_max`, `$page_control`). Corregidas con `undefined-guard.php --fix` solo sobre esas líneas del log: 8 lecturas con `?? null`, `verify-guard-diff` 5 de 5 explicados solo por guardas, y al repetir 0 avisos. Los avisos que quedan en el log de esa pasada (`anketo*`, `customer_detail`) no llegan a la pantalla: sin `cd` la página redirige con `header("Location: …")`, igual que en `master`.

**2026-11-20, pasada de envío de formularios, subidas y navegador** (red aislada, copia de la BD, devuelta al terminar). El recorrido y los 48 flujos no enviaban formularios con datos ni archivos; esta pasada sí. **Formularios:** `formularios.php` (ahora sube archivos y anota avisos en pantalla y en el log) envió 406 formularios contra 230 scripts, desde las 612 pantallas y los 123 enlaces de editar/detalle. **Subidas:** los 33 scripts de `system/` que reciben archivos, con un archivo real. **Navegador:** errores de JavaScript en 605 pantallas, y las 31 con error abiertas también en `master` (PHP 7.4.33). Encontró y quedó corregido:
- **Fallo real de la migración:** 24 bucles `foreach ($_REQUEST …) @pg_escape_string($connect, $val)` y `get_form2()`/`fnc_set_form()` (más `get_form()` de `hp/`) lanzaban `TypeError` con un array (`name[]`): la selección de clientes de メルマガ/デコメール moría. `array-arg-guard.php --fix`, `PHP85-REPLACEMENTS.md`.
- **Avisos en pantalla al enviar:** 94 lecturas indefinidas en 20 archivos (`reserve_new_confirm`, `reserve_change_status`, `contact/*`, `auto_new`, `reminder_new`, `point_rules_update`, `customer_list`…) con `undefined-guard.php --fix --log=<extracto>` (`verify-guard-diff`: 17 de 18 solo guardas; el otro es la forma de `.=` de la herramienta), 4 `explode(null)` de `staff_shift.php` con `null-parametros.php`, y a mano: `exit(null)`, `end(explode())`, `@fsockopen` del servidor push, `pg_query('')` de `point_config` (también en `master`), `false` a array en `report_entry*`.
- **Instalación nueva:** `uploads/` no está en git; 18 scripts de subida no creaban su carpeta (los de personal, フォロー y メルマガ sí) y 2 `mkdir()` no eran recursivos. Ahora crean la carpeta igual que sus hermanos. Con la carpeta ya existente (producción) no cambia nada; `master` en una instalación nueva falla igual (comprobado con `ajax_apliFileUpload`, 8 de 8).
- **Tablas borradas:** `category11/item/register.php` silencia solo con `tb_c2_*` (`MISSING-TABLES.md`).
- **Navegador:** 30 de 31 errores de JavaScript son idénticos en `master`; el otro es `sumup_graph.php`, que en `master` da 500 (`BROKEN-FILES.md`).
- **Sin tocar, igual que `master`:** búsqueda por メールアドレス en 店舗一覧 (`tb_shop.mail` no existe, pregunta 14) y errores de SQL con texto en campos numéricos.

Después, con la BD limpia: smoke 617 páginas 0 avisos, `functional-flows` 48/48, y la paridad completa contra `master` en PHP 7.4.33 (rama por Apache + FPM): sin diferencias nuevas, solo los calendarios (1970, decidido), las 14 de reservas (1b.6) y artefactos de prueba (imágenes subidas, host del QR). **No volver a correr `formularios.php` sin copia de la BD:** deja registros que rompen otras pantallas (un informe sin contenido manda al logout y el smoke pierde la sesión).

**2026-11-19, comparación con `master` en PHP 7.4** (`tools/parity/run.sh`): la primera vez que se compara `master` ejecutándose en PHP 7.4 (la versión de producción) con la rama en PHP 8.5.12, pantalla por pantalla, con la misma BD y en una red sin salida a internet. 524 pantallas: 7 dan 500 en `master` y funcionan en la rama (`staff_shift_kind` ×5, `working_list`, `working_total_list`: fallos de `master` en PHP 7, pasos 5d y 1b.2), y de las 517 restantes **500 son iguales** quitando los cambios deliberados de `tools/parity/rules.php` (jQuery UI 1.13, pestaña de 女の子管理, comillas de selectores de jQuery 3, formato del id de sesión). Las 17 que difieren: 2 calendarios (el 1970 de 1b.1, decidido), 14 de `category3/reservation` (1b.6, abajo) y `shop_new.php`, que tras la corrección de `$dato == $i` ya es igual. **Encontró dos fallos de la migración que nada había visto:** `0 == ''` (PHP 7 verdadero, PHP 8 falso) en `Area.php` (el selector de áreas perdía las tiendas sin área en 7 pantallas) y en comparaciones con el contador de un bucle (`$dato == $i`). Corregidos 44 sitios en 33 archivos con `int-vs-text-compare.php` (`PHP85-REPLACEMENTS.md`, dos filas nuevas). **Pendiente de Antony:** `reserve_detail`/`reserve_list` muestran `1970/01/01 09:00` donde `master` deja la celda vacía (1b.6, `date()` con dato vacío).

**2026-11-19, 0.11 leído** (`PENDING.md` 0.11): de los 16 scripts de `category0/config/`, `db_fix_light` estaba roto por el refactor (`(int)` en un nombre de tabla) y ya está corregido y probado. Al leer `db_fix` apareció **`PENDING.md` 4.0, crítico y también en `master`**: `login.php?activation=1`, sin sesión y con otro `Host`, inicializa la BD. **No lanzar nunca esa URL** (borra unas 100 tablas de la BD local); está en la lista de "no pedir", junto a `create_customer.php` y `create_shop.php`. Pendiente de Antony y de Japón (pregunta 13).

Documentos y para qué sirve cada uno. **2026-11-18:** los nombres pasaron a inglés (antes `CONTINUAR`, `PENDIENTES`, `TABLAS-FALTANTES`, `ARCHIVOS-ROTOS`, `REEMPLAZOS-PHP85`, `PRUEBAS-MANUALES`, `REFACTORIZACION`, `INTEGRACIONES`, `DEPENDENCIAS`, `BITACORA`), con las referencias del código actualizadas. Los marcados con 🇯🇵 tienen versión japonesa (`*.ja.md`) para el equipo de Japón: **al cambiar uno, actualizar también su `.ja.md`**.

| Documento | Para qué |
|---|---|
| `MIGRATION.md` 🇯🇵 | Entrada para el equipo: qué se cambió y reemplazó, qué hacer al desplegar y mapa de documentos |
| `CONTINUE.md` (este) | Estado, criterios acordados, comandos y trampas conocidas |
| `PENDING.md` 🇯🇵 | Todo lo que necesita una decisión, numerado: 0 para el final (Slack, BD de prueba), 1 lo que falta del entorno, 2 archivos rotos de `master`, 4 seguridad aparcada, 5 calidad técnica, 7 preguntas para Japón |
| `MISSING-TABLES.md` 🇯🇵 | Lo que falta en la BD o en la red, qué sale ahora en cada pantalla y qué saldrá cuando exista |
| `BROKEN-FILES.md` 🇯🇵 | Archivos que no funcionan (casi todos ya fallaban en `master`) y candidatos a borrar |
| `PHP85-REPLACEMENTS.md` 🇯🇵 | Catálogo de cada patrón cambiado (antes, después, resultado, cuántos, con qué herramienta) |
| `MANUAL-TESTS.md` | Una fila por pantalla de los 10 módulos, con lo probado |
| `REFACTORING.md` | Plan, porcentajes por bloque y seguridad (sección B) |
| `INTEGRATIONS.md` 🇯🇵 | Quién usa desde fuera cada clave, token o contrato. Leer antes de cambiar uno |
| `DEPENDENCIES.md` 🇯🇵 | Librerías de terceros y su estado |
| `CHANGELOG.md` | Historial generado desde los commits |
| `tools/README.md` | Herramientas de verificación y cómo se ejecutan |

## Cómo se hizo la prueba a mano (terminada el 2026-11-09)

**Encargo de Antony:** recorrer el sistema del personal en Chrome con `display_errors = On` (como en producción), **pulsar cada botón y ejecutar cada función** de cada pantalla, corregir lo que salga y **hacer push por vista o por módulo**. El mapa, con una fila por pantalla, está en **`MANUAL-TESTS.md`**: es el documento que hay que abrir para continuar.

| Módulo | Estado |
|---|---|
| 1. 顧客管理 a 10. 基本情報管理 | **Terminados** (163 filas). El 9 (女の子管理) retirado el 2026-11-09: oculto en el menú y 403 por URL |
| **Para el final** | `PENDING.md`, sección 0: webhook de Slack, restaurar `zz_test_bak_base_system*`, revisar `BROKEN-FILES.md` y lo que no está en Docker |
| **Último fallo de la migración (2026-11-17)** | Los 9 `exp_cmd/verUp/versionUP_*.php` morían en PHP 8.5.12 (`pg_field_num` con 3 argumentos). Ahora dan el resultado de PHP 7 (`PENDING.md` 2a). Revisada la sección 2 entera: lo demás que está roto ya lo estaba en `master`. **2026-11-18:** dos clases escribían propiedades sin declarar (`Deprecated` desde PHP 8.2): `Twitter_OAuthConsumer` (2 copias) y `MobileShop\Constant`. Ahora llevan `#[\AllowDynamicProperties]`, como `cKengen`. Un escáner por tokens, comprobado con las versiones de `HEAD`, no encuentra ninguna otra en el repositorio |
| **Secretos (2026-11-18)** | Ya no queda ninguna clave activa en el código: las dos últimas (`MOBILESHOP_EXTLINK_PASS`, `MAIL_ADD_POP3_PASS`) se leen de `.env`, con el mismo valor (comprobado por SHA-256). `.env.example` trae todas las variables que lee el código y, por decisión de Antony, **los valores** de las claves que estaban en el código de `master` (`.env` no se sube): 23 valores, comprobados idénticos a `.env`. `.env` local tiene las mismas variables (`PENDING.md` 1.8b). Pregunta 2 respondida: las tareas programadas van por cron |
| **Sin errores en pantalla (2026-11-11)** | `smoke.php --per-dir=25 --urls=tools/scripts/smoke-seeds.txt` (índices y los 140 enlaces de los menús): 615 pantallas de `/system`, **0 avisos de PHP de ningún tipo**. Las conexiones a bases que ya no existen o están en otro servidor tampoco avisan: `silenciar-conexion.php` (`@pg_connect`, 60 llamadas). Fuera de `/system` (`hp/`, `touchcation/`, `signage`, `digitalsignage`, 305 páginas sin sesión de socio ni de quiosco): solo 4 páginas que también dan 500 en desarrollo y en `master`, anotadas en `BROKEN-FILES.md`. Herramientas: `proteger-pg-resultado.php` (consulta fallida), `proteger-pg-conexion.php` (conexión a otro servidor nula) y `verify-pg-guard-ast.php` (que el diff solo añada guardas). Lo que falta en la BD o en la red: `MISSING-TABLES.md`. El recorrido atribuye el log por hora: si alguien navega a la vez, los fallos salen en otra pantalla |

**Cómo se prueba cada módulo** (lo que funcionó en los tres primeros):

1. Marca del log, para la pasada final: `docker compose -f docker/docker-compose.yml exec app sh -c 'wc -l /var/www/php-error.log'`.
2. Barrido por `fetch` de todas las pantallas del módulo desde la consola del navegador, ya con sesión, buscando `<b>Warning|Deprecated|Fatal error</b>` en el HTML. Encuentra los avisos de carga sin gastar tiempo.
3. A mano, pantalla por pantalla: alta, edición, borrado, cada botón, cada filtro y la paginación, con datos `TEST`. Mirar **siempre** tres sitios además de la página: el panel verde del final (`docker/dev-show-errors.php`), los **campos ocultos** de cada formulario (un aviso dentro se envía como dato, F7) y las **descargas** (CSV, PDF: F6).
4. Pasada de log del módulo: `php tools/scripts/undefined-guard.php --since=<marca> --fix` y `php tools/scripts/null-parametros.php /var/www/php-error.log <bytes>`.
5. Verificar, **leer los resultados** y solo después commitear: lint de lo cambiado, `verify-guards` (que el diff solo añada guardas), las 524 páginas con el código viejo y el nuevo sobre los mismos datos, `functional-flows` y PHPUnit.
6. Rellenar las filas del módulo en `MANUAL-TESTS.md`, actualizar el resumen, `CHANGELOG.md` y este archivo. Commit y push.

**Trucos del navegador que costaron tiempo** (no volver a tropezar):

- **Las claves no son las mismas en local y en desarrollo:** local `master` / `12345678`, desarrollo (`development.crm-s.net`) `master` / `master`. En desarrollo, solo lectura (`PENDING.md` 0.15).
- El MCP de Chrome que funciona aquí es `chrome-devtools`. Login en `/system/login.php` con master / 12345678; tras `docker compose restart app` la sesión se pierde y hay que repetirlo.
- Un `take_snapshot` de estas pantallas ocupa muchísimo. Usar `evaluate_script` con `sourcePath` y **`tools/navegador/inspect-page.js`**, que devuelve solo lo necesario (panel de PHP, formularios con sus campos ocultos, botones con su `onclick`, enlaces). Para el barrido del paso 2, **`tools/navegador/scan-urls.js`**.
- `take_screenshot` se cuelga (`Page.captureScreenshot timed out`) si la pestaña no está al frente: antes `select_page` con `bringToFront: true`, y mejor en `jpeg`. `window.open` (年間カレンダー) solo abre con el `click` del MCP sobre un `uid` de `take_snapshot`, no con `element.click()`.
- Los diálogos de `jquery.alerts` responden a `mouseup`, no a `click`. Los de jQuery UI (el "本当に送信されますか？" de las notificaciones push) necesitan un **clic nativo** en su botón OK: con eventos sintéticos no se envía nada y parece que el botón está roto. Y si queda abierto el diálogo de プレビュー, intercepta el clic del botón de debajo.
- Los `confirm()` nativos se aceptan con `dialogAction: "accept"` en `evaluate_script`.
- Si un botón parece no hacer nada, mirar la red (`list_network_requests`): si no sale ninguna petición, es que el clic no llegó al manejador.
- **Subir imágenes sin diálogo de archivos:** crear un `File` con `fetch()` de una imagen del propio servidor (`/system/category11/pos/img/receipt_logo.jpg`), ponerlo en el `input[type=file]` con `DataTransfer` y lanzar `change`. Funciona con `ajaxupload` y con los `.addImage` de jQuery. Los botones de los diálogos de jQuery UI de マイページ sí responden a `.click()` lanzado con `setTimeout` desde `evaluate_script`.
- **Si el MCP dice "The browser is already running":** otra sesión de Claude tiene abierto el Chrome del perfil del MCP. No hay permiso para cerrarlo: pedírselo a Antony. **Filtrar el log por módulo** (`grep` de la carpeta propia a un archivo y `--log=` en `undefined-guard.php`, en vez de `--since`): `functional-flows.php` también escribe avisos de otros módulos (señalización, `category7`), y no hay que confundirlos con otra sesión.
- **Comparar código viejo y nuevo sin reiniciar `app`:** `page-fingerprint.php --strip-php-messages` quita los avisos y el panel antes del hash, así que vale con `display_errors` On. Para el código viejo, copiar solo los archivos propios con `git show HEAD:f > f` y restaurarlos después comprobando el md5.
- `docker/dev-show-errors.php` ya no añade el panel a peticiones con archivos subidos: `ajaxupload` lee la respuesta como nombre de archivo y el panel se pegaba al nombre.

## Siguiente paso, en orden

**Prioridad de Antony (2026-11-01): primero terminar la migración a PHP 8.5.12, la seguridad después.** Un hallazgo de seguridad se anota en "Abierto, sin corregir" y no se corrige ahora, salvo que lo pida.

1. **Bloqueado por lo que no está en Docker; lo aporta Antony o su jefe:**
   - **`crontab` y el demonio cron no están en la imagen.** `cSendSchedule.php` escribe ahí los envíos programados, las alertas de puntos y sellos, y los rangos: `exec("crontab …")` falla en silencio y **nada de eso se ejecuta**. Requiere cambiar el Docker o definir otro lanzador.
   - Los servidores POS y un servidor de staging (A6). La BD de `admin/` ya no: fuera de alcance (2026-11-11, ver "Decisiones").
2. **Pruebas funcionales (A5), hechas:** `tools/scripts/functional-flows.php` cubre cupones, niveles de cliente, reglas de puntos y de sellos, clientes con campos personalizados y `add_record`, personal, reservas (configuración, reserva de socio con correo, mover, aviso, anular), envío masivo inmediato y señalización (pantallas y programas). No se ejecutan: envíos programados (por cron), `mycoupon` y encuestas, que comparten emisor pero no pantalla, y señalización en el dispositivo.
3. **Al final:** revisar `BROKEN-FILES.md` con Antony.

**Traducción de `tools/scripts` al inglés, hecha en el paso 41 (comentarios, mensajes e identificadores). Sigue en español:** los nombres de archivo, que `CHANGELOG.md`, los commits y `CLAUDE.md` citan; las claves de los informes JSON (`archivo`, `errores`), porque los leen otras herramientas e informes antiguos; `bitacora.php`, que genera un documento en español; 5 `.sh` (`check-php`, `metricas`, `psalm`, `restaurar-bd`, `semgrep`), que el hook no revisa; y `tools/navegador/` salvo `csrf-attack.mjs`. `check-english.php` sigue listando 44 líneas de `tools/scripts`: esas claves y los nombres de archivo en los comentarios `Usage:`.

**En espera por decisión de Antony (2026-11-01): XSS almacenado (BD).** No se ha empezado a corregir. Quedan ~4,800 ecos sin `h()`; `h()` rompería los campos con HTML legítimo.
- En la BD de prueba solo hay HTML en `tb_mail_template.naiyou` (7 filas) y `sales_items.discription` y `discription_detail` (1 fila).
- En el código, 12 pantallas usan editor enriquecido: plantillas de correo (`*_mail_template_entry.php`, `mail_frm.php`, `mail_future_frm.php`) y `category2/item/edit.php`.
- **Esta BD no basta para decidir:** tiene muy poco texto enriquecido, y los campos de contenido (`freepage`, `whatsnew`, `topics`, `recommend`) pueden llevar HTML escrito a mano en un `textarea` sin dejar rastro aquí. Falta una muestra de producción, o que Antony confirme la lista.
- Enfoque propuesto: lista blanca de columnas con HTML, `h()` en el resto, por módulos y comparando la salida antes y después. No hacerlo de una vez por nombre de campo.
- También hay 13 casos marcados como posible texto enriquecido (`xss-request-variables.php --detail`, líneas `review`).

**Estado de la migración (2026-11-03).** El código está migrado a PHP 8.5.12: compila entero sin avisos, no llama a funciones eliminadas (salvo `rss_list.php`, sin enlaces) y los cambios de comportamiento hacia PHP 8 que se pueden detectar están corregidos para dar lo mismo que antes. Lo que no está verificado depende de lo que no hay en Docker (cron, BD de `admin/`, POS, staging). Preguntas para Antony:
- **Producción era PHP 7 (7.3 o 7.4), no PHP 5** (2026-11-05, Antony lo detectó). El código y los `.md` dicen "PHP 5" por un error de partida. Para casi todas las guardas da igual, porque PHP 5 y 7 devuelven lo mismo; las excepciones y lo que falta probar están en `PENDING.md` 1b. **Ya hay PHP 7.4 en local** (imagen `crm2-php74-pgsql`, construida desde `php:7.4-cli` con `archive.debian.org`): `tools/scripts/php74-parity.php` compara cada guarda con lo que hacía PHP 7.4. Ante una guarda nueva o dudosa, añadir el caso ahí y ejecutarlo en las dos versiones. **Desde ahora, comparar con PHP 7**: al escribir "igual que antes", pensar en lo que hacía PHP 7.4. `register_globals` no existía en PHP 7: esa pregunta está cerrada.
- **`db/crm2019.backup` no es una copia de producción con datos:** 175 tablas, el mismo esquema que la BD local, pero 0 clientes, 0 reservas y 0 asistencias; el 98 % de sus filas son códigos postales. Para probar nóminas y datos reales hace falta un volcado con datos, o staging.

**Herramientas del paso 39 (CSRF):** `tools/navegador/csrf-attack.mjs` (ataque real en Chromium y Firefox; antes hay que copiar `point_alert_mail_title` y `point_alert_mail_naiyou` de `admin_master` y restaurarlas después) y `tools/scripts/csrf-guard-check.php` (matriz de cabeceras).

**Herramientas del paso 37:** `sql-array-sinks.php` (detector, con `--fix`), `sql-array-sinks-attack.php` (ataques) y `sql-createform-check.php`. Lo que el detector sigue listando (137 filas) se revisó a mano y no es SQLi: `$order_by` tras lista blanca, tablas con lista blanca o guarda, `$partsview` que sí es HTML, fragmentos `wherestr` que el servidor construye, y valores ya escapados por el archivo que los incluye.

**Abierto, sin corregir:**
- **`system/login.php?activation=1`: una petición anónima inicializa la BD** (`db_fix.php`, unas 100 tablas) si el `Host` no coincide con `admin_master.domain`. Igual en `master`. Propuesta: 403 salvo por CLI o sesión de soporte. Decide Antony; pregunta 13 para Japón (`PENDING.md` 4.0).
- **`system/create_customer.php` y `create_shop.php`: una petición anónima sobrescribe clientes** (`login_pass = '0000'`) y reinicia `hatuban`. Propuesta: 403 por web. Decide Antony, ver `INTEGRATIONS.md`.
- `admin/inc/common.php:31`: `?customer_cd=N` en `news_list.php` y similares abre sesión `ACCESS_LEVEL_MASTER` si ese cliente tiene `app_staff_flg = '1'`, sin contraseña. Solo leído en el código, sin probar. `admin/api/layouts.php` enlaza a esas pantallas: el cliente parece ser la app, fuera del repositorio.
- CSRF sin cubrir: `pos_session.php` (7 archivos que escriben, decisión del token POS), sesiones de quiosco en `touchcation/` y GET en la zona de socios (los clientes llegan desde correos). Con `SameSite=Lax`, un POST desde otro sitio no escribe, pero cierra la sesión de la víctima: PHP responde con una cookie nueva. En Chromium ya ocurría.
- `hp/mailsyori/felica_detail.php` registra clientes sin login: cualquier petición con `shop_cd` crea uno. Lo usan terminales Felica de fuera del repositorio: se decide con "Autenticación del CTI y de las APIs" (pendiente de Antony).
- `hp/user/` con sesión de socio no se ha escaneado (XSS reflejado): no hay credenciales de prueba. Sin sesión esas páginas redirigen, así que el 0 del paso 38 no las cubre.
- `admin/detail/database_merge.php:20`: `pg_connect` con host, usuario y clave por `$_GET`.
- `hp/user/sc/weather/index.php:159`: XPath con `$_REQUEST['area']`.
- `system/class/orClass_shopping_v0.1.php`: SQL sin escapar, pero nadie carga esta clase.
- `system/category7/card_signage/control.php` → `cUrl.php`: título y URL de las tarjetas van al SQL desde `$_POST` sin escapar (el `form2Db` recorre `$_REQUEST`). Inyección SQL con sesión del personal, y además un título con comilla no se guarda. Igual en `master`. Visto en la prueba a mano del módulo 7 (paso 59), sin atacar ni corregir.
- `src/Util/Slack.php`: la URL de un webhook real de Slack estaba escrita en el código. Desde el 2026-11-17 viene de `.env` (`SLACK_WEBHOOK_URL`), pero sigue en el historial de git: conviene revocarla. Solo la usa `tests/Util/SlackTest.php`, que publica de verdad (ver "Cómo trabajar").
- `system/category5/working/change_syukkin2.php` (ruta suelta, sin enlaces): el día que llega en `nengappi` va sin validar al **nombre de la columna** del `UPDATE tb_work_time` (`syukkin_time_` + día). Inyección SQL con sesión del personal. Igual en `master`. Visto el 2026-11-17, sin atacar ni corregir.
- `category0/config/config_register.php`: la clave de `master` viaja en claro por URL (`login.php?...admin_pass=`, en un `meta Refresh`) y se guarda sin hash. Igual en `master`. Visto en la prueba del módulo 10, sin atacar ni corregir.
- **Al probar:** `category0/shop/shop_new_register.php` llama a `b.g-api.net` si `application_use_flg = '1'`, y esta BD lo tiene así: poner la bandera a 0 antes y reponerla después. Los scripts de mantenimiento de `category0/config/` (`db_fix*`, `change_html_*`, `create_immovables`…) dan 403 o van al login sin sesión, pero con sesión no se han abierto: contienen sentencias de escritura. Leerlos antes de abrirlos.

## Decisiones

**Tomadas por Antony; no volver a preguntar:**
- El código nuevo va en inglés (reglas en `CLAUDE.md`).
- La clave de `tool/api` pasó a `.env` (hecho, paso 35).
- Token POS: **no se toca** hasta saber qué dispositivos lo usan (`INTEGRATIONS.md`).
- Archivos rotos: se anotan en `BROKEN-FILES.md` y se ven al final.
- `display_errors` **en On**, como en producción (Antony, 2026-11-04): cada aviso de PHP se ve en pantalla y se corrige vista por vista (`MANUAL-TESTS.md`). Las comparaciones automáticas (`page-fingerprint.php`) se hacen con Off. Por lo demás, Docker queda como lo dejó su jefe, con rutas dinámicas.
- Push de las correcciones al terminar cada módulo de `MANUAL-TESTS.md`, o cada vista si es importante.
- Riesgos aceptados, no tocar: `BOX_FLG_COMPLETE` en "8" (desde 2026-11-10 sin los avisos: las dos redefiniciones están comentadas y el valor sigue siendo "8"); `cron/` sin protección; backup y CSV de datos en git; `security.php` y `test.php` en la raíz web.
- La BD local es desechable y debe conservar los datos de prueba. No restaurarla después de probar.
- **Paridad funcional y visual con `master` por encima de todo** (Antony, 2026-11-09): migrar las versiones, pero cada pantalla debe funcionar y verse igual que antes. Por eso jQuery UI 1.13.3 va **sin** su CSS y con `jquery-ui-dialog-compat.js`, que reproduce lo que hacía cada versión vieja (medido al píxel contra el servidor de desarrollo). No cargar `jquery-ui.structure.css` ni tocar `mainStyle.css` para los modales.
- **"Que la funcionalidad no cambie para nada"** (Antony, 2026-11-10): ante una diferencia, lo que vale es lo que hace `master` en PHP 7, y el servidor de desarrollo es la muestra de cómo se comporta. La única diferencia buscada es el módulo 9 (女の子管理) oculto. Aplicado ya a `pg_fetch_result` con fila `''` (1b.6). Cuando PHP 7 daba un resultado roto pero visible (botones a 1970 en `entrance/working_calendar.php`, comprobado en desarrollo), preguntar antes de imitarlo.
- **Los resultados rotos de `master` se dejan corregidos** (Antony, 2026-11-16; era la pregunta 10). Afecta a `mktime()` con 7 argumentos (220 llamadas: calendarios, horarios, 営業日設定, informes) y a `date`, `number_format` y `mktime` con un dato vacío. El motivo: en los 5 casos el código original **lanza una excepción en PHP 8.5.12** (medido en PHP 7.4.33 con `php74-parity.php`), así que había que tocarlo de todas formas e imitar a PHP 7 habría sido conservar un fallo. La excepción sigue siendo `pg_fetch_result` con fila `''` (1b.6), que sí imita a `master`. **Medido en el servidor de desarrollo con sesión (2026-11-16):** de las 6 pantallas de calendario, 4 están rotas allí y 2 ya funcionaban (esas 2 tienen la estructura idéntica a la de local). En las 4 rotas, `date("t", mktime(…,0))` da los 31 días de enero de 1970: desarrollo dibuja un **「2026/11/31」** inexistente y en 営業日設定 ese día de más tiene casilla propia. Queda avisar a Japón: revisar en producción si hay días imposibles guardados en `tb_shop_work` y en los horarios.
- **Páginas que destrozan datos con un GET: 403 por web** (Antony, 2026-11-16): `category0/config/test_fix.php` (rehacía `tb_base_system_child`) y `category3/visit/fix_visit_stats.php` (reescribía todo `tb_raiten_rireki`). Con `PHP_SAPI !== 'cli'`, como los 12 cron del paso 26c. **Corrección (2026-11-18):** por CLI tampoco hacen nada, porque cargan `session.php` antes de tocar la BD y sin sesión sale con `exit`. Nada las llama (ni en `master`): hoy no las ejecuta nadie. Estaban en las listas del barrido.
- **Sin errores en pantalla por lo que falta** (Antony, 2026-11-10): si falta una tabla, un dato o un servidor, la pantalla carga vacía y se anota en `MISSING-TABLES.md` qué saldrá cuando exista. **Ampliado el 2026-11-11** (Antony, por indicación de su jefe): las conexiones a bases o servidores que fallan tampoco muestran aviso (`@pg_connect`); la pantalla hace lo mismo que hacía sin conexión. Lo que hay que ver en pantalla son errores reales del código, no lo que falta en el entorno. Para encontrarlos: `smoke.php --per-dir=25 --urls=tools/scripts/smoke-seeds.txt` (los `index.php` de `/system`), que reparte el recorrido por carpetas.
- **`admin/` fuera de alcance** (Antony, 2026-11-11): conecta con otra BD (`admin`) que tampoco existe en desarrollo (`development.crm-s.net/admin/login.php` da el mismo `データベースサーバに接続できませんでした`). Solo se exige que no muestre avisos de PHP: las 160 páginas recorridas por URL dan 0, salvo `mail/index.php`, rota en `master` (`BROKEN-FILES.md`). Los 14 fragmentos de `admin/inc`, `demo2/inc` y `api/class` que avisaban al pedirlos sueltos dan 403 por URL directa, con la misma guarda que los de `/system`. No pedir su BD.

**Pendientes de Antony o su jefe; no decidir por ellos:**
- Reemplazar la descarga CSV con Flash (17 pantallas). Ver `DEPENDENCIES.md` 11b.
- Editores CKEditor/TinyMCE (licencia GPL) y módulo SNS.
- Migrar a `password_hash` (rompe integraciones con `md5(login_pass)`).
- Autenticación del CTI y de las APIs entre servidores.
- Rotar credenciales en producción.
- Revisar exportaciones CSV antiguas y logs de acceso en producción.
- Borrar las 3 copias de Qdmail sin uso.
- Traducir también los `.md` y los commits al inglés (hoy en español).

## Estado de la BD de prueba (no restaurarla)

La BD local es desechable y **conserva los datos de prueba a propósito**. Lo que hay puesto ahora mismo:

- Cliente **TEST-001** (`tb_customer.cd = 1793745944001838501`, `kanri_id = 'TEST-001'`): se usa como destinatario único en todos los envíos, para no disparar 22,000 correos. Buscarlo por **管理番号**, no por 顧客番号.
- Notificaciones push de prueba **ID 31** (seguimiento) y **ID 32** (automática), activas.
- Fila `tb_felica_mode.cd = 2`, que la BD no traía y sin la cual esa pantalla no guarda nada.
- Los 27 submódulos activados para poder ver los menús, con la configuración original guardada en las tablas `zz_test_bak_base_system*`. **Restaurarlas al final de todas las pruebas.**
- Directorio `cron_schedule/` creado dentro del contenedor (está en `.gitignore` y no existía).
- Borrados ya (lógico, `disp_flg = 9`): cliente TEST-DEL, cupón TEST, las dos encuestas TEST y la plantilla TEST; en マイページ, las filas TEST de フリーページ, トピックス, オススメ, 新着情報, フォトギャラリー y 求人.
- `tb_customer` tiene hoy 22,325 filas (TEST-001 y TEST-DEL incluidas).
- **Lo temporal de la prueba por IP, ya deshecho (2026-11-17, `PENDING.md` 0.16):** sin `STAGE` en `.env` y con `app_layout_mode` y `beacon_use_flg` en sus valores originales, así que en Docker lo restringido por IP vuelve a estar oculto. Para volver a verlo: añadir `STAGE=docker` a `.env`. Siguen en borrado lógico `tb_nextouch_display.cd = 4` y `tb_point_setting.cd = 7`. La base `crm_before` y las copias de `/tmp` ya no existen.
- `.env` tiene ahora `SLACK_WEBHOOK_URL` (2026-11-17); `.env.example` la trae vacía.
- Carpetas creadas dentro del contenedor para probar subidas (fuera de git): `hp/uploads/c6/…`, `hp/uploads/c4/shop_hp/header/` y `system/category6/recruit/uploads/temp/` (ver `PENDING.md` 2d).

## Cómo trabajar

Por cada paso: medir → atacar → corregir → verificar → **leer los resultados** → commit y push.

**Docker:** comprobar primero `docker compose -f docker/docker-compose.yml ps`. Si `db` o `mailhog` están caídos: `docker compose -f docker/docker-compose.yml up -d db mailhog`. **Para recrear `app` o `web`: `cd docker && docker compose up -d`, sin `-f`.** Con `-f` no se carga `docker-compose.override.yml`, falta el montaje de la raíz y el login da un fatal por `vendor/autoload.php` (2026-11-20). **Una BD caída parece una página lenta.**

**`display_errors` está en On** en `docker/php.ini` (decisión de Antony: producción trabaja así). Para medir huellas hay que ponerlo en Off, reiniciar `app` y **volver a dejarlo en On al terminar**.

**El log de PHP (`/var/www/php-error.log`) es solo local** (decisión de Antony, 2026-11-20). `docker/php.ini` deja `error_log` como en `master` (salida del contenedor, `docker logs`), para no llenar el disco del servidor. Las herramientas de `tools/scripts` lo encuentran porque `error_log = /var/www/php-error.log` está en `public_html/.user.ini`, que se excluye en `.git/info/exclude` y no se sube. En otra máquina hay que crear ese archivo con esa línea y reiniciar `app`.

```sh
# Login de prueba: usuario master, clave 12345678

# Avisos de variables sin depender del log, en todo el repositorio (paso 53/54)
docker compose -f docker/docker-compose.yml exec -T app sh -c 'cd /var/www && php -d memory_limit=2G tools/scripts/undefined-guard.php --locals --fix'
# Por qué una variable no se guarda: qué archivos cree la herramienta que incluye y qué definen
docker compose -f docker/docker-compose.yml exec -T app sh -c 'cd /var/www && php -d memory_limit=2G tools/scripts/undefined-guard.php --locals --explain=html/ruta/al/archivo.php'

# Lint en PHP 8.5.12, solo lo modificado
git -c core.safecrlf=false diff --name-only | grep -E '^public_html.*\.php$' > "$TMP/mod.txt"
docker compose -f docker/docker-compose.yml exec -T app sh -c 'cd /var/www && while read f; do php -l "$f" | grep -v "^No syntax"; done' < "$TMP/mod.txt"

# PHPStan (desde tools/)
xargs -a "$TMP/mod-ps.txt" php vendor/bin/phpstan analyse -c phpstan.neon --no-progress --memory-limit=4G --error-format=raw

# Smoke (más de 10 min: en segundo plano)
docker compose -f docker/docker-compose.yml exec -T app sh -c 'cd /var/www && php tools/scripts/smoke.php --base=https://web --pass=12345678 --max=2000'

# Navegador (unos 20 min: en segundo plano)
MSYS_NO_PATHCONV=1 docker run --rm -i --network crm2_default -v C:/Users/Brahams/NeoTech/crm2-php8:/repo -w /repo/tools/navegador mcr.microsoft.com/playwright:v1.49.1-noble node consola.mjs --pass=12345678 < tools/reportes/paginas-formularios.txt

# PHPUnit: no hay phpunit.xml. Vacía crm2019_test, no crm2019. tests/Util ya se puede lanzar entero (2026-11-09): SlackTest y
# DbConnectTest::testConnectOtherDb se saltan salvo SLACK_LIVE_TEST=1 / OTHER_DB_LIVE_TEST=1 (Slack real; claves de .env a c7-develop.crm-s.net).
# JwtAuthTest falla en Docker: llama a http://localhost/api/auth desde app, que no tiene servidor web.
# CustomerForMobileShopTest::testFetchToken y ::testFetchPaidMemberFlg fallan en Docker: MOBILE_SHOP_EXTLINK_API_URL está vacía y fetchToken() devuelve false sin salir a la red.
docker compose -f docker/docker-compose.yml exec -T app sh -c 'cd /var/www && for d in Customer MobileShop Mypage SystemCore Validator Util; do vendor/bin/phpunit --bootstrap vendor/autoload.php tests/$d; done'

# Commit (el hook check-english corre solo)
php tools/scripts/preservar-eol.php
git -c core.autocrlf=false add <rutas>
git -c core.autocrlf=false commit -F mensaje.txt
php tools/scripts/bitacora.php   # regenera CHANGELOG.md
```

**Mensaje de commit:** en español, con una línea `Verificado…`, que es lo que recoge la bitácora.

## Errores que ya cometí

- **Ataques:** sin línea base, una BD caída parece una inyección. Con `AND` u `OR`, Postgres puede no evaluar `pg_sleep` (tabla vacía): usar sentencias apiladas, `-999; SELECT pg_sleep(3) --`. La primera petición a una página fría es lenta: la línea base es la segunda. Si no se dispara en el código viejo, no prueba nada: decirlo.
- **Pantallas que mueren antes del SQL:** un 200 rápido no es un "no se dispara". `model1_search.php` hace `die()` si no hay un formulario `セレクト`, `ラジオボタン` o `チェックボックス`, y la BD de prueba no tiene ninguno: hay que insertar uno temporal y borrarlo.
- **Detectores de flujo:** una reasignación dentro de un `if` no limpia la variable (`$_REQUEST['f'] = "53"` escondía un SQLi). Mirar el estado en el punto de uso, no al final del archivo.
- **`(int)` por el nombre rompe código:** `$chk_cd = " and cd <> 5"` y `$sche_flg = "出勤flg_".$day` se llaman como números y son SQL o nombres de columna. El detector ya los manda a revisión, pero tras un `--fix` masivo hay que mirar qué se asigna a cada variable convertida. El smoke lo cazó solo en una de las 5 pantallas afectadas.
- **Recorridos que no escapan:** `array_walk_recursive` solo cambia el valor si el callback lo recibe por referencia. `form2Db`, `set_form` y `escapeString` sí; `get_form` y `sanitize_and_pg_escape` no hacen nada. `fnc_set_form` depende: la de `kihon.php` no, la local de `hp/reserve` sí. Y solo afecta al superglobal que recorre: `$_POST` sigue crudo tras recorrer `$_REQUEST`.
- **Bash:** un heredoc con `<<<'A'` dentro puede cortarse: escribir el script con la herramienta de archivos. Un `cd` a `public_html` persiste entre comandos.
- **Para comparar:** copiar `git show HEAD:f` a `.orig_f` en la misma carpeta, y borrarlo al terminar.
- **Commit:** nunca en el mismo comando que lee los resultados. `git add public_html` no incluye `src/` ni `migrations-db.php`.
- **Detectores:** ignorar los comentarios (`php_strip_whitespace`): un `session.php` comentado no protege nada.
- **`h()` no es universal:** no protege sin comillas dentro de `<script>` ni en atributos sin comillas (usar `(int)`). `$partsview` es SQL y `<>` es el separador de datos del sistema.
- **Renombrar por tokens no basta:** al traducir `tools/scripts`, el lint pasaba en todo, pero las propiedades declaradas perdían sus accesos `$this->x`, los métodos se confundían con propiedades y un `array_filter($x, 'termina')` se quedaba con el nombre viejo. Solo lo vio comparar la salida de la versión vieja y la nueva: el lint no basta para validar un cambio masivo.
- **Herramientas:** editar solo los bytes afectados. Reimprimir el AST aplasta las consultas de varias líneas.
- **Git Bash:** evitar `php -r` con `$` y expresiones regulares: escribir el script en un archivo. Un `?>` dentro de un comentario `//` cierra PHP. En `shell_exec` de PHP no usar `/dev/null` ni `;`, porque corre en `cmd`.
- **Nunca pedir `system/login.php` con `activation=1`** (2026-11-19): con un `Host` distinto de `admin_master.domain` (en local, cualquiera que no sea `localhost`, por ejemplo `127.0.0.1`) borra unas 100 tablas. Las listas de `tools/reportes` traen `login.php` sin ese parámetro.
- **Nunca pedir `system/create_customer.php` ni `create_shop.php`, ni recorrer listas que las incluyan (`tools/reportes/sin-sesion-rutas.txt`, `paginas-formularios-2.txt`, `barrido-lista.txt`, `internos.txt`):** un GET sin parámetros añade 7,752 clientes y reescribe todos los que tienen `cd` entre 2249 y 10000. Me pasó el 2026-11-01 pese al aviso en `REFACTORING.md`. Las 7,752 filas se borraron, pero los campos aleatorios de las otras 22,323 quedaron con valores nuevos, sin copia de la que restaurarlos: `db/crm2019.backup` no trae clientes. Esas 22,323 son a su vez salida de este generador, de barridos anteriores, por eso `cd` tiene 2 o 3 copias. `get_shukei_data*.php` intenta además conectar con un servidor externo. **Antes de sondear sin sesión, leer el código de lo que no sea un fragmento.**
- **Escapar de más también rompe, sin error:** el `createform_sql()` del paso 37 guardaba `A''1` en vez de `A'1` en clientes, porque la confirmación ya duplica las comillas con `get_form2`. Ni el smoke ni la prueba directa de la función lo vieron: solo un flujo completo con una comilla. Otros valores pasan por una confirmación que reenvía el `$_POST` crudo (`add_record_confirm`): mirar cada caso.
- **Barrido y formularios sin destrozar datos:** copia exacta de la BD (`CREATE DATABASE ... TEMPLATE`), red aislada, probar, y devolver la copia con `DROP` y `RENAME`. `up -d` con `docker-compose.barrido.yml` **no aísla** si la red ya existe: hay que hacer `down` y comprobar `internal=true`. Después borrar los CSV que dejen en `csv_download/csv/`. El barrido pide cada archivo suelto: 328 con fatal no significa nada; se compara con el barrido anterior.
- **Fatales que el barrido no ve:** un texto vacío usado como número o como array solo revienta con ese dato o en ese camino: `'' + 1`, `date('Y', '')`, `number_format('')`. `tools/scripts/string-as-array.php` busca el segundo caso y `tools/scripts/numeric-on-text.php` el primero (necesita `columns.tsv`, sacado de `information_schema.columns`). Vaciar datos en esta BD no los dispara, porque casi no hay filas; enviar vacíos los parámetros, sí. No cubre variables corrientes: no se puede saber qué llevan. `tools/scripts/array-fn-guard.php` hace lo mismo con `count(null)`, `implode(',', '')` y similares. Sin cubrir a propósito: `pg_num_rows(false)` y similares tras una consulta fallida, que dan 500 en vez de una página vacía, casi siempre por abrir una pantalla sin su `cd`. `tools/scripts/page-fingerprint.php` compara la salida de las páginas antes y después de un cambio masivo.
- **Probar borrados toca archivos:** `coupon_deleteup.php` borra `uploads/coupon/<cd>.jpg`, y el siguiente `cd` coincide con una imagen versionada sin fila. Tras un flujo, mirar `git status` además de la BD.
- **Pantallas de configuración sobrescriben datos reales:** `free_yoyaku_register.php` cambió el nombre de la reserva libre de la tienda 1, y solo se vio al compararlo con el backup. Un flujo nuevo debe guardar y reponer lo que edita, no solo borrar lo que crea.
- **Sesión de socio a mano:** `user_id` tiene que ser numérico. `hp/user/top.php:83` hace `number_format()` sobre él y con texto da un 500 que no es un fallo real.
- **Sondear genera datos:** una exportación anónima dejó 6.8 MB de clientes en `csv/`, y `felica_detail.php` crea clientes con cualquier `shop_cd`. Revisar y borrar lo que generen las pruebas (solo lo no versionado), y contar `tb_customer` antes y después (hoy 22323). El escáner de XSS también escribe: su marca acabó en `admin_master`, `tb_shop`, `tb_coupon`, `tb_terminal` y `tb_felica_log`. Tras escanear, buscar `zqx7` en todas las columnas de texto, usando `db/crm2019.backup` en una base temporal como referencia, sin restaurar la BD.
- **Leer con `sed` engaña:** muestra igual el código dentro de `/* */`. Antes de declarar un falso negativo del detector, comprobar que la línea no está comentada.
- **Secretos y datos:** nunca imprimir valores. Medir tamaños y conteos.
- **`sed -i` en Git Bash pasa CRLF a LF en todo el archivo:** el diff sale entero. `preservar-eol.php` lo arregla, pero mejor usar Edit.
- **El número de `submitChk2()` no siempre es el `cd`:** en `recruit_category.php` es el número de fila (`form_N`). Borré la fila de al lado y hubo que reponerla. Buscar el formulario por su `cd` oculto antes de pulsar.
- **`exercise.js` envió un alta:** dejaba pasar cualquier POST cuya ruta tuviera `list`, y `my_freepage_list.php` registra con ese nombre. Ahora también descarta los botones 登録, 更新, 削除, 保存, 送信 y 変更.
- **`undefined-guard.php` no entra en heredocs:** `{$check1}` dentro de `<<<` hay que inicializarlo a mano a `null` antes del bloque. Tampoco reconoce el aviso "Undefined global variable" (`$GLOBALS['x']`): esas lecturas se guardan a mano.
- **`f.elements['3']` no es el campo "3":** en una colección de formulario, una clave numérica es el índice. Para campos con nombre numérico (las casillas de días de 営業日設定), usar `querySelector('input[name="3"]')`. Lo mismo con `document.forms.form1` cuando varios elementos se llaman `form1`.
- **`confirm()` lanzado desde un `setTimeout`** salta cuando `evaluate_script` ya terminó, y `dialogAction` no lo acepta: pulsar el botón dentro del propio script.
- **Un `(int)` puede convertir «no hace nada» en «escribe en la fila 0»:** en `master`, un `cd` que no llegaba dejaba `WHERE cd = ` y la consulta fallaba sin cambiar nada; con el `(int)` del refactor queda `WHERE cd = 0`. Solo hace daño donde existe una fila con `cd = 0`: `tb_base_system` (基本情報管理), `tb_base_system_child` y `tb_stamp_pattern` (`tb_change_model` solo se lee). Lo destapó guardar sin cambios el formulario de módulos de `system.php` (2026-11-13). Ante un `(int)` nuevo en un `WHERE` de escritura, comprobar qué pasa si el valor no llega.
- **Guardar «sin cambios» también escribe:** antes de reenviar formularios, foto de la BD (`md5` por tabla) y comparación después. La copia hecha con `pg_dump -Fc` permite reponer tabla por tabla en una transacción.
- **Barridos que cierran la sesión:** varias pantallas mandan a `logout.php` si les falta un `cd` válido (`category4/page/entry_list.php` y sus hermanas). Pedirlas sin parámetros, o con un contenido ya borrado, deja todo lo que sigue en el login. Desde el paso 56, `scan-urls.js` y `exercise.js` lo detectan y paran; antes lo daban por limpio.

## Documentos

| Archivo | Para qué | Cuándo leerlo |
|---|---|---|
| `CLAUDE.md` | Reglas del proyecto | Se carga solo |
| `REFACTORING.md` | Plan, avance por bloques, mapa de páginas sin sesión | Sección 0, si hace falta |
| `DEPENDENCIES.md` | Librerías y servicios a reemplazar | Al tocar una |
| `INTEGRATIONS.md` | Lo que se conecta desde fuera del repo | Antes de cambiar una clave o un contrato |
| `BROKEN-FILES.md` | Archivos rotos | Al encontrar otro, y al final |
| **`MANUAL-TESTS.md`** | **Mapa de la prueba a mano: una fila por pantalla, con qué se probó y los fallos de alcance general (F1 a F9)** | **Al continuar las pruebas: es el documento de trabajo** |
| `PENDING.md` | Todo lo abierto que necesita una decisión, con quién decide | Antes de preguntar algo a Antony |
| `PHP85-REPLACEMENTS.md` | Catálogo de los reemplazos de la migración, con ejemplos reales | Al hacer un cambio masivo parecido |
| `CHANGELOG.md` | Historial por paso (generado) | Casi nunca |
