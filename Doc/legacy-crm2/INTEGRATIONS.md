# Integraciones: qué se conecta con este CRM

> Versión japonesa: [INTEGRATIONS.ja.md](INTEGRATIONS.ja.md). Resumen de la migración y mapa de documentos: [MIGRATION.md](MIGRATION.md).

> **Por qué existe:** cambiar una clave o un contrato aquí puede romper tablets, quioscos u otros servidores que no están en este repositorio. Antes de tocar algo de esta lista, hay que saber quién lo usa.
> Actualizado: 2026-10-31. Medido en el código y en las 6 APK del repo. **Lo que está fuera del repo no se pudo ver**: esa es justamente la pregunta para tu jefe.

## Resumen

| Integración | Quién llama | ¿Está el cliente en el repo? | Autenticación | Riesgo de cambiarla |
|---|---|---|---|---|
| Terminal POS (`pos_session.php`, 62 archivos lo cargan) | App o tablet que envía `User-Agent: CRMPOS-<id>` | **No** | Ver abajo | **Alto** |
| Quiosco Touchcation (`jp.CrmShWeb`) | APK en `version/` (6 versiones): `WebView` + NFC | Sí, solo el `.apk` compilado | `terminal_cd`, `androidId` en la URL | Alto: hay que publicar una APK nueva |
| API `system/tool/api/*` (9 endpoints) | Desconocido: ni el repo ni las 6 APK la llaman | **No** | `key`, ver abajo | Medio: paso 35 |
| `get_touroku_data.php`, `get_shukei_data*.php` | Desconocido: ningún archivo del repo las llama | **No** | Ninguna | Medio |
| `system/login.php?activation=1` (2026-11-19) | Parece el alta de un sistema nuevo copiado de una plantilla: inicializa la BD (`db_fix.php`) y reescribe `inc/db.php`. Ningún archivo del repo la llama | **No** | Ninguna: sin sesión, basta un `Host` distinto de `admin_master.domain` | **Crítico** ([PENDING.md](PENDING.md) 4.0) |
| `system/create_customer.php`, `system/create_shop.php` | Parecen generadores de datos de prueba, no integraciones. Ningún archivo del repo las llama | **No** | Ninguna | **Crítico si siguen así, ver abajo** |
| Programa CTI de escritorio (`desktop_request/*`) | Programa de escritorio | **No** | Ninguna | Medio |
| Servidores POS de cada tienda (`$posConnect`) | El CRM se conecta a ellos | No aplica | `POS_DB_PASSWORD` en `.env` | Alto: BD compartida |
| Enlaces con `md5(login_pass)` | `system/category3`, `hp/user`, `system/class`, `touchcation/*` | Parcial | Hash de la contraseña en la URL | **Alto**: bloquea migrar a `password_hash` |
| API extlink de la tienda móvil (salida) y buzón POP3 de `mm_add.php` | Este sistema llama a ellos (`src/MobileShop/CustomerForMobileShop.php`, `hp/mailsyori/mm_add.php`) | No (son servidores de fuera) | Clave en `.env` desde el 2026-11-18: `MOBILESHOP_EXTLINK_PASS`, `MAIL_ADD_POP3_PASS`. Antes, escritas en `src/` | **Ninguno si se copia el valor**: es el mismo; sin él en el `.env` del servidor, las dos dejan de autenticar (`PENDING.md` 1.8b) |
| Dominios externos en el código | `coupon.site-one.net`, `hal456.net`, `extlink.co.jp`, `b.g-api.net`, `gos00096.go-server.jp`, `crm-s.jp`, `smatopi.net` | No aplica | Variable | Sin revisar uno por uno |

## `create_customer.php` y `create_shop.php`: sin corregir, pendiente de Antony

Medido el 2026-11-01 en Docker (una petición GET anónima, sin parámetros, lo disparó):

- `create_customer.php` recorre `cd` de 2249 a 10000: inserta una fila por `cd` y luego hace `UPDATE ... WHERE cd = $i`. **Sobrescribe nombre, sexo, nacimiento, tienda, `id`, `login_id` y pone `login_pass = '0000'` a cualquier cliente con ese `cd`**, y deja `hatuban.kazu` de `tb_customer` en 10001, lo que puede repetir códigos ya usados.
- `create_shop.php` hace lo mismo con `tb_shop` (`cd` de 4 a 100, sin `INSERT`) y deja `hatuban.kazu` de `tb_shop` en 101.
- `tb_customer` no tiene ninguna restricción de unicidad, así que repetirlo duplica clientes.

**Propuesta:** responder 403 por web, como los fragmentos del paso 34. No se ha aplicado porque el cliente, si existe, estaría fuera del repositorio.

## Procesos en segundo plano y cron

`inc/php_cli.php` resuelve la ruta de PHP, porque `/usr/bin/php` no existe en `php:8.5-fpm` (está en `/usr/local/bin/php`); `PHP_CLI_PATH` en `.env` la fija a mano. `crontab` no está en la imagen, así que las tareas programadas no corren en Docker. En producción se ejecutan por cron (respuesta de Japón, 2026-11-18): el servidor nuevo necesita cron y que PHP-FPM pueda usar `crontab` (`PENDING.md` 1.1). El informe de fin de envío masivo va a la dirección de `admin_master.system_mail` (en la BD de prueba, una de `extlink.co.jp`): en Docker se queda en MailHog, en producción saldría a ese destinatario.

