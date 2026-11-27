# Diseño S4 — Contenido y terminales

> Sesión S4 · rama `l13/contenido` · 2026-11-26. Módulos: `mypage` (マイページ管理), `web` (ホームページ管理), `terminales` (端末管理, category7), `senalizacion` (reproductores `signage/` y `digitalsignage/`).
> Fuentes: `Doc/analisis-legacy/04-web-y-mypage.md`, `05-…` (category7), `08-…` (signage, digitalsignage); columnas legacy leídas con `pg_restore -s` de `crm2-php8/db/crm2019.backup`.
> **Alcance (S0, 2026-11-26):** touchcation y `system/category7/kiosk/` (`tb_nextouch_*`) **fuera**: no se usan. `hp/` no se migra: el contenido que solo leía `hp/` queda `descartado: sin consumidor`.

## 0. Criterios aplicados

- **Vivo** = lo lee algo que sigue: la app (`api/mpa_*`, `appview`), los reproductores (`signage/view`, `digitalsignage`) o código interno (`cKujibiki`, cupones). Todo lo demás de category4/6 solo lo leía `hp/` → descartado.
- Un solo código para lo que el legacy repite (las 5 pantallas マイコレ → un controlador por sección).
- Los `*_register`, `*_delete_register`, AJAX e includes no son filas propias: se listan dentro de la fila de su pantalla (columna «Archivo legacy»).
- Rutas de administración: `routes/modulos/<modulo>.php`, nombre `admin.<modulo>.*`. Rutas de dispositivos (reproductores): fuera del grupo `admin` (ver Pedidos).

## 1. Tabla de paridad

### 1.1 マイページ管理 — `mypage` (category6)

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `category6/index.php`, `inc/page_control.php`, `inc/left_navi6.php` | マイページ管理 | `admin.mypage.index` | `Mypage\MenuController@index` | — | pendiente (menú en `config/menu/mypage.php`) |
| `user_hp/mobile_mycolle_edit.php` + `ajax_apliFileUpload.php` | マイコレ管理 | `admin.mypage.app-images.edit` (`?section=`) | `Mypage\AppImageController@edit/update/upload/destroy` | `app_images`, `app_image_shop` | pendiente |
| `user_hp/mobile_mycolle_edit_coverflow.php` | カバーフロー画像設定管理 | `admin.mypage.app-images.edit` `section=cover` | ídem | ídem | pendiente |
| `user_hp/mobile_mycolle_edit_banner.php` | バナー画像設定管理 | ídem `section=banner` | ídem | ídem | pendiente |
| `user_hp/mobile_mycolle_edit_main.php` | HOMEメインイメージ管理 | ídem `section=main` | ídem | ídem | pendiente |
| `user_hp/mobile_mycolle_edit_thumbnail.php` | サムネイル画像管理 | ídem `section=thumbnail` | ídem | ídem | pendiente |
| `user_hp/mobile_hp_edit.php` (+ `ajax_change_pattern/pic`, `ajax_picupload`, `pic_config_function`, `base_color_function`) | マイページデザイン管理 | `admin.mypage.app-settings.edit` | `Mypage\AppSettingController@edit/update` | `app_settings` (solo `customer_group_display_flg`, `home_comment`) | pendiente **parcial**: solo la opción 「アプリで顧客グループを表示」 (`apli_customer_group_disp`, la lee `appview`) y el comentario HOME (`apli_home_comment`, `mpa_corresponding`). Plantillas/colores/imágenes de diseño: descartado: sin consumidor (`hp/user`) |
| `user_hp/config.php`, `config_register.php` | ユーザーマイページ設定 | — | — | — | descartado: sin consumidor (`tb_hp_color` solo `hp/`) |
| `user_hp/topics.php`, `topics_register.php`, `topics_delete_register.php`, `inc/upload_topics.php` | トピックス管理 | — | — | — | descartado: sin consumidor |
| `user_hp/recommend.php`, `recommend_register.php`, `recommend_delete_register.php` | オススメ管理 | `admin.mypage.recommendations.*` (resource) | `Mypage\RecommendationController@index/store/update/destroy` | `recommendations` | pendiente (lo lee `appview/Util`; push en cola) |
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
| `recruit/recruit_new.php` + `recruit_register.php`, `ajax_category.php` | 求人情報新規登録 | `admin.mypage.job-postings.create/store` | `Mypage\JobPostingController@create/store` | `job_postings` | pendiente (lo leen los XML de `digital_signage`: `floormap.xml`) |
| `recruit/recruit_list.php` | 求人情報一覧 | `admin.mypage.job-postings.index` | `…@index` | ídem | pendiente |
| `recruit/recruit_detail.php` | 求人情報詳細 | `admin.mypage.job-postings.show` | `…@show` | ídem | pendiente |
| `recruit/recruit_update.php`, `recruit_complete.php` | 求人情報修正 / 完了 | `admin.mypage.job-postings.edit/update` | `…@edit/update` | ídem | pendiente (el 完了 es el flash tras guardar) |
| `recruit/recruit_delete.php` | 求人情報削除 | `admin.mypage.job-postings.destroy` | `…@destroy` | ídem | pendiente |
| `recruit/recruit_category.php`, `_register`, `_delete` | 求人職種管理 | `admin.mypage.job-categories.*` | `Mypage\JobCategoryController@index/store/update/destroy` | `job_categories` | pendiente |
| `kujibiki/kujibiki_config.php` | ガチャリン管理 各種設定 | `admin.mypage.lottery.edit/update` | `Mypage\LotterySettingController@edit/update` | `lottery_settings`, `lottery_prizes` | pendiente (lo lee `cKujibiki` / `category3/mycoupon`, S3) |

