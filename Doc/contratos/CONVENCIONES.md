# Convenciones compartidas (el "contrato" entre sesiones)

> Lo cambia **solo S0**. Si una sesión necesita otra cosa, la pide a S0 por mensaje y la anota en "Pedidos" de su archivo de estado.
> Objetivo: que 5 sesiones trabajen en paralelo en el mismo Laravel sin pisarse al fusionar.

## 0. Base: misma BD y misma UI que el legacy (Antony, 2026-11-26)

**BD = la del legacy.**
- El esquema es `public_html/database/schema/pgsql-schema.sql`, generado desde `crm2-php8/db/crm2019.backup` (175 tablas `tb_*`, `admin_master`, `hatuban`, `mailmag_master`, `zip`…) más las tablas base de Laravel (`users`, `cache`, `jobs`, `sessions`…), que **existen pero no se usan**: sesión y caché en archivo (`SESSION_DRIVER=file`, `CACHE_STORE=file`), colas síncronas (`QUEUE_CONNECTION=sync`) y login contra el personal del legacy, no contra `users`.
- `php artisan migrate` (y `migrate:fresh`, y `RefreshDatabase` en las pruebas) carga ese esquema. Datos base del legacy (códigos postales, `admin_master`, tienda, `hatuban`…): `bash scripts/bd-legacy.sh <bd>`.
- **Modelos Eloquent sobre las tablas del legacy:** `protected $table = 'tb_customer'`, `protected $primaryKey = 'cd'`, `public $timestamps = false` (o las columnas de fecha del legacy con `const CREATED_AT`/`UPDATED_AT`), los nombres de columna tal cual (`kaiin_cd`, `touroku_date`…). Numeración con `hatuban` como el legacy. Borrado lógico con `disp_flg = 9` como el legacy (scope global o trait propio, **no** SoftDeletes).
- **Migraciones nuevas solo si hacen falta** y siempre compatibles con el legacy: agregar tablas o columnas nullable; **nunca** borrar ni renombrar columnas del legacy (la BD de producción es la misma).
- No hay importador: pasar a producción es apuntar a la misma BD.
- Otras BD del legacy: la BD `admin` de `signage/` va en una conexión aparte (`signage`, configurada por S1 con `DB_SIGNAGE_*`), igual que el legacy. Su esquema no está en `crm2019.backup`: hace falta un volcado (Antony).
- `login_pass` del cliente y las demás columnas sensibles se quedan como en el legacy: la app y las integraciones dependen de ellas. Los cambios de seguridad que rompan el contrato se anotan para Japón.
- `Doc/database/ESTANDARES_BD.md` y `TABLAS_MODULO_*.md` **ya no aplican** (eran para un esquema nuevo).
- Lo que CRMSystem tenía sobre tablas nuevas (clientes, promociones, layout Tailwind) se borró; queda en git (commit `8d84c98`) solo como referencia de lógica.

**UI = la del legacy.**
- Mismo HTML y **mismos CSS**, imágenes y JS del legacy. S1 copia los estáticos de `crm2-php8/public_html` (`system/css`, `system/js`, `system/images`, `common/`…) a `public_html/public/legacy/` conservando la estructura relativa (así funcionan los `url(../images/...)` de los CSS), y arma el layout Blade con la cabecera y el menú lateral idénticos.
- Cada módulo pasa a Blade el HTML de cada pantalla, con las mismas clases e ids, y usa los mismos JS (jQuery 3.7.1 + compat del legacy). Textos japoneses directamente en el Blade, copiados del legacy.
- **Responsive y rediseño: al final**, cuando todo funcione igual.

## 1. Versiones

