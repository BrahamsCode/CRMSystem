# 05 — Operaciones, terminales y reservas (業務支援管理 / 端末管理 / 予約管理)
> Fuente: C:\Users\User\VivaTech\crm2-php8 (rama master = código ya migrado a PHP 8.5.12). Generado el 2026-11-26. Destino: Laravel 13 + PHP 8.5.

Alcance: `system/category5/` (70 PHP), `system/category7/` (78 PHP) y `system/category9/` (66 PHP). Excluidos: `hp/` (obsoleta), `admin/` y `category8`. Dependencias con `hp/`: `kiosk` y `digital_signage` escriben sus XML e imágenes en `hp/uploads/c5|c7/...` y el mypage de `hp/user/yoyaku` consume las reservas; en Laravel eso pasa a `Storage` y a la API del mypage (grupo 06).

Nota de numeración: el número de carpeta (`category5`, `category7`, `category9`) es dinámico; los menús lo leen de `tb_base_system_child.tb_base_system_cd`. En Laravel los módulos se identifican por nombre de ruta, no por número.

## Resumen

| Módulo/submódulo | Carpeta | Nº PHP | Nº vistas reales (pantallas) | Complejidad (S/M/L/XL) | Puntos (1 punto ≈ media jornada de un agente) |
|---|---|---|---|---|---|
| スタッフ管理 (ficha, grupos, búsqueda, インフォメーション) | `category5/staff/` | 25 | 21 | L | 14 |
| 勤怠管理 (terminal de asistencia: 勤怠データ, 各種設定, パスワード, シフト区分) | `category5/kintai/` | 20 | 19 | XL | 16 |
| 勤怠一覧 / 集計 / 帳票 | `category5/working/` | 13 | 13 | L | 10 |
| スケジュール管理 (turnos 日別/月別) | `category5/schedule/` | 6 | 5 | M | 6 |
| 初期設定管理 (modo lector FeliCa, tarjetas, 営業日) | `category5/config/` | 4 | 4 | M | 4 |
| (menú y `inc` de category5) | `category5/` | 2 | 0 | S | 0 |
| ビーコン管理 | `category7/beacon/` | 7 | 6 | M | 4 |
| ペーパーサイネージ (カード管理, QR) | `category7/card_signage/` | 8 | 6 | M | 5 |
| 電子看板管理 | `category7/digital_signage/` | 25 | 23 | L | 10 |
| 情報端末管理 (kiosk) | `category7/kiosk/` | 32 | 28 | XL | 14 |
| ICカード管理 (FeliCa) | `category7/felica/` | 4 | 4 | M | 5 |
| (menú y `inc` de category7) | `category7/` | 2 | 0 | S | 0 |
| (menú de category9) | `category9/` | 1 | 0 | S | 0 |
| 予約管理: 予約表 (tabla de horarios, arrastrar, AJAX) | `category9/yoyaku/` | 11 | 9 | XL | 14 |
| 予約管理: flujo de reserva (yoyaku, calendario, cliente, registro, correo) | `category9/yoyaku/` | 18 | 18 | L | 10 |
| 予約管理: 受付日設定, 項目, カテゴリ, 設備, スタッフ割当, 設定 | `category9/yoyaku/` | 22 | 20 | L | 14 |
| 予約管理: listados, impresión y CSV, 関連顧客 | `category9/yoyaku/` y `print/` | 14 | 14 | M | 7 |
| **Total** | | **214** (70+78+66) | **190** | | **133** |

Los totales de PHP suman 214 porque el menú `index.php` de cada categoría y los `inc` se cuentan aparte. "Vistas reales" excluye fragmentos (`inc/`, `*_frm.php` incluidos, clases `cUrl`/`cPageControl`, `beacon_db.php`, `time_table_function.php`, `sample_array.php`, `staff_shift.function.php`). Los 3 `*_frm.php` de cada familia de señalización se cuentan como pantalla de formulario (alta/edición) porque son la vista que ve el usuario; los registros y borrados son endpoints POST.

Convención de códigos: la BD usa `cd` como clave y `hatuban` (tabla `hatuban`: `koumoku` = nombre de tabla, `kazu` = contador) para numerar; el borrado es lógico con `disp_flg = '9'`, y `hyouji_flg` controla si se muestra. Casi todos los POST pasan por `array_walk_recursive($_REQUEST, 'form2Db')` (escape SQL global).

---

## スタッフ管理 (`category5/staff/`)

- Alta, búsqueda, edición y baja lógica de la ficha de personal (`tb_staff`) con foto, cargo (`tb_officialposition` / `tb_belongpost`), grupo y tienda. Incluye el 検索項目設定 (qué campos son visibles/buscables, en `tb_koumoku`), los grupos de personal y los インフォメーション (avisos para el personal por tienda y cargo).
- Menú: スタッフ新規登録, スタッフ検索・一覧, スタッフグループ設定, スタッフ検索項目設定, インフォメーション管理.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `staff_entry.php` | スタッフ新規登録 | alta | Formulario: nombre y kana, 住所 con búsqueda por código postal (`zip.php`), contacto, tienda, grupo, cargo, `login_id`/`login_pass`, foto; botón 確認 | L: `tb_staff`, `tb_shop`, `tb_staff_group`, `tb_base_system_grandchild` | Lee `hatuban` de `tb_staff` para mostrar el siguiente número |
| `staff_entry_confirm.php` | (sin título propio) | confirmación | Valida (`error_check`), muestra el resumen; botones 戻る / 登録 | L: las mismas | 1082 líneas: validaciones en servidor |
| `staff_entry_register.php` | スタッフ登録情報 | registro(POST) | Inserta/actualiza `tb_staff`; asigna `tb_belongpost`; sube foto; sincroniza con POS (`db_sync_all2`) | E: `tb_staff`, `tb_belongpost`, `hatuban` | Guarda `login_pass` sin hash. Sincroniza el personal con el servidor POS de **cada tienda** (`tb_shop.pos_domain`, BD `crm2019`, usuario/clave en `POS_DB_PASSWORD`). El bloque antiguo está comentado |
| `staff_entry_complete.php` | スタッフ登録情報 | detalle | Pantalla final tras el alta | L: `tb_staff`, `tb_shop`, `tb_staff_group` | |
| `staff_list.php` | スタッフ一覧 | lista | Búsqueda (名前 etc., `search_set1/2`), paginación `kensu`/`page`, CSV (botón Flash), enlace a ficha, 修正 y 削除 | L: `tb_staff` | Estado de búsqueda en sesión; `.swf` para el CSV |
| `staff_detail.php` | スタッフ登録情報 | detalle | Ficha con 4 pestañas; 修正する (reutiliza `staff_entry`), 削除する, カード解除 | L: `tb_staff`, `tb_shop`, `tb_base_system_grandchild` | |
| `staff_delete_register.php` | スタッフ登録情報 | borrado | Borrado lógico (`disp_flg='9'`), libera su tarjeta (`tb_card`) y borra su fila en `tb_work_time`; re-sincroniza POS | L: `tb_card`, `tb_shop`, `tb_staff`; E: `tb_card`, `tb_staff`, `tb_work_time` | |
| `staff_recover_confirm.php` | スタッフ登録情報 | confirmación | Reactivar a un empleado dado de baja | L: `tb_shop`, `tb_staff` | |
| `staff_card_delete.php` | スタッフ登録情報 | borrado | Desvincula la tarjeta FeliCa del empleado | L/E: `tb_card`, `tb_staff`, `tb_work_time` | |
| `staff_group.php` + `_frm` + `_register` | スタッフグループ設定 | config | Alta/edición/borrado y orden ▲▼ de grupos, en una sola pantalla con formulario embebido | L/E: `tb_staff_group`, `hatuban` | Patrón "frm + register" repetido en kintai |
| `form_config.php`, `form_config_register.php` | スタッフ検索項目設定 | config | Casillas de 検索 por campo | L/E: `tb_koumoku` | Bug legacy: la casilla del primer campo se pinta con `koumoku_select` (siempre marcada) |
| `information.php`, `information_register.php`, `information_delete_register.php` | インフォメーション管理 | lista/alta/borrado | Aviso por tienda (全店舗 = tienda 0) y cargo | L: `tb_information`, `tb_officialposition`, `tb_shop`; E: `tb_information`, `hatuban` | JS bloquea mensaje vacío |
| `csv_download.php`, `csv_download/csv_file_out.php`, `csv_file_del.php` | (CSV スタッフ) | csv | Genera CSV en archivo temporal y lo entrega; botón Flash | L: `tb_koumoku`, `tb_shop`, `tb_staff` | Reemplazo: `StreamedResponse` |
| `zip.php`, `inc/*` (`disp_pic`, `search_set1`, `search_set2`, `upload`) | — | ajax / fragmento(include) | Código postal → dirección; miniatura; formulario de búsqueda; subida de foto | L: `tb_koumoku`, `tb_shop` | |