### 1.2 ホームページ管理 — `web` (category4)

Todo category4 solo alimentaba `hp/` (sitio PC) → el módulo `web` queda **vacío** (sin rutas ni menú).

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
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

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `category7/index.php`, `inc/*` | 端末管理 | `admin.terminales.index` | `Terminales\MenuController@index` | — | pendiente |
| `beacon/beacon_list.php` | ビーコン管理 | `admin.terminales.beacons.index` | `Terminales\BeaconController@index` | — (BD externa, conexión `beacon`) | pendiente — **bloqueado**: BD externa `b-develop.g-api.net` no responde |
| `beacon/beacon_detail.php` + `ajax_beaconSave.php` | ビーコン詳細 | `admin.terminales.beacons.show/update` | `…@show/update` | — | pendiente (bloqueado, ídem) |
| `beacon/terminal_list.php` | 受信端末一覧 | `admin.terminales.beacon-receivers.index` | `Terminales\BeaconReceiverController@index` | — | pendiente (bloqueado, ídem) |
| `beacon/terminal_detail.php` + `ajax_terminalSave.php` | 受信端末詳細 | `admin.terminales.beacon-receivers.show/update` | `…@show/update` | — | pendiente (bloqueado, ídem) |
| `card_signage/list.php` (+ `cPageControl`, descarga QR) | ペーパーサイネージ (カード管理) | `admin.terminales.paper-cards.index`, `…qr` | `Terminales\PaperCardController@index/qr` | `paper_cards` | pendiente |
| `card_signage/new.php` + `control.php` (`insert`) | ペーパーサイネージ 新規 | `admin.terminales.paper-cards.create/store` | `…@create/store` | ídem | pendiente |
| `card_signage/edit.php` + `control.php` (`update`, `hyoujiOff`, `delete`) | ペーパーサイネージ 編集 | `admin.terminales.paper-cards.edit/update/stop/destroy` | `…@edit/update/stop/destroy` | ídem | pendiente |
| `card_signage/aggregate.php` | 集計 | `admin.terminales.paper-cards.stats` | `Terminales\PaperCardStatsController@index` | — (lee `customers`) | pendiente |
| `card_signage/csv_output.php` | 集計CSV | `admin.terminales.paper-cards.stats.csv` | `…@csv` (StreamedResponse) | — | pendiente |
| `digital_signage/screen_saver_list.php` | 放映素材管理 | `admin.terminales.signage-materials.index` | `Terminales\SignageMaterialController@index` | `signage_materials` | pendiente |
| `digital_signage/screen_saver_new.php` + `_frm`, `_register`, `pic_up.php` | 放映素材 新規登録 | `…signage-materials.create/store` (+ `upload`) | `…@create/store/upload` | ídem | pendiente |
| `digital_signage/screen_saver_detail.php` | 放映素材 詳細 | `…signage-materials.show` | `…@show` | ídem | pendiente (sin vista previa `.swf`) |
| `digital_signage/screen_saver_update.php` | 放映素材 修正 | `…signage-materials.edit/update` | `…@edit/update` | ídem | pendiente |
| `digital_signage/screen_saver_delete.php`, `_delete_complete.php` | 放映素材 削除 / 完了 | `…signage-materials.destroy` | `…@destroy` | ídem | pendiente |
| `digital_signage/movie_program_list.php` | 放映プログラム管理 | `admin.terminales.signage-programs.index` | `Terminales\SignageProgramController@index` | `signage_programs`, `signage_material_signage_program`, `coupon_signage_program` | pendiente |
| `digital_signage/movie_program_new.php` + `_frm`, `_register`, `xml_up.php` | 放映プログラム 新規登録 | `…signage-programs.create/store` | `…@create/store` | ídem | pendiente (corrige 最後に追加, ver Decisiones) |
| `digital_signage/movie_program_detail.php` | 放映プログラム 詳細 | `…signage-programs.show` | `…@show` | ídem | pendiente |
| `digital_signage/movie_program_update.php` | 放映プログラム 修正 | `…signage-programs.edit/update` (botón 「…選択中のディスプレイに反映する」) | `…@edit/update` | ídem | pendiente |
| `digital_signage/movie_program_delete.php` | 放映プログラム 削除 | `…signage-programs.destroy` | `…@destroy` | ídem | pendiente |
| `digital_signage/display_list.php` | ディスプレイ管理 | `admin.terminales.signage-displays.index` | `Terminales\SignageDisplayController@index` | `signage_displays`, `signage_display_signage_program`, `signage_display_signage_system` | pendiente |
| `digital_signage/display_new.php` + `_frm`, `_register` | ディスプレイ 新規登録 | `…signage-displays.create/store` | `…@create/store` | ídem | pendiente |
| `digital_signage/display_detail.php` | ディスプレイ 詳細 | `…signage-displays.show` | `…@show` | ídem | pendiente |
| `digital_signage/display_update.php` | ディスプレイ 修正 | `…signage-displays.edit/update` | `…@edit/update` | ídem | pendiente |
| `digital_signage/display_delete.php` | ディスプレイ 削除 | `…signage-displays.destroy` | `…@destroy` | ídem | pendiente |
| `digital_signage/xml_info_up.php` | (floormap.xml) | — (servicio) | `Services\Terminales\SignageFeedBuilder::floormap()` | — (lee `job_postings`) | pendiente |
| `kiosk/movie_program_*` (7) | 映像管理 | — | — | — | descartado: touchcation fuera de alcance |
| `kiosk/movie_program.php`, `ajax_movie_program.php`, `movie_program_frm_rAJAX.php` | 映像 editor | — | — | — | descartado: touchcation fuera de alcance |
| `kiosk/movie_program_popPre.php` | プレビュー | — | — | — | descartado: touchcation fuera de alcance |
| `kiosk/display_*` (7) | 映像振り分け管理 | — | — | — | descartado: touchcation fuera de alcance |
| `kiosk/screen_saver_*` (7) | 素材 | — | — | — | descartado: touchcation fuera de alcance |
| `kiosk/pic_up.php`, `inc/*`, `xml_up.php`, `xml_info_up.php` | — | — | — | — | descartado: touchcation fuera de alcance |
| `kiosk/obi_sample.php`, `wait_time_sample.php` | 帯/待ち時間サンプル | — | — | — | descartado: touchcation fuera de alcance |
| `felica/terminal_list.php` + `ajax_del.php` (`tbNo=terminal`) | 端末管理 (FeliCa) | `admin.terminales.felica-terminals.index/sort/destroy` | `Terminales\FelicaTerminalController@index/sort/destroy` | — (usa `terminals` de S1) | pendiente |
| `felica/card_list.php` + `ajax_del.php` (`tbNo=customer_card`) | カード管理 | `admin.terminales.felica-cards.index/assign/unassign/destroy` | `Terminales\FelicaCardController@…` | `felica_cards` | pendiente |
| `felica/ajax_search_kaiin.php` | (búsqueda de socio) | `admin.terminales.felica-cards.customers` (JSON) | `…@searchCustomers` | — | pendiente |

