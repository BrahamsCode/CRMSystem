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

- 2026-11-26 (Antony): **misma BD y misma UI que el legacy** (`CONVENCIONES.md` §0). Esquema Laravel = `database/schema/pgsql-schema.sql` desde `crm2019.backup`; lo construido sobre tablas nuevas se borró (clientes, promociones, layout Tailwind, `Admin`). Los diseños ya escritos o aprobados (S4) deben rehacer su parte de esquema y UI. Cada worktree: `git merge laravel13`, después `bash scripts/en-docker.sh php artisan migrate:fresh` y `bash scripts/bd-legacy.sh crmsystem2_<grupo>`.

- 2026-11-26: orquestación creada (S0 + 5 trabajadores, ver ORCHESTRATION.md §7). Rama de integración `laravel13`. Todos los permisos: los trabajadores hacen commit y push de su rama, S0 integra. Fuera (confirmado que no se usan): POS (`category11`), SNS (`category12`) y touchcation con `category7/kiosk/`; `digitalsignage` se mantiene (reproductor Flash → HTML5). Análisis vista por vista en `Doc/analisis-legacy/`. `hp/` fuera de alcance (Antony).
- 2026-11-26: arranque de S0. Fase 1 confirmada a S1 (en su cola, el grupo de rutas del quiosco pasa a ser solo para integraciones y señalización) y diseño confirmado a S2–S5, con aviso del recorte de touchcation (filas `descartado: touchcation fuera de alcance`). A S6 se le pidió detenerse sin hacer commit; la rama `l13/quiosco` y el worktree `CRMSystem-quiosco` quedan sin uso (no se borran sin Antony).
- 2026-11-26: S6 se detuvo y cerró la sesión: worktree limpio, sin commits ni push.
- 2026-11-26: diseño de S4 aprobado con cambios menores (`hyouji_flg` → `status`; revisar `felica_cards.name`; rutas de dispositivos en `routes/publico/senalizacion.php`). CONVENCIONES: nuevo `routes/publico/<modulo>.php` (fuera del grupo `admin`, sin CSRF ni prefijo; también para S5), redirección `/hp/uploads/*` a cargo de S1 y configuración por módulo en tablas `<algo>_settings`. Pedidos de S4 reenviados a S1, S2, S3 y S5; GoTel/PDF y ビーコン escalados a Antony.
- 2026-11-26: diseños de S3 (≈71,5 pts, sin partir la sesión por ahora) y S5 (75 pts) aprobados con cambios menores. CONVENCIONES: códigos legacy en este orden: `id = cd` (1:1 y en contrato) → `legacy_cd` (tablas fundidas y en contrato) → `legacy_id_map` de S1; CSV en SJIS-win. `category3/reservation` → S2. `CustomerDevice` sigue siendo de S2 (regla `Customer*`); S3 lo lee. Cupones migrados sin importe: `type`/`value` nulos = cupón solo de texto, como el legacy (sin preguntar a Antony). A Antony: push (g-api frente a FCM), uso de 新着情報/DM/スマトピ y uso de 勤怠 sin touchcation.
- 2026-11-26: diseño de S2 aprobado (76 pts). Rutas `admin.clientes.*`. CONVENCIONES: `PosGateway` propio de cada módulo en `app/Services/<Modulo>/` (sin interfaz común). La pantalla 顧客グループ設定 es de S2 y usa el modelo `CustomerGroup` de S3 sin cambiarlo. Decidido sin Antony: las ventas viejas (`tb_customer_sub` -1/-2) se importan a `sales`/`expenses`; los campos dudosos (`customer_level`, `school*`, `mail_comment`, `biyou`) se migran como datos, aunque solo tengan pantalla si el legacy la tenía; sin límite de 60 días en los informes (como el legacy).
- 2026-11-26: las pruebas de pantallas fallaban en los worktrees, con 30–70 s por prueba, porque faltaba `public/build/` (Vite, fuera de git). `scripts/en-docker.sh` ahora lo copia del principal si falta, y ya se copió a los 5 worktrees. La línea base de S1 se repite.
- 2026-11-26: cambio de base de Antony (bf3ab66). S2–S5 se detuvieron y guardaron en stash su capa de datos sobre tablas nuevas, que es recuperable. La capa de datos de S4 (23f14c9) no se integra. En CONVENCIONES §4 se quitan las reglas que dependían del esquema nuevo (`<algo>_settings`, `id = cd`/`legacy_cd`/`legacy_id_map`, `hyouji_flg` → `status`). Se quedan: `routes/publico`, `PosGateway` por módulo, CSV SJIS-win y propiedad de modelos. Los diseños aprobados conservan la paridad, las rutas y los servicios; cada sesión rehace las secciones de esquema y UI.
- 2026-11-27: Fase 1 de S1 integrada: Laravel 13.44.1 sobre PHP 8.5.13, pruebas en verde, build OK, web 200 en `CRMSystem/`. Diseños v2 sobre la BD y la UI del legacy aprobados e integrados: S2 (84 pts), S3 (≈83), S4 (sin cambio de orden) y S5 (63, de los que 22 esperan 勤怠). También se integraron `SignageFeedBuilder` sobre `tb_*` (S4) y `LegacyJson` (S5).
- 2026-11-27: CONVENCIONES:
  - Los estáticos de cada categoría o carpeta pública los copia el módulo dueño; S1 solo copia lo común.
  - Los modelos de cada módulo van en `app/Models/<Modulo>/`; los traits comunes son de S1.
  - La BD `admin` de `signage/` usa una conexión aparte `signage`, y falta un volcado (pedido a Antony).
  - `login_pass` y lo sensible se quedan como en el legacy.
  - `tb_mobile_navi` → S4.
  - `numbered_ticket` (クイック予約, solo touchcation, no está en el backup) → descartado.
- 2026-11-27: CONVENCIONES: `routes/legacy/<modulo>.php`, URL literales `/system/category<N>/…` para los AJAX y formularios de los JS del legacy. S1 las carga sin prefijo, con el guard del personal; el layout pone `X-CSRF-TOKEN` en jQuery. Estáticos de S2 integrados (8e15561).
- 2026-11-27: siguiente tarea de S1 (Antony: el login primero): modelo base y traits comunes, luego el login contra el personal del legacy, el layout idéntico y los estáticos comunes.

## Hecho

- 2026-11-26: Docker igual que crm2-php8 (PHP 8.5 FPM + Apache + PostgreSQL 18), documentación de referencia y análisis legacy.
- 2026-11-26: `CRMSystem/` con Laravel 12.69.3 en el Docker nuevo: `migrate --seed` OK (121 clientes, 13 envíos de promoción); la web responde en `http://localhost:8081`.