**Flujo principal:** staff_list → staff_entry → staff_entry_confirm → staff_entry_register → staff_entry_complete; edición desde staff_detail reaprovecha el mismo trío.

**Reglas de negocio no obvias**
- Número de empleado vía `hatuban` con `FOR UPDATE`; en Laravel usar la clave autoincremental de la tabla y conservar el `cd` legacy para el mapeo de importación.
- El alta, edición y baja de personal se replica a la BD POS de cada tienda (`pos_domain`). Hay que decidir si sigue (ver Riesgos).
- La baja libera la tarjeta y borra `tb_work_time` del empleado (se pierde el historial de asistencia del mes: confirmar si es intencional).
- `login_pass` en texto plano; el login del personal compara con `md5(login_pass)` en otros puntos (ver grupo de login). No reproducir: usar `Hash::make` y migrar en el primer inicio de sesión.
- Una comilla se duplica en cada vuelta de validación (`O''''Neil`): defecto de doble escape; no reproducir.

**Servicios viejos y reemplazo:** CSV con Flash → `StreamedResponse` con BOM/Shift_JIS opcional; fotos en `hp/uploads/c5/` → `Storage::disk('public')`; sesión de búsqueda → query string + `FormRequest`; sync POS → Job (`SyncStaffToPos`) en cola.

---

## 勤怠管理 (`category5/kintai/`)

- Terminal de asistencia por tienda: el personal ficha entrada/salida/salida temporal/regreso (por FeliCa o por pantalla del terminal). La pantalla administra los registros del día (alta, edición, borrado), la configuración del terminal (horarios, fondo de pantalla, contraseña) y los 区分 de turno.
- Menú: 勤怠データ一覧, 端末パスワード変更, 各種設定.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `index.php` | 日付別勤怠データ一覧 | lista | Lista del día por persona, navegación de fecha, 勤怠データを追加する, 編集, 削除, CSV diario | L: `kintai_tb_config`, `kintai_tb_kintai`, `tb_staff`; E: `kintai_tb_config` (crea fila de la tienda) | `mktime` con `is_dst` (ver Riesgos) |
| `kintai_new.php` | 勤怠データ新規追加 | alta | Formulario de 出勤/外出/戻り/退勤, estado, 備考 | L: `kintai_tb_config`, `kintai_tb_kintai`, `tb_staff` | 801 líneas, mucho JS |
| `kintai_new_up.php` | 勤怠データ新規追加 | registro(POST) | Transacción: inserta `kintai_tb_hiduke` y una fila `kintai_tb_kintai` por cada marca (出勤, 外出, 戻り, 退勤) | E: `kintai_tb_hiduke`, `kintai_tb_kintai`, `hatuban`; L: `tb_staff` | Un `INSERT` + `UPDATE` concatenados en una sola consulta |
| `kintai_update.php` | 勤怠データ編集 | edición | Edita las marcas del día | L: `kintai_tb_config`, `kintai_tb_hiduke`, `kintai_tb_kintai`, `tb_staff` | 1388 líneas: el archivo más grande del grupo |
| `kintai_update_up.php` | 勤怠データ編集 | registro(POST) | Actualiza o crea marcas de 退勤/外出/戻り | E: `kintai_tb_hiduke`, `kintai_tb_kintai`, `hatuban` | |
| `kintai_update_del.php`, `kintai_delete.php` | (sin título) | borrado | Borra una marca / un día completo (`jConfirm`) | E: `kintai_tb_kintai`, `kintai_tb_hiduke` | |
| `password_update.php`, `password_update_complete.php` | 端末パスワード変更 | edición/registro | Pide contraseña actual y nueva del terminal; PRG a la pantalla completa | L/E: `kintai_tb_config.password` | Contraseña del terminal en texto plano; no se imprime |
| `config.php` | 各種設定データ | config | Horarios (基準, 始業, 終業), imagen de fondo del terminal (miniatura 150 px), CSV mensual por persona, y administración de シフト区分 | L: `tb_staff`, `kintai_tb_config`, `tb_staff_shift_kind` | 954 líneas; en Docker faltaba `hp/uploads/c5/kintai/` |
| `staff_shift_kind.php`, `staff_shift_kind_frm.php`, `staff_shift_kind_register.php` | シフト区分管理 | config | Alta, edición, ▲▼ y borrado de 区分 de turno | L/E: `tb_staff_shift_kind`, `hatuban` | Mismo patrón que `staff_group*` |
| `csv_out.php`, `monthly_csv_out.php` | (CSV) | csv | CSV del día y mensual por persona (Shift_JIS) | L: `kintai_tb_config`, `kintai_tb_kintai`, `tb_staff` | Duplicados en `csv_download/` (misma lógica) |
| `csv_download/csv_out.php`, `monthly_csv_out.php`, `csv_file_out.php`, `csv_file_del.php` | (CSV) | csv | Variante con archivo temporal y botón Flash | L: las mismas | Unificar en un solo `StreamedResponse` |
| `ajax_session_update.php` | — | ajax | Actualiza la sesión (tienda seleccionada) | — | |

**Flujo principal:** index → kintai_new → kintai_new_up → index; edición: index → kintai_update → kintai_update_up; borrado con confirmación JS.

**Reglas de negocio no obvias**
- Cada marca (出勤/外出/戻り/退勤) es una fila de `kintai_tb_kintai` ligada a un `kintai_tb_hiduke` del día; `kintai_flg` guarda el estado.
- `kintai_tb_config.password` es la contraseña con la que el terminal físico entra al modo de configuración.
- Cálculos de horas y minutos (extras, descansos, 基準) se hacen en PHP y JS dentro de `index.php`/`kintai_update.php`; hay que extraerlos a una clase de servicio con pruebas.
- **No reproducir:** el calendario con `mktime(..., is_dst)` de 7 argumentos. En PHP 7 devolvía `false` y `date('Y', false)` daba 1970; 先月/翌月 saltaban a enero de 1970. Usar `CarbonImmutable` (`startOfMonth()`, `addMonthNoOverflow()`), y revisar los datos guardados por producción con fechas calculadas así (`PENDING.md` 1b.1).

**Servicios viejos y reemplazo:** CSV Flash y archivos temporales → `StreamedResponse`; imagen de fondo → `Storage` + `Intervention Image`; `cHatuban` → autoincrement / secuencias; terminal de asistencia físico llama a otros endpoints (`tool/api`, fuera de este grupo) que deben seguir funcionando contra las mismas tablas.

---

## 勤怠一覧 / 集計 (`category5/working/`)