### 1.4 Reproductores — `senalizacion` (`signage/`, `digitalsignage/`)

**`signage/` (CMS web + reproductor).** Gestión dentro del admin; el reproductor conserva su URL.

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `signage/index.php` | ホーム (現在表示中のサイネージ) | `admin.senalizacion.index` | `Senalizacion\HomeController@index` | — | pendiente |
| `signage/list/terminal.php` | 端末一覧 | `admin.senalizacion.terminals.*` | `Senalizacion\TerminalController@index/store/update/destroy` | `web_signage_terminals` | pendiente |
| `signage/list/packages.php` | パッケージ一覧 | `admin.senalizacion.packages.*` | `Senalizacion\PackageController@…` (crea los ciclos de cada marco) | `web_signage_packages` | pendiente |
| `signage/list/cycle.php` | サイネージ管理 (ciclos) | `admin.senalizacion.cycles.edit/update` | `Senalizacion\CycleController@edit/update` | `web_signage_cycles` | pendiente |
| `signage/list/contents_category.php` | 画像サイズ別一覧 | `admin.senalizacion.content-categories.*` | `Senalizacion\ContentCategoryController@…` | `web_signage_content_categories` | pendiente |
| `signage/list/contents.php`, `contents_detail.php` | サイネージ管理 (画像) | `admin.senalizacion.contents.*` (`kind=image`) | `Senalizacion\ContentController@…` | `web_signage_contents` | pendiente |
| `signage/list/movie_contents.php`, `movie_contents_detail.php`, `movie_contents_category.php` | サイネージ管理 (動画) | `admin.senalizacion.contents.*` (`kind=video`) | ídem | ídem | pendiente (corrige el borrado de categoría, ver Decisiones) |
| `signage/list/signage.php` | サイネージ管理 (ファイル) | `admin.senalizacion.files.store/destroy` | `Senalizacion\FileController@store/destroy` | `web_signage_files` | pendiente (lista blanca sin `swf`) |
| `signage/list/inc/*`, `inc_flame_cd/*`, `inc_js/*` | (17 diseños de marco) | — | componentes Blade `resources/views/admin/senalizacion/layouts/*` | — | pendiente (fragmentos) |
| `signage/view/index.php` + `view/h_*`, `v_*` | (pantalla) | `GET /signage/view/` y `/signage/view/index.php` (`?cd=`) — **público** | `Senalizacion\PlayerController@show` | — | pendiente |
| `signage/view/ajax.php` | (sondeo de cambios) | `/signage/view/ajax.php` — público, JSON | `…@poll` | — | pendiente |
| `signage/view/packages.php` | (vista previa de paquete) | `admin.senalizacion.packages.preview` | `Senalizacion\PackageController@preview` | — | pendiente |
| `signage/mail/index.php` | GoTel管理 (お問い合わせ) | `admin.senalizacion.contact.create/store` | `Senalizacion\ContactController@create/store` | `web_signage_contacts` | pendiente (confirmar uso; Mailable en cola) |
| `signage/pdf/invoice.php` | 請求書 | `admin.senalizacion.invoices.show` | `Senalizacion\BillingPdfController@invoice` | — | pendiente (confirmar uso; PDF del paquete de S1) |
| `signage/pdf/receipt.php` | 領収書 | `admin.senalizacion.receipts.show` | `…@receipt` | — | pendiente (ídem) |
| `signage/inc/*`, `basic_authentication.php` | (núcleo, login `?s=`) | — | sesión del admin + Gate `manageSignage` | — | pendiente (reemplazado) |
| `signage/_tool.php` | (chmod 777) | — | — | — | descartado: peligroso, sin función |
| `signage/sample.php` … `sample4.php` | (pruebas) | — | — | — | descartado: pruebas de desarrollo |

