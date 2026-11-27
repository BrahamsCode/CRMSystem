# Diferencias: 予約管理 (category9)

Revisión visual S1, 2026-11-27 (método en [README.md](README.md)). Pantallas: `/system/category9/` y `yoyaku/yoyaku_config.php` ~ `/admin/reservas/settings/shop`.

| Pantalla | Qué difiere | Detalle |
|---|---|---|
| `/system/category9/` | Desde `7d54d3a` redirige a `yoyaku/time_table.php` como el legacy, pero esa pantalla (la agenda) **no existe** en local: 404 | Es la entrada del módulo (pestaña 予約管理 de la cabecera); los popups de ajustes se abren desde ella. |
| `yoyaku_config.php` (各店舗設定) | Sin diferencias | Popup `layouts.legacy-popup`. |

## Segunda pasada (S1, 2026-11-27): R5 y R6 de S2

Muestra (master, solo GET salvo el login) ~ web principal `localhost:8081` (`laravel13`, BD `crmsystem2`). Comparación del texto visible de `.main` línea a línea y capturas lado a lado.

| Pantalla | Resultado |
|---|---|
| `yoyaku/time_table.php` (予約表) | En local no sale la tabla de personal: **por datos** (`tb_staff_works` está vacía en `crmsystem2`; en la muestra hay 王 y 管理者 con 施術). **Visual**: en local el tercer mini calendario (1月) baja a otra línea; en la muestra los tres van en fila. Captura: [capturas/reservas-time_table.png](capturas/reservas-time_table.png). Revisar el ancho del bloque de calendarios con datos iguales. |
| `yoyaku/view_shop.php` (店舗毎予約数) | Igual. |
| `yoyaku/yoyaku_list_calendar.php` (年間カレンダー) | La muestra pinta un día **31** en noviembre (`31 (0件)`); local, 30 días. Es un error del legacy: no hace falta copiarlo. |
| `yoyaku/print/yoyaku_list_print.php` | Igual (página de impresión vacía en los dos lados). |
| `category3/reservation/reserve_calendar.php`, `category_entry.php`, `config.php` | Iguales. |
| `category3/reservation/reserve_list.php` | La muestra pone `■予約一覧 (1970/01/01)` sin fecha elegida; local `■予約一覧 ()`. Correcto no reproducir el 1970 (CONVENCIONES). |
| `category3/reservation/reserve_customer_search.php` | Igual salvo el árbol de grupos de tienda (test1, test2, Mujeres…), que solo existe en la BD de la muestra. |
| `category3/reservation/model_reservation.php` | 「現在過去お知らせ配信はありません」 frente a 「現在過去配信はありません」: la muestra pone お知らせ (nombre de la herramienta con la app activa). Comprobar con la misma `admin_master.application_use_flg`; si coincide, falta el `tool_name`. |
| R7 (`r/`, `reserve/`) | No es pantalla: JSON con pruebas de contrato byte a byte de S2. |
