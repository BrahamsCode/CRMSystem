# S0 — Coordinador y orquestador

**Modelo:** Opus 5.5, esfuerzo alto · **Carpeta:** `CRMSystem/` · **Rama:** `laravel13` (integración)
**Escribe en:** `Doc/contratos/`, `Doc/planificacion/`, `Doc/MIGRACION-LARAVEL13.md`, `CLAUDE.md`, este archivo. **No escribe código de módulos:** delega.

## Próximo paso

1. Arranque (ver protocolo). Confirmar a S1 la Fase 1 (Laravel 13) y a S2–S5 el diseño de su grupo (`Doc/diseno/<grupo>.md`).
   - Primero: asegurar que `CRMSystem/` tenga Laravel 12 funcionando en el Docker nuevo (`bash scripts/en-docker.sh php artisan migrate --seed`; la BD de PostgreSQL 18 empieza vacía).
2. Cuando S1 termine la Fase 1 con pruebas en verde: commit en `l13/nucleo`, merge a integración, actualizar las demás ramas, y asignar a S1 el núcleo (paso 2 del plan).
3. Cuando llegue cada diseño: revisarlo contra el análisis y `CONVENCIONES.md`, aprobarlo o devolverlo con cambios concretos.

## Protocolo de orquestación

**Al arrancar:**
1. Leer [ORCHESTRATION.md](../planificacion/ORCHESTRATION.md), secciones 4 y 7, y [CONVENCIONES.md](../contratos/CONVENCIONES.md).
2. Leer el archivo de estado de cada sesión en su worktree: `../CRMSystem-nucleo/Doc/coordinacion/S1-nucleo.md`, `../CRMSystem-clientes/Doc/coordinacion/S2-clientes.md`, `../CRMSystem-promocion/Doc/coordinacion/S3-promocion.md`, `../CRMSystem-contenido/Doc/coordinacion/S4-contenido.md`, `../CRMSystem-operaciones/Doc/coordinacion/S5-operaciones.md`.
3. `ListAgents`. Nombres: **"S1 Nucleo"**, **"S2 Clientes"**, **"S3 Promocion"**, **"S4 Contenido"**, **"S5 Operaciones"**. Si alguna no aparece, anotarlo y volver a mirar en el siguiente ciclo, sin esperar en bucle.
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
| Subió su rama con un entregable | Integrar (ver abajo) |
| Pide algo que solo Antony tiene | Escalar con "ANTONY:" y asignarle otra tarea mientras tanto |

**Integración: la hace S0** (Antony dio todos los permisos el 2026-11-26).
- Cada trabajador hace commit y `git push origin l13/<grupo>` de su rama al terminar un entregable con pruebas en verde, y te avisa.
- **Al recibir el aviso:** en ese worktree, `bash scripts/en-docker.sh php artisan test` (si fallan, devolver la tarea: no se integra); revisar que no entren `.env`, claves, `vendor/`, `node_modules/` ni `.claude/`; después, en `CRMSystem/`: `git merge l13/<grupo>` y `git push origin laravel13`.
- **Actualizar a los demás:** `git -C ../CRMSystem-<otro> merge laravel13` (los trabajadores tienen el merge bloqueado) y avisarles si el cambio les afecta.
- **Conflictos:** resolver solo los triviales (docs, estados); en código, devolverlos a la sesión dueña.
- **Migraciones:** después de cada merge, `bash scripts/en-docker.sh php artisan migrate` en `CRMSystem/` para que la web (`http://localhost:8081`) quede al día.
- **Nunca** `push --force`, ni push a `main` o `brahamscode/*`.

**Nunca:** escribir código en módulos; editar estados de otras sesiones (se pide por mensaje).

## Lee solo

- `CLAUDE.md` (se carga solo), `Doc/planificacion/ORCHESTRATION.md` secciones 4 y 7, `Doc/contratos/CONVENCIONES.md`
- Los archivos de estado de S1–S5 y, al revisar, el `Doc/diseno/<grupo>.md` correspondiente
- `Doc/analisis-legacy/README.md` (resumen y puntos)

## Decisiones

- 2026-11-26: orquestación creada (S0 + 5 trabajadores, ver ORCHESTRATION.md §7). Rama de integración `laravel13`. Todos los permisos: los trabajadores hacen commit y push de su rama, S0 integra. Fuera (confirmado que no se usan): POS (`category11`), SNS (`category12`) y touchcation con `category7/kiosk/`; `digitalsignage` se mantiene (reproductor Flash → HTML5). Análisis vista por vista en `Doc/analisis-legacy/`. `hp/` fuera de alcance (Antony).

## Hecho

- 2026-11-26: Docker igual que crm2-php8 (PHP 8.5 FPM + Apache + PostgreSQL 18), documentación de referencia y análisis legacy.
