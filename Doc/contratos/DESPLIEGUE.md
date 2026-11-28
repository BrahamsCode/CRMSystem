# Despliegue de `laravel13` en un servidor (S1)

Qué falta para llevar `laravel13` a un servidor. Revisado sobre `laravel13` 85a2f10 (2026-11-27), solo leyendo:

- `Dockerfile`, `docker-entrypoint.sh`, `render.yaml`, `.dockerignore`, `DEPLOY_RENDER.md` (raíz);
- `docker/` (desarrollo);
- `public_html/config/`, `.env.example`, `bootstrap/app.php`, `public/.htaccess`;
- los `ServiceProvider` que programan tareas;
- `Doc/qa/llamadas-externas.md` y `Doc/contratos/NUCLEO.md`.

No se construyó la imagen ni se desplegó nada. Lo marcado **(verificar)** se dedujo del código y hay que confirmarlo en el primer build.

**Actualización (2026-11-27):** los problemas de las secciones 1, 2, 3 (`.env.example`), 8 y 11 (proxies) ya están corregidos; el estado está en la sección 13. Las tablas siguientes se dejan como el diagnóstico de partida.

Leyenda: **Bloquea** = sin esto no funciona o es inseguro. **Falta** = hay que hacerlo antes de producción. **Mejora** = recomendable.

## 1. Imagen: `Dockerfile` de Render

| Punto | Estado | Qué hacer |
|---|---|---|
| Base `php:8.5-apache-trixie` (mod_php, un solo contenedor) | OK | Igual versión que `docker/dockerfile` (8.5 FPM + Apache aparte); solo cambia la forma de servir. `composer.json` pide `php ^8.3`: no molesta. |
| Extensiones | OK | `pdo_pgsql pgsql intl zip gd pcntl exif bcmath imagick` = las de `docker/dockerfile`; `install-php-extensions` ya compila gd con freetype y jpeg. `mbstring` y `opcache` vienen en el núcleo de 8.5. `pdo_mysql` sobra (inofensiva). No hace falta `msmtp`: Laravel envía por SMTP directo. |
| **`public_html/.env` entra en la imagen** | **Bloquea** | `.dockerignore` pone `.env`, que solo cubre la raíz del contexto: el `.env` de desarrollo de `public_html/` se copia con `COPY public_html /var/www` (`APP_ENV=local`, `APP_DEBUG=true` y lo que tenga la máquina que construye). Añadir `public_html/.env` y `public_html/.env.*` a `.dockerignore`. También `public_html/storage/app/public/*` y `public_html/storage/app/private/*` (subidas de desarrollo). |
| **Apache ignora `.htaccess`** (verificar) | **Bloquea** | El `sed` cambia `<Directory /var/www/>` por `/var/www/public`, pero conserva el `AllowOverride None` de Debian. Sin `.htaccess` no hay reescritura a `index.php` (todas las URL salvo `/` darían 404) ni atajo de estáticos `/system/...` → `public/legacy/`. Copiar un `000-default.conf` de producción con `AllowOverride All` (como `docker/000-default.conf`, sin el `SetHandler proxy:fcgi`) o añadir `<Directory ${APACHE_DOCUMENT_ROOT}> AllowOverride All </Directory>`. |
| `php.ini` | **Falta** | Se copia `docker/php.ini`, que es de desarrollo: `display_errors = On` y `opcache.validate_timestamps = 1`. Hace falta un `docker/php-production.ini` con `display_errors = Off`, `log_errors = On`, `opcache.validate_timestamps = 0` (el código no cambia dentro de la imagen) y los mismos `memory_limit 512M`, `upload_max_filesize`/`post_max_size 256M`, `mbstring.language = Japanese` y `date.timezone = Asia/Tokyo`. |
| `LimitRequestBody` | Mejora | En desarrollo es de 256 MB (`docker/000-default.conf`). En la imagen de Render se queda el valor por defecto de Apache, que hoy no limita. Ponerlo igual que `post_max_size`. |
| Política de ImageMagick | **Falta** | En desarrollo se monta `docker/policy.xml` en `/etc/ImageMagick-7/policy.xml`; la imagen de Render usa la de Debian. Copiarla (`COPY docker/policy.xml /etc/ImageMagick-7/policy.xml`; comprobar la ruta de la versión de trixie). La usan `PointSettingImageStore`, `PhotoAlbumUploader` y `AppImageResizer`. |
| Zona horaria | OK | `TZ=Asia/Tokyo` y `/etc/localtime`, como el legacy (`config/app.php` también). |
| Node 24 + `npm ci && npm run build`, luego `rm -rf node_modules` | OK | Ya corregido (Vite está en devDependencies). |
| Permisos | OK con matiz | `chown -R www-data /var/www` + `755` en `storage` y `bootstrap/cache`. El dueño es `www-data`, así que alcanza. Si `storage` va en un disco persistente (sección 6), el disco se monta después del build: el entrypoint debe hacer `chown -R www-data:www-data storage` y crear `storage/framework/{cache,sessions,views}`, `storage/logs` y `storage/app/public`. |
| Fuentes IPAex | **Falta** (si hay PDF con nombres) | Ver sección 8. |
| Claves `keys/*.pem` | **Falta** | Ver sección 3. |

