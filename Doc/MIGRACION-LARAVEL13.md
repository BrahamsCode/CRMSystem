# Migración del CRM legacy a Laravel 13 (leer primero)

> Creado: 2026-11-26 · Rama `brahamscode/modulo-promocion`.
> Documento de entrada para rehacer el CRM en este repositorio. Al terminar cada fase, actualizar "Estado" y "Siguiente paso".

## 1. Contexto: de dónde sale este proyecto

| Fuente | Qué es | Dónde |
|---|---|---|
| **Página de desarrollo** | El CRM legacy funcionando. CRMSystem empezó analizando sus pantallas (mockups y diseño de BD en `Doc/`) | `https://development.crm-s.net/system/login.php` · usuario `master` / clave `master` · **solo lectura** |
| **crm2-php8** | El código fuente del legacy, entregado después. Antony lo migró de PHP 7 a **PHP 8.5.12** sin cambiar el comportamiento | `C:\Users\User\VivaTech\crm2-php8` · rama `refactor/fase-0` |
| **Legacy original (PHP 7)** | Los 2 primeros commits de crm2-php8, tal como estaba en producción | `a1447e81` "First commit" y `6aae16ba` "Second commit" (2026-10-29) |
| **Este repo (CRMSystem)** | La reescritura en Laravel. Hoy en **Laravel 12** (`laravel/framework ^12.0`, PHP ^8.2, Fortify, Pest 3, Vite 7, Tailwind 4) con avance en clientes y promociones | `public_html/` |

**Objetivo:** pasar todo el CRM a **Laravel 13** (última estable: `v13.44.1`, 2026-11-24, requiere PHP `^8.3`; comprobado en Packagist) sobre **PHP 8.5**, usando crm2-php8 como referencia de comportamiento y la página de desarrollo como muestra visual.

### Cómo consultar cada fuente

```sh
# Legacy original en una carpeta aparte, sin tocar la rama de trabajo de crm2-php8
cd C:/Users/User/VivaTech/crm2-php8
git worktree add ../crm2-legacy 6aae16ba

# Un archivo concreto tal como estaba en producción (PHP 7)
git show 6aae16ba:public_html/system/category1/customer/customer_list.php

# Qué cambió la migración a PHP 8.5 en un archivo
git diff 6aae16ba refactor/fase-0 -- public_html/system/category1/customer/customer_list.php
```

- **Local (crm2-php8 en Docker):** `https://localhost/system/login.php`, `master` / `12345678`. No levantar crm2-php8 y CRMSystem con los mismos puertos: CRMSystem usa 8081/8444/15434/8026 (`docker/.env`).
- **BD de referencia:** `crm2-php8/db/crm2019.backup` (dump `pg_dump -Fc`). Tiene el esquema completo (175 tablas) pero **no hay datos reales**: 0 clientes, 0 reservas; el 98 % son códigos postales. Para datos reales hace falta un volcado de producción o staging (pregunta abierta a Japón).

## 2. Reglas heredadas del refactor PHP 8.5 que siguen valiendo

1. **"Que la funcionalidad no cambie para nada"** (Antony, 2026-11-10). Ante una duda, vale lo que hace el legacy (`master` en PHP 7) y la página de desarrollo es la muestra. Los resultados que estaban rotos y se decidió corregir están en [legacy-crm2/PENDING.md](legacy-crm2/PENDING.md) (sección 1b) y [legacy-crm2/CONTINUE.md](legacy-crm2/CONTINUE.md) ("Decisiones").
2. **Integraciones externas:** tablets, quioscos y otros servidores llaman al CRM desde fuera del repositorio. **Laravel tiene que respetar sus URL y contratos** (o redirigirlos). Ver sección 5 y [legacy-crm2/INTEGRATIONS.md](legacy-crm2/INTEGRATIONS.md).
3. **Textos para el usuario final en japonés**, como en las pantallas actuales. Términos de dominio con el nombre que ya usa el legacy (`kaiin_cd`, `hatuban`, `yoyaku`) cuando aparezcan en el esquema o en integraciones.
4. **Nunca lanzar contra el legacy** (local ni desarrollo):
   - `system/login.php?activation=1` → con otro `Host` borra ~100 tablas.
   - `system/create_customer.php` / `create_shop.php` → un GET sobrescribe clientes y tiendas.
   - Antes de sondear una página sin sesión, leer su código.
5. **Fuera de alcance:** `admin/` (su BD no existe ni en desarrollo), 女の子管理 (`category8`, retirado) y **`hp/`** (sitio viejo; decisión del 2026-11-26).

