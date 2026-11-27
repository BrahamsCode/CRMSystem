# Regresión de punta a punta (S1, 2026-11-27)

## Tercera pasada: testeo real por módulo con datos (Antony, 2026-11-27)

Web `http://localhost:8081` (BD `crmsystem2` recargada con `migrate:fresh` + `bd-legacy.sh` + `db:seed`). Cada sesión recorre con Playwright todas sus pantallas como master y como usuario limitado: listar, buscar, alta, edición, borrado, CSV, PDF y paginación. Compara el HTML y el comportamiento con development.crm-s.net, en **solo lectura**.

Cada sesión escribe solo en su subsección. Formato de cada fallo: `| pantalla (URL) | fallo | corrección + prueba Pest | commit |`. Al final de cada subsección va una línea de resumen: pantallas probadas, fallos encontrados y corregidos, y pendientes.

### Núcleo / 基本情報 (S1)

Recorrido con Playwright en `:8081` (BD `crmsystem2`, datos de la ronda anterior) como master y como `qa_partial`. Las pantallas que exigen IP de desarrollo (店舗新規登録 y 削除, システム管理, 権限項目管理, CRM端末管理) dan 403 en `:8081`, como el legacy sin esa IP: se probaron en `:18081`, mi worktree con `STAGE=docker` sobre mi BD `crmsystem2_nucleo`, que ya tiene los datos nuevos de la fase A (tienda de baja, nombre de 255 caracteres, tienda sin opcionales). En `:8081` lo creado lleva el prefijo «QA-S1» y se borró al terminar. Lo cambiado en 機能設定, 権限管理, 基本情報確認・変更 y ショートカット se dejó como estaba, comprobado en la BD: admin_master y tb_office con el mismo md5 que antes y tb_bookmark sin marcas ni orden. La muestra solo se abrió para comparar. Cada prueba usa un contexto de navegador limpio.

| Pantalla | Probado | Resultado |
|---|---|---|
| Login / logout (`login.php`, `logout.php`) | Clave errónea, usuario inexistente, campos vacíos, 退職, 店長, `qa_partial`, master; entrar sin sesión | Mismos mensajes que el legacy; sin sesión manda al login |
| システム選択 y cabecera | Módulos de master (1,3,5,6,7,9,0) y de un no master (sin 基本情報管理); お問合せ | OK (お問合せ: fallo corregido abajo) |
| 店舗検索・一覧 (`shop_list.php`) | Búsqueda por nombre, 責任者, correo, sin resultados y por エリア (árbol: doble clic despliega, un clic marca las tiendas); 表示件数; paginación (`kensu=2`, página 2); 表示 activas, de baja y todas | OK, igual que la muestra; el nombre de 255 caracteres no rompe la tabla |
| 店舗詳細, 修正 y validación (`shop_detail.php`, `shop_new.php?shop_cd=`) | Edición de 京都店 guardada y revertida; nombre vacío, correo y código postal erróneos | OK (ver el fallo de g-api abajo) |
| 店舗新規登録 y 削除 / 削除を取り消す (`:18081`) | Alta «QA-S1 テスト店», baja (aviso de traspaso de staff y clientes), recuperación y baja otra vez | OK, sin llamadas externas |
| 店舗グループ (`shop_category.php`) | Alta (vacía: aviso), edición, ▲▼, borrado y renumeración | OK |
| エリア管理 (`area.php`, `area_edit.php`, `area_confirm.php`) | Alta bajo 関西 con confirmación, edición, ▲, 表示 (更新する), borrado | OK |
| 権限管理 (`authority.php`) | Matriz de スタッフ por AJAX; 新規 de スタッフ管理 guardado y revertido | OK |
| 機能設定 (`features_config.php`) | En `:8081`: 端末ログアウト時間, 店舗グループ, メールフッター y 配信フィルター cambiados y revertidos. En `:18081`: プライバシーポリシー, 予約メール, プッシュタイトル, 集計範囲 y 不達配信数リセット | OK; mismo formulario que la muestra |
| 基本情報確認・変更 (`config.php`) | ホームページ表示名称 guardado y revertido; validación (会社名 vacío, correo) | OK; la パスワード solo va a tb_office, no a la clave de master |
| ショートカット管理 (`bookmark.php`, `bookmark_list.php`) | Registrar 4, reordenar arrastrando, enlaces de la cabecera, desmarcar; 設定を初期化する en `:18081` | Fallo corregido abajo |
| システム管理, 権限項目管理, CRM端末管理 (`:18081`) | ビーコン使用 cambiado y revertido; alta y edición de un 権限項目 en su ventana; lista de CRM端末 | OK. CRM端末登録 no se pulsó: abre el servidor externo ac.crm-s.jp |
| Usuario limitado (`qa_partial`) | Todas las URL de category0 | 403, como debe ser (基本情報管理 es solo de master) |

El núcleo no tiene pantallas con CSV ni PDF (los helpers `LegacyCsv` y `LegacyPdf` los usan los módulos).