- Vista mensual de asistencia por empleado (`tb_work_time`: una fila por empleado y mes, con columnas `syukkin_time_N`, `taikin_time_N`, `syukkin_flg_N`, `syukkin_kubun_N` para N = 1..31), calendario de 営業日, totales y 帳票. Permite corregir una marca.
- Menú: 勤怠一覧 (lista y calendario), 勤怠集計一覧, 帳票管理 (solo en el servidor `202.218.51.245`).

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `working_calendar.php` | 勤怠一覧 | lista | Calendario del mes con 営業日/休日, 先月/翌月, enlaza a la lista del día, 更新する | L: `tb_shop`, `tb_shop_work`; E: `tb_shop_work` | Pantalla de `mktime` con `is_dst` |
| `work_list.php` | 勤怠一覧 | lista | Lista del día por empleado: 出勤/退勤, 区分, 表示件数, 更新する, CSV | L: `tb_shop`, `tb_staff`, `tb_work_time` | |
| `work_list2.php`, `ng_work_list.php` | 勤怠一覧 | lista | Variantes (con filtros distintos / solo registros NG: sin salida) | L: las mismas | Duplicación fuerte con `work_list.php` |
| `work_list_update.php` | 勤怠情報 | registro(POST) | Actualiza `syukkin_time_N`/`taikin_time_N` del día | L/E: `tb_work_time` | Concatena la columna con el día |
| `change_syukkin.php` | — | ajax | Cambia 出勤区分/flag de un día (AJAX desde la lista) | L/E: `tb_work_time` | Usa `(int)` en `staff_cd` |
| `change_syukkin2.php` | — | ajax (ruta suelta, sin enlaces) | Igual que el anterior con hora de entrada y salida | L/E: `tb_work_time` | **Inyección SQL:** el día de `nengappi` va sin validar al **nombre de la columna** del `UPDATE` (`syukkin_time_` + día). No reproducir; validar el día contra `1..31` y usar una lista blanca de columnas |
| `working_list.php` | スタッフ一覧 | lista | Lista mensual por persona, búsqueda (`staff_search_set1/2`) | L: `tb_shop`, `tb_staff`, `tb_work_time` | |
| `working_total_list.php` | スタッフ勤怠集計一覧 | lista | Totales de horas/días por empleado en un periodo | L: `tb_shop`, `tb_shop_work`, `tb_staff`, `tb_work_time` | |
| `working_chouhyou.php` | 日別シフト管理 (帳票管理) | lista | Planilla/帳票 diaria de asistencia para confirmar | L: `tb_shop`, `tb_staff`, `tb_work_time` | Solo en `202.218.51.245`; 60 avisos de variables en la prueba |
| `csv_download.php`, `csv_download/csv_file_out.php`, `csv_file_del.php` | (CSV 勤怠) | csv | CSV mensual por tienda (Shift_JIS) | L: `tb_shop`, `tb_staff`, `tb_work_time` | Flash + archivo temporal |

**Flujo principal:** working_calendar → work_list (día) → corrección (work_list_update / change_syukkin) → CSV.

**Reglas de negocio no obvias**
- Una jornada que empieza antes de las 5:00 pertenece al día anterior: si la hora de 出勤 o 退勤 es menor que 5 se suma `86400` (24 h) al timestamp (`change_syukkin2.php`). Mantener como regla de dominio documentada (`day_cutoff_hour = 5`) y configurable por tienda.
- `tb_work_time` guarda timestamps Unix como texto (`'1700000000'`) y modela el mes con 31 pares de columnas: en Laravel normalizar a una tabla `attendance_records(staff_id, work_date, clock_in, clock_out, kind, flag)`, con un importador desde el legacy.
- `syukkinbi` = timestamp del día 1 del mes a las 0:00 (clave del mes), calculado con `mktime`: de nuevo el problema de 1970, ahora corregido en el código migrado.
- 営業日 vs 休日 (`tb_shop_work`): la cuenta de días laborables se calcula en la pantalla.

**Servicios viejos y reemplazo:** CSV Flash → descarga HTTP; PDFs/帳票 solo en HTML (sin mPDF); control de IP/servidor por `SERVER_ADDR` → feature flag/Gate (`viewWorkReport`).

---

## スケジュール管理 (`category5/schedule/`)

- Turnos planificados por empleado: vista diaria (10:00-18:00 con modal) y mensual. `shift.php` (arrastrar) y `schedule.php` están sin enlace (comentado en el menú por "ドラッグ方式のため"). Menú solo en el servidor `202.218.51.245`.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `staff_shift.php` | 日別シフト管理 | lista/edición | Tabla diaria; modal para crear/editar un turno por empleado; 更新 | L: `kintai_tb_config`, `tb_shop`, `tb_staff`, `tb_staff_shift`, `tb_staff_shift_kind`; E: `tb_staff_shift` | Al guardar crea una fila mensual para **cada** persona de la tienda; el comentario se corta en la comilla; enlace a `schedule/time_table.php` que no existe (404) |
| `staff_shift_month.php` | 月別シフト管理 (スケジュール設定) | lista | Vista del mes con los turnos | L: las mismas | |
| `ajax_staff_shift_hatuban.php` | — | ajax | Devuelve el siguiente `cd` de `tb_staff_shift` (`hatuban`) | E: `hatuban` | Reemplazar por autoincrement |
| `schedule.php` | 営業日設定 | edición (sin enlace) | Variante de la pantalla de 営業日 | L/E: `tb_shop_work` | Duplicado de `config/shop_work_calendar.php` |
| `shift.php` | 営業日設定 | edición (sin enlace) | Calendario "arrastrar"; sin lógica propia | — | No migrar |
| `staff_shift.function.php` | — | fragmento(include) | Funciones de fechas del turno | — | `mktime` con `is_dst` |

**Reglas:** el turno tiene `tb_staff_shift_kind` (区分); la hora de inicio y fin va dentro de una fila mensual (columnas por día), igual que `tb_work_time`.
**Reemplazo:** tabla normalizada `shifts(staff_id, date, kind_id, start, end, comment)`; la vista diaria con Livewire/Inertia; drag-and-drop opcional.

---

## 初期設定管理 (`category5/config/`)

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `felica_mode.php` | カードリーダーモード設定 | config | Cambia el modo del lector FeliCa: 運用 (0), 運用出勤強制 (1), 登録 (2), etc., guarda la fecha de cambio | L/E: `tb_felica_mode` (fila `cd = 1`) | El lector lee ese modo para decidir qué hacer con una tarjeta |
| `card_new.php` | カード管理 (新規カード管理) | lista/edición | En modo 登録 muestra las tarjetas sin asignar (`card_flg=0`) y asigna una a un empleado sin tarjeta; vuelve al modo operación | L/E: `tb_card`, `tb_felica_mode`, `tb_staff`, `tb_work_time` | Escribe `felica_no` en `tb_staff` y `tb_work_time`. Asignar necesita el lector real |
| `card_staff_select.php` | — | fragmento(include) | Selector de empleado para una tarjeta | L: `tb_staff` | |
| `shop_work_calendar.php` | 営業日設定 | config | Marca 休日/営業日 por día; navegación de mes | L/E: `tb_shop_work`, `hatuban` | `mktime` con `is_dst`; botón 更新 fuera del `<form>` en el HTML |

---

## ビーコン管理 (`category7/beacon/`)

