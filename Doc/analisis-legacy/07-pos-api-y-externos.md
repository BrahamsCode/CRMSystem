# 07 — POS, reservas POS, SNS, API móvil y endpoints externos
> Fuente: C:\Users\User\VivaTech\crm2-php8 (rama master = código ya migrado a PHP 8.5.12). Generado el 2026-11-26. Destino: Laravel 13 + PHP 8.5.

Alcance: `system/category11/` (POS), `system/category12/` (SNS), `api/`, `appview/`, `reserve/`, `r/`, `exp_cmd/`, `cron/`, `version/`, `instagram/`, `ipad/`, `ptag/`, `air/`, más `system/pos_session.php` y `system/terminal_login.php`. Excluidos: `hp/` (obsoleta; solo se menciona donde hay dependencia), `admin/` y `category8`.

**Idea central:** a diferencia del back-office, aquí hay clientes externos que no están en el repo (tablets POS con User-Agent `CRMPOS-<id>`, la tienda móvil con JWT, la app マイページ, los WebView de `appview/`, las APK que consultan `version/`). **Laravel debe mantener las URLs y los formatos exactos.** La Parte A documenta esos contratos; la Parte B documenta los submódulos internos.

## Resumen

| Módulo/submódulo | Carpeta | Nº PHP | Nº vistas reales (pantallas/endpoints) | Complejidad | Puntos |
|---|---|---|---|---|---|
| Autenticación de terminal POS | `system/pos_session.php`, `terminal_login.php`, `category11/terminal.php` | 3 | 2 | M | 3 |
| API JWT tienda móvil | `api/` (auth, customer, shop, point, `mobile_shop/*`, index) | 14 | 13 endpoints | L | 7 |
| API app マイページ (mpa_*) | `api/mpa_*.php` | 7 (1 vacío) | 6 | M | 4 |
| App de cliente (WebView) | `appview/` | 34 (6 son clases) | 26 | L | 8 |
| Reserva web pública | `r/` + `reserve/` | 9 + 9 (copias divergentes) | 7 por carpeta | M | 5 |
| POS: レジ (pos) | `category11/pos/` | 7 | 5 | XL | 8 |
| POS: 精算/開店 (checkout, opening) | `category11/checkout/`, `opening/` | 9 | 7 | L | 4 |
| POS: 商品/割引/カテゴリ (item) | `category11/item/` | 19 | 15 | L | 6 |
| POS: レジ設定 (config) | `category11/config/` | 7 | 5 | M | 2 |
| POS: 場所 (place, placeview) | `category11/place/`, `placeview/` | 11 | 6 | M | 3 |
| POS: 売上集計 (sumup) | `category11/sumup/` | 10 | 8 | L | 5 |
| POS: 予約 (yoyaku) | `category11/yoyaku/` + `ajax_getYoyakuData.php` | 70 | ~55 | XL | 14 |
| SNS Twitter/Facebook | `system/category12/` | 24 | 12 (APIs muertas) | M | 3 |
| Utilitarios (exp_cmd, cron, version, instagram, ipad, ptag, air) | varios | 24 | 20 | S | 4 |
| Restos de category11 (index, inc, test, test2) | `category11/` | 5 | 1 | S | 0.5 |
| **Total** | | **~257 PHP** | **~190** | | **≈ 74** |

---

# PARTE A — CONTRATOS EXTERNOS (Laravel debe mantenerlos)

## A.1 Terminal POS: `pos_session.php` y User-Agent `CRMPOS-<id>`

**Quién llama:** app/tablet POS (cliente fuera del repo; ni las 6 APK de `version/` lo generan). 61 archivos cargan `pos_session.php` en lugar de `session.php`: `category1/inc/*` (21), `category11/yoyaku` (21), `sumup` (7), `checkout`, `opening`, `placeview` (2 c/u), `pos`, `terminal.php`, `contact/*` (3), `terminal_login.php`. Los de `category1/inc` eligen la sesión con `$userAgent['UNIQUE_ID'] && $userAgent['DEVICE_NAME'] == 'CRMPOS' ? 'pos_session.php' : 'session.php'`.

**Detección del User-Agent** (`system/class/cUserAgent.php`; `inc/kihon.php` crea `$userAgent`): si el UA contiene `CRMPOS`, `DEVICE_NAME = 'CRMPOS'` y `UNIQUE_ID` = texto tras `CRMPOS-` (primera parte del UA).

**Orden de identificación de la terminal** (`pos_session.php`):

| Prioridad | Origen | Detalle |
|---|---|---|
| 1 | Parámetro (GET/POST) `unique_id=X&unique_key=md5(X . "<sufijo fijo en el código>")` | Modo prueba (テスト用): fija además `$_SESSION['c11']['disp']['mode'] = 'TABLET'`. El token es calculable por cualquiera. |
| 2 | `$_SESSION['terminal_id']` | Visita anterior (cookie de sesión PHP). |
| 3 | `$userAgent['UNIQUE_ID']` | Valor tras `CRMPOS-` en el User-Agent. **Sin ningún secreto.** |

**Efecto:** `SELECT *, (SELECT kaishamei FROM tb_shop ...) FROM tb_pos_terminal WHERE terminal_no = '<id>' ORDER BY cd DESC LIMIT 1`. Si existe, escribe en sesión: `shop_cd` (= `tb_shop_cd`), `shop_name`, `terminal_cd` (= `cd`), `terminal_printer_cd` (= `tb_printer_cd`), `terminal_posno` (= `pos_no`), `terminal_id`; pone `$POS_ACTIVATE = 1`; instancia `cKengen` (permisos); y si la tienda no tiene fila en `tb_c2_shopping` la inserta (`cd` = shop_cd, `tax = 5`, `tax_flg = '1'`, `disp_flg = '1'`, numerando con `hatuban`). Si no existe responde en texto plano `端末が認証されていません。` (más `【<unique_id>】` escapado si vino por parámetro) y hace `exit` (HTTP 200, sin código de error).

**Contrato para Laravel:**
- Middleware `PosTerminalAuth`: lee (1) la cabecera `User-Agent` con regex `CRMPOS-(\S+)`, (2) la sesión previa, (3) el par `unique_id`/`unique_key` solo mientras se confirme que alguien lo usa; busca en `terminals` por `terminal_no`.
- Mantener el método 3 tal cual (el cliente real no se puede cambiar a ciegas). Mantener la respuesta 200 con el texto `端末が認証されていません。` hasta confirmar que el cliente no depende de él.
- Mejora propuesta en convivencia: secreto por terminal en la tabla y cabecera `X-POS-Token`; primero solo registrar quién lo envía, luego retirar el método antiguo (INTEGRATIONS.md, preguntas 1-4).
- `terminal_login.php`: carga `pos_session.php`; si `$POS_ACTIVATE`, redirige (302) a `category11/terminal.php` (menú del POS); si no, muestra el texto de no autenticado.
- `tb_pos_terminal` equivale a `terminals` de CRMSystem (modelo `Terminal` con `shop()`), pero **faltan** `terminal_no`, `pos_no`, `tb_printer_cd`. En la BD local tiene 0 filas; producción desconocida.

## A.2 API JWT de la tienda móvil (`/api/...`)

**Enrutamiento:** `api/.htaccess` reescribe todo lo que no sea archivo/directorio a `index.php?<ruta>`. `index.php` toma la primera parte de la query string: `mobile_shop/<nombre>` pasa a `mobile_shop/<nombre>.php`; cualquier otra a `<nombre>.php`. Solo `[A-Za-z0-9_-]` en los segmentos; si no existe, intenta `404.php` (que no existe: fatal). En Laravel: URLs idénticas bajo `/api/...` (cuidar que el prefijo `api` de `routes/api.php` no duplique) y 404 JSON para rutas desconocidas.

