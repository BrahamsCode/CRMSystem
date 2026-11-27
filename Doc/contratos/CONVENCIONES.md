# Convenciones compartidas (el "contrato" entre sesiones)

> Lo cambia **solo S0**. Si una sesión necesita otra cosa, la pide a S0 por mensaje y la anota en "Pedidos" de su archivo de estado.
> Objetivo: que 5 sesiones trabajen en paralelo en el mismo Laravel sin pisarse al fusionar.

## 1. Versiones

- **PHP 8.5** (Docker `php:8.5-fpm-trixie`), **Laravel 13** (`laravel/framework ^13.0`, `php: ^8.3` en `composer.json`), PostgreSQL 18, Pest, Vite + Tailwind 4.
- Solo **S1** cambia `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `config/`, `bootstrap/`. Otra sesión que necesite un paquete lo pide a S0.

## 2. Propiedad de carpetas

Cada módulo escribe **solo** en sus carpetas. `<Modulo>` en PascalCase inglés, `<modulo>` en kebab-case.

| Carpeta | Dueño |
|---|---|
| `routes/web.php`, `routes/api.php`, `routes/console.php` | S1 (solo incluyen los archivos de `routes/modulos/`) |
| `routes/modulos/<modulo>.php` | El módulo. Se carga dentro del grupo `admin` (prefijo, `auth:admin`, nombre `admin.`) |
| `app/Http/Controllers/Admin/<Modulo>/`, `app/Http/Requests/<Modulo>/` | El módulo |
| `resources/views/admin/<modulo>/` | El módulo |
| `resources/views/layouts/`, `resources/views/components/` | S1. Componentes nuevos genéricos: pedirlos a S0 |
| `lang/ja/<modulo>.php` | El módulo (textos de pantalla en japonés) |
| `config/menu/<modulo>.php` | El módulo (entradas del menú lateral; S1 las une) |
| `app/Models/` | Según la tabla de la sección 3 |
| `app/Enums/`, `app/Jobs/`, `app/Mail/`, `app/Console/Commands/` | Subcarpeta `<Modulo>/` del módulo para archivos nuevos |
| `database/migrations/` | Cada módulo crea las suyas con el nombre `AAAA_MM_DD_HHMMSS_<modulo>_<accion>.php`. **Nunca editar una migración de otro módulo**: pedir una migración nueva a su dueño vía S0 |
| `database/seeders/<Modulo>Seeder.php`, `database/factories/` | El módulo |
| `tests/Feature/<Modulo>/`, `tests/Unit/<Modulo>/` | El módulo |
| `Doc/diseno/<grupo>.md` | El módulo |
| `Doc/contratos/`, `CLAUDE.md`, `Doc/planificacion/`, `Doc/MIGRACION-LARAVEL13.md` | S0 |

## 3. Módulos y sesiones

| Sesión | Módulos (`<modulo>`) | Análisis | Modelos de los que es dueña |
|---|---|---|---|
| S1 Núcleo | `nucleo`, `base` (基本情報管理), importador legacy | `00-nucleo-y-base.md` | `Admin`, `Shop`, `Terminal`, `Staff` (login y permisos), `BaseModel`, `Concerns/*`, módulos activables, `hatuban`/numeración, configuración global (`admin_master`, `contents_master`) |
| S2 Clientes | `clientes` (顧客管理), `ventas` (販売管理), `reservas` (予約管理: category9, `r/`, `reserve/`) | `01-clientes-y-ventas.md`, `05-…` (sección category9), `07-…` (secciones `r/` y `reserve/`) | `Customer*`, `CustomCategory`, `CustomField*`, `CustomValue`, `FieldSetting`, `Visit*`, productos y ventas, reservas |
| S3 Promoción | `promocion` (販売促進管理: mensajería y fidelización) | `03a-promocion-mensajeria.md`, `03b-promocion-fidelizacion.md` | `Coupon*`, `Message*`, `Point*`, `Stamp*`, `Rank*`, `Survey*`, `CustomerGroup*` |
| S4 Contenido | `mypage` (マイページ管理), `web` (ホームページ管理), `terminales` (端末管理: beacon, felica, card_signage, digital_signage de category7), `senalizacion` (`signage/`, `digitalsignage/`) | `04-web-y-mypage.md`, `05-…` (sección category7), `08-…` (secciones signage y digitalsignage) | Contenido de mypage, páginas, catálogos, displays y programas de señalización, beacons, felica |
| S5 Operaciones | `operaciones` (業務支援管理: staff, 勤怠, turnos), `integraciones` (API tienda móvil, `mpa_*`, `appview/`, `tool/api`, CTI, `exp_cmd/`, `version/`) | `05-…` (sección category5), `07-pos-api-y-externos.md` | Asistencia, turnos, horarios; contratos externos (rutas `routes/modulos/integraciones.php` sin `auth:admin`) |

**Fuera de alcance por ahora (Antony, 2026-11-26):** POS (`system/category11` completo, incluido `category11/yoyaku`; terminales `CRMPOS-<id>`/`pos_session.php`; conexión a los servidores POS de cada tienda: donde el legacy llama al POS, dejar una interfaz `PosGateway` sin implementación y anotarlo), SNS (`system/category12`) y touchcation completo (`touchcation/` y su gestión `system/category7/kiosk/`, tablas `tb_nextouch_*`): confirmado que no se usan.

Un modelo que necesitan varios módulos (por ejemplo `Customer`) lo **lee** cualquiera, pero solo su dueño lo **cambia**.

## 4. Código

- Controladores delgados; la lógica en `app/Services/<Modulo>/` o `app/Actions/<Modulo>/`. Validación en Form Requests.
- Textos de pantalla en japonés vía `__('<modulo>.clave')`. Nada de texto japonés suelto en los controladores.
- Borrado lógico: SoftDeletes (reemplaza `disp_flg = 9`). Fechas con Carbon; **nunca** reproducir el `mktime()` que daba 1970.
- Correo: Mailables + cola (`QUEUE_CONNECTION=database`). Envíos programados: Scheduler (`routes/console.php`, dueño S1; cada módulo registra sus tareas en un `ServiceProvider` propio en `app/Providers/<Modulo>ServiceProvider.php`).
- CSV: `StreamedResponse` (reemplaza Flash). PDF: el paquete que defina S1.
- Autorización: Policies/Gates según el nivel de permiso que defina S1 (equivalente a `cKengen` / `ACCESS_LEVEL_*`).
- Cada pantalla nueva lleva al menos una prueba Pest de "carga con sesión" y una del flujo principal (alta/edición).

## 5. Paridad

- Por cada vista del análisis, una fila en `Doc/diseno/<grupo>.md` con su ruta Laravel y estado (`pendiente`, `hecho`, `descartado: motivo`).
- Una vista se da por hecha cuando: la pantalla existe, hace lo mismo que el legacy (mismos campos, filtros, botones, validaciones) y sus pruebas pasan.
- Lo que el legacy hacía mal y se corrige se anota en "Decisiones" del archivo de estado.