## Restricción por IP (medido el 2026-11-13)

No hay reglas de IP en Apache ni en `.htaccess`: todo está en PHP y solo mira `REMOTE_ADDR` (no `X-Forwarded-For`).

- **`Common::isDevelopmentAuthorityFromIP()`** (`src/Util/Common.php`): `true` si la IP está en `Constant::DEVELOPMENT_IPS` (una sola, `58.13.x.x`) o si `STAGE=docker`. La usan 18 archivos: 6 entradas del menú (`left_navi0`, `left_navi1`), el bloque マスタ管理 de PCHP管理 (`left_navi4`), `mail.php` y `mail_send.php` enteras, y partes de `system.php`, `features_config`, `db_config`, `backup`, `export_all`, `db_export2`, `shop_list`, `shop_detail`, `stamp_config` y `kiosk/display_*`. Varias de esas partes también se abren con la sesión de soporte (`RELATION_LOGIN_PASS`, `SUPPORT_MASTER_PASS`).
- **`category0/config/db_bigint.php`**: su propia lista de 3 rangos, distinta, para exportar.
- **`left_navi5.php`**: 帳票管理 y las dos シフト solo salen en el servidor `202.218.x.x` (`SERVER_ADDR`, la IP del servidor, no la del visitante).
- `admin/inc/common.php:47`: una lista de 5 IP, comentada.

**Al desplegar:** si producción va detrás de un proxy, `REMOTE_ADDR` será la del proxy. Antes de tocar la lista o la forma de leer la IP, preguntar quién la usa (`PENDING.md`, 0.17 y preguntas 11 y 12).

## CSRF: qué pueden notar los clientes externos

`public_html/inc/csrf.php` solo actúa con login y solo si el navegador declara que la petición viene de otro host (`Sec-Fetch-Site`, `Origin`, `Referer`). Los clientes sin esas cabeceras (tablets, quioscos, servidores) pasan igual. Las 6 APK no llevan páginas HTML propias.

**Al desplegar:** poner `CSRF_GUARD=report` en `.env`, buscar `csrf_guard` en el log de errores de PHP durante unos días y luego quitarlo. Si un proxy cambia la cabecera `Host`, los navegadores modernos siguen pasando por `Sec-Fetch-Site`; un navegador sin esa cabecera sería rechazado.

## Terminal POS: por qué no se cambia todavía

`system/pos_session.php` identifica la terminal de tres formas, en este orden:

1. `?unique_id=X&unique_key=md5(X . "key")`: **el token se puede calcular**, porque la palabra `key` está fija en el código.
2. `$_SESSION['terminal_id']` de una visita anterior.
3. `$userAgent['UNIQUE_ID']`: el texto tras `CRMPOS-` en la cabecera `User-Agent`, **sin ningún secreto**.

Después busca ese ID en `tb_pos_terminal` y, si existe, abre sesión con la tienda de esa terminal.

**Consecuencias:**
- Cambiar solo `md5(...)` **no arregla nada**: la vía 3 permite entrar con el mismo ID sin token. El secreto real hoy es que nadie conozca un ID de terminal.
- Las líneas comentadas de `pos_session.php` indican que la vía 1 se creó para pruebas (`テスト用`) y fija el modo `TABLET`.
- Nada en el repo ni en las 6 APK genera `unique_key` ni `CRMPOS`: el cliente real está fuera. Un cambio a ciegas puede dejar sin servicio las terminales.

**Qué preguntar a tu jefe antes de tocarlo:**
1. ¿Qué app o dispositivo envía `CRMPOS-<id>`? ¿Quién la mantiene y se puede actualizar?
2. ¿Otros proyectos (`possystem`, por ejemplo) comparten este contrato o esta tabla?
3. ¿Alguien usa de verdad `unique_id` + `unique_key`, o quedó de las pruebas?
4. ¿Cuántas terminales hay en producción? En la BD local `tb_pos_terminal` tiene 0 filas.

**Propuesta, cuando se sepa lo anterior:** un secreto por terminal guardado en `tb_pos_terminal` y enviado en una cabecera; primero en convivencia con el método actual y registrando quién lo usa, y luego retirando el antiguo.

## API `tool/api`

- **Antes:** `key = md5("VIZAN" . HTTP_HOST . "GLORY")`, calculable por cualquiera. Medido: `searchCustomerList.php` devolvía 194,583 bytes de clientes sin login.
- **Ahora:** secreto `CRM_API_KEY` en `.env`, comparado con `hash_equals`. Si no está definido, la API no responde (paso 35).

### Al desplegar en producción

Los servidores que llaman a la API siguen enviando la clave antigua. Para no cortarles el servicio:

1. Calcula la clave actual de cada dominio con la fórmula de arriba y ponla como `CRM_API_KEY` en el `.env` de ese dominio. Todo sigue funcionando, **pero la clave sigue siendo calculable**.
2. Identifica quién llama: registra en los logs de acceso las peticiones a `/system/tool/api/`.
3. Genera una clave aleatoria (`openssl rand -hex 32`), dásela a esos servidores y cámbiala en `.env`. Hasta este paso el problema no queda resuelto.

Si `.env` se despliega sin esta variable, **la API deja de responder** a todos: es lo seguro, pero corta a quien la use.
