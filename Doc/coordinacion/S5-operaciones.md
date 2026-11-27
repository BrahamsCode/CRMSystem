# S5 — Operaciones e integraciones externas

**Modelo:** Sonnet 5.5, esfuerzo alto · **Carpeta:** `CRMSystem-operaciones/` · **Rama:** `l13/operaciones`
**Módulos:** 業務支援管理 (`operaciones`: staff, 勤怠, turnos, horarios) e integraciones externas (`integraciones`). **Es dueña de:** sus carpetas según `Doc/contratos/CONVENCIONES.md` (secciones 2 y 3).

## Modo autónomo

Trabajas sin pedir confirmación. Al terminar cada tarea: actualizar este archivo y seguir con la siguiente. Si falta un detalle menor, decide lo más simple que respete las convenciones, anótalo en "Decisiones" y sigue. Al terminar todo o bloquearte: `SendMessage` a "S0 Coordinador CRM" (una línea: qué hiciste, qué sigue, qué necesitas). Cada entregable terminado con pruebas en verde: `git add` de tus rutas, commit (mensaje en español) y `git push origin <tu rama>`; después avisa a S0, que integra en `laravel13`. No haces merge ni cambias de rama; nunca `--force`.

## Próximo paso — Diseño (sin código)

> **2026-11-26, cambio de base (Antony):** misma BD y misma UI que el legacy (`CONVENCIONES.md`, sección 0). Si ya escribiste tu diseño con tablas nuevas, rehaz la parte de esquema y UI. — **rehecho sobre la BD legacy el 2026-11-27, esperando revisión de S0**

Escribir `Doc/diseno/operaciones-integraciones.md`:
1. **Tabla de paridad:** una fila por cada vista del análisis (en `05-operaciones-terminales-reservas.md` solo la sección de category5 (業務支援管理); `07-pos-api-y-externos.md` (todo salvo `category11`, `category12`, `r/` y `reserve/`)): archivo legacy, título (ja), ruta Laravel (`admin.<modulo>.*`), controlador@método, tablas nuevas, estado (`pendiente` / `descartado: motivo`).
2. **Modelos sobre la BD del legacy** (`CONVENCIONES.md`, sección 0): qué `tb_*` usa cada vista, un modelo Eloquent por tabla (`$table`, `$primaryKey = 'cd'`, columnas tal cual, `disp_flg = 9`, `hatuban`) y sus relaciones. Sin tablas nuevas salvo que sea imprescindible (y compatible con el legacy).
2b. **UI:** cada pantalla reproduce el HTML y los CSS del legacy (mismas clases, mismos textos); anota qué JS del legacy necesita.
3. **Servicios viejos → Laravel 13:** cada uno con su reemplazo (Mail + cola, Scheduler, StreamedResponse, Storage, Policies…).
4. **Orden de implementación** por submódulo, con dependencias de otros grupos y estimación en puntos (1 punto ≈ media jornada).
5. **Pedidos** a S1 (núcleo) y a otros grupos.
Criterio de terminado: todas las vistas del análisis tienen fila; avisar a S0 y esperar su revisión. Mientras esperas, puedes preparar factories y seeders de tu grupo (en tus carpetas).

## Después (cuando S0 apruebe el diseño y el núcleo esté integrado)

Implementar submódulo por submódulo en el orden del diseño: migraciones, modelos, Form Requests, controladores, vistas Blade (textos en `lang/ja/<modulo>.php`), rutas en `routes/modulos/<modulo>.php`, menú en `config/menu/<modulo>.php`, pruebas Pest. Cada submódulo terminado: pruebas en verde (`bash scripts/en-docker.sh php artisan test`), filas en `hecho`, commit y push de tu rama, y aviso a S0.

**Integraciones = contratos de alto riesgo:** API JWT de la tienda móvil (13 endpoints, con sus erratas `discription`/`cutomer_cd` y errores con HTTP 200), `mpa_*`, `appview/`, `tool/api`, CTI, `version/`. **Mismas URL, mismos parámetros, mismas respuestas byte a byte.** Una prueba Pest de contrato por endpoint, comparando con la respuesta del legacy. Seguridad mejorada solo donde no rompa al cliente; lo demás, anótalo para Japón.
**No reproducir:** los calendarios que saltaban a 1970 (`mktime`, `Doc/legacy-crm2/PENDING.md` 1b) ni la inyección SQL de `change_syukkin2.php`. El staff (login, permisos) es del núcleo (S1): tú haces las pantallas de gestión (la sincronización de staff con el POS queda fuera: interfaz `PosGateway` sin implementar).

**Fuera de alcance por ahora (Antony, 2026-11-26):** POS (`category11`, terminales `CRMPOS-<id>`, servidores POS de tienda) y SNS (`category12`). Donde tu grupo llame al POS, deja una interfaz `PosGateway` sin implementación y anótalo.