| Pantalla (URL) | Fallo | Corrección + prueba Pest | Commit |
|---|---|---|---|
| Cabecera y ショートカット管理 (`bookmark*.php`) | Los accesos directos enlazaban la URL legacy de `tb_bookmark`. Las pantallas movidas a `/admin/…` daban 404: スタッフ一覧, クーポン一覧, メルマガ y otras; 40 de los 63 accesos de fábrica no tenían ruta (12 de ellos son pantallas descartadas o de kiosk) | `Bookmark::targetUrl()`: usa la URL legacy si conserva su ruta, si no la de Laravel (`/admin/promocion/<dir>/<archivo>` en category3 y un mapa para スタッフ, マイページ y 求人). Los accesos a pantallas descartadas (携帯HP, トピックス, フォトギャラリー) y a kiosk no se ofrecen. `BookmarkTest`: la cabecera enlaza la ruta nueva y cada URL de `bookmark_reset.sql` resuelve a una ruta existente | 4cd2fa7 (verificado en `:8081` tras fusionar: los 5 accesos probados dan 200) |
| お問合せ de la cabecera (`/system/contact/?wintype=no_headers`), hallado por S2 | 404: 要望・お問い合わせ (`system/contact/index.php`, `contact_confirm.php`, `contact_complete.php`) no estaba migrada | Migrada en la ventana emergente: formulario, confirmación y envío con `ContactMail` en cola a `crm.notify.contact` (`crm@extlink.co.jp`, como el legacy). master elige tienda; los demás envían la suya. `verify` evita el doble envío. `ContactTest` | 4edb8f8 |
| `<head>` común (login y todas las pantallas), hallado por S2 | `/system/img/favicon.ico` y `favicon.png` daban 404. El legacy tampoco los tiene (la muestra también da 404) | Favicon nuevo con la marca «CRM» del logo de la cabecera, en `public/legacy/system/img/`. `ContactTest` comprueba que se sirven | 4edb8f8 |
| Guardar tienda (`shop_new_register.php`) y login de soporte | Con `application_use_flg = 1` y `push_api_host = 1` (así viene el backup) las llamadas a g-api iban a b.g-api.net de **producción** desde desarrollo: mis dos ediciones de 京都店 en `:8081` mandaron `setStoreTel` con `domain=localhost` | Interruptor común `App\Support\GApi` (`CRM_GAPI_ENABLED`, apagado fuera de producción: solo registra en el log) en ShopService y StaffLogin; el seeder pone `push_api_host = 0` y `system_mail` @example.com. `GApiTest`, `LoginTest`, `DemoSeederTest` | dc7f712 |

**Resumen S1:** 13 grupos de pantallas del núcleo probados en el navegador como master y como usuario limitado, con alta, edición, borrado, búsqueda y paginación donde existen. 4 fallos encontrados y corregidos, cada uno con su prueba: ショートカット a URL legacy (4cd2fa7), お問合せ sin migrar (4edb8f8), favicon (4edb8f8) y llamadas a g-api de producción (dc7f712). Pendientes:
- Que S3, S4 y S5 apliquen `GApi` a sus llamadas: GApiPushTrigger, QrBaseInfo, RecommendationPush, `Appview\GApi` y PhotoAlbumUploader.
- ~~Volver a pasar el recorrido en `:8081` con los datos de la fase A~~: hecho, ver la segunda pasada abajo.
- Observación sin corregir, porque el legacy hace lo mismo: el `<select>` de tiendas de お問い合わせ se ensancha con el nombre de 255 caracteres. Queda para el rediseño final.

**Segunda pasada S1** (`:8081` en 738907f, `crmsystem2` recargada con las fases A de los 5 módulos): sin fallos nuevos.
- Recorrido de todas las URL de category0: 200 como master, con 403 en las 4 de IP de desarrollo; 403 en todas como `qa_partial`.
- Datos de la fase A:
  - 6 tiendas activas, 1 de baja (心斎橋店) y 7 en total.
  - Paginación con `kensu=5`: 5 filas en la página 1 y 難波店 en la 2.
  - La tienda de 255 caracteres no ensancha la tabla (945 px en una ventana de 1280).
  - Los detalles de 天王寺店, que no tiene opcionales, y de 心斎橋店 abren sin errores. 削除を取り消す no aparece sin IP de desarrollo, como en el legacy.
- Usuarios demo: `taishoku01` (退職) no entra (※ご入力のユーザーIDは無効です); `long01` y `tencho_kyoto` entran.
- `qa_partial` con la matriz nueva (権限管理 de QA部分権限: 1002 = 0000, 1024 = 1000):
  - 顧客管理: sin 顧客新規登録.
  - 販売促進管理: sin クーポン (1009) ni 新規 de メルマガ. Le salen los 新規作成 de push y リマインダ porque el legacy tampoco los controla con permisos.
  - 業務支援管理: sin スタッフ新規登録.
  - マイページ管理: solo オススメ y 求人情報一覧.
- Edición de 京都店 guardada y revertida, ahora sin llamada externa: el log de `:8081` registra «g-api apagado» con `b-develop.g-api.net`, porque `push_api_host = 0`.
- admin_master: `push_api_host = 0` y `system_mail = crm-mail@example.com`.
- `:8081` queda limpio: sin ショートカット marcadas y sin filas QA-S1 activas.

