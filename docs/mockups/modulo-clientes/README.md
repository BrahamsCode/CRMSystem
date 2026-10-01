# Mockups · Módulo 1: Gestión de clientes

Rediseño de la interfaz del sistema legacy (CRM system) como base para la versión en Laravel 12.

Lienzo interactivo (Claude Design): https://claude.ai/artifact/H85itqausayWcS3rntBH65

## Pantallas

| Archivo | Pantalla | Equivalente legacy |
|---|---|---|
| `Main.dc.html` | Inicio · panel de módulos y avisos | 総合トップ |
| `Clientes-Buscar.dc.html` | Buscar clientes (filtros + resultados) | 顧客検索 |
| `Clientes-Estadisticas.dc.html` | Estadísticas · altas de miembros | 顧客情報集計 |
| `Clientes-Campos.dc.html` | Campos de registro, búsqueda y CSV | 登録・検索項目設定 |

`canvas.json` es el índice del lienzo (posición y tamaño de cada pantalla).

## Notas

- Los archivos `.dc.html` usan el formato de Claude Design y cargan `support.js` del propio lienzo, así que se visualizan desde el enlace de arriba, no abriéndolos directamente en el navegador. Sirven como referencia de estructura, campos y estilos para pasarlos a Blade.
- Las cifras, clientes de ejemplo y avisos son datos de prueba; los valores reales aparecen como marcadores (`[FECHA]`, `[TOTAL]`).
- Estilo base: tipografía Manrope, fondo `#F5F6F8`, acento del módulo `#C8343A`, texto `#14171F`.

## Capturas del sistema legacy

Referencia de las pantallas originales en `legacy/`: `inicio.webp`, `buscar-clientes.webp`, `estadisticas.png`, `campos-csv.png`.
