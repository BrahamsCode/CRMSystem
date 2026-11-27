# Lanzador del CRM (Laravel 13): levanta Docker, prepara los worktrees y abre las sesiones S0 a S6,
# cada una en su pestana de Windows Terminal, con su modelo y esfuerzo.
# Uso normal: doble clic en INICIAR-CRM.bat
#   -Sesiones S0,S1      abre solo esas
#   -Continuar           retoma la ultima conversacion de cada una (gasta mas tokens)
#   -SoloVerificar       revisa todo y muestra lo que haria, sin abrir nada
#   -SinDocker           no levanta los contenedores
# Nota: archivo solo con caracteres ASCII a proposito (PowerShell 5.1 lee mal los acentos sin BOM).

param(
    [ValidateSet('S0', 'S1', 'S2', 'S3', 'S4', 'S5', 'S6')]
    [string[]]$Sesiones = @('S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S0'),  # S0 al final: cuando arranca, las demas ya existen
    [switch]$Continuar,
    [switch]$SoloVerificar,
    [switch]$SinDocker
)

$ErrorActionPreference = 'Continue'
. (Join-Path $PSScriptRoot 'config-sesiones.ps1')
$scriptSesion = Join-Path $PSScriptRoot 'sesion.ps1'
$settingsFuente = Join-Path $PSScriptRoot 'claude-settings.json'

function Write-Paso($texto) { Write-Host "-> $texto" -ForegroundColor Cyan }
function Write-Aviso($texto) { Write-Host "!! $texto" -ForegroundColor Yellow }
function Write-Fallo($texto) { Write-Host "XX $texto" -ForegroundColor Red }
function Exit-Fallo($texto) { Write-Fallo $texto; if (-not $SoloVerificar) { Read-Host 'Enter para cerrar' }; exit 1 }

# 1. Herramientas
Write-Paso 'Verificando Claude Code, git y Docker'
if (-not (Get-Command claude -ErrorAction SilentlyContinue)) { Exit-Fallo 'No se encontro el comando claude.' }
if (-not (Get-Command git -ErrorAction SilentlyContinue)) { Exit-Fallo 'No se encontro git.' }
$hayDocker = [bool](Get-Command docker -ErrorAction SilentlyContinue)
if (-not $hayDocker) { Write-Aviso 'No se encontro docker: las sesiones no podran correr pruebas.' }

# 2. Configuracion de Claude del repo principal (.claude/ esta en .gitignore: se copia desde scripts/)
function Set-SettingsProyecto([string]$dir) {
    $carpeta = Join-Path $dir '.claude'
    $archivo = Join-Path $carpeta 'settings.json'
    if ($SoloVerificar) { Write-Paso "   copiaria scripts/claude-settings.json a $archivo"; return }
    # Se copia siempre: scripts/claude-settings.json es la unica fuente de las reglas
    New-Item -ItemType Directory -Force $carpeta | Out-Null
    Copy-Item $settingsFuente $archivo -Force
    Write-Paso "   configuracion al dia: $archivo"
}
Set-SettingsProyecto $repo

# 3. Lo que necesitan los worktrees debe estar en commit (los worktrees solo ven lo que esta en commit)
Write-Paso 'Verificando que el contexto este en commit'
$necesarios = @(
    'CLAUDE.md', 'Doc/MIGRACION-LARAVEL13.md', 'Doc/contratos/CONVENCIONES.md',
    'Doc/planificacion/ORCHESTRATION.md', 'Doc/coordinacion/README.md',
    'Doc/coordinacion/S1-nucleo.md', 'Doc/coordinacion/S2-clientes.md', 'Doc/coordinacion/S3-promocion.md',
    'Doc/coordinacion/S4-contenido.md', 'Doc/coordinacion/S5-operaciones.md', 'Doc/coordinacion/S6-quiosco.md',
    'Doc/analisis-legacy/README.md', 'scripts/en-docker.sh', 'scripts/claude-settings.json', 'docker/docker-compose.yml'
)
$faltan = @()
foreach ($archivo in $necesarios) {
    $enGit = git -C $repo ls-files -- $archivo
    if (-not $enGit) { $faltan += $archivo }
}
$pendientes = git -C $repo status --porcelain -- CLAUDE.md Doc scripts docker
if ($faltan.Count -gt 0) {
    Write-Aviso 'Estos archivos no estan en commit; S1 a S6 no los veran (no se crearan worktrees nuevos):'
    $faltan | ForEach-Object { Write-Host "     $_" }
}
elseif ($pendientes) {
    Write-Aviso 'Hay cambios sin commit en CRMSystem; S1 a S6 no los veran hasta el commit y el merge:'
    $pendientes | Select-Object -First 10 | ForEach-Object { Write-Host "     $_" }
}
else { Write-Host '   OK' -ForegroundColor Green }
$rama = git -C $repo branch --show-current
Write-Paso "Rama de integracion (CRMSystem/): $rama"