### Clientes / ventas / reservas (S2)

| pantalla (URL) | fallo | corrección + prueba Pest | commit |
|---|---|---|---|
| ficha `/admin/clientes/{cd}`, 来店処理 `customer_search.php` y 7 vistas más de clientes (lookup, photos, point-history, custom-records, visit-records, processing) | `<title>` «顧客登録情報 - Laravel»: usaban `config('app.name')` en vez del nombre del sitio (`$site_name` del legacy) | `LegacyUi::siteName()` en las 9 vistas de `resources/views/admin/clientes`; `QaClientesTest` («titula la ficha y 来店処理…») | 4df0081 |
| ficha, pestaña 有料会員状況 (`?tab=14`) de un 無料会員 | Error de JS `Cannot read properties of null (reading 'addEventListener')`: sin botón 変更する el script lo usaba igual (bug heredado del legacy) | `tabs/tab14.blade.php` sale si no existe `#paidMemberSendBtn`; `QaClientesTest` («no lanza error de JS…») | 4df0081 |
| CSV `/admin/clientes/export?format=standard o ex02`, 来店履歴 CSV `/admin/clientes/reports/visit-csv/download` | Cuerpo en Shift_JIS pero cabecera `text/x-csv; charset=utf-8` (Laravel añade utf-8 si no hay charset) | `text/x-csv; charset=Shift_JIS` en `CustomerCsvExporter` y `Reports/VisitCsvService`; `QaClientesTest` («declara Shift_JIS…») | 4df0081 |
| 売上データ削除 `/system/category1/karute_menu/karute_delete_list.php` (`/admin/ventas/sales`) | El botón 削除 no borraba nada: `deleteHandler(1773810038002900241,11)` pasaba el `tb_customer_cd` de 19 cifras como número y JS lo redondea (…900200), así que el POST no casaba ninguna fila (bug heredado: el legacy hacía lo mismo) | `sales/index.blade.php`: cd y grupo como cadena con `Js::from((string) …)`; `QaVentasTest` («pasa el cd de 19 cifras como cadena…») | 65f178c |

Clientes (master y umeda01): lista y paginación (10/50 por página, primera/última), búsqueda con filtros combinados AND/OR/NOT (tienda + sexo + edad, 来店回数, 会計金額, 予測来店日, 削除済; recuentos cotejados con la BD), alta y edición (master y umeda01, con 住所検索), borrado y 削除済, ficha con todas sus pestañas, 来店処理 (ficha y `customer_search.php`, インターバル), puntos (añadir/restar), sellos, 集計 (`customer_sumup_list` 業種別/県別, point1/point2/stamp1, 来店履歴 CSV), CSV estándar y DM, configuración (form_config, グループ, 来店動機, ランク, 振り分け, 追加情報, 入退室, カード). umeda01: solo 梅田店 (76), sin 削除 ni CSV (403). Sin 500 ni SQLSTATE. 3 fallos corregidos. Para S0/S1: en `crmsystem2` la contraseña de master no es `master` como dice el brief (ver `tb_staff.login_pass`); お問合せ de la cabecera abre `/system/contact/?wintype=no_headers` (404); `favicon.png`/`favicon.ico` 404 en el login. Datos demo: ningún `tb_stamp_log` tiene `kaikei`, así que el 集計 stamp1 sale a 0 (correcto según el SQL legacy). CSVインポート y table-export dan 403 a master (`nucleo.maintenance`, ya anotado). Ventas y reservas: pendientes (otra ronda). Reverificado en :8081 tras 005d388: `<title>` «顧客登録情報 - shop» y «来店処理 - shop» (sin «Laravel»), `?tab=14` de un 無料会員 sin errores de consola, CSV estándar con `text/x-csv; charset=Shift_JIS`.

Ventas (master, qa_partial y umeda01): 売上データ作成 (tienda, búsqueda de socio, スタッフ, メニュー, 割引, total 9.240円 cotejado; grabada y borrada la venta QA-S2), 売上データ削除 (búsqueda por fechas y スタッフ, paginación, borrado), メニュー管理 y 割引管理 (alta QA-S2 con ｶﾅ normalizada, edición, ▲▼, borrado), 経費管理 (alta, edición, borrado, paginación), スタッフ別集計 y 日報・週報・月報 (día, semana, mes, 全店舗, sin fechas; totales cotejados con la BD), 商品管理 category2 (validación AJAX, alta, edición, búsqueda, borrado). qa_partial: menú solo con 集計; los POST de alta y borrado dan 403. umeda01: siempre 梅田店, aunque se pase `?shop=3`. Sin 500, SQLSTATE ni errores de JS. 1 fallo corregido (pendiente de reverificar tras la fusión). Igual que el legacy, sin cambios: 売上データ削除 muestra la hora en 12 h sin AM/PM (`h:i`); la fecha por defecto del formulario es la del calendario, pero el 履歴 usa el día de negocio (corte a las 5:00), así que una venta hecha entre las 0:00 y las 5:00 no sale en el 履歴; los botones 削除 se ven aunque falte el permiso (el servidor da 403); CKEditor sale en el idioma del navegador. No hay PDF ni CSV en ventas.