**Autenticación:**
- `POST /api/auth`, cuerpo JSON `{"user_id":"…","password":"…"}`, comparado con las variables `MOBILESHOP_API_USER` y `MOBILESHOP_API_PASS` del `.env`. Éxito: `{"message":"Success.","jwt":"<token>"}`. JWT RS256 firmado con `keys/private.pem`; claims `iat`, `exp` (+30 días), `user_id`. Errores (HTTP 200): `{"message":"Invalid config."}` (sin variables) o `{"message":"Invalid User."}`. El README muestra también `?user_id=…&password=…` en la URL, pero el código solo lee el cuerpo.
- Resto: cabecera `Authorization: Bearer <jwt>`, verificada con `keys/public.pem` (RS256, `Firebase\JWT`). Token inválido: HTTP 200 con `{"status":"ng","message":"<mensaje de la librería>"}`.
- Laravel: `firebase/php-jwt`, mismas claves `keys/*.pem` y mismas variables de entorno (`config/services.php`, `hash_equals`). Middleware `jwt.mobileshop` que devuelva exactamente ese JSON con HTTP 200.

**Endpoints.** Formato común de éxito: `{"message":"Success.","user_id":<claim>,"payload":<cuerpo recibido>,"result":<datos>}` salvo indicación. Todos leen el **cuerpo JSON incluso en GET** (Laravel: `$request->json()`, no `query`).

| Método y URL | Parámetros | Respuesta / notas | Tablas |
|---|---|---|---|
| `POST /api/auth` | `user_id`, `password` | `{message, jwt}` | — |
| `GET /api/customer` | Filtros en cuerpo: `id`, `kojin_mobile` (igualdad), `tb_shop_cd` (numérico), `user_address` (LIKE sobre `add`), `kojin_birth` (LIKE `%v`), cualquier otra clave = LIKE `%v%` sobre esa columna. Sin filtros no devuelve nada | `{message,user_id,result:[{id,email1,zip,user_address,name,name_kana,kojin_mobile,kojin_birth,tb_shop_cd}]}` (solo `disp_flg='1'`, orden `cd`); `""` si vacío. **SQL concatenado: reescribir con bindings y lista blanca de columnas** | L tb_customer |
| `GET /api/shop` | — | `{message,result:[{cd,kaishamei,kaishamei_kana}]}` (disp_flg 1, orden cd) | L tb_shop |
| `GET /api/point` | `user_id` (entero > 0 = `tb_customer.id`), opc. `page`, `limit` (10), `range` | `{message,result:{count,data:[filas tb_point_log],limit,page}}`, orden `touroku_date DESC`. Error: `{"status":"ng","message":"必須情報がありません。"}` | L tb_point_log, tb_customer |
| `GET /api/mobile_shop/customer/<cd>` | `<cd>` entero en la ruta | `result`: `{cd,id,email1,name,name_kana,zip,pref,add,add_build,add_number,kojin_birth,kojin_mobile,touroku_date,update_date}` | L tb_customer |
| `GET /api/mobile_shop/customer` | Cuerpo: `disp_flg`, `user_id` (formato `<dominio><id lpad 9>`), `id` (varios separados por espacio = OR), `kojin_mobile`; al menos uno | **Consulta varias BD** (una por dominio de `DOMAIN_LIST`; `test` = BD de prueba): `result:{count,data:[{mycolle_cateogry,id,user_id,email1,zip,user_address(pref+add+add_build+add_number),name,name_kana,kojin_mobile,kojin_birth,tb_shop_cd,disp_flg}]}`; solo `touroku_kubun='2'`; orden `id DESC`. Sin filtros: `false`. Sin dominios: excepción `ERROR: No domain list.` | L tb_customer en N BD |
| `POST /api/mobile_shop/fetch_paid_member` | `customer_id` | Obtiene token (`/apiSnderAuth.php`) y consulta a la API extlink (`/apiJudServiceTgt.php`); devuelve si es miembro de pago | salida HTTP |
| `POST /api/mobile_shop/send_unpaid_member_status` | `customer_id` | Notifica a extlink la baja (`/apiSnderCan.php`) | salida HTTP |
| `POST /api/mobile_shop/passcode` | `shop_cd`, `passcode` | `{"status":"ok"}` o `{"status":"ng","message":"invalid passcode"}`. Compara con `contents_master` (`category='mobileshop'`, `field='mobileshop_auth_passcode'`, `int_data=shop_cd`, última por `update_date`, valor en `text_data`), texto plano sin `hash_equals`. Aquí `Authorization` ausente da un error de índice | L contents_master |
| `GET/POST /api/mobile_shop/point` | `customer_id` (entero > 0 = `tb_customer.id`) | `result`: `{service_point, point_yukoukigen}` o `false`. Errores `{"status":"ng","message":"IDがありません。"}` / `"顧客コードが見つかりません。"` | L tb_customer, tb_customer_point |
| `POST /api/mobile_shop/point_use` | `customer_id`, `shop_cd`, `use_points` (enteros > 0) | **Resta puntos**: `Point::setPoint(use_points, 2, shop_cd, staff -1, customer_cd, 'api:point_use', terminal -3)` en transacción con `FOR UPDATE`. Responde el resultado de `setPoint` directo (sin envoltorio) | E tb_customer_point, tb_point_log |
| `POST /api/mobile_shop/point_exchange_item` | `customer_id`, `salesItemCd`, `shop_cd` | Canje: exige `is_exchangeable_of_point=1`, `number_of_points_exchanged >= 0`, tienda dentro de `available_shop_cds` y saldo suficiente; luego `setPoint(puntos, 2, shop_cd, 0, customer_cd, "exchangeSalesItem:<cd>:<nombre>")`. Errores `{"status":"ng","res":"<texto>"}`: `不正なリクエスト。`, `顧客情報がありません。`, `交換可能商品ではありません。`, `使用可能な店舗ではありません。`, `ポイントが不足です。` | L sales_items; E tb_customer_point, tb_point_log |
| `GET /api/mobile_shop/sales_items/<cd>` y `GET /api/mobile_shop/sales_items` | Cuerpo: `page`, `limit` (10), `range` (3), `name`, `discription` (sic; ILIKE por palabras, espacio normal o ancho), `available_shop_cds` (`{1,2}`/lista), `is_exchangeable_of_point`, `number_of_points_exchanged_start/_end`, `hyouji_flg` | `result`: ítem o `{count,data[],limit,page}` (`disp_flg=1`) | L sales_items |

**Puntos del contrato que no se pueden romper:** (a) erratas de nombres (`discription`, `cutomer_cd`, `mycolle_cateogry`); (b) HTTP 200 también en error, con `status:"ng"` o `message`; (c) los GET llevan cuerpo JSON; (d) `user_id`/`customer_id` es el `id` público, no el `cd` interno de `tb_customer`; (e) `terminal_cd = -3` y `staff_cd = -1` (o `0` en el canje) marcan el origen API en el registro de puntos.

**Reglas de puntos** (`system/class/Point.php`, `setPoint`): transacción con `SELECT ... FOR UPDATE` de `tb_customer_point`; tipo 2 = resta; deja rastro en `tb_point_log`. En Laravel: `PointService` con `DB::transaction` y `lockForUpdate()`, reutilizando `CustomerPoint` y el enum `PointMovement`.

## A.3 API de la app マイページ (`/api/mpa_*.php`)

Sin autenticación (identifican con `login_id` o `regist_id` de `tb_customer`). Los llaman el servidor `g-api.net` (según comentarios y redirecciones) y la app móvil. La URL pública lleva `.php` (el archivo existe, no pasa por el enrutador).

