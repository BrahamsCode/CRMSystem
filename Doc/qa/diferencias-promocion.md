# Diferencias: 販売促進管理 (category3)

Revisión visual S1, 2026-11-27 (método en [README.md](README.md)). Pantallas: portada, `mail_mag/mail_customer_select`, `mail_mag/mail_past`, `mycoupon/mycoupon_list` (local en `/admin/promocion/…`).

| Pantalla | Qué difiere | Detalle |
|---|---|---|
| Menú lateral (todas) | **Vacío en local**: solo LOGIN NAME; la muestra tiene los bloques メルマガ/お知らせ配信, 来店履歴, 予約, リマインダ… | `config/menu/promocion.php` usa otro formato (lista de bloques con `class`/`system`/`items`/`route`/`kengen`) y no el contrato de NUCLEO.md §6 (`category`, `blocks[title, child, ac, links[label, url, can]]`): `LegacyUi::menu()` no encuentra `blocks`. Pasarlo al contrato (las entradas `route` → `url` con `route()` no sirve en config: usar la URL; `kengen [cd, nivel]` → `'can' => 'kengen.<cd>.<nivel>'`; `title_app` → `title_from`). Desde `7d54d3a` el núcleo ya deduce la categoría 3 aunque falte `category`. |
| Pantallas `/admin/promocion/…` | `<title>` sin el nombre del sitio: «クーポン 一覧» frente a «クーポン 一覧 - shop»; igual en «メール 発行条件選択» | Las vistas ponen el título sin `' - ' . $siteName`. |
| `mycoupon/mycoupon_list` | Local añade un desplegable de tienda (desactivado) delante de 「メルマガ配信用」 en ■クーポン 抽出 | Captura: [capturas/promocion-mycoupon_list.png](capturas/promocion-mycoupon_list.png). El listado vacío es por datos. |
| `mail_mag/mail_past` | En local tardó más de 30 s y no llegó a cargar (dos intentos) | Revisar la consulta con la BD del backup (`crmsystem2_nucleo`). |

Cabecera y pie: sin diferencias (salvo サポート, ver README).
