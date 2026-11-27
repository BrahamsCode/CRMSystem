# Diseño S1 — 基本情報管理 (category0)

> Sesión S1 · rama `l13/nucleo` · 2026-11-27. Fuente: `Doc/analisis-legacy/00-nucleo-y-base.md` §2 y §5. Misma BD y misma UI que el legacy (CONVENCIONES §0); contrato del núcleo en `Doc/contratos/NUCLEO.md`.

## 0. Criterios

- **Mismas URL que el legacy** (`/system/category0/config/*.php`, `/system/category0/shop/*.php`) en `routes/legacy/base.php` (nombre `legacy.base.*`): así el menú (`config/menu/base.php`), los enlaces y los JS del legacy no cambian.
- Pantallas en Blade con el HTML del legacy (`layouts.legacy`, menú `base`); controladores en `app/Http/Controllers/Admin/Base/`, validación en `app/Http/Requests/Base/`, lógica en `app/Services/Base/`.
- Los flujos 入力 → 確認 → 登録 → 完了 del legacy se conservan (mismas pantallas); el 確認 recibe el POST y el 登録 guarda (PRG al 完了).
- Los `*_register.php`, AJAX e includes van en la fila de su pantalla.
- Solo `master` ve el módulo (la pestaña 0 se oculta a otros), igual que el legacy; las partes de mantenimiento con `nucleo.maintenance` / `nucleo.shop-create`.

## 1. Tabla de paridad

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador | Tablas | Estado |
|---|---|---|---|---|---|
| `index.php` | 基本情報管理トップ | `legacy.base.index` | `Base\IndexController` | `tb_base_system` | hecho |
| `shop/shop_list.php` | 店舗検索・一覧 | `legacy.base.shops.index` | `Base\ShopController@index` | `tb_shop`, `tb_shop_category` | hecho |
| `shop/shop_detail.php` | 店舗・営業所詳細 | `legacy.base.shops.show` | `Base\ShopController@show` | `tb_shop`, `tb_area`, `tb_shop_category` | hecho |
| `shop/shop_new.php` → `_confirm` → `_register` → `_complete` | 店舗登録情報 | `legacy.base.shops.form/confirm/register/complete` | `Base\ShopController` | `tb_shop`, `hatuban`, `tb_area`, `tb_shop_category`, `admin_master` | hecho |
| `shop/shop_delete_register.php` | 店舗削除 / 取り消し | `legacy.base.shops.delete` | `Base\ShopController@delete` | `tb_shop`, `tb_staff`, `tb_customer` | hecho |
| `shop/shop_category*.php` | 店舗グループ | `legacy.base.shop-categories` | `Base\ShopCategoryController` | `tb_shop_category`, `hatuban` | hecho |
| `config/config.php` → `_confirm` → `_register` | 基本情報確認・変更 | `legacy.base.config.*` | `Base\OfficeController` | `tb_office`, `tb_staff` | hecho (`config_complete.php`: descartado, el flujo del legacy no llega a él) |
| `system/config.php` → `_confirm` → `_register` (primer acceso) | 基本情報登録 | `legacy.base.setup.*` | `Base\OfficeController` (setup) | `tb_office`, `tb_shop`, `tb_staff` | hecho |
| `config/features_config.php` + `ajax_point_yukoukigen_kakunin.php` | 機能設定 | `legacy.base.features.*` | `Base\FeaturesController` | `admin_master`, `contents_master`, `tb_base_system_*`, `tb_customer` | pendiente |
| `config/{point,stamp}_{alert,shikkou}_mail_template_*.php` | 失効予告・失効メールテンプレート | `legacy.base.mail-templates.*` | `Base\ExpiryMailTemplateController` | `admin_master` | pendiente |
| `config/authority.php` + `ajax_group_authority*.php` | 権限管理 | `legacy.base.authority.*` | `Base\AuthorityController` | `tb_staff_group`, `tb_kengen`, `tb_kengen_contents`, `hatuban` | hecho |
| `config/bookmark.php`, `bookmark_list.php`, `ajax_bookmark.php` | ショートカット管理 | `legacy.base.bookmarks.*` | `Base\BookmarkController` | `tb_bookmark`, `tb_base_system*`, `hatuban` | pendiente |
| `config/area*.php` | エリア管理 | `legacy.base.areas.*` | `Base\AreaController` | `tb_area`, `hatuban` | pendiente |
| `config/authority_contents*.php` | 権限項目管理 (mantenimiento) | `legacy.base.authority-contents.*` | `Base\AuthorityContentsController` | `tb_kengen_contents`, `tb_base_system` | pendiente |
| `config/system.php` | システム管理 (mantenimiento) | `legacy.base.system.*` | `Base\SystemController` | `admin_master`, `tb_base_system*`, `tb_hp_*`, `tb_manage_words`, `contents_master` | pendiente |
| `config/slim_list.php` | CRM端末管理 (mantenimiento) | `legacy.base.access-terminals.*` | `Base\AccessTerminalController` | `tb_access_mapping`, `tb_shop`, `hatuban` | pendiente |
| `shop/zip.php`, `config/zip.php` (+ category1/customer, category5/staff, inc) | 郵便番号 | `legacy.nucleo.zip.*` | `Nucleo\ZipController` (común) | `zip` | hecho |
| `config/db_config.php`, `ajax_tb_config.php` | データベース管理 | — | — | — | descartado: editor de BD crudo (usar herramienta de BD) |
| `config/backup.php`, `export_all.php`, `db_export*.php`, `system_file_export.php`, `db_bigint.php` | バックアップ / エクスポート | — | — | — | descartado: `pg_dump` programado |
| `config/db_fix*.php`, `dbphp_fix.php`, `test*.php`, `change_html_*.php`, `create_immovables.php`, `slim_href.php`, `slim_del_href.php`, `upfile_fix.php`, `versionUpAjax.php`, `system_option.php`, `zip.php` | (mantenimiento) | — | — | — | descartado: scripts de inicialización/arreglo |

