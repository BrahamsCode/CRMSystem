# S2 — Clientes, ventas y reservas

**Modelo:** Sonnet 5.5, esfuerzo medio · **Carpeta:** `CRMSystem-clientes/` · **Rama:** `l13/clientes`
**Módulos:** 顧客管理 (`clientes`), 販売管理 (`ventas`), 予約管理 (`reservas`). **Es dueña de:** sus carpetas según `Doc/contratos/CONVENCIONES.md` (secciones 2 y 3).

## Modo autónomo

Trabajas sin pedir confirmación. Al terminar cada tarea: actualizar este archivo y seguir con la siguiente. Si falta un detalle menor, decide lo más simple que respete las convenciones, anótalo en "Decisiones" y sigue. Al terminar todo o bloquearte: `SendMessage` a "S0 Coordinador CRM" (una línea: qué hiciste, qué sigue, qué necesitas). Cada entregable terminado con pruebas en verde: `git add` de tus rutas, commit (mensaje en español) y `git push origin <tu rama>`; después avisa a S0, que integra en `laravel13`. No haces merge ni cambias de rama; nunca `--force`.

## Próximo paso — Esperar revisión de S0 del diseño v2 (BD y UI del legacy); después D1 (modelos sobre `tb_*`, `Reservation` para S3 y servicios publicados)

## Tarea anterior — Diseño (sin código, HECHO)

Escribir `Doc/diseno/clientes-ventas-reservas.md`:
1. **Tabla de paridad:** una fila por cada vista del análisis (`Doc/analisis-legacy/01-clientes-y-ventas.md`; en `05-operaciones-terminales-reservas.md` solo la sección de category9 (予約管理); en `07-pos-api-y-externos.md` solo `r/` y `reserve/`): archivo legacy, título (ja), ruta Laravel (`admin.<modulo>.*`), controlador@método, tablas nuevas, estado (`pendiente` / `descartado: motivo`).
2. **Modelos sobre la BD del legacy** (`CONVENCIONES.md`, sección 0): qué `tb_*` usa cada vista, un modelo Eloquent por tabla (`$table`, `$primaryKey = 'cd'`, columnas tal cual, `disp_flg = 9`, `hatuban`) y sus relaciones. Sin tablas nuevas salvo que sea imprescindible (y compatible con el legacy).
2b. **UI:** cada pantalla reproduce el HTML y los CSS del legacy (mismas clases, mismos textos); anota qué JS del legacy necesita.
3. **Servicios viejos → Laravel 13:** cada uno con su reemplazo (Mail + cola, Scheduler, StreamedResponse, Storage, Policies…).
4. **Orden de implementación** por submódulo, con dependencias de otros grupos y estimación en puntos (1 punto ≈ media jornada).
5. **Pedidos** a S1 (núcleo) y a otros grupos.
Criterio de terminado: todas las vistas del análisis tienen fila; avisar a S0 y esperar su revisión. Mientras esperas, puedes preparar factories y seeders de tu grupo (en tus carpetas).

## Después (cuando S0 apruebe el diseño y el núcleo esté integrado)

Implementar submódulo por submódulo en el orden del diseño: migraciones, modelos, Form Requests, controladores, vistas Blade (textos en `lang/ja/<modulo>.php`), rutas en `routes/modulos/<modulo>.php`, menú en `config/menu/<modulo>.php`, pruebas Pest. Cada submódulo terminado: pruebas en verde (`bash scripts/en-docker.sh php artisan test`), filas en `hecho`, commit y push de tu rama, y aviso a S0.

**Lo anterior de CRMSystem (clientes sobre tablas nuevas, UI Tailwind) se borró:** se rehace sobre `tb_customer` y compañía con la UI del legacy. El código viejo está en git (`git show 8d84c98:public_html/app/...`) solo como referencia de lógica.
**Reservas:** `r/` duplica a `reserve/` (divergentes): un solo módulo; documenta las diferencias. `category11/yoyaku` (copia dentro del POS) queda fuera. El recordatorio de S3 depende del modelo de reservas: define y publica pronto el modelo `Reservation` en tu diseño.

**Fuera de alcance por ahora (Antony, 2026-11-26):** POS (`category11`, terminales `CRMPOS-<id>`, servidores POS de tienda) y SNS (`category12`). Donde tu grupo llame al POS, deja una interfaz `PosGateway` sin implementación y anótalo.

## Lee solo