## 2. `docker-entrypoint.sh` (corre en cada arranque)

| Línea | Problema | Qué hacer |
|---|---|---|
| `cp .env.example .env` si falta | **Bloquea** | `.env.example` es de desarrollo: `APP_ENV=local`, `APP_DEBUG=true`, `CRM_GAPI_ENABLED=false`, `CRM_NOTIFY_*` @example.com. Las variables del panel de Render tienen prioridad sobre `.env`, pero toda variable que falte en el panel tomaría el valor de desarrollo (por ejemplo `APP_DEBUG=true` muestra trazas a cualquiera). Quitar la copia: en producción la configuración sale solo de las variables del servicio. |
| `key:generate` si `.env` no tiene `APP_KEY` | **Falta** | Escribe una clave nueva en un `.env` que se pierde en cada despliegue. Si `APP_KEY` no está en el panel, cada arranque cambia la clave y cierra todas las sesiones. Quitarlo: `APP_KEY` se fija una vez (`render.yaml` ya pone `generateValue: true`). |
| **`php artisan migrate --force`** | **Bloquea** | No hay migraciones (`database/migrations` vacío): `migrate` sobre una BD sin tabla `migrations` carga `database/schema/pgsql-schema.sql` con `psql`, que sigue aunque falle. Sobre la BD de producción del legacy intentaría crear las 184 tablas: fallaría en las que existen y crearía las que faltan (Laravel base, `sales_items`, `smatopi_message`…). No borra nada (el archivo no tiene `DROP` ni `INSERT` salvo en `migrations`), pero cambia el esquema de producción sin control. Quitarlo del entrypoint. El esquema de producción se prepara una vez, a mano (sección 9). |
| `config:cache`, `route:cache`, `view:cache` | OK | Ver sección 10. Mejor `php artisan optimize` (incluye `event:cache`). |
| `cache:clear` después | OK | Con `CACHE_STORE=file` vacía `storage/framework/cache/data`; no toca la caché de config. |
| `pg_isready -h ${DB_HOST:-db}` | Mejora | Esperaría para siempre si la BD no responde. Poner un límite de intentos. |
| No arranca el planificador | **Falta** | Ver sección 4. |

## 3. Variables de entorno de producción

Valores **solo en el panel del hosting o en el `.env` del servidor**, nunca en git. Los valores reales del legacy están en el `.env` de producción de crm2 (`Doc/legacy-crm2/`: se pueden leer, no copiar a archivos versionados).

### Laravel