**`digitalsignage/` (Flash → HTML5, mismo contrato).**

| Archivo legacy | Título (ja) | Ruta Laravel | Controlador@método | Tablas nuevas | Estado |
|---|---|---|---|---|---|
| `digitalsignage/index.php` → `index2.php` | digital signage | `GET /digitalsignage/`, `/digitalsignage/index.php`, `/index2.php` (`?shop_cd=&display_cd=`) — público | `Senalizacion\DigitalPlayerController@show` | — (lee `signage_*`) | pendiente (reproductor HTML5 que lee el mismo XML; orientación por `terminal_type`) |
| `hp/uploads/c7/display/<cd>.xml` (generado por `xml_up.php`) | (programa XML) | `GET /hp/uploads/c7/display/{cd}.xml` — público | `Senalizacion\DigitalFeedController@program` | — | pendiente (misma forma XML `<p cd time title …>`) |
| `ajax_xml_check.php`, `ajax_xml_check_2.php` | (sondeo XML) | mismas URL — público | `Senalizacion\DigitalPollController@xml` | — | pendiente (responde `<script>` igual que hoy) |
| `ajax_display_config_check.php`, `_2.php` | (sondeo config) | mismas URL | `…@displayConfig` | — | pendiente |
| `ajax_network_check.php` | (red) | misma URL | `…@network` (texto `確認できました`) | — | pendiente |
| `ajax_print_coupon_crawl.php`, `ajax_print_coupon_craw_2l.php` | (cupón impreso por visita) | mismas URL | `Senalizacion\CouponCrawlController@visit` | — (lee `visits`, `coupons`, `shops`) | pendiente |
| `ajax_touch_print_coupon_crawl*.php` | (cupón táctil) | mismas URL | `…@touch` | — | pendiente |
| `ajax_shinkyaku_print_coupon_crawl*.php` | (cupón cliente nuevo) | mismas URL | `…@newCustomer` | — | pendiente |
| `ajax_kaiin_raiten_print_coupon_crawl.php` | (cupón socio por visita) | misma URL | `…@memberVisit` | — | pendiente |
| `ajax_Mujyouken_print_coupon_crawl_2.php` | (cupón sin condición) | misma URL | `…@unconditional` | — | pendiente |
| `ajax_kaiin_mycoupon_print_crawl.php` | (cupón del socio, marca impreso/usado) | misma URL | `…@memberCoupon` | — (escribe `coupon_customer` de S3 vía su servicio) | pendiente |
| `roulette.php` (+ `.swf`, `tig_game/`) | ルーレット | `/digitalsignage/roulette.php` | `Senalizacion\DigitalGameController@roulette` | — | pendiente (rehacer en HTML5/Canvas) |
| `slot.php` (+ `.swf`) | スロット | `/digitalsignage/slot.php` | `…@slot` | — | pendiente (ídem) |
| `fla/floormap/ajax_print_crawl.php` | (mapa de planta + 求人) | misma URL | `Senalizacion\DigitalFeedController@floormap` | — (lee `job_postings`, `coupons`) | pendiente |
| `fla/immovables/getFlash.php`, `html/immovables/*` (6) | 不動産 | — | — | — | descartado: micro-sitio de otro sistema (`tb_madori`, `tb_item` no están en el CRM) y `index.php` roto en el legacy |
| `html/recipe/index.php`, `recipe_detail.php` | レシピ | — | — | — | descartado: contenido estático de demostración, sin datos; se rehace solo si el cliente lo pide |

## 2. Esquema (tablas nuevas)

Todas: `id()` (bigserial), `text` en vez de `varchar`, `smallint` con comentario + enum en vez de booleanos, `status` default 1, `timestamps()`, `softDeletes()` (reemplaza `disp_flg='9'`). `legacy_cd` (`bigint`, índice único) en las tablas que importa S1, para poder resolver las referencias cruzadas (`<>`, `cd` en URL de reproductores). `tb_shop_cd` nulo/0 = 全店舗 → `shop_id` nulo. Fechas texto/epoch → `timestamp`/`date` con Carbon al importar. `hyouji_flg` → `display_flg` (`0` oculto, `1` visible).

