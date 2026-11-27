# S4 — Contenido y terminales (マイページ, ホームページ, 端末管理, señalización)

**Modelo:** Sonnet 5.5, esfuerzo medio · **Carpeta:** `CRMSystem-contenido/` · **Rama:** `l13/contenido`
**Módulos:** マイページ管理 (`mypage`), ホームページ管理 (`web`), 端末管理 (`terminales`), señalización (`senalizacion`). **Es dueña de:** sus carpetas según `Doc/contratos/CONVENCIONES.md` (secciones 2 y 3).

## Modo autónomo

Trabajas sin pedir confirmación. Al terminar cada tarea: actualizar este archivo y seguir con la siguiente. Si falta un detalle menor, decide lo más simple que respete las convenciones, anótalo en "Decisiones" y sigue. Al terminar todo o bloquearte: `SendMessage` a "S0 Coordinador CRM" (una línea: qué hiciste, qué sigue, qué necesitas). Cada entregable terminado con pruebas en verde: `git add` de tus rutas, commit (mensaje en español) y `git push origin <tu rama>`; después avisa a S0, que integra en `laravel13`. No haces merge ni cambias de rama; nunca `--force`.

## Próximo paso — Modelos de los submódulos 1–5 sobre `tb_*` (cuando S0 avise que la base de S1 está integrada)

Diseño v2 aprobado. Hecho: estáticos en `public/legacy/` y ruta pública del XML. Siguiente: modelos en `app/Models/Mypage|Terminales/` (`Recommendation`, `Recruit`, `RecruitCategory`, `MobileNavi`, `SignageDisplay`, `SignageProgram`, `SignageMaterial`, `SignageSystem`) con `LegacyModel`/`HasHatuban`/`LegacySoftDeletes` de S1, y servicios `MypageConfig`/`LotteryConfig` sobre `ContentsMaster`. Rutas de AJAX y formularios del personal: `routes/legacy/<modulo>.php`.

## Tarea anterior — Diseño (sin código, hecho)

Escribir `Doc/diseno/contenido-terminales.md`:
1. **Tabla de paridad:** una fila por cada vista del análisis (`Doc/analisis-legacy/04-web-y-mypage.md`; en `05-operaciones-terminales-reservas.md` solo la sección de category7 (端末管理) **sin** 情報端末管理 (`kiosk/`); en `08-quiosco-y-senalizacion.md` solo signage y digitalsignage): archivo legacy, título (ja), ruta Laravel (`admin.<modulo>.*`), controlador@método, tablas nuevas, estado (`pendiente` / `descartado: motivo`).
2. **Modelos sobre la BD del legacy** (`CONVENCIONES.md`, sección 0): qué `tb_*` usa cada vista, un modelo Eloquent por tabla (`$table`, `$primaryKey = 'cd'`, columnas tal cual, `disp_flg = 9`, `hatuban`) y sus relaciones. Sin tablas nuevas salvo que sea imprescindible (y compatible con el legacy).
2b. **UI:** cada pantalla reproduce el HTML y los CSS del legacy (mismas clases, mismos textos); anota qué JS del legacy necesita.
3. **Servicios viejos → Laravel 13:** cada uno con su reemplazo (Mail + cola, Scheduler, StreamedResponse, Storage, Policies…).
4. **Orden de implementación** por submódulo, con dependencias de otros grupos y estimación en puntos (1 punto ≈ media jornada).
5. **Pedidos** a S1 (núcleo) y a otros grupos.
Criterio de terminado: todas las vistas del análisis tienen fila; avisar a S0 y esperar su revisión. Mientras esperas, puedes preparar factories y seeders de tu grupo (en tus carpetas).

## Después (cuando S0 apruebe el diseño y el núcleo esté integrado)

Implementar submódulo por submódulo en el orden del diseño: migraciones, modelos, Form Requests, controladores, vistas Blade (textos en `lang/ja/<modulo>.php`), rutas en `routes/modulos/<modulo>.php`, menú en `config/menu/<modulo>.php`, pruebas Pest. Cada submódulo terminado: pruebas en verde (`bash scripts/en-docker.sh php artisan test`), filas en `hecho`, commit y push de tu rama, y aviso a S0.