## 2. Orden

1. 店舗情報管理 (lo usan todos los módulos) y 店舗グループ.
2. 基本情報確認・変更 y el asistente de primer acceso (cierra el pendiente del login).
3. 権限管理 (matriz de cKengen), ショートカット管理, エリア管理.
4. 機能設定 y plantillas de caducidad.
5. Mantenimiento: システム管理, 権限項目管理, CRM端末管理.

## 3. Decisiones

- Validación de los 確認 con `App\Support\LegacyCheck` (mismas reglas y mensajes de `error_check.php`, re-muestra del formulario con `class="Ng"`), no con Form Requests: así la pantalla de error es la del legacy.
- Texto: cada pantalla limpia como su `*_register.php` (`LegacyText::form()` = `get_form` en tiendas; `form2Db` en 店舗グループ).
- 店舗検索: el filtro de メールアドレス busca en `email` (el legacy usaba una columna `mail` inexistente y fallaba); `disp_flg=all` no filtra (el legacy buscaba `disp_flg='all'`).
- 店舗: el alta exige `nucleo.shop-create` y el borrado `nucleo.developer` en el servidor (el legacy solo ocultaba los botones); la edición guarda `update_date` (el legacy lo dejaba vacío).
- Aviso de alta al soporte: `ShopCreatedMail` en cola, destinatario `CRM_NOTIFY_SHOP_CREATED` (por defecto el del legacy).
- Comunes nuevos del núcleo: `Nucleo\AreaTree` (createAreaCheckUi), `LegacyHtml` (tooltip, prefOptions, areaOptions, pager, kensuOptions), `ZipController`.
- `config.php` no copia `tb_office.pass` a la clave de `master` (trampa MANUAL-TESTS #123); en el primer acceso sí (es cuando se fija la clave). Tras el primer acceso se renueva la sesión en vez de volver a entrar con la clave en la URL. 書き直す conserva lo escrito (el legacy releía la BD).
