# S4 — Contenido y terminales (マイページ, ホームページ, 端末管理, señalización)

**Modelo:** Sonnet 5.5, esfuerzo medio · **Carpeta:** `CRMSystem-contenido/` · **Rama:** `l13/contenido`
**Módulos:** マイページ管理 (`mypage`), ホームページ管理 (`web`), 端末管理 (`terminales`), señalización (`senalizacion`). **Es dueña de:** sus carpetas según `Doc/contratos/CONVENCIONES.md` (secciones 2 y 3).

## Modo autónomo

Trabajas sin pedir confirmación. Al terminar cada tarea: actualizar este archivo y seguir con la siguiente. Si falta un detalle menor, decide lo más simple que respete las convenciones, anótalo en "Decisiones" y sigue. Al terminar todo o bloquearte: `SendMessage` a "S0 Coordinador CRM" (una línea: qué hiciste, qué sigue, qué necesitas). Cada entregable terminado con pruebas en verde: `git add` de tus rutas, commit (mensaje en español) y `git push origin <tu rama>`; después avisa a S0, que integra en `laravel13`. No haces merge ni cambias de rama; nunca `--force`.

## Próximo paso — Esperar a S0 / Antony

Hecho (promocion, por reparto de carga): F6 アンケート/お客様アンケート, F7 来店履歴, M8 DM発行; permisos de mypage (QA). Pendiente: クリックカウンタ de anketo (M4 de S3); en espera de Antony: ビーコン, GoTel/PDF, volcado de `signage/`.

## Tarea anterior — Diseño (sin código, hecho)

Escribir `Doc/diseno/contenido-terminales.md`:
1. **Tabla de paridad:** una fila por cada vista del análisis (`Doc/analisis-legacy/04-web-y-mypage.md`; en `05-operaciones-terminales-reservas.md` solo la sección de category7 (端末管理) **sin** 情報端末管理 (`kiosk/`); en `08-quiosco-y-senalizacion.md` solo signage y digitalsignage): archivo legacy, título (ja), ruta Laravel (`admin.<modulo>.*`), controlador@método, tablas nuevas, estado (`pendiente` / `descartado: motivo`).
2. **Modelos sobre la BD del legacy** (`CONVENCIONES.md`, sección 0): qué `tb_*` usa cada vista, un modelo Eloquent por tabla (`$table`, `$primaryKey = 'cd'`, columnas tal cual, `disp_flg = 9`, `hatuban`) y sus relaciones. Sin tablas nuevas salvo que sea imprescindible (y compatible con el legacy).
2b. **UI:** cada pantalla reproduce el HTML y los CSS del legacy (mismas clases, mismos textos); anota qué JS del legacy necesita.
3. **Servicios viejos → Laravel 13:** cada uno con su reemplazo (Mail + cola, Scheduler, StreamedResponse, Storage, Policies…).
4. **Orden de implementación** por submódulo, con dependencias de otros grupos y estimación en puntos (1 punto ≈ media jornada).
5. **Pedidos** a S1 (núcleo) y a otros grupos.
Criterio de terminado: todas las vistas del análisis tienen fila; avisar a S0 y esperar su revisión. Mientras esperas, puedes preparar factories y seeders de tu grupo (en tus carpetas).

## Después (cuando S0 apruebe el diseño y el núcleo esté integrado)

Implementar submódulo por submódulo en el orden del diseño: migraciones, modelos, Form Requests, controladores, vistas Blade (textos en `lang/ja/<modulo>.php`), rutas en `routes/modulos/<modulo>.php`, menú en `config/menu/<modulo>.php`, pruebas Pest. Cada submódulo terminado: pruebas en verde (`bash scripts/en-docker.sh php artisan test`), filas en `hecho`, commit y push de tu rama, y aviso a S0.

**Sin `hp/`:** el sitio viejo no se migra. Marca como `descartado: sin consumidor` el contenido que solo leía `hp/`, salvo que S0 diga otra cosa; lo que leen la app (`api/mpa_*`, `appview`) o la señalización sí se migra. Imágenes de `hp/uploads/` → Storage, conservando o redirigiendo las URL que devuelven las APIs y que leen los reproductores (`hp/uploads/c7/display/`).
**Fuera de alcance:** touchcation y su gestión `category7/kiosk/` (情報端末管理, `tb_nextouch_*`): confirmado que no se usan. **Señalización:** `digital_signage` de category7 + los reproductores. Los reproductores (`signage/`, `digitalsignage/`) son dispositivos: mantener su contrato (AppKey `md5(HTTP_HOST + '<>' + rank)` incluida) o avisar a S0 antes de cambiarlo. `digitalsignage` **se mantiene** (Antony): su reproductor es Flash y se rehace en HTML5 con el mismo contrato (`?shop_cd=&display_cd=`, XML del programa, ruleta y tragaperras). En desarrollo no hay displays registrados: crea datos de prueba con seeders.

## Lee solo

