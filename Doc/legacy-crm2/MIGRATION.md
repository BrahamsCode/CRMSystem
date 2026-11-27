# Migración a PHP 8.5.12: resumen y mapa de documentos

> Versión japonesa: [MIGRATION.ja.md](MIGRATION.ja.md). Rama `refactor/fase-0`, estado del 2026-11-18.
> Este documento es la entrada: qué se cambió, qué se reemplazó por qué, qué hay que hacer al desplegar y dónde está el detalle de cada cosa.

## 1. Estado

- **Origen:** producción corría **PHP 7** (7.3 o 7.4). **Destino:** **PHP 8.5.12**.
- **El código está migrado.** Los 2,564 archivos PHP del repositorio compilan en PHP 8.5.12 sin errores ni avisos. Los 10 módulos del sistema del personal se probaron a mano, pantalla por pantalla y botón por botón ([MANUAL-TESTS.md](MANUAL-TESTS.md)). Las 615 pantallas de `/system` cargan con 0 avisos de PHP, y las 48 comprobaciones automáticas de flujos (`tools/scripts/functional-flows.php`) pasan.
- **Avance:** migración ≈ 94 %; seguridad ≈ 80 % ([REFACTORING.md](REFACTORING.md), sección 0). El 6 % que falta de la migración no es código: son piezas que no existen en Docker (cron, servidor POS, datos reales, `php.ini` de producción).
- **Regla seguida:** el sistema hace lo mismo que hacía en producción con PHP 7. Donde PHP 8.5.12 habría dado un error nuevo, el código se corrigió para dar **el mismo resultado que PHP 7**. Las pocas excepciones, donde PHP 7 ya fallaba y ahora funciona, están en [PENDING.md](PENDING.md), sección 1b.
- **Fuera de alcance:** `admin/`, que conecta con una BD que tampoco existe en el servidor de desarrollo, y el módulo 女の子管理 (`category8`), retirado porque ya no se usa (oculto en el menú y 403 por URL).

## 2. Servicios y librerías reemplazados

| Qué | Antes | Ahora | Qué hay que saber |
|---|---|---|---|
| PHP | 7.3 / 7.4 | **8.5.12** (Docker: `php:8.5-fpm-trixie` + Apache, PostgreSQL 18, MailHog) | `docker/README.md` |
| Envío de correo | `mb_send_mail()` (97 llamadas) y Qdmail 2008 (decomail con imágenes) | **Symfony Mailer 8.1**: `crm_send_mail()` con la misma firma, y `CrmDecomail` con la misma interfaz que Qdmail (`public_html/inc/mail.php`) | Transporte con `MAILER_DSN` (por defecto `native://default` = `sendmail_path` del `php.ini`); `MAIL_CHARSET` UTF-8 o ISO-2022-JP |
| PDF | mPDF 5.7 (no funcionaba desde PHP 7) y una copia local de FPDF | `mpdf/mpdf` 8.3 y `setasign/fpdf` 1.9 por Composer | Facturas y recibos de señalización, informe de admin y etiquetas DM |
| JSON | Services_JSON (PEAR, 3 copias) | `json_encode()` / `json_decode()` nativos | APIs de la app (`api/mpa_*`) verificadas |
| Lectura de correo | `imap_open()` | `Pop3Simple`, un cliente POP3 propio | La extensión IMAP salió del núcleo en PHP 8.4 |
| jQuery | 34 copias, de la 1.2.3 a la 3.1.1 | **3.7.1** + Migrate 3.5.2 + capa propia (`common/js/jquery-compat-legacy.js`), **con el mismo nombre de archivo** | No se tocó ninguna página; los avisos `JQMIGRATE` en la consola señalan lo que hay que modernizar |
| jQuery UI | 1.7.2, 1.8rc3, 1.10, 1.11 | **1.13.3** + `jquery-ui-dialog-compat.js` | Los diálogos se ven igual que antes (medido contra el servidor de desarrollo) |
| Bootstrap | 3.3.5 – 3.3.7 | **3.4.1** | Solo parches de seguridad |
| Librerías de Composer | `illuminate` 7, JWT, Doctrine 2… | `illuminate/database` 13, `firebase/php-jwt` 7, `doctrine/migrations` 3.9, `vlucas/phpdotenv` 5.8, PHPUnit 12 | Doctrine 3 necesita **un SQL una vez por entorno** antes del primer `migrate` ([REFACTORING.md](REFACTORING.md), 2B) |
| Contraseñas y claves | Escritas en el código | `.env` (`env_value()`, `getenv()`) | `.env.example` trae todas las variables y los valores que estaban en el código |
| Rutas | `/home/system/...` fijas | `dirname(__DIR__, N)`: funcionan en cualquier carpeta | 1,588 rutas |
| PHP por línea de comandos | `/usr/bin/php` fijo | `crm_php_cli()` (`inc/php_cli.php`), configurable con `PHP_CLI_PATH` | Sin esto, el envío masivo no enviaba nada |
| `php_value` en `.htaccess` | Solo con `mod_php` | `.user.ini` equivalente (PHP-FPM) | 3 carpetas |
| Código muerto | PHPMailer 6.0.1, TCPDF, copias de mPDF 5.7 y de Services_JSON, `node_modules` de una demo | Borrado (405 archivos, siguen en el historial de git) | Con confirmación de Antony |