### Promoción (S3)

Datos (fase A, `PromocionDemoSeeder`, marca `[demo-promocion-limites]`): 25 cupones más (caducado, 31/12, 29/02/2028, fin de mes, título de 100, oculto, 2 de baja, 0 y 999999 puntos) repartidos por tienda; 38 envíos más (fin de mes de 12 meses, 1/1 0:00, 29/02/2024, asunto de 100 y cuerpo largo, sin destinatarios, con 不達 —6 socios con `error_send_cnt`, 4 en `mailmaga_flg` 9—, 2 de baja, 16 デコメール, 3 de cupón) y 3 programados (fin de mes, 31/12, 29/02/2028), con 登録者 por tienda y 本部; 23 push (fin de mes, largo, 0 y 99999 puntos, sin destinatarios, de baja); 6 encuestas sin respuestas; rango oculto y rango de baja asignados a socios demo (los 4 niveles por tipo los crea S2); 新着情報 largo y de baja.

| pantalla (URL) | fallo | corrección + prueba Pest | commit |
|---|---|---|---|
| Datos demo: 過去配信 de メルマガ/デコメール como usuario de tienda (umeda01) | No ve ningún envío: los demo tenían `登録者` = cd del staff, y la lista de tienda filtra por 登録者 = tienda (フッター表記店舗) | `登録者` = cd de tienda (o -1) en `PromocionDemoSeeder` · `DemoSeederTest` (`登録者`, reparto por tienda) | ver abajo |
| テンプレート管理, テストメールアドレス, アンケートテスト, 過去配信詳細… (`/admin/promocion/*`) | 404 de los 絵文字: las plantillas del backup guardan `<img src="../decomail/js/tiny_mce/plugins/emoji…">`, relativo a `system/category3/<carpeta>/` | Ruta `admin/promocion/decomail/js/tiny_mce/…` → 301 a `legacy/system/category3/decomail/js/…` · `QaTerceraPasadaTest` | ver abajo |
| DM発行 (`/admin/promocion/dm/customer_list`) | Error de JS `myFlexSelect is not a function` (el buscador de S2 sin su plugin ni el datepicker) | La vista carga `myFlexSelect.js` y `ui.datepicker` · `QaTerceraPasadaTest` | ver abajo |
| クーポン一覧, 過去配信, 予定配信, クーポン利用数確認 (vuelta de 削除/修正 con `kensu=`) | 1 fila por página (`input('kensu', 20)` con '' → null → 1) | `(int) ($request->input('kensu') ?: 20)` · `QaTerceraPasadaTest` | ver abajo |
| Envíos de メルマガ en :8081 | Cada envío de texto avisaba a **b.g-api.net** real (BD con `push_api_host = 1`, `domain = localhost`) | `GApiPushTrigger` pasa por el interruptor común `App\Support\GApi` (CRM_GAPI_ENABLED, NUCLEO.md §9; apagado fuera de producción → solo log). Sustituye al `push_trigger` provisional · `QaTerceraPasadaTest` | ver abajo |
| 確認 de 新規メール/予約メール (`mail_mag/mail_new_confirm`) | El aviso azul 「＊＊＊＊ここに簡単ログインが入ります＊＊＊＊」 salía como texto escapado (`<span style=…>`); el legacy imprime `nl2br($message)` | Se escapa solo el cuerpo y el aviso va como HTML · `M2MailFlowTest` (confirma) | ver abajo |

Probado en :8081 hasta ahora: メルマガ y デコメール inmediatos a demo7@example.com (correo en el log, push solo log); 予約配信 31/12 23:55 alta → 修正 → 予定解除; テンプレート alta → 修正 → 表示 → 削除; ポイントルール 京都店 alta → 削除; push QA-S3 guardado (9490) pero la respuesta dio 500 por el permiso del laravel.log de :8081 (entorno, avisado a S0); recorrido de 145 pantallas como master y 131 como umeda01 (sin 500 ni 403 inesperados); cupón QA-S3 alta → lista (梅田店) → 修正 → 削除; CSV de クリックカウンタ (SJIS, `"	"` como el legacy); PDF de DM (300 socios). Sin fallo de código: imágenes de スタンプ `hp/uploads/c4/stamp/*.gif` (archivos subidos de producción, tampoco están en el repo legacy). スマトピ (404) espera F8.

### マイページ / 端末 / señalización (S4)

Datos (fase A): `ContenidoDemoSeeder` añade casos límite: bajas (`disp_flg`/`flg` 9), ガチャリン caducadas, ペーパーサイネージ 停止 y de baja, japonés al máximo (100/400/1000), opcionales vacíos, kana de ancho medio, 3 tiendas, 11–20 filas más por lista (paginan), 12 ICカード 端末, ICカード sin socio, perdidas y de tipo oculto, y en signage/ ciclos por periodo que acaban a fin de mes, a fin de año, el 29-02, uno caducado, dos solapados y uno diario. Prueba: `tests/Feature/Contenido/DemoSeederTest.php`.