| Endpoint | Método | Parámetros | Respuesta | Qué hace / tablas |
|---|---|---|---|---|
| `mpa_corresponding.php` | GET/POST | `login_id` (opc.) | `{status:'ok'\|'ng', version:'0.0.2', application_main_image:{main,small}, apli_home_thumbnail:{main}, apli_home_comment}` | `status` según `admin_master.application_use_flg = 1`. Con `login_id` busca la tienda del cliente y elige imagen/comentario de `contents_master` (`category='tb_c6_user_hp'`, `field='mypage_config'`, PHP serializado en `text_data`) y archivos `hp/uploads/c6/apli_home_image/…`. URLs absolutas `http://<HTTP_HOST>/hp/uploads/...?r=<filemtime>` (**depende de `hp/uploads`**) |
| `mpa_getTopBanner.php` | GET/POST | `login_id` (opc.) | `{status:'ok',errMsg:'',data:[{imageUrl,url}]}` | Banners 0-14 de `aplibanner/` según `mypage_config`; el banner "mi página" enlaza a `hp/user/control.php?log_chk=…&login_id=md5(login_id)&login_pass=md5(login_pass)&app=top.php` (**md5 de la contraseña en la URL**) |
| `mpa_getCoverFlow.php` | GET/POST | `login_id` (opc.) | `{status,errMsg,data:[{imageUrl,url}]}` | Imágenes de `hp/uploads/c6/aplicover/` filtradas por `mypage_config['cover']`; `url` de `contents_master` (`aplicover`, `text_data` separado por `\|\|`) |
| `mpa_albumUpdate.php` | POST multipart | `domain`, `cd` (= `tb_customer.id`), `pic_name` (imagen), `comment` | **302** a `http://b-develop.g-api.net/api/mpa/myAlbumPage?domain=…&cd=…&status=ok\|err1\|err2&comment=…` (`err1` faltan datos, `err2` no es imagen) | Alta en `tb_photo_album` (numeración `hatuban`); guarda en `hp/uploads/c1/photoalbum/<n>/0_moto.jpg` y crea `l/m/s/ss` con ImageMagick `convert` (`system()`); solo extensiones de imagen |
| `mpa_setraiten.php` | GET/POST | `regist_id` (numérico = `tb_customer.id`), `membership` (1 = beacon), `shop_cd`, `beacon_cd` | `{status:'ok',customer_info:{cd,id,name}}` o `{status:'error',errMsg,errorMsg}` | Registra visita con `cUtility::raitenFunc` (intervalo mínimo, sellos/puntos) o, si `membership=1`, inserta en `tb_beacon_history`. Errores: `入力される内容は数字のみでお願いいたします。`, `顧客が特定できませんでした。`, `来店処理できませんでした。インターバルなどをご確認ください。` |
| `mpa_customerSync.php` | GET/CLI | `cd` = id numérico, `unsynchronized`, `all` o `full` | Texto `{"status":"error","errorMsg":…}` si falla | Copia `tb_customer` y `tb_customer_sub` (`touroku_kubun='2'`) a la BD POS de **cada tienda con `tb_shop.pos_domain`** (`dbname=crm2019`, clave `POS_DB_PASSWORD`) con INSERT/UPDATE dinámicos según `pg_attribute`; marca `synchronize_flg='1'`. **Sin autenticación**: cualquiera puede lanzar una sincronización completa |
| `mpa_registCustomer.php` | — | — | Archivo vacío (0 bytes) | No migrar |

Laravel: rutas `Route::any('api/mpa_*.php')` hacia un `MpaController` con respuestas idénticas. `mpa_customerSync` pasa a **comando Artisan + Job** (no a ruta pública) si el POS externo se retira; si sigue vivo, mantener la ruta protegida.

## A.4 `appview/` — páginas de cliente (WebView/QR) y reserva exprés

Páginas públicas para el WebView de la app del cliente. **No hay sesión de cliente**: la identidad llega por `customer_cd` en la URL (cualquiera que conozca el cd ve cupones o sellos de otro cliente). No cambiar sin avisar a la app; proponer luego un token firmado.

| Archivo | Título (ja) | Parámetros | Qué hace | Tablas |
|---|---|---|---|---|
| `coupon.php` | クーポン | `customer_cd`, `kensu` (5), `page` | Lista paginada de cupones de la tienda del cliente | L tb_customer, tb_mycoupon, tb_mycoupon_master, tb_shop |
| `coupon_detail.php` | クーポン詳細 | cupón | Detalle y estado | L tb_mycoupon(_master), tb_shop, tb_base_system_grandchild |
| `coupon_use.php` | クーポン利用 | `customer_cd`, cupón | Confirmación de uso | L tb_customer, tb_mycoupon_master, tb_mycoupon_used |
| `coupon_control.php` | (AJAX) | cupón, cliente, `tb_shop_cd` | Registra el uso del cupón | E tb_mycoupon, tb_mycoupon_used |
| `used_coupon_list.php` | 利用済みクーポン | `customer_cd` | Cupones usados | L tb_mycoupon_used… |
| `stamp.php` | スタンプ | `customer_cd` | Tarjeta de sellos | L tb_customer, tb_stamp_config, admin_master |
| `visit_history.php` | 来店履歴 | `cutomer_cd` (sic) | Historial; consulta `http://<$_api_domin>/api/mpa/getComingHistory?domain=…&login_id=…` (servidor externo) | L admin_master |
| `qr_code.php` | (QR) | `customer_cd` (o `[customer_cd]` + `demo=1`) | Genera el QR del cliente | L tb_customer |
| `memberId.php` | (ID de miembro) | `customer_cd` (solo dígitos) | Muestra `login_id` (`error` si no es válido) | L tb_customer |
| `photo_gallery.php` | フォトギャラリー | `customer_cd` | Álbum de fotos | L admin_master |
| `member_login.php` | 会員ページ | POST `login_id`, `login_pass` | Compara `tb_customer.id` y `login_pass` **en texto plano** (`disp_flg='1'`, `touroku_kubun='2'`) | L tb_customer |
| `member_kiyaku.php`, `member_regist.php`, `member_comfirm.php`, `member_complete.php` | 顧客登録ページ, 新規会員登録フォーム, 登録確認ページ, 登録完了ページ | `shop_cd` | Auto-registro: términos, formulario, confirmación, INSERT en `tb_customer`/`tb_customer_sub`; puntos de alta en `tb_customer_point` y `tb_point_log` | E tb_customer, tb_customer_sub, tb_customer_point, tb_point_log |
| `member_init.php`, `member_comp.php` | 会員ページ | — | Inicio y vinculación; `member_comp` actualiza `tb_customer` | E tb_customer |
| `customerUpdate.php` | 個人情報の登録変更フォーム | `customer_cd` | Cambio de datos personales | L/E tb_customer, tb_shop, admin_master |
| `anketo2.php`, `anketo2_register.php` | 問診表 | `customer_cd`, respuestas | Cuestionario y registro; si hay premio crea cupón/punto | E tb_c3_anketo2_sub, tb_anketo_present, tb_mycoupon, tb_customer, hatuban |
| `reservation.php` | (予約) | `shop_cd`… | Contenedor de la reserva (incluye `r/`) | — |
| `quick_reservation.php`, `quick_reservation_register.php` | (クイック予約) | `shop_cd`, `staff_cd`, `sess` (= `session_id()`) | Reserva rápida con ticket numerado y tiempo de espera; el registro responde JSON `{status…}` | E tb_yoyaku, numbered_ticket |
| `privacyPage.php` | プライバシーポリシー | `shop_cd` | Texto por tienda | L contents_master, tb_shop |
| `introduction.php`, `detail_view.php` | プレビュー | — | Estáticas/preview | — |
| `ms_paid_member_regist.php`, `ms_paid_member_test.php` | (prueba) | — | Formulario de prueba que hace POST a `ssl-weblink.jp/sys/mycl/mycl_index.php` con `mycolle_*` y un cliente de prueba fijo. **Código de prueba: no migrar** | — |
| `class/*` (Customer, Database, Password, QuickReservation, UserAgent, Util) | | | Apoyo (`QuickReservation::getReservationWaitTime/addReservation/isStarted`) | |

