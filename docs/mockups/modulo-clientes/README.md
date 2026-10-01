# Mockups · Módulo 1: Gestión de clientes

Rediseño de la interfaz del sistema legacy (CRM system) como base para la versión en Laravel 12.

Lienzo interactivo (Claude Design): https://claude.ai/artifact/H85itqausayWcS3rntBH65

## Pantallas

| Archivo | Pantalla | Equivalente legacy |
|---|---|---|
| `Login.dc.html` | Iniciar sesión | (pantalla de acceso) |
| `Main.dc.html` | Inicio · panel de módulos y avisos | 総合トップ |
| `Clientes-Resumen.dc.html` | Resumen del módulo (altas y bajas) | 顧客管理 (portada) |
| `Clientes-Nuevo.dc.html` | Nuevo cliente (formulario con los campos de la BD) | 顧客新規登録 |
| `Clientes-Buscar.dc.html` | Buscar clientes (filtros + resultados) | 顧客検索 |
| `Clientes-Estadisticas.dc.html` | Estadísticas · altas de miembros | 顧客情報集計 |
| `Clientes-Campos.dc.html` | Campos de registro, búsqueda y CSV | 登録・検索項目設定 |

`canvas.json` es el índice del lienzo (posición y tamaño de cada pantalla).

## Notas

- Los archivos `.dc.html` usan el formato de Claude Design y cargan `support.js` del propio lienzo, así que se visualizan desde el enlace de arriba, no abriéndolos directamente en el navegador. Sirven como referencia de estructura, campos y estilos para pasarlos a Blade.
- Las cifras, clientes de ejemplo y avisos son datos de prueba; los valores reales aparecen como marcadores (`[FECHA]`, `[TOTAL]`).
- Estilo base: tipografía Manrope, fondo `#F5F6F8`, acento del módulo `#C8343A`, texto `#14171F`.

## Capturas del sistema legacy

Referencia de las pantallas originales en `legacy/`: `inicio.webp`, `buscar-clientes.webp`, `resumen-clientes.png`, `lista-clientes.webp`, `estadisticas.png`, `campos-csv.png`.

## Temas y propuestas de diseño

Página «Temas y propuestas» del lienzo:

- `Tema-Personalizador.dc.html`: pantalla interactiva con panel de personalización (modo claro/oscuro, color principal, disposición del menú doble/lateral/superior, estilo del menú, tarjetas, esquinas, densidad, tipografía y títulos). Genera las variables CSS del tema.
- `Propuesta-1…6-*.dc.html`: la misma pantalla con cada propuesta aplicada, para presentar al cliente.

| Propuesta | Idea | Referencia |
|---|---|---|
| 1 · Coral moderno | Diseño actual de los mockups, menú doble | — |
| 2 · Azul corporativo | Menú superior, tarjetas con sombra | [Tabler](https://github.com/tabler/tabler) (MIT) |
| 3 · Laravel Filament | Menú lateral claro, ámbar, esquinas redondeadas | [Filament](https://filamentphp.com) (MIT) |
| 4 · Minimal monocromo | Negro y blanco, compacto | [shadcn-admin](https://github.com/satnaing/shadcn-admin) (MIT) |
| 5 · Washi · Ai-iro | Papel cálido, índigo japonés, títulos Mincho | Estética japonesa tradicional |
| 6 · Nocturno | Modo oscuro | [AdminLTE](https://github.com/ColorlibHQ/AdminLTE) / Tabler (modo oscuro) |

En Laravel el tema se guarda como ajustes (por empresa o usuario) y se aplica con variables CSS y atributos `data-*` en `<body>`, como muestra el bloque que genera el panel.

### Tema compartido

Inicio, Login, Nuevo cliente y el Personalizador tienen un botón **Tema** en la barra superior. El tema elegido se guarda en el navegador (`localStorage`, clave `crm-theme-v1`) y lo aplican todas esas pantallas. Resumen, Buscar, Estadísticas y Campos siguen con el diseño Coral hasta elegir la propuesta final.
