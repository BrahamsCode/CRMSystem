# Diferencias: 販売促進管理 (category3)

Revisión visual S1, 2026-11-27 (método en [README.md](README.md)). Pantallas: portada, `mail_mag/mail_customer_select`, `mail_mag/mail_past`, `mycoupon/mycoupon_list` (local en `/admin/promocion/…`).

| Pantalla | Qué difiere | Detalle |
|---|---|---|
| Menú lateral (todas) | ~~Vacío en local~~ **Corregido en la revisión de permisos**: `config/menu/promocion.php` ya sigue el contrato y el menú sale completo | El vacío venía de que el servidor de prueba leía la BD principal (`artisan serve` no pasa `DB_DATABASE` del contenedor), donde los submódulos de category3 no están activos. Con `crmsystem2_nucleo` el menú coincide con `left_navi3` (ver [permisos-promocion.md](permisos-promocion.md)). |
| Pantallas `/admin/promocion/…` | `<title>` sin el nombre del sitio: «クーポン 一覧» frente a «クーポン 一覧 - shop»; igual en «メール 発行条件選択» | Las vistas ponen el título sin `' - ' . $siteName`. |
| `mycoupon/mycoupon_list` | Local añade un desplegable de tienda (desactivado) delante de 「メルマガ配信用」 en ■クーポン 抽出 | Captura: [capturas/promocion-mycoupon_list.png](capturas/promocion-mycoupon_list.png). El listado vacío es por datos. |
| `mail_mag/mail_past` | En local tardó más de 30 s y no llegó a cargar (dos intentos) | Revisar la consulta con la BD del backup (`crmsystem2_nucleo`). |

Cabecera y pie: sin diferencias (salvo サポート, ver README).