- Lista y detalle de beacons y de los terminales receptores. Lee una **BD externa** (`BEACON_DB*`, host `b-develop.g-api.net`) mediante `beacon_db.php`, que usa las variables `BEACON_DBNAME`, `BEACON_DBUSER`, `BEACON_DBPASS` (clave en `BEACON_DB_PASSWORD`).

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `beacon_list.php` | ビーコン管理 | lista | Lista de beacons con paginación | L: BD externa de beacons (SQL por dentro de `$beaconConnect`) | El servidor externo rechaza la conexión (no responde en Docker) |
| `beacon_detail.php` | (detalle) | detalle | Ficha de un beacon, edita nombre/ubicación vía AJAX | L/E: BD externa | |
| `ajax_beaconSave.php` | — | ajax | Guarda cambios del beacon | E: BD externa | |
| `terminal_list.php`, `terminal_detail.php` | (端末) | lista/detalle | Terminales que reciben beacons | L: BD externa | |
| `ajax_terminalSave.php` | — | ajax | Guarda cambios del terminal | E: BD externa | |
| `beacon_db.php` | — | fragmento(include) | Conexión a la BD externa | — | Usa `.env`; sin acceso hoy |

**Reemplazo:** conexión adicional `beacon` en `config/database.php` (`DB_BEACON_*`), modelos con `$connection = 'beacon'`. Dudoso: la BD externa podría estar caída; decidir si se migra o se retira.

---

## ペーパーサイネージ (`category7/card_signage/`)

- Lista de tarjetas con `id` único, URL y título (`tb_url_list`). Permite crear (alta AJAX), editar, detener, borrar, ver el QR y los 集計 (visitas) con su CSV.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `list.php` | ペーパーサイネージ (カード管理) | lista | Lista paginada, descarga del QR (`category3/mail_mag/qr_img.php` por `file_get_contents` HTTP) | L: `tb_url_list` | El QR sale dentro del JPG el aviso si el servidor no se alcanza a sí mismo |
| `new.php`, `edit.php` | ペーパーサイネージ | alta/edición | Formularios (envían por AJAX a `control.php`) | — | |
| `control.php` | — | registro(POST)/ajax | Acciones `insert`, `update`, `delete`, `hyoujiOff` de `cUrl` | E: `tb_url_list`, `hatuban` | **Inyección SQL:** título y URL van sin escapar a `cUrl::update` (el `form2Db` recorre `$_REQUEST`, no `$_POST`); un título con comilla no se guarda. No reproducir: usar Eloquent / `FormRequest` |
| `cUrl.php` | — | fragmento(include) | Clase de acceso a `tb_url_list` (`id` = `CRM` + 9 dígitos) | L/E: `tb_url_list` | Solo incluida; 403 por URL directa |
| `aggregate.php`, `csv_output.php` | (集計) | detalle/csv | Visitas por tarjeta (cruza `tb_customer`) y su CSV | L: `tb_customer`, `tb_url_list` | `aggregate.php` tenía 588 avisos |
| `cPageControl.php` | — | fragmento(include) | Paginación | — | Corregido para `OFFSET` negativo |

**Regla:** `tb_url_list.id` único mientras `disp_flg = '1'` (`cUrl::isFree`). Prefijo `CRM` + `cd` en 9 dígitos si se deja vacío.
**Reemplazo:** Eloquent `Card` con `Rule::unique`; QR con `simplesoftwareio/simple-qrcode` en vez de la llamada HTTP a sí mismo.

---

## 電子看板管理 (`category7/digital_signage/`)

- Gestiona la señalización digital: **スクリーンセーバー** (contenido: imagen/movimiento con duración), **放映プログラム** (lista ordenada de contenidos con periodo) y **ディスプレイ** (pantalla física que reproduce un programa). Al guardar, escribe un XML por pantalla que el reproductor consulta.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `display_list.php`, `display_new.php`, `display_detail.php`, `display_frm.php`, `display_update.php`, `display_register.php`, `display_delete.php` | ディスプレイ管理 | lista, alta, detalle, config, edición, registro(POST), borrado | CRUD del display con su programa asignado | L: `tb_display`, `tb_c7_movie_program`, `tb_dispaly_systems`, `tb_screensaver`; E (por clase genérica): `tb_display`, `hatuban` | El nombre `tb_dispaly_systems` es así en el esquema (errata) |
| `movie_program_list.php`, `_new`, `_detail`, `_frm`, `_update`, `_register`, `_delete` | 放映プログラム管理 | lista, alta, detalle, config, edición, registro(POST), borrado | Alta con orden de contenidos; "この内容を登録し、選択中のディスプレイに反映する" regenera el XML | L: `tb_screensaver`, `tb_dispaly_systems`, `tb_display`, `tb_mycoupon_master`; E: `tb_c7_movie_program`, `hatuban` | `movie_program_register.php` falla con la opción 最後に追加 (columna inexistente `tb_c7_movie_program_cd`); el borrado no limpia los screensavers por la misma columna (`BROKEN-FILES.md`) |
| `screen_saver_list.php`, `_new`, `_detail`, `_frm`, `_update`, `_register`, `_delete`, `_delete_complete` | (放映素材 / スクリーンセーバー) | lista, alta, detalle, config, edición, registro(POST), borrado, confirmación | Sube imagen o contenido (con `pic_up.php`), tiempo de reproducción, cupón relacionado | L: `tb_display`, `tb_screensaver`; E: `tb_screensaver`, `hatuban` | `.swf` en detalle y formulario (Flash) |
| `pic_up.php` | — | ajax | Subida de imagen, genera miniaturas | — | Archivos a `hp/uploads/c7/...` |
| `xml_up.php` | — | fragmento(include) | Escribe el XML de un programa (`<p cd time title ...>` por contenido) en `hp/uploads/c7/display/<cd>.xml` | L: `tb_c7_movie_program`, `tb_screensaver` | Sesión de personal exigida |
| `xml_info_up.php` | — | fragmento(include) | Escribe `floormap.xml` con info de reclutamiento | L: `tb_recruit`, `tb_recruit_category`, `tb_shop` | Datos de `category4` (otro módulo) |

**Flujo principal:** screen_saver_list → alta → movie_program (ordenar contenidos) → display (asignar) → XML publicado.

**Reglas de negocio no obvias:** las listas de contenidos de un programa se guardan en una sola columna con separador `<>` (`tb_screensaver_cds`, `tb_mycoupon_master_cds`, `tb_dispaly_systems_cds`); las franjas horarias de reproducción usan `tb_screensaver_active_time` (columna muy usada); orden con 先頭に追加/最後に追加.

**Reemplazo:** tablas pivote (`program_screensaver` con `position`) en vez de listas `<>`; el XML pasa a un endpoint JSON/XML servido por Laravel (`GET /signage/displays/{id}.xml`) o a un archivo en `Storage` generado por un Job al guardar; subida con `Storage`; Flash → quitar.

---

## 情報端末管理 (`category7/kiosk/`)

