# S1 — Núcleo e integraciones

**Modelo:** Opus 5.5, esfuerzo alto · **Carpeta:** `CRMSystem-nucleo/` · **Rama:** `l13/nucleo`
**Es dueña de:** ver `Doc/contratos/CONVENCIONES.md`, secciones 2 y 3 (S1): `composer.*`, `package*.json`, `config/`, `bootstrap/`, `routes/*.php` de raíz, layouts y componentes, modelos del núcleo.

## Modo autónomo

Trabajas sin pedir confirmación. Al terminar cada tarea: actualizar este archivo y seguir con la siguiente de "Cola". Al terminar todo o bloquearte: `SendMessage` a "S0 Coordinador CRM" (una línea). Cada entregable terminado con pruebas en verde: `git add` de tus rutas, commit (mensaje en español) y `git push origin <tu rama>`; después avisa a S0, que integra en `laravel13`. No haces merge ni cambias de rama; nunca `--force`.

## Próximo paso — Fase 1: Laravel 12 → 13 en PHP 8.5

1. Verificar el entorno: `bash scripts/en-docker.sh php -v` (debe decir 8.5) y `bash scripts/en-docker.sh php artisan test` con Laravel 12 (línea base: anotar cuántas pruebas pasan).
2. `public_html/composer.json`: `php: ^8.3`, `laravel/framework: ^13.0` y las versiones compatibles de `laravel/fortify`, `laravel/tinker`, `pestphp/pest` (4.x), `pestphp/pest-plugin-laravel`, `nunomaduro/collision`, `laravel/pail`, `laravel/sail`, `laravel/pint`. `composer update` con `bash scripts/en-docker.sh composer update -W`.
3. Aplicar la guía oficial de actualización 12 → 13 (cambios de configuración, `bootstrap/app.php`, deprecaciones). Edición quirúrgica.
4. Criterio de terminado: `php artisan --version` = 13.x, mismas pruebas en verde que la línea base, `npm run build` OK (`docker compose run --rm node npm run build` desde `docker/` del repo principal no sirve para tu worktree: usa `bash scripts/en-docker.sh npm ci && bash scripts/en-docker.sh npm run build`).
5. `Dockerfile` de la raíz (Render): `php:8.2-apache` → PHP 8.5, coherente con `docker/dockerfile`.
6. Avisar a S0.

## Cola (después de la Fase 1, en orden)

1. **Núcleo** (`Doc/contratos/NUCLEO.md`, lo escribes tú): carga automática de `routes/modulos/*.php` dentro del grupo `admin` y de `config/menu/*.php` en el menú lateral; layout del sistema del personal (paridad visual con la muestra de desarrollo); login del personal y permisos (Policies/Gates equivalentes a `cKengen`/`ACCESS_LEVEL_*`); tiendas; módulos activables (`tb_base_system`, `tb_base_system_child` → システム選択 ya existe, commit `7cf0c1c`). Fuente: `Doc/analisis-legacy/00-nucleo-y-base.md`.
2. **基本情報管理** (category0): pantallas de configuración y tiendas.
3. **Grupos de rutas para dispositivos y APIs** (los usan S4 y S5): grupo sin `auth:admin` ni CSRF, con su propia cookie de sesión, para los reproductores de señalización (`routes/modulos/senalizacion.php`) y las integraciones (`routes/modulos/integraciones.php`). Documentarlo en `NUCLEO.md`. Las integraciones las hace S5 y los reproductores S4.
4. **Importador legacy:** comando `crm:importar-legacy` que lee una BD restaurada de `crm2019.backup` (o un volcado de producción) y llena las tablas nuevas, por módulo (cada módulo aporta su mapeo en su diseño).
5. Paquetes comunes que pidan los módulos (PDF, Excel, FCM…), vía S0.

## Lee solo

- `CLAUDE.md`, `Doc/contratos/CONVENCIONES.md`
- `Doc/MIGRACION-LARAVEL13.md`, secciones 4 y 6
- Del análisis: `Doc/analisis-legacy/00-nucleo-y-base.md` (al empezar el núcleo), por secciones; `Doc/analisis-legacy/README.md` (hallazgos transversales)

## Pedidos

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

- Fase 1: `config/session.php` → `serialization = json` y `config/cache.php` → `serializable_classes = false` (valores del skeleton 13; si chocan con la base de Antony con `file`, prevalece la suya). `lock_path` de la caché de archivos → `framework/cache/locks`; canal `daily` de logging `days` → `max_files` (skeleton 13).
- `Dockerfile` (Render): `php:8.5-apache-trixie`, Node 24, sin instalar `opcache` (ya viene en el núcleo de 8.5). Se corrige `npm ci --only=production`, que omitía Vite (devDependency) y rompía el build.
- `CSRF`: no había referencias a `VerifyCsrfToken`/`ValidateCsrfToken` en el código; nada que renombrar a `PreventRequestForgery`.

## Hecho

- **Fase 1 (2026-11-26):** Laravel 12.69.3 → **13.44.1** en PHP 8.5.13. Línea base L12: 86 pruebas en verde (238 aserciones); con L13: las mismas 86 en verde. `npm ci && npm run build` OK (Vite 7). Paquetes: fortify 1.44, tinker 3.2, pest 4.7, pest-plugin-laravel 4.1, pail 1.3, pint 1.32, sail 1.71, collision 8.9.