Laravel: `Route::prefix('appview')` con los mismos nombres de archivo (`.php`) para el WebView, vistas Blade, y redirecciones. No se migran `bower_components` ni `img`/`css` (≈270 archivos): se reemplazan por Vite/Tailwind.

## A.5 Reserva web pública `r/` y `reserve/`

Dos copias **divergentes** (los 9 PHP difieren; `reserve/` añade `Util.php`, su `ajax_reserveDelete` es más largo y usa el catálogo; `r/` añade `getReservationWaitTime.php`). Se usan desde la web de cada tienda (iframe) y desde `appview/reservation.php`. **Decisión pendiente:** cuál es la vigente; propuesta: migrar `r/` y dejar `reserve/` como alias.

| Endpoint | Método | Parámetros | Respuesta |
|---|---|---|---|
| `/r/index.php` (「Reserve」) | GET | `shop_cd`, `menu_cd[]`, `staff_cd`, `start_date`, `token` | Página con calendario; la sesión (`session_id()`) se usa como `sess` en los AJAX |
| `/r/check.php` (「Reserve」) | POST | `menu_cd[]` + datos | Confirmación del menú |
| `/r/ajax_getScheduleData.php` | GET | `shop_cd`, `start_date`, `end_date`, `staff_cd` | `{"status":"ok","data":{"YYYY-MM-DD":{youbi,dow,weekNum,flg,startTime,startTimeUT,endTime,endTimeUT}}}` o `{"status":"error","errorMsg":"不正アクセス。"}` |
| `/r/ajax_getChoiceData.php` | GET | `shop_cd`, `start_date`, `end_date`, `menu_cd`, `staff_cd`, `kind` | `{"status":"ok","data":{…franjas…}}` o error `不正アクセス` |
| `/r/ajax_reserveRegister.php` | POST | `sess`, `shop_cd`, `selectedDate` (YYYY/MM/DD o con `-`), `selectedTime` (H:MM), `menu_cd[]`, `staff_cd`, `name`, `name_kana`, `tel`, `mail`, `customer_cd`, `token`, `free_koumoku` | `{"status":"ok","refreshSchedule":true}` o `{"status":"error","errorMsg":…,"refreshSchedule":true}` (textos: `不正アクセス。`, `オプションメニューのみで予約できません。`, `選択した日時の予約できる人数を超えた為…`) |
| `/r/ajax_reserveDelete.php` | POST | `sess`, `cd`, `shop_cd` | `{"status":"ok"}` o error (`予約データがありません。`, `取消処理に失敗しました。`); borrado lógico en `tb_yoyaku` |
| `/r/getReservationWaitTime.php` | GET | `shop_cd` | `{"status":"ok","waitTime":<min, mínimo 10>,"minReservationDatetime":"Y-m-d H:i","minReservationUT":<ts>}` o `{"status":"error","errorMsg":"予約できません。"}` |

Autenticación: **cookie de sesión PHP + parámetro `sess`** (sin login). En Laravel: `Route::prefix('r')` + alias `reserve`, JSON idéntico (claves `status`, `errorMsg`, `refreshSchedule`), `sess` validado contra `session()->getId()`. La lógica (aforo, `tb_yoyaku_config`, `tb_yoyaku_uketsuke_day`) está en `r/class/Reserve.php` y `Timetable.php`. Tablas: E tb_yoyaku; L tb_yoyaku_koumoku_master, tb_customer, admin_master, contents_master, tb_manage_words, tb_shop, tb_c2_catalog/category.

## A.6 Otros endpoints consumidos desde fuera

| URL | Método | Parámetros | Respuesta | Cliente / notas |
|---|---|---|---|---|
| `/version/pos/v.php`, `/version/android3/v.php` | GET | — | Texto plano `2.704` (sin salto de línea) | Las APK consultan y descargan `/version/<canal>/<ver>.apk` |
| `/version/sharp/v.php` | GET | — | Texto plano `2.703` | Canal `sharp` (APK 2.7.0.1, 2.701, 2.702, 2.703) |
| `/version/crm_version.php` | GET | — | Texto `2.704` | Versión general. Laravel: rutas `text/plain` idénticas y `Storage` público para los `.apk` (6 APK); **no JSON ni salto de línea** |
| `/air/update-info.xml`, `/air/crossdomain.xml` | GET | — | XML de actualización de Adobe AIR (versión 1.0.13, url `extlink.crm-s.net/air/crm.air`) y política `allow-access-from *` | Cliente de escritorio AIR obsoleto: servir estático si sigue instalado |
| `/air/air_info_out.php` | GET | `login_id`, `kensu` | Última `tb_information` visible para ese personal (por `shop_cd`, `belongpost`, `employmentsystem`, `officialposition`; `master` ve todo) | Confirmar uso |
| `/ptag/<ID>` | GET (`.htaccess` reescribe a `ptag/index.php`) | ID tipo `TAG000000007`; cookie `UID` = `md5(login_id/login_pass)` | **302** a `tb_url_list.url` (solo `disp_flg='1'` y `hyouji_flg='1'`); registra `url_transfer_data(access, tb_customer_cd, data_id)`. Textos: `無効なIDです。`, `GETデータがありません` | Enlaces de seguimiento en correos/QR |
| `/instagram/index.php` | GET | `access_token`, `keyword` | Proxy de `api.instagram.com/v1/tags/<keyword>/media/recent` (**API retirada**) | No migrar |
| `/ipad/index.php`, `downLoadInfo.php`, `otherBrowser.php` | GET | User-Agent | Guía de instalación como web-app iPad (`ホームへダウンロードしてください。`) | Obsoleto |
| `/exp_cmd/cron/get_shukei_data.php`, `get_shukei_data2.php` | GET/CLI | `year`, `month` (o argv; por defecto mes anterior) | Texto; consolida ventas por tienda del dominio `tb_hp_domain` | Nadie del repo lo llama (INTEGRATIONS: desconocido); sin autenticación |
| `/cron/regenerateCronJob.php` | GET | — | `{"status":"ok"}` | Reconstruye el crontab (`cSendSchedule->createSchedule()`); lo invoca `category0/config/db_export.php` |

---

# PARTE B — SUBMÓDULOS INTERNOS

## POS (`system/category11/`)

Qué hace: POS web para peluquerías/restaurantes/tiendas que corre en tablets con User-Agent `CRMPOS-<id>`. El cajero abre el día (開店), toma pedidos desde un catálogo, cobra (efectivo, tarjeta, 商品券, puntos), cierra la caja (精算) y consulta ventas; además gestiona reservas internas (yoyaku), parte de ellas contra la BD POS de la tienda. Terminal, tienda e impresora se resuelven con `pos_session.php` (A.1).

