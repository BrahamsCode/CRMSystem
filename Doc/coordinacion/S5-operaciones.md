# S5 — Operaciones e integraciones externas

**Modelo:** Sonnet 5.5, esfuerzo alto · **Carpeta:** `CRMSystem-operaciones/` · **Rama:** `l13/operaciones`
**Módulos:** 業務支援管理 (`operaciones`: staff, 勤怠, turnos, horarios) e integraciones externas (`integraciones`). **Es dueña de:** sus carpetas según `Doc/contratos/CONVENCIONES.md` (secciones 2 y 3).

## Modo autónomo

Trabajas sin pedir confirmación. Al terminar cada tarea: actualizar este archivo y seguir con la siguiente. Si falta un detalle menor, decide lo más simple que respete las convenciones, anótalo en "Decisiones" y sigue. Al terminar todo o bloquearte: `SendMessage` a "S0 Coordinador CRM" (una línea: qué hiciste, qué sigue, qué necesitas). Cada entregable terminado con pruebas en verde: `git add` de tus rutas, commit (mensaje en español) y `git push origin <tu rama>`; después avisa a S0, que integra en `laravel13`. No haces merge ni cambias de rama; nunca `--force`.

## Próximo paso — Diseño (sin código) — **entregado 2026-11-26, esperando revisión de S0**

Escribir `Doc/diseno/operaciones-integraciones.md`:
1. **Tabla de paridad:** una fila por cada vista del análisis (en `05-operaciones-terminales-reservas.md` solo la sección de category5 (業務支援管理); `07-pos-api-y-externos.md` (todo salvo `category11`, `category12`, `r/` y `reserve/`)): archivo legacy, título (ja), ruta Laravel (`admin.<modulo>.*`), controlador@método, tablas nuevas, estado (`pendiente` / `descartado: motivo`).
2. **Esquema:** tablas nuevas según `Doc/database/ESTANDARES_BD.md`, reutilizando lo que ya existe en `public_html/app/Models` y `database/migrations`. Mapeo columna a columna desde las `tb_*` legacy (lo usará el importador de S1).
3. **Servicios viejos → Laravel 13:** cada uno con su reemplazo (Mail + cola, Scheduler, StreamedResponse, Storage, Policies…).
4. **Orden de implementación** por submódulo, con dependencias de otros grupos y estimación en puntos (1 punto ≈ media jornada).
5. **Pedidos** a S1 (núcleo) y a otros grupos.
Criterio de terminado: todas las vistas del análisis tienen fila; avisar a S0 y esperar su revisión. Mientras esperas, puedes preparar factories y seeders de tu grupo (en tus carpetas).

## Después (cuando S0 apruebe el diseño y el núcleo esté integrado)

Implementar submódulo por submódulo en el orden del diseño: migraciones, modelos, Form Requests, controladores, vistas Blade (textos en `lang/ja/<modulo>.php`), rutas en `routes/modulos/<modulo>.php`, menú en `config/menu/<modulo>.php`, pruebas Pest. Cada submódulo terminado: pruebas en verde (`bash scripts/en-docker.sh php artisan test`), filas en `hecho`, commit y push de tu rama, y aviso a S0.

**Integraciones = contratos de alto riesgo:** API JWT de la tienda móvil (13 endpoints, con sus erratas `discription`/`cutomer_cd` y errores con HTTP 200), `mpa_*`, `appview/`, `tool/api`, CTI, `version/`. **Mismas URL, mismos parámetros, mismas respuestas byte a byte.** Una prueba Pest de contrato por endpoint, comparando con la respuesta del legacy. Seguridad mejorada solo donde no rompa al cliente; lo demás, anótalo para Japón.
**No reproducir:** los calendarios que saltaban a 1970 (`mktime`, `Doc/legacy-crm2/PENDING.md` 1b) ni la inyección SQL de `change_syukkin2.php`. El staff (login, permisos) es del núcleo (S1): tú haces las pantallas de gestión (la sincronización de staff con el POS queda fuera: interfaz `PosGateway` sin implementar).