- Misma idea que la señalización, para el quiosco táctil (`tb_nextouch_*`): **映像管理** (programa con diapositivas, banda inferior 帯画像, zonas táctiles), **映像振り分け管理** (qué terminal reproduce qué programa) y素材 (screensaver). Incluye un editor visual (`movie_program.php`) con arrastrar y guardar por AJAX.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `movie_program_list.php`, `_new`, `_detail`, `_frm`, `_update`, `_register`, `_delete` | 映像管理 | lista, alta, detalle, config, edición, registro(POST), borrado | CRUD del programa con imagen de banda | L: `tb_shop`, `tb_nextouch_screensaver`, `tb_nextouch_display`, `tb_dispaly_systems`, `tb_mycoupon_master`; E: `tb_nextouch_movie_program`, `hatuban` | `movie_program_register.php` tiene el mismo fallo de columna que digital_signage |
| `movie_program.php` | (editor de 映像) | edición | Editor visual: diapositiva del stock a zona izquierda, 登録 por AJAX | L: `tb_nextouch_movie_program`, `tb_nextouch_screensaver` | 784 líneas JS+PHP |
| `ajax_movie_program.php`, `movie_program_frm_rAJAX.php` | — | ajax | Guarda el editor y refresca el formulario | L/E: `tb_nextouch_movie_program`, `tb_nextouch_screensaver`, `hatuban` | Devolvía un aviso delante del JSON (F6), corregido |
| `movie_program_popPre.php` | (vista previa) | detalle | Popup de preview | L: `tb_nextouch_screensaver`, `tb_nextouch_movie_program`, `tb_dispaly_systems` | |
| `display_list.php`, `_new`, `_detail`, `_frm`, `_update`, `_register`, `_delete` | 映像振り分け管理 | lista, alta, detalle, config, edición, registro(POST), borrado | Asigna programa y zona táctil a un terminal; el detalle muestra la `AppKey` del terminal | L: `tb_nextouch_display`, `tb_nextouch_movie_program`, `tb_shop`; E: `tb_nextouch_display` | El botón 削除 solo existe con IP de desarrollo (`isDevelopmentAuthorityFromIP`) |
| `screen_saver_list`(en digital), `screen_saver_new`, `_detail`, `_frm`, `_update`, `_register`, `_delete`, `_delete_complete` | (素材) | alta, detalle, config, edición, registro(POST), borrado, confirmación | Alta/edición de la diapositiva | L: `tb_nextouch_screensaver`, `tb_display`, `tb_shop`; E: `tb_nextouch_screensaver` | Sin `list` propio en kiosk: la lista sale del detalle del programa; `.swf` en detalle |
| `pic_up.php`, `inc/upload.php`, `inc/disp_pic.php` | — | ajax / fragmento(include) | Subida de imágenes a `kiosk/uploads/temp` y luego a `hp/uploads/c7/kiosk/obi/` | — | |
| `xml_up.php`, `xml_info_up.php` | — | fragmento(include) | XML del programa y datos de reclutamiento | L: `tb_nextouch_movie_program`, `tb_nextouch_screensaver`, `tb_recruit*`, `tb_shop` | |
| `obi_sample.php`, `wait_time_sample.php` | (muestras) | detalle | Páginas de ejemplo de 帯 y tiempo de espera | — | Plantillas de muestra, migrar solo si se usan |

**Reglas:** igual que digital (listas con `<>`), con **dos IDs de terminal** (`AppKey`); borrado solo para IP de desarrollo (`Common::isDevelopmentAuthorityFromIP`, una sola IP, o `STAGE=docker`). Reemplazo: Gate `manageKiosk` + permiso real en lugar de IP; tablas pivote; `Storage`.

**Duplicación:** `digital_signage/` y `kiosk/` son dos copias casi idénticas con distinto prefijo de tabla (`tb_*` vs `tb_nextouch_*`). En Laravel: un solo módulo `signage` con un campo `type` (`digital|kiosk`) o dos modelos que comparten un trait/servicio.

---

## ICカード管理 (`category7/felica/`)

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `terminal_list.php` | 端末管理 | lista | Lista de terminales FeliCa, ▼▲ orden, 表示件数, borrado | L: `tb_shop`, `tb_terminal` | Los terminales los da de alta el propio dispositivo (sin pantalla de alta) |
| `card_list.php` | カード管理 | lista | Tarjetas de socios: búsqueda 全/未/済, AND/OR, por ID y nombre; asigna una tarjeta a un socio (`ajax_search_kaiin.php`) y la quita | L: `tb_customer`, `tb_shop`, `tb_customer_card`; E: `tb_customer` (`felica_no`), `tb_customer_card` | Fatal en cualquier búsqueda sin resultados (OFFSET negativo): corregido con `max(0, …)` |
| `ajax_search_kaiin.php` | — | ajax | Busca socios por nombre/ID para asignar | L: `tb_customer`, `tb_shop` | |
| `ajax_del.php` | — | ajax/borrado | Borra una tarjeta o terminal (`DELETE` físico) y limpia `tb_customer.felica_no`; otras tablas: `disp_flg = '9'` | E: `tb_terminal`, `tb_customer_card`, `tb_customer` | **Riesgo:** el nombre de la tabla sale de `$_REQUEST['tbNo']` (`'tb_' + valor`), con `key = md5(session_id())` como único control; un valor arbitrario toca otra tabla. No reproducir: lista blanca de modelos + Policy |

**Regla:** un socio solo puede tener una tarjeta; al borrar la tarjeta se vacía `felica_no` del socio. El terminal usa `md5(session_id())` como clave CSRF casera: reemplazar por el token CSRF de Laravel.

---

## 予約管理 (`category9/yoyaku/`)

Interfaz propia sin menú lateral (`yoyaku_navi.php`: 本日, 店舗毎予約数, 予約項目管理, スタッフ割当, 予約設定, 設備管理, フリー予約; las ventanas se abren como popups 600 px). Se entra por `time_table.php` desde `/system/select.php` (総合TOP). La reserva tiene `tb_yoyaku` (cabecera con cliente y menús) ligada a una franja `tb_yoyaku_uketsuke_day` (empleado, día, hora inicio/fin, personas, `status_flg`).

### 予約表 (tabla de horarios)

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `time_table.php` | 予約表 | lista | Vistas día, 月間 y 週間; 新規予約リスト, 来店リスト; popup de reserva (更新, 削除), 状況変更, cambiar hora, mover a otro empleado, descanso | L: `tb_base_system_grandchild`, `tb_staff`, `tb_yoyaku`, `tb_yoyaku_config`, `tb_yoyaku_koumoku_master`, `tb_yoyaku_uketsuke_day` | 1201 líneas; es el corazón del módulo |
| `time_table_staff.php` | スタッフ別予約表 | lista | Vista mensual por empleado | L: `tb_staff`, `tb_staff_works`, `tb_yoyaku`, `tb_yoyaku_config`, `tb_yoyaku_koumoku_master`, `tb_yoyaku_uketsuke_day` | |
| `new_time_table.php` | 予約表 (nuevo) | lista (sin enlace) | Versión nueva; solo carga | L: las mismas | Nada la enlaza; el nombre del menú sale vacío. No migrar |
| `ajax_time_table.php` | — | ajax | Devuelve el HTML/datos de la tabla por rango | L: `tb_customer`, `tb_raiten_rireki`, `tb_staff`, `tb_staff_works`, `tb_yoyaku*` | 713 líneas |
| `ajax_move_objs.php` | — | ajax | Arrastrar/mover una reserva o descanso a otro empleado u hora; crea la franja si no existe | L: `tb_staff`, `tb_yoyaku`, `tb_yoyaku_config`; E: `tb_yoyaku`, `tb_yoyaku_uketsuke_day` | El arrastre con ratón no se probó a mano |
| `ajax_uketsuke_status.php` | — | ajax | Cambia `status_flg` de una franja y registra el log (`Log`) | E: `tb_yoyaku_uketsuke_day` | |
| `ajax_loadNewYoyakuList.php`, `ajax_loadNewRaitenList.php` | — | ajax | Listas laterales de 新規予約 y 来店 | L: `tb_customer`, `tb_staff`, `tb_yoyaku`, `tb_raiten_rireki` | |
| `ajax_getYoyakuDatetimeForm.php` | — | ajax | Formulario de fecha y hora del popup | L: `tb_staff`, `tb_yoyaku`, `tb_yoyaku_config`, `tb_yoyaku_uketsuke_day` | |
| `time_table_function.php`, `sample_array.php` | — | fragmento(include) | Funciones de pintado; datos de ejemplo | L: `tb_customer` | |

