# Para Antony y Japón: informe consolidado de la migración

S5, 2026-11-27, sobre `laravel13` (8e0f4e2, 931 pruebas en verde); §3 y §5 al día con las respuestas de Antony (05cca12). Fuentes: secciones «Decisiones» y «Pedidos» de `Doc/coordinacion/S0…S5-*.md`, los diseños de `Doc/diseno/` y `Doc/qa/`. Una línea por punto: **qué** — sesión, archivo de origen.

Base común (Antony, 2026-11-26): misma BD (`crm2019`) y misma UI que el legacy; `hp/`, `admin/`, POS (category11), SNS (category12) y touchcation quedan fuera.

## 1. Cambios de comportamiento: fallos del legacy que Laravel corrige

### Núcleo y configuración (S1)
- CRM端末管理 「設定する」 no guardaba nada (comillas en la clave `tb_access_mapping[0]['cd']`, UPDATE a `cd = 0`); ahora guarda tienda y On/Off y da de baja las シリアル marcadas — S1, `diseno/nucleo-base.md`.
- `redirectToSslPage` ya no baja de HTTPS a HTTP — S0, `coordinacion/S0-coordinador.md`.
- `select.php` sin efectos secundarios en cada carga (sync POS, normalizar fechas, insertar `tb_koumoku`/`tb_kengen_contents`, お知らせ de `b.g-api.net`) — S1, `coordinacion/S1-nucleo.md`.
- La sesión guarda una huella HMAC de `login_pass`, no la clave en claro; login con límite de 20 intentos/min — S1, `coordinacion/S1-nucleo.md`.

### Clientes, ventas y reservas (S2)
- Permisos comprobados en el servidor (alta, edición, borrado, CSV); el legacy solo ocultaba botones — S2, `coordinacion/S2-clientes.md`.
- `r/ajax_reserveDelete.php` solo anula reservas del socio de la sesión (el legacy anulaba cualquier `cd`) — S2, `diseno/clientes-ventas-reservas.md`.
- 予約一覧 con varias tiendas muestra 「全N件」 (vacío en el legacy por una variable sin definir) — S2, `diseno/clientes-ventas-reservas.md`.
- 売上データ作成 lista el personal de la tienda elegida (el legacy fijaba `shop_cd = 1`) — S2, `coordinacion/S2-clientes.md`.
- Categoría de menú vacía = NULL (el legacy guardaba 0 y ocultaba los menús) — S2, `coordinacion/S2-clientes.md`.
- Si falta `tb_felica_mode cd=2`, se crea al guardar (el legacy no guardaba, sin aviso) — S2, `coordinacion/S2-clientes.md`.
- El filtro de メール desde informes lo arma el servidor (el legacy aceptaba SQL del navegador) — S2, `coordinacion/S2-clientes.md`.

### Promoción (S3)
- Detalle y ranking de cupones: `kaiseki_disp()` y `couponKubunDisp()` fallaban (`pg_query(['connect'], …)`) con usos o cupones de メルマガ — S3, `diseno/promocion.md`.
- フォロー: los días se validan en el servidor (el legacy los validaba en JS y los metía en el SQL) — S3, `diseno/promocion.md`.
- Push 停止/起動/修正 solo para master (en el legacy `$kengenNo` no estaba definido) — S4/S3, `coordinacion/S4-contenido.md`.
- `aterna_a4_12.php` (`kaiin_master` → `tb_customer`), `daycsv_download.php` vacío, consultas de `anketo2` y gráficas de Google retiradas (→ `Chart.min.js`) — S3, `diseno/promocion.md`.

### Contenido y terminales (S4)
- 電子看板: 最後に追加 usaba una columna inexistente; `movie_contents_category` borraba de `contents_category`; franja `hh24:mm` (mes en lugar de minutos) — S4, `diseno/contenido-terminales.md`.
- ICカード管理: OFFSET negativo, paginación que perdía la búsqueda, OR ignorado, «フェリカ未登録» enviado siempre, 削除 que enviaba otro formulario, fechas vacías como 1970 — S4, `diseno/contenido-terminales.md`.
- 職種 vacío con una sola tienda; borrado de imagen que dejaba derivados; 【応募資格】 del XML — S4, `diseno/contenido-terminales.md`.

