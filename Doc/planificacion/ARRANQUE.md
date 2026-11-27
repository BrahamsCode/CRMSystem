# Arranque con un clic

> Cómo prender todo cada día. Roles: [ORCHESTRATION.md](ORCHESTRATION.md), sección 3.

## 1. Preparación (una sola vez)

1. **Commit del contexto** en la rama de integración (la que tenga `CRMSystem/`): `CLAUDE.md`, `INICIAR-CRM.bat`, `Doc/`, `scripts/`, `docker/`, `.gitignore`. Los worktrees solo ven lo que está en commit; el lanzador avisa si falta algo.
2. **Acceso directo en el Escritorio** ("CRM Laravel 13"):
   ```powershell
   powershell -ExecutionPolicy Bypass -File C:\Users\User\VivaTech\CRMSystem\scripts\crear-acceso-directo.ps1
   ```
3. **Probar sin abrir nada:** `INICIAR-CRM.bat verificar`.
4. La primera vez que se abre cada carpeta (`CRMSystem` y los 6 worktrees), Claude Code pregunta si se confía en ella: aceptar una vez por carpeta.

## 2. Cada día

**Doble clic en "CRM Laravel 13"** (o en `INICIAR-CRM.bat`). El lanzador ([scripts/iniciar-sesiones.ps1](../../scripts/iniciar-sesiones.ps1)):

1. Verifica Claude Code, git y Docker, y que el contexto esté en commit.
2. Copia `scripts/claude-settings.json` a `.claude/settings.json` de cada carpeta si falta (`.claude/` está en `.gitignore`).
3. **Levanta Docker** (`docker compose up -d`: app PHP 8.5 FPM, web Apache, PostgreSQL 18, Mailpit).
4. Crea los worktrees que falten (`CRMSystem-nucleo`, `-clientes`, `-promocion`, `-contenido`, `-operaciones`, `-quiosco`, al lado de `CRMSystem`) con `settings.local.json` sin commit, merge ni push.
5. Abre **una pestaña de Windows Terminal por sesión**, con su modelo, su esfuerzo y su prompt: cada sesión lee su archivo de `Doc/coordinacion/` y sigue desde "Próximo paso". S0 se abre al final y reparte el trabajo.

**Al terminar el día:** en cada pestaña, *"actualiza tu archivo de estado y termina"*.

| Comando | Qué hace |
|---|---|
| `INICIAR-CRM.bat` | Sesiones nuevas que leen su estado (**recomendado**) |
| `INICIAR-CRM.bat continuar` | Retoma la última conversación de cada sesión (gasta más tokens) |
| `INICIAR-CRM.bat verificar` | Revisa todo y muestra lo que haría |
| `powershell -ExecutionPolicy Bypass -File scripts\iniciar-sesiones.ps1 -Sesiones S0,S1` | Solo algunas sesiones |
| `powershell -ExecutionPolicy Bypass -File scripts\iniciar-sesiones.ps1 -SinDocker` | Sin levantar Docker |

## 3. Docker compartido, BD por sesión

Un solo stack para todas. Cada worktree corre PHP con **su propio código** y **su propia BD** con [scripts/en-docker.sh](../../scripts/en-docker.sh):

| Carpeta | BD de desarrollo | BD de pruebas |
|---|---|---|
| `CRMSystem` | `crmsystem2` | `crmsystem2_testing` |
| `CRMSystem-clientes` (y las demás) | `crmsystem2_clientes` | `crmsystem2_testing_clientes` |

```sh
bash scripts/en-docker.sh php artisan test
bash scripts/en-docker.sh php artisan migrate --seed
```

La web (`http://localhost:8081`) sirve el código de `CRMSystem/` (rama de integración).

## 4. Permisos

- Modo `bypassPermissions` (lo pasa el lanzador); las reglas `deny` se aplican siempre.
- **Prohibido para todas:** `git push`, `reset --hard`, `clean`, `rebase`, `checkout`, `stash`, `worktree remove`, `rm -rf`, leer `env-legacy.example` (claves reales), `vendor/`, `node_modules/` y los `.backup`.
- **S1 a S6:** además, sin `commit`, `merge`, `switch` ni `git -C` (`settings.local.json` de su worktree, lo crea el lanzador).
- `crossSessionInbound: accept`: los mensajes entre sesiones llegan sin diálogo de aprobación.

## 5. Problemas comunes

| Síntoma | Solución |
|---|---|
| "No existe la carpeta CRMSystem-…" | Falta el commit del contexto. Hacerlo y volver a abrir |
| Una sesión no aparece en `ListAgents` | Está detenida en la pregunta de confianza de la carpeta, o se lanzó desde otra sesión de Claude (el script ya quita `CLAUDE_CODE_CHILD_SESSION`) |
| `en-docker.sh`: falta la imagen | `cd docker && docker compose build app` |
| Una sesión no sabe qué hacer | Su archivo no tiene "Próximo paso": S0 o Antony lo completan |
