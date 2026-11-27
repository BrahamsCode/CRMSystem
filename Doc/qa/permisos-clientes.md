# Permisos: 顧客管理 (category1)

Revisión S1, 2026-11-27 ([permisos.md](permisos.md)).

| Dónde | Legacy | Laravel | Estado |
|---|---|---|---|
| Menú 顧客新規登録 | 1001-2 | `clientes.create` | ✔ |
| Menú ポイント管理 / ポイント履歴 | `isExtShopDomain()` (no depende del rango) | `clientes.ext-shop`: master se lo salta por `Gate::before` | ✘ renombrar a `clientes.ext-shop.enabled` (ya en [diferencias-clientes.md](diferencias-clientes.md)) |
| Menú 売上管理 (karute_menu) | 1033-2 / 1033-4 / 1033-1 | `ventas.create` / `ventas.delete` / `ventas.view` | ✔ |
| Menú CSVインポート | IP de desarrollo o soporte, sin Relation | `nucleo.maintenance` | ✔ |
| `customer_list`: botones お知らせ / CSV | 1005-2 / 1002-1 | `@can('kengen', [1005, 2])` / `[1002, 1]` | ✔ |
| `customer_detail`: 編集 / 削除 | 1001-3 / 1001-4 | `@can('kengen', [1001, 3])` / `[1001, 4]` | ✔ |
| Campos del cliente (contenidos 1–51) en alta, confirmación y ficha | `myKengen[N][1]` | `@can('kengen', [N, 1])` | ✔ |
| Entrada directa a `customer_new.php` sin 1001-2 | abre (solo se ocultaba el enlace) | 403 | más estricto (aceptable) |