**Aviso crítico:** las tablas `tb_c2_*` (`tb_c2_shopping`, `tb_c2_sum_master`, `tb_c2_catalog`, `tb_c2_category`, `tb_c2_catalog_categorys`…) las **borró la migración `Version20200615093300`** (MISSING-TABLES.md): no existen en el respaldo y las consultas se silencian con `@`. Hay que decidir (PENDING 2b) si category11 sigue en uso antes de invertir en migrarlo. Constantes (`category11/inc/inc.php`): modos de terminal 1 registro / 2 tableta / 3 rehacer / 4 anular; pago 1 efectivo, 2 tarjeta, 3 商品券; precio 1 básico, 2 socio, 3 especial, 4 oferta; forma 1 peluquería, 2 restauración, 3 tienda; estados de mesa `BOX_FLG_*` 1 a 8; el día comercial cambia a las 5:00 (`TODAY_UNTIL`).

### Vistas category11 (sin yoyaku)

| Archivo | Título (ja) | Tipo | Qué hace / acciones | Tablas BD | Notas |
|---|---|---|---|---|---|
| `index.php`, `terminal.php` | CRM POS SYSTEM | detalle | Menú principal del POS | — | `terminal.php` lo abre `terminal_login.php` |
| `pos/index.php` | CRM POS SYSTEM | edición | Caja: catálogo por categoría, carrito, descuentos | L tb_c2_shopping | Usa `pos_session.php`; jQuery 1.4.4 |
| `pos/order.php` | CRM POS SYSTEM | edición | Pedido/mesa (modo restauración) | L tb_c2_shopping | |
| `pos/complete.php` | CRM POS SYSTEM | confirmación | Cobro: recibido, vuelto, forma de pago; POST AJAX a `sendPosData.php` | — | |
| `pos/sendPosData.php` | (texto plano) | registro(POST)/ajax | Registra la venta: `tb_order_main` y `tb_order_sub` (detalle separado por tabulador), puntos usados, visita, stock; también anula/rehace | L/E tb_c2_catalog, tb_c2_shopping, tb_c2_sum_master, tb_order_main, tb_order_sub, tb_yoyaku_order_main, hatuban | **Contrato:** POST `tb_order_main_cd`, `tb_customer_cd`, `item_cd[]`, `<item_cd>[num\|price\|witch_price]`, `used_points`, `nyukin_price`, `waribiki`, `waribiki_per`, `gift_card`, `how_to_pay`, `witch_price`; respuesta texto `complete` / `complete:取り消しました` / `Error:<texto>` (no JSON) |
| `pos/ajax_getCatalogData.php` | (JSON a mano) | ajax | Busca producto por `val` (cd) o `searchField=bcid` (código de barras) y `shop_cd` | L tb_c2_category, tb_c2_catalog_categorys, tb_c2_catalog | Requiere `session.php` (personal). Respuesta `{...,"status":"ok"}` o `{"status":"error"}` |
| `pos/posCommon.php`, `pos/inc/head.php` | | fragmento | Carga común (impuesto `tb_c2_shopping.tax`, pedido abierto) | L tb_c2_shopping, tb_order_main, tb_yoyaku_order_main | |
| `checkout/index.php` | CRM POS SYSTEM | lista | Pedidos pendientes de cobro del día | L tb_c2_sum_master, tb_order_main, tb_yoyaku_order_main | pos_session |
| `checkout/checkout.php`, `checkout_frm.php` | CRM POS SYSTEM | edición | Cierre de caja (conteo de billetes `regi_end_regi_cash_num0..9`, tarjeta, 商品券, puntos, descuentos) | L tb_c2_sum_master, tb_order_main, tb_staff | |
| `checkout/checkout_register.php` | CRM POS SYSTEM | registro(POST) | `UPDATE tb_c2_sum_master` con los totales (`regi_cash`, `regi_credit_card`, `count_regi_*`) si `sessId == session_id()` y `act=up` | E tb_c2_sum_master | Responde 403 si se abre directo |
| `checkout/check_out_query.php` | | ajax | Totales por pedido | L tb_order_main, tb_order_sub, tb_c2_shopping | |
| `opening/index.php`, `opening/reset.php` | CRM POS SYSTEM | alta | 開店 (fondo de caja y responsable) y reinicio del día | E tb_c2_sum_master, hatuban; L tb_staff | |
| `config/index.php` | CRM POS SYSTEM | config | レジ設定: día de inicio, impuesto, textos de recibo; categorías de bandera | L/E tb_c2_shopping, tb_flg_category(_joint), hatuban | |
| `config/flg_category_list/_frm/_new/_edit.php`, `register.php` | | lista/alta/edición/registro | Categorías de bandera (フラグカテゴリ) y su relación con tiendas | E tb_flg_category, tb_dispaly_systems (sic) | |
| `item/index.php`, `item/master.php` | CRM POS SYSTEM | detalle | Menú de gestión de productos | L tb_flg_category, hatuban | |
| `item/item_category_list/_frm/_new/_edit.php` | | lista/alta/edición | Categorías de producto por tienda | L/E tb_c2_category, tb_dispaly_systems | |
| `item/item_entry*.php`, `item_list.php`, `item_list_search.php` | 商品管理 | lista/alta/edición | Alta de producto/servicio: categoría, 4 precios, banderas, descuentos asociados, tiendas | L/E tb_c2_catalog(_categorys), tb_c2_category, tb_flg_category_joint, tb_waribiki(_joint), tb_shop | `item_entry_frm.php` 671 líneas |
| `item/waribiki_list/_frm/_new/_edit.php` | 割引管理 | lista/alta/edición | Descuentos y tiendas donde aplican | E tb_waribiki, tb_waribiki_joint, tb_dispaly_systems | |
| `item/register.php` | | registro(POST) | Escritura genérica (`$tableName` entre tb_c2_category, tb_c2_catalog y tb_waribiki) con `hatuban` | E varias | `tb_c2_*` se silencia porque no existen |
| `place/place_list/_frm/_new/_edit.php`, `register.php` | 場所管理 | lista/alta/edición/registro | Zonas/mesas/asientos | E tb_place, tb_flg_category(_joint), hatuban | |
| `placeview/index.php` | (場所一覧) | lista | Mapa de mesas con estado `BOX_FLG_*` | L tb_information_place, tb_shop, hatuban | `BOX_FLG_COMPLETE` se define 3 veces |
| `placeview/register.php` | 入店登録 | registro(POST) | Registra la entrada de un cliente a la mesa | E tb_information_place, tb_place | |
| `sumup/index.php` | CRM POS SYSTEM | detalle | Menú de 売上集計 | L tb_c2_shopping | pos_session |
| `sumup/shop_sum_news.php`, `shop_sum_news_detail.php` | 店舗別売上速報 | lista/detalle | Ventas del día por tienda y personal | L tb_c2_shopping, tb_c2_sum_master, tb_order_main, tb_shop, tb_staff | |
| `sumup/sumup_list_pc.php`, `sumup_list.php`, `sumup_list_data.php` | 月間売上集計 | lista | Ventas mensuales por tienda | L tb_c2_shopping, tb_shop | |
| `sumup/sumup_staff_pc.php`, `sumup_staff_data.php` | スタッフ別売上 | lista | Ventas por empleado | idem | |
| `sumup/sumup_graph.php` | 年間売上集計 | detalle | Gráfico anual | idem | **Roto en master** (500) y llama a `setGraph1…12` inexistentes |
| `test.php`, `test2.php`, `inc/*`, `*/inc/head.php` | | fragmento/prueba | Utilidades y pruebas | L tb_yoyaku_config, tb_staff… | No migrar test/test2 |

### Yoyaku POS (`system/category11/yoyaku/`) — 69 PHP

Reservas internas del POS: calendario, línea de tiempo por personal, alta por cliente/menú/personal, configuración, listados e impresión. Una parte **se conecta a la BD POS de la tienda** (`$posConnect`; host = `tb_shop.pos_domain`; variable `POS_DB_PASSWORD`); el resto usa la BD del CRM.