### 2.1 `mypage`

**`app_images`** — reemplaza `contents_master` `aplicover|aplibanner|mainimage|apli_home_thumbnail` + archivos numerados en `hp/uploads/c6/<campo>/` + `mypage_config` serializado.

| Columna | Tipo | Origen legacy |
|---|---|---|
| `section` | smallint | `1` cover (`aplicover`), `2` banner (`aplibanner`), `3` main (`mainimage`, `home_image_flg`), `4` thumbnail (`apli_home_thumbnail`, `home_thum_flg`) — enum `AppImageSection` |
| `position` | integer | índice del hueco (nº de archivo / posición en `text_data` separado por `||`) |
| `path` | text | ruta en `Storage::disk('public')` (`mypage/app-images/<section>/<position>.<ext>`) |
| `link_url` | text null | `text_data` (`||`) del hueco |
| `banner_mode` | smallint null | solo banner: `1` デフォルト選択, `2` 自由選択 (`cell_status`) |
| `all_shops_flg` | smallint | `mypage_config[sección][hueco][0]` (`1` = 全店舗) |
| `status`, timestamps, softDeletes | | |

**`app_image_shop`** (`app_image_id`, `shop_id`, `id`) — `mypage_config[sección][hueco][shop_cd] = 1`.

**`app_settings`** (fila única, `id = 1`): `customer_group_display_flg` smallint (`apli_customer_group_disp`), `home_comment` text (`apli_home_comment`), `comment_flg` smallint (`mypage_config['comment_flg']`). *Si S1 crea una tabla genérica de configuración (`contents_master`), se mueve allí: ver Pedidos.*

**`recommendations`** ← `tb_hp_recommend`

| Columna | Tipo | Origen |
|---|---|---|
| `legacy_cd` | bigint unique | `cd` |
| `shop_id` | bigint null FK | `tb_shop_cd` (0/null → null) |
| `published_on` | date | `disp_date` (`Y/m/d` texto) |
| `title` | text | `title` |
| `body` | text null | `naiyou` |
| `link_url` | text null | `link` (admite `tel:`) |
| `link_type` | smallint null | `link_type` |
| `image_path` | text null | `img_flg` + archivo `hp/uploads/c6/user_hp/recommend/{cd}/` |
| `image_type` | smallint null | `img_type` |
| `image_link_url` | text null | `pic_link` |
| `push_flg` | smallint | (formulario `push`; no se guardaba) `0`/`1` |
| `display_flg` | smallint | `hyouji_flg` (`9` → softDelete; la app lo excluye) |
| `updated_at` | | `update` |

**`job_categories`** ← `tb_recruit_category`: `legacy_cd`, `shop_id` (`tb_shop_cd`), `name` (`shokushu`), `created_at` (`touroku_date`), `updated_at` (`update_date`), `deleted_at` (`disp_flg='9'`).

**`job_postings`** ← `tb_recruit`

| Columna | Origen | Columna | Origen |
|---|---|---|---|
| `legacy_cd` | `cd` | `holidays` | `kyuka` |
| `shop_id` | `tb_shop_cd` | `benefits` | `taigu` |
| `job_category_id` | `boshu_shokushu` → `job_categories.legacy_cd` | `image1_path`, `image2_path` | `pic_flg1`, `pic_flg2` + archivo |
| `headline` | `boshu_headline` | `company_name` | `kaisha_name` |
| `description` | `shigoto_naiyou` | `street_address` | `add` |
| `employment_type` | `koyou_keitai` (texto libre) | `tel` | `tel` |
| `requirements` | `oubo_shikaku` | `fax` | `fax` |
| `work_location` | `kinmu_chi` | `business_description` | `jigyou_naiyou` |
| `work_hours` | `kinmu_jikan` | `how_to_apply` | `oubo_houhou` |
| `salary` | `kyuyo` (texto) | `interview_location` | `mensetsuchi` |
| `contact_person` | `tantou` | `created_at` / `updated_at` / `deleted_at` | `touroku_date` / `update_date` / `disp_flg='9'` |

**`lottery_settings`** (fila única) ← `contents_master` `category='tb_c6_kujibiki'`: `enabled_flg` (`kujibiki_disp_flg`), `max_tries` (`kujibiki_max`), `limit_flg` (`kujibiki_limit_flg`), `prize_count` (`prize_count`), `name` (`kujibiki_name`), `image_path` (`kujibiki_img`), más las variantes de entrada `enabled_on_enter_flg`, `max_tries_on_enter`, `limit_on_enter_flg`, `rate_on_enter`, `limit_on_form_flg`, `rate_on_form` (`*_enter`, `*_form`). **`lottery_prizes`**: `rank` smallint (enum `LotteryRank` existente: 1 diamante…4 bronce), `quantity` (`kujibiki_<r>_num`), `rate` numeric(5,2) (`kujibiki_<r>_rate`). La probabilidad de ハズレ se calcula (complemento), no se guarda. `kujibiki_count`/`kujibiki_log` son registros de uso: los lleva S3 (sorteo).

### 2.2 `terminales`

