# Revisión de permisos de punta a punta

S1, 2026-11-27. Objetivo: un usuario **no master** con kengen parcial ve en el menú y puede abrir solo lo que el legacy le deja.

## Cómo decide el legacy

- `class/cKengen.php`: `myKengen[contenido][1 閲覧 | 2 新規 | 3 編集 | 4 削除]` sale de `tb_kengen` del grupo del staff (grupo con `disp_flg = '1'`, contenido con `hyouji_flg = '1'`). **Sin ninguna fila, todo permitido** (fallback abierto). `master` (`login_id = 'master'`) lo pasa todo. `App\Services\Nucleo\Kengen` hace lo mismo (comprobado línea a línea).
- **Menús** (`left_navi*.php`): cada enlace con `myKengen[x][y] == 1 || rank == master`. Comparados enlace a enlace con `config/menu/*.php`: coinciden en los cinco módulos (diferencia anotada en clientes).
- **Páginas**: el legacy casi nunca bloquea la entrada por URL; oculta botones (編集/削除/CSV/お知らせ) y campos (contenidos 1–51 = campos del cliente). Las 42 páginas con `myKengen` se revisaron contra las vistas migradas.
- **Cabecera y システム選択**: 基本情報管理 solo para master; SNS管理 con kengen 1034-1. Igual en Laravel (`LegacyUi::visibleModules`).
- `$_SESSION['rank']` de un no master es su `rank_pass`; si valiera `'master'` el legacy lo trataría como master. En los datos `rank_pass` guarda la matriz antigua (`4,4,4…<>…`) y ningún formulario lo rellena, así que `Staff::isMaster()` (`login_id = 'master'`) es equivalente.

## Prueba con un usuario real

En la BD local `crmsystem2_nucleo` (nunca en la muestra): grupo 900 «QA部分権限» y staff `qa_partial` / `qa12345` con 閲覧 solo en 1001, 1002, 1005, 1012, 1016, 1023, 1024 y 1033, y nada en 1009 (script: `.playwright-mcp/qa/perm.js`). Resultado:

| Dónde | Ve / abre | Esperado (legacy) |
|---|---|---|
| Pestañas y システム選択 | todo menos 基本情報管理 | igual |
| 顧客管理 | sin 顧客新規登録 ni ポイント管理/履歴 | igual |
| 販売促進管理 | 予定/過去配信 sí, 新規配信 no; クーポン nada; 来店履歴一覧 sí, カードリーダ設定 no | igual |
| 業務支援管理 | sin スタッフ新規登録 ni グループ設定 | igual |
| マイページ管理 | オススメ管理 y 求人情報一覧; sin マイコレ ni 求人新規/職種 | igual |
| `/system/category0/*` | **antes abría** (権限管理 incluido); ahora 403 | el legacy lo ocultaba pero no lo cerraba: ver Núcleo |
| `customer_new`, `staff/create`, `staff-groups`, `shift-kinds` | 403 | el legacy abría la página y solo ocultaba el enlace: Laravel es **más estricto** (aceptable) |
| `recommendations` | 登録 y 削除 visibles | ocultos sin 1016-2/3/4: ver [permisos-mypage.md](permisos-mypage.md) |

## Núcleo: corregido

- **基本情報管理 solo para master en el servidor** (`routes/legacy/base.php`, `can:nucleo.master`): un no master podía abrir por URL 店舗情報, 権限管理 (y cambiar la matriz de permisos), 機能設定… El legacy lo oculta en cabecera, システム選択 y menú, pero no cerraba las URL. Queda abierto el asistente de primer acceso `system/config.php` (lo ve quien entre primero, como el legacy) y los comunes `zip.php` y gráficos. Prueba: `tests/Feature/Base/Category0AccessTest.php`.

## Por módulo

| Módulo | Archivo |
|---|---|
| 顧客管理 | [permisos-clientes.md](permisos-clientes.md) |
| 販売促進管理 | [permisos-promocion.md](permisos-promocion.md) |
| 業務支援管理 | [permisos-operaciones.md](permisos-operaciones.md) |
| マイページ管理 | [permisos-mypage.md](permisos-mypage.md) |
| 端末管理 | [permisos-terminales.md](permisos-terminales.md) |
| 予約管理 | [permisos-reservas.md](permisos-reservas.md) |