| Pantalla (URL) | Fallo | Corrección + prueba Pest | Commit |
|---|---|---|---|
| 放映素材 `/admin/terminales/signage-materials` | Lista vacía: filtra por el primer display (como el legacy) y los 素材 de la demo no tenían `tb_display_cd` | El seeder los asigna al primer display; `DemoSeederTest` (assertSee ランチメニュー) | (este) |
| オススメ `/admin/mypage/recommendations` | La demo guardaba 全店舗 como `tb_shop_cd = 0` (tienda inexistente); el legacy usa vacío/NULL | Seeder con NULL; `DemoSeederTest` | (este) |
| signage/ reproductor `/signage/view?cd=` (también dentro de `/admin/senalizacion`, 端末一覧, パッケージ) | El sondeo `view/ajax.php` iba con `type: "GETT"` (errata del legacy, Apache lo aceptaba): **405** cada pocos segundos | `type: "GET"` en las 13 vistas `signage-view/*`; `SignagePlayerTest` (assertSee `type: "GET"`) | (este) |

Probado en :8081 como master: alta/edición/borrado con imagen de オススメ, 求人 y 求人職種 (全店舗 sale en todas las tiendas); ガチャリン (valor no numérico rechazado); 放映素材, 放映プログラム (selección de 素材 → XML) y ディスプレイ, y el reproductor `digitalsignage/index2.php` con telop e imagen; ペーパーサイネージ (alta, 停止, edición, borrado, QR, `/ptag/` 302, 集計, CSV SJIS); ICカード (búsqueda 全/未/済 = legacy); マイコレ カバーフロー (subida y borrado); signage/ 端末 (alta/edición/borrado) y GoTel (入力→確認→送信). Como `qa_partial` (matriz vieja): マイコレ, 求人新規, 求人職種 y signage/ dan 403; 端末管理 abre (sin kengen, como el legacy).

Ajenos a S4 (vistos en :8081): accesos directos de cabecera `tb_bookmark` a `/system/category3/mycoupon/mycoupon_list.php` y `/system/category5/staff/staff_list.php` dan 404, y お問合せ `/system/contact/` también (S1/S3/S5).

### Operaciones / 勤怠 / integraciones (S5)

Recorrido en :8081 con los datos de fe7296c (antes de la recarga) como `master`, `tencho_umeda` (店長), `umeda01` y `qa_partial` (matriz vieja). Lo creado lleva «QA-S5» y se borró al terminar, salvo el empleado `QA-S5 試験 太郎` (梅田店), que se queda para la segunda vuelta. Muestra consultada en solo lectura.

| Pantalla (URL) | Fallo | Corrección + prueba Pest | Commit |
|---|---|---|---|
| スタッフ新規登録 / 修正 y su 確認 (`/admin/operaciones/staff/create`, `/staff/confirm`) | La fila ログインID salía vacía. En Blade, `管理者用ID@else` no compila el `@else` porque la `@` va pegada a una letra latina, así que la rama del empleado nunca se pintaba | `{{ '管理者用ID' }}@else` en `form` y `partials/summary`; aserciones en `StaffEntryTest` (formulario y 確認) | 90b1daa |
| 勤怠一覧 variante 2 (`/admin/operaciones/working/day2`) | Error de JS «Unexpected identifier 'selectBox'»: el `class="selectBox"` se insertaba sin escapar dentro de una cadena JS con comillas dobles, y se rompía todo el script de edición de la fila | Comillas escapadas en el `@include` de `edit-js`; aserción en `WorkingDayTest` | 90b1daa |
| 勤怠一覧 / 集計 con los datos demo | Las marcas del seeder (fase A anterior) calculaban 出勤/遅刻/早出 y 退社/残業/早退 respecto al turno, no a la jornada de la tienda. Las pantallas calculan con `start_work_time` + 8 h + 休憩, y salían horas incoherentes (salida 17:10 con 退社 → 19:00) | `AsistenciaDemoSeeder::standardDay()`: el 勤怠 usa la jornada estándar de la tienda; los turnos (シフト) mantienen su variedad. Cubierto en `DemoSeederTest` | 90b1daa |

Probado sin fallos:
- **スタッフ:** búsqueda, paginación (10 por página), CSV (`user.csv`), alta → 確認 → registro, edición, 削除, 復元 y ficha con tarjeta (pestaña 2).
- **グループ:** alta, 修正, ▲▼ y 削除.
- **検索項目設定:** guardado sin pérdidas.
- **インフォメーション:** alta con tienda y 役職, HTML escapado, 修正 y 削除.
- **勤怠データ (kintai):**
  - alta, edición y borrado (el borrado confirma con `jquery.alerts`);
  - CSV diario y mensual (este último desde 各種設定);
  - 端末パスワード: error y cambio, devuelto a 9999;
  - 各種設定: guardado.
- **勤怠一覧:** calendario → día, edición en la fila (cambio y vuelta atrás), CSV, スタッフ一覧 y 集計.
- **シフト:**
  - 日別: pintado y guardado de un turno, que luego se quitó;
  - 月別: carga;
  - シフト区分: alta, 修正, ▲ y 削除.
