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

_(ninguno)_

## Decisiones

## Hecho