- `CLAUDE.md`, `Doc/contratos/CONVENCIONES.md` (sección 0 primero), `Doc/analisis-legacy/README.md` (hallazgos transversales)
- Del análisis, por secciones (grep del título): `Doc/analisis-legacy/04-web-y-mypage.md`; en `05-operaciones-terminales-reservas.md` solo la sección de category7 (端末管理) **sin** 情報端末管理 (`kiosk/`); en `08-quiosco-y-senalizacion.md` solo signage y digitalsignage
- Para dudas de comportamiento: el código en `C:\Users\User\VivaTech\crm2-php8\public_html` (solo lectura) y `Doc/legacy-crm2/MANUAL-TESTS.md` (tu módulo)

## Pedidos


- S1: `@section('ui_version')` en el layout; decidir si `/system/inc/ajax_picupload.php` y `/system/uploads/` pasan al núcleo.
- S3: (M3) フォロー/自動 de push publican en `admin.promocion.push.create`; enlace 「クリック詳細」 de mailmag_list a `push.show?data_type=mailmaga`.
- S3: `MailController::family()/activeAc()` con anketo y 返信メール; marcar encuesta emitida en `MailRegistrar::register()`; edición de envío programado anketo; クリックカウンタ (M4).

- S1: `memory_limit` mayor para la suite (128 MB ya no alcanzan con 600+ pruebas; con 512M pasa).

- S3: servicio común de QR (`qr_img.php`); S5: relación cliente en `UrlTransferData`; S1: helper común de CSV SJIS-win.

Ver diseño §5. A S0: BD `admin` de `signage/` (no está en el esquema); GoTel/PDF y ビーコン (Antony). A S1: base de modelos legacy (`disp_flg`, `hatuban`, `ContentsMaster`, `AdminMaster`, `Shop`, `Terminal`), dueño de `tb_mobile_navi`, copia de JS/CSS de carpeta de módulo a `public/legacy/`, paquetes imagen/QR/PDF, conexión `beacon`, Policies.

## Decisiones

- `qr_img.php` en `routes/publico/promocion.php` (sin login: lo usan appview de S5). `qr_print_pdf.php` del legacy era HTML para imprimir: ahora PDF A4 con `LegacyPdf`. Push: 停止/起動/修正 solo master (en el legacy `$kengenNo` no estaba definido).

- `ventas` volvió a S2 (S0): mi 0cb4201 revertido en 741543e.
- DM: PDF con escritor mínimo propio (`DmLabelPdf`, fuente CID KozMinPro sin incrustar, como el FPDF del legacy) hasta que S1 fije paquete PDF; verificar una hoja impresa.

- `signage/`: Gate `senalizacion.manage` = solo master (el legacy entraba con `?s=` y sin menú del CRM); pantallas como vistas PHP planas convertidas del legacy (`SignageLegacyView` silencia avisos como producción); borrados por POST con CSRF; vídeo usa `movie_contents_category` (el legacy usaba la de imágenes).

- ペーパーサイネージ: título/id con form2Db (trim + mb_convert_kana KV), url con htmlspecialchars como cUrl → `{!! !!}`; editar no reactiva una tarjeta 停止. ICカード: borrado físico como el legacy, `felica_no` vía `CustomerFelicaService::detach`; ajax_del.php con una ruta por tipo y CSRF.

- Menús: solo entradas vivas; ガチャリン sin entrada (comentada en left_navi6); オススメ con etiqueta fija (el legacy usa el nombre de tb_mobile_navi); ペーパーサイネージ/ICカード/ビーコン con URL legacy hasta migrarse.

- コメント HOME de マイコレ: sin get_form2 (mobile_mycolle_edit.php no lo usa); `"`→`'` y serialize, se imprime con `{{ }}`. ガチャリン: premios en `field='rate'` (float_data %, int_data 本数), `prize_count` = contador del día.

- Texto de formularios: 電子看板, 求人 y 職種 usan `get_form()` → `LegacyText::form()`; オススメ y マイコレ `get_form2()` → `LegacyText::input()`; ガチャリン `form2Db`. Pendiente de que S1 integre `LegacyText`/`LegacyCheck`. `SignageFeedBuilder` sigue escapando para XML: los textos de get_form() no llegan escapados a la BD.

- マイコレ: main/thumbnail por `shop_cd` (lo que lee `mpa_corresponding`); cover se compacta moviendo imagen+enlace+banderas juntos; temporales en `hp/uploads/c6/apli_temp/`. 電子看板: 最後に追加 = max(rank)+1 y renumera; ▲▼ sin tocar `update_date`; los materiales guardan `tb_display_cd`; el borrado de un display no arrastra sus materiales: comportamiento del legacy que los deja huérfanos, se mantiene por paridad (S0) y queda para revisar en Japón.

- Vistas: `content` = interior de `<div class="main">`. 電子看板 sin pantallas de confirmación (el legacy no las tiene: `processing` con meta Refresh); 放映素材 usa `layouts.legacy-popup`. マイコレ renderiza solo la sección visible. Bugs corregidos/conservados: ver resúmenes en el commit de vistas.

