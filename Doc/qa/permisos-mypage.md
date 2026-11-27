# Permisos: マイページ管理 (category6)

Revisión S1, 2026-11-27 ([permisos.md](permisos.md)).

| Dónde | Legacy | Laravel | Estado |
|---|---|---|---|
| Menú マイコレ (5 pantallas) | 1035-1 y app activa | `mypage.mycolle.enabled` (app + `kengen.1035.1`) | ✔ |
| Menú オススメ管理 | 1016-1 | `kengen.1016.1` | ✔ |
| Menú 求人: 新規 / 一覧 / 職種 | 1023-2 / 1023-1 / 1023-4 | `kengen.1023.*` | ✔ (probado con `qa_partial`) |
| `recommend.php`: formulario de alta | 1016-2 (sin `cd`) | sin comprobación | ✘ `qa_partial` (solo 1016-1) ve 「この内容で登録する」 |
| `recommend.php`: editar (formulario con `cd`, enlace 修正, 表示順) | 1016-3 | sin comprobación | ✘ |
| `recommend.php`: 削除 | 1016-4 | sin comprobación | ✘ `qa_partial` ve 「削除する」 |
| `recruit_detail.php`: 編集 / 削除 | 1023-3 / 1023-4 | sin comprobación | ✘ |
| Guardado (POST) de オススメ y 求人 sin el permiso | el legacy solo ocultaba el botón | sin comprobación | recomendable cerrar también en el servidor (como clientes/operaciones) |

En `resources/views/admin/mypage`, sus controladores y rutas no hay ningún `@can` / `Gate` / `can:` de kengen: faltan todas las comprobaciones de página.
