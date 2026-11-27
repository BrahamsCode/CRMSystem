# 00 — Núcleo compartido y base (login, sesión, permisos, módulos, tiendas, layout)
> Fuente: C:\Users\User\VivaTech\crm2-php8 (rama master = código ya migrado a PHP 8.5.12). Generado el 2026-11-26. Destino: Laravel 13 + PHP 8.5.

Este grupo es la base de todos los demás: quien lo implemente primero desbloquea clientes, promociones, staff y tiendas. Excluidos: `hp/` (obsoleta; solo se menciona donde algo depende de ella), `admin/` (fuera de alcance; allí viven los `ACCESS_LEVEL_*`, ver §3) y `system/category8` (女の子管理, retirado).

## Resumen

| Módulo/submódulo | Carpeta | Nº PHP | Nº vistas reales (pantallas/endpoints) | Complejidad | Puntos (1 ≈ media jornada) |
|---|---|---|---|---|---|
| Autenticación y sesión del personal (login, logout, session, select, config inicial) | `system/*.php` (raíz) | 23 | 9 | L | 8 |
| 基本情報管理 > 基本情報 / 機能設定 / ショートカット / エリア | `system/category0/config/` (parte de usuario) | ~30 | 14 | L | 9 |
| 権限管理 (grupos, 権限項目) | `system/category0/config/` | 6 | 4 | L | 6 |
| システム管理 (activación de módulos), CRM端末管理, DB管理 (solo soporte/IP) | `system/category0/config/` | ~25 (casi todo mantenimiento) | 5 | XL | 8 |
| 店舗情報管理 (店舗・店舗グループ) | `system/category0/shop/` | 11 | 8 | M | 6 |
| Clases, includes y layout comunes | `system/class` (41), `system/inc` (40), `src/*` | ~98 | (fragmentos) | XL | 12 |
| API `tool/api`, CTI `desktop_request`, `contact`, `setting` | `system/tool`, `desktop_request`, `contact`, `setting` | 24 | 10 | M | 5 |
| Infraestructura `public_html/inc` + `.env` | `public_html/inc`, `src/Util` | 8 | 0 | M | 3 |
| **Total** | | ~210 | **~50** | XL | **~57** |

Notas de conteo: de los 65 PHP de `category0` se migran solo ~31 pantallas/endpoints reales. El resto son scripts de mantenimiento o prueba que **no se migran** (`db_fix*`, `dbphp_fix`, `test*`, `change_html_*`, `create_immovables`, `slim_href/slim_del_href`, `upfile_fix`, `versionUpAjax`, `db_bigint`, `system_file_export`, `system_option`, `zip`, `index`).

---

## 1. Autenticación del personal y sesión (`system/*.php`, raíz)

- **Qué hace.** El personal entra con `login_id` + clave en `login.php` (ログイン, "CRM System 管理画面へようこそ / 総合業務管理システム"). Tras entrar va a `select.php` (システム選択: tarjetas de módulos). Si la empresa (`tb_office.kaishamei`) aún no tiene nombre (primer acceso) va a `config.php` (基本情報登録 inicial). Toda página protegida carga `session.php`, que revalida la sesión en cada petición.
- **Hay 3 mecanismos de login en el mismo `login.php`:** (a) normal; (b) usuario `Relation` (clave de soporte validada contra el servidor externo `b.g-api.net/api/support/verifyAccountLogin.php`, o clave de un solo uso de 8 caracteres); (c) usuario `extsupport` (clave de un solo uso de 6 caracteres contra `verifyOnetimePassword.php`). (b) y (c) fuerzan `login_id = master` y marcan la sesión como soporte.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD (L/E) | Notas |
|---|---|---|---|---|---|
| `login.php` | ログイン | alta + registro (POST `act=login`) | Formulario ID/clave + casilla "ログイン情報を保存" (cookie `admin_login[id]`, solo el usuario; antes guardaba la clave en claro, ya eliminado). Valida `tb_staff` con `disp_flg='1'`; compara `login_pass` en claro con la de la fila, o con `RELATION_LOGIN_PASS` / `SUPPORT_MASTER_PASS`. Inserta una fila en `tb_login_rireki` (komento "ログイン") con `hatuban` bloqueado (`FOR UPDATE`). Redirige a `select.php` o `config.php`. Antes: borra archivos de `system/uploads` de más de 1 día (`find … -mmin +1440 -exec rm`), pone `admin_master.base_system_version_flg='2'` si el UA contiene `MTBROWSER`, redirige a HTTPS según `ssl_flg` (`redirectToSslPage`). | L `tb_staff`, `tb_office`, `tb_shop`, `admin_master`; E `tb_login_rireki`, `hatuban`, `admin_master` | **Contiene la rama `activation=1`** (reinicializa la BD con `db_fix.php` y reescribe `inc/db.php`; ver Riesgos). **No migrar esa rama.** Clave en texto plano. Mensajes: `※パスワードが正しくありません。`, `※ご入力のユーザーIDは無効です。` |
| `logout.php` | (sin pantalla) | registro | Inserta `tb_login_rireki` (komento "ログアウト"), vacía `$_SESSION` conservando `$_SESSION['mobile']`, redirige a `login.php`. | E `tb_login_rireki`, `hatuban` | Mismo patrón hatuban |
| `session.php` | — | fragmento (include) | Guardián: sin `$_SESSION['login_id']` → `/system/login.php`. Con él **relee `tb_staff` por `login_id` y `login_pass` de la sesión** (si cambió la clave o desapareció, expulsa), carga `tb_office` y `tb_shop` y rellena la sesión: `login_id`, `login_pass`, `rank` ('master' si `login_id='master'`, si no `tb_staff.rank_pass`), `login_user_name` ('管理者' para master o nombre de la tienda), `tantou`, `user_master_cd`, `shop_cd`, `master_cd`, `kaishamei`, `shop_name`. Luego llama a `csrf_guard()` y crea `$kengen = new cKengen(...)`. | L `tb_staff`, `tb_office`, `tb_shop`, `tb_kengen*` | Casi todas las pantallas lo cargan. Variable `tb_master_cd` "ASP" (multi-inquilino preparado, sin uso) |
| `select.php` | システム選択 | lista | Inicio tras el login: una tarjeta por módulo (`tb_base_system.system_flg='1'`, ordenado por `narabi`), enlace `categoryN/`. Oculta módulo 0 si no es master, oculta módulo 8, oculta `SNS管理` si no tiene `kengen[1034][1]`. Añade enlace サポート si el dominio es `*.crm-s.*`. **Efectos secundarios en cada carga**: normaliza `tb_customer.kojin_birth`, inserta `tb_koumoku` cd=185 (`my_colle_use`) y `tb_kengen_contents` 1032–1036 si faltan, y sincroniza con los servidores POS de cada tienda (`pg_connect` a `tb_shop.pos_domain`; tablas `tb_staff`, `tb_customer`, `tb_customer_sub`, `tb_raiten_douki_master`, `tb_customer_group` con `db_sync_all/db_sync_all2`). | L `tb_base_system`, `admin_master`, `tb_shop`, `tb_kengen*`; E `tb_customer`, `tb_koumoku`, `tb_kengen_contents` + tablas POS remotas | Es el システム選択 que CRMSystem ya replica (sin efectos secundarios) |
| `config.php`, `config_confirm.php`, `config_register.php`, `config_complete.php` | 基本情報登録 | alta / confirmación / registro / detalle | Asistente inicial (primer login): empresa (`tb_office`), tienda principal (`tb_shop`) y usuario `master` (`tb_staff`, `rank_pass='4,4,…'`, clave tomada del formulario). Al final vuelve a `login.php?act=login&admin_id=master&admin_pass=…` por meta-refresh. | E `tb_office`, `tb_shop`, `tb_staff` | La clave viaja en la URL de redirección. Reemplazar por seeder + asistente de instalación. Duplicado de `category0/config/config*.php` |
| `pos_session.php` | (terminal) | fragmento (include) | Autentica **terminales POS/tablet**, no personas: por `?unique_id` + `unique_key = md5(id.'key')`, o `$_SESSION['terminal_id']`, o `User-Agent: CRMPOS-<id>`. Busca `tb_pos_terminal.terminal_no`, fija `shop_cd`, `terminal_cd`, `terminal_printer_cd`, `terminal_posno`, y crea `tb_c2_shopping` de la tienda si falta. Si no existe: "端末が認証されていません。" | L `tb_pos_terminal`, `tb_shop`; E `tb_c2_shopping`, `hatuban` | Sin secreto real (ver INTEGRATIONS.md). Cliente fuera del repo |
| `terminal_login.php` | (redirige) | fragmento | Parsea el UA (`cUserAgent`), carga `pos_session.php` y redirige a `category11/terminal.php`. | — | Depende del módulo 11 (予約) |
| `index.php`, `s1.php` | — | config | `index.php`: 7 líneas, portada vacía. `s1.php`: exige sesión y fuerza `pit_coupon.flg2='2'` para `coupon_cd=12` (parche suelto). | E `pit_coupon` | Descartar `s1.php` |
| `mail.php`, `mail_send.php` | メール送信 - GLORY | alta / registro | Herramienta de prueba de correo, **solo `$is_develop`** (IP de desarrollo). | — | No migrar; usar Mailpit/tinker |
| `news.php` | (お知らせ) | detalle | Lee un RSS externo (`vizan.azas24.com/hp/rss.xml`) y lo muestra. | — | Servidor externo, ¿vivo? Depende de la carpeta `hp/` del servidor remoto |
| `local_settings.php` | 設定 | config | Elige ubicación de BD local/web, escribe un archivo de ajustes con `cUtility::setLocalSettings` y cierra sesión. | archivo | Residuo de la versión instalada en PC. No migrar |
| `get_touroku_data.php`, `get_shukei_data.php`, `get_shukei_data2.php` | — | ajax/API sin auth | Devuelven datos de altas, mailmag y verificación de operaciones (`tb_crm_check_operation*`, `tb_login_rireki`). Ningún archivo del repo los llama. | L `tb_customer`, `tb_shop`, `tb_login_rireki`, `tb_staff`, `tb_hp_domain`, `tb_mailmag_analyze`, `tb_mycoupon_master` | **Sin autenticación**. Probable monitor de otro servidor |
| `create_customer.php`, `create_shop.php` | — | — | Generadores de datos de prueba que sobrescriben datos reales (INTEGRATIONS.md). | — | **No migrar.** |
| `zip.php`, `aterna_a4_12.php` | 郵便番号 / A4 | ajax / pdf | `zip.php` busca dirección por código postal; `aterna_a4_12.php` es una plantilla de impresión A4. | — | CRMSystem ya tiene `PostalCodeController` |