### Operaciones e integraciones (S5)
- Calendarios que saltaban a **enero de 1970** (営業日設定, 勤怠): no se reproduce — S5, `qa/diferencias-operaciones.md`.
- 勤怠管理: editar/borrar marcas y los ajustes del terminal ya exigen master (el legacy solo ocultaba los botones); la edición actualizaba cualquier marca por `cd`; la ステータス mostraba marcas borradas; en la UI clásica 削除 fallaba (sin jquery.alerts); el CSV sumaba 24 h a 外出/戻り según otra marca — S5, `coordinacion/S5-operaciones.md`.
- `appview/coupon_control.php act=use` daba **500** (constante `N` sin definir) y no usaba el cupón; aquí lo usa — S5, `tests/Fixtures/integraciones/README.md`.
- `appview/member_complete.php`: el INSERT nombra 8 columnas que no existen en la BD y **no guardaba nada**, pero mostraba 登録完了; aquí guarda — S5, `coordinacion/S5-operaciones.md`.
- `appview/member_regist.php` con sellos de bienvenida (`touroku_bonus` > 0) daba un **fatal** (`new Hatuban`) y no daba de alta; aquí sí — S5, `coordinacion/S5-operaciones.md`.
- Push «ご登録ありがとうございます» filtrado por `cd = <login_id>` (**no llegaba a nadie**); `appview/pushMessage.php` no existe (**ningún push salía**); aquí salen con `CustomerPushService` — S5, `coordinacion/S5-operaciones.md`.
- `mpa_setraiten` con socio válido registraba la visita y daba 500; aquí responde `ok` — S5, `coordinacion/S5-operaciones.md`.
- `setRaitenDetail` JSONP de error roto por precedencia de operadores; se emite el formato correcto — S5, `coordinacion/S5-operaciones.md`.
- `/api/<desconocido>` daba 500 sin cuerpo; ahora 404 JSON — S5, `coordinacion/S5-operaciones.md`.
- 日別/月別シフト: el master puede elegir otra tienda (el legacy volvía a la primera); 月別 pinta solo los días del mes (el legacy, siempre 31 filas) — S5, `coordinacion/S5-operaciones.md`.
- スタッフ: el 所属部署 libre ya no se duplica ni se vacía; los teléfonos se guardan sin guiones (el legacy validaba una versión y guardaba otra); el ID y el correo se revalidan al recuperar — S5, `coordinacion/S5-operaciones.md`.
- Baja de un empleado: libera su tarjeta (`tb_card`, `felica_no`) sin borrar su asistencia — S5, `diseno/operaciones-integraciones.md` §7.
- Sin inyección SQL en `change_syukkin2.php` (columnas por día con lista blanca) — S5, `coordinacion/S5-operaciones.md`.
- XSS: los valores del formulario de registro y el `domain` de appview salen escapados — S5, `coordinacion/S5-operaciones.md`.

## 2. Riesgos de seguridad que se mantienen por contrato (con propuesta)

- `login_pass` en texto plano en `tb_staff`, `tb_customer`, `kintai_tb_config.password` y el passcode de la tienda móvil — S0/S1/S2/S5, `coordinacion/S0-coordinador.md` · **Propuesta:** pasar a hash cuando el legacy deje de compartir la BD, junto con `appview`, `ptag` y `mpa_*`.
- `tool/api getStaffList` devuelve `tb_staff.*` con `login_pass` — S5, `coordinacion/S5-operaciones.md` · **Propuesta:** quitar la columna de la respuesta si la app de escritorio no la usa.
- CTI (`ctiWindow`, `customerDetailHtml`, `jGetCustomerData`) sin autenticación — S5, `diseno/operaciones-integraciones.md` §7 · **Propuesta:** token firmado por terminal y throttle.
- `mpa_*` y `appview/*` sin autenticación: **IDOR por `customer_cd`** (cupones, sellos, QR, ID de socio, edición de datos) — S5, `diseno/operaciones-integraciones.md` §7 · **Propuesta:** token firmado emitido en el login de la app y throttle.
- `md5(login_pass)` en URL (`mpa_getTopBanner`) y en la cookie `UID` de `ptag`; token de reserva `md5(cd:touroku_date)` — S5/S2, `diseno/operaciones-integraciones.md` §7 · **Propuesta:** enlaces firmados con caducidad.
- `member_complete.php` acepta el alta sin token si no hay sesión (`null == null`) — S5, `tests/Fixtures/integraciones/README.md` · **Propuesta:** exigir el token siempre.
- El QR de socio solo está ofuscado (`LegacyPassword`, clave fija «わたしのかぎ»), no cifrado — S5, `app/Services/Integraciones/Appview/LegacyPassword.php` · **Propuesta:** firma HMAC si se cambian los lectores.
- `tool/api` autentica con una clave fija (`CRM_API_KEY`) por parámetro — S5, `diseno/operaciones-integraciones.md` §6.4 · **Propuesta:** rotarla y pasarla por cabecera.
- La API extlink de la tienda móvil (有料会員) se llama sin verificar el certificado TLS (`withoutVerifying()`, como `CURLOPT_SSL_VERIFYPEER false` del legacy). Fuera de producción no sale ninguna petición (interruptor `MOBILESHOP_EXTLINK_ENABLED`) — S5, `app/Services/Integraciones/MobileShop/HttpMobileShopClient.php` · **Propuesta:** verificar el certificado en cuanto se confirme que el servidor de extlink tiene uno válido.
- `signage/view` (reproductor) es público — S4, `diseno/contenido-terminales.md` · **Propuesta:** limitar por terminal registrado.
- Al borrar un display de 電子看板 sus materiales quedan huérfanos (como el legacy) — S4/S0, `coordinacion/S4-contenido.md`.

