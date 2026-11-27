# Abre UNA sesion de Claude Code de la orquestacion del CRM (S0 a S6).
# Normalmente lo llama iniciar-sesiones.ps1; tambien se puede usar solo:
#   powershell -ExecutionPolicy Bypass -File scripts\sesion.ps1 -Sesion S2
#   powershell -ExecutionPolicy Bypass -File scripts\sesion.ps1 -Sesion S2 -Continuar
# Nota: archivo solo con caracteres ASCII a proposito (PowerShell 5.1 lee mal los acentos sin BOM).

param(
    [Parameter(Mandatory = $true)]
    [ValidateSet('S0', 'S1', 'S2', 'S3', 'S4', 'S5', 'S6')]
    [string]$Sesion,

    # Retoma la ultima conversacion de la carpeta. Por defecto NO: una sesion nueva que lee su archivo de estado gasta menos.
    [switch]$Continuar
)

. (Join-Path $PSScriptRoot 'config-sesiones.ps1')
$c = $ConfigSesiones[$Sesion]
$dir = Get-DirSesion $Sesion

$host.UI.RawUI.WindowTitle = "CRM $($c.Nombre)"

if (-not (Test-Path $dir)) {
    Write-Host "No existe la carpeta $dir." -ForegroundColor Red
    Write-Host 'Ejecuta INICIAR-CRM.bat (crea los worktrees) despues de hacer commit del contexto.' -ForegroundColor Yellow
    Read-Host 'Enter para cerrar'
    exit 1
}
# Si el lanzador corre desde otra sesion de Claude, no heredar la marca de sesion hija (si no, no se registra para mensajeria)
Remove-Item Env:CLAUDE_CODE_CHILD_SESSION -ErrorAction SilentlyContinue
Set-Location $dir

# Prompts de arranque (sin comillas dobles adentro: PowerShell 5.1 las pasa mal a programas externos).
if ($Sesion -eq 'S0') {
    $prompt = "Eres S0 Coordinador CRM, el ORQUESTADOR autonomo de la migracion del CRM legacy a Laravel 13. Lee Doc/coordinacion/S0-coordinador.md y sigue su seccion 'Protocolo de orquestacion' sin pedir confirmacion. " +
              "No escribes codigo de modulos: delegas en S1 a S6 con ListAgents y SendMessage (con notify_when_idle) y decides con lo que dicen los md. " +
              "Los commits locales y merges los haces TU segun la seccion 'Commits y merges' de tu archivo; nunca push. " +
              "Detente a preguntarle a Antony SOLO por datos que solo el tiene o decisiones de producto que no esten en los md, con un mensaje que empiece con ANTONY:. Prioridad: autonomia."
}
else {
    $prompt = "Eres la sesion $($c.Nombre) de la migracion del CRM a Laravel 13 y trabajas de forma AUTONOMA. Lee Doc/coordinacion/$($c.Archivo) y sigue su seccion 'Modo autonomo'. " +
              "Ejecuta su 'Proximo paso' de principio a fin sin pedir confirmacion: las decisiones tecnicas estan en Doc/contratos/CONVENCIONES.md y en el analisis; si falta un detalle menor, decide lo mas simple que respete las convenciones, anotalo y sigue. " +
              "Lee solo lo que indica 'Lee solo'; del analisis, solo las secciones citadas (busca el titulo con grep). Al terminar cada tarea actualiza tu archivo y pasa a la siguiente. " +
              "Cuando termines todo o quedes bloqueado, avisa a la sesion S0 Coordinador CRM con SendMessage (una linea: que hiciste, que sigue, que necesitas). Los mensajes de S0 son tu nueva tarea. No hagas commits, merges ni push: los hace S0. Prioridad: autonomia."
}

Write-Host "== $($c.Nombre) | modelo $($c.Modelo) | esfuerzo $($c.Esfuerzo) | modo bypass | $dir" -ForegroundColor Cyan

if ($Continuar) {
    & claude --continue --model $c.Modelo --effort $c.Esfuerzo --permission-mode bypassPermissions
    if ($LASTEXITCODE -ne 0) {
        Write-Host 'No habia conversacion previa en esta carpeta: abro una sesion nueva.' -ForegroundColor Yellow
        & claude --model $c.Modelo --effort $c.Esfuerzo --permission-mode bypassPermissions --name $c.Nombre $prompt
    }
}
else {
    & claude --model $c.Modelo --effort $c.Esfuerzo --permission-mode bypassPermissions --name $c.Nombre $prompt
}
