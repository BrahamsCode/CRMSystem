# Configuracion UNICA de las sesiones de la orquestacion del CRM (Laravel 13).
# La cargan iniciar-sesiones.ps1 y sesion.ps1 con: . (Join-Path $PSScriptRoot 'config-sesiones.ps1')
# Para cambiar modelo, esfuerzo o carpeta de una sesion, se cambia solo aqui.
# Nota: archivo solo con caracteres ASCII a proposito (PowerShell 5.1 lee mal los acentos sin BOM).

$repo = Split-Path -Parent $PSScriptRoot      # C:\Users\User\VivaTech\CRMSystem
$base = Split-Path -Parent $repo              # C:\Users\User\VivaTech

# S0 trabaja en el repo principal (rama de integracion = la que tenga CRMSystem/).
# S1 a S6 trabajan cada una en su worktree y su rama.
$ConfigSesiones = [ordered]@{
    S0 = @{ Nombre = 'S0 Coordinador CRM'; Carpeta = 'CRMSystem';             Rama = $null;            Modelo = 'opus';   Esfuerzo = 'high';   Archivo = 'S0-coordinador.md' }
    S1 = @{ Nombre = 'S1 Nucleo';          Carpeta = 'CRMSystem-nucleo';      Rama = 'l13/nucleo';      Modelo = 'opus';   Esfuerzo = 'high';   Archivo = 'S1-nucleo.md' }
    S2 = @{ Nombre = 'S2 Clientes';        Carpeta = 'CRMSystem-clientes';    Rama = 'l13/clientes';    Modelo = 'sonnet'; Esfuerzo = 'medium'; Archivo = 'S2-clientes.md' }
    S3 = @{ Nombre = 'S3 Promocion';       Carpeta = 'CRMSystem-promocion';   Rama = 'l13/promocion';   Modelo = 'sonnet'; Esfuerzo = 'medium'; Archivo = 'S3-promocion.md' }
    S4 = @{ Nombre = 'S4 Contenido';       Carpeta = 'CRMSystem-contenido';   Rama = 'l13/contenido';   Modelo = 'sonnet'; Esfuerzo = 'medium'; Archivo = 'S4-contenido.md' }
    S5 = @{ Nombre = 'S5 Operaciones';     Carpeta = 'CRMSystem-operaciones'; Rama = 'l13/operaciones'; Modelo = 'sonnet'; Esfuerzo = 'high';   Archivo = 'S5-operaciones.md' }
    S6 = @{ Nombre = 'S6 Quiosco';         Carpeta = 'CRMSystem-quiosco';     Rama = 'l13/quiosco';     Modelo = 'sonnet'; Esfuerzo = 'medium'; Archivo = 'S6-quiosco.md' }
}

function Get-DirSesion([string]$s) { return (Join-Path $base $ConfigSesiones[$s].Carpeta) }