| Grupo | Archivos | Tipo | Qué hace | Tablas | Notas |
|---|---|---|---|---|---|
| Nueva reserva | `yoyaku_new.php` → `yoyaku_new_customer_select.php` → `yoyaku_new_customer_form.php` → `yoyaku_new_select_menu.php` → `yoyaku_new_select_staff.php` → `yoyaku_new_register.php` → `yoyaku_new_complete.php` | alta/confirmación/registro | Elegir tienda, cliente (existente o nuevo), menús, personal y fecha; registrar | E tb_yoyaku, hatuban, tb_base_system_grandchild; L tb_customer, tb_staff, tb_staff_works, tb_yoyaku_koumoku_master | `yoyaku_new_register` escribe `moushikomi_time` (columna inexistente) y redirige a `yoyaku_new_calendar.php` (404): **roto** |
| Reserva clásica | `yoyaku.php`, `yoyaku_customer_select.php`, `yoyaku_register.php`, `yoyaku_revice.php` | alta/edición/registro | Alta y modificación de reserva | E tb_yoyaku, hatuban; L tb_customer, tb_shop, tb_staff, tb_koumoku | pos_session |
| Calendario y listas | `yoyaku_calendar.php`, `yoyaku_list_calendar.php`, `yoyaku_list_shop.php`, `yoyaku_list_time_table.php`, `view_shop.php`, `pop_calendar.php`, `time_table.php`, `time_table_staff.php`, `time_table_function.php`, `ajax_time_table.php` | lista/ajax | Calendario mensual, línea de tiempo por personal, 予約一覧, 年間カレンダー | L tb_yoyaku, tb_yoyaku_uketsuke_day, tb_staff, tb_staff_works, tb_c2_catalog/category | `ajax_time_table.php` (544 líneas) es el núcleo |
| Impresión/CSV | `yoyakuPrint.php` (予約プリント), `ajaxYoyakuPrint.php`, `print/yoyaku_list_print.php` (予約一覧出力), `print/csv_download.php`, `print/csv_download/csv_file_out.php`, `csv_file_del.php` | pdf/csv | Impresión y CSV | L tb_yoyaku, tb_customer, tb_staff, tb_c2_catalog | CSV por archivo temporal |
| Configuración | `yoyaku_config.php`, `yoyaku_comment_config.php`, `yoyaku_form_config.php`, `yoyaku_kinou_config.php` (予約設定), `yoyaku_koumoku_new.php`/`_register.php`, `yoyaku_koumoku_category.php`/`_register.php` (予約項目管理), `setsubi_new.php`/`_register.php` (設備管理), `staff_works.php` (スタッフ施術管理), `free_yoyaku.php`/`_register.php` (フリー予約設定), `yoyaku_uketsuke_day/_register/_delete.php`, `yoyaku_multi_uketsuke_day/_register.php` (受付日) | config/alta/edición | Parámetros, menús, equipamiento y horarios de atención | E tb_yoyaku_config, tb_yoyaku_koumoku_master, tb_yoyaku_koumoku_category, tb_setsubi, tb_staff_works, tb_yoyaku_uketsuke_day, tb_manage_words, contents_master, hatuban | Usan `$posConnect` los de time_table, staff_works, yoyaku_config, koumoku, setsubi, uketsuke, free_yoyaku |
| `posYoyaku.php` | 予約受付 | alta | Recepción de reservas en el POS (776 líneas) | L tb_customer, tb_staff, tb_staff_works, tb_yoyaku_config | `$posConnect` |
| Vínculo | `related_customer.php`, `ajax_related_customer.php` | edición/ajax | 顧客関連づけ | E tb_yoyaku | |
| AJAX | `ajax_menu`, `ajax_staff`, `ajax_yoyaku_config`, `ajax_yoyaku_menu`, `ajax_yoyaku_koumoku_new`, `ajax_yoyaku_koumoku_category`, `ajax_yoyakuRegister`, `ajax_yoyaku_register`, `ajax_yoyakuDelete`, `ajax_move_objs`, `ajax_search_kaiin`, `ajax_getYoyakuDatetimeForm`, `ajax_uketsuke_status`, `ajax_loadNewYoyakuList`, `ajax_loadNewRaitenList`, `ajax_free_yoyaku_rank`, `ajax_view_pos_shop`, `../ajax_getYoyakuData.php` | ajax | Fragmentos HTML/JSON de las pantallas | tb_yoyaku, tb_staff, tb_yoyaku_* | Muchos con `$posConnect` (500 en Docker por falta de servidor POS) |
| Auxiliares | `posConnect.php`, `inc/yoyaku_control.php`, `inc/yoyaku_navi.php`, `sample_array.php` | fragmento | Conexión a BD POS por `shop_cd`, navegación, ejemplos | tb_shop, tb_base_system(_child) | |

Existe una copia en `system/category9/yoyaku/` (mismo error de `moushikomi_time`): migrar una sola vez y coordinar con el grupo de `category9`.

### Flujo principal POS
`terminal_login.php` (autentica la terminal) → `category11/terminal.php` (menú) → `opening/index.php` (開店) → `pos/index.php` (carrito) → `pos/complete.php` (cobro) → `pos/sendPosData.php` (registra venta y puntos) → `checkout/index.php` → `checkout_frm.php` → `checkout_register.php` (精算) → `sumup/*` (informes).

### Reglas de negocio no obvias
- `tb_order_sub` guarda el detalle del pedido separado por tabuladores y `tb_order_main` los totales; `sendPosData.php` numera con `hatuban` (`FOR UPDATE`) en una transacción.
- `witch_price` 1 a 4 (básico/socio/especial/oferta) se resuelve en el cliente: llega en `price` el precio final.
- `used_points` descuenta `tb_customer_point` (misma lógica que A.2); `how_to_pay` 1 efectivo, 2 tarjeta, 3 商品券. Modo 3 (rehacer) marca cancelación en el registro anterior y crea uno nuevo; modo 4 anula todo.
- Día comercial: de 0:00 a 4:59 cuenta como el día anterior (`TODAY_UNTIL = 5`).
- Impuesto en `tb_c2_shopping.tax` (por defecto 5 al autoalta la tienda).
- Borrado lógico `disp_flg = '9'`; `flg = '1'` en categorías disponibles.
- Impresora por terminal (`tb_pos_terminal.tb_printer_cd`).

### Servicios viejos y reemplazo
| Viejo | Reemplazo Laravel 13 |
|---|---|
| `pos_session.php` + UA `CRMPOS-` | Middleware `PosTerminalAuth` + guard `pos`; tabla `terminals` con `terminal_no` |
| `hatuban` con `FOR UPDATE` | Secuencias de PostgreSQL o `DB::transaction` + `lockForUpdate` |
| `sendPosData.php` en texto plano | Controlador con transacción y Form Request; conservar `complete`/`Error:` solo si la tablet lo exige |
| CSV con archivos temporales (`csv_file_out`/`csv_file_del`) | `StreamedResponse` con `fputcsv` |
| `$posConnect` (BD POS por tienda) | Conexión dinámica por `pos_domain` (config en runtime) o API; contraseña en `.env` |
| jQuery 1.4.4 / jQuery UI | Blade + Alpine/Livewire o React + Vite según decida el módulo |
| Gráficos de `sumup_graph` | Chart.js (resolver `setGraphN` faltantes) |

## SNS (`system/category12/`)

Qué hace: el administrador de tienda conecta su cuenta de Twitter o Facebook (OAuth) y publica novedades desde el CRM con texto e imagen. **Las dos APIs usadas ya no existen** (Twitter OAuth 1.0a v1.1 y SDK viejo de Facebook); BROKEN-FILES lo marca como pendiente de consulta y no se ejecutó para no publicar.

