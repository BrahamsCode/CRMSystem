# S2 — Clientes, ventas y reservas

**Modelo:** Sonnet 5.5, esfuerzo medio · **Carpeta:** `CRMSystem-clientes/` · **Rama:** `l13/clientes`
**Módulos:** 顧客管理 (`clientes`), 販売管理 (`ventas`), 予約管理 (`reservas`). **Es dueña de:** sus carpetas según `Doc/contratos/CONVENCIONES.md` (secciones 2 y 3).

## Modo autónomo

Trabajas sin pedir confirmación. Al terminar cada tarea: actualizar este archivo y seguir con la siguiente. Si falta un detalle menor, decide lo más simple que respete las convenciones, anótalo en "Decisiones" y sigue. Al terminar todo o bloquearte: `SendMessage` a "S0 Coordinador CRM" (una línea: qué hiciste, qué sigue, qué necesitas). No haces commits, merges ni push.

## Próximo paso — Diseño (sin código)

Escribir `Doc/diseno/clientes-ventas-reservas.md`:
1. **Tabla de paridad:** una fila por cada vista del análisis (`Doc/analisis-legacy/01-clientes-y-ventas.md`; en `05-operaciones-terminales-reservas.md` solo la sección de category9 (予約管理); en `07-pos-api-y-externos.md` solo `category11/yoyaku`, `r/` y `reserve/`): archivo legacy, título (ja), ruta Laravel (`admin.<modulo>.*`), controlador@método, tablas nuevas, estado (`pendiente` / `descartado: motivo`).
2. **Esquema:** tablas nuevas según `Doc/database/ESTANDARES_BD.md`, reutilizando lo que ya existe en `public_html/app/Models` y `database/migrations`. Mapeo columna a columna desde las `tb_*` legacy (lo usará el importador de S1).
3. **Servicios viejos → Laravel 13:** cada uno con su reemplazo (Mail + cola, Scheduler, StreamedResponse, Storage, Policies…).
4. **Orden de implementación** por submódulo, con dependencias de otros grupos y estimación en puntos (1 punto ≈ media jornada).
5. **Pedidos** a S1 (núcleo) y a otros grupos.
Criterio de terminado: todas las vistas del análisis tienen fila; avisar a S0 y esperar su revisión. Mientras esperas, puedes preparar factories y seeders de tu grupo (en tus carpetas).

## Después (cuando S0 apruebe el diseño y el núcleo esté integrado)

Implementar submódulo por submódulo en el orden del diseño: migraciones, modelos, Form Requests, controladores, vistas Blade (textos en `lang/ja/<modulo>.php`), rutas en `routes/modulos/<modulo>.php`, menú en `config/menu/<modulo>.php`, pruebas Pest. Cada submódulo terminado: pruebas en verde (`bash scripts/en-docker.sh php artisan test`), filas en `hecho` y aviso a S0 para el commit.

**Ya existe en CRMSystem:** buena parte de 顧客管理 (`Admin/Customers`, modelos `Customer*`, `Doc/database/TABLAS_MODULO_CLIENTES.md`, mockups en `Doc/mockups/modulo-clientes`). Parte de eso: marca como `hecho` lo que ya cumple la paridad y anota lo que falta.
**Reservas:** `category11/yoyaku` duplica a `category9/yoyaku` y `r/` a `reserve/` (divergentes): un solo módulo; documenta las diferencias. El recordatorio de S3 depende del modelo de reservas: define y publica pronto el modelo `Reservation` en tu diseño.

## Lee solo

- `CLAUDE.md`, `Doc/contratos/CONVENCIONES.md`, `Doc/database/ESTANDARES_BD.md`, `Doc/analisis-legacy/README.md` (hallazgos transversales)
- Del análisis, por secciones (grep del título): `Doc/analisis-legacy/01-clientes-y-ventas.md`; en `05-operaciones-terminales-reservas.md` solo la sección de category9 (予約管理); en `07-pos-api-y-externos.md` solo `category11/yoyaku`, `r/` y `reserve/`
- Para dudas de comportamiento: el código en `C:\Users\User\VivaTech\crm2-php8\public_html` (solo lectura) y `Doc/legacy-crm2/MANUAL-TESTS.md` (tu módulo)

## Pedidos

_(ninguno)_

## Decisiones

## Hecho