- `CLAUDE.md`, `Doc/contratos/CONVENCIONES.md` (sección 0 primero), `Doc/analisis-legacy/README.md` (hallazgos transversales)
- Del análisis, por secciones (grep del título): `Doc/analisis-legacy/01-clientes-y-ventas.md`; en `05-operaciones-terminales-reservas.md` solo la sección de category9 (予約管理); en `07-pos-api-y-externos.md` solo `r/` y `reserve/`
- Para dudas de comportamiento: el código en `C:\Users\User\VivaTech\crm2-php8\public_html` (solo lectura) y `Doc/legacy-crm2/MANUAL-TESTS.md` (tu módulo)

## Pedidos

Ver `Doc/diseno/clientes-ventas-reservas.md` §5 (S1: `staff`, Gates, rutas públicas `r/`, `shops.card_reader_mode`, `taxes`, paquetes, `PosGateway`; S3: `LoyaltyService` por `visit_id`, `tb_reserve`; S4: `customer_cards`; S5: `shop_work_days`, `MobileShopClient`).

## Decisiones

- 2026-11-26: `<modulo>` = `clientes`, `ventas`, `reservas`; lo existente (`admin.customers.*`, `views/admin/customers`, `lang/ja/customers.php`) se muda a esos nombres en la tarea C0.
- 2026-11-26: `r/` es la versión CRM y `reserve/` la versión POS (lee `tb_c2_*` del servidor POS): se migra el contrato de `r/` y `reserve/*` es alias; la rama POS queda tras `PosGateway` sin implementar.
- 2026-11-26: `営業日設定` (`shop_work_days`) es de S5; `point_setting` es de S3; envío desde 集計 es de S3.
- 2026-11-26: descartados sin enlace en el menú: `customer_level`, `school*`, `mail_comment`, `customer_new_complete`; se reabren si Japón confirma uso.
- 2026-11-26: ventas y gastos salen de `tb_customer_sub` (-1/-2) a `sales`/`sale_lines`/`expenses`.
- 2026-11-26: `category3/reservation` (`tb_reserve`) pasa a S2 (S0); comparte `reservations` con category9 (`channel = 300`). `model_reservation.php` descartado (`tb_change_model` sin escritor en el repo).
- 2026-11-26: el estado de atención (受付中/電話受付/締切間近/受付終了) es de la franja (`reservation_slots.accept_state`), no de la reserva; la reserva tiene `state` 1 受付 / 2 確定 / 9 キャンセル. Tiempos de franja en segundos desde 00:00 como el legacy (pasan de 24 h).
- 2026-11-26: `felica_no` en `customers` (pedido S4); `felica_cards` y カード管理 son de S4. Sin `paper_card_id`: el legacy solo relaciona cliente y tarjeta de papel en `url_transfer_data` (S4).
- 2026-11-26: `id = cd` en `customers`, `visits`, `products`; resto por `legacy_id_map`. Mapeo inverso para API de S5: `name` = apellido + espacio de medio ancho + nombre; `kojin_birth` = `Y/m/d` (vacío `'//'`).

- 2026-11-26 (v2, BD legacy): sin tablas nuevas; modelos sobre `tb_*`. La v1 queda en git (`917be77`) y en el stash `obsoleto: tablas nuevas (S2 R1 reservas)` (SHA `9db26956`).
- 2026-11-26: `login_pass` se mantiene en claro como el legacy (misma BD que producción; `appview`, `ptag`, `mpa_*` comparan en claro o con `md5`). El hash queda para cuando se migren esos contratos.
- 2026-11-26: `category3/reservation` sigue sobre `tb_reserve` (sistema independiente de `tb_yoyaku`, como el legacy); `Reservation` = `tb_yoyaku`, `SimpleReservation` = `tb_reserve`.
- 2026-11-26: pantallas sin enlace en el menú (`customer_level`, `school*`, `mail_comment`, `biyou`, `model_reservation`) se migran con prioridad baja (S0: tienen pantalla porque el legacy la tenía).
- 2026-11-26: si falta `tb_felica_mode` `cd = 2`, se crea al guardar en vez de no guardar en silencio (defecto del legacy).

## Hecho

- 2026-11-26: diseño v2 sobre la BD y la UI del legacy (paridad con tablas `tb_*`, modelos por tabla y dueño, UI/JS por pantalla, contrato `Reservation` sobre `tb_yoyaku`, orden 84 pts).

- 2026-11-26: diseño `Doc/diseno/clientes-ventas-reservas.md` (paridad de todas las vistas de 01, category9 de 05, `r/`/`reserve/` de 07 y category3/reservation; esquema con mapeo `tb_*` en el Anexo A; servicios; orden 76 puntos; pedidos). Pendiente de revisión de S0.