- S0 (2026-11-26): touchcation y `category7/kiosk` fuera de alcance (no se usan). La AppKey `md5(host<>rank)` era de `signageApp` (touchcation): ya no aplica a S4.
- category4 entero y lo de category6 que solo leía `hp/`: descartado: sin consumidor. Vivo: マイコレ, 2 opciones de app, オススメ, 求人, ガチャリン.
- Base del legacy (Antony, 2026-11-26): misma BD y UI. Capa de datos sobre tablas nuevas quitada (d45a652); `SignageFeedBuilder` reescrito sobre las `tb_*` (f7deb45).
- XML: se escribe al guardar en `hp/uploads/c7/display/` como el legacy; forma idéntica pero escapada, materiales borrados omitidos, 【応募資格】 corregido.
- Config de マイコレ y ガチャリン en `contents_master` (modelo de S1) vía servicios de S4; `mypage_config` sigue con `serialize()` (lo leen `mpa_*`).

## Hecho

- 2026-11-28 (respuestas provisionales de Antony) GoTel provisional: `/signage/mail/index.php` お問い合わせ (`ContactController`, INSERT en `contact` + `ContactReceivedMail` en cola si `SIGNAGE_CONTACT_NOTIFY`) y `/signage/pdf/{invoice,receipt}.php` (`BillingPdfController` con `LegacyPdf`); `customer`/`shop`/`contact` marcadas PROVISIONAL en `signage-admin.sql`. ビーコン: solo modelos `Terminales\Beacon\{Beacon,BeaconReceiver}` en la conexión `beacon` (pendiente S1), sin pantallas; 4 filas «pendiente (provisional, espera a Japón)». Paridad S4: 70 hechos / 4 pendientes. Suite 955 verde.

- 2026-11-28 Quitadas de `routes/legacy/promocion.php` las rutas `system/inc/ajax_picupload.php` y `system/uploads/{file}` (las sirve el núcleo); `PushImageStore::promote()` usa `TempUploads` (sin GD propio). Paridad: reproductores `digitalsignage/` y portadas category6/7 marcados como hechos en `contenido-terminales.md` y `Doc/qa/paridad.md` (S4: 67 hechos, 7 pendientes ⏸ de Antony: ビーコン ×4, GoTel/PDF ×3). Suite 940 verde.

- 2026-11-28 `AnketoMailController` simplificado (rutas directas a MailController con defaults; emitida la marca MailRegistrar); filas F6/F7/M5/M7/M8 de `promocion.md` en hecho. Suite 931 verde.

- 2026-11-28 M5 プッシュ通知 (alta, listas, pasados, historial, comando follow push) y M7 QR/基本情報 (4 variantes, `QrImage`, `qr_img.php` público, QR印刷 con `LegacyPdf`); etiquetas DM con `LegacyPdf`. Suite 893 verde.

- 2026-11-27 F6 (anketo/anketo2 con `AnketoFamily`, envío vía MailController de S3), F7 (来店履歴 + 3 CSV SJIS-win + カードリーダ設定), M8 (DM + PDF A4×12 propio): suite 829 verde. Permisos de mypage (1685a84).

- 2026-11-27 `signage/`: CMS (端末, パッケージ, サイクル, コンテンツ, ファイル), reproductor `view/` + `ajax.php`, franja horaria corregida (`HH24:MI`); 集計 CSV con `LegacyCsv`. Suite 631 verde (con memory_limit 512M).

- 2026-11-27 ペーパーサイネージ (CRUD por `control.php`, QR con bacon, 集計 + CSV SJIS-win) e ICカード (terminales ▲▼, tarjetas, asignar con `CustomerFelicaService`, `FelicaCardService::unassign`); menú de category7 y título de オススメ desde tb_mobile_navi: suite 407 verde.

- 2026-11-27 Reproductor `digitalsignage` HTML5 (player, sondeos, cupones impresos con CouponService de S3, ruleta/スロット/インフォメーション): 14 pruebas; Gates `.enabled`.

- 2026-11-27 Menús laterales de category6/7 con Gates `mypage.mycolle` (app activa + 1035) y `terminales.paper-signage` (UI CRM2): suite 310 verde.

- 2026-11-27 オススメ (+push), 求人 + 職種 (+floormap), ガチャリン (`LotteryConfig`, filas `rate`/`prize_count` como lee S3), LegacyText por pantalla, pruebas con login real: suite 308 verde.

- 2026-11-27 マイコレ (5 secciones + `ajax_apliFileUpload.php`) y 電子看板 (素材/プログラム/ディスプレイ, ▲▼, XML al guardar): suite 214 verde.

- 2026-11-27 Modelos Mypage/Terminales sobre `tb_*`, `MypageConfig`, `SignageFeedBuilder` con modelos; 30 vistas Blade (マイコレ, 電子看板) que compilan.

- 2026-11-27 Estáticos de category6/7, `digitalsignage/` y `signage/` en `public/legacy/`; ruta `publico.senalizacion.display-xml` con 4 pruebas (suite 22 verde).

- 2026-11-27 Diseño rehecho sobre la BD del legacy; limpieza de la capa obsoleta; `SignageFeedBuilder` sobre `tb_*` con 8 pruebas (suite 10 verde).

- 2026-11-26 Diseño `Doc/diseno/contenido-terminales.md` (paridad, esquema con mapeo, servicios, orden 60 pts, pedidos).