| Variable | Producción | Notas |
|---|---|---|
| `APP_ENV` | `production` | Enciende g-api (`CRM_GAPI_ENABLED`) y extlink (`MOBILESHOP_EXTLINK_ENABLED`) por defecto. |
| `APP_DEBUG` | `false` | |
| `APP_KEY` | generada una vez | `php artisan key:generate --show`. Si cambia, se pierden las sesiones (los datos del legacy no se cifran con ella). |
| `APP_URL` | `https://<dominio>` | La usan `asset('storage/…')` (cupón de la メルマガ) y la URL del disco `public`. |
| `APP_LOCALE` | `ja` | `.env.example` y `render.yaml` dicen `es`: los textos de pantalla están en `lang/ja`. |
| `LOG_CHANNEL` / `LOG_STACK` / `LOG_LEVEL` | `stack` / `daily` / `warning` | En Render: `stderr` para verlo en el panel. |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | `file`, `file`, `sync` | Convención del proyecto (CONVENCIONES §0, NUCLEO §9). `render.yaml` pone `database` en los tres: corregir o decidir (sección 6, «Decide Antony»). |
| `SESSION_SECURE_COOKIE` | `true` | Con HTTPS. `SESSION_DOMAIN` vacío (un dominio por instalación). |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | la BD del legacy | `pgsql`. `DB_SSLMODE=require` si la BD está en otra máquina. |

### Correo

