# Revisión visual de punta a punta (paso 7 del plan)

S1, 2026-11-27. Comparación con Playwright entre la muestra (`https://development.crm-s.net`, master, **solo GET**) y la web local (`l13/nucleo` con `laravel13` fusionada, BD `crmsystem2_nucleo` cargada con `scripts/bd-legacy.sh`).

## Método

1. **Menú lateral, cabecera y pie como texto**: un script de Playwright extrae `#header`, `.leftNavi` y `#footer` de cada pantalla en los dos lados (texto de cada enlace, destino, imágenes) y saca un diff por líneas. En los módulos con rutas nuevas (`/admin/<modulo>/…`) se comparan solo las etiquetas, por pares de pantallas equivalentes.
2. **Capturas lado a lado** (muestra a la izquierda, local a la derecha) de una pantalla principal por módulo: `Doc/qa/capturas/`.
3. Los datos de las dos BD no son iguales (el backup local no es la BD de la muestra): las diferencias de contenido, recuentos y submódulos activos solo se anotan cuando no se explican por los datos.

## Diferencias por módulo

| Módulo | Archivo | Resumen |
|---|---|---|
| 顧客管理 (category1) | [diferencias-clientes.md](diferencias-clientes.md) | 2 pantallas sin migrar del menú; ポイント管理/履歴 visibles para master |
| 販売促進管理 (category3) | [diferencias-promocion.md](diferencias-promocion.md) | menú lateral vacío (`config/menu/promocion.php` fuera del contrato); títulos sin nombre del sitio |
| 業務支援管理 (category5) | [diferencias-operaciones.md](diferencias-operaciones.md) | bloque 勤怠管理 ausente (decisión pendiente de Antony) |
| マイページ管理 (category6) | [diferencias-mypage.md](diferencias-mypage.md) | sin diferencias propias |
| 端末管理 (category7) | [diferencias-terminales.md](diferencias-terminales.md) | la muestra pinta `felica/terminal_list.php` con la UI clásica |
| 予約管理 (category9) | [diferencias-reservas.md](diferencias-reservas.md) | falta la agenda `yoyaku/time_table.php` (entrada del módulo) |
| 基本情報管理 (category0) | — | revisado pantalla a pantalla al migrarlo (S1): sin diferencias |

## Núcleo: corregido en esta revisión (`7d54d3a`)

- **Portadas de los módulos**: `/system/category1|3|5|6|7/` (e `index.php`) daban 404. Ahora son del núcleo (`Nucleo\CategoryTopController`): imagen `coverN.gif`, menú del módulo y, en 1 y 3, los contadores de socios de `inc/entry_counter.php`; category9 redirige a `yoyaku/time_table.php`, como el legacy. Título, `<body>`, cabecera y pie iguales a la muestra. Pruebas: `tests/Feature/Nucleo/CategoryTopTest.php`.
- **Clase del `<body>`**: ahora sigue `Base::getSelectCategoryNo` (`/system/select.php` → `pageCategoryselect.php`, antes `pageCategory`). En las URL `/admin/…` usa la categoría del menú, y si el `config/menu` no la declara, la del mapa de portadas (promoción se veía con `pageCategory` y sin la pestaña 販売促進管理 marcada).

## Núcleo: diferencias aceptadas

- **サポート** en la cabecera: solo sale en dominios crm-s (`LegacyUi::isCrmsDomain`), igual que en el legacy; en `localhost` no aparece.
- **Enlace 「ページトップ」 del pie**: apunta a la URL actual; la muestra tiene `/system/category0/` porque Apache añade la barra final, y Laravel sirve `/system/category0`. Mismo destino.
- **Menús de mantenimiento** (権限項目管理, システム管理, CRM端末管理, データベース管理): solo con IP de desarrollo o con login de soporte, como el legacy. Con `artisan serve`, `STAGE` del contenedor no llega a las peticiones web: para verlos en local hay que ponerlo en el `.env` del worktree.
