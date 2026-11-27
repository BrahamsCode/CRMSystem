# Permisos: 販売促進管理 (category3)

Revisión S1, 2026-11-27 ([permisos.md](permisos.md)).

| Dónde | Legacy | Laravel | Estado |
|---|---|---|---|
| Menú メルマガ: 新規 / 予定 / 過去 | 1005-2 / 1005-1 / 1005-1 | `kengen.1005.*` | ✔ (probado con `qa_partial`) |
| Menú デコメール, フォロー, 自動 | 1006, 1007, 1008 (-2 新規, -1 一覧) | `kengen.100x.*` | ✔ |
| Menú クーポン: 新規 / 一覧 / 過去配信 | 1009-2 / 1009-1 / 1005-2 | igual | ✔ |
| Menú アンケート, 来店履歴, 予約, スタンプ | 1011, 1012, 1026, 1010 | igual | ✔ (1026 tiene `hyouji_flg` vacío: ningún no master lo ve, igual que el legacy) |
| Menú ポイント: 仕様 / ルール一覧 | 1037-2 / (ビーコン y 1037-3) | `kengen.1037.2` / `promocion.point-rules.enabled` | ✔ |
| `mycoupon_list` / `detail`: 編集 | 1009-3 **y** cupón de su tienda, o master | `CouponController` (`kengen.1009.3` + tienda) | ✔ |
| `mycoupon_detail`: お知らせ | 1005-2 | `kengen.1005.2` | ✔ |
| `mailmag_list`: 削除 | `myKengen[1005|1006|…][4]` | `Gate::allows('kengen.' …)` | ✔ |
| Entrada directa a `mail_customer_select`, `mycoupon_new` sin permiso | abre | abre | igual que el legacy |

Aparte (no es de permisos): `point/point_config.php`, en su rama de tablet (`roid.popUp`), abre `category0/config/point_alert_mail_template_entry.php` y `point_shikkou_alert_mail_template_entry.php` (este último ni existe en category0): es un error de copia del legacy; la rama de PC abre las de `category3/point/`. Al migrar 失効メール, usar siempre las de category3 (las de category0 se descartaron, ver `Doc/diseno/nucleo-base.md`).