**Flujo principal.** `login.php` → (si no hay empresa) `config.php` → `config_confirm.php` → `config_register.php` → login automático → `select.php` → `categoryN/index.php` (cada módulo carga su `left_navi*.php`). `logout.php` limpia y vuelve a `login.php`.

**Reglas no obvias.**
- `rank = 'master'` es el superusuario (`login_id` literal `master`). Para los demás `rank` es la cadena `tb_staff.rank_pass` (6 bloques de 10 banderas separados por `<>`, formato antiguo que solo escriben `category5/staff/staff_entry_register.php` y el asistente de `config_register.php`). El control real usa `tb_kengen` (§3).
- Soporte: `support_login` (entró con `RELATION_LOGIN_PASS` o `SUPPORT_MASTER_PASS`), `support_master` (solo `SUPPORT_MASTER_PASS`), `relation_login` (login vía `Relation`) condicionan los menús de mantenimiento. `seller_id` (`admin_master.seller_id`) cambia el logo del login (1 = CraftFix, otro = exlink).
- **`admin_master.cd = 1`** es la fila única de configuración global (versión de UI `base_system_version_flg`, `application_use_flg`, `push_api_host`, `beacon_use_flg`, `ssl_flg`, `domain`, `system_mail`, `related_features`, `seller_id`…). **`tb_office.cd = 1`** es la fila de la empresa.
- Todas las consultas concatenan SQL con `pg_escape_string` (no hay sentencias preparadas).

**Servicios viejos → reemplazo Laravel 13.**

| Legacy | Reemplazo |
|---|---|
| `$_SESSION` plano + `session.php` en cada página | Guard `auth:staff` con `Authenticatable` sobre `tb_staff`; middleware `auth` + `staff.active` (relee `disp_flg`) |
| Clave en texto plano / `md5(login_pass)` en enlaces | `password_hash` (cast `hashed`) con re-hash al primer login correcto; no usar la clave en URLs |
| "Remember me" por cookie con el id | Fortify `remember` |
| Soporte `Relation`/`extsupport` contra `b.g-api.net` | Gate `support` + servicio `SupportLoginService` con `Http::` y `config('crm.support')`; **decisión pendiente** |
| `tb_login_rireki` con hatuban `FOR UPDATE` | Tabla `login_logs` (autoincrement) escrita por listeners de los eventos `Login`/`Logout` |
| Borrado de `system/uploads` en cada login | Scheduler (limpieza de `Storage` temporal) |
| `redirectToSslPage()` según `ssl_flg` | `URL::forceScheme('https')` / proxy |
| Sync con servidores POS al abrir `select.php` | **Sacarlo del login**; job en cola/Scheduler si el POS sigue vivo |

---

## 2. 基本情報管理 (`system/category0/config/`)