## 3. Mapa del legacy → Laravel

Contado en `crm2-php8/public_html` (2,441 archivos PHP). Nombres de módulo según [legacy-crm2/MANUAL-TESTS.md](legacy-crm2/MANUAL-TESTS.md).

### Sistema del personal (`/system`, 1,363 archivos)

| # | Módulo | Carpeta legacy | PHP | Submódulos | Estado en CRMSystem |
|---|---|---|---|---|---|
| 1 | Subir Laravel 12 → 13 y PHP 8.5 (`composer.json`, Pest 4, Fortify, Dockerfile de Render) | **Hecho (2026-11-27)**: Laravel 13.44.1, PHP 8.5.13 (S1, b44dcea) |
| 2 | 販売管理 | `system/category2/` | 5 | 商品情報管理 | Pendiente |
| 3 | 販売促進管理 | `system/category3/` | 390 | お知らせ配信, 来店履歴, 予約, ポイント, スマトピ連携, スタンプ, デコメール, 自動お知らせ, フォローお知らせ, アンケート, マイクーポン, お客様アンケート, プッシュ通知, リマインダ | **En curso**: `Doc/database/TABLAS_MODULO_PROMOCIONES.md`, modelos `Coupon`, `Message*`, `Point*`, `Stamp*`, `Rank*`, `Survey` |
| 4 | マイページ管理 | `system/category6/` | 83 | マイページ, 携帯HP, フォトギャラリー, 求人情報 | Pendiente |
| 5 | ホームページ管理 | `system/category4/` | 91 | 不動産管理 | Pendiente |
| 6 | 業務支援管理 | `system/category5/` | 70 | スタッフ, 勤怠, 初期設定, スケジュール | Pendiente |
| 7 | 端末管理 | `system/category7/` | 78 | ビーコン, サイネージ, カード | Pendiente |
| 8 | 予約管理 | `system/category9/` | 67 | タイムテーブル | Pendiente |
| 9 | 女の子管理 | `system/category8/` | 15 | — | **Fuera de alcance** (retirado) |
| 10 | 基本情報管理 | `system/category0/` | 65 | 店舗, 設定, システム選択 | Parcial: `システム選択` (commit `7cf0c1c`) |
| — | POS | `system/category11/` | 139 | — | **Fuera por ahora** (Antony, 2026-11-26) |
| — | SNS | `system/category12/` | 24 | — | **Fuera por ahora** (APIs de Twitter/Facebook muertas) |

### Fuera de `/system`

| Carpeta | PHP | Qué es | Notas |
|---|---|---|---|
| `hp/` | 387 | Sitio y área de socio (マイページ del cliente final) | **Fuera de alcance** (obsoleto). Lo que la app o los dispositivos leían de `hp/uploads/` se migra a Storage |
| `touchcation/` | 305 | Quiosco Android (`jp.CrmShWeb`, APK en `version/`) | **Fuera de alcance** (no se usa, Antony 2026-11-26), igual que su gestión `category7/kiosk/` |
| `signage/`, `digitalsignage/` | 92 + 28 | Señalización digital | |
| `api/` | 20 | API JWT para la tienda móvil (`MOBILESHOP_*`) | Contrato en `crm2-php8/README.md` |
| `appview/` | 34 | Vistas para la app | |
| `reserve/`, `r/` | 9 + 9 | Reservas públicas, redirecciones | |
| `exp_cmd/`, `cron/`, `version/` | 13 + 1 + 4 | Actualizaciones de versión, cron, APK | |
| `admin/` | 160 | Panel de soporte con otra BD | **Fuera de alcance** |
| `src/` (raíz) | — | Clases `CrmSystem\*` (Customer, MobileShop, Mypage, Sales, SystemCore, Util, Validator) | Lo más fácil de portar: ya es POO con tests PHPUnit |

## 4. Equivalencias técnicas