### Flujo de reserva

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `yoyaku.php` | 予約 | alta | Datos del cliente, elección de empleado y menú, 確定 | L: `tb_customer` | El aviso de `$menu` rompía el JS (sin empleados ni menú no se podía reservar) |
| `yoyaku_calendar.php` | 時間選択 | alta | Calendario de franjas libres con menú opcional | L: `tb_setsubi`, `tb_shop`, `tb_staff`, `tb_staff_works`, `tb_yoyaku`, `tb_yoyaku_config`, `tb_yoyaku_koumoku_master`, `tb_yoyaku_uketsuke_day` | Sin día configurado ofrece `新規設定を行う` |
| `yoyaku_customer_select.php` | 顧客情報入力 | alta | Elegir socio o escribir datos libres | L: `tb_customer`, `tb_staff`, `tb_staff_works`, `tb_yoyaku_koumoku_master` | |
| `yoyaku_register.php` | (registro de reserva) | registro(POST)/correo | Inserta/actualiza `tb_yoyaku`, numera con `hatuban`, evita doble envío con `register_verify`, envía correo a empleado y cliente | L: `tb_customer`, `tb_koumoku`, `tb_shop`, `tb_staff`, `tb_staff_works`, `tb_yoyaku_koumoku_*`; E: `tb_yoyaku`, `hatuban` | Correo vía `crm_send_mail` (clase `cYoyaku::sendYoyakuMail`) |
| `ajax_yoyaku_register.php` | — | ajax | Alta/edición desde el popup; llama por HTTP a `ajax_move_objs.php` (`file_get_contents`) | L/E: `tb_yoyaku`, `tb_yoyaku_uketsuke_day` | Llamada HTTP a sí mismo: sustituir por una llamada a servicio |
| `ajax_yoyaku_menu.php`, `ajax_menu.php`, `ajax_staff.php`, `ajax_search_kaiin.php`, `ajax_yoyaku_koumoku_category.php` | — | ajax | Menús por empleado, empleados, búsqueda de socios, categorías | L: `tb_staff`, `tb_staff_works`, `tb_yoyaku_koumoku_*`, `tb_customer` | |
| `ajax_free_yoyaku_rank.php`, `ajax_getReservationData.php` | — | ajax | Prioridad フリー予約; datos para el POS | L/E: `tb_free_yoyaku_rank`; L: `tb_c2_catalog`, `tb_yoyaku_config`... | `ajax_getReservationData.php` necesita el POS externo (`$posConnect`): da fatal sin él |
| `yoyaku_new.php`, `yoyaku_new_select_staff.php`, `yoyaku_new_select_menu.php`, `yoyaku_new_customer_select.php`, `yoyaku_new_customer_form.php`, `yoyaku_new_register.php`, `yoyaku_new_complete.php` | (flujo `yoyaku_new_*`) | alta/registro(POST) | Flujo antiguo de reserva en 5 pasos | L: `tb_base_system`, `tb_shop`, `tb_staff*`, `tb_yoyaku_koumoku_master`; E: `tb_yoyaku` | **Roto en master:** escribe en la columna inexistente `tb_yoyaku.moushikomi_time` y redirige a `yoyaku_new_calendar.php`, que no existe. No migrar (usar el flujo `yoyaku.php`) |

### 受付日設定 y configuración

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `yoyaku_uketsuke_day.php`, `yoyaku_uketsuke_register.php`, `yoyaku_uketsuke_delete.php` | 予約項目管理 / 予約受付削除 | alta/edición, registro(POST), borrado | Define días y horas de atención de un empleado (descanso incluido) | L: `tb_customer`, `tb_staff`, `tb_yoyaku`, `tb_yoyaku_config`; E: `tb_yoyaku_uketsuke_day` | |
| `yoyaku_multi_uketsuke_day.php`, `yoyaku_multi_uketsuke_register.php` | 予約項目管理 (varios días) | alta, registro(POST) | Alta de varios días a la vez | L: `tb_yoyaku_config`; E: `tb_yoyaku_uketsuke_day` | |
| `yoyaku_koumoku_new.php`, `yoyaku_koumoku_register.php`, `ajax_yoyaku_koumoku_new.php` | 予約項目管理 | alta/edición, registro(POST), ajax | Menús reservables (base y opcional): precio, tiempo, categoría, 設備, ▲▼ | L: `tb_setsubi`, `tb_yoyaku_koumoku_category`, `tb_yoyaku_koumoku_master`; E: `tb_yoyaku_koumoku_master` | `ajax_yoyaku_koumoku_new.php` usa `$posConnect` en la copia de category11 |
| `yoyaku_koumoku_category.php`, `yoyaku_koumoku_category_register.php` | 予約項目カテゴリ管理 | config, registro(POST) | Categorías de menú, ▲▼, 表示 | L/E: `tb_yoyaku_koumoku_category` | |
| `setsubi_new.php`, `setsubi_register.php` | 設備管理 | alta/edición, registro(POST) | Alta, edición y borrado de equipos/salas | L/E: `tb_setsubi`, `hatuban` | |
| `staff_works.php` | スタッフ施術管理 | config | Asigna menús a cada empleado y su tiempo propio | L/E: `tb_staff_works`, `tb_staff` | Fuera del popup recarga en bucle (`parent.location.reload()`) |
| `free_yoyaku.php`, `free_yoyaku_register.php` | フリー予約設定 | config, registro(POST) | Reserva sin empleado: activar, nombre, selección de empleado, línea de tiempo | L/E: `contents_master` (`text_data`) | Pares clave-valor en `contents_master` |
| `yoyaku_config.php`, `ajax_yoyaku_config.php` | 予約設定 (各店舗設定) | config | Horario de la tienda por día, aviso por correo (`mailmag_master`) | L/E: `tb_yoyaku_config`, `mailmag_master`, `hatuban` | |
| `yoyaku_form_config.php` | 予約設定 (フォーム) | config | Campos del formulario de reserva | L/E: `tb_koumoku` | |
| `yoyaku_kinou_config.php` | 予約設定 (機能) | config | Intervalo de tiempo (`yoyaku_time_interval`) y `meyasu_jikan_flg` | L/E: `admin_master` | |
| `yoyaku_comment_config.php` | 予約設定 (注釈) | config | Texto de注釈 del formulario | L/E: `tb_manage_words` (y `tb_koumoku`) | Un `<textarea>` guarda CRLF |
| `inc/yoyaku_control.php`, `inc/yoyaku_navi.php` | — | fragmento(include) | Cabecera/navegación y control de menú | L: `tb_base_system_child`, `tb_base_system_grandchild` | `mktime` con `is_dst` |

### Listados, impresión, CSV y 関連顧客

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L=lee, E=escribe) | Notas |
|---|---|---|---|---|---|
| `view_shop.php` | 予約一覧 (店舗毎予約数) | lista | Reservas por tienda y día | L: `tb_shop`, `tb_staff`, `tb_yoyaku`, `tb_yoyaku_uketsuke_day` | |
| `yoyaku_list_calendar.php` | 予約一覧 | lista | Calendario mensual de reservas | L: `tb_reserve`, `tb_shop`, `tb_shop_work` | Lee `tb_reserve` (módulo categoría 3); `mktime` |
| `yoyaku_list_shop.php`, `yoyaku_list_time_table.php`, `yoyaku_revice.php` | (一覧) | lista | Variantes de listado | L: `tb_base_system` | |
| `pop_calendar.php` | 年間カレンダー | detalle | Selector anual en popup | — | `mktime` |
| `print/yoyaku_list_print.php` | 予約一覧出力 | lista/pdf-html | Lista del día para imprimir | L: `tb_customer`, `tb_staff`, `tb_yoyaku`, `tb_yoyaku_koumoku_master`, `tb_yoyaku_uketsuke_day` | `.swf` para el CSV |
| `print/csv_download.php`, `print/csv_download/csv_file_out.php`, `csv_file_del.php` | (CSV 予約) | csv | CSV del día | L: `tb_customer`, `tb_staff`, `tb_yoyaku_koumoku_master` | Flash |
| `yoyakuPrint.php`, `ajaxYoyakuPrint.php` | 予約プリント | detalle/ajax | Impresión en tiempo real: la impresora de la tienda consulta las reservas con `print_flg = '1'` y las marca `'2'` | L: `tb_customer`, `tb_staff`, `tb_yoyaku*`; E: `tb_yoyaku` | La impresora no se probó. Hace sondeo continuo (polling) |
| `related_customer.php`, `ajax_related_customer.php` | 顧客関連づけ | detalle/ajax | Vincula una reserva a un socio (`tb_customer_cd`) | L: `tb_yoyaku`, `tb_yoyaku_uketsuke_day`; E: `tb_yoyaku` | |