# 4. Docker: un solo stack para todas las sesiones (cada sesion usa su propia BD, ver scripts/en-docker.sh)
if ($hayDocker -and -not $SinDocker) {
    $dirDocker = Join-Path $repo 'docker'
    if ($SoloVerificar) { Write-Paso "Levantaria Docker: docker compose up -d (en $dirDocker)" }
    else {
        Write-Paso 'Levantando Docker (app, web, db, mailpit)'
        Push-Location $dirDocker
        docker compose up -d
        if ($LASTEXITCODE -ne 0) { Write-Aviso 'docker compose up fallo. Las sesiones abren igual; revisar Docker Desktop.' }
        Pop-Location
    }
}

# 5. Worktrees de S1 a S6
# Permisos locales de los trabajadores: commit y push SOLO de su rama l13/<grupo>.
# Lo unico bloqueado es lo que los haria pisarse: merge (lo hace S0), cambiar de rama,
# tocar otro worktree (git -C) y subir a la rama de integracion.
$permisosTrabajador = @'
{
  "permissions": {
    "deny": [
      "Bash(git merge *)",
      "Bash(git switch *)",
      "Bash(git checkout *)",
      "Bash(git -C *)",
      "Bash(git push * laravel13*)",
      "Bash(git push * HEAD:laravel13*)"
    ]
  }
}
'@
function Set-PermisosTrabajador([string]$dir) {
    Set-SettingsProyecto $dir
    $archivo = Join-Path $dir '.claude\settings.local.json'
    if ($SoloVerificar) { return }
    # Se reescribe siempre: asi un cambio de reglas llega a los worktrees existentes
    Set-Content -Path $archivo -Value $permisosTrabajador -Encoding Ascii
    Write-Paso "   permisos locales: $archivo (commit y push de su rama; sin merge ni cambio de rama)"
}

$abrir = @()
foreach ($s in $Sesiones) {
    if ($s -eq 'S0') { $abrir += $s; continue }
    $c = $ConfigSesiones[$s]
    $dir = Get-DirSesion $s
    if (Test-Path $dir) {
        Write-Paso "$s : worktree existente en $dir"
        Set-PermisosTrabajador $dir
        $abrir += $s
        continue
    }
    if ($faltan.Count -gt 0) { Write-Aviso "$s : no se crea el worktree hasta que el contexto este en commit. Se omite."; continue }
    if ($SoloVerificar) { Write-Paso "$s : crearia el worktree $dir (rama $($c.Rama))"; $abrir += $s; continue }
    Write-Paso "$s : creando worktree $dir (rama $($c.Rama))"
    $ramaExiste = git -C $repo branch --list $c.Rama
    if ($ramaExiste) { git -C $repo worktree add $dir $c.Rama }
    else { git -C $repo worktree add -b $c.Rama $dir }
    if ($LASTEXITCODE -eq 0) { Set-PermisosTrabajador $dir; $abrir += $s } else { Write-Fallo "$s : no se pudo crear el worktree. Se omite." }
}
if ($abrir.Count -eq 0) { Exit-Fallo 'No hay sesiones para abrir.' }

# 6. Abrir las sesiones
$extra = ''
if ($Continuar) { $extra = ' -Continuar' }
$usarWt = [bool](Get-Command wt -ErrorAction SilentlyContinue)
if ($usarWt) {
    $pestanas = foreach ($s in $abrir) {
        "new-tab --title `"CRM $s`" powershell -NoExit -ExecutionPolicy Bypass -File `"$scriptSesion`" -Sesion $s$extra"
    }
    $argumentos = $pestanas -join ' ; '
    if ($SoloVerificar) { Write-Paso 'Abriria Windows Terminal con:'; Write-Host "   wt $argumentos"; exit 0 }
    Write-Paso "Abriendo $($abrir.Count) pestanas en Windows Terminal: $($abrir -join ', ')"
    Start-Process wt -ArgumentList $argumentos
}
else {
    foreach ($s in $abrir) {
        $argumentos = "-NoExit -ExecutionPolicy Bypass -File `"$scriptSesion`" -Sesion $s$extra"
        if ($SoloVerificar) { Write-Paso "Abriria: powershell $argumentos"; continue }
        Start-Process powershell -ArgumentList $argumentos
    }
}