- **営業日設定:** marcar y desmarcar un 休日.
- **Usuario limitado:** el menú oculta alta, グループ, シフト区分, パスワード y 各種設定, y esas URL dan 403. La ficha solo ofrece 戻る y no hay botón CSV.
- **API con curl:**
  - CTI: `jGetCustomerData` (socio y `NO_DATA`), `customerDetailHtml` y `ctiWindow` (búsqueda, y agenda con `customerCd`);
  - `mpa_corresponding`, `mpa_getTopBanner` y `mpa_getCoverFlow`;
  - `appview`: `qr_code`, `memberId`, `coupon`, `used_coupon_list`, `stamp`, `visit_history`, `photo_gallery` y `member_kiyaku`, con un socio demo de S2.

Paridad comprobada con la muestra:
- スタッフ一覧 vacía hasta buscar.
- El ID del formulario usa la tienda de la sesión y el registro, la elegida.
- インフォメーション «No» = `cd`.
- `rank` de un grupo nuevo = n.º de grupos + 1. Sale 2.º porque los grupos sembrados tienen `rank` 99–101 (S1).

**Segunda vuelta** (:8081 en 738907f, con los datos de las 5 fases A; `master` y `qa_partial`/`qa12345`):

| Pantalla (URL) | Fallo | Corrección + prueba Pest | Commit |
|---|---|---|---|
| 日別 / 月別シフト管理 (`/admin/operaciones/shifts/day`, `/shifts/month`) en 難波店 y 京都店 | Las filas de `kintai_tb_config` de esas tiendas vienen del backup con el horario por defecto del legacy (08:00–10:00). Los turnos caen fuera del tablero y `add.shift.js` manda `sch[]` con `undefined`; al pulsar 更新 se guardaba `undefined` y el turno se perdía (también en el legacy) | `ShiftBoard::allComplete()`: un turno sin inicio o fin en epoch no se guarda y ese día de ese empleado queda como estaba. El seeder pone el horario de la demo a las tiendas que siguen con 08:00–10:00. Pruebas en `StaffShiftTest` y `DemoSeederTest` | 21b8635 |

Probado sin fallos:
- Las 20 pantallas de operaciones como master y como `qa_partial`: 0 errores 500 o de JS. `qa_partial` recibe 403 en alta, グループ, パスワード, 各種設定 y シフト区分, y solo ve su tienda en el calendario.
- Datos nuevos visibles:
  - ficha con 所属, 役職, dirección y 交通費;
  - tarjeta asignada;
  - 14 empleados en 2 páginas;
  - 12 インフォメーション (se muestran las 10 últimas, por tienda y por 役職);
  - 勤怠 coherente con la jornada de la tienda (09:37 con apertura a las 10:00 → 早出);
  - turnos 締め 17:00–01:00 y solapes (応援).
- **`tool/api`** con la clave local: `getStaffList`, `searchCustomerList` (`keyword`), `getCustomerDetail`, `getRaitenList`, y `getSearchCateFormData` (`NO_DATA`). Con una clave errónea la respuesta sale vacía.
- **API JWT de la tienda móvil:**
  - `/api/auth`: credenciales erróneas → `Invalid User.`, y con las buenas emite el JWT;
  - `shop`, `point`, `mobile_shop/customer/<id>`, `mobile_shop/point`, `mobile_shop/sales_items`;
  - `passcode` por tienda: `04321` acepta `4321`, como el `==` del legacy;
  - sin token: 500 sin cuerpo, como el legacy.
- **g-api:** `appview/photo_gallery` y `visit_history` pasan por el interruptor común `App\Support\GApi`; con `CRM_GAPI_ENABLED=false` no sale ninguna petición. `PhotoAlbumUploader` no llama a g-api: solo devuelve a la app la URL `myAlbumPage`.

Pendiente: las columnas 普通残業/休日残業/休日出勤 de 集計 siguen vacías (pregunta de S2 en `para-japon.md` §3).

**Resumen S5:** 25 pantallas de operaciones y 4 grupos de API probados con datos. 4 fallos encontrados y corregidos, con prueba: ログインID vacía, JS de day2, datos de 勤怠 incoherentes y turnos `undefined`. Pendiente solo la pregunta de 集計 para Japón.

Web local `http://localhost:8081` (checkout principal en `laravel13`, BD `crmsystem2` con el esquema `signage`). La muestra no se tocó.

## Regresión global sobre `laravel13` 738907f con los datos de las fases A (S1, 2026-11-28)

`Doc/qa/scripts/regresion.js` recorrió `:8081` después de la recarga de S0 (`migrate:fresh` + `bd-legacy.sh` + `db:seed` con los DemoSeeder de los 5 módulos, 05:07 JST). Se abrieron todos los menús, de la cabecera, del lateral y de 予約管理, más las 128 URL de `config/menu`, que coinciden con la lista anterior. Hubo una pasada como master y otra como `qa_partial`. Los diálogos no se aceptan y los enlaces de borrar, reiniciar y logout se saltan.

