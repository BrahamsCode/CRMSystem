# Coordinación de sesiones

Un archivo por sesión. **Cada sesión lee y edita solo el suyo** (así no hay conflictos al fusionar ramas).

| Archivo | Sesión | Rama · carpeta |
|---|---|---|
| [S0-coordinador.md](S0-coordinador.md) | S0 Coordinador CRM | integración · `CRMSystem/` |
| [S1-nucleo.md](S1-nucleo.md) | S1 Nucleo | `l13/nucleo` · `CRMSystem-nucleo/` |
| [S2-clientes.md](S2-clientes.md) | S2 Clientes | `l13/clientes` · `CRMSystem-clientes/` |
| [S3-promocion.md](S3-promocion.md) | S3 Promocion | `l13/promocion` · `CRMSystem-promocion/` |
| [S4-contenido.md](S4-contenido.md) | S4 Contenido | `l13/contenido` · `CRMSystem-contenido/` |
| [S5-operaciones.md](S5-operaciones.md) | S5 Operaciones | `l13/operaciones` · `CRMSystem-operaciones/` |
| [S6-quiosco.md](S6-quiosco.md) | S6 Quiosco | `l13/quiosco` · `CRMSystem-quiosco/` |

## Formato obligatorio (para gastar pocos tokens)

- **Máximo unas 60 líneas.** Es un tablero, no un diario.
- **"Próximo paso"** va arriba y es concreto.
- **"Hecho"**: solo las últimas 5 entradas, de una línea. El historial está en git.
- **"Lee solo"**: exactamente qué documentos y secciones. Del análisis, buscar el título con grep y leer solo ese tramo.
- **"Pedidos"**: lo que se necesita de otra sesión o de S0 (paquetes, cambios a modelos ajenos, componentes del layout).
- **Al terminar cada tarea**, actualizar el archivo **antes** de seguir.

## Mensajes

- **S0 → sesión:** `SendMessage` con `notify_when_idle: true`: tarea, criterio de terminado y archivos a leer.
- **Sesión → S0:** al terminar todo o al bloquearse, `SendMessage` a "S0 Coordinador CRM" con una línea: qué hizo, qué sigue, qué necesita.
- Lo que llega por mensaje también se anota en el archivo de estado: el archivo sobrevive al cerrar la sesión, el mensaje no.

## Cómo se ven entre sí

Cada sesión trabaja en su worktree. Para ver el estado de otra: `../CRMSystem-<grupo>/Doc/coordinacion/<archivo>`. Solo S0 lo hace de forma habitual.