| Legacy (crm2-php8, PHP 8.5) | Laravel 13 |
|---|---|
| `pg_*` procedural, `inc/db.php` | Eloquent / Query Builder sobre PostgreSQL |
| `illuminate/database` 13 suelto (en `src/`) | El mismo componente, ya integrado |
| Doctrine Migrations 3.9 (`Migrations/`, 15 versiones) | Migraciones de Laravel |
| `.env` + `env_value()` | `.env` + `config()` (las variables del legacy están en `crm2-php8/.env.example`: **contiene claves reales, no copiar a este repo**) |
| Symfony Mailer (`crm_send_mail()`, `CrmDecomail`, ISO-2022-JP) | Laravel Mail (Symfony Mailer por debajo); decomail con imágenes inline |
| mPDF 8 / FPDF | Mismo paquete, o `barryvdh/laravel-dompdf` si basta |
| `firebase/php-jwt` (API tienda móvil) | Mantener contrato; Sanctum o JWT según decisión |
| `crontab` escrito por PHP (`cSendSchedule.php`) — **no funciona en Docker** | Scheduler de Laravel (`schedule:run` + cola). Resuelve el hueco del cron del legacy |
| `inc/csrf.php` | Middleware CSRF de Laravel (excluir rutas de POS/quiosco) |
| Sesión del personal (`session.php`) | Fortify (ya instalado) |
| `md5(login_pass)` | Mientras haya integraciones que lo usen, mantener compatibilidad; migrar a `password_hash` es decisión pendiente |
| jQuery 3.7 + capa compat, jQuery UI, Bootstrap 3 | Blade + Tailwind 4 + Vite (como el resto de CRMSystem) |
| Descarga CSV con Flash (17 pantallas) | Respuesta en streaming de Laravel |
| CKEditor 4 | Decisión pendiente (licencia) |

## 5. Integraciones que no se pueden romper

Resumen de [legacy-crm2/INTEGRATIONS.md](legacy-crm2/INTEGRATIONS.md):

- **Terminal POS:** `User-Agent: CRMPOS-<id>`, 62 archivos cargan `pos_session.php`. **Fuera por ahora**: el legacy sigue atendiéndolo hasta que se decida.
- **Quiosco Touchcation:** APK compilada; cambiar URLs obliga a publicar una APK nueva.
- **API `system/tool/api/*`** (9 endpoints, `CRM_API_KEY`), `get_touroku_data.php`, `get_shukei_data*.php`: llamadores desconocidos.
- **CTI de escritorio** (`desktop_request/*`).
- **Servidores POS de cada tienda** (`$posConnect`, BD compartida). **Fuera por ahora**: interfaz `PosGateway` sin implementar.
- **Enlaces con `md5(login_pass)`** en correos y `hp/user`.
- **API extlink de la tienda móvil** y buzón POP3 (`mm_add.php`).
- **Restricción por IP** solo con `REMOTE_ADDR`: tenerla en cuenta si producción va detrás de un proxy.

## 6. Docker (2026-11-26)

`docker/` copia la arquitectura de crm2-php8, adaptada a Laravel:

| Servicio | Antes en CRMSystem | Ahora (igual que crm2-php8) |
|---|---|---|
| PHP | `php:8.2-apache` (mod_php, un solo contenedor) | `app`: `php:8.5-fpm-trixie` |
| Web | el mismo contenedor | `web`: Apache en `debian:trixie-slim` → `proxy:fcgi://app:9000`, HTTP + HTTPS autofirmado |
| BD | `postgres:15-alpine` | `postgres:18`, volumen nuevo `dbdata18` |
| Correo | mailpit | mailpit (crm2-php8 usa mailhog, sin mantenimiento) |
| Node | `node:20-alpine` | `node:24-alpine` (perfil `node`) |

Diferencias con crm2-php8, a propósito: `DocumentRoot /var/www/public` (Laravel), puertos configurables en `docker/.env` para levantar los dos proyectos a la vez, `php.ini` mínimo en `conf.d` (el de crm2-php8 es el `php.ini` completo del legacy), base `_testing` creada por `initdb/`, extensión `bcmath`.

**Al cambiar a PostgreSQL 18 la BD empieza vacía** (el volumen de PG 15 no es compatible): `php artisan migrate --seed`. El volumen viejo `crmsystem2_dbdata` sigue existiendo hasta borrarlo a mano.

## 7. Estado