| Usuario | Pantallas | 500 | En blanco | Error de JS | 404 | 403 |
|---|---:|---:|---:|---:|---:|---:|
| master | 135 | 0 | 0 | 0 | 3 | 7: condiciones que el menú también oculta: `nucleo.maintenance`/`nucleo.shop-create` (4, sin IP de desarrollo), `clientes.ext-shop.enabled` (ポイント管理), `nucleo.maintenance` (CSVインポート) y `operaciones.working-report.enabled` (帳票管理) |
| qa_partial | 133 | 0 | 0 | 0 | 3 | 28: las 7 de master más las que no tiene permitidas: category0 entera (solo master), 顧客新規登録 (1001-2), マイコレ y las imágenes de la app (1035), 求人新規 y 職種 (1023-2), スタッフ新規 y グループ, シフト区分, y 勤怠 パスワード y 各種設定 (solo master) |

- **404**: las mismas tres de siempre, que esperan una decisión y no son fallos nuevos: `smatopi_message.php` y `smatopi_sent_history.php` (スマトピ, F8) y `category7/beacon/beacon_list.php` (ビーコン, en pausa). El enlace externo `https://smatopi.net/login` no se abre (es externo).
- **Log** (`storage/logs/laravel.log` de `:8081`, desde la recarga, solo las entradas `local.*`; las `testing.*` son de la suite que S0 pasa en el checkout principal y comparte el archivo):
  - Ningún error ni excepción. Los dos recorridos (268 pantallas) no dejaron ninguna línea.
  - Intentos de salir: 2 «g-api apagado» de ShopService `setStoreTel` (mi edición de 京都店 en la segunda pasada S1, bloqueada) y 1 «PushTrigger (log)» (`push` cd 9513, a las 05:23: un envío de push de prueba de S3, que no sale porque en local el trigger es el de log).
- **Antes de la recarga** (04:16 y 04:50, en `:8081`), dos errores de permisos de archivos del entorno. Se pasan a S0:
  - `Unable to create a directory at /var/www/storage/app/public/hp/uploads/c6/apli_temp. mkdir(): Permission denied`: subida de imágenes de la app (S4, `app-images`).
  - `file_get_contents(/var/www/storage/app/keys/private.pem): Failed to open stream: Permission denied`: `MobileShopJwt` (S5), que no puede leer la clave privada.
- Auditoría de llamadas externas del código: [llamadas-externas.md](llamadas-externas.md).

## Segunda pasada: `laravel13` 1c56ac2 (2026-11-27)

El mismo script, sobre `:8081` con `laravel13` en 1c56ac2 (más a7d724b, que solo toca documentación). Se hizo `composer install` y no había migraciones pendientes. La lista de URL de `config/menu` se regeneró: ahora son 128 e incluyen 勤怠 (`/admin/operaciones/kintai*`, `working/calendar`, `working/report`).

| Usuario | Pantallas abiertas | 500 | En blanco | 404 | Error de JS | 403 |
|---|---:|---:|---:|---:|---:|---:|
| master | 134 | 0 | 0 | 3 | 0 | 7: condiciones que el menú oculta, `nucleo.maintenance` (5, entre ellas CSVインポート), `clientes.ext-shop.enabled` (ポイント管理) y `operaciones.working-report.enabled` (帳票管理, apagado por defecto) |
| qa_partial | 131 | 0 | 0 | 3 | 0 | 28: las 7 de master más las de sin permiso (category0, 顧客新規, マイコレ, 求人新規/職種, スタッフ新規/グループ, シフト区分, 勤怠 パスワード/各種設定) |

`storage/logs/laravel.log` no registró ninguna línea durante las dos pasadas.

Estado de los hallazgos de la primera pasada:

| Hallazgo | Estado |
|---|---|
| ホームページ管理 y データベース管理 (núcleo) | Corregidos |
| プッシュ通知 フォロー/自動通知新規作成 (404) | Corregidos: el menú apunta a `/admin/promocion/{follow,auto}/…_new?kind=…` (200) |
| 顧客ランク設定, error de JS de `@json` | Corregido: 0 errores de JS |
| ポイント管理 `point_setting.php` (404) | Tiene ruta. Da 403 aquí porque esta BD no tiene `clientes.ext-shop.enabled`, la misma condición que oculta la entrada del menú |
| スマトピ連携 `smatopi_message.php`, `smatopi_sent_history.php` (404) | Siguen en 404: pendientes de la decisión F8 (¿se usa スマトピ?) |
| ビーコン管理 `beacon_list.php` (404) | Sigue en 404: ビーコン en pausa, solo modelos, sin pantallas (provisional) |

Las tres 404 que quedan son las dos pantallas de スマトピ y la de ビーコン, las mismas que figuran como pendientes en [paridad.md](paridad.md). Mientras esperan una decisión, siguen en el menú. El enlace externo `https://smatopi.net/login` no se abre.

## Primera pasada (`laravel13` anterior a la integración de la regresión)

**Método** (`Doc/qa/scripts/regresion.js` con Playwright: plantilla con `__USER__`/`__PASS__`):

