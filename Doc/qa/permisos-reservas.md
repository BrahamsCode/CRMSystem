# Permisos: 予約管理 (category9)

Revisión S1, 2026-11-27 ([permisos.md](permisos.md)).

| Dónde | Legacy | Laravel | Estado |
|---|---|---|---|
| `yoyaku_navi`: 予約項目 / スタッフ割当 / 予約設定 | 1027-1 / 1028-1 / 1029-1 | `@can('kengen', [1027, 1])`, `[1028, 1]`, `[1029, 1]` | ✔ |
| Agenda (`ajax_time_table`): datos del cliente | 1001-1 | `@can('kengen', [1001, 1])` | ✔ |

No se pudo recorrer con el navegador: la agenda aún no estaba integrada en la rama revisada.