| Archivo | Título (ja) | Tipo | Qué hace | Tablas | Notas |
|---|---|---|---|---|---|
| `index.php` | (redirección) | fragmento | Si `tb_shop.sns` (JSON) tiene tokens va a `postSNS.php`; si no, a `connect.php` | L tb_shop | Requiere `session.php` |
| `connect.php` | SNS連携 | config | Botones para conectar Twitter/Facebook | L tb_base_system, tb_shop | |
| `postSNS.php` | SNS投稿 | alta | Formulario de publicación | L tb_base_system, tb_shop | |
| `ajax_postSNS.php`, `twitter/ajax_postSNS.php`, `ajax_uploadImage.php` | | ajax | Publica / sube imagen | — | |
| `unregistSNS.php` | | registro(POST) | Desvincula (vacía `tb_shop.sns`) | E tb_shop | |
| `twitter/index.php`, `twitterLogin.php`, `callback.php`, `ajax_postTwitter.php`, `util.php` | | config/ajax | OAuth 1.0a (HMAC-SHA1); guarda `access_token` y `access_token_secret` en `tb_shop.sns` | E tb_shop | Constantes `CONSUMER_KEY`/`CONSUMER_SECRET` |
| `facebook/index.php`, `callback.php`, `ajax_getExtToken.php`, `ajax_postFacebook.php`, `ajax_postFacebookPage.php` | | config/ajax | OAuth 2 y publicación en Página; token en `tb_shop.sns` (`facebookAccessToken`) | E tb_shop | `FACEBOOK_APP_SECRET` |
| `lib/twitteroauth/*`, `lib/facebookoauth/*`, `autoload.php`, `util.php` | | fragmento | Librerías incluidas | — | No migrar |
| `test.php` | | prueba | Exige `inc/left_navi.php` (no existe) | L tb_base_system | Fatal; borrar |

Reemplazo: **no portar el OAuth viejo**. Si se mantiene, Laravel Socialite (Graph API actual) y API v2 de X; tokens cifrados (cast `encrypted`) en una tabla `shop_social_accounts` en vez de JSON en `tb_shop.sns`; publicar con Jobs. Prioridad baja.

## Utilitarios (`exp_cmd/`, `cron/`, `version/`, `instagram/`, `ipad/`, `ptag/`, `air/`)

| Archivo | Tipo | Qué hace | Tablas | Notas |
|---|---|---|---|---|
| `exp_cmd/verUp/versionUP_from2.2to2.3.php` … `from2.90to2.91.php` (9), `fuyouKoumokuCheck.php`, `verTest.php` | cron/CLI | Actualizadores de esquema/datos entre versiones del CRM (v2.2 a 2.91); envían correo con el resultado (`inc/mail.php`) | L/E varias (`tb_hp_domain`, esquema) | **Histórico**: Laravel usa migraciones; no portar. `pg_field_num()` con 3 argumentos falla en PHP 8 (PENDING) |
| `exp_cmd/cron/get_shukei_data.php`, `get_shukei_data2.php` | cron/CLI | Consolidación mensual de ventas por tienda | L tb_hp_domain, tb_shop | Sin autenticación; pasar a comando `crm:shukei {year?} {month?}` + Scheduler mensual |
| `cron/regenerateCronJob.php` | cron | Regenera el crontab desde los envíos programados (`cSendSchedule`): mail_mag, `sendPointShikkou*Mail`, `setRegisterPoint` (cada 5 min), `setPaidMemberPoint` (10:00) | tb_mail_* | Reemplazo: Scheduler (`schedule:run` por minuto) + Jobs; no escribir crontab desde PHP (necesita `crontab` y `PHP_CLI_PATH`, PENDING 1.1) |
| `version/*`, `air/*`, `ptag/index.php` | endpoint | Ver A.6 | tb_url_list, url_transfer_data, tb_information | Migrar `version` y `ptag`; `air` condicional |
| `instagram/index.php`, `ipad/*` | ajax/pantalla | Ver A.6 | — | No migrar |

## Tablas de BD del grupo

| Tabla | Para qué | Submódulos que la usan | Notas (columnas clave) |
|---|---|---|---|
| `tb_pos_terminal` | Terminales POS | pos_session, terminal | `cd`, `terminal_no` (= ID del UA), `tb_shop_cd`, `pos_no`, `tb_printer_cd`. Equivale a `terminals` (faltan `terminal_no`, `pos_no`, `printer_cd`) |
| `tb_shop` | Tiendas | casi todo | `pos_domain` (host BD POS), `sns` (JSON de tokens), `kaishamei`, `disp_flg` |
| `tb_customer`, `tb_customer_sub`, `tb_customer_point`, `tb_point_log` | Clientes y puntos | api, appview, mpa, pos | `id` (público) distinto de `cd` (interno); `login_pass` en texto plano; `touroku_kubun='2'` socio; `synchronize_flg` |
| `tb_c2_shopping`, `tb_c2_sum_master`, `tb_c2_catalog`, `tb_c2_category`, `tb_c2_catalog_categorys` | Configuración de caja, cierres y catálogo POS | pos, checkout, opening, item, sumup, config | **Borradas en 2020**; confirmar si existen en producción |
| `tb_order_main`, `tb_order_sub`, `tb_yoyaku_order_main` | Pedidos/ventas POS | pos, checkout, sumup | Detalle con tabuladores |
| `tb_flg_category`, `tb_flg_category_joint`, `tb_waribiki`, `tb_waribiki_joint`, `tb_dispaly_systems` | Banderas, descuentos y su alcance por tienda | item, config, place | `dispaly` es la ortografía real |
| `tb_place`, `tb_information_place` | Mesas/zonas y su estado | place, placeview | |
| `tb_yoyaku`, `tb_yoyaku_config`, `tb_yoyaku_uketsuke_day`, `tb_yoyaku_koumoku_master`, `tb_yoyaku_koumoku_category`, `tb_staff_works`, `tb_setsubi`, `tb_koumoku`, `tb_manage_words`, `tb_reserve`, `tb_shop_work`, `numbered_ticket` | Reservas y configuración | yoyaku, r, reserve, appview | `moushikomi_time` no existe (usar `start_time`) |
| `tb_staff` | Personal | yoyaku, sumup, air | |
| `tb_mycoupon`, `tb_mycoupon_master`, `tb_mycoupon_used`, `tb_c3_anketo2_sub`, `tb_anketo_present` | Cupones y cuestionario del cliente | appview | |
| `tb_stamp_config`, `tb_raiten_rireki`, `tb_beacon_history` | Sellos, visitas y beacon | appview, mpa, yoyaku | |
| `tb_photo_album` | Álbum de fotos | mpa_albumUpdate | |
| `sales_items` | Productos canjeables por puntos | api/mobile_shop | `is_exchangeable_of_point`, `number_of_points_exchanged`, `available_shop_cds` (array), `hyouji_flg`; ya usa Eloquent en el legacy |
| `contents_master` | Configuración clave/valor | api, mpa, appview, yoyaku | `category`, `field`, `int_data`, `text_data`; `mypage_config` es PHP serializado |
| `admin_master` | Ajustes globales | mpa, r, appview | `cd = 1`: `application_use_flg`, `system_mail` |
| `tb_url_list`, `url_transfer_data` | Enlaces cortos y registro de clics | ptag | |
| `tb_hp_domain`, `tb_information`, `tb_base_system(_child/_grandchild)` | Dominio, avisos y menú | exp_cmd, air, yoyaku | |
| `hatuban` | Numeración (`koumoku`, `kazu`) | pos, appview, mpa, yoyaku | `SELECT … FOR UPDATE` |

