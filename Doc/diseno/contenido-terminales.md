# Diseño S4 — Contenido y terminales

> Sesión S4 · rama `l13/contenido` · 2026-11-26, rehecho el 2026-11-27 sobre la BD y la UI del legacy (CONVENCIONES §0). Módulos: `mypage` (マイページ管理), `web` (ホームページ管理), `terminales` (端末管理, category7), `senalizacion` (reproductores `signage/` y `digitalsignage/`).
> Fuentes: `Doc/analisis-legacy/04-web-y-mypage.md`, `05-…` (category7), `08-…` (signage, digitalsignage); columnas en `public_html/database/schema/pgsql-schema.sql`.
> **Alcance (S0, 2026-11-26):** touchcation y `system/category7/kiosk/` (`tb_nextouch_*`) **fuera**: no se usan. `hp/` no se migra: el contenido que solo leía `hp/` queda `descartado: sin consumidor`.

## 0. Criterios aplicados

- **Vivo** = lo lee algo que sigue: la app (`api/mpa_*`, `appview`), los reproductores (`signage/view`, `digitalsignage`) o código interno (`cKujibiki`, cupones). Todo lo demás de category4/6 solo lo leía `hp/` → descartado.
- Un solo código para lo que el legacy repite (las 5 pantallas マイコレ → un controlador por sección).
- Los `*_register`, `*_delete_register`, AJAX e includes no son filas propias: se listan dentro de la fila de su pantalla (columna «Archivo legacy»).
- Rutas de administración: `routes/modulos/<modulo>.php`, nombre `admin.<modulo>.*`. Rutas de dispositivos (reproductores): `routes/publico/senalizacion.php`.
- Misma BD y misma UI que el legacy: modelos sobre las `tb_*` (§2), HTML y CSS del legacy (§2b).

## 1. Tabla de paridad

> Rutas de los reproductores: `routes/publico/senalizacion.php` (`publico.senalizacion.*`), fuera del grupo `admin`. Columna «Tablas»: las `tb_*` del legacy que lee o escribe la pantalla.

### 1.1 マイページ管理 — `mypage` (category6)

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas (legacy) | Estado |
|---|---|---|---|---|---|
| `category6/index.php`, `inc/page_control.php`, `inc/left_navi6.php` | マイページ管理 | `admin.mypage.index` | `Mypage\MenuController@index` | — | pendiente (menú en `config/menu/mypage.php`) |
| `user_hp/mobile_mycolle_edit.php` + `ajax_apliFileUpload.php` | マイコレ管理 | `admin.mypage.app-images.edit` (`?section=`) | `Mypage\AppImageController@edit/update/upload/destroy` | `contents_master` (`tb_c6_user_hp`: `aplicover`, `aplibanner`, `mainimage`, `apli_home_thumbnail`, `mypage_config`) + archivos `hp/uploads/c6/<carpeta>/` | hecho (falta verlo con el layout y el login de S1) |
| `user_hp/mobile_mycolle_edit_coverflow.php` | カバーフロー画像設定管理 | `admin.mypage.app-images.edit` `section=cover` | ídem | ídem | hecho (falta verlo con el layout y el login de S1) |
| `user_hp/mobile_mycolle_edit_banner.php` | バナー画像設定管理 | ídem `section=banner` | ídem | ídem | hecho (falta verlo con el layout y el login de S1) |
| `user_hp/mobile_mycolle_edit_main.php` | HOMEメインイメージ管理 | ídem `section=main` | ídem | ídem | hecho (falta verlo con el layout y el login de S1) |
| `user_hp/mobile_mycolle_edit_thumbnail.php` | サムネイル画像管理 | ídem `section=thumbnail` | ídem | ídem | hecho (falta verlo con el layout y el login de S1) |
| `user_hp/mobile_hp_edit.php` (+ `ajax_change_pattern/pic`, `ajax_picupload`, `pic_config_function`, `base_color_function`) | マイページデザイン管理 | `admin.mypage.app-settings.edit` | `Mypage\AppSettingController@edit/update` | `contents_master` (`apli_customer_group_disp`, `apli_home_comment`), `admin_master` (L) | pendiente **parcial**: solo la opción 「アプリで顧客グループを表示」 (`apli_customer_group_disp`, la lee `appview`) y el comentario HOME (`apli_home_comment`, `mpa_corresponding`). Plantillas/colores/imágenes de diseño: descartado: sin consumidor (`hp/user`) |
| `user_hp/config.php`, `config_register.php` | ユーザーマイページ設定 | — | — | — | descartado: sin consumidor (`tb_hp_color` solo `hp/`) |
| `user_hp/topics.php`, `topics_register.php`, `topics_delete_register.php`, `inc/upload_topics.php` | トピックス管理 | — | — | — | descartado: sin consumidor |
| `user_hp/recommend.php`, `recommend_register.php`, `recommend_delete_register.php` | オススメ管理 | `admin.mypage.recommendations.*` (resource) | `Mypage\RecommendationController@index/store/update/destroy` | `tb_hp_recommend`, `tb_mobile_navi` (L), `tb_shop` (L), `hatuban` | pendiente (lo lee `appview/Util`; push al host de `admin_master`) |
| `user_hp/header.php`, `header_register.php`, `header_delete_register.php` | ヘッダ画像管理 | — | — | — | descartado: sin consumidor |
| `user_hp/my_freepage_list.php` | フリーページ管理 | — | — | — | descartado: sin consumidor |
| `user_hp/my_freepage.php`, `my_freepage_register.php`, `my_freepage_delete_register.php` | フリーページ記事管理 | — | — | — | descartado: sin consumidor |
| `shop_hp/mobile_hp_edit.php` (+ ajax y funciones) | 携帯HPデザイン管理 | — | — | — | descartado: sin consumidor (`hp/m`) |
| `shop_hp/config.php`, `config_register.php` | ショップページ設定 | — | — | — | descartado: sin consumidor |
| `shop_hp/whatsnew.php`, `whatsnew_register.php`, `whatsnew_delete_register.php` | 新着情報管理 | — | — | — | descartado: sin consumidor |
| `shop_hp/header.php`, `header_register.php`, `header_delete_register.php` | ヘッダ画像管理 | — | — | — | descartado: sin consumidor |
| `shop_hp/company.php` | 店舗詳細管理 | — | — | — | descartado: sin consumidor (los campos extra de `tb_shop` solo los mostraba `hp/m`; la ficha de tienda es de S1) |
| `shop_hp/company_detail.php`, `company_update.php`, `company_register.php`, `company_complete.php` | 店舗詳細 | — | — | — | descartado: sin consumidor |
| `shop_hp/freepage.php`, `freepage_register.php`, `freepage_delete_register.php` | フリーページ管理 | — | — | — | descartado: sin consumidor |
| `digital_catalog/digital_catalog_new.php` + `_frm`, `_register` | フォトギャラリー 新規登録 | — | — | — | descartado: sin consumidor (`hp/user/sc/digital_catalog`; `appview/photo_gallery` no lee estas tablas) |
| `digital_catalog/digital_catalog_list.php` + `ajax_check_star`, `ajax_check_hyouji`, `_update`, `_delete` | 掲載一覧 | — | — | — | descartado: sin consumidor |
| `digital_catalog/digital_catalog_tag.php` + `ajax_tag`, `ajax_search_tags` | カテゴリ管理 | — | — | — | descartado: sin consumidor |
| `recruit/recruit_new.php` + `recruit_register.php`, `ajax_category.php` | 求人情報新規登録 | `admin.mypage.job-postings.create/store` | `Mypage\JobPostingController@create/store` | `tb_recruit`, `tb_recruit_category` (L), `tb_shop` (L), `hatuban` | pendiente (lo leen los XML de `digital_signage`: `floormap.xml`) |
| `recruit/recruit_list.php` | 求人情報一覧 | `admin.mypage.job-postings.index` | `…@index` | `tb_recruit` | pendiente |
| `recruit/recruit_detail.php` | 求人情報詳細 | `admin.mypage.job-postings.show` | `…@show` | ídem | pendiente |
| `recruit/recruit_update.php`, `recruit_complete.php` | 求人情報修正 / 完了 | `admin.mypage.job-postings.edit/update` | `…@edit/update` | ídem | pendiente (el 完了 es el flash tras guardar) |
| `recruit/recruit_delete.php` | 求人情報削除 | `admin.mypage.job-postings.destroy` | `…@destroy` | ídem | pendiente |
| `recruit/recruit_category.php`, `_register`, `_delete` | 求人職種管理 | `admin.mypage.job-categories.*` | `Mypage\JobCategoryController@index/store/update/destroy` | `tb_recruit_category`, `hatuban` | pendiente |
| `kujibiki/kujibiki_config.php` | ガチャリン管理 各種設定 | `admin.mypage.lottery.edit/update` | `Mypage\LotterySettingController@edit/update` | `contents_master` (`tb_c6_kujibiki`) | pendiente (lo lee `cKujibiki` / `category3/mycoupon`, S3) |