- **PHP 8.5** (Docker `php:8.5-fpm-trixie`), **Laravel 13** (`laravel/framework ^13.0`, `php: ^8.3` en `composer.json`), PostgreSQL 18, Pest. UI con los CSS y JS del legacy (sección 0); Vite/Tailwind solo para lo nuevo, si hace falta.
- Solo **S1** cambia `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `config/`, `bootstrap/`. Otra sesión que necesite un paquete lo pide a S0.

## 2. Propiedad de carpetas

Cada módulo escribe **solo** en sus carpetas. `<Modulo>` en PascalCase inglés, `<modulo>` en kebab-case.

| Carpeta | Dueño |
|---|---|
| `routes/web.php`, `routes/api.php`, `routes/console.php` | S1 (solo incluyen los archivos de `routes/modulos/`) |
| `routes/modulos/<modulo>.php` | El módulo. Se carga dentro del grupo `admin` (prefijo, `auth:admin`, nombre `admin.`) |
| `routes/publico/<modulo>.php` | El módulo. Rutas de dispositivos y contratos externos (reproductores de señalización, API, app, CTI): S1 las carga **fuera** del grupo `admin`, sin `auth:admin`, sin CSRF y sin prefijo, para conservar las URL literales del legacy (con `.php`). Nombre de ruta `publico.<modulo>.*` |
| `routes/legacy/<modulo>.php` | El módulo. URL literales del legacy dentro del sistema del personal (`/system/category<N>/…/*.php`) que llaman los JS del legacy por AJAX o en formularios, para no tocar esos JS. S1 las carga sin prefijo, con `web` + el guard del personal, y nombre `legacy.<modulo>.*`. El CSRF lo añade el layout de S1 a todas las peticiones jQuery (cabecera `X-CSRF-TOKEN` con `beforeSend`, que también funciona con jQuery 1.3.2). Las pantallas nuevas siguen en `routes/modulos/` |
| `GET /hp/uploads/{path}` (redirección genérica a Storage) | S1. Una ruta específica de un módulo bajo `/hp/uploads/...` (p. ej. el XML de señalización de S4) tiene prioridad |
| `app/Http/Controllers/Admin/<Modulo>/`, `app/Http/Requests/<Modulo>/` | El módulo |
| `resources/views/admin/<modulo>/` | El módulo |
| `resources/views/layouts/`, `resources/views/components/`, `public/legacy/` (CSS, JS e imágenes del legacy) | S1. Componentes nuevos genéricos: pedirlos a S0 |
| `public/legacy/system/category<N>/…` y estáticos propios de una carpeta pública (`public/legacy/appview/`, `r/`, `signage/`, `digitalsignage/`…) | El módulo dueño de esa categoría o carpeta: copia del legacy sus CSS, JS e imágenes con la misma estructura relativa. S1 copia solo lo común (`system/css`, `system/js`, `system/images`, `system/plugin`, `common/`). Si una carpeta pública necesita sus URL relativas originales, puede ir en `public/<carpeta>/`, con el mismo dueño |
| `lang/ja/<modulo>.php` | El módulo (opcional: los textos pueden ir directo en el Blade, como en el legacy) |
| `config/menu/<modulo>.php` | El módulo (entradas del menú lateral; S1 las une) |
| `app/Models/` | Raíz y `app/Models/Concerns/`: S1 (modelos del núcleo y traits comunes `HasHatuban`, `LegacySoftDeletes` (`disp_flg = '9'`), `LegacyTimestamps` (`touroku_date`/`update_date`)). Cada módulo pone sus modelos en `app/Models/<Modulo>/` (`Customers`, `Sales`, `Reservations`, `Promotions`, `Mypage`, `Terminales`, `Senalizacion`, `Operaciones`, `Integraciones`); un modelo por `tb_*`, del dueño de la sección 3 |
| `app/Enums/`, `app/Jobs/`, `app/Mail/`, `app/Console/Commands/` | Subcarpeta `<Modulo>/` del módulo para archivos nuevos |
| `database/schema/pgsql-schema.sql` | S1 (no se edita a mano: es el esquema del legacy) |
| `database/migrations/` | Solo si hace falta algo nuevo, compatible con el legacy (sección 0). Nombre `AAAA_MM_DD_HHMMSS_<modulo>_<accion>.php` |
| `database/seeders/<Modulo>Seeder.php`, `database/factories/` | El módulo |
| `tests/Feature/<Modulo>/`, `tests/Unit/<Modulo>/` | El módulo |
| `Doc/diseno/<grupo>.md` | El módulo |
| `Doc/contratos/`, `CLAUDE.md`, `Doc/planificacion/`, `Doc/MIGRACION-LARAVEL13.md` | S0 |

## 3. Módulos y sesiones

| Sesión | Módulos (`<modulo>`) | Análisis | Modelos de los que es dueña |
|---|---|---|---|
| S1 Núcleo | `nucleo`, `base` (基本情報管理), layout y estáticos del legacy | `00-nucleo-y-base.md` | `Admin`, `Shop`, `Terminal`, `Staff` (login y permisos), `BaseModel`, `Concerns/*`, módulos activables, `hatuban`/numeración, configuración global (`admin_master`, `contents_master`) |
| S2 Clientes | `clientes` (顧客管理), `ventas` (販売管理), `reservas` (予約管理: category9, `r/`, `reserve/`) | `01-clientes-y-ventas.md`, `05-…` (sección category9), `07-…` (secciones `r/` y `reserve/`) | `Customer*`, `CustomCategory`, `CustomField*`, `CustomValue`, `FieldSetting`, `Visit*`, productos y ventas, reservas |
| S3 Promoción | `promocion` (販売促進管理: mensajería y fidelización) | `03a-promocion-mensajeria.md`, `03b-promocion-fidelizacion.md` | `Coupon*`, `Message*`, `Point*`, `Stamp*`, `Rank*`, `Survey*`, `CustomerGroup*` |
| S4 Contenido | `mypage` (マイページ管理), `web` (ホームページ管理), `terminales` (端末管理: beacon, felica, card_signage, digital_signage de category7), `senalizacion` (`signage/`, `digitalsignage/`) | `04-web-y-mypage.md`, `05-…` (sección category7), `08-…` (secciones signage y digitalsignage) | Contenido de mypage, páginas, catálogos, displays y programas de señalización, beacons, felica |
| S5 Operaciones | `operaciones` (業務支援管理: staff, 勤怠, turnos), `integraciones` (API tienda móvil, `mpa_*`, `appview/`, `tool/api`, CTI, `exp_cmd/`, `version/`) | `05-…` (sección category5), `07-pos-api-y-externos.md` | Asistencia, turnos, horarios; contratos externos (rutas en `routes/publico/integraciones.php`) |

**Fuera de alcance por ahora (Antony, 2026-11-26):** POS (`system/category11` completo, incluido `category11/yoyaku`; terminales `CRMPOS-<id>`/`pos_session.php`; conexión a los servidores POS de cada tienda: donde el legacy llama al POS, dejar una interfaz `PosGateway` sin implementación y anotarlo), SNS (`system/category12`) y touchcation completo (`touchcation/` y su gestión `system/category7/kiosk/`, tablas `tb_nextouch_*`): confirmado que no se usan.

Un modelo que necesitan varios módulos (por ejemplo `Customer`) lo **lee** cualquiera, pero solo su dueño lo **cambia**.

## 4. Código

- Controladores delgados; la lógica en `app/Services/<Modulo>/` o `app/Actions/<Modulo>/`. Validación en Form Requests.
- Textos de pantalla en japonés directo en el Blade (copiados del legacy) o en `lang/ja`. Nada de texto japonés suelto en los controladores.
- Configuración: donde la guarda el legacy (`admin_master`, `contents_master` por `category`/`field`, columnas de `tb_shop`…), leída y escrita con modelos sobre esas tablas. Sin tablas `<algo>_settings` nuevas.
- Códigos: los `cd` del legacy, numerados con `hatuban` (servicio común de S1). Los contratos externos siguen usando los mismos `cd`: no hay mapeo ni importador.
- Modelos de tablas legacy compartidas: un modelo por tabla, del dueño de la sección 3. Si dos módulos necesitan la misma `tb_*`, la lee cualquiera, pero solo su dueño cambia el modelo, igual que antes.
- POS (fuera de alcance): cada módulo que lo necesite declara **su** interfaz en `app/Services/<Modulo>/PosGateway.php`, con los métodos que usa, y la enlaza a una implementación nula en su `<Modulo>ServiceProvider`. No hay una interfaz común: así ningún archivo es compartido.
- CSV de descarga: Shift_JIS (`SJIS-win`) como el legacy, porque los abren en Excel en Japón. Un helper común lo publica S1; mientras tanto, cada módulo usa uno propio con la misma firma.
- Banderas del legacy (`hyouji_flg`, `disp_flg`, `*_flg` en texto) tal cual: mismos valores que el legacy, con constantes o enums PHP para nombrarlos.
- Borrado lógico con `disp_flg = 9`, como el legacy (sección 0). Fechas con Carbon; **nunca** reproducir el `mktime()` que daba 1970.
- Correo: Mailables (`QUEUE_CONNECTION=sync`: las tablas de Laravel no se usan; los envíos masivos van en comandos del Scheduler). Envíos programados: Scheduler (`routes/console.php`, dueño S1; cada módulo registra sus tareas en un `ServiceProvider` propio en `app/Providers/<Modulo>ServiceProvider.php`).
- CSV: `StreamedResponse` (reemplaza Flash). PDF: el paquete que defina S1.
- Autorización: Policies/Gates según el nivel de permiso que defina S1 (equivalente a `cKengen` / `ACCESS_LEVEL_*`).
- **Contrato del layout** (S1 lo implementa; los módulos lo usan desde ya): `@extends('layouts.legacy')` con las secciones `title` (el `<title>` del legacy), `css` (los `<link>` propios de la pantalla), `js` (los `<script>` propios) y `content` (lo que el legacy pone dentro del área principal, después de `left_navi`). El menú lateral se elige con `@section('menu', '<modulo>')`. Las pantallas sin menú (popups, `yoyaku_navi`) usan `@extends('layouts.legacy-popup')` con las mismas secciones, sin `menu`. Los estáticos se referencian con `asset('legacy/...')` o con la ruta que fije S1 en NUCLEO.md.
- Cada pantalla nueva lleva al menos una prueba Pest de "carga con sesión" y una del flujo principal (alta/edición).

## 5. Paridad

- Por cada vista del análisis, una fila en `Doc/diseno/<grupo>.md` con su ruta Laravel y estado (`pendiente`, `hecho`, `descartado: motivo`).
- Una vista se da por hecha cuando: la pantalla existe, hace lo mismo que el legacy (mismos campos, filtros, botones, validaciones) y sus pruebas pasan.
- Lo que el legacy hacía mal y se corrige se anota en "Decisiones" del archivo de estado.
