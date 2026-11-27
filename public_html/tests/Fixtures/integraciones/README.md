# Fixtures de contrato de integraciones (S5)

Respuestas **reales** del legacy (crm2-php8, PHP 8.5) a la API JWT de la tienda móvil (`/api/*`) y a la API de la app マイページ (`/api/mpa_*.php`). Las pruebas de contrato de Laravel comparan sus respuestas con estas, byte a byte.

| Carpeta / archivo | Qué es |
|---|---|
| `legacy/manifest.json` | Un caso por petición: `name`, `method`, `path`, cabeceras, cuerpo (`json`/`form`), `auth` (con el JWT de `api_auth_ok`) y la respuesta: `status`, `content_type`, `location`, `body_file` |
| `legacy/<caso>.body` | Cuerpo exacto de la respuesta (sin conversión de fin de línea: `.gitattributes`) |
| `legacy-seed.sql` | Datos de prueba (3 socios, saldo de puntos, passcode de la tienda 1, un producto canjeable) sobre el esquema del legacy + `scripts/bd-legacy.sh` |
| `keys/test-only-*.pem` | Par RSA **solo para pruebas**, generado para grabar estas fixtures. No es la clave de producción |
| `tools/` | `cases.json` (los casos), `capture.php` (los ejecuta y graba) y `vhost-fixtures.conf` (Apache del legacy apuntando al FPM de las fixtures) |

## Cómo se grabaron (2026-11-27)

- Copia de `crm2-php8` (sin `db/`, `hp/`, touchcation ni category11) con `composer install`, servida con **Apache + PHP-FPM** como el Docker del legacy (`docker/000-default.conf`, `docker/php.ini` con `display_errors = Off`, como producción). `php -S` no sirve: el legacy lee `filter_input(INPUT_SERVER, …)`, que el servidor embebido no rellena.
- BD `crmsystem2_fixtures_s5` en el Postgres de crmsystem2: `database/schema/pgsql-schema.sql` + `bd-legacy.sh` + `legacy-seed.sql`, recreada antes de cada captura (los casos `point_use`, `point_exchange` y `setraiten` modifican datos; se ejecutan en el orden del manifiesto).
- `.env` del legacy con valores de prueba: `MOBILESHOP_API_USER=fixture-user`, `MOBILESHOP_API_PASS=fixture-pass`, `MOBILESHOP_LIST=test` (la BD «test» es la misma), `MOBILE_SHOP_EXTLINK_API_URL` inalcanzable (no se llama a extlink real), claves de `keys/`.
- Nunca contra producción ni con `activation=1`.

Para volver a grabar: levantar los contenedores `s5-legacy-fpm` (imagen `crmsystem2-app`, la copia montada en `/var/www`) y `s5-legacy-web` (imagen `crmsystem2-web` con `tools/vhost-fixtures.conf`) en la red `crmsystem2_default`, recrear la BD y ejecutar `php tools/capture.php http://s5-legacy-web <salida>` dentro de `s5-legacy-fpm`.

## Valores que cambian en cada captura

El JWT de `api_auth_ok` (claims `iat`/`exp`), y `touroku_date` / `update_date` de las filas que crean `point_use`, `point_exchange` y `setraiten`. Las pruebas de contrato los normalizan; todo lo demás se compara exacto.

## Lo que mostraron (y corrige o precisa el análisis 07)

- **Sin cabecera `Authorization`** el legacy no responde `{"status":"ng"}`: da un **fatal, HTTP 500 sin cuerpo** (`api_shop_without_authorization`, `ms_passcode_without_authorization`). Con un token mal formado sí responde `{"status":"ng","message":"Syntax error, malformed JSON"}` con 200.
- **`/api/<desconocido>`**: fatal, 500 sin cuerpo (`404.php` no existe). Laravel responderá 404 (decisión del diseño, §6).
- **`GET /api/customer` sin filtros**: 200 con **cuerpo vacío** (no JSON). Sin resultados: `"result":""`.
- **`GET /api/mobile_shop/customer/<x>`** busca por **`tb_customer.id`** (el id público), no por `cd`: con un `cd` devuelve todas las claves en `null` (`ms_customer_detail_by_cd`).
- **Tipos:** `/api/shop`, `/api/customer` y `mobile_shop/point` salen de `pg_fetch_*` (todo texto: `"cd":"1"`); `/api/point`, `mobile_shop/customer/<id>` y `sales_items` salen de Eloquent/PDO (enteros como números: `"cd":12`, `"tb_customer_cd":1609897927001809158`). `LegacyJson::pgText()` solo se aplica en los primeros.
- **`mobile_shop/passcode`** compara con `==`: `"04321"` coincide con `"4321"` (`ms_passcode_leading_zero`).
- **`point_use` / `point_exchange_item`** responden `{"status":"ok","res":"<n>ポイントを削減しました。"}` sin envoltorio.
- **`fetch_paid_member` con extlink caído**: `"result":false` con 200.
- **`mpa_*`**: `mpa_corresponding` sale como `text/html` (sin `header()`), `mpa_getTopBanner`/`mpa_getCoverFlow` como `application/json; charset=utf-8` (minúsculas). Sin imágenes configuradas, las claves de imágenes (`application_main_image`…, `data`) no aparecen.
- **`mpa_setraiten` con un socio válido**: el legacy registra la visita y luego da un **fatal (500)** en la línea 43 (`Cannot access offset of type string on string`); la segunda llamada responde el error de intervalo. Con `membership=1` (beacon) responde `{"status":"ok","customer_info":{…}}`.
- **`mpa_albumUpdate` sin imagen**: 302 a `…myAlbumPage?domain=…&cd=…&status=err2&comment=` (`err2`, no `err1`).