### 1.2 ホームページ管理 — `web` (category4)

Todo category4 solo alimentaba `hp/` (sitio PC) → el módulo `web` queda **vacío** (sin rutas ni menú).

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas (legacy) | Estado |
|---|---|---|---|---|---|
| `category4/index.php` | ホームページ管理 | — | — | — | descartado: sin consumidor |
| `contents/sitemap.php`, `sitemap_edit.php`, `sitemap_confirm.php`, `sitemap_register.php` | サイトマップ管理 | — | — | — | descartado: sin consumidor |
| `contents/contents_list.php` | コンテンツ一覧・検索 | — | — | — | descartado: sin consumidor |
| `contents/contents_detail.php` | コンテンツ登録情報 | — | — | — | descartado: sin consumidor |
| `contents/contents_entry.php`, `_confirm`, `_register`, `_complete` | コンテンツ登録 | — | — | — | descartado: sin consumidor |
| `contents/contents_change_status.php`, `contents_delete_register.php` | コンテンツ情報 | — | — | — | descartado: sin consumidor |
| `contents/contents_recover_confirm.php` | コンテンツ登録情報 | — | — | — | descartado: sin consumidor (además roto en el legacy) |
| `contents/form_config.php`, `form_config_register.php` | コンテンツ検索項目設定 | — | — | — | descartado: sin consumidor (`tb_koumoku` de clientes lo gestiona S2) |
| `page/page_list.php` | ページ管理 | — | — | — | descartado: sin consumidor |
| `page/entry_list.php` | ページ管理 (記事一覧) | — | — | — | descartado: sin consumidor |
| `page/report.php` | ページ管理 (詳細) | — | — | — | descartado: sin consumidor |
| `page/report_entry.php`, `report_entry2.php`, `report_entry_confirm.php`, `entry_register.php`, `entry_delete.php` | 記事登録 | — | — | — | descartado: sin consumidor |
| `page/textlist_entry.php`, `textlist_entry_confirm.php` | テキストリスト登録 | — | — | — | descartado: sin consumidor |
| `pc_shop_hp/config.php`, `config_register.php` | PCホームページ設定 | — | — | — | descartado: sin consumidor |
| `pc_shop_hp/header.php`, `header_register.php` | ヘッダ管理 | — | — | — | descartado: sin consumidor |
| `pc_shop_hp/footer.php`, `footer_register.php` | フッタ管理 | — | — | — | descartado: sin consumidor |
| `pc_shop_hp/report_list.php` | — | — | — | — | descartado: archivo vacío |
| `category4/user_hp/*`, `shop_hp/*`, `recruit/*` | (copias) | — | — | — | descartado: copia antigua de category6 sin enlace |
| `category4/stamp/*` | スタンプ設定 (copia) | — | — | — | descartado: copia; lo vigente es `category3/stamp` (S3) |
| Enlace 不動産管理 | 不動産管理 | — | — | — | descartado: otro sistema (enlace externo) |

