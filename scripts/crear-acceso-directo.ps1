# Crea en el Escritorio un acceso directo "CRM Laravel 13" que levanta Docker y abre las sesiones.
# Se ejecuta una sola vez:  powershell -ExecutionPolicy Bypass -File scripts\crear-acceso-directo.ps1
# Nota: archivo solo con caracteres ASCII a proposito.

$repo = Split-Path -Parent $PSScriptRoot
$bat = Join-Path $repo 'INICIAR-CRM.bat'
$escritorio = [Environment]::GetFolderPath('Desktop')
$destino = Join-Path $escritorio 'CRM Laravel 13.lnk'

$shell = New-Object -ComObject WScript.Shell
$acceso = $shell.CreateShortcut($destino)
$acceso.TargetPath = $bat
$acceso.WorkingDirectory = $repo
$acceso.Description = 'Levanta Docker y abre las sesiones S0 a S6 de la migracion del CRM a Laravel 13'
$acceso.IconLocation = "$env:SystemRoot\System32\shell32.dll,137"
$acceso.Save()

Write-Host "Acceso directo creado: $destino"
