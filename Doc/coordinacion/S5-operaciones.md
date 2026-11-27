# S5 — Operaciones e integraciones externas

**Modelo:** Sonnet 5.5, esfuerzo alto · **Carpeta:** `CRMSystem-operaciones/` · **Rama:** `l13/operaciones`
**Módulos:** 業務支援管理 (`operaciones`: staff, 勤怠, turnos, horarios) e integraciones externas (`integraciones`). **Es dueña de:** sus carpetas según `Doc/contratos/CONVENCIONES.md` (secciones 2 y 3).

## Modo autónomo

Trabajas sin pedir confirmación. Al terminar cada tarea: actualizar este archivo y seguir con la siguiente. Si falta un detalle menor, decide lo más simple que respete las convenciones, anótalo en "Decisiones" y sigue. Al terminar todo o bloquearte: `SendMessage` a "S0 Coordinador CRM" (una línea: qué hiciste, qué sigue, qué necesitas). Cada entregable terminado con pruebas en verde: `git add` de tus rutas, commit (mensaje en español) y `git push origin <tu rama>`; después avisa a S0, que integra en `laravel13`. No haces merge ni cambias de rama; nunca `--force`.

## Próximo paso — Diseño (sin código)

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

_(ninguno)_

## Decisiones

## Hecho