**Sin reemplazar, pendientes de decisión** ([DEPENDENCIES.md](DEPENDENCIES.md), [PENDING.md](PENDING.md)):
- **Adobe Flash**: 17 pantallas descargan el CSV del personal con un `.swf`, y ningún navegador actual lo ejecuta.
- **Módulo SNS** (`category12`): usa APIs de Twitter y Facebook que ya no existen.
- **CKEditor 4**: sin soporte; las versiones actuales son GPL o comerciales.

Detalle de cada librería: [DEPENDENCIES.md](DEPENDENCIES.md).

## 3. Cómo se cambió el código

- Los cambios masivos se hicieron con herramientas que leen PHP como el intérprete (árbol de sintaxis), no con expresiones regulares, y solo modifican los bytes necesarios. Cada herramienta comprueba que el archivo sigue compilando antes de guardarlo. Están en `tools/scripts/` ([tools/README.md](../tools/README.md)).
- **Guardas:** donde PHP 7 devolvía `null` o `false` con un aviso y la página seguía, PHP 8.5.12 lanza una excepción y la página da 500. Una guarda devuelve ese mismo valor solo en ese caso. Con datos normales no cambia nada. Ejemplo: `$r ? pg_num_rows($r) : null`.
- **Verificación:** la salida de cada página se comparó antes y después del cambio. El catálogo de casos se ejecutó en PHP 7.4.33 real, para confirmar que el resultado es el mismo que en producción.

Catálogo completo, con ejemplos reales y cantidades: [PHP85-REPLACEMENTS.md](PHP85-REPLACEMENTS.md).

## 4. Qué cambia para quien usa el sistema

