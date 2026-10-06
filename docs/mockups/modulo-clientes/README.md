# Mockups · Módulo 1: Gestión de clientes

Rediseño de la interfaz del sistema legacy (CRM system) como base para la versión en Laravel 12.

Lienzo interactivo (Claude Design): https://claude.ai/artifact/H85itqausayWcS3rntBH65

## Pantallas

| Archivo | Pantalla | Legacy | Captura |
|---|---|---|---|
| `Login.dc.html` | Iniciar sesión | ログイン | `legacy/00-login.png` |
| `Main.dc.html` | Inicio · módulos y avisos | 総合トップ | `legacy/01-portal-inicio.png` |
| `Clientes-Resumen.dc.html` | Resumen del módulo (altas y bajas) | 顧客管理 | `legacy/03-dashboard-clientes.png` |
| `Clientes-Nuevo.dc.html` | Nuevo cliente (con paso de confirmación) | 顧客新規登録 | `legacy/07-nuevo-cliente.png` |
| `Clientes-Buscar.dc.html` | Buscar clientes y resultados | 顧客検索 | `legacy/04`, `legacy/14` |
| `Clientes-Ficha.dc.html` | Ficha del cliente (12 pestañas) | 顧客詳細 | `legacy/15-detalle-cliente.png` |
| `Clientes-Visita.dc.html` | Registrar visita | 来店処理 | `legacy/08-procesamiento-visita.png` |
| `Clientes-Estadisticas.dc.html` | Estadísticas: total por sexo y edad, altas por año | 顧客情報集計 | `legacy/05`, `24`, `25` |
| `Clientes-Campos.dc.html` | Campos de registro, búsqueda y CSV | 登録・検索項目設定 | `legacy/06-configuracion-campos.png` |
| `Clientes-Grupos.dc.html` | Grupos de clientes | 顧客グループ設定 | `legacy/09-grupos-clientes.png` |
| `Clientes-Motivos.dc.html` | Motivos de primera visita | 初回来店動機項目設定 | `legacy/10-motivos-visita.png` |
| `Clientes-Rangos.dc.html` | Rangos de clientes | 顧客ランク設定 | `legacy/11-rangos-clientes.png` |
| `Clientes-Reglas.dc.html` | Asignación automática de rangos | 顧客ランク振り分け設定 | `legacy/12-distribucion-rangos.png` |
| `Clientes-InfoAdicional.dc.html` | Categorías de información adicional | 顧客追加情報設定 | `legacy/13`, `17`, `18`, `23` |
| `Clientes-InfoAdicional-Campos.dc.html` | Campos de cada categoría | 検索フォーム部品設定 | `legacy/19`–`22` |

`canvas.json` es el índice del lienzo (posición y tamaño de cada pantalla).

## Notas

- Los archivos `.dc.html` usan el formato de Claude Design y cargan `support.js` del propio lienzo, así que se visualizan desde el enlace de arriba, no abriéndolos directamente en el navegador. Sirven como referencia de estructura, campos y estilos para pasarlos a Blade.
- Las cifras, clientes de ejemplo y avisos son datos de prueba; los valores reales aparecen como marcadores (`[FECHA]`, `[TOTAL]`).
- Estilo base: tipografía Manrope, fondo `#F5F6F8`, acento del módulo `#C8343A`, texto `#14171F`.

## Capturas del sistema legacy

Pantallas originales en `legacy/`, numeradas de `00` a `25`. No existen la `02` ni la `16` porque eran duplicados exactos de la `01` y la `09`. La pantalla de estadísticas del legacy abría el informe en una ventana emergente (`05` → `24`, `25`); en el rediseño el informe se muestra directamente.

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

Todas las pantallas (Login, Inicio y las 14 del módulo de clientes) tienen un botón **Tema** en la barra superior y usan la misma lógica de tema (`_fuente/kit.js`). El tema elegido se guarda en el navegador (`localStorage`, clave `crm-theme-v1`) y se aplica en todas: colores, modo claro/oscuro, disposición del menú (doble, lateral o superior), tarjetas, esquinas, densidad y tipografía. Las 6 propuestas de la página «Temas y propuestas» son vistas fijas para presentar.

## Código fuente de las pantallas (`_fuente/`)

Las pantallas del módulo se generan desde una plantilla común para que todas compartan menú, barra superior y panel de tema:

- `shell.html`: plantilla (menú según disposición, barra superior con botón Tema, panel de personalización).
- `kit.js`: lógica del tema (propuestas, variables, menú del módulo).
- `pages/*.page`: contenido y lógica de cada pantalla.
- `build.py`: genera los `.dc.html` (`python3 _fuente/build.py`).

## Base de datos

Las tablas que necesitan estas pantallas, con los estándares de la empresa, están en [`docs/base-de-datos/MODULO_CLIENTES.md`](../../base-de-datos/MODULO_CLIENTES.md).