### 1.3 端末管理 — `terminales` (category7)

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas (legacy) | Estado |
|---|---|---|---|---|---|
| `category7/index.php`, `inc/*` | 端末管理 | `admin.terminales.index` | `Terminales\MenuController@index` | — | pendiente |
| `beacon/beacon_list.php` | ビーコン管理 | `admin.terminales.beacons.index` | `Terminales\BeaconController@index` | BD externa (conexión `beacon`) | pendiente — **bloqueado**: BD externa `b-develop.g-api.net` no responde |
| `beacon/beacon_detail.php` + `ajax_beaconSave.php` | ビーコン詳細 | `admin.terminales.beacons.show/update` | `…@show/update` | — | pendiente (bloqueado, ídem) |
| `beacon/terminal_list.php` | 受信端末一覧 | `admin.terminales.beacon-receivers.index` | `Terminales\BeaconReceiverController@index` | — | pendiente (bloqueado, ídem) |
| `beacon/terminal_detail.php` + `ajax_terminalSave.php` | 受信端末詳細 | `admin.terminales.beacon-receivers.show/update` | `…@show/update` | — | pendiente (bloqueado, ídem) |
| `card_signage/list.php` (+ `cPageControl`, descarga QR) | ペーパーサイネージ (カード管理) | `admin.terminales.paper-cards.index`, `…qr` | `Terminales\PaperCardController@index/qr` | `tb_url_list`, `hatuban` | pendiente |
| `card_signage/new.php` + `control.php` (`insert`) | ペーパーサイネージ 新規 | `admin.terminales.paper-cards.create/store` | `…@create/store` | ídem | pendiente |
| `card_signage/edit.php` + `control.php` (`update`, `hyoujiOff`, `delete`) | ペーパーサイネージ 編集 | `admin.terminales.paper-cards.edit/update/stop/destroy` | `…@edit/update/stop/destroy` | ídem | pendiente |
| `card_signage/aggregate.php` | 集計 | `admin.terminales.paper-cards.stats` | `Terminales\PaperCardStatsController@index` | `tb_url_list`, `url_transfer_data` (S5), `tb_customer` (L) | pendiente |
| `card_signage/csv_output.php` | 集計CSV | `admin.terminales.paper-cards.stats.csv` | `…@csv` (StreamedResponse) | — | pendiente |
| `digital_signage/screen_saver_list.php` | 放映素材管理 | `admin.terminales.signage-materials.index` | `Terminales\SignageMaterialController@index` | `tb_screensaver`, `tb_display` (L), `hatuban` | hecho (falta verlo con el layout y el login de S1) |
| `digital_signage/screen_saver_new.php` + `_frm`, `_register`, `pic_up.php` | 放映素材 新規登録 | `…signage-materials.create/store` (+ `upload`) | `…@create/store/upload` | ídem | hecho (falta verlo con el layout y el login de S1) |
| `digital_signage/screen_saver_detail.php` | 放映素材 詳細 | `…signage-materials.show` | `…@show` | ídem | hecho (falta verlo con el layout y el login de S1) (sin vista previa `.swf`) |
| `digital_signage/screen_saver_update.php` | 放映素材 修正 | `…signage-materials.edit/update` | `…@edit/update` | ídem | hecho (falta verlo con el layout y el login de S1) |
| `digital_signage/screen_saver_delete.php`, `_delete_complete.php` | 放映素材 削除 / 完了 | `…signage-materials.destroy` | `…@destroy` | ídem | hecho (falta verlo con el layout y el login de S1) |
| `digital_signage/movie_program_list.php` | 放映プログラム管理 | `admin.terminales.signage-programs.index` | `Terminales\SignageProgramController@index` | `tb_c7_movie_program`, `tb_screensaver` (L), `tb_dispaly_systems` (L), `tb_mycoupon_master` (L), `hatuban` + XML `hp/uploads/c7/display/<cd>.xml` | hecho (falta verlo con el layout y el login de S1) |
| `digital_signage/movie_program_new.php` + `_frm`, `_register`, `xml_up.php` | 放映プログラム 新規登録 | `…signage-programs.create/store` | `…@create/store` | ídem | hecho (falta verlo con el layout y el login de S1) (corrige 最後に追加, ver Decisiones) |
| `digital_signage/movie_program_detail.php` | 放映プログラム 詳細 | `…signage-programs.show` | `…@show` | ídem | hecho (falta verlo con el layout y el login de S1) |
| `digital_signage/movie_program_update.php` | 放映プログラム 修正 | `…signage-programs.edit/update` (botón 「…選択中のディスプレイに反映する」) | `…@edit/update` | ídem | hecho (falta verlo con el layout y el login de S1) |
| `digital_signage/movie_program_delete.php` | 放映プログラム 削除 | `…signage-programs.destroy` | `…@destroy` | ídem | hecho (falta verlo con el layout y el login de S1) |
| `digital_signage/display_list.php` | ディスプレイ管理 | `admin.terminales.signage-displays.index` | `Terminales\SignageDisplayController@index` | `tb_display`, `tb_c7_movie_program` (L), `tb_dispaly_systems` (L), `tb_mycoupon_master` (L), `hatuban` | hecho (falta verlo con el layout y el login de S1) |
| `digital_signage/display_new.php` + `_frm`, `_register` | ディスプレイ 新規登録 | `…signage-displays.create/store` | `…@create/store` | ídem | hecho (falta verlo con el layout y el login de S1) |
| `digital_signage/display_detail.php` | ディスプレイ 詳細 | `…signage-displays.show` | `…@show` | ídem | hecho (falta verlo con el layout y el login de S1) |
| `digital_signage/display_update.php` | ディスプレイ 修正 | `…signage-displays.edit/update` | `…@edit/update` | ídem | hecho (falta verlo con el layout y el login de S1) |
| `digital_signage/display_delete.php` | ディスプレイ 削除 | `…signage-displays.destroy` | `…@destroy` | ídem | hecho (falta verlo con el layout y el login de S1) |
| `digital_signage/xml_info_up.php` | (floormap.xml) | — (servicio) | `Services\Terminales\SignageFeedBuilder::floormap()` | `tb_shop`, `tb_recruit`, `tb_recruit_category` (L) → `floormap.xml` | hecho (falta verlo con el layout y el login de S1) |
| `kiosk/movie_program_*` (7) | 映像管理 | — | — | — | descartado: touchcation fuera de alcance |
| `kiosk/movie_program.php`, `ajax_movie_program.php`, `movie_program_frm_rAJAX.php` | 映像 editor | — | — | — | descartado: touchcation fuera de alcance |
| `kiosk/movie_program_popPre.php` | プレビュー | — | — | — | descartado: touchcation fuera de alcance |
| `kiosk/display_*` (7) | 映像振り分け管理 | — | — | — | descartado: touchcation fuera de alcance |
| `kiosk/screen_saver_*` (7) | 素材 | — | — | — | descartado: touchcation fuera de alcance |
| `kiosk/pic_up.php`, `inc/*`, `xml_up.php`, `xml_info_up.php` | — | — | — | — | descartado: touchcation fuera de alcance |
| `kiosk/obi_sample.php`, `wait_time_sample.php` | 帯/待ち時間サンプル | — | — | — | descartado: touchcation fuera de alcance |
| `felica/terminal_list.php` + `ajax_del.php` (`tbNo=terminal`) | 端末管理 (FeliCa) | `admin.terminales.felica-terminals.index/sort/destroy` | `Terminales\FelicaTerminalController@index/sort/destroy` | `tb_terminal` (modelo de S1) | pendiente |
| `felica/card_list.php` + `ajax_del.php` (`tbNo=customer_card`) | カード管理 | `admin.terminales.felica-cards.index/assign/unassign/destroy` | `Terminales\FelicaCardController@…` | `tb_customer_card`, `tb_customer` (vía servicio de S2) | pendiente |
| `felica/ajax_search_kaiin.php` | (búsqueda de socio) | `admin.terminales.felica-cards.customers` (JSON) | `…@searchCustomers` | — | pendiente |

