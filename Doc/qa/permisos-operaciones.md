# Permisos: 業務支援管理 (category5)

Revisión S1, 2026-11-27 ([permisos.md](permisos.md)).

| Dónde | Legacy | Laravel | Estado |
|---|---|---|---|
| Menú スタッフ新規登録 | 1024-2 | `kengen.1024.2` | ✔ (probado con `qa_partial`) |
| Menú スタッフグループ設定 | master | `nucleo.master` | ✔ |
| Menú 勤怠: 端末パスワード変更 / 各種設定 | master | — | bloque 勤怠 pendiente (decisión de Antony) |
| `staff_list`: columna/acción con 1024-1 | 1024-1 | `@can('kengen.1024.1')` | ✔ |
| `staff_detail`: 編集 / 削除 | 1024-3 / 1024-4 | `@can` + `can:kengen.1024.3` / `.4` en rutas | ✔ |
| Entrada directa a `staff/create`, `staff-groups`, `shift-kinds` sin permiso | abre (solo se ocultaba el enlace) | 403 | más estricto (aceptable) |