| Variable | Producción | Notas |
|---|---|---|
| `MAIL_MAILER` | `smtp` | Por defecto `log`: no sale nada. |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME` | del proveedor | El legacy usaba `MAILER_DSN`; traducirlo a estas variables o a `MAIL_URL`. |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | los del legacy (`MAIL_FROM`) | Algunos envíos usan el remitente de `admin_master.system_mail`. |
| `CRM_NOTIFY_SHOP_CREATED` | sin definir (= `support@glory.ne.jp`, como el legacy) | Fuera de producción poner una dirección propia. |
| `CRM_NOTIFY_CONTACT` | sin definir (= `crm@extlink.co.jp`, como el legacy) | Igual. |
| `SIGNAGE_CONTACT_NOTIFY` | dirección del aviso de お問い合わせ de GoTel | Vacío = no envía (como el legacy si no hay destinatario). |

### Integraciones y soporte

| Variable | Producción | Notas |
|---|---|---|
| `CRM_GAPI_ENABLED` | sin definir (= encendido con `APP_ENV=production`) | Quitar el `false` que trae `.env.example`. Con `push_api_host = 1` en `admin_master` apunta a b.g-api.net de producción. |
| `MOBILESHOP_EXTLINK_ENABLED` | sin definir (= encendido con `APP_ENV=production`) | No dejarlo vacío: vacío = apagado. |
| `MOBILE_SHOP_EXTLINK_API_URL`, `MOBILESHOP_EXTLINK_PASS` | las del legacy | Vacía la URL = no llama. El cliente usa `withoutVerifying()` (no comprueba TLS, como el legacy). |
| `MOBILESHOP_API_USER`, `MOBILESHOP_API_PASS`, `MOBILESHOP_LIST` | las del legacy | API de la tienda móvil (S5). |
| `MOBILESHOP_PRIVATE_KEY`, `MOBILESHOP_PUBLIC_KEY` | ruta a los `.pem` | Por defecto `base_path('../keys/…')` = `/var/keys/` en la imagen, que no existe. En Render: *Secret Files* (`/etc/secrets/private.pem`) y estas dos variables con esa ruta. Los `.pem` vienen del servidor del legacy (JWT RS256 de `MobileShopJwt`). |
| `CRM_API_KEY` | la del legacy | Clave de `system/tool/api/*` (S5). |
| `RELATION_LOGIN_PASS`, `SUPPORT_MASTER_PASS` | las del legacy | Login de soporte (NUCLEO §2). |
| `CRM_SUPPORT_API_HOST` | sin definir (= `b.g-api.net`) | |
| `CRM_DEVELOPER_IPS` | IP fija del proveedor (la `58.13.x.x` de `Constant::DEVELOPMENT_IPS`) | Abre `nucleo.maintenance` y `nucleo.shop-create`. Depende de los proxies de confianza (sección 11). |
| `STAGE` | vacío | `docker` = todo el mundo es desarrollador. |
| `CRM_VERSION` | la del legacy | Hoy vale por defecto `2.2.2`; S0 anotó `crm_version` 2.704. Confirmar el valor que muestra システム管理 en producción. |
| `CRM_WORKING_REPORT` | `true` solo si se usa 帳票管理 | El legacy lo mostraba solo en el servidor `202.218.x.x`. |
| `CRM_PDF_DEFAULT_FONT`, `CRM_PDF_FONT_DIR` | `ipaexm` y la carpeta de las fuentes | Sección 8. |
| `DB_SIGNAGE_*`, `DB_BEACON_*` | sección 7 | |

**Faltan en `.env.example`** (existen en `config/` pero no en la plantilla): `STAGE`, `CRM_DEVELOPER_IPS`, `RELATION_LOGIN_PASS`, `SUPPORT_MASTER_PASS`, `CRM_SUPPORT_API_HOST`, `CRM_VERSION`, `CRM_WORKING_REPORT`, `CRM_PDF_FONT_DIR`, `CRM_PDF_DEFAULT_FONT`, `DB_SIGNAGE_SCHEMA`, `DB_BEACON_TIMEOUT`, `DB_BEACON_SSLMODE`. Añadirlas vacías o comentadas (tarea de S1, en la lista de la sección 13).

## 4. Planificador y cola

### Tareas programadas (`schedule:run` cada minuto)

Reemplazan el crontab que el legacy reescribía desde PHP (`cSendSchedule`). Todas con `withoutOverlapping()`; varias deciden por sí mismas si les toca (`when()` o la hora de `admin_master`), así que deben evaluarse **cada minuto**:

| Tarea | Frecuencia | Dónde |
|---|---|---|
| Limpieza de `system/uploads` (más de 1 día) | cada hora | `NucleoServiceProvider` |
| Limpieza de temporales de fotos de cliente | cada hora | `ClientesServiceProvider` |
| `clientes:asignar-rangos --tipo=…` (a la hora de `admin_master.<tipo>_rank_shori_time`) | cada minuto | `ClientesServiceProvider` |
| `SendMailCommand` (メルマガ programada), `PointShikkouCommand`, `StampShikkouCommand`, `PaidMemberPointCommand` | cada minuto | `PromocionServiceProvider::TASKS` |
| `SendFollowPushCommand`, `RegisterPointCommand` | cada 5 minutos | ídem |
| `CleanTempCommand` | diaria | ídem |
| `GrantRegisterPointsCommand` (cada 5 min), `GrantPaidMemberPointsCommand` (10:00), sellos (cada minuto) | ver proveedor | `PromocionFidelizacionServiceProvider` |

**Hallazgo (2026-11-27, `schedule:list` en la imagen):** `promocion:stamp-shikkou` está registrada **dos veces** cada minuto, desde `PromocionServiceProvider::TASKS` y desde `PromocionFidelizacionServiceProvider`. Para S3: dejar una sola.

Cómo correrlo:

- **VPS o servidor propio:** crontab de `www-data`: `* * * * * cd /var/www && php artisan schedule:run >> /dev/null 2>&1`.
- **Render:** un *Cron Job* de Render corre en otro contenedor, sin el disco del web. Las limpiezas (`system/uploads`, temporales) y lo que escribe en `storage/app/public` no verían los archivos del servicio web. Opciones:
  1. Correr `php artisan schedule:work` dentro del mismo contenedor que Apache (supervisord, o en segundo plano desde el entrypoint con reinicio).
  2. Llevar los archivos a S3 (otra decisión).

  Con un solo contenedor, `withoutOverlapping` funciona con `CACHE_STORE=file`. Con varias instancias del web, hace falta `->onOneServer()` y una caché compartida (`database` o `redis`).

### Cola

- Convención: `QUEUE_CONNECTION=sync`. `ContactMail`, `ShopCreatedMail` y `ContactReceivedMail` (`ShouldQueue`) se envían dentro de la petición.
- `SendMailmagJob` (envío inmediato de la メルマガ) va con `dispatch()->afterResponse()`: corre en el mismo proceso de Apache después de responder.
  - Para listas grandes le afectan `max_execution_time = 60` y el tiempo de vida del worker.
  - El legacy lo lanzaba como proceso en segundo plano.
  - **(Verificar)** con una lista del tamaño real antes de producción.
  - Si no alcanza, pasar a `QUEUE_CONNECTION=database` (las tablas `jobs`/`failed_jobs` ya están en el esquema) con un worker `php artisan queue:work --tries=1` en el mismo contenedor que el planificador. Es una decisión de S0 con S3, porque cambia la convención.

## 5. Disco: `storage:link` y permisos

- `php artisan storage:link` crea `public/storage` → `storage/app/public`. Hace falta para `asset('storage/system/category3/coupon/…')` (cupón en la メルマガ). Ponerlo en el entrypoint (`storage:link --force`; es idempotente).
- El resto de archivos públicos se sirve por rutas propias: `/hp/uploads/{path}`, `/system/uploads/…` y las de señalización. Leen el disco `public` y no dependen del enlace.
- Escribe en `storage/app/public`:
  - `hp/uploads/c1|c2|c3|c5|c6|c7|c9`;
  - `system/uploads`;
  - `system/category1/customer`;
  - `system/category3/{coupon,mail_mag,follow,auto,digital_signage}`;
  - las subidas de señalización.

  Todo debe **persistir entre despliegues** (sección 6).
- Usuario de Apache: `www-data` en la imagen de Render (en desarrollo, `apache` uid 1000). Escribibles: `storage/` completo y `bootstrap/cache/`. Las tareas de `schedule:run` deben correr con el mismo usuario; si no, crean archivos de caché o de log que Apache no puede escribir.
- **Archivos del legacy:** el backup no trae `hp/uploads` (las imágenes del push 9 dan 404 en `:8081`). En el corte hay que copiar del servidor del legacy:
  - `public_html/hp/uploads/` → `storage/app/public/hp/uploads/`;
  - las carpetas de subida de `system/category*` que se usen;
  - `signage/upload/`, con el mapeo de S4 (operaciones o señalización, §2).

## 6. Persistencia en Render

El disco del contenedor de Render se borra en cada despliegue. Con la convención actual se pierde en cada despliegue o reinicio:

- las sesiones (`file`): todo el personal tiene que volver a entrar;
- la caché;
- **todas las subidas** (`storage/app/public`);
- los logs.

Hace falta un *Persistent Disk* montado en `/var/www/storage` (o al menos en `storage/app` y `storage/framework/sessions`). Ese disco solo se puede montar en **una** instancia y quita los despliegues sin corte. La alternativa es S3 para archivos y `database` para sesión y caché. Ver «Decide Antony».

## 7. Conexiones `signage` y `beacon`

- **`signage`** (NUCLEO §9): en producción es la BD `admin` del legacy. Se configura así:
  - `DB_SIGNAGE_HOST`, `DB_SIGNAGE_PORT`, `DB_SIGNAGE_DATABASE`, `DB_SIGNAGE_USERNAME` y `DB_SIGNAGE_PASSWORD` (en el legacy, `SIGNAGE_DB_PASSWORD`/`ADMIN_DB_PASSWORD`);
  - `DB_SIGNAGE_SCHEMA=public` **(verificar)**: en desarrollo las tablas están en el esquema `signage` de la BD principal; en la BD `admin` real estarán en `public`.
  - Si no se definen, usa la BD principal y el esquema `signage`, que en producción no existe: las pantallas de señalización fallarían.
  - `database/schema/signage-admin.sql` es un esquema **reconstruido** solo para desarrollo: no se carga en producción. Antes hay que compararlo con el volcado real (S4).
- **`beacon`:** ビーコン está en pausa, sin pantallas (solo modelos). En producción puede quedar vacía. Si se rellena (`DB_BEACON_*`, en el legacy `BEACON_DB_PASSWORD`), la conexión es perezosa y tiene un tiempo máximo de `DB_BEACON_TIMEOUT` (5 s): la app arranca aunque esa BD no responda.
- Las dos BD externas tienen que aceptar conexiones desde la IP del servidor nuevo (reglas de `pg_hba.conf` o del firewall del servidor del legacy).

## 8. Fuentes IPAex (PDF)

- mPDF trae la fuente CJK Sun-ExtA, así que los PDF funcionan sin instalar nada. En nombres como 髙 o 﨑 no se distingue bien (decisión S0, NUCLEO §11).
- Para etiquetas DM, 請求書 y 領収書:
  1. Poner `ipaexg.ttf` e `ipaexm.ttf` (licencia IPA, moji.or.jp) en `CRM_PDF_FONT_DIR` (por defecto `storage/fonts`).
  2. Definir `CRM_PDF_DEFAULT_FONT=ipaexm`.
- Las fuentes no van en git. En Render, la carpeta tiene que sobrevivir a los despliegues: o se descargan en el build (`RUN curl … && unzip` a `/var/www/storage/fonts`, con la URL fijada) o se dejan en el disco persistente. Si `storage` es un disco montado, el build no puede escribir ahí: poner `CRM_PDF_FONT_DIR=/usr/local/share/fonts/ipaex` y descargarlas allí en el build.
- mPDF escribe temporales en `storage/framework/cache/mpdf`: tiene que ser escribible.

## 9. Base de datos de producción

- La app usa **la misma BD del legacy** (CONVENCIONES §0). Se restaura el volcado de producción (`pg_restore`) en el PostgreSQL del servidor nuevo. No se usa `migrate`. `render.yaml` crea una BD vacía `crmsystem2`, que no sirve sin ese volcado.
- Antes del corte:
  1. Comparar el esquema de producción con `database/schema/pgsql-schema.sql`, que sale del backup de 2019. Las tablas o columnas que Laravel use y producción no tenga (p. ej. `sales_items`, `smatopi_message`, columnas añadidas por los módulos) se crean con un script revisado, no con `migrate`.
  2. Probar las pantallas con esos datos (Cola 4 de S1: `CRM_LEGACY_BACKUP=<volcado> bash scripts/bd-legacy.sh <bd> --forzar`).
- Versión de PostgreSQL:
  - desarrollo: 18;
  - `render.yaml`: 15;
  - producción del legacy: preguntar.

  El volcado se restaura en una versión igual o mayor.
- Las tablas base de Laravel (`cache`, `jobs`, `sessions`, `users`…) solo hacen falta si se elige `database` para sesión, caché o cola.

## 10. Cachés de Laravel en producción (aquí no)

- En el servidor, después de cada despliegue: `php artisan optimize` (config, rutas, vistas y eventos). Para volver atrás: `php artisan optimize:clear`.
- Condiciones, ya comprobadas:
  - ningún `env()` fuera de `config/` (en `app/`, `routes/` y `bootstrap/` no hay ninguno);
  - los menús usan `[Clase::class, 'metodo']` y no closures (NUCLEO §6);
  - `route:cache` funciona: S0 lo corre antes de las pruebas;
  - `tests/Feature/Nucleo/RouteTableTest.php` falla si dos rutas comparten método+URI o nombre, o si una se queda con el nombre del grupo sin completar. Con la caché se pisarían sin aviso. `POST admin/operaciones/kintai` queda saltada hasta que S5 la corrija.
- **No** en desarrollo, en `:8081` ni en los worktrees: con caché de config, un cambio en `.env` o `config/` no se ve hasta `optimize:clear`, y las pruebas fijan valores con `config([...])`.
- `opcache.validate_timestamps = 0` en producción (sección 1). Por eso cada despliegue tiene que reiniciar Apache o crear un contenedor nuevo; en Render ya pasa.

## 11. Red, proxy y dominio

- **Proxies de confianza (falta código):** `bootstrap/app.php` no llama a `trustProxies()`. Detrás del balanceador de Render (o de cualquier proxy), `request()->ip()` sería la IP del proxy, y eso rompe:
  - `CRM_DEVELOPER_IPS` (`nucleo.maintenance`, `nucleo.shop-create`);
  - el límite de 20 intentos de login por usuario+IP, que pasa a ser uno para todos;
  - la IP que se guarda en `tb_login_rireki`.

  Además Laravel no sabría que la petición llegó por HTTPS: las URL generadas saldrían con `http://` y la cookie segura podría fallar. Añadir `$middleware->trustProxies(at: …)` con la IP o el rango del proxy (o `'*'` si solo el proxy llega al contenedor). Es de S1 (sección 13). El legacy solo miraba `REMOTE_ADDR` (INTEGRATIONS.md, «Restricción por IP»).
- **HTTPS:** el certificado lo pone el hosting. En Render es automático con dominio propio.
- **URL y contratos externos** (CLAUDE.md, «Integraciones externas»):
  - las tablets, la app (`/appview/*`, `/api/*`), los reproductores de señalización y `system/tool/api/*` siguen llamando a las mismas URL del legacy: el dominio nuevo tiene que ser el mismo (cambio de DNS) o hay que redirigir sin perder `POST` (307/308, no 301);
  - las listas de dominios que el código compara (`MOBILESHOP_LIST`, dominios `*.crm-s.net` de サポート y CRM端末) dependen del `Host`.
- **Salidas a internet en producción** (`Doc/qa/llamadas-externas.md`):
  - g-api, a través de `GApi`, encendido con `APP_ENV=production`;
  - extlink, encendido con la URL definida;
  - SMTP.

  Si el servidor filtra salidas, abrir:
  - `b.g-api.net`, y `b-develop.g-api.net` solo con `push_api_host = 0`;
  - el host de extlink;
  - el SMTP;
  - las BD `signage` y `beacon`.

  Todas las llamadas a g-api ya pasan por `GApi` (`RecommendationPush` incluido).

## 12. Comprobación después de desplegar

1. `/system/login.php` muestra el login del legacy; los estáticos `/system/css/mainStyle.css` y `/common/…` responden 200 **sin pasar por PHP** (las cabeceras no traen la cookie de sesión de Laravel). Si pasan por PHP, `.htaccess` no se está aplicando.
2. Entrar con un usuario real, abrir システム選択 y una pantalla por módulo; `storage/logs` sin errores.
3. Subir una imagen (予定プッシュ, `ajax_picupload.php`), redesplegar y comprobar que sigue ahí (disco persistente).
4. `php artisan schedule:list` muestra las tareas, y en el log se ve que `schedule:run` corre cada minuto.
5. Un correo de prueba (テストメール de la メルマガ a una dirección propia).
6. `php artisan about`: entorno `production`, debug apagado y config, rutas y vistas en caché.
7. Un PDF con un nombre con 髙 (si se instaló IPAex).
8. Una pantalla de señalización (conexión `signage`) y una llamada de integración (`/api/...` con su clave).

## 13. Lista de cambios pendientes (para que S0 los asigne)

Los puntos 1-7 y 9 están **hechos** (S1, 2026-11-27) y verificados con la imagen construida (`docker build`):

- la imagen no lleva `.env`;
- `optimize` no falla al arrancar;
- con la caché de config y de rutas puesta, `/system/login.php`, システム選択 y las portadas y pantallas de los módulos responden 200 contra una copia de la BD de desarrollo;
- `/system/css/mainStyle.css` lo sirve Apache, sin cookie de Laravel;
- un PDF con 髙/﨑 incrusta IPAex;
- `schedule:list` muestra las tareas.

| # | Cambio | Dueño probable | Estado |
|---|---|---|---|
| 1 | `.dockerignore`: excluir `public_html/.env*` y `public_html/storage/app/{public,private}/*` | S0/S1 | **Hecho.** Además excluye `public/build`, `public/storage`, las pruebas y `scripts/`. Corregido también que excluyera `docker/` y `docker-entrypoint.sh`, que el `Dockerfile` copia: el build anterior no podía terminar. Solo entran `docker/produccion/` y `docker/policy.xml`. |
| 2 | Apache de producción con `AllowOverride All` (o vhost propio) y `LimitRequestBody` | S1 | **Hecho:** `docker/produccion/000-default.conf` (vhost propio, `ServerName`, 256 MB); quitados los `sed` del `DocumentRoot`. |
| 3 | `docker/php-production.ini` y usarlo en el `Dockerfile` | S1 | **Hecho:** `docker/produccion/php.ini`. |
| 4 | Copiar `docker/policy.xml` en la imagen | S1 | **Hecho:** `/etc/ImageMagick-7/policy.xml` (ImageMagick 7.1.1 en trixie). |
| 5 | Entrypoint: quitar `cp .env.example`, `key:generate` y `migrate --force`; añadir `storage:link --force`, crear y dar permisos a `storage/*` y usar `optimize`; arrancar el planificador (y el worker si se decide) | S1 | **Hecho.** Sin `APP_KEY` el contenedor no arranca. La espera a PostgreSQL tiene límite (`DB_WAIT_SECONDS`). `optimize` corre como `www-data`. `schedule:work` arranca con `CRM_SCHEDULER_ENABLED=true` (por defecto) y `queue:work` con `CRM_QUEUE_WORKER_ENABLED=false` (por defecto), los dos con reinicio. El docker local y `en-docker.sh` no cambian. |
| 6 | `trustProxies()` en `bootstrap/app.php` | S1 | **Hecho** con `TRUSTED_PROXIES` → `NucleoServiceProvider::trustProxies()` (`TrustProxies::at()`), con prueba `TrustProxiesTest`. No va en `bootstrap/app.php` porque ahí la configuración aún no está cargada. |
| 7 | Completar `.env.example` con las variables de la sección 3; `APP_LOCALE=ja` | S1 | **Hecho:** interruptores externos apagados (`CRM_GAPI_ENABLED=false`, `MOBILESHOP_EXTLINK_ENABLED=false`), `TRUSTED_PROXIES` y las variables del entrypoint comentadas. |
| 8 | `render.yaml`: `APP_LOCALE=ja`; sesión, caché y cola según la decisión; disco persistente; variables de la sección 3 (`sync: false`) y *Secret Files* de las claves | S0/S1, tras «Decide Antony» | Pendiente |
| 9 | Descarga de IPAex en el build (si se decide) | S1 | **Hecho** con los paquetes de Debian `fonts-ipaexfont-gothic`/`-mincho` (sin descarga externa), enlazados en `/usr/local/share/fonts/ipaex`. La imagen pone `CRM_PDF_FONT_DIR` y `CRM_PDF_DEFAULT_FONT=ipaexm`, y el build falla si faltan los `.ttf`. |
| 10 | Probar `SendMailmagJob` con una lista grande y decidir la cola | S3 + S0 |
| 11 | Comparar el esquema de producción con `pgsql-schema.sql` y preparar un script para lo que falte | S1 (Cola 4), con el volcado |
| 12 | Actualizar `DEPLOY_RENDER.md`: habla de `AdminSeeder`, `CatalogSeeder`, la rama `brahamscode/modulo-promocion` y `migrate` | S0 |

## Decide Antony

1. **Hosting.** Render (contenedor sin disco propio: hace falta un disco persistente de una sola instancia o S3) o un VPS o servidor en Japón como el del legacy (cron, disco local, misma red que las BD `admin` y `beacon`).
   - Render no tiene región en Japón (la más cercana es Singapur); `render.yaml` usa Oregón.
   - Para clientes en Japón y BD externas en el servidor del legacy, la latencia pesa.
2. **Sesión, caché y archivos** según el hosting: `file` con disco persistente (convención actual) o `database` para sesión y caché + S3 para archivos (permite varias instancias).
3. **Dominio y DNS.**
   - ¿Una instalación por cliente (como el legacy, `*.crm-s.net` con su BD) o varias en un mismo servidor?
   - ¿Se mantiene el mismo dominio? Las tablets, la app y los reproductores tienen la URL grabada.
4. **Corte.**
   - ¿Cambio de golpe (se para el legacy, se copia la BD y los archivos y se cambia el DNS) o una temporada con los dos sistemas sobre la misma BD?
   - La convivencia es posible porque los números salen del mismo `hatuban`, pero el crontab del legacy y el `schedule:run` de Laravel no pueden correr a la vez: enviarían dos veces las メルマガ y los push.
   - Ventana de mantenimiento y aviso a los clientes.
5. **Volcado de producción** (y de la BD `admin` de señalización) para comparar el esquema y probar con datos reales antes del corte.
6. **Versión de PostgreSQL** de producción (desarrollo 18, `render.yaml` 15).
7. **Correo:** proveedor SMTP del servidor nuevo, remitente y SPF/DKIM del dominio (el legacy enviaba desde su servidor).
8. **IP de desarrollo/soporte** (`CRM_DEVELOPER_IPS`) y si el servidor estará detrás de un proxy o CDN.
9. **Copias de seguridad** de la BD y de `storage/app/public` en el hosting nuevo (el legacy tenía `backup` en category0; aquí no se migró la exportación).