### 1.4 Reproductores — `senalizacion` (`signage/`, `digitalsignage/`)

**`signage/` (CMS web + reproductor).** Gestión dentro del admin; el reproductor conserva su URL.

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas (legacy) | Estado |
|---|---|---|---|---|---|
| `signage/index.php` | ホーム (現在表示中のサイネージ) | `admin.senalizacion.index` | `Senalizacion\HomeController@index` | — | pendiente |
| `signage/list/terminal.php` | 端末一覧 | `admin.senalizacion.terminals.*` | `Senalizacion\TerminalController@index/store/update/destroy` | BD `admin`: `terminal` | pendiente |
| `signage/list/packages.php` | パッケージ一覧 | `admin.senalizacion.packages.*` | `Senalizacion\PackageController@…` (crea los ciclos de cada marco) | BD `admin`: `packages`, `cycle` | pendiente |
| `signage/list/cycle.php` | サイネージ管理 (ciclos) | `admin.senalizacion.cycles.edit/update` | `Senalizacion\CycleController@edit/update` | BD `admin`: `cycle`, `contents` | pendiente |
| `signage/list/contents_category.php` | 画像サイズ別一覧 | `admin.senalizacion.content-categories.*` | `Senalizacion\ContentCategoryController@…` | BD `admin`: `contents_category` | pendiente |
| `signage/list/contents.php`, `contents_detail.php` | サイネージ管理 (画像) | `admin.senalizacion.contents.*` (`kind=image`) | `Senalizacion\ContentController@…` | BD `admin`: `contents` / `movie_contents` | pendiente |
| `signage/list/movie_contents.php`, `movie_contents_detail.php`, `movie_contents_category.php` | サイネージ管理 (動画) | `admin.senalizacion.contents.*` (`kind=video`) | ídem | ídem | pendiente (corrige el borrado de categoría, ver Decisiones) |
| `signage/list/signage.php` | サイネージ管理 (ファイル) | `admin.senalizacion.files.store/destroy` | `Senalizacion\FileController@store/destroy` | BD `admin`: `signage` | pendiente (lista blanca sin `swf`) |
| `signage/list/inc/*`, `inc_flame_cd/*`, `inc_js/*` | (17 diseños de marco) | — | componentes Blade `resources/views/admin/senalizacion/layouts/*` | — | pendiente (fragmentos) |
| `signage/view/index.php` + `view/h_*`, `v_*` | (pantalla) | `GET /signage/view/` y `/signage/view/index.php` (`?cd=`) — **público** | `Senalizacion\PlayerController@show` | — | pendiente |
| `signage/view/ajax.php` | (sondeo de cambios) | `/signage/view/ajax.php` — público, JSON | `…@poll` | — | pendiente |
| `signage/view/packages.php` | (vista previa de paquete) | `admin.senalizacion.packages.preview` | `Senalizacion\PackageController@preview` | — | pendiente |
| `signage/mail/index.php` | GoTel管理 (お問い合わせ) | `admin.senalizacion.contact.create/store` | `Senalizacion\ContactController@create/store` | BD `admin`: `contact`, `customer` | pendiente (confirmar uso; Mailable en cola) |
| `signage/pdf/invoice.php` | 請求書 | `admin.senalizacion.invoices.show` | `Senalizacion\BillingPdfController@invoice` | — | pendiente (confirmar uso; PDF del paquete de S1) |
| `signage/pdf/receipt.php` | 領収書 | `admin.senalizacion.receipts.show` | `…@receipt` | — | pendiente (ídem) |
| `signage/inc/*`, `basic_authentication.php` | (núcleo, login `?s=`) | — | sesión del admin + Gate `manageSignage` | — | pendiente (reemplazado) |
| `signage/_tool.php` | (chmod 777) | — | — | — | descartado: peligroso, sin función |
| `signage/sample.php` … `sample4.php` | (pruebas) | — | — | — | descartado: pruebas de desarrollo |