**Sin `hp/`:** el sitio viejo no se migra. Marca como `descartado: sin consumidor` el contenido que solo leía `hp/`, salvo que S0 diga otra cosa; lo que leen la app (`api/mpa_*`, `appview`) o la señalización sí se migra. Imágenes de `hp/uploads/` → Storage, conservando o redirigiendo las URL que devuelven las APIs y que leen los reproductores (`hp/uploads/c7/display/`).
**Fuera de alcance:** touchcation y su gestión `category7/kiosk/` (情報端末管理, `tb_nextouch_*`): confirmado que no se usan. **Señalización:** `digital_signage` de category7 + los reproductores. Los reproductores (`signage/`, `digitalsignage/`) son dispositivos: mantener su contrato (AppKey `md5(HTTP_HOST + '<>' + rank)` incluida) o avisar a S0 antes de cambiarlo. `digitalsignage` **se mantiene** (Antony): su reproductor es Flash y se rehace en HTML5 con el mismo contrato (`?shop_cd=&display_cd=`, XML del programa, ruleta y tragaperras). En desarrollo no hay displays registrados: crea datos de prueba con seeders.

## Lee solo

- `CLAUDE.md`, `Doc/contratos/CONVENCIONES.md` (sección 0 primero), `Doc/analisis-legacy/README.md` (hallazgos transversales)
- Del análisis, por secciones (grep del título): `Doc/analisis-legacy/04-web-y-mypage.md`; en `05-operaciones-terminales-reservas.md` solo la sección de category7 (端末管理) **sin** 情報端末管理 (`kiosk/`); en `08-quiosco-y-senalizacion.md` solo signage y digitalsignage
- Para dudas de comportamiento: el código en `C:\Users\User\VivaTech\crm2-php8\public_html` (solo lectura) y `Doc/legacy-crm2/MANUAL-TESTS.md` (tu módulo)

## Pedidos

Ver diseño §5. A S0: BD `admin` de `signage/` (no está en el esquema); GoTel/PDF y ビーコン (Antony). A S1: base de modelos legacy (`disp_flg`, `hatuban`, `ContentsMaster`, `AdminMaster`, `Shop`, `Terminal`), dueño de `tb_mobile_navi`, copia de JS/CSS de carpeta de módulo a `public/legacy/`, paquetes imagen/QR/PDF, conexión `beacon`, Policies.

## Decisiones

- S0 (2026-11-26): touchcation y `category7/kiosk` fuera de alcance (no se usan). La AppKey `md5(host<>rank)` era de `signageApp` (touchcation): ya no aplica a S4.
- category4 entero y lo de category6 que solo leía `hp/`: descartado: sin consumidor. Vivo: マイコレ, 2 opciones de app, オススメ, 求人, ガチャリン.
- Base del legacy (Antony, 2026-11-26): misma BD y UI. Capa de datos sobre tablas nuevas quitada (d45a652); `SignageFeedBuilder` reescrito sobre las `tb_*` (f7deb45).
- XML: se escribe al guardar en `hp/uploads/c7/display/` como el legacy; forma idéntica pero escapada, materiales borrados omitidos, 【応募資格】 corregido.
- Config de マイコレ y ガチャリン en `contents_master` (modelo de S1) vía servicios de S4; `mypage_config` sigue con `serialize()` (lo leen `mpa_*`).

## Hecho

- 2026-11-27 Estáticos de category6/7, `digitalsignage/` y `signage/` en `public/legacy/`; ruta `publico.senalizacion.display-xml` con 4 pruebas (suite 22 verde).

- 2026-11-27 Diseño rehecho sobre la BD del legacy; limpieza de la capa obsoleta; `SignageFeedBuilder` sobre `tb_*` con 8 pruebas (suite 10 verde).

- 2026-11-26 Diseño `Doc/diseno/contenido-terminales.md` (paridad, esquema con mapeo, servicios, orden 60 pts, pedidos).