## Lee solo

- `CLAUDE.md`, `Doc/contratos/CONVENCIONES.md` (sección 0 primero), `Doc/analisis-legacy/README.md` (hallazgos transversales)
- Del análisis, por secciones (grep del título): en `05-operaciones-terminales-reservas.md` solo la sección de category5 (業務支援管理); `07-pos-api-y-externos.md` (todo salvo `category11`, `category12`, `r/` y `reserve/`)
- Para dudas de comportamiento: el código en `C:\Users\User\VivaTech\crm2-php8\public_html` (solo lectura) y `Doc/legacy-crm2/MANUAL-TESTS.md` (tu módulo)

## Pedidos

Detalle en `Doc/diseno/operaciones-integraciones.md` §5 (rehecho sobre la BD legacy). Resumen:
- **S1:** modelos `Staff`/`Shop`/`Hatuban`/`AdminMaster`/`ContentsMaster`, trait de `disp_flg`, mutator de `form2Db`; rutas `operaciones` y `publico/integraciones`; registrar `OperacionesServiceProvider` e `IntegracionesServiceProvider`; estáticos de category5 y `appview` (+ `admin/css/font-awesome`); disco sobre `hp/uploads`; `firebase/php-jwt`, Intervention/Imagick, config de la tienda móvil y `CRM_API_KEY`, `DOMAIN_LIST`; niveles de permiso; `zip` y helper CSV.
- **S2:** modelos de `tb_customer(_sub)`, `tb_raiten_rireki`, `tb_koumoku`, `tb_search_*`, `sales_items`, `tb_photo_album`, `tb_yoyaku`, `numbered_ticket`; servicio de visitas (`raitenFunc`), búsqueda por teléfono, alta de socio, reserva rápida; rutas destino de `ctiWindow`.
- **S3:** servicio de puntos con los campos de `setPoint`; modelos de cupones, sellos y encuestas para `appview`.
- **S4:** modelos de `tb_url_list` y `tb_beacon_history`; imágenes y `mypage_config` de mypage en solo lectura.
- **Antony/S0:** ⏸ confirmar si 勤怠 (kintai/working/lector FeliCa) se usa sin touchcation (22 de 63 puntos).

## Decisiones

- **BD y UI del legacy (2026-11-27):** sin tablas nuevas; modelos sobre `tb_*` con `cd`, `hatuban` y `disp_flg='9'`; HTML y CSS de cada pantalla copiados. El diseño con tablas nuevas quedó en git (84f6590) y su código en el stash `b050e409` «obsoleto: tablas nuevas (S5 operaciones)».
- **Columnas por día** (`tb_work_time`, `tb_staff_shift`, `tb_shop_work`): lista blanca de días 1..31, nunca el nombre de columna armado con la entrada (evita la inyección de `change_syukkin2`).
- **Corte de jornada:** el mismo de cada pantalla del legacy («hora < 5» en working, `kijun_time` en kintai), en `WorkTimeService`.
- **`url_transfer_data` es de S5** (lo escribe `ptag`); sin PK en el legacy: modelo sin clave primaria, solo inserta.
- **`tb_felica_mode`:** modelo `FelicaMode` de S5; la fila `cd=2` la edita S2.
- **Estáticos de category5 y `appview`:** S5 los copia (`public/legacy/system/category5/`, `public/appview/`), pendiente de confirmar por S0/S1.
- **`/api/<desconocido>`:** 404 JSON en lugar del fatal del legacy.
- **`setRaitenDetail` JSONP de error:** se emite el formato correcto (el legacy está roto por precedencia de operadores).
- **`change_syukkin2.php`, `schedule.php`, `shift.php`, archivos temporales CSV:** descartados (duplicados, sin enlace o reemplazados). `ajax_session_update` vuelve a `pendiente ⏸` (con la UI del legacy la tienda va en la sesión).
- **`version/<canal>/v.php` y APK:** descartados (touchcation, S0); `crm_version.php` se mantiene.
- **Rutas de integración** en `routes/publico/integraciones.php`, nombre `publico.integraciones.*` (convención S0).
- **`tool/api`:** su JSON se arma como texto en el legacy; se reproduce con plantillas, no con `LegacyJson`.

## Hecho

- 2026-11-26 — Diseño con tablas nuevas, aprobado por S0 (obsoleto desde el cambio de base).
- 2026-11-27 — `LegacyJson` (json_encode sin flags y filas como texto) con 8 pruebas unitarias (557be46, 6425449).
- 2026-11-27 — Diseño rehecho sobre la BD y la UI del legacy (`Doc/diseno/operaciones-integraciones.md`): paridad completa, modelos por `tb_*` con dueño, UI y estáticos por pantalla, servicios, orden (63 puntos, 22 ⏸), pedidos y contratos con los mismos `cd` y columnas. **Esperando revisión de S0.**