**`digitalsignage/` (Flash → HTML5, mismo contrato).**

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas (legacy) | Estado |
|---|---|---|---|---|---|
| `digitalsignage/index.php` → `index2.php` | digital signage | `GET /digitalsignage/`, `/digitalsignage/index.php`, `/index2.php` (`?shop_cd=&display_cd=`) — público | `Senalizacion\DigitalPlayerController@show` | `tb_display`, `tb_c7_movie_program` (L) | pendiente (reproductor HTML5 que lee el mismo XML; orientación por `terminal_type`) |
| `hp/uploads/c7/display/<cd>.xml` (generado por `xml_up.php`) | (programa XML) | `GET /hp/uploads/c7/display/{cd}.xml` — público | `Senalizacion\DigitalFeedController@program` | — | pendiente (misma forma XML `<p cd time title …>`) |
| `ajax_xml_check.php`, `ajax_xml_check_2.php` | (sondeo XML) | mismas URL — público | `Senalizacion\DigitalPollController@xml` | — | pendiente (responde `<script>` igual que hoy) |
| `ajax_display_config_check.php`, `_2.php` | (sondeo config) | mismas URL | `…@displayConfig` | — | pendiente |
| `ajax_network_check.php` | (red) | misma URL | `…@network` (texto `確認できました`) | — | pendiente |
| `ajax_print_coupon_crawl.php`, `ajax_print_coupon_craw_2l.php` | (cupón impreso por visita) | mismas URL | `Senalizacion\CouponCrawlController@visit` | `tb_raiten_rireki`, `tb_mycoupon_master`, `tb_shop` (L) | pendiente |
| `ajax_touch_print_coupon_crawl*.php` | (cupón táctil) | mismas URL | `…@touch` | — | pendiente |
| `ajax_shinkyaku_print_coupon_crawl*.php` | (cupón cliente nuevo) | mismas URL | `…@newCustomer` | — | pendiente |
| `ajax_kaiin_raiten_print_coupon_crawl.php` | (cupón socio por visita) | misma URL | `…@memberVisit` | — | pendiente |
| `ajax_Mujyouken_print_coupon_crawl_2.php` | (cupón sin condición) | misma URL | `…@unconditional` | — | pendiente |
| `ajax_kaiin_mycoupon_print_crawl.php` | (cupón del socio, marca impreso/usado) | misma URL | `…@memberCoupon` | `tb_mycoupon*` vía `CouponIssuer` de S3 | pendiente |
| `roulette.php` (+ `.swf`, `tig_game/`) | ルーレット | `/digitalsignage/roulette.php` | `Senalizacion\DigitalGameController@roulette` | — | pendiente (rehacer en HTML5/Canvas) |
| `slot.php` (+ `.swf`) | スロット | `/digitalsignage/slot.php` | `…@slot` | — | pendiente (ídem) |
| `fla/floormap/ajax_print_crawl.php` | (mapa de planta + 求人) | misma URL | `Senalizacion\DigitalFeedController@floormap` | `tb_recruit*`, `tb_mycoupon_master`, `tb_shop` (L) | pendiente |
| `fla/immovables/getFlash.php`, `html/immovables/*` (6) | 不動産 | — | — | — | descartado: micro-sitio de otro sistema (`tb_madori`, `tb_item` no están en el CRM) y `index.php` roto en el legacy |
| `html/recipe/index.php`, `recipe_detail.php` | レシピ | — | — | — | descartado: contenido estático de demostración, sin datos; se rehace solo si el cliente lo pide |

## 2. Datos: tablas del legacy y modelos

Base (CONVENCIONES §0 y §4): **la BD es la del legacy** (`database/schema/pgsql-schema.sql`), sin tablas nuevas, sin mapeo ni importador. Un modelo Eloquent por tabla: `$table` = la `tb_*`, `$primaryKey = 'cd'`, `$timestamps = false` (las fechas son `touroku_date`/`update_date` en texto o epoch, se escriben como el legacy), columnas con su nombre legacy, numeración con `hatuban` (servicio de S1) y borrado lógico `disp_flg = '9'` (scope/trait de S1, no SoftDeletes). Banderas (`hyouji_flg`, `pic_flg`, `disp_conf`…) con sus valores legacy, nombradas con constantes. Las listas `<>` y `||` se siguen guardando igual; los modelos dan accesores que las parten y las unen.

### 2.1 Modelos de S4

| Modelo (`app/Models/Mypage|Terminales/`) | Tabla | Claves y columnas relevantes | Relaciones | Lo leen además |
|---|---|---|---|---|
| `Recommendation` | `tb_hp_recommend` | `cd`; `tb_shop_cd` (0/null = 全店舗), `disp_date` (`Y/m/d` texto), `title`, `naiyou`, `link`, `link_type`, `img_flg`, `img_type`, `pic_link`, `hyouji_flg` (`1` 表示; `9` se excluye en la app), `update`, `disp_flg` | `shop()` → `Shop` (`tb_shop_cd`) | S5 (`appview`: últimos 20, `tb_shop_cd IN (tienda, 0)`) |
| `Recruit` | `tb_recruit` | `cd`; `tb_shop_cd`, `boshu_headline`, `boshu_shokushu` (→ `tb_recruit_category.cd`), `shigoto_naiyou`, `koyou_keitai`, `oubo_shikaku`, `kinmu_chi`, `kinmu_jikan`, `kyuyo`, `kyuka`, `taigu`, `pic_flg1`, `pic_flg2`, `kaisha_name`, `add`, `tel`, `fax`, `jigyou_naiyou`, `oubo_houhou`, `mensetsuchi`, `tantou`, `touroku_date`, `update_date`, `disp_flg` | `shop()`, `category()` → `RecruitCategory` | `SignageFeedBuilder` (floormap) |
| `RecruitCategory` | `tb_recruit_category` | `cd`; `tb_shop_cd`, `shokushu`, `disp_flg` (`1` vigente), fechas | `recruits()` | — |
| `SignageDisplay` | `tb_display` | `cd` (= `display_cd` del reproductor); `tb_shop_cd`, `name`, `comment`, `terminal_id`, `terminal_type` (`<>`), `disp_conf`, `tb_c7_movie_program_cds` (el formulario elige uno), `tb_dispaly_systems_cds`, `tb_mycoupon_master_cds` (`<>`), `act_flg`, `kaiin_flg`, `rank`, `update_date` (lo sondea el reproductor), `disp_flg` | `program()` (primer cd de `tb_c7_movie_program_cds`), `materials()` (`tb_screensaver.tb_display_cd`) | reproductor `digitalsignage` |
| `SignageProgram` | `tb_c7_movie_program` | `cd` (= nombre del XML); `tb_shop_cd`, `name`, `comment`, `terminal_id`, `terminal_type`, `disp_conf` (`1` 放映ファイル選択, `2` システム内蔵), `tb_screensaver_cds` (`<>`, orden de reproducción), `tb_dispaly_systems_cds`, `tb_mycoupon_master_cds`, `feli_…`, `mypage_…`, `system_tb_mycoupon_master_cds`, `act_flg`, `kaiin_flg`, `feli_act_flg`, `mypage_act_flg`, `telop_comment`, `vertical_setting`, `rank`, fechas, `disp_flg` | `materials()` (por `tb_screensaver_cds` en orden) | `SignageFeedBuilder`, reproductor |
| `SignageMaterial` | `tb_screensaver` | `cd` (sale en el XML); `tb_shop_cd`, `tb_display_cd`, `title`, `comment`, `filetype` (texto: 画像ファイル / FLVファイル / SWFファイル), `pic_flg` y `pic_extension` (`<>` por hueco), `play_frame_sec`, `type_flg`, `data`, `select_flg`, `categorize_value_0`, `rank` (先頭に追加 = 0, 最後に追加 = max+1 por display), fechas, `disp_flg` | `display()` | `SignageFeedBuilder` |
| `SignageSystem` | `tb_dispaly_systems` (sic) | `cd`; `tb_shop_cd`, `name`, `system_type`, `system_flg`, `non_coupon_flg`, `rank`, `disp_flg` | — | — |
| `PaperCard` | `tb_url_list` | `cd` (integer); `id` (código único entre `disp_flg = '1'`; vacío → `CRM` + cd en 9 dígitos), `url`, `title`, `hyouji_flg` (`hyoujiOff` = 停止), `disp_flg` | `accesses()` → `PaperCardAccess` de S5 | S5 (`ptag`: busca por `id`, solo con `hyouji_flg` activo) |
| `FelicaCard` | `tb_customer_card` | `cd`; `tb_shop_cd`, `tb_terminal_cd`, `tb_customer_cd`, `tb_staff_cd`, `cid` (IDm), `typ`, `typ_exp`, `type` (`PHP_SELF` del lector), `terminal_no`, `flg`, `point`, `lost`, `comment`, fechas. `name` existe pero ningún código lo escribe: el filtro 会員名 busca en `tb_customer` | `customer()` → `Customer` (S2), `terminal()` → `Terminal` (S1), `shop()` | S2 (UID解除 llama a `FelicaCardService::unassign`) |
| `BeaconHistory` | `tb_beacon_history` | `cd`; `beacon_cd`, `tb_customer_id`, `tb_shop_cd`, `touroku_date` | — | S5 (`mpa_setraiten`) |