## 3. Decisiones de Antony

Respuestas del 2026-11-27 (`coordinacion/S0-coordinador.md` y `MIGRACION-LARAVEL13.md` §8b).

### Firmes

- **新着情報 (M9)** se migra por paridad; **DM発行** ya está; **スマトピ (F8)** sigue oculto — S3, `coordinacion/S3-promocion.md`.
- **勤怠**: se migra la gestión, que es local y sin dependencias externas: `category5/kintai` (S5) y `category5/working` (S2), sobre los modelos de S5. El fichaje desde el quiosco queda fuera, con touchcation — S5/S2, `coordinacion/S5-operaciones.md`.
- **Login de `hp/user`**: fuera (`hp/` completo). La reserva web entra con el token desde la app; el enlace de `mpa_getTopBanner` queda como deuda (§5) — S0/S5, `coordinacion/S0-coordinador.md`.

### Provisionales (hasta que conteste Japón)

- **BD `admin` de `signage/`**: se sigue con el esquema mínimo `signage-admin.sql` y se reemplaza cuando llegue el volcado real — S4/S0, `diseno/contenido-terminales.md`.
- **GoTel (`signage/mail`) y PDF (`signage/pdf`)**: en pausa porque dependen de esa BD; rutas preparadas con la URL legacy — S4, `diseno/contenido-terminales.md`.
- **ビーコン**: en pausa (que no responda en desarrollo no prueba que esté muerto en producción); solo modelo y conexión `beacon`, sin pantallas — S1/S4, `diseno/contenido-terminales.md`.
- **Push**: se mantiene el GET a `b.g-api.net` como el legacy; FCM se decide después — S3, `diseno/promocion.md`.

### Pendientes de Japón

- **Volcado de la BD `admin`** de `signage/` (producción) — S4/S0.
- **¿`b.g-api.net` sigue activo en producción?** De eso dependen el push (S3) y las llamadas de `appview` a g-api (S5).
- **¿Qué módulos usan los clientes reales?** Decide lo que se prueba primero y lo que queda oculto.
- **勤怠 集計: ¿qué deben mostrar 普通残業, 休日残業 y 休日出勤?** El legacy nunca los calculó — S2, `coordinacion/S2-clientes.md`.
- **勤怠 帳票: ¿休出勤合計 sigue siendo 外出×休憩?** (fórmula del legacy). Mientras tanto, 帳票 queda apagado por defecto (`CRM_WORKING_REPORT`) — S2, `coordinacion/S2-clientes.md`.

### Siguen pendientes

- **`exp_cmd/cron/get_shukei_data*.php`**: escribe `tb_crm_check_operation` en la BD de VIZAN (203.183.91.218) con la clave del POS; no se sabe quién lo llama — S5, `diseno/operaciones-integraciones.md`.
- **Cliente Adobe AIR** (`air/`): ¿sigue en uso? (hecho igual por contrato) — S5, `diseno/operaciones-integraciones.md`.
- **`DOMAIN_LIST` y credenciales** de las BD que consulta `mobile_shop/customer` — S5, `diseno/operaciones-integraciones.md` §7.
- **Pantallas sin enlace** (`customer_level`, `school*`, `mail_comment`, `customer_new_complete`): se reabren si Japón las usa — S2, `coordinacion/S2-clientes.md`.
- **Worktree `CRMSystem-quiosco` y rama `l13/quiosco`** sin uso: ¿se borran? — S0, `coordinacion/S0-coordinador.md`.
- **Hash de contraseñas** (ver §2): decisión conjunta con Japón — S1/S5, `diseno/operaciones-integraciones.md` §7.

