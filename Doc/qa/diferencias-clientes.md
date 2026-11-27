# Diferencias: 顧客管理 (category1)

Revisión visual S1, 2026-11-27 (método en [README.md](README.md)). Pantallas: portada, `customer_list.php`, `customer_new.php`, `form_config.php`, `customer_group_category_new.php`, `raiten_douki.php`, `rank/customer_rank_list.php`, `search_cate_list.php`, `customer_search.php`, `shukei/shukei_list.php`.

| Pantalla | Qué difiere | Detalle |
|---|---|---|
| Menú lateral (todas) | **ポイント管理** y **ポイント履歴** salen para master; en la muestra no | El legacy los muestra solo si `isExtShopDomain()` (`contents_master` mycolle/app_layout_mode = '1'). El Gate `clientes.ext-shop` es una **condición**, pero no termina en `.enabled`: el `Gate::before` del núcleo deja pasar a master (NUCLEO.md §4, "permisos vs condiciones"). Renombrar a `clientes.ext-shop.enabled` en el Gate y en `config/menu/clientes.php`. |
| `customer/customer_search.php` (来店処理) | 404 en local | Enlace del menú sin pantalla migrada. |
| `shukei/shukei_list.php` (顧客情報集計) | 404 en local | Enlace del menú sin pantalla migrada. |
| `customer_list.php` sin búsqueda | Local pinta la barra **■顧客一覧** vacía debajo del formulario; la muestra no pinta nada hasta buscar | Captura: [capturas/clientes-customer_list.png](capturas/clientes-customer_list.png) (muestra a la izquierda). |

Sin diferencias en cabecera, pie, título ni `<body>` en el resto de pantallas.