Beacons y sus terminales receptores: modelos `Beacon` y `BeaconReceiver` con `$connection = 'beacon'` sobre la BD externa (tablas por confirmar cuando haya acceso).

### 2.2 Tablas de otros dueños que S4 usa

| Tabla | Modelo (dueño) | Uso en S4 |
|---|---|---|
| `contents_master` | `ContentsMaster` (S1) | Config de マイコレ (`category='tb_c6_user_hp'`, `field` = `aplicover`, `aplibanner`, `mainimage`, `apli_home_thumbnail`: enlaces `||` en `text_data`; `mypage_config`: `serialize()` de `[sección][hueco][shop_cd] = 1`, `cell_status`, `cell_img`, `comment_flg`, `home_image_flg`, `home_thum_flg`; `apli_home_comment`, `apli_customer_group_disp`) y de ガチャリン (`category='tb_c6_kujibiki'`: `kujibiki_disp_flg`, `_max`, `_limit_flg`, `_dia/gold/silver/bronze_num|rate`, `prize_count`, `_enter`, `_form`…). Lectura y escritura a través de un servicio de S4 (`MypageConfig`, `LotteryConfig`) sobre el modelo de S1. `mypage_config` se lee con `unserialize(…, ['allowed_classes' => false])` y se guarda con `serialize()` como el legacy (lo leen `mpa_*`) |
| `admin_master` | `AdminMaster` (S1) | `application_use_flg`, `push_api_host` (push de オススメ) |
| `tb_shop` | `Shop` (S1) | Filtro por tienda; `floor_name`, `service_kubun`, `kaishamei`, `kaishamei_kana`, `kaisha_data` para `floormap.xml` |
| `tb_terminal` | `Terminal` (S1) | Lista ICカード 端末管理: orden `rank` (▼▲) y borrado |
| `tb_customer` | `Customer` (S2) | Asignar/quitar tarjeta: `CustomerFelicaService::attach/detach` (nunca se escribe `felica_no` directo); búsqueda de socio; 集計 |
| `tb_mycoupon_master` | `Coupon` (S3) | Cupones de programas/displays y cupones impresos (`CouponIssuer::markPrinted/markUsed`) |
| `tb_raiten_rireki` | `Visit` (S2) | Sondeo de cupones impresos por visita (`digitalsignage`) |
| `url_transfer_data` | `PaperCardAccess` (S5) | 集計 de ペーパーサイネージ |
| `tb_mobile_navi` | `MobileNavi` (S4) | Nombre del menú de オススメ |
| `hatuban` | servicio de numeración (S1) | Alta de todos los registros |

### 2.3 Archivos

Imágenes y XML en el disco `public` de Storage con **la misma ruta relativa que el legacy** (`hp/uploads/c6/<carpeta>/<archivo>`, `hp/uploads/c6/user_hp/recommend/<cd>/`, `hp/uploads/c7/...`, `hp/uploads/c7/display/<cd>.xml`, `floormap.xml`); los sirve la redirección genérica `/hp/uploads/{path}` de S1. Así no cambian las URL que devuelven `mpa_*`/`appview` (con `?r=<mtime>`) ni las que lee el reproductor.

### 2.4 `signage/` (BD `admin`)

Las tablas de `signage/` (`terminal`, `packages`, `cycle`, `contents`, `contents_category`, `movie_contents`, `signage`, `customer`, `shop`, `contact`) viven en otra BD (`admin`, no `crm2019`) y **no están** en `pgsql-schema.sql`: conexión aparte `signage` como el legacy (S0). Sin volcado de Antony, esquema mínimo de desarrollo en `database/schema/signage-admin.sql`. Sus modelos (`app/Models/Senalizacion/`) serían `SignageWebTerminal`, `SignageWebPackage`, `SignageWebCycle` (`img_path_ary`/`action_ary` con `serialize()`: se leen con `allowed_classes => false`), `SignageWebContent`, `SignageWebContentCategory`, `SignageWebMovieContent`, `SignageWebFile`, `SignageWebContact`.

