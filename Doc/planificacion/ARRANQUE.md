# Arranque con un clic

> Cómo prender todo cada día. Roles: [ORCHESTRATION.md](ORCHESTRATION.md), sección 3.

## 1. Preparación (una sola vez)

1. **Hecho (2026-11-26):** el contexto está en commit en la rama de integración **`laravel13`** (subida a GitHub). `CRMSystem/` debe estar en `laravel13`; los worktrees salen de ahí.
2. **Acceso directo en el Escritorio** ("CRM Laravel 13"):
   ```powershell
   powershell -ExecutionPolicy Bypass -File C:\Users\User\VivaTech\CRMSystem\scripts\crear-acceso-directo.ps1
   ```
3. **Probar sin abrir nada:** `INICIAR-CRM.bat verificar`.
4. La primera vez que se abre cada carpeta (`CRMSystem` y los 5 worktrees), Claude Code pregunta si se confía en ella: aceptar una vez por carpeta.

## 2. Cada día

**Doble clic en "CRM Laravel 13"** (o en `INICIAR-CRM.bat`). El lanzador ([scripts/iniciar-sesiones.ps1](../../scripts/iniciar-sesiones.ps1)):

1. Verifica Claude Code, git y Docker, y que el contexto esté en commit.
2. Copia `scripts/claude-settings.json` a `.claude/settings.json` de cada carpeta en cada arranque (es la única fuente de las reglas; `.claude/` está en `.gitignore`).
3. **Levanta Docker** (`docker compose up -d`: app PHP 8.5 FPM, web Apache, PostgreSQL 18, Mailpit).
4. Crea los worktrees que falten (`CRMSystem-nucleo`, `-clientes`, `-promocion`, `-contenido`, `-operaciones`, al lado de `CRMSystem`) y les escribe `settings.local.json` (commit y push de su rama; sin merge ni cambio de rama).
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

**Todos los permisos** (Antony, 2026-11-26), salvo lo que haría que las sesiones se pisen o lo que no tiene vuelta atrás:
- Modo `bypassPermissions` (lo pasa el lanzador) y `allow` amplio: Bash, lectura y edición, web, Playwright, Docker y git (incluido `push`). Pueden leer `Doc/legacy-crm2/env-legacy.example`.
- **Prohibido para todas:** `push --force`, push a `main` o a `brahamscode/*`, `reset --hard`, `clean`, `rebase`, `worktree remove`, `rm -rf /` o `~`; leer `vendor/`, `node_modules/` y los `.backup` (solo por tamaño).
- **S1 a S5:** además, sin `merge`, `switch`, `checkout`, `git -C` ni push a `laravel13`: cada una escribe solo en su rama `l13/<grupo>`; S0 integra.
- `crossSessionInbound: accept`: los mensajes entre sesiones llegan sin diálogo de aprobación.

## 5. Problemas comunes

| Síntoma | Solución |
|---|---|
| "No existe la carpeta CRMSystem-…" | Falta el commit del contexto. Hacerlo y volver a abrir |
| Una sesión no aparece en `ListAgents` | Está detenida en la pregunta de confianza de la carpeta, o se lanzó desde otra sesión de Claude (el script ya quita `CLAUDE_CODE_CHILD_SESSION`) |
| `en-docker.sh`: falta la imagen | `cd docker && docker compose build app` |
| Una sesión no sabe qué hacer | Su archivo no tiene "Próximo paso": S0 o Antony lo completan |