**`paper_cards`** ← `tb_url_list`: `legacy_cd` (`cd`), `code` text (`id`; único entre no borrados; vacío → `CRM` + `cd` 9 dígitos), `url`, `title`, `display_flg` (`hyouji_flg`; `hyoujiOff` = 停止), `deleted_at` (`disp_flg='9'`). Índice único parcial `code WHERE deleted_at IS NULL`.

**`felica_cards`** ← `tb_customer_card`

| Columna | Origen |
|---|---|
| `legacy_cd` | `cd` |
| `shop_id` / `terminal_id` | `tb_shop_cd` / `tb_terminal_cd` (FK a tablas de S1) |
| `customer_id` | `tb_customer_cd` (unique parcial: un socio = una tarjeta) |
| `staff_id` | `tb_staff_cd` (sin FK hasta que S1 tenga `staff`) |
| `felica_no` | `tid` (IDm) |
| `card_id`, `card_type`, `card_type_expiry`, `card_status` | `cid`, `typ`, `typ_exp`, `sts` |
| `name`, `comment` | `name`, `comment` |
| `point`, `lost_flg` | `point`, `lost` |
| `type` | smallint `type` |
| `expires_on` | `limit_date` |
| `terminal_no` | `terminal_no` (androidId del lector) |
| `created_at`/`updated_at` | `touroku_date`/`update_date` |

El borrado legacy es físico: aquí softDelete + `customers.felica_no = null` (S2).

**`signage_systems`** ← `tb_dispaly_systems` (catálogo de 「システム」 que muestra la pantalla): `legacy_cd`, `shop_id`, `name`, `system_type` smallint, `system_flg` smallint, `non_coupon_flg`, `sort` (`rank`).

**`signage_materials`** ← `tb_screensaver`

| Columna | Origen |
|---|---|
| `legacy_cd` | `cd` |
| `shop_id` | `tb_shop_cd` |
| `title`, `comment` | `title`, `comment` |
| `type` | smallint `type_flg` (`1` imagen, `2` vídeo, …) — enum `SignageMaterialType` |
| `path` | `data`/`pic_flg` + `pic_extension` → Storage `signage/materials/` |
| `file_type` | `filetype` |
| `duration_seconds` | integer `play_frame_sec` |
| `select_flg` | `select_flg` |
| `category_value` | `categorize_value_0` |
| `sort` | `rank` |
| `signage_display_id` | `tb_display_cd` (null si 0) |

**`signage_programs`** ← `tb_c7_movie_program`: `legacy_cd`, `shop_id`, `name`, `comment`, `terminal_code` (`terminal_id`), `terminal_type` smallint, `sort` (`rank`), `action_flg` (`act_flg`), `member_flg` (`kaiin_flg`), `felica_action_flg` (`feli_act_flg`), `mypage_action_flg` (`mypage_act_flg`), `display_config` (`disp_conf`), `ticker_text` (`telop_comment`), `vertical_setting`, timestamps (`touroku_date`/`update_date`; `updated_at` es lo que sondea el reproductor).

**`signage_material_signage_program`** (pivote) ← `tb_screensaver_cds` (`<>`): `signage_program_id`, `signage_material_id`, `position` (orden de la lista), `id`.

**`coupon_signage_program`** (pivote) ← `tb_mycoupon_master_cds`, `feli_…`, `mypage_…`, `system_…` (`<>`): `coupon_id`, `signage_program_id`, `usage` smallint (`1` general, `2` felica, `3` mypage, `4` system), `position`.

**`signage_displays`** ← `tb_display`: `legacy_cd`, `shop_id`, `name`, `comment`, `terminal_code` (`terminal_id`), `terminal_types` json (`terminal_type` `<>`: horizontal/vertical), `sort` (`rank`), `display_config` (`disp_conf`), `action_flg`, `member_flg`, timestamps. Pivotes **`signage_display_signage_program`** (`tb_c7_movie_program_cds`, `position`), **`signage_display_signage_system`** (`tb_dispaly_systems_cds`) y cupones en `coupon_signage_display` (`tb_mycoupon_master_cds`).
`tb_master_cd` (todas): no se usa en ninguna pantalla vigente → no se migra.

**FeliCa / terminales:** `tb_terminal` → `terminals` (S1). Columnas que necesito (pedido): `sort` (`rank`), `terminal_no`, `model`, `type`, `comment`. Beacons: sin tablas (BD externa).

### 2.3 `senalizacion` (`signage/`, BD `admin`)

Se importan desde la BD `admin` a tablas propias del CRM (no conexión adicional).