| Cambio | Detalle |
|---|---|
| Los calendarios ya no saltan a enero de 1970 | 入退室一覧, 勤怠, 営業日設定 y similares: los botones de mes anterior y siguiente y el número de días del mes ahora son correctos (decidido el 2026-11-16). En producción pueden estar guardados días que no existen, como un 31 de noviembre: conviene revisarlo ([PENDING.md](PENDING.md), pregunta 10) |
| Pantallas que con PHP 7 daban error ahora funcionan | `ereg`, mPDF y algunos casos de texto vacío usado como lista ([PENDING.md](PENDING.md), 1b) |
| Más páginas piden sesión | Páginas que respondían sin login ahora exigen la sesión del personal. Los fragmentos que solo se incluyen dan 403 si se piden directamente. Las tareas de cron solo se ejecutan por línea de comandos ([REFACTORING.md](REFACTORING.md), sección B) |
| Scripts peligrosos bloqueados por web | `test_fix.php` y `fix_visit_stats.php` borraban datos con un GET de cualquiera con la sesión iniciada: ahora dan 403 por web. Por línea de comandos tampoco hacen nada (exigen sesión), y nada los llama: hoy no se pueden ejecutar |
| API `system/tool/api` | Necesita `CRM_API_KEY` en `.env`; sin ella no responde. Hay un plan para no cortar a quien la usa ([INTEGRATIONS.md](INTEGRATIONS.md)) |
| Protección CSRF | `inc/csrf.php` rechaza los envíos desde otros sitios. Tablets, quioscos y servidores no se ven afectados ([INTEGRATIONS.md](INTEGRATIONS.md)) |
| Cookie «recordarme» | Ya no guarda la contraseña, solo el usuario |
| Sin avisos de conexión | Si una BD externa no responde, la pantalla carga vacía o con su propio mensaje en japonés, sin avisos de PHP ([MISSING-TABLES.md](MISSING-TABLES.md)) |

## 5. Para desplegar

Lista completa en [PENDING.md](PENDING.md), sección 1.

0. **Docker y dependencias (2026-11-20):** los archivos de `docker/` son idénticos a los de `master`. La rama solo añade `docker/docker-compose.override.yml`, que monta la raíz del repositorio (`..:/var/www`) en `app` y `web`. Sin ese montaje no arrancan ni `master` ni la rama: `public_html/inc/db.php` carga `vendor/` y `.env` desde la carpeta de arriba de `public_html`, y el `composer install` en `/var/www` de `docker/README.md` necesita ahí `composer.json`. Docker Compose lo carga solo con `cd docker && docker compose up -d`; **no se carga si se arranca con `-f docker-compose.yml`**. Después ejecutar `docker compose exec app composer install` (`vendor/` no está en git y la rama cambió las dependencias). `my.env` desaparece al actualizar: era una plantilla que ningún código leía; el código lee `.env`, igual que `master`. **Permisos (2026-11-20):** PHP-FPM trabaja como `apache` (uid 1000, `docker/dockerfile`) y tiene que poder escribir en `public_html`. La rama añade `public_html/hp/uploads/.htaccess`, así que git crea `hp/uploads/` con el dueño de quien descarga el código: si no es el uid 1000, subir una imagen (por ejemplo en `system/category2/item/edit.php`) muestra `mkdir(): Permission denied`. Comprobarlo con `docker compose exec app su apache -s /bin/sh -c 'test -w /var/www/html/hp/uploads && echo OK'` y, si no dice `OK`, ejecutar `docker compose exec app chown apache:apache /var/www/html/hp/uploads`.
1. Copiar `.env.example` a `.env` y ajustar la BD, `MAILER_DSN`, `CRM_API_KEY` y las variables vacías que se usen en ese servidor.
2. **Cron:** las tareas programadas van por cron. El usuario de PHP-FPM debe poder ejecutar `crontab` (lo llama el propio código), y la carpeta `system/category3/mail_mag/cron_schedule/` tiene que existir y ser escribible.
3. `php.ini` de producción: `display_errors=Off`, `log_errors=On`, `expose_php=Off` y las cookies con `httponly`, `secure` y `samesite`.
4. Doctrine 3: ejecutar una vez el SQL de conversión antes del primer `migrate`.
5. `CSRF_GUARD=report` los primeros días, revisando `csrf_guard` en el log de PHP.
6. Las carpetas de subidas que el código no crea deben existir y ser escribibles ([PENDING.md](PENDING.md), 2d).
7. Restricción por IP: solo se compara `REMOTE_ADDR`. Si el servidor va detrás de un proxy, nadie verá esas funciones ([INTEGRATIONS.md](INTEGRATIONS.md)).
8. Cambiar en producción las contraseñas y claves que estaban en el código, revocar el webhook de Slack y actualizar `.env.example` con los valores nuevos.