1. Se entra como `master` y como `qa_partial`. Para `qa_partial` se copiaron a `crmsystem2` el staff 900, el grupo 900 y sus 9 filas de `tb_kengen` desde `crmsystem2_nucleo` (ver [permisos.md](permisos.md)).
2. Se recorren por anchura todos los enlaces de la cabecera, de システム選択, del menú lateral (con submenús) y de la navegación propia de 予約管理 (`#yoyakuNavi` y sus ventanas `openPopWindow`), empezando por las portadas `system/category0|1|3|5|6|7|9`.
3. Después se abren directamente las 125 URL de `config/menu/*.php`, incluidas las que el menú oculta por permiso o por bandera de módulo.
4. En cada pantalla se anota el estado HTTP, el texto visible (página en blanco si tiene menos de 30 caracteres), los textos de error (`Server Error`, `SQLSTATE`, `Undefined`…), las peticiones XHR, JS o CSS que fallan y los errores de JS.
5. Para no escribir nada se saltan los enlaces de borrar, reiniciar y logout, y `alert`/`confirm` no se ejecutan (`confirm` responde «no»).

**Resultado:**

| Usuario | Pantallas abiertas | 500 | En blanco | 404 | Error de JS | 403 |
|---|---:|---:|---:|---:|---:|---:|
| master | 132 | 0 | 0 | 8 | 1 | 5 (condiciones `nucleo.maintenance` / `nucleo.shop-create`: sin IP de desarrollo, como el legacy) |
| qa_partial | 129 | 0 | 0 | 8 | 1 | 24 (sin permiso: category0, 顧客新規, マイコレ, 求人新規/職種, スタッフ新規/グループ, シフト区分) |

`storage/logs/laravel.log` no registró ningún error durante las dos pasadas: los 404 son URL sin ruta y no se registran.

## Núcleo: corregido en `l13/nucleo`

| URL | Qué pasaba | Arreglo |
|---|---|---|
| `/system/category4/` (pestaña y tarjeta de システム選択 ホームページ管理) | **404**. La pestaña sale porque `tb_base_system` cd 4 está activo, pero category4 se descartó entera (S4: sin consumidor) | `LegacyUi::visibleModules()` oculta el cd 4 igual que el 8 (`BaseSystem::DISCARDED_CD`); NUCLEO.md §2 y `LayoutTest` |
| `/system/category0/config/db_config.php` (menú データベース管理, con `nucleo.maintenance`) | **404** con IP de desarrollo o login de soporte. La pantalla está descartada (editor de BD crudo) pero seguía en el menú | Se quita la entrada de `config/menu/base.php` |

## Módulos: para S0

| Módulo | URL / menú | Qué pasa | Arreglo propuesto |
|---|---|---|---|
| promocion (S3/S4) | `/system/category3/follow/follow_new.php?kind=followPush` (プッシュ通知 › フォロー通知新規作成) | **404**: la URL del legacy no tiene ruta | En `config/menu/promocion.php` usar `/admin/promocion/follow/follow_new?kind=followPush`, que ya responde 200 («フォロー通知登録情報»), o redirigir la URL legacy |
| promocion (S3/S4) | `/system/category3/auto/auto_new.php?kind=autoPush` (自動通知新規作成) | **404**, mismo motivo | `/admin/promocion/auto/auto_new?kind=autoPush` (200, «自動通知登録情報») |
| promocion (S3) | `/system/category3/smatopi/smatopi_message.php`, `smatopi_sent_history.php` (スマトピ連携) | **404**: pendientes en la paridad, «uso por confirmar», pero visibles en el menú en esta BD | Migrarlas o quitarlas del menú hasta decidir |
| clientes (S2) | `/system/category1/rank/customer_rank_list.php` (顧客ランク設定) | **Error de JS** `Syntax error, unrecognized expression: # raitenRankList`: no se puede ordenar la lista de 来店ランク. `ranks/index.blade.php:152` `@json(implode(',', $types))` se compila como `json_encode(implode(', ', $types))`, porque Blade parte los argumentos de `@json` por las comas, así que sale `"kingaku, raiten"` | `{{ Js::from(implode(',', $types)) }}` o `{!! json_encode(implode(',', $types)) !!}`. Es la única `@json` con una coma dentro de una cadena |
| clientes (S2) | `/system/category1/customer/point_setting.php` (ポイント管理, solo con `clientes.ext-shop.enabled`) | **404**: sin ruta | Migrarla o confirmar que es descartada y quitarla del menú |
| terminales (S4) | `/system/category7/beacon/beacon_list.php` (ビーコン管理) | **404**: pendiente (el menú lo avisa: «aún no migradas») | Pendiente de S4 |

Fuera de esta lista: el enlace externo `https://smatopi.net/login` (スマトピ管理画面), que no se abrió.

## Notas

- En esta BD los menús de 端末管理 y 予約管理 salen vacíos para master por las banderas de módulo (igual que en la muestra, ver [diferencias-terminales.md](diferencias-terminales.md)). Por eso se abrieron también las URL de `config/menu` directamente: todas responden 200 salvo las de las tablas anteriores.
- Con `qa_partial` se abren con 200 pantallas cuyo menú se oculta, por ejemplo `karute_new.php` sin permiso de alta. Eso es de permisos, no de esta regresión: ver [permisos.md](permisos.md) y `permisos-*.md`.