| Fase | Qué | Estado |
|---|---|---|
| 0 | Docker igual que crm2-php8 + documentación de referencia | **Hecho (2026-11-26)** |
| 0b | Análisis vista por vista (`Doc/analisis-legacy/`, ≈ 1.020 vistas, ≈ 615 puntos) y orquestación (`Doc/planificacion/`, `INICIAR-CRM.bat`) | **Hecho (2026-11-26)** |
| 1 | Subir Laravel 12 → 13 y PHP 8.5 (`composer.json`, Pest 4, Fortify, Dockerfile de Render) | **Hecho (2026-11-27)**: Laravel 13.44.1 sobre PHP 8.5.13 |
| 1' | Diseño por grupo (`Doc/diseno/`) | **Hecho (2026-11-27)**: v2 sobre la BD y la UI del legacy, aprobados: S2 (84 pts), S3 (≈83), S4, S5 (63, de los que 22 esperan 勤怠) |
| 2 | **Misma BD y misma UI que el legacy** (Antony, 2026-11-26): esquema `database/schema/pgsql-schema.sql` desde `crm2019.backup`, modelos sobre `tb_*`, mismos CSS; responsive al final. Lo construido sobre tablas nuevas se borró | **Hecho (esquema y limpieza)** |
| 3+ | Módulos en paralelo según `Doc/planificacion/ORCHESTRATION.md` | **Hecho salvo decisiones de Japón** (2026-11-27): 989 pruebas en verde; paridad del 97 % (`Doc/qa/paridad.md`), con 7 pendientes (スマトピ, ビーコン, `shukei`); regresión de punta a punta sin errores 500 (`Doc/qa/regresion.md`); informe en `Doc/qa/para-japon.md` |

## 8. Siguiente paso

1. (2026-11-27, noche) El plan de `ORCHESTRATION.md` §4 está cumplido salvo lo que espera a Japón. S0 pasa la suite completa en serie sobre `laravel13` y S1 prepara `Doc/contratos/DESPLIEGUE.md`. La siguiente fase (responsive y rediseño, despliegue o cargar datos reales) la decide Antony.

## 8b. Respuestas de Antony (2026-11-27)

Provisionales hasta que conteste Japón: (1) BD `admin` de signage con `signage-admin.sql`; (2) GoTel/`signage/pdf` en pausa, con rutas preparadas; (3) ビーコン en pausa, solo con modelo y conexión; (4) push por g-api como el legacy. Firmes: 新着情報 se migra; スマトピ oculto; 勤怠 se migra la gestión (sin quiosco); `hp/user` fuera. Detalle en `Doc/coordinacion/S0-coordinador.md` y en `Doc/qa/para-japon.md`.

## 9. Decisiones abiertas (Antony / Japón)

- Esquema de BD: nuevo con estándares o compatible con `tb_*` (afecta a integraciones que leen la BD: POS).
- ¿Se reescriben `hp/`, `touchcation/`, `signage/` y `api/` en Laravel, o conviven con el legacy al principio?
- Cuándo entra el POS (`category11`); SNS (`category12`) probablemente nunca. Flash CSV, CKEditor, `password_hash`.
- Señalización (S4): ¿se usan `signage/mail` (GoTel, お問い合わせ) y `signage/pdf` (請求書/領収書)? ¿Se mantiene ビーコン管理? Su BD externa `b-develop.g-api.net` no responde. Mientras tanto, S4 deja esas partes al final.
- Push (S3): con la misma BD, `b.g-api.net` puede seguir leyendo `push_master`/`mailmag_master` como hoy, y el contrato no se rompe. Solo falta confirmar que g-api se mantiene (o si algún día se pasa a FCM directo).
- Promoción (S3): ¿se usan 新着情報 (`category3/digital_signage`), DM発行 y スマトピ? Si no, se descartan (≈5,5 puntos).
- Asistencia (S5): los dos registradores de 勤怠 (`touchcation/kintai_crm_n` y `touchcation/timecard`) son touchcation, que no se usa. ¿Se sigue usando 勤怠管理/勤怠一覧 (y el lector FeliCa con sus tarjetas)? Si no, ≈27 puntos pasan a «solo datos históricos».
- Señalización web (S4): volcado de la BD `admin` de `signage/` (no está en `crm2019.backup`). Sin él, el submódulo `signage/` no se puede probar.
- Las de [legacy-crm2/PENDING.md](legacy-crm2/PENDING.md), sección 7 (servidor POS de prueba, staging con datos reales, IPs de desarrollo, `activation=1`).

## 10. Inconsistencias vistas al preparar esto

- `public_html/.env` tiene `DB_DATABASE=crmsystem`, pero `docker/.env` crea `crmsystem2` (y `crmsystem2_testing`). Con la BD nueva de PG 18 hay que alinearlos.
- `docker/apache.conf` y `docker/nginx.conf`: borrados el 2026-11-26 (no los usaba nadie).
- Los documentos de `legacy-crm2/` enlazan a rutas de crm2-php8 (`../tools/`, `../README.md`) que no existen aquí: abrirlas en el repo original.