## 6. Preguntas abiertas para el equipo de Japón

Lista completa en [PENDING.md](PENDING.md), sección 7. Respondidas: la 2 (cron) y la 10 (calendarios: se quedan corregidos).

- **Urgente (2026-11-19), afecta ya a producción:** ¿se sigue usando `system/login.php?activation=1` para dar de alta sistemas? Sin sesión, y con un dominio distinto del de `admin_master.domain` (basta cambiar la cabecera `Host`), esa URL borra unas 100 tablas, clientes incluidos. El código es el mismo en `master` ([PENDING.md](PENDING.md) 4.0, pregunta 13).
- ¿Se puede tener un servidor POS de prueba y un staging con datos reales? (Sin eso, el POS y los cálculos con datos reales no se han probado.)
- ¿De quién es la IP de desarrollo de `DEVELOPMENT_IPS`, y producción irá detrás de un proxy?
- ¿Sigue existiendo el servidor `202.218.x.x`?
- ¿Alguien llama a `create_customer.php` o `create_shop.php`? ¿Qué dispositivo envía `CRMPOS-<id>`? ¿Quién usa el CTI, `tool/api` y `get_shukei_data`?
- ¿Se siguen usando `category11`, las señalizaciones de `touchcation` y el módulo SNS?
- ¿Qué editor de texto enriquecido usar, y qué columnas de la BD guardan HTML a propósito?

## 7. Mapa de documentos

| Documento | Idiomas | Contenido |
|---|---|---|
| **MIGRATION** (este) | español, [japonés](MIGRATION.ja.md) | Resumen y mapa |
| [DEPENDENCIES.md](DEPENDENCIES.md) | español, [japonés](DEPENDENCIES.ja.md) | Librerías y servicios reemplazados, una por una |
| [PHP85-REPLACEMENTS.md](PHP85-REPLACEMENTS.md) | español, [japonés](PHP85-REPLACEMENTS.ja.md) | Cada cambio de código hecho para PHP 8.5.12: antes, después, por qué y cuántos |
| [INTEGRATIONS.md](INTEGRATIONS.md) | español, [japonés](INTEGRATIONS.ja.md) | Tablets, quioscos, APIs y servidores que dependen de este código, y cómo desplegar sin cortarlos |
| [PENDING.md](PENDING.md) | español, [japonés](PENDING.ja.md) | Todo lo que necesita una decisión: despliegue, cambios de comportamiento, archivos rotos, seguridad aparcada y preguntas para Japón |
| [BROKEN-FILES.md](BROKEN-FILES.md) | español, [japonés](BROKEN-FILES.ja.md) | Archivos que no funcionan (casi todos ya fallaban antes de la migración) |
| [MISSING-TABLES.md](MISSING-TABLES.md) | español, [japonés](MISSING-TABLES.ja.md) | Tablas, datos y servidores que faltan en el entorno local, y qué mostrará cada pantalla cuando existan |
| [REFACTORING.md](REFACTORING.md) | español | Plan, porcentajes por bloque y detalle de los cambios de seguridad |
| [CONTINUE.md](CONTINUE.md) | español | Estado, decisiones tomadas y cómo seguir trabajando |
| [MANUAL-TESTS.md](MANUAL-TESTS.md) | español | Registro de las pruebas manuales, una fila por pantalla |
| [CHANGELOG.md](CHANGELOG.md) | español | Historial generado desde los commits (`tools/scripts/bitacora.php`) |
| [tools/README.md](../tools/README.md) | español | Herramientas de verificación y de reemplazo |
| [README.md](../README.md) | japonés | README original del proyecto |

La versión en español es la de referencia: si las dos dicen cosas distintas, vale la española, y la japonesa se actualiza.