## Propuesta Laravel 13

**Rutas y controladores** (prefijos que mantienen las URLs legacy):
- `routes/api_legacy.php`, `Route::prefix('api')`: `POST auth` (`MobileShopAuthController@issue`); grupo `middleware('jwt.mobileshop')`: `customer`, `shop`, `point`, `mobile_shop/{customer|fetch_paid_member|send_unpaid_member_status|passcode|point|point_use|point_exchange_item|sales_items}` (con `{id?}` en customer y sales_items); `Route::any('mpa_*.php')`.
- `routes/pos.php`: `Route::prefix('system/category11')->middleware('pos.terminal')`; `system/terminal_login.php`. Mantener `.php` solo donde llame un cliente externo (`sendPosData`, `ajax_getCatalogData`); el resto puede modernizarse con redirecciones 301.
- `routes/reserve.php`: `r/*` y `reserve/*` (alias) y `getReservationWaitTime`. `routes/appview.php`: `appview/*.php`. Además `version/{canal}/v.php`, `version/crm_version.php`, `ptag/{id}`.
- Controladores: `PosTerminalController`, `PosSaleController`, `PosCheckoutController`, `PosOpeningController`, `PosItemController`, `PosPlaceController`, `PosSumupController`, `YoyakuController`, `AppviewCouponController`, `AppviewMemberController`, `QuickReservationController`, `MpaController`, `SnsController` (opcional). Servicios: `PointService`, `ReserveService` + `TimetableService`, `MobileShopExtlinkClient` (`Http::`), `PosDatabaseManager`.
- Jobs/comandos/Scheduler: `crm:shukei`, `crm:sync-customers-to-pos`, `crm:set-register-point` (cada 5 min), `crm:set-paid-member-point` (10:00), `crm:send-point-shikkou-mail` y variantes, envíos programados vía Scheduler dinámico (sustituye `regenerateCronJob`); correo con `Mailable` en cola.
- Policies/Gates: `PosAccess` (terminal autenticada + tienda), global scope por `shop_cd` de la terminal, `ManageSns`. `throttle` en `/api`.
- Variables de entorno: `MOBILESHOP_API_USER`, `MOBILESHOP_API_PASS`, `MOBILESHOP_EXTLINK_PASS`, `POS_DB_PASSWORD`, `keys/private.pem`, `keys/public.pem` (mismos valores que producción).

**Reutilizable de CRMSystem** (C:\Users\User\VivaTech\CRMSystem\public_html): `app/Models/Terminal.php` (ampliar con `terminal_no`, `pos_no`, `printer_cd`), `Shop`, `Customer`, `CustomerPoint`, `Visit`, `Coupon`, `CouponCustomer`, `Survey*`, `StampSetting`/`CustomerStamp`; enums `PointMovement`, `StampMovement`, `CouponUsage`; `Doc/database/TABLAS_MODULO_CLIENTES.md`, `ESQUEMA_BD_MODULO_CLIENTES.md`, `TABLAS_MODULO_PROMOCIONES.md`. **No existe aún:** modelos de POS, reservas, ventas, `sales_items`, `contents_master`, `hatuban`.

**Orden recomendado:**
1. `terminals` ampliado + middleware POS (A.1): bloquea todo lo demás del POS.
2. API JWT `/api/*` (A.2) con pruebas de contrato (formato, HTTP 200 en error, GET con cuerpo). Depende de clientes, puntos y `sales_items`.
3. `PointService` común (API, appview, POS).
4. `appview/*` y `r/*`/`reserve/*` (A.4, A.5): dependen de clientes, cupones, sellos, reservas y puntos.
5. `mpa_*`, `version/*`, `ptag` (endpoints pequeños, A.3 y A.6).
6. Reservas POS (`yoyaku`) coordinadas con el grupo de reservas (`category9`, `category3/reservation`).
7. POS completo (item, pos, checkout, sumup) solo si se confirma su uso y se recrean las `tb_c2_*`.
8. SNS al final y solo si hay demanda.

Dependencias con otros grupos: clientes (`tb_customer`, rangos, visitas, cupones, sellos, puntos), tiendas y personal (`tb_shop`, `tb_staff`, `tb_base_system`), reservas de `category9`/`category3`, correo/envíos programados (cron), configuración global (`contents_master`, `admin_master`).

## Riesgos y dudas

1. **Contrato POS por User-Agent** (INTEGRATIONS.md): el único secreto real es conocer el ID de terminal y el cliente (app POS) está fuera del repo; un cambio a ciegas deja tablets sin servicio. Preguntar a Japón: qué app envía `CRMPOS-<id>`, si alguien usa `unique_id`+`unique_key`, cuántas terminales hay en producción.
2. **Tablas `tb_c2_*` borradas en 2020** (MISSING-TABLES.md, `Version20200615093300`): category11 (POS, checkout, sumup, item, config) no funciona con el respaldo; `sumup_graph.php` está roto en master (500 y `setGraph1…12` sin definir). PENDING 2b: confirmar si category11 se usa; si no, migrar solo yoyaku y utilitarios.
3. **API de la tienda móvil con cliente invisible:** HTTP 200 en errores, GET con cuerpo JSON, erratas en nombres, `mobile_shop/customer` consulta varias BD (`DOMAIN_LIST` está vacía en el código: viene de configuración), SQL concatenado en `api/customer.php` y `CustomerForMobileShop`, `passcode` en texto plano, `mpa_*` sin autenticación, `mpa_customerSync` abierta, IDOR por `customer_cd` en `appview`.
4. **Claves y dominios externos** (INTEGRATIONS.md): API extlink (`MOBILESHOP_EXTLINK_PASS`, `MAIL_ADD_POP3_PASS`), `ssl-weblink.jp`, `b.g-api.net`/`b-develop.g-api.net`, `extlink.crm-s.net/air/crm.air`, BD POS por tienda (`POS_DB_PASSWORD`). Copiar los valores al `.env` de producción antes del corte.
5. **md5(login_pass) en URL y cookie:** `mpa_getTopBanner` (enlaces a `hp/user/control.php`, dependencia de `hp/`, solo mención), cookie `UID` de `ptag`; `appview/member_login.php` compara `login_pass` en texto plano. Bloquea migrar a `password_hash`.
6. **Pantallas rotas** (BROKEN-FILES.md): `yoyaku_new_register.php` (columna `moushikomi_time` y redirección inexistente), `category12/test.php`, `placeview/index.php` (`BOX_FLG_COMPLETE` x3), `ajax_yoyaku_koumoku_new.php` y similares requieren servidor POS externo (500 sin él); `api/mobile_shop/*` y `sendPosData` sin pruebas con datos reales (respaldo sin datos).
7. **Twitter/Facebook:** las APIs usadas ya no existen y nunca se ejecutaron; decidir descartar o rehacer con Socialite/API v2.
8. **Duplicados y obsoletos:** `r/` frente a `reserve/` (9 de 9 archivos divergen) y `category11/yoyaku` frente a `category9/yoyaku`; `exp_cmd/verUp` es histórico; `cron/regenerateCronJob` requiere `crontab` y `PHP_CLI_PATH`; `instagram/` e `ipad/` son obsoletos.
9. **Imágenes:** `mpa_albumUpdate` usa ImageMagick (`/usr/bin/convert`) y `hp/uploads/...` para banners/portadas; sustituir por `intervention/image` y `Storage`, pero el cliente espera URLs `http://<host>/hp/uploads/c6/...?r=<mtime>`.
10. **Seguridad al probar:** no abrir `system/login.php?activation=1`, `create_customer.php` ni `create_shop.php` (no se usaron en este análisis).