| Tabla nueva | Origen | Columnas |
|---|---|---|
| `web_signage_terminals` | `terminal` | `legacy_cd` (`cd`, lo usa `view/?cd=`), `name`, `web_signage_package_id` (`packages_cd`), `orientation` smallint (`display_direction`: 1 縦, 2 横), `screen_count` (`display_number`), `layout` text (`display_type` = nombre del diseño `h_*`/`v_*`), `status` (`flg`) |
| `web_signage_packages` | `packages` | `legacy_cd`, `name`, `orientation`, `screen_count`, `layout` |
| `web_signage_cycles` | `cycle` | `legacy_cd`, `web_signage_package_id`, `frame_no` (`flame_cd`), `items` jsonb (`img_path_ary` + `action_ary` **unserialize en el importador, con `allowed_classes=false`** → `[{content_id, action}]`), `repeat_type` smallint (`schedule_repeat_type`: 1 siempre, 2 franja diaria, 3 rango de fechas), `starts_at`/`ends_at` (`schedule_start_time`/`_end_time`; tipo 2 se guarda como `time`: columnas `daily_start`, `daily_end`), `status` |
| `web_signage_content_categories` | `contents_category` | `legacy_cd`, `width`, `height`, `name` |
| `web_signage_contents` | `contents` + `movie_contents` | `legacy_cd`, `kind` smallint (1 imagen, 2 vídeo), `web_signage_content_category_id`, `name`, `path` (Storage `signage/web/<w>x<h>/<cd>.<ext>`) |
| `web_signage_files` | `signage` | `name`, `path`, `mime` |
| `web_signage_contacts` | `contact` | `customer_ref`, `body`, `sent_at` |

`customer` / `shop` de la BD `admin` (datos de facturación GoTel y hash de acceso `email01`+`pass`): no se migran como tablas nuevas hasta confirmar el uso de `mail/` y `pdf/` (Pedidos a S0).

### 2.4 Enums nuevos (`app/Enums/<Modulo>/`)

`Mypage\AppImageSection`, `Mypage\BannerMode`, `Terminales\SignageMaterialType`, `Terminales\SignageCouponUsage`, `Terminales\SignageTerminalType`, `Senalizacion\Orientation`, `Senalizacion\RepeatType`, `Senalizacion\ContentKind`.

## 3. Servicios viejos → Laravel 13

| Legacy | Reemplazo |
|---|---|
| `hatuban` (`SELECT … FOR UPDATE` + `kazu+1`) | PK `bigserial`; `legacy_cd` conserva el código viejo |
| `disp_flg='9'` | `SoftDeletes` |
| `hyouji_flg`, `pic_flg`, banderas texto | `smallint` + enum |
| Listas `<>` / `||` en columnas, `serialize()` | Tablas pivote con `position`; `jsonb` (sin `unserialize` en runtime) |
| Subida a `hp/uploads/c6|c7/…` + `temp/` + `system("find … -mtime +1 -exec rm")` | `Storage::disk('public')`; comando `mypage:prune-temp-uploads` registrado en `MypageServiceProvider` (Scheduler diario); URL viejas `/hp/uploads/...` redirigidas (Pedidos) |
| Miniaturas generadas con GD a mano | Servicio `ImageResizer` (paquete de imágenes que defina S1; mientras, GD nativo) |
| `Push::exec` (`fsockopen` a `b.g-api.net`) | Job `App\Jobs\Mypage\SendRecommendationPush` (cola `database`, `Http::get`, host en `config/services.php` `push.host`) si `push_flg=1` y la app está activa |
| `xml_up.php` escribe `hp/uploads/c7/display/<cd>.xml` al guardar | `SignageFeedBuilder` genera el XML al vuelo en `GET /hp/uploads/c7/display/{cd}.xml` (misma URL y forma); caché invalidada en el evento `saved` del programa/pantalla |
| `xml_info_up.php` (`floormap.xml`) | `SignageFeedBuilder::floormap()` desde `job_postings` |
| QR por `file_get_contents` HTTP a `qr_img.php` | Generador QR en PHP (paquete que defina S1) devuelto como descarga |
| CSV con `header()` / Flash | `StreamedResponse` |
| mPDF propio de `signage/pdf` + HTML con autenticación básica | Paquete PDF de S1 renderizando la vista Blade directamente (sin pedir la propia URL) |
| `mail/index.php` con `mb_send_mail` | `Mailable` en cola |
| `kengen->myKengen[código]` (1014-1036), `rank=='master'`, `isDevelopmentAuthorityFromIP` | Policies/Gates de S1 (`MypagePolicy`, `TerminalPolicy`, Gate `manageSignage`) |
| `md5(session_id())` como token y `tbNo` arbitrario (`felica/ajax_del.php`) | CSRF de Laravel + rutas por modelo (lista blanca implícita) |
| `?s=<hash>` (acceso a `signage/`) | Sesión del admin + Gate `manageSignage` |
| `beacon_db.php` (`pg_connect` externo) | Conexión `beacon` en `config/database.php`; modelos con `$connection = 'beacon'` |
| jQuery UI (diálogos, sortable) / AJAX | Blade + Alpine/Vite del layout de S1; endpoints JSON con Form Request |
| Polling JS del reproductor Flash | Mismo polling en el reproductor HTML5 (mismas URL `ajax_*`) |
| `signage` franja `to_char(…,'hh24:mm')` (bug: `mm` = mes) | `SignageScheduleResolver` con comparación de horas correcta (anotado en Decisiones) |

## 4. Orden de implementación