**Flujo principal:** time_table (hora libre) → yoyaku / popup → yoyaku_calendar (hora) → yoyaku_customer_select → yoyaku_register (+ correo) → time_table. Configuración previa:予約設定, 予約項目管理, スタッフ割当, 受付日設定.

**Reglas de negocio no obvias**
- Una reserva no existe sin franja: `tb_yoyaku_uketsuke_day` define (empleado, día, `start_time`, `end_time`, `people`), y `ajax_move_objs.php` crea una franja al mover.
- `yoyaku_time_interval` y `meyasu_jikan_flg` (tiempo estimado) condicionan la longitud de la franja y de los menús.
- Protección anti-doble envío con `register_verify` en sesión; en Laravel usar un token idempotente/`PRG`.
- Reserva con menú base más opcionales (`menu` y `option_menu` se guardan como lista separada por comas de `cd`).
- El correo de confirmación contiene un enlace `login.php?login_id=md5(login_id)&login_pass=md5(login_pass)`: **no reproducir**; usar enlaces firmados (`URL::temporarySignedRoute`) hacia el mypage.
- **No reproducir** la cuenta de calendario con `mktime(..., is_dst)` (1970): afecta a `time_table`, `time_table_staff`, `yoyaku_calendar`, `view_shop`, `yoyaku_list_calendar` y `pop_calendar`.
- Las franjas y el descanso deben calcularse con zona horaria `Asia/Tokyo` explícita.

**Servicios viejos y reemplazo:** `crm_send_mail`/`mb_send_mail` (SMTP con Symfony Mailer) → `Mailable` en cola con plantilla Blade; CSV Flash → `StreamedResponse`; impresión en tiempo real por sondeo → endpoint autenticado con token por impresora o evento/broadcast; popups 600 px → modales o rutas completas; llamadas HTTP a sí mismo (`file_get_contents`) → llamadas directas a servicios; POS externo (`$posConnect`) → cliente HTTP/API del POS o retirar.

---

## Tablas de BD del grupo

| Tabla | Para qué | Submódulos que la usan | Notas (columnas clave) |
|---|---|---|---|
| `hatuban` | Contador de numeración por tabla | todos | `koumoku` (nombre), `kazu`; `SELECT … FOR UPDATE` |
| `tb_staff` | Personal | staff, kintai, working, schedule, config, yoyaku | `cd`, `shop_cd`, `felica_no`, `login_id`, `login_pass` (plano), `disp_flg`, `synchronize_flg` |
| `tb_staff_group` | Grupos de personal | staff | `cd`, orden |
| `tb_belongpost`, `tb_officialposition` | Cargo y pertenencia | staff, information | |
| `tb_information` | Avisos para el personal | staff | tienda (0 = 全店舗), cargo |
| `tb_koumoku` | Campos configurables (buscable/visible) | staff, yoyaku (form, comentario) | `tb_koumoku_name`, `koumoku_select`, `mail_mag_select` |
| `tb_card`, `tb_felica_mode` | Tarjetas del personal y modo del lector | config, staff | `card_flg`, `felica_no`, `staff_cd`; `mode` (0/1/2/9), `update` |
| `tb_work_time` | Asistencia mensual por empleado | working, config, staff | `syukkinbi` (ts del día 1), `syukkin_time_N`, `taikin_time_N`, `syukkin_flg_N`, `syukkin_kubun_N`, `felica_no` |
| `kintai_tb_config`, `kintai_tb_hiduke`, `kintai_tb_kintai` | Terminal de asistencia: configuración, día y marcas | kintai | `password`, `kintai_flg`, `felica_no`, `tb_staff_cd`, `bikou` |
| `tb_shop_work` | 営業日/休日 por tienda y día | config, working, schedule, yoyaku (calendario) | |
| `tb_staff_shift`, `tb_staff_shift_kind` | Turnos y su 区分 | schedule, kintai | |
| `tb_shop`, `tb_base_system*` | Tiendas (con `pos_domain`) y menú | todos | `pos_domain` = servidor POS de la tienda |
| `tb_terminal`, `tb_customer_card`, `tb_customer` | Terminales FeliCa, tarjetas de socios, socios | felica, card_signage, yoyaku | `tb_customer.felica_no` |
| `tb_url_list` | Tarjetas de papel con URL/QR | card_signage | `id` único con `disp_flg = '1'` |
| `tb_screensaver`, `tb_c7_movie_program`, `tb_display`, `tb_dispaly_systems`, `tb_mycoupon_master` | Señalización digital | digital_signage, kiosk | listas `<>`; `tb_screensaver_active_time`; **no existe** `tb_screensaver.tb_c7_movie_program_cd` |
| `tb_nextouch_screensaver`, `tb_nextouch_movie_program`, `tb_nextouch_display` | Quiosco | kiosk | `AppKey` en display |
| `tb_recruit`, `tb_recruit_category` | Datos de reclutamiento para el XML de información | digital_signage, kiosk | pertenece a `category4` |
| BD externa de beacons | Beacons y terminales | beacon | host `b-develop.g-api.net`, `BEACON_DB_PASSWORD` |
| `tb_yoyaku` | Reserva | yoyaku | `cd`, `tb_customer_cd`, `print_flg`, `menu`, `option_menu`, `start_time`; **no existe** `moushikomi_time` |
| `tb_yoyaku_uketsuke_day` | Franja de atención | yoyaku | `tb_staff_cd`, `moushikomi_date`, `start_time`, `end_time`, `people`, `status_flg`, `hyouji_flg` |
| `tb_yoyaku_config` | Configuración de reserva por tienda | yoyaku | horario por día |
| `tb_yoyaku_koumoku_master`, `tb_yoyaku_koumoku_category` | Menús reservables y categorías | yoyaku | orden, tiempo, precio, base/opcional |
| `tb_staff_works` | Menús por empleado | yoyaku | tiempo propio |
| `tb_setsubi` | Equipos/salas | yoyaku | |
| `tb_free_yoyaku_rank` | Prioridad フリー予約 | yoyaku | |
| `contents_master`, `admin_master`, `tb_manage_words`, `mailmag_master` | Ajustes sueltos | yoyaku | clave-valor `text_data`; `yoyaku_time_interval`, `meyasu_jikan_flg` |
| `tb_raiten_rireki`, `tb_reserve`, `tb_c2_catalog` | 来店 historia, reservas de categoría 3, catálogo POS | yoyaku | `tb_c2_catalog` solo vía POS |

---

## Propuesta Laravel 13

**Modelos Eloquent** (inglés, snake_case en BD): `Staff`, `StaffGroup`, `StaffNotice` (tb_information), `Position`, `AttendanceRecord`, `AttendanceTerminalConfig`, `AttendanceKind`, `Shift`, `ShiftKind`, `ShopWorkDay`, `Card`, `FelicaMode`, `FelicaTerminal`, `CustomerCard`, `Beacon` (conexión `beacon`), `UrlCard`, `SignageScreen`, `SignageProgram`, `SignageContent` (con `type digital|kiosk`), `Reservation`, `ReservationSlot`, `ReservationConfig`, `ReservationMenu`, `ReservationMenuCategory`, `StaffMenu`, `Facility`, `FreeReservationRank`. Enums: `FelicaMode`, `AttendanceFlag`, `ReservationStatus`, `ScreenType`.