**Fuera de alcance por ahora (Antony, 2026-11-26):** POS (`category11`, terminales `CRMPOS-<id>`, servidores POS de tienda) y SNS (`category12`). Donde tu grupo llame al POS, deja una interfaz `PosGateway` sin implementación y anótalo.

## Lee solo

- `CLAUDE.md`, `Doc/contratos/CONVENCIONES.md`, `Doc/database/ESTANDARES_BD.md`, `Doc/analisis-legacy/README.md` (hallazgos transversales)
- Del análisis, por secciones (grep del título): en `05-operaciones-terminales-reservas.md` solo la sección de category5 (業務支援管理); `07-pos-api-y-externos.md` (todo salvo `category11`, `category12`, `r/` y `reserve/`)
- Para dudas de comportamiento: el código en `C:\Users\User\VivaTech\crm2-php8\public_html` (solo lectura) y `Doc/legacy-crm2/MANUAL-TESTS.md` (tu módulo)

## Pedidos

Detalle en `Doc/diseno/operaciones-integraciones.md` §5. Resumen:
- **S1:** tabla `staff` con el mapeo de §2.3; importador con `id = cd` en tablas cuyo `cd` sale en contratos (`shops`, `customers`, `staff`, `visits`, productos) — decide S0; config de la tienda móvil (JWT, claves `.pem`, extlink, `DOMAIN_LIST` con conexiones), `CRM_API_KEY`; paquetes `firebase/php-jwt` e `intervention/image`; niveles de permiso; la pantalla 機能設定 escribe `mobile_shop_settings` (S5).
- **S2:** `VisitService` (raitenFunc), búsqueda de clientes, productos canjeables por puntos, fotos de cliente, alta de socio y reserva rápida para `appview`, mapeo inverso `customers` → `tb_customer`.
- **S3:** `PointService` con tipos de la API (uso, canje) y datos para reconstruir `tb_point_log` (`staff_id`, origen, saldo previo); cupones, sellos y encuestas para `appview`.
- **S4:** `paper_cards` (`status` en lugar de `display_flg`), historial de beacon, ruta y `mtime` de `app_images`.
- **Antony/S0:** confirmar si 勤怠 (kintai/working/lector FeliCa) se usa sin touchcation (27 puntos en juego).

## Decisiones

- **Asistencia normalizada:** `kintai_tb_*` y `tb_work_time` → `attendance_days` + `attendance_punches` con `source` (1 terminal 勤怠, 2 タイムカード); cada pantalla filtra su origen.
- **Turnos:** una fila por turno (`shifts`), porque el legacy admite varios por día (listas `<>`).
- **Corte de jornada:** `attendance_terminal_settings.cutoff_time` por tienda (default 05:00) para todo; el 04:40 fijo de touchcation queda anotado.
- **`legacy_cd`** en las tablas de S5 (como S4); `id = cd` propuesto solo para las tablas de otros cuyo `cd` sale en contratos externos.
- **Cargos, departamentos y 雇用形態** pasan de texto en `tb_staff` a FK (`positions`, `departments`, `employment_types`); el importador crea los que falten por nombre.
- **`/api/<desconocido>`:** 404 JSON en lugar del fatal del legacy.
- **`setRaitenDetail` JSONP de error:** se emite el formato correcto (el legacy está roto por precedencia de operadores).
- **`change_syukkin2.php`, `schedule.php`, `shift.php`, archivos temporales CSV, `ajax_session_update`:** descartados (duplicados, sin enlace o reemplazados).
- **`version/<canal>/v.php` y APK:** descartados (touchcation, S0); `crm_version.php` se mantiene.
- **Rutas de integración** en `routes/publico/integraciones.php`, nombre `publico.integraciones.*` (convención S0).
- **Passcode de la tienda móvil** en `mobile_shop_settings` (tabla propia, CONVENCIONES §4).

## Hecho

- 2026-11-26 — Diseño `Doc/diseno/operaciones-integraciones.md`: paridad (category5 + 07 sin category11/12, `r/`, `reserve/`; más `tool/api` y CTI de 00 §10), esquema con mapeo desde `tb_*`, servicios viejos → Laravel, contratos externos, orden (75 puntos) y pedidos. **Esperando revisión de S0.**