| # | Submódulo | Contenido | Depende de | Puntos |
|---|---|---|---|---|
| 0 | Base del grupo | Rutas, menú, `lang/ja`, Storage, enums, factories/seeders | S1: grupo `admin`, layout, Policies | 2 |
| 1 | `mypage` マイコレ + `app_settings` | 4 secciones en una pantalla, subida, por tienda | S1 (`shops`), S5 (`mpa_*` leerán `app_images`) | 5 |
| 2 | `mypage` オススメ + push | CRUD + Job | S1 (`config/services.php`), S5 (`appview`) | 3 |
| 3 | `mypage` 求人 + 職種 | CRUD | S1 (`shops`) | 4 |
| 4 | `mypage` ガチャリン設定 | Configuración + premios | S3 (sorteo y `coupons.lottery_rank`) | 2 |
| 5 | `terminales` 電子看板 (素材, プログラム, ディスプレイ) + feed XML | CRUD + pivotes + `SignageFeedBuilder` | S3 (`coupons`), 3 (floormap), pedido de rutas públicas | 8 |
| 6 | `senalizacion` `digitalsignage` HTML5 | Reproductor, sondeos, cupones impresos, ruleta, tragaperras | 5; S2 (`visits`, `customers`), S3 (`coupon_customer`) | 8 |
| 7 | `senalizacion` `signage/` | CMS (端末, パッケージ, サイクル, コンテンツ) + reproductor `view/` | pedido de rutas públicas | 14 |
| 8 | `senalizacion` GoTel / PDF | Contacto + 請求書/領収書 | confirmación de uso (S0), paquete PDF (S1) | 3 |
| 9 | `terminales` ペーパーサイネージ | CRUD + QR + 集計 + CSV | S2 (`customers` con referencia a la tarjeta), paquete QR (S1) | 4 |
| 10 | `terminales` ICカード (FeliCa) | Terminales + tarjetas | S1 (`terminals` con columnas nuevas), S2 (`customers.felica_no`) | 4 |
| 11 | `terminales` ビーコン | Lista/detalle externos | S1 (conexión `beacon`), BD externa accesible | 3 |
| | **Total** | | | **60** |

(El análisis daba ~115 puntos con kiosk, touchcation y category4/6 completos; lo descartado explica la diferencia.)

## 5. Pedidos

**A S0 / S1 (núcleo)**
1. Archivo de rutas públicas para dispositivos (sin `auth:admin`, sin CSRF, sin prefijo) — el mismo que pidió S6 (`routes/dispositivos.php`) o `routes/modulos/senalizacion-publico.php` cargado fuera del grupo `admin`. URL literales con `.php`: `/signage/view/*`, `/digitalsignage/*`, `/hp/uploads/c7/display/{cd}.xml`.
2. Redirección genérica `GET /hp/uploads/{path}` → `Storage` (la usan la app y los reproductores; compartida con S5).
3. `terminals`: columnas `sort` (`rank`), `terminal_no`, `model`, `type`, `comment` (mapeo de `tb_terminal`).
4. Paquetes: imágenes (miniaturas), QR, PDF con fuente japonesa.
5. `config/services.php` → `push.host` (`b.g-api.net` / `b-develop.g-api.net`) y `config/database.php` → conexión `beacon` (`DB_BEACON_*`).
6. Policies/Gates equivalentes a `kengen` 1014–1017, 1023, 1032, 1035, 1036 y al menú de category7; Gate `manageSignage`.
7. Importador: mapeo de la sección 2 (incluida la BD `admin` de `signage/` y los archivos de `hp/uploads/c6|c7` y `signage/upload/`). Si S1 crea una tabla genérica para `contents_master`, decidir si `app_settings`/`lottery_settings` se mueven allí.
8. Confirmar con Antony si se usan `signage/mail` (GoTel) y `signage/pdf` (請求書/領収書); y si se conserva `beacon` (servidor externo caído).

**A S2 (clientes):** `customers.felica_no` (text, null) y la referencia de la tarjeta de papel (`paper_card_id` o el código de `tb_url_list` con el que se registró el cliente) para el 集計.
**A S3 (promoción):** lectura de `lottery_settings`/`lottery_prizes` desde el sorteo; servicio para marcar cupón impreso/usado (`ajax_kaiin_mycoupon_print_crawl`).
**A S5 (integraciones):** `mpa_getCoverFlow`, `mpa_getTopBanner`, `mpa_corresponding` y la lista de オススメ de `appview` leen `app_images`, `app_settings` y `recommendations` (modelos de S4, solo lectura).

## 6. Decisiones (de este diseño)

- category4 completo y en category6 todo lo que solo leía `hp/` (topics, header, freepage, diseño, colores, 携帯HP, フォトギャラリー): descartado: sin consumidor. De `mobile_hp_edit.php` solo sobreviven las dos opciones que lee la app.
- Módulo `web` sin pantallas.
- `kiosk` (category7) y touchcation: descartados (S0, 2026-11-26).
- Bugs que se corrigen: 最後に追加 en `movie_program_register` (columna inexistente) → pivote con `position`; `movie_contents_category` borraba de `contents_category`; franja `hh24:mm`; desplegable de 職種 vacío con una tienda; borrado de imagen que dejaba derivados.
- `micro-sitios` de `digitalsignage` (inmuebles, recetas) descartados.
- `.swf` ya no se acepta en las subidas.