## 2b. UI

Mismo HTML (mismas clases e ids) y mismos CSS que el legacy, copiados al Blade (`resources/views/admin/<modulo>/`), dentro del layout de S1 con la cabecera y el menú lateral del legacy. Estáticos comunes desde `public/legacy/` (S1). JS por pantalla, con jQuery 3.7.1 + compat del legacy:

| Pantallas | JS / CSS del legacy |
|---|---|
| マイコレ (5) | `category6/user_hp/js/imageselect.js`, diálogos jQuery UI (`/system/js/jquery-ui-dialog-compat.js`), subida AJAX (endpoint JSON que reemplaza `ajax_apliFileUpload.php`) |
| マイページデザイン管理 (parcial) | `pic_upload.js` no hace falta: solo quedan dos opciones de formulario |
| オススメ | `category6/user_hp/js/staff_entry.js`, `/system/js/ajaxupload-3.5.js`, `/system/js/jquery.snspost.js` |
| 求人 | `category6/recruit/js/item_entry.js`, `/system/js/ajaxupload-3.5.js` (AJAX de 職種) |
| ガチャリン | `/system/plugin/ui-datepicker/` (`ui.datepicker.css`, `ui.datepicker-ja.js`, `gcalendar-holidays.js`) |
| ペーパーサイネージ | `/system/js/popupwindow-1.8_vizan.js` + `/system/css/popupwindow.css`; 集計: `/system/js/highcharts.js` |
| 電子看板 (素材, プログラム, ディスプレイ) | `category7/digital_signage/js/drag_io.js`, `user.js`, `jquery-ui-1.8rc3.custom.min.js` (sortable; usar el jQuery UI común si el compat alcanza), `/system/js/popupwindow-1.8_vizan.js`, `/system/js/query-image-preview.js` + `.css` |
| ICカード | `category7/felica/js/drag_io.js`, `user.js`, `jquery.colorbox.js` + `css/colorbox.css`, `css/add.css` |
| `digitalsignage` (reproductor) | HTML5 nuevo en lugar de `digitalsignage.swf` (ruleta y tragaperras en Canvas); mismo polling JS a `ajax_xml_check*.php` / `ajax_display_config_check*.php` |
| `signage/` | CSS/JS propios de `signage/` (`list/inc_js/*`, diseños `view/h_*`, `v_*`) |

**Copiados (2026-11-27)** a `public/legacy/` con la misma estructura relativa: `system/category6/{user_hp,recruit}`, `system/category7/{digital_signage,felica,card_signage}`, `digitalsignage/` y `signage/` (143 archivos, 2,3 MB). Excluidos: `.php`, `.swf`/`.fla` (Flash), `.htaccess`/`.ini`, cargas de usuarios (`uploads/`, `signage/upload/`) y los micrositios descartados (`digitalsignage/html/{immovables,recipe}`, `fla/immovables`). Lo común (`system/css|js|images|plugin`, `common/`) lo copia S1. Las URL que llaman los JS del personal (`/system/category6|7/…/ajax_*.php`, formularios) van en `routes/legacy/<modulo>.php` (`legacy.<modulo>.*`); las de los reproductores, en `routes/publico/senalizacion.php`.

## 3. Servicios viejos → Laravel 13

| Legacy | Reemplazo |
|---|---|
| `pg_query` con SQL concatenado, `form2Db` | Modelos Eloquent / query builder con bindings |
| `hatuban` (`SELECT … FOR UPDATE` + `kazu+1`) | Mismo `hatuban`, servicio común de S1 (en transacción) |
| `disp_flg = '9'` | Igual (scope/trait de S1) |
| Subida a `hp/uploads/c6|c7/…` + `temp/` + `system("find … -mtime +1 -exec rm")` | `Storage::disk('public')` en la misma ruta relativa; comando `mypage:prune-temp-uploads` en `MypageServiceProvider` (Scheduler diario) |
| Miniaturas con GD a mano | Servicio de imágenes (paquete que defina S1; mientras, GD) |
| `Push::exec` (`fsockopen` a `b.g-api.net`) | `Http::get` al host de `admin_master.push_api_host` (`QUEUE_CONNECTION=sync`: síncrono, como el legacy) |
| `xml_up.php` / `xml_info_up.php` escriben el XML al guardar | `SignageFeedBuilder::writeProgram()` / `writeFloormap()` al guardar programa, display, tienda o 求人: **mismo archivo y misma ruta** (elegido por ser lo más simple: el reproductor compara la fecha del archivo) |
| QR por `file_get_contents` HTTP a `qr_img.php` | Generador QR en PHP (paquete de S1) |
| CSV con `header()` / Flash | `StreamedResponse` en SJIS-win |
| mPDF de `signage/pdf` + HTML con autenticación básica | Paquete PDF de S1 renderizando el Blade |
| `mail/index.php` con `mb_send_mail` | `Mailable` |
| `kengen->myKengen[código]` (1014-1036), `rank=='master'`, `isDevelopmentAuthorityFromIP` | Policies/Gates de S1 |
| `md5(session_id())` como token y `tbNo` arbitrario (`felica/ajax_del.php`) | CSRF de Laravel + una ruta por modelo (`tb_terminal` o `tb_customer_card`) |
| `?s=<hash>` (acceso a `signage/`) | Sesión del admin + Gate `manageSignage` |
| `beacon_db.php` (`pg_connect` externo) | Conexión `beacon` en `config/database.php` |
| Flash del reproductor `digitalsignage` | HTML5 con el mismo contrato |
| `signage` franja `to_char(…,'hh24:mm')` (bug: `mm` = mes) | Comparación correcta `hh24:mi` (anotado en Decisiones) |

## 4. Orden de implementación