Menú lateral del módulo 0 (`left_navi0.php`), en orden: 基本情報確認・変更, 機能設定, 権限管理, ショートカット管理, [IP/soporte: 権限項目管理, システム管理, CRM端末管理, データベース管理], エリア管理. Solo accesible para `master` (la tarjeta del módulo 0 no se muestra a otros rangos). Apoyo: MANUAL-TESTS §10, filas 123–127.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD | Notas |
|---|---|---|---|---|---|
| `config.php` → `config_confirm.php` → `config_register.php` → `config_complete.php` | 基本情報確認・変更 / 基本情報登録 | edición / confirmación / registro / detalle | Edita `tb_office` (empresa, dirección, tel, mail, logo, clave `pass`), propaga a `tb_shop` y a `tb_staff` (`master`). Botón 確認 → 登録. | L/E `tb_office`, `tb_shop`, `tb_staff` | **Trampa (MANUAL-TESTS #123)**: el formulario trae `tb_office.pass` y al guardar la escribe como clave del usuario `master`; si difieren, se pierden las herramientas. No replicar esa fusión |
| `features_config.php` | 機能設定 | config (13+ formularios independientes) | Un solo archivo con formularios `act` distintos: `change_id_raiten_type`, `change_id_smtp` (SMTP propio), `change_mail_footer`, `change_mail_send_filter`, `change_name_input_mode_flg`, `change_reserve_button_position`, `change_sended_log`, `change_shop_category_flg`, `change_shuki_limit`, `change_terminal_logout_time`, `change_terminal_sound` (`sound_select`), `disabled_push_title_flg`, `mail_send_status`, `mobileshop_auth_passcode`, `privacy_policy_flg`, **`reset_error_send_cnt`** (pone `tb_customer.error_send_cnt = 0` a todos), fechas de punto/vigencia y un bloque solo IP. | L/E `admin_master`, `tb_base_system_child`, `tb_base_system_grandchild`, `tb_customer`, `tb_shop`, `contents_master` | Las banderas viven en `contents_master` (category/field/text_data/int_data) y `admin_master`. Convertir a tabla `settings` clave/valor (por tienda cuando aplique). MANUAL-TESTS #124 |
| `ajax_point_yukoukigen_kakunin.php` | — | ajax | Calcula cuántos clientes caducan con una nueva vigencia de puntos. | L `admin_master`, `tb_customer` | |
| `point_alert_mail_template_entry/register.php`, `point_shikkou_mail_template_entry/register.php`, `stamp_alert_mail_template_entry/register.php`, `stamp_shikkou_mail_template_entry/register.php` | ポイント/スタンプ 失効予告・失効 メールテンプレート | edición + registro (4 pares) | Plantillas del aviso previo y de la ejecución de caducidad de puntos y sellos; se abren desde `features_config`. | L/E `admin_master` | Funcionalmente pertenecen a promociones; compartido |
| `authority.php` | 権限管理 | edición | Elige un **grupo de personal** (`tb_staff_group`, select "▼グループ選択") y marca por 権限コンテンツ las 4 casillas 閲覧/新規/編集/削除 (`etsuran_flg`, `shinki_flg`, `shusei_flg`, `sakujo_flg`). La matriz se carga por AJAX. | L `tb_staff_group` | §3 |
| `ajax_group_authority.php` | — | ajax | Devuelve la matriz del grupo agrupada por `tb_base_system`. | L `tb_kengen`, `tb_kengen_contents`, `tb_base_system`, `tb_staff`, `tb_shop` | |
| `ajax_group_authority_register.php` | — | registro (POST) | `act=update`: `LOCK TABLE tb_kengen_contents, tb_kengen`; para cada contenido visible actualiza o inserta la fila de `tb_kengen` (nuevo `cd` con `cHatuban::set_hatuban('tb_kengen')`). | L/E `tb_kengen`, `tb_kengen_contents`, `hatuban` | |
| `authority_contents_list.php`, `authority_contents.php`, `ajax_authority_contents_register.php` | 権限項目管理 | lista / alta-edición / ajax | CRUD del catálogo **権限コンテンツ** (cd, nombre, `tb_base_system_cd`, comentario con la descripción de las 4 funciones, `hyouji_flg`). Solo `$is_develop` o soporte (no `relation_login`). | L/E `tb_kengen_contents`, `tb_base_system` | MANUAL-TESTS #125: avisos en alta y lista. Es catálogo de desarrollo, no de usuarios |
| `bookmark.php`, `bookmark_list.php`, `ajax_bookmark.php` | ショートカット管理 | edición / lista / ajax | Elige qué opciones del menú (nodos de `tb_base_system_child`/`grandchild`) aparecen como accesos directos en la cabecera (`tb_bookmark`: `bookmark_flg`, `user_rank`, `tb_kengen_contents_cd` + `tb_kengen_contents_node`). Botón `reset` repuebla 63 filas. | L/E `tb_bookmark`, `tb_base_system*`, `hatuban` | `header.php` los pinta si `kengen[cd][nodo]==1` o master |
| `area.php`, `area_edit.php`, `area_confirm.php`, `area_register.php` | エリア管理 | lista / edición / confirmación / registro | Árbol de áreas (padre-hijo, orden con flechas, borrado). La clase `Area.php` (`createAreaCheckUi`) lo usa también como selector de tiendas por área. | L/E `tb_area`, `hatuban` | MANUAL-TESTS #127: avisos `$mail_error` y `$cateDel_error` en cada confirmación |
| `system.php` | システム管理 | config (solo IP/soporte) | **Activación de módulos**: listas de casillas padre/hijo/nieto → `UPDATE tb_base_system / _child / _grandchild SET system_flg='1'|'0' WHERE cd=…`. Más: dominios HP (`tb_hp_domain`, `tb_hp_data`), funciones relacionadas (`admin_master.related_features`), `pos_koubai_tb_search_form_cd`, 文言 (`tb_manage_words`), `shiire_ya_conf`, `base_system_version`, `application_use_flg` + `push_api_host`, `beacon_use_flg`, `ssl_flg`, `smatopi_environment`, `app_layout_mode`, `karute_time_disp_flg`, `atokara_disp_flg` (en `contents_master`). `act=init_all` reinicializa la BD (no se pulsa) y hay sincronización de BD/archivos con otro servidor. | L/E `admin_master`, `tb_base_system*`, `tb_hp_*`, `tb_manage_words`, `tb_package_master`, `tb_access_mapping`, `contents_master` | **Bug ya corregido en la migración PHP 8 (2026-11-13)**: sin `cd` en el formulario desactivaba 基本情報管理; ahora, sin `cd`, no actualiza (hijos y nietos igual) |
| `slim_list.php` | CRM端末管理 | lista / edición (IP/soporte) | Registro de terminales/PC que acceden (mapeo de acceso). | L/E `tb_access_mapping`, `tb_shop`, `hatuban` | `slim_href` y `slim_del_href` son sus enlaces; no migrar |
| `db_config.php` (+ `ajax_tb_config.php`) | データベース管理 | config (IP) | Elige una tabla y edita filas crudas; `ajax_tb_config.php` lista columnas de `pg_attribute` (valida el nombre con regex). Panel de administración de BD, no de negocio. | L/E tablas arbitrarias, `hatuban` | **No migrar**; usar herramienta de BD/Filament |
| `backup.php`, `export_all.php`, `db_export.php`, `db_export2.php`, `db_export_another.php`, `system_file_export.php`, `db_bigint.php` | バックアップ / DBエクスポート | csv / config (IP) | Volcados de BD/archivos y exportación a otros servidores. `backup.php` pide `BACKUP_PASS`. | L `tb_customer`, `tb_customer_sub`, `tb_package_master`… | **No migrar**: `pg_dump` programado + backup de Laravel |
| `db_fix.php`, `db_fix_light.php`, `dbphp_fix.php`, `test_fix.php`, `test.php`, `test/test.php`, `change_html_initialize.php`, `change_html_juku.php`, `create_immovables.php`, `slim_href.php`, `slim_del_href.php`, `upfile_fix.php`, `versionUpAjax.php`, `system_option.php`, `zip.php` | (mantenimiento) | cron/CLI / config | Scripts de inicialización y arreglo de BD. `db_fix.php` (3355 líneas) **crea ~120 tablas**; `test_fix.php` rehace `tb_base_system_child` sin condición; `upfile_fix.php` borra `hp/uploads`. | muchas | **No migrar.** `db_fix.php` sirve como inventario de tablas y se sustituye por migraciones. Retirados en 403 por web desde 2026-11-16: `test_fix.php` |

**Flujo principal.** `features_config` y `system.php`: cada bloque guarda con su propio POST (sin pantalla de confirmación) y recarga. `authority.php`: elegir grupo → AJAX carga la matriz → marcar → AJAX registra con bloqueo de tabla.

**Reglas de negocio no obvias.**
- `authority.php` guarda todas las filas de `tb_kengen_contents` con `hyouji_flg='1'`; lo no marcado se guarda como cadena vacía (= sin permiso).
- `reset_error_send_cnt` actualiza TODOS los clientes (22 325 en la BD de prueba, incluidos los `NULL`).
- `app_layout_mode` (`contents_master`, `category='mycolle'`, `field='app_layout_mode'`) activa el modo "tienda en app": `Base::isExtShopDomain()` lo lee y condiciona los menús de ポイント管理, ポイント履歴, `base_point`, `csv_grant_points`, `push_history`.
- `application_use_flg=1` cambia los textos "メルマガ" → "お知らせ" y hace que guardar una tienda llame a `b.g-api.net`.
- `base_system_version_flg='2'` activa la UI nueva (CSS V2, vRippleEffect, módulo SNS).

---

## 3. Permisos y niveles (`cKengen`, `rank`, `ACCESS_LEVEL_*`)

**Modelo real del CRM de personal (`system/`): 4 capas, no una.**

1. **Superusuario**: `$_SESSION['rank'] == 'master'` ve y hace todo (en `cKengen::creatKaiinForm` fuerza 1/1/1/1).
2. **Matriz 権限コンテンツ × 4 funciones** (`class/cKengen.php`), calculada al cargar `session.php`:
   - `tb_kengen` (por `tb_staff_group_cd` × `tb_kengen_contents_cd`: `etsuran_flg`, `shinki_flg`, `shusei_flg`, `sakujo_flg`), unido con `tb_staff.tb_staff_group_cd`; solo grupos con `tb_staff_group.disp_flg='1'` y contenidos con `hyouji_flg='1'`.
   - Se expone como `$kengen->myKengen[cd_contenido][1..4]` = 閲覧 / 新規 / 編集 / 削除. En contenidos que no son de datos, 1–4 son "機能1–4" (p. ej. 1034 SNS: función 1 = configurar).
   - **Si el staff no tiene ninguna fila, se le dan todos los permisos** (fallback abierto sobre `tb_kengen_contents`). Cuidado: un usuario sin grupo es administrador de hecho.
   - `cKengen` además genera formularios con campos ocultos (`******` sin 閲覧) o `readonly` (sin 新規/編集) y trae listas auxiliares (prefecturas, oficios, etc.).
3. **Banderas de soporte**: `support_login`, `support_master`, `relation_login` (sesión) y el eje de IP de desarrollo (§7). Controlan los menús de mantenimiento (権限項目管理, システム管理, CRM端末管理, データベース管理, 店舗新規登録).
4. **Activación por módulo y por banderas de BD**: `tb_base_system*.system_flg`, `admin_master.*_use_flg`, `contents_master.app_layout_mode` (§4).

Contenidos con `cd` fijo usados por código: `1001` (顧客; nuevo = `[1001][2]`), `1033` (売上管理: 1 閲覧, 2 新規, 3 編集, 4 削除), `1034` (SNS管理), `1032` マイフリーページ, `1035` マイコレ, `1036` フォトギャラリー. `select.php` inserta 1032–1036 si faltan. El resto del catálogo lo crea `db_fix.php`.

**`ACCESS_LEVEL_*`** (DEVELOP=1, MASTER=100, SHOP=200, con `$_SESSION['login_level']`) **no pertenecen al CRM de personal**: solo existen en `admin/inc/config.php` y `admin/inc/*` (panel `admin/`, fuera de alcance). En `system/` no hay equivalente; su análogo es `rank='master'` / `support_master` / usuario de tienda. Solo si se migra `admin/` harán falta roles `developer` / `master` / `shop`.

**Propuesta Laravel.** Policies/Gates por recurso (`customers.view|create|update|delete`): cada `tb_kengen_contents.cd` es un `Permission` (o spatie/laravel-permission: `staff_group` = Role, 4 permisos por contenido), con `Gate::before(fn ($u) => $u->is_master ? true : null)`. Cambiar el fallback abierto a *deny-by-default* (cambio de comportamiento, **decisión pendiente de Antony**).

---

## 4. Activación de módulos y menú lateral

**Tablas de activación (3 niveles):**
- `tb_base_system` (módulo padre, `cd` 0–12; `system_name`, `system_flg` '1' = activo, `narabi` = orden). Códigos que el código usa: 0 基本情報管理, 1 顧客情報管理, 2 商品情報管理, 3 販促 (メルマガ, クーポン, スタンプ…), 4 PCHP管理, 5 スタッフ/勤怠, 6 マイページ/マイコレ/SNS-HP, 7 ビーコン/ムービー, 8 女の子管理 (**retirado, nunca se muestra**), 9 y 11 予約, 12 SNS管理. Los nombres exactos viven en la BD (`db_fix.php`).
- `tb_base_system_child` (opciones del menú de cada módulo: `tb_base_system_cd`, `system_flg`, `rank`, `cd`, `system_name`).
- `tb_base_system_grandchild` (sub-opciones; p. ej. `顧客ランク%` activa el menú de ランク).
- Se administran en `category0/config/system.php` (solo soporte/IP). `BaseSystem::fetchBaseSystem($crmVersion)` además oculta `SNS管理` si `base_system_version_flg != '2'`; `fetchBaseSystemChild($cd)` ordena por `rank, cd`.

**Selección de módulo (`select.php` y cabecera `header.php`):** misma regla en ambos. Módulo `cd=0` solo master; `cd=8` oculto; `SNS管理` solo con `kengen[1034][1]=1` o master; se añaden los accesos directos `tb_bookmark` (si `kengen[cd][nodo]==1`) y enlaces externos (仕入れ屋 desde `contents_master`, サポート si el dominio es `crm-s`).

**Menú lateral (`system/inc/left_navi{0..8,12}.php`, `src/Layout/LeftNavi.php`, `BaseFrame`):**
- El módulo actual se deduce de la URL (`getSelectCategoryNo`: segmento `categoryN`) y se incluye `inc/left_navi<N>.php`. Los enlaces están **escritos a mano en PHP** y condicionados por: `tb_base_system_child` activo (`left_navi1` lee `tb_base_system_child where tb_base_system_cd=1 and system_flg='1'`), permisos `kengen`, `rank=='master'`, banderas de soporte/IP, `application_use_flg`, `app_layout_mode`, `beacon_use_flg`, y el servidor (`left_navi5`: 帳票管理 y シフト solo si `SERVER_ADDR` es el servidor 202.218.x.x).
- `src/Layout/LeftNavi.php` es una reescritura parcial (solo 商品情報管理: 商品検索・一覧 / 商品新規登録); no sustituye a los `left_navi*.php`.
- Cada bloque es un `<dl class="ac1 accordionBlock">` (acordeón jQuery) con imagen `ln_tN_M.gif` generada por `createHtmlPart('leftNavi', …)`.

**Menú de 基本情報管理** (para replicar): 基本情報確認・変更 / 機能設定 / 権限管理 / ショートカット管理 / [soporte+IP: 権限項目管理, システム管理, CRM端末管理, データベース管理] / エリア管理. Bloque 店舗情報管理: [IP o `support_master`: 店舗新規登録] / 店舗検索・一覧 / 店舗グループ.

**Propuesta Laravel.** Tabla `modules` (key, name, order, active, parent_id, route, permission, feature_flag) cargada en un `ViewComposer` + `<x-layouts.modulo>` (CRMSystem ya lo tiene; `config/crm.php → modulos`). Visibilidad en un `MenuBuilder` que combine `Gate`, `Setting::flag()` y la regla de IP; sin repetir `if` por enlace.

---

## 5. 店舗情報管理 (`system/category0/shop/`) y tiendas (`tb_shop`)

Alta, búsqueda, edición, baja lógica y recuperación de tiendas, y agrupación en 店舗グループ. Apoyo: MANUAL-TESTS #128 y #129.

| Archivo | Título (ja) | Tipo | Qué hace / acciones y botones | Tablas BD | Notas |
|---|---|---|---|---|---|
| `shop_list.php` | 店舗一覧 / 店舗検索・一覧 | lista | Buscador y lista paginada (`class/Shop.php`: `getListSQL/getList`), filtro por grupo; con IP muestra 検索対象 (有効/削除済/全て). | L `tb_shop`, `tb_shop_category` | Opción "全て" rota (busca `disp_flg='all'`, BROKEN-FILES.md). `$page_control` sin resultados |
| `shop_detail.php` | 店舗・営業所詳細 | detalle / edición | Detalle de la tienda; botones 編集, 削除, 削除を取り消す (estos dos solo con IP). | L `tb_shop`, `tb_area`, `tb_shop_category`, `admin_master`, `tb_base_system_grandchild` | |
| `shop_new.php` → `shop_new_confirm.php` → `shop_new_register.php` → `shop_new_complete.php` | 店舗登録情報 | alta / confirmación / registro / detalle | Alta y edición con el mismo flujo (`act=new|edit`): nombre (`kaishamei`), dirección con `zip.php`, tel, mail, `mycolle_tel`, área (`tb_area`), grupo (`tb_shop_category`), `pos_domain`, URL… | L/E `tb_shop`, `hatuban`, `admin_master`, `tb_area`, `tb_shop_category` | **Nuevo `cd` = `hatuban.kazu` de `tb_shop`**: `INSERT INTO tb_shop(cd)` y luego `UPDATE`. Si `admin_master.application_use_flg='1'` llama a `b.g-api.net/api/mpa/setStoreTel` (o `b-develop.g-api.net` si `push_api_host=0`). Envía correo de aviso al soporte del proveedor (en el alta). **El alta solo está disponible con IP de desarrollo o `support_master`.** Al editar guarda `update_date` vacío (igual en master) |
| `shop_delete_register.php` | 店舗登録情報 | borrado / recuperar | **Borrado lógico**: `disp_flg='9'` en `tb_shop`; mueve `tb_staff.shop_cd` y `tb_customer.tb_shop_cd` a la tienda `1`; recuperar pone `disp_flg='1'`. | E `tb_shop`, `tb_staff`, `tb_customer` | Las reasignaciones no se deshacen al recuperar. `shop_cd=1` es la tienda "casa central" |
| `shop_category.php`, `shop_category_frm.php`, `shop_category_register.php` | 店舗グループ | lista + alta + edición + borrado | CRUD en una sola página con flechas de orden. | L/E `tb_shop_category`, `hatuban` | MANUAL-TESTS #129 OK |
| `zip.php`, `index.php` | — | ajax / portada | Código postal; portada del módulo 0 (lee `tb_base_system`). | L `tb_base_system` | |

**Reglas de negocio.** `tb_shop.disp_flg`: `'1'` activo, `'9'` borrado lógico. `tb_shop.master_cd` (oficina padre) se copia a la sesión. `tb_shop.pos_domain` (`{x}.pos-s.net`, escrito por `tool/api/category0/setPosdomain.php`) y `pos_package` enlazan con la BD POS remota. Cada tienda necesita una fila `tb_c2_shopping` con `tax=5` (se crea al conectar el primer terminal). `getShopNameFromCd` y `make_shop_options` (`inc/function.php`) alimentan todos los selectores de tienda.

**Admin master (`admin_master`).** Tabla de **una sola fila (`cd=1`)** con la configuración global (§1). No tiene CRUD propio: se edita desde `features_config.php` y `system.php`. La empresa es `tb_office cd=1`. No hay multiempresa real: `tb_master_cd` y `__ADMIN_CD` están preparados para ASP, sin uso.

**Servicios viejos → Laravel.** `b.g-api.net` → `Http::timeout(3)->get()` en un `ShopObserver::saved` encolado (solo si `config('crm.application_use')`). Aviso por correo → `Mailable ShopCreated` en cola (destinatario en `config/crm.php`, no en código). Borrado lógico → `SoftDeletes` (el `BaseModel` de CRMSystem ya lo hace).

---

## 6. Numeración `hatuban`

- Tabla `hatuban(koumoku, kazu)`: **un contador por tabla** (`koumoku` = nombre de la tabla: `tb_customer`, `tb_shop`, `tb_staff`, `tb_login_rireki`, `tb_kengen`, `tb_c2_shopping`…). `kazu` es el **próximo** número libre.
- `class/cHatuban.php`: `view_hatuban($tabla)` devuelve el siguiente sin consumirlo; `set_hatuban($tabla)` devuelve el siguiente y suma 1; si no existe la fila la crea (empieza en 1). Patrón dominante: `BEGIN; SELECT … FOR UPDATE; UPDATE kazu+1; INSERT (cd); UPDATE …; COMMIT`.
- Los `cd` no son `SERIAL` de PostgreSQL. Aparte, `Customer::createID()` (`src/Customer`) genera el id de cliente visible, y `Common::createHatsuban($shopCd)` (`time` + tienda 3 dígitos + `rand` 6 dígitos) genera un código único entre servidores.
- `create_customer.php` y `create_shop.php` dejan `hatuban.kazu` en 10001 / 101: riesgo de colisión.
- **Propuesta Laravel:** `id` autoincrement + `uid` (CRMSystem ya usa `HasUid`); al importar datos legacy conservar el `cd` como `legacy_cd` y reajustar la secuencia (`setval`) a `hatuban.kazu`. No reimplementar `cHatuban`.

## 7. Restricción por IP y `DEVELOPMENT_IPS`

- `Common::isDevelopmentAuthorityFromIP($ip, $stage)` (`src/Util/Common.php`): `true` si `STAGE='docker'` (siempre) o si `REMOTE_ADDR` está en `Constant::DEVELOPMENT_IPS` (hoy una sola IP pública; no se copia aquí). Usa `REMOTE_ADDR` (no `X-Forwarded-For`): detrás de un proxy verá la IP del proxy.
- Se usa en 18 archivos: `left_navi0/1`, bloque マスタ管理 de `left_navi4`, `mail.php` y `mail_send.php` (completas), partes de `system.php`, `features_config`, `db_config`, `backup`, `export_all`, `db_export2`, `shop_list`, `shop_detail`, `stamp_config` y `kiosk/display_*`. Varias partes también se abren con soporte.
- Otros controles: `db_bigint.php` (3 rangos propios), `left_navi5.php` (`SERVER_ADDR`, IP del servidor), `admin/inc/common.php` (fuera de alcance), `STAGE` en `.env` (**debe quedar vacío en producción**).
- **Propuesta Laravel:** middleware `EnsureDeveloperAccess` (IP en `config('crm.developer_ips')`, variable `CRM_DEVELOPER_IPS`, o rol `developer`) y `TrustProxies` configurado. Mejor sustituir la IP por el rol `support_master` y dejar la IP como segunda barrera.

## 8. Layout común

- `inc/head.php` (143 líneas): `<head>` común, jQuery (varias versiones en `system/js`: 1.3.2, 1.4.4, 1.11.2, 2.1.1; jQuery UI 1.7/1.10/1.11), CSS según `base_system_version_flg`. `inc/header.php` (147): cabecera con nombre de tienda, usuario, accesos `tb_bookmark`, selector de módulos y enlaces externos. `inc/footer.php`. `inc/left_navi*.php` (menús). `inc/kihon.php` (889): constantes y utilidades (UA/tablet con `cUserAgent`, `checkApplicationUseFlg`, `getMailmagazineName`, `change_exif_orientation` con Imagick, `file_download`…). `inc/function.php` (2822): ~100 helpers (`make_*_options`, `fnc_make_array`, `createMailFooter`, `manageWord`, `karteToolCheck`, `redirectToSslPage`, `db_sync_all/all2`…). `inc/error_check.php`, `inc/search_*_set*.php` (buscadores reutilizables), `inc/graph_bar/pie.php`, `inc/ac*.php`, `inc/tooltip*.php`, `inc/ajax_pdfupload/picupload.php`, `inc/cal.php`.
- `src/Layout/BaseFrame.php` (419): versión OO de `head`, `importJsCss(V2)`, `createHtmlHeader`, `createFooter`, `createHtmlHeaderPart` (partes `functionSelect`, `leftNavi`, `headerShiire`, `headerSupport`). `src/Layout/Pagenation.php`: paginador. `src/Mypage/OutputHtml.php` (`normalizeAriticleLink`). `src/Validator/Validator.php` (`item_edit_validate`, solo 商品). `src/Customer/Customer.php` (`fetchList`, `findFromId`, `createID`). `src/Sales/SalesItem.php` (lista de ítems con búsqueda múltiple y `makeArrAvailableShopCds`). `src/MobileShop/*` (clientes de tienda móvil: API externa `extlink` con token y estado de pago). `src/SystemCore/BaseSystem.php` (módulos/hijos/versión). `src/Util/*`: `Common`, `Constant`, `DbConnect`, `Domain`, `Log`, `Slack`, `JwtAuth` (`keys/public.pem`, RS256, API móvil) e `Initialize` (2737 líneas con el SQL de creación/datos iniciales).
- **Propuesta Laravel:** layout Blade (`<x-layouts.modulo>` ya existe), `Paginator` nativo, `FormRequest` en lugar de `Validator`, Eloquent en lugar de `fetchList`, `Cache` para los módulos activos. Eliminar jQuery viejo; Vite + stack de frontend elegido por el equipo.

## 9. Clases e includes (`system/class` 41, `system/inc` 40)

| Archivo/s | Qué hace | Destino |
|---|---|---|
| `class/cKengen.php` | Matriz de permisos + helpers de formulario (`creatKaiinForm`) | Policies/Gates (§3) |
| `class/cHatuban.php` | Contadores `hatuban` | Descartar (autoincrement) |
| `class/Base.php` | Head, partes HTML, `setBaseSystem` (marcada obsoleta por `BaseSystem`), `isExtShopDomain` | `Setting::flag()` + componentes Blade |
| `class/Shop.php`, `Staff.php`, `Area.php`, `cCustomer.php`, `cRaiten.php` | Consultas de lista con filtros (`getListSQL/getList`) | Modelos + scopes |
| `class/cUserAgent.php` (378) | Parsea UA: `UNIQUE_ID` (`CRMPOS-…`), `DEVICE_NAME`, tablets (`CRMRW107`, `CRMH580VT_A`), `MTBROWSER` | Servicio de terminal, o descartar si se retiran las apps |
| `class/cUtility.php`, `cMobileViewPort.php` | Utilidades, ajustes locales, viewport móvil | Descartar / helpers |
| `class/Log.php`, `Logger.php` | Logs a archivo | `Log::channel()` |
| `class/Point.php` (761), `cStamp.php`, `cKujibiki.php`, `cOshirase*.php`, `cMailsoushin.php`, `Push.php`, `ApricotPush.php`, `Apricot.php`, `Smatopi.php` (+ claves públicas `smatopi_*_public.key`), `SNS.php`, `ReceiptMailDecoder`, `Pop3Simple` | Puntos, sellos, sorteos, envío masivo, push, SNS | **Otros grupos** (promociones, POS, SNS) |
| `class/cYoyaku*`, `maClass_sysParts*`, `orClass_*` | Reservas, partes HTML, tienda y actualizaciones de versión | Otros grupos |
| `class/lib/` | Librerías de terceros embebidas | `composer` |
| `inc/search_*`, `staff_search_*`, `check_select.php`, `staff_check_select.php`, `error_check.php`, `entry_counter.php`, `html_customize.php`, `ac.php`, `ac_2.php`, `tooltip*.php`, `graph_*`, `cal.php`, `zip.php`, `path_config.php`, `ajax_*upload.php`, `ajax_createPosMenuForm.php` | Buscadores avanzados, validación y utilidades de página | `FormRequest`, componentes Blade, query builder |

## 10. API `system/tool/api`, CTI, contacto, setting

| Archivo | Tipo | Qué hace | Tablas | Notas |
|---|---|---|---|---|
| `tool/api/checkKey.php` | fragmento | Compara `key` con `CRM_API_KEY` (`hash_equals`); vacía o ausente → no responde. Antes: `md5("VIZAN".host."GLORY")`. | — | Desplegar con la clave antigua en la fase 1 (INTEGRATIONS.md) |
| `tool/api/category0/setPosdomain.php` | ajax | `shop`, `domain`, `package` → `tb_shop.pos_domain = "{domain}.pos-s.net"` y `pos_package`. | E `tb_shop` | |
| `tool/api/category1/searchCustomerList.php`, `getCustomerDetail.php`, `getRaitenList.php`, `getRaitenDetail.php`, `getSearchCateFormData.php`, `addRaitenRecord.php`, `setRaitenDetail.php` | ajax (JSON/JSONP) | Búsqueda de clientes, detalle, lista y registro de 来店 (visitas); `setRaitenDetail` (708 líneas) maneja puntos/sellos y `hatuban`. | L/E `tb_customer(_sub)`, `tb_raiten_rireki`, `tb_point_log`, `tb_stamp_log`, `tb_search_cate/form`, `tb_staff`, `tb_shop`, `admin_master` | Cliente desconocido (ni repo ni APK). Candidato a `routes/api.php` con Sanctum y clave por cliente |
| `tool/api/category5/getStaffList.php` | ajax | Lista de personal por tienda. | L `tb_staff` | |
| `desktop_request/ctiWindow.php` | redirect | CTI: con `?tel=` redirige a nueva ficha (`act=new`), a la agenda de reservas (módulo 9 u 11 activo) o a la búsqueda de clientes por teléfono. Valida con `ctype_digit`. | L `tb_base_system` | Sin login propio (la sesión se exige en la página destino) |
| `desktop_request/customerDetailHtml.php`, `jGetCustomerData.php` | ajax | HTML/JSON de la ficha del cliente para el programa CTI de escritorio. | L `tb_customer`, `tb_raiten_rireki`, `tb_shop`, `tb_staff` | **Sin autenticación** |
| `contact/index.php`, `contact_confirm.php`, `contact_complete.php` | alta / confirmación / registro + correo | 要望・お問い合わせ: formulario de consulta al proveedor con selección de tienda; envía correo. | L `tb_shop` | Reemplazar por `Mailable` en cola |
| `setting/index.php`, `setting/config.php` | config | Portada y ajustes del área `setting`. Sin enlace desde el menú. | — | Verificar si sigue viva |

## 11. `public_html/inc` e infraestructura

| Archivo | Qué hace | Destino Laravel |
|---|---|---|
| `inc/db.php` (130) | Carga `.env` (Dotenv), arranca Eloquent Capsule (`pgsql`) y `DbConnect` (`pg_connect`); consulta `Domain::fetchSystemDomain()` (`admin_master.domain`, por defecto un dominio de desarrollo), define `$hp_domain`, `$admin_add/send/sche`, `$from_mail`, `__ADMIN_CD=1`, carga `tb_office cd=1` en `$site_*` y define `form2Db()` (kana de ancho medio → completo, recorta, `pg_escape_string`) | `config/database.php`, `config('crm.*')`, FormRequests; Eloquent en lugar de SQL concatenado |
| `inc/env.php` | `env_value(name, default)`: carga `.env` solo si hace falta | `env()` en archivos de config |
| `inc/csrf.php` | `csrf_guard($check_get)`: bloquea peticiones *cross-site* con login según `Sec-Fetch-Site`/`Origin`/`Referer`, sin token; `CSRF_GUARD=report` solo registra | Middleware CSRF nativo (token en forms, `@csrf`); exceptuar las rutas de API |
| `inc/mail.php` (193) | `crm_send_mail()` con Symfony Mailer (antes `mb_send_mail`), `MAILER_DSN`, `MAIL_FROM`, `MAIL_CHARSET` (UTF-8 o ISO-2022-JP), clase de compatibilidad `easyDeco` (decomail) | `Mailable` + cola + driver `smtp`; ISO-2022-JP requeriría codificación propia |
| `inc/php_cli.php` | `crm_php_cli()`: ruta del binario PHP para procesos en segundo plano y cron | Colas/Scheduler; `PHP_CLI_PATH` desaparece |
| `inc/h.php` | `h()` escape HTML | `{{ }}` de Blade |
| `inc/qdmail.php` (4128 líneas), `inc/calendar.inc` | Librería de correo antigua y calendario PHP | Descartar |
| `public_html/index.php` | Vacío (0 bytes) | `/` → `redirect()->route('admin.home')` |
| `public_html/security.php` | "暗号化ツール": devuelve `md5(dominio)` (herencia de la clave vieja de API/activación), sin autenticación | **No migrar** |
| `system/js/` | jQuery (varias versiones), jQuery UI, colorbox, lightbox, Highcharts, Chart.js, `crm2.js`, `popupwindow`, `jquery.alerts`, `freerte_v1` (editor), `vis` | Vite + Chart.js/Alpine; no portar |

---

## Variables de entorno que lee el código (solo nombres)

Fuente: `C:\Users\User\VivaTech\crm2-php8\.env.example`, contrastado con `getenv()` / `env_value()` en `public_html/system`, `public_html/inc`, `src`. Ningún valor se copia aquí. Las claves, tokens y contraseñas **se leen de `.env`** y pasan a `config/*.php` + `.env` de Laravel.

| Variable | Qué la lee | Equivalente Laravel |
|---|---|---|
| `POSTGRES_HOST`, `POSTGRES_PORT`, `POSTGRES_DB`, `POSTGRES_USER`, `POSTGRES_PASSWORD`, `POSTGRES_DB_TEST` | `inc/db.php`, `Util/DbConnect.php` | `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| `STAGE` (vacía en producción; `docker`) | `Common::isDevelopmentAuthorityFromIP` (17 llamadas) | `APP_ENV` + `config('crm.developer_mode')` |
| `CRM_API_KEY` | `tool/api/checkKey.php` | `config('crm.api_key')` en middleware |
| `CSRF_GUARD` | `inc/csrf.php` | No necesaria (CSRF nativo) |
| `RELATION_LOGIN_PASS`, `SUPPORT_MASTER_PASS` | `login.php` y flags de sesión (8 y 5 usos) | `config('crm.support.*')` — **decidir si se mantienen** |
| `POS_DB_PASSWORD` | 29 usos: sincronía con servidores POS (`select.php`, `features_config`, etc.) | Conexión `pos` dinámica en `config/database.php` |
| `BACKUP_PASS` | `category0/config/backup.php` | Se descarta con `backup.php` |
| `MAILER_DSN`, `MAIL_FROM`, `MAIL_CHARSET` | `inc/mail.php` | `MAIL_MAILER`, `MAIL_FROM_ADDRESS`, `MAIL_*` |
| `MAIL_ADD_POP3_PASS` | `hp/mailsyori/mm_add.php` (HP, obsoleta) | — |
| `PHP_CLI_PATH` | `inc/php_cli.php` | Innecesaria |
| `MOBILESHOP_API_USER`, `MOBILESHOP_API_PASS` | `api/auth.php` (API tienda móvil) | Otro grupo |
| `MOBILESHOP_LIST`, `MOBILE_SHOP_EXTLINK_API_URL`, `MOBILESHOP_EXTLINK_PASS` | `src/MobileShop/*` | `config/services.php` → `mobileshop` |
| `SLACK_WEBHOOK_URL`, `SLACK_LIVE_TEST` | `Util/Slack.php`, tests | `LOG_SLACK_WEBHOOK_URL` |
| `SIGNAGE_DB_PASSWORD`, `SIGNAGE_PDF_BASE_URL` | `digitalsignage/`, `signage/` | Otro grupo |
| `TWITTER_CONSUMER_KEY`, `TWITTER_CONSUMER_SECRET` | `category12` (SNS) | Otro grupo |
| `BEACON_DB_PASSWORD` | category7 (ビーコン) | Otro grupo |
| `AZASNIGHT_DB_PASSWORD` | category8 (**retirado**) | — |
| `ADMIN_DB_PASSWORD`, `ADMIN_EXPORT_FALLBACK_DB_PASSWORD`, `LEGACY_REMOTE_DB_PASSWORD`, `ADMIN_FIREBASE_API_KEY_INDEX`, `ADMIN_FIREBASE_API_KEY_CONFIG`, `GOOGLE_API_KEY`, `GOOGLE_API_KEY_ADMIN` | `admin/` y código comentado | Fuera de alcance |
| `OTHER_DB_LIVE_TEST`, `CRM_SMOKE_PASS` | tests / herramientas | — |

No vienen de `.env` (constantes en código) y deben pasar a `config/crm.php`: `Constant::DEVELOPMENT_IPS`, `ADD_HOST`/`ADD_USER` (POP3 de `mm_add.php`), el dominio de desarrollo por defecto de `db.php`, y las URLs `b.g-api.net`, `crm-s.jp`, `vizan.azas24.com`, `pos-s.net`. `keys/public.pem` (JWT) es un archivo.

---

## Tablas de BD del grupo

| Tabla | Para qué | Submódulos que la usan | Notas (columnas clave) |
|---|---|---|---|
| `tb_staff` | Cuentas del personal | login, session, config, authority, shop_delete | `cd`, `login_id`, `login_pass` (texto), `disp_flg` ('1' activo), `shop_cd`, `office_cd`, `rank_pass`, `tb_staff_group_cd`, `name`; usuario `master` especial |
| `tb_staff_group` | Grupos de permisos | authority, cKengen | `cd`, `name`, `rank`, `disp_flg` |
| `tb_kengen`, `tb_kengen_contents` | Matriz grupo × contenido y catálogo de contenidos | authority*, cKengen, menús, select | `etsuran_flg`, `shinki_flg`, `shusei_flg`, `sakujo_flg`; `hyouji_flg`, `tb_base_system_cd`, `comment` |
| `tb_office` | Empresa (fila `cd=1`) | config, login, db.php | `kaishamei`, `pass`, dirección, `shop_url`, `hp_hyouji_name/tel` |
| `admin_master` | Configuración global (fila `cd=1`) | casi todo | `base_system_version_flg`, `application_use_flg`, `push_api_host`, `beacon_use_flg`, `ssl_flg`, `domain`, `system_mail`, `seller_id`, `related_features`, `pos_koubai_tb_search_form_cd` |
| `contents_master` | Pares categoría/campo/valor (banderas por función) | features_config, system, Base | `category`, `field`, `text_data`, `int_data`, `disp_flg`, `update_date` |
| `tb_base_system`, `tb_base_system_child`, `tb_base_system_grandchild` | Activación y árbol de menú | select, header, left_navi, system.php, bookmark | `cd`, `system_name`, `system_flg`, `narabi`/`rank`, `tb_base_system_cd` |
| `tb_bookmark` | Accesos directos de cabecera | bookmark, header | `bookmark_flg`, `user_rank`, `tb_kengen_contents_cd`, `tb_kengen_contents_node` |
| `tb_shop` | Tiendas | shop/*, session, select, API | `cd`, `kaishamei`, `disp_flg` (1/9), `master_cd`, `pos_domain`, `pos_package`, `mycolle_tel`, `email` |
| `tb_shop_category` | 店舗グループ | shop_category, shop_list | |
| `tb_area` | Árbol de エリア | area*, shop_new | Padre/hijo, orden |
| `hatuban` | Contadores por tabla | todo alta | `koumoku`, `kazu` |
| `tb_login_rireki` | Historial de login/logout | login, logout, get_shukei_data | `staff_cd`, `staff_name`, `update` (epoch), `komento` |
| `tb_pos_terminal`, `tb_c2_shopping` | Terminales POS y ajustes de tienda | pos_session, terminal_login | `terminal_no`, `tb_shop_cd`, `tb_printer_cd`, `pos_no`; `tax=5` |
| `tb_access_mapping` | Mapeo de acceso de terminales CRM | slim_list, system.php | |
| `tb_manage_words`, `tb_koumoku`, `tb_package_master`, `tb_hp_domain`, `tb_hp_data` | Textos configurables, campos de cliente, paquetes, dominios HP | system.php, select | `tb_koumoku cd=185 my_colle_use` se auto-inserta |
| `tb_crm_check_operation(2)` | Verificación de operaciones (monitor) | get_shukei_data* | Puede faltar (MISSING-TABLES.md) |

Convención del legacy: ids `cd`, tablas `tb_*`, banderas `disp_flg` (`1` activo, `9` borrado), fechas como epoch (`touroku_date`, `update_date`). El catálogo completo (~120 tablas) está en `category0/config/db_fix.php`.

---

## Propuesta Laravel 13

**Qué ya cubre CRMSystem** (`C:\Users\User\VivaTech\CRMSystem\public_html`):
- **Auth**: guard `admin` + modelo `Admin` (Fortify). Cubre un login genérico, pero **no** `login_id` (usa email), grupos, el `master` especial, soporte ni historial de login.
- **Tiendas**: `Shop` + `Terminal` + `BaseModel` (SoftDeletes, `status`, `uid`, scopes `active/ordered`). Faltan `master_cd`, `pos_domain`, grupos y áreas.
- **システム選択** (commit 7cf0c1c): `HomeController`, `config/crm.php → modulos` (8 tarjetas fijas; las activas enlazan, el resto "Próximamente"). **No lee `tb_base_system`, ni filtra por permisos ni por rango.**
- Layout `components/layouts/modulo.blade.php`, enums, lista de prefecturas, `PostalCodeController`, `Doc/database/*` (estándares, TABLAS_MODULO_CLIENTES/PROMOCIONES).

**A construir en este grupo:**
1. Modelos: `Staff` (`Authenticatable`), `StaffGroup`, `Permission`/`PermissionGrant` (o spatie), `Module`, `Setting`, `Office`, `ShopGroup`, `Area`, `LoginLog`, `Bookmark`, `AccessTerminal`.
2. Rutas (prefijo `/admin`): `settings/*` (基本情報, 機能設定), `permissions`, `bookmarks`, `areas`, `shops`, `shop-groups`, `system/modules` (solo `developer`), `support/*`.
3. Controladores: login de personal (Fortify con `username => login_id`), `SettingsController`, `PermissionController`, `ShopController`, `AreaController`, `ModuleController`, `BookmarkController`.
4. Middleware: `EnsureStaffActive`, `EnsureDeveloperAccess`, `ResolveCurrentShop`; `Gate::before` para master.
5. Jobs/Scheduler: sincronía con POS (si sigue vivo), limpieza de temporales, comando manual `ResetErrorSendCount`, avisos de caducidad de puntos (grupo promociones).
6. Policies: `ShopPolicy`, `AreaPolicy`, `SettingPolicy` mapeadas a `tb_kengen_contents`.
7. Seeders: catálogo `tb_kengen_contents`, `tb_base_system*` y los contenidos fijos 1032–1036 (hoy se insertan en `select.php`).

**Orden recomendado y dependencias:**
1. `Office` / `Setting` (`admin_master`, `contents_master`) y `legacy_cd` (todo depende de esto).
2. `Shop`, `ShopGroup`, `Area` (CRUD): desbloquea clientes (`tb_customer.tb_shop_cd`), staff, terminales y puntos.
3. `Staff` + `StaffGroup` + login con `login_id` + re-hash progresivo + `LoginLog`.
4. Permisos (matriz 4 × contenido) y `Gate::before` master.
5. `Module` (activación) + menú lateral dinámico + システム選択 real.
6. `features_config` (formularios → `settings`).
7. API `tool/api` y CTI, solo cuando se confirme quién las usa.

Dependencias con otros grupos: clientes necesita 1–4; promociones (puntos/sellos/mail) usa `features_config` y las plantillas de caducidad; staff/勤怠 (módulo 5) usa `Staff` y `StaffGroup`; 予約 (9/11) usa `pos_session` y `terminal_login`; el layout lo comparten todos.

---

## Riesgos y dudas

1. **`login.php` con `activation=1`**: reinicializa la BD y reescribe `inc/db.php` sin autenticación (basta un `Host` distinto de `admin_master.domain`); `create_customer.php` y `create_shop.php` sobrescriben datos. No migrar. Pendiente de Antony (PENDING.md 4.0, INTEGRATIONS.md).
2. **Contraseñas en texto plano y `md5(login_pass)` en enlaces** (category3, `hp/user`, `system/class`, `touchcation/*`): bloquea migrar a `password_hash`; hace falta convivencia (re-hash progresivo) y revisar los enlaces que llevan el hash.
3. **Soporte externo (`Relation`, `extsupport`, `b.g-api.net`) y las claves `RELATION_LOGIN_PASS`/`SUPPORT_MASTER_PASS`**: acceso `master` del proveedor. Decidir si se conserva (servicio auditable) o se elimina.
4. **Terminal POS sin secreto real** (`CRMPOS-<id>` en el UA; el token `md5(id.'key')` es calculable) y **API `tool/api` con cliente desconocido**: averiguar quién las llama antes de tocarlas (INTEGRATIONS.md).
5. **IP de desarrollo por `REMOTE_ADDR`**: detrás de proxy/Render cambia la IP; `STAGE=docker` abre todo. `STAGE` vacío en producción.
6. **Fallback abierto de `cKengen`** (staff sin grupo = todos los permisos) y la trampa de `config.php` (`tb_office.pass` pisa la clave de `master`): decisiones de comportamiento al migrar.
7. **Efectos secundarios de `select.php`** (sincroniza POS, normaliza fechas, inserta filas) y de `system.php`/`reset_error_send_cnt` (afectan a toda la BD sin confirmación): no replicar en el inicio; jobs idempotentes o descartar.
8. Dependencias externas: `b.g-api.net` (soporte, `setStoreTel`), `vizan.azas24.com` (RSS), `crm-s.jp`, servidores POS (`pos-s.net`, BD compartida), Imagick, `crontab` (INTEGRATIONS.md).
9. Pantallas con fallos (BROKEN-FILES.md, MANUAL-TESTS #125/#127/#128): `shop_list` opción "全て", avisos en `authority_contents*` y `area_*`, `update_date` vacío al editar tienda (igual en master).
10. Decisiones pendientes (PENDING.md): qué scripts de mantenimiento de `category0/config` se retiran; si `admin/` se migra (y con él `ACCESS_LEVEL_*`); si el POS remoto sigue como BD compartida; si se mantiene la UI de dos versiones (`base_system_version_flg`).
11. CRMSystem declara `laravel/framework ^12.0` en `composer.json` y `Doc/MIGRACION-LARAVEL13.md` sigue sin versionar: confirmar la versión real antes de generar código.
