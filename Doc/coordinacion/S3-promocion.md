# S3 — Promoción (販売促進管理)

**Modelo:** Sonnet 5.5, esfuerzo medio · **Carpeta:** `CRMSystem-promocion/` · **Rama:** `l13/promocion`
**Módulos:** 販売促進管理 (`promocion`): mensajería y fidelización. **Es dueña de:** sus carpetas según `Doc/contratos/CONVENCIONES.md` (secciones 2 y 3).

## Modo autónomo

Trabajas sin pedir confirmación. Al terminar cada tarea: actualizar este archivo y seguir con la siguiente. Si falta un detalle menor, decide lo más simple que respete las convenciones, anótalo en "Decisiones" y sigue. Al terminar todo o bloquearte: `SendMessage` a "S0 Coordinador CRM" (una línea: qué hiciste, qué sigue, qué necesitas). No haces commits, merges ni push.

## Próximo paso — Diseño (sin código)

Escribir `Doc/diseno/promocion.md`:
1. **Tabla de paridad:** una fila por cada vista del análisis (`Doc/analisis-legacy/03a-promocion-mensajeria.md` y `03b-promocion-fidelizacion.md`): archivo legacy, título (ja), ruta Laravel (`admin.<modulo>.*`), controlador@método, tablas nuevas, estado (`pendiente` / `descartado: motivo`).
2. **Esquema:** tablas nuevas según `Doc/database/ESTANDARES_BD.md`, reutilizando lo que ya existe en `public_html/app/Models` y `database/migrations`. Mapeo columna a columna desde las `tb_*` legacy (lo usará el importador de S1).
3. **Servicios viejos → Laravel 13:** cada uno con su reemplazo (Mail + cola, Scheduler, StreamedResponse, Storage, Policies…).
4. **Orden de implementación** por submódulo, con dependencias de otros grupos y estimación en puntos (1 punto ≈ media jornada).
5. **Pedidos** a S1 (núcleo) y a otros grupos.
Criterio de terminado: todas las vistas del análisis tienen fila; avisar a S0 y esperar su revisión. Mientras esperas, puedes preparar factories y seeders de tu grupo (en tus carpetas).

## Después (cuando S0 apruebe el diseño y el núcleo esté integrado)

Implementar submódulo por submódulo en el orden del diseño: migraciones, modelos, Form Requests, controladores, vistas Blade (textos en `lang/ja/<modulo>.php`), rutas en `routes/modulos/<modulo>.php`, menú en `config/menu/<modulo>.php`, pruebas Pest. Cada submódulo terminado: pruebas en verde (`bash scripts/en-docker.sh php artisan test`), filas en `hecho` y aviso a S0 para el commit.

**Ya existe en CRMSystem:** `Admin/Promotions`, modelos `Message*`, `Coupon*`, `Point*`, `Stamp*`, `Rank*`, `Survey`, job `SendMessageBatch`, `PromotionMail`, comandos `promotions:*` y `Doc/database/TABLAS_MODULO_PROMOCIONES.md`. Parte de eso: marca lo que ya cumple. **El crontab del legacy (`cSendSchedule`) se reemplaza por el Scheduler**, registrado en `app/Providers/PromocionServiceProvider.php`; las tareas de puntos, sellos y rangos también son tuyas. Es el grupo más grande: si S0 lo decide, se parte en dos sesiones (mensajería / fidelización).

## Lee solo

- `CLAUDE.md`, `Doc/contratos/CONVENCIONES.md`, `Doc/database/ESTANDARES_BD.md`, `Doc/analisis-legacy/README.md` (hallazgos transversales)
- Del análisis, por secciones (grep del título): `Doc/analisis-legacy/03a-promocion-mensajeria.md` y `03b-promocion-fidelizacion.md`
- Para dudas de comportamiento: el código en `C:\Users\User\VivaTech\crm2-php8\public_html` (solo lectura) y `Doc/legacy-crm2/MANUAL-TESTS.md` (tu módulo)

## Pedidos

_(ninguno)_

## Decisiones

## Hecho
