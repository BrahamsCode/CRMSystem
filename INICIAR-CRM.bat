@echo off
rem Levanta Docker y abre las sesiones de la migracion del CRM a Laravel 13 (S0 a S5) en Windows Terminal. Doble clic.
rem   INICIAR-CRM.bat              sesiones nuevas que leen su archivo de estado (recomendado)
rem   INICIAR-CRM.bat continuar    retoma la ultima conversacion de cada sesion
rem   INICIAR-CRM.bat verificar    revisa todo sin abrir nada
set "LANZADOR=%~dp0scripts\iniciar-sesiones.ps1"
if /I "%~1"=="continuar" (
  powershell -NoProfile -ExecutionPolicy Bypass -File "%LANZADOR%" -Continuar
) else if /I "%~1"=="verificar" (
  powershell -NoProfile -ExecutionPolicy Bypass -File "%LANZADOR%" -SoloVerificar
  pause
) else (
  powershell -NoProfile -ExecutionPolicy Bypass -File "%LANZADOR%"
)
