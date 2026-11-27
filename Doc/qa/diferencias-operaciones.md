# Diferencias: 業務支援管理 (category5)

Revisión visual S1, 2026-11-27 (método en [README.md](README.md)). Pantallas (muestra ~ local): portada, `staff/staff_list.php` ~ `/admin/operaciones/staff`, `staff_entry.php` ~ `staff/create`, `staff_group.php` ~ `staff-groups`, `form_config.php` ~ `staff-fields`, `information.php` ~ `information`, `working/working_calendar.php` ~ `shifts/month`.

| Pantalla | Qué difiere | Detalle |
|---|---|---|
| Menú lateral (todas) | Falta el bloque **勤怠管理** (勤怠一覧, 勤怠データ一覧, 端末パスワード変更, 各種設定) | Conocido: `config/menu/operaciones.php` lo deja fuera hasta que Antony confirme 勤怠. Los turnos van en スケジュール管理 (decisión del módulo). |
| Menú lateral (todas) | Los enlaces apuntan a `/admin/operaciones/…` en vez de las URL del legacy (`/system/category5/…`) | Estructural (rutas del módulo); las etiquetas coinciden. |
| `staff_list.php` | Sin diferencias | Captura: [capturas/operaciones-staff_list.png](capturas/operaciones-staff_list.png). |

Cabecera, pie, título y `<body>`: sin diferencias.

## Segunda pasada (S5, 2026-11-27)

Comparación con Playwright de `.main1` (títulos, cabeceras de tabla, controles, hidden, enlaces, imágenes) y del menú lateral, muestra (master, solo GET salvo el login) ~ local (`l13/operaciones`, BD `crmsystem2_operaciones` con el backup).

| Pantalla | Resultado |
|---|---|
| `staff_list.php` ~ `staff` | Igual salvo hidden: la muestra pasa `post_wherestr`/`stock_max_no` (el WHERE como SQL); local `act`/`kensu` (condiciones validadas en sesión, decisión del módulo). |
| `staff_entry.php` (alta y `?cd=1`) ~ `staff/create`, `staff/1/edit` | Igual (solo `_token`). |
| `staff_detail.php?cd=1` ~ `staff/1` | Mismo HTML visible; las pestañas son enlaces GET en lugar de formularios ocultos por POST (decisión: detalle por GET). |
| `staff_group.php` ~ `staff-groups` | Igual; los ▲▼ no salen en local porque la BD local tiene 1 grupo (salen con 2 o más). |
| `form_config.php`, `information.php` | Iguales. |
| `config/shop_work_calendar.php` ~ `shop-work` | Igual; la muestra abre en **enero de 1970** (bug del legacy, PENDING 1b, no se reproduce) y lleva el hidden `shop_work_flg=up` (marcador de acción; en Laravel es la ruta PUT). |
| Menú lateral | Falta 勤怠管理 (⏸ Antony). スケジュール管理 e 初期設定管理 no salen en ninguno de los dos lados (su `tb_base_system_child` está inactivo en ambas BD). |

| `schedule/staff_shift.php` ~ `shifts/day` | **Corregido**: los 3 calendarios eran el mes anterior, el actual y el siguiente; el legacy pinta el actual y los dos siguientes. **Corregido**: la fecha grande (`#dateDisp`) vuelve a ser un `<a>` (su color sale de `#dateDisp a:link`); enlaza al propio día en vez de `time_table.php?shop=1&d=20150825`, que no existe. Lo demás igual (99 `th`, 10 select). La muestra solo carga esperando su redirección interna. |
| `schedule/staff_shift_month.php` ~ `shifts/month` | Igual salvo la fila «2026/11/31» con su modal: el legacy pinta siempre 31 filas; Laravel solo los días del mes (decisión del módulo). El select 役職 (`kubun`) sale solo con filas en `tb_staff_shift_kind`, en los dos (en la BD local se agregaron 2 para comparar). |
| `kintai/staff_shift_kind.php` ~ `shift-kinds` | Abierto solo da error HTTP en la muestra: es una sección incluida en `kintai/config.php` (各種設定). Esa sección y la pantalla local tienen el mismo formulario y los mismos borradores por fila; solo cambia que aquí es pantalla propia (decisión del módulo, 各種設定 ⏸). |
| `appview/*` | Sin pasada visual aparte: sus 60 contratos byte a byte (`AppviewContractTest`) ya comparan el HTML con el del legacy. |

Correcciones: `StaffShiftController::day` (meses) y `shifts/day.blade.php` (enlace de la fecha), con prueba en `StaffShiftTest`.