**Rutas (prefijos sugeridos):** `/staff` (+`/groups`, `/notices`, `/search-fields`), `/attendance` (+`/terminal`, `/kinds`, `/calendar`, `/totals`), `/shifts`, `/settings/felica`, `/terminals/beacons`, `/terminals/cards`, `/terminals/signage` (digital y kiosk), `/terminals/felica`, `/reservations` (`/timetable`, `/slots`, `/menus`, `/facilities`, `/config`, `/print`).

**Controladores:** resource controllers con `FormRequest`; acciones ajax como endpoints JSON; Livewire/Inertia para el timetable y el editor de kiosk.

**Jobs / scheduler:** `SyncStaffToPos` (cola), `SendReservationMail` (cola), `BuildSignageFeed` (genera el XML/JSON al guardar), `PruneOldExports`; `Scheduler` solo si algún cron legacy se conserva (no hay cron en este grupo).

**Policies / Gates:** `StaffPolicy`, `AttendancePolicy`, `ReservationPolicy`, `manageKiosk`/`deleteKiosk` (reemplaza la IP de desarrollo), `viewWorkReport` (reemplaza `SERVER_ADDR`). Throttle y token propio para las rutas que usa el terminal físico.

**Reutilizable en CRMSystem (`C:\Users\User\VivaTech\CRMSystem\public_html`):**
- `app/Models/Shop.php` y `Terminal.php` y la migración `create_terminals_table`: base de `FelicaTerminal`/`Terminal`; `Terminal::shop()` ya modela tienda y terminal.
- `Customer`, `CustomerDevice`, `Visit` (`2026_10_02_120008_create_visits_table.php`): 来店 y socio para `ReservationSlot` y `CustomerCard`.
- `Admin`/`User` y two-factor: base de autenticación del personal (`Staff` aún no existe: `Doc/database/TABLAS_MODULO_CLIENTES.md` marca "Falta la tabla de empleados").
- `BaseModel`, `Concerns/HasUid`, `Enums/*` para el patrón de modelos y enums. No hay nada propio de asistencia, reservas ni señalización.

**Orden recomendado de implementación**
1. Prerrequisitos de otros grupos: tiendas, autenticación del personal y `Staff` (grupo 01/clientes y base de configuración).
2. `category5/staff` + grupos + cargos (sin POS) y 営業日設定 (`ShopWorkDay`).
3. FeliCa: `felica_mode`, `card`, `felica` (terminales y tarjetas de socio); dependen de Staff y Customer.
4. `kintai` y `working`: normalizar `tb_work_time` → `attendance_records` con importador; CSV; terminal.
5. `schedule` (turnos) con `ShiftKind`.
6. `category9/yoyaku`: config y menús → staff_works → 受付日 → flujo de reserva (+correo) → timetable → listados/print → 関連顧客. Depende de Customer (grupo clientes), Staff y Shop.
7. `category7` señalización (digital y kiosk unificadas) y `card_signage`; `beacon` al final (depende de la BD externa).

---

## Riesgos y dudas

**Seguridad (reglas a no reproducir)**
- `category5/working/change_syukkin2.php`: el día sin validar entra en el **nombre de columna** del `UPDATE tb_work_time` (inyección SQL con sesión del personal; `CONTINUE.md`). Reemplazo: validar `1..31` y lista blanca; mejor aún, la tabla normalizada.
- `category7/card_signage/control.php` → `cUrl.php`: título y URL sin escapar al SQL (`PENDING.md`/`CONTINUE.md`, paso 59).
- `category7/felica/ajax_del.php`: el nombre de la tabla viene de la petición (`tbNo`); `md5(session_id())` hace de token CSRF.
- Contraseñas en texto plano: `tb_staff.login_pass` y `kintai_tb_config.password`; enlace de correo con `md5(login_id)` y `md5(login_pass)`.
- Acceso restringido por IP (`isDevelopmentAuthorityFromIP`) o por `SERVER_ADDR` (`202.218.51.245`): usar permisos reales.

**Fechas**
- **Calendarios de 1970 / `mktime` con 7 argumentos (`is_dst`)**: 220 llamadas en 24 archivos del proyecto; en este grupo `category5/working`, `kintai`, `config`, `schedule` y `category9` (calendario de 予約). En PHP 7 daban 1970 y ya están corregidas en master (`PENDING.md` 1b.1, decidido: se queda corregido). En Laravel usar Carbon. Hay que comprobar si producción guardó fechas de 1970 (営業日設定, horarios) y no mezclarlas con las correctas al importar.
- `date()`/`mktime()` con dato vacío se mantienen corregidos (1b.6); cuidado con `reserve_time` vacío (muestra 1970/01/01).
- Zona horaria Asia/Tokyo explícita; la jornada con corte a las 5:00.

**Integraciones externas** (`Doc\legacy-crm2\INTEGRATIONS.md`)
- Servidor POS por tienda (`tb_shop.pos_domain`, BD `crm2019`): sincronización de personal (`db_sync_all2`) y `ajax_getReservationData.php` / `$posConnect` en las AJAX de category11/yoyaku. Sin POS de prueba el código da 500; decidir si se mantiene.
- BD externa de beacons (`b-develop.g-api.net`): rechaza la conexión; decidir migrar o retirar.
- Lector FeliCa y terminal de asistencia físicos: llaman a `tool/api` (fuera de este grupo); no se pudo probar la asignación de tarjetas.
- Impresora de reservas por sondeo (`ajaxYoyakuPrint.php`): no probada.
- Reproductores de señalización leen XML en `hp/uploads/c7/display/` (carpeta de `hp/`, obsoleta): hay que definir el nuevo endpoint y avisar a quien tenga los reproductores.
- Servidor de correo (Symfony Mailer en la copia migrada; antes `mb_send_mail`): MailHog en pruebas.

**Pantallas rotas** (`Doc\legacy-crm2\BROKEN-FILES.md`)
- `category9/yoyaku/yoyaku_new_register.php` y flujo `yoyaku_new_*`: columna `moushikomi_time` inexistente y `yoyaku_new_calendar.php` inexistente.
- `category7/digital_signage|kiosk/movie_program_register.php` (opción 最後に追加) y las bajas: columna `tb_c7_movie_program_cd` inexistente.
- `category5/schedule/time_table.php` (404 desde `staff_shift`), `new_time_table.php`, `shift.php` y `schedule.php` sin enlace.
- `ajax_getReservationData.php`: necesita POS externo.

**Tablas que faltan** (`MISSING-TABLES.md`): BD de beacons, servidor POS, `tb_screensaver.tb_c7_movie_program_cd`.

**Decisiones pendientes** (`PENDING.md`): descarga CSV Flash (propuesta: botón HTML + endpoint con sesión, 11b); preguntar a Japón si las pantallas de `mktime` fallaban en producción (1b.1); `pg_fetch_result` con vacío ya vuelve a `NULL` como master (afecta `display_detail` y `movie_program_detail`: sin vista previa y marco horizontal); duplicación `digital_signage` vs `kiosk`; si se mantiene el sync a POS; si la baja de empleado debe borrar `tb_work_time`; destino de las uploads de `hp/uploads`.

**Dudas abiertas**
- Alcance real de `kiosk` frente a `digital_signage` (mismo código con otros prefijos).
- Si los empleados siguen iniciando sesión con `md5(login_pass)`, y cómo migrar las contraseñas.
- Si `working_chouhyou`, `schedule/*` y los menús restringidos a `202.218.51.245` siguen en uso fuera de ese servidor.
