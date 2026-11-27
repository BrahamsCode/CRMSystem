# S0 — Coordinador y orquestador

**Modelo:** Opus 5.5, esfuerzo alto · **Carpeta:** `CRMSystem/` · **Rama:** la de integración (la que tenga `CRMSystem/`)
**Escribe en:** `Doc/contratos/`, `Doc/planificacion/`, `Doc/MIGRACION-LARAVEL13.md`, `CLAUDE.md`, este archivo. **No escribe código de módulos:** delega.

## Próximo paso

1. Arranque (ver protocolo). Confirmar a S1 la Fase 1 (Laravel 13) y a S2–S6 el diseño de su grupo (`Doc/diseno/<grupo>.md`).
   - Primero: asegurar que `CRMSystem/` tenga Laravel 12 funcionando en el Docker nuevo (`bash scripts/en-docker.sh php artisan migrate --seed`; la BD de PostgreSQL 18 empieza vacía).
2. Cuando S1 termine la Fase 1 con pruebas en verde: commit en `l13/nucleo`, merge a integración, actualizar las demás ramas, y asignar a S1 el núcleo (paso 2 del plan).
3. Cuando llegue cada diseño: revisarlo contra el análisis y `CONVENCIONES.md`, aprobarlo o devolverlo con cambios concretos.

## Protocolo de orquestación

**Al arrancar:**
1. Leer [ORCHESTRATION.md](../planificacion/ORCHESTRATION.md), secciones 4 y 7, y [CONVENCIONES.md](../contratos/CONVENCIONES.md).
2. Leer el archivo de estado de cada sesión en su worktree: `../CRMSystem-nucleo/Doc/coordinacion/S1-nucleo.md`, `../CRMSystem-clientes/Doc/coordinacion/S2-clientes.md`, `../CRMSystem-promocion/Doc/coordinacion/S3-promocion.md`, `../CRMSystem-contenido/Doc/coordinacion/S4-contenido.md`, `../CRMSystem-operaciones/Doc/coordinacion/S5-operaciones.md`, `../CRMSystem-quiosco/Doc/coordinacion/S6-quiosco.md`.
3. `ListAgents`. Nombres: **"S1 Nucleo"**, **"S2 Clientes"**, **"S3 Promocion"**, **"S4 Contenido"**, **"S5 Operaciones"**, **"S6 Quiosco"**. Si alguna no aparece, anotarlo y volver a mirar en el siguiente ciclo, sin esperar en bucle.
4. A cada sesión abierta: `SendMessage` con `notify_when_idle: true` confirmando su tarea actual.
5. Terminar el turno. **No consultar en bucle**: los avisos llegan solos.

**Cada vez que llega un aviso o mensaje:**
1. Leer **solo** el archivo de estado de esa sesión.
2. Decidir según la tabla y actuar en el mismo turno.
3. Al asignar: `SendMessage` con `notify_when_idle: true`, con **tarea**, **criterio de terminado** y **archivos a leer**.
4. Si se terminó un entregable: actualizar "Estado" en `Doc/MIGRACION-LARAVEL13.md`.
5. Anotar la decisión en "Decisiones" (una línea) y terminar el turno.

| Situación de la sesión | Qué hace S0 |
|---|---|
| Terminó su "Próximo paso" | Asignar lo siguiente del plan (ORCHESTRATION.md, sección 4) o de su diseño, que no dependa de algo pendiente |
| Entregó su diseño | Revisar: cada vista del análisis tiene fila; tablas según `ESTANDARES_BD.md`; nada fuera de su propiedad. Aprobar o devolver |
| Pide algo ajeno (paquete, modelo de otro, componente) | Si es razonable, reenviarlo al dueño (S1 para núcleo) con prioridad; si cambia convenciones, editar `CONVENCIONES.md`, commit y avisar a todos |
| Bloqueada por otra sesión | Darle otra tarea de su diseño o que use un stub/factory propio |
| Falló dos veces la misma tarea | Escalar a Antony con el resumen |
| Submódulo terminado con pruebas en verde | Commit y merge (ver abajo) |
| Pide algo que solo Antony tiene | Escalar con "ANTONY:" y asignarle otra tarea mientras tanto |

**Commits y merges: los hace S0, locales, nunca push.**
- **Cuándo:** cada entregable con pruebas en verde, y cada cambio de S0 en `Doc/` o `CLAUDE.md`.
- **Cómo:** `git -C ../CRMSystem-<grupo> add <rutas explícitas>` y `git -C ../CRMSystem-<grupo> commit -m "<mensaje en español>"`; en `CRMSystem/`, `git merge l13/<grupo>`; después actualizar las demás: `git -C ../CRMSystem-<otro> merge <rama de integración>` (S1–S5 tienen el merge bloqueado).
- **Antes de cada commit:** correr sus pruebas (`bash scripts/en-docker.sh php artisan test` dentro de ese worktree); si fallan, no hay commit: devolver la tarea. Comprobar que no entren `.env`, `env-legacy.example`, claves, `vendor/`, `node_modules/` ni `.claude/`.
- **Conflictos:** resolver solo los triviales (docs, estados); en código, devolverlos a la sesión dueña.
- **Migraciones de la rama de integración:** después de cada merge, `bash scripts/en-docker.sh php artisan migrate` en `CRMSystem/` para que la web (`http://localhost:8081`) quede al día.

**Nunca:** escribir código en módulos; push; editar estados de otras sesiones (se pide por mensaje).

## Lee solo

- `CLAUDE.md` (se carga solo), `Doc/planificacion/ORCHESTRATION.md` secciones 4 y 7, `Doc/contratos/CONVENCIONES.md`
- Los archivos de estado de S1–S5 y, al revisar, el `Doc/diseno/<grupo>.md` correspondiente
- `Doc/analisis-legacy/README.md` (resumen y puntos)

## Decisiones

- 2026-11-26: orquestación creada (S0 + 6 trabajadores, ver ORCHESTRATION.md §7). Análisis vista por vista en `Doc/analisis-legacy/`. `hp/` fuera de alcance (Antony).

## Hecho

- 2026-11-26: Docker igual que crm2-php8 (PHP 8.5 FPM + Apache + PostgreSQL 18), documentación de referencia y análisis legacy.
