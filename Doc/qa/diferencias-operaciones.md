# Diferencias: 業務支援管理 (category5)

Revisión visual S1, 2026-11-27 (método en [README.md](README.md)). Pantallas (muestra ~ local): portada, `staff/staff_list.php` ~ `/admin/operaciones/staff`, `staff_entry.php` ~ `staff/create`, `staff_group.php` ~ `staff-groups`, `form_config.php` ~ `staff-fields`, `information.php` ~ `information`, `working/working_calendar.php` ~ `shifts/month`.

| Pantalla | Qué difiere | Detalle |
|---|---|---|
| Menú lateral (todas) | Falta el bloque **勤怠管理** (勤怠一覧, 勤怠データ一覧, 端末パスワード変更, 各種設定) | Conocido: `config/menu/operaciones.php` lo deja fuera hasta que Antony confirme 勤怠. Los turnos van en スケジュール管理 (decisión del módulo). |
| Menú lateral (todas) | Los enlaces apuntan a `/admin/operaciones/…` en vez de las URL del legacy (`/system/category5/…`) | Estructural (rutas del módulo); las etiquetas coinciden. |
| `staff_list.php` | Sin diferencias | Captura: [capturas/operaciones-staff_list.png](capturas/operaciones-staff_list.png). |

Cabecera, pie, título y `<body>`: sin diferencias.