| # | Submódulo | Contenido | Depende de | Puntos |
|---|---|---|---|---|
| 0 | Base del grupo | Modelos de §2.1, rutas, menú, `MypageServiceProvider` | S1: layout, base de modelos legacy (`disp_flg`, `hatuban`), Policies | 2 |
| 1 | `mypage` マイコレ + opciones de app | 4 secciones en una pantalla, subida, por tienda (`contents_master`) | S1 (`ContentsMaster`, `Shop`); S5 lee lo mismo | 5 |
| 2 | `mypage` オススメ + push | Lista/alta/edición/borrado + push | S1 (`AdminMaster`) | 3 |
| 3 | `mypage` 求人 + 職種 | CRUD + `floormap.xml` al guardar | S1 (`Shop`) | 4 |
| 4 | `mypage` ガチャリン設定 | Configuración en `contents_master` | S3 (sorteo) | 2 |
| 5 | `terminales` 電子看板 + XML | Material, programa, display; `writeProgram()` | S3 (`Coupon`) | 8 |
| 6 | `senalizacion` `digitalsignage` HTML5 | Reproductor, sondeos, cupones impresos, ruleta, tragaperras | 5; S2 (`Visit`, `Customer`), S3 (`CouponIssuer`) | 8 |
| 7 | `senalizacion` `signage/` | CMS + reproductor `view/` | **respuesta sobre la BD `admin`** | 14 |
| 8 | `senalizacion` GoTel / PDF | Contacto + 請求書/領収書 | confirmación de Antony, paquete PDF (S1) | 3 |
| 9 | `terminales` ペーパーサイネージ | CRUD + QR + 集計 + CSV | S5 (`PaperCardAccess`), paquete QR (S1) | 4 |
| 10 | `terminales` ICカード (FeliCa) | Terminales + tarjetas | S1 (`Terminal`), S2 (`CustomerFelicaService`) | 4 |
| 11 | `terminales` ビーコン | Lista/detalle externos + `tb_beacon_history` | conexión `beacon`, BD externa accesible, Antony | 3 |
| | **Total** | | | **60** |

Ya hecho y sin dependencias: `SignageFeedBuilder` (XML del programa y `floormap.xml` sobre las `tb_*`), la ruta pública `GET /hp/uploads/c7/display/{<cd>.xml|floormap.xml}` (`publico.senalizacion.display-xml`: entrega el archivo guardado o lo genera) y los estáticos de §2b, con sus pruebas.

## 5. Pedidos

**A S0**
1. ~~BD `admin` de `signage/`~~: resuelto, conexión aparte `signage` (`DB_SIGNAGE_*`, S1); volcado pedido a Antony. Sin volcado, esquema mínimo de desarrollo en `database/schema/signage-admin.sql` reconstruido de las consultas (solo pruebas).
2. Confirmación de Antony: `signage/mail` (GoTel) y `signage/pdf`; si se conserva ビーコン (servidor externo caído).

**A S1 (núcleo)**
1. Base de modelos legacy: scope `disp_flg <> '9'`, servicio `hatuban`, y los modelos `ContentsMaster`, `AdminMaster`, `Shop`, `Terminal`.
2. ~~`tb_mobile_navi`~~: resuelto, es de S4.
3. ~~Estáticos de carpeta de módulo~~: resuelto, los copia S4. Registrar la redirección genérica `/hp/uploads/{path}` **después** de `routes/publico/*` (la ruta del XML de S4 debe ganar).
4. Paquetes: imágenes (miniaturas), QR, PDF con fuente japonesa; conexión `beacon` (`DB_BEACON_*`).
5. Policies/Gates para `kengen` 1014–1017, 1023, 1032, 1035, 1036, el menú de category7 y `manageSignage`.
6. Redirección `/hp/uploads/{path}` al disco public (ya acordada).

**A S2:** resuelto: `CustomerFelicaService::attach/detach`, `VisitService`/`CustomerEntry` para lecturas del lector. S4 publica `FelicaCardService::unassign()`.
**A S3:** resuelto: el sorteo lee la configuración de ガチャリン de `contents_master` (S4 publica `LotteryConfig` de solo lectura) y cuenta en su propia tabla; cupones impresos con `CouponIssuer::markPrinted/markUsed`.
**A S5:** `mpa_getCoverFlow`, `mpa_getTopBanner`, `mpa_corresponding` y `appview` leen `contents_master` (vía `MypageConfig` de S4), `tb_hp_recommend` (`Recommendation`) y los archivos de `hp/uploads/c6/`; `ptag` lee `tb_url_list` (`PaperCard`); `mpa_setraiten` escribe `tb_beacon_history` (`BeaconHistory`).

## 6. Decisiones (de este diseño)

- Base del legacy (Antony, 2026-11-26): misma BD y misma UI; nada de tablas nuevas. La capa de datos sobre tablas nuevas (23f14c9) se quitó (d45a652).
- category4 entero y lo de category6 que solo leía `hp/` (topics, header, freepage, diseño, colores, 携帯HP, フォトギャラリー): descartado: sin consumidor. De `mobile_hp_edit.php` solo sobreviven las dos opciones que lee la app.
- Módulo `web` sin pantallas.
- `kiosk` (category7) y touchcation: descartados (S0, 2026-11-26).
- XML de señalización: se escribe al guardar en `hp/uploads/c7/display/` como hoy (lo más simple con el mismo contrato). Misma forma que `xml_up.php`/`xml_info_up.php`, espacios dobles incluidos, pero con los valores escapados; un material borrado o inexistente se omite (el legacy emitía un `<p>` vacío); 【応募資格】 sale de `oubo_shikaku` (el legacy repetía 雇用形態); floormap toma el primer 求人 de cada tienda, como el legacy.
- Bugs que se corrigen: 最後に追加 en `movie_program_register` (columna inexistente `tb_screensaver.tb_c7_movie_program_cd`: se usa el orden de `tb_screensaver_cds`); `movie_contents_category` borraba de `contents_category`; franja `hh24:mm`; desplegable de 職種 vacío con una tienda; borrado de imagen que dejaba derivados.
- `tb_customer_card.name` no se usa (nadie lo escribe).
- Micro-sitios de `digitalsignage` (inmuebles, recetas) descartados.
- `.swf` ya no se acepta en las subidas; los materiales SWF existentes se listan pero el reproductor HTML5 los salta.