## 4. Descartados con motivo

- touchcation, `category7/kiosk/` y クイック予約 (`numbered_ticket`, no está en el backup): no se usan — S0, `coordinacion/S0-coordinador.md`.
- POS (`category11`, terminales `CRMPOS-*`, `reserve/` rama `tb_c2_*`, sync `mpa_customerSync`/`db_sync_all2`): fuera de alcance, interfaz `PosGateway` nula — S0/S2/S5, `coordinacion/S0-coordinador.md`.
- SNS (`category12`) — S0, `coordinacion/S0-coordinador.md`.
- `hp/` y `admin/`; category4 entero y lo de category6 que solo leía `hp/` (topics, header, freepage, 新着情報 de tienda, フォトギャラリー de catálogo): sin consumidor — S4, `diseno/contenido-terminales.md`.
- `appview/introduction.php`, `detail_view.php`, `reservation.php`: leen la BD `admin` (y la reserva rápida) — S5, `diseno/operaciones-integraciones.md`.
- `appview/anketo2_register.php` (include; directo da fatal) y `ms_paid_member_*` (pruebas con cliente fijo) — S5, `diseno/operaciones-integraciones.md`.
- `version/<canal>/v.php` y las 6 APK (`jp.CrmShWeb`): touchcation — S0/S5, `diseno/operaciones-integraciones.md`.
- `mpa_registCustomer.php`: archivo vacío — S5, `diseno/operaciones-integraciones.md`.
- category5: `change_syukkin2.php`, `schedule.php`, `shift.php` y CSV temporales (duplicados, sin enlace o reemplazados) — S5, `coordinacion/S5-operaciones.md`.
- category5 カードリーダーモード設定 y 新規カード管理 (`config/felica_mode.php`, `card_new.php`, `card_staff_select.php`): solo sirven para fichar con FeliCa en touchcation, fuera de alcance (Antony, 2026-11-27: el fichaje desde el quiosco queda fuera). **Reversible** si Japón vuelve a traer touchcation: el diseño (rutas, tablas `tb_felica_mode`/`tb_card`) y el modelo `FelicaMode` siguen en el repositorio — S5, `diseno/operaciones-integraciones.md` §1.5.
- category0: editor de BD crudo, backup/export (→ `pg_dump`), scripts de arreglo e inicialización, plantillas de caducidad (ninguna pantalla las abre) — S1, `diseno/nucleo-base.md`.
- Clientes: `customer_export.php` (volcado arbitrario de tablas: riesgo), copias de `karute_*`/`stamp_use_register`, flujo `yoyaku_new_*` (roto), `model_reservation.php` (sin escritor), `csv_from_js`/`user_csv` (Flash/huérfano) — S2, `diseno/clientes-ventas-reservas.md`.
- Promoción: sistema de cupón-imagen (`coupon_*`, `coupon_bg_*`, `pit_coupon*`, sin enlace), flujo antiguo `mycoupon_issue`/`_hakkou`/`_rireki`, variantes muertas de mail_mag, `fix_visit_stats.php` (script peligroso), M10 (ya no hay importador) — S3/S0, `diseno/promocion.md`.
- Señalización: `signage/_tool.php` (chmod 777), `sample*.php`, micrositios de inmuebles y recetas — S4, `diseno/contenido-terminales.md`.
- Scripts de cron y CLI (envíos, caducidades, puntos): no son vistas; los reemplazan comandos del Scheduler — S3, `diseno/promocion.md`.

## 5. Deuda conocida

- **`mpa_getTopBanner` enlaza la banda マイページ (`mypage.png`, `mypage02.png`) a `hp/user/control.php`** con `md5` de usuario y contraseña. `hp/` queda fuera (Antony, 2026-11-27), así que en Laravel ese enlace apunta a una página que no se migra: o la sirve el legacy mientras conviva, o queda roto. Se mantiene por contrato con la app. **Propuesta:** cuando haya una マイページ en Laravel (o se decida quitarla), cambiar el destino del enlace y, de paso, pasar a un enlace firmado (§2) — S5, `public_html/app/Http/Controllers/Publico/Integraciones/MpaController.php`.
