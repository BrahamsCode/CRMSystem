-- Datos del backup del legacy (crm2019.backup, cargado con scripts/bd-legacy.sh) que necesitan las pruebas de contrato de S5.
-- admin_master va reducido a las columnas que usa la API (la fila completa trae contraseñas).





INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1472606202001578864, 'mypage', 'privacy_policy_text', '当社及び共同企業は今回ご登録頂きます会員情報を
当社ならびに共同企業の取り扱い商品等のご案内、その他、各社のプライバシーポリシー
に定める目的の範囲内で利用いたします。当社及び共同企業(以下、「当社等」といいます。)
は、今回ご登録頂いたお客様情報(個人情報を含みます。)を、以下の場合において利用するものとします。

1.当社等が取り扱う各種商材に関する案内をする場合。
2.当社等がキャンペーンやアンケートを実施する場合。
3.マーケティングデータの調査、分析、新たなサービス開発を行う場合。
4.当社等及び業務定期企業に提供する統計資料の作成を行う場合。
5.法令の規定に基づく場合。


会員への登録が上記への同意とさせて頂きます。

共同企業：アース㈱、㈱エアー、㈱アイディ、モバイルステーション㈱', NULL, NULL, '1', '1', 1472606202, 1472640633);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1359639208001710037, 'tb_c3_coupon', 'atokara_disp_flg', '', 1, NULL, '1', '1', 1359639208, 1359639221);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1359639208002193197, 'tb_c3_coupon', 'atokara_disp_flg', '', 2, NULL, '1', '1', 1359639208, 1359639221);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1367901802001905512, 'tb_c1_customer', 'name_input_mode_flg', '1', NULL, NULL, '1', '1', 1367901802, 1367901802);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1476950655001229176, 'tb_c9_yoyaku', 'mail_send_status', '1', NULL, NULL, '1', '1', 1476950655, 1480579846);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1438235736001145984, 'tb_c3_anketo2', 'set_anketo_cd', NULL, 5, NULL, '1', '1', 1438235736, 1501829091);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1346391173001561506, 'tb_c6_osusume', 'kensu', '', 1, 1, '1', '1', 1346391173, 1431065601);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1532061926008103550, 'tb_c9_yoyaku', 'free_yoyaku_disp_flg', '1', 8, NULL, '1', '1', 1532061926, 1532327424);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1532061926008021920, 'tb_c9_yoyaku', 'free_yoyaku_name', '指名なし', 8, NULL, '1', '1', 1532061926, 1532327424);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1532061926008507989, 'tb_c9_yoyaku', 'free_yoyaku_staff_select', '1', 8, NULL, '1', '1', 1532061926, 1532327424);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1532061926008700730, 'tb_c9_yoyaku', 'free_yoyaku_timeline_staff', '1', 8, NULL, '1', '1', 1532061926, 1532327424);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1450059443, 'tb_c6_user_hp', 'banner', '', NULL, NULL, '1', '1', 1450059443, 1450059443);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1486011403004652657, 'tb_c9_yoyaku', 'free_yoyaku_disp_flg', '', 4, NULL, '1', '1', 1486011403, 1486011403);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1486011403004233168, 'tb_c9_yoyaku', 'free_yoyaku_name', '', 4, NULL, '1', '1', 1486011403, 1486011403);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1486011403004081073, 'tb_c9_yoyaku', 'free_yoyaku_staff_select', '1', 4, NULL, '1', '1', 1486011403, 1486011403);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1486011403004687383, 'tb_c9_yoyaku', 'free_yoyaku_timeline_staff', '', 4, NULL, '1', '1', 1486011403, 1486011403);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1538623531009008362, 'tb_c9_yoyaku', 'free_yoyaku_disp_flg', '1', 9, NULL, '1', '1', 1538623531, 1538623531);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1538623531009997585, 'tb_c9_yoyaku', 'free_yoyaku_name', '', 9, NULL, '1', '1', 1538623531, 1538623531);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1514274212, 'tb_c1_point_setting', 'mypage_config', 'a:1:{s:14:''home_image_flg'';a:2:{i:-1;s:1:''1'';i:2;s:1:''1'';}}', NULL, NULL, '1', '1', 1514274212, 1514275143);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1538623531009261231, 'tb_c9_yoyaku', 'free_yoyaku_staff_select', '1', 9, NULL, '1', '1', 1538623531, 1538623531);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1538623531009136257, 'tb_c9_yoyaku', 'free_yoyaku_timeline_staff', '1', 9, NULL, '1', '1', 1538623531, 1538623531);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1474951271002607165, 'tb_c9_yoyaku', 'free_yoyaku_disp_flg', '1', 2, NULL, '1', '1', 1474951271, 1519799582);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1474951271002670773, 'tb_c9_yoyaku', 'free_yoyaku_name', '', 2, NULL, '1', '1', 1474951271, 1519799582);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1474951271002449432, 'tb_c9_yoyaku', 'free_yoyaku_staff_select', '1', 2, NULL, '1', '1', 1474951271, 1519799582);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1474951271002541526, 'tb_c9_yoyaku', 'free_yoyaku_timeline_staff', '1', 2, NULL, '1', '1', 1474951271, 1519799582);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1514275143, 'tb_c1_point_setting', 'point_setting', '||||||||||||||||||', NULL, NULL, '1', '1', 1514275143, 1514275143);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1527046466006117263, 'tb_c9_yoyaku', 'free_yoyaku_disp_flg', '1', 6, NULL, '1', '1', 1527046466, 1578387239);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1527046466006899439, 'tb_c9_yoyaku', 'free_yoyaku_name', '', 6, NULL, '1', '1', 1527046466, 1578387239);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1527046466006580607, 'tb_c9_yoyaku', 'free_yoyaku_staff_select', '1', 6, NULL, '1', '1', 1527046466, 1578387239);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1527046466006672041, 'tb_c9_yoyaku', 'free_yoyaku_timeline_staff', '', 6, NULL, '1', '1', 1527046466, 1578387239);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1515484057001315972, 'mycolle', 'app_layout_mode', '1', NULL, NULL, '0', '1', 1515484057, 1525938187);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1461212551001944324, 'tb_c6_user_hp', 'apli_home_comment', 'a:2:{i:0;s:0:"";i:1;s:0:"";}', NULL, NULL, '1', '1', 1461212551, 1610525824);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1460450463001237183, 'tb_c6_user_hp', 'apli_customer_group_disp', NULL, 0, NULL, '1', '1', 1460450463, 1578543013);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1519003245001485975, 'mycolle', 'disabled_push_title_flg', '2', NULL, NULL, '1', '1', 1519003245, 1578543810);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1521784773001758618, 'tb_c1_customer', 'reserve_button_position', '1', NULL, NULL, '1', '1', 1521784773, 1521786527);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1576727487, 'tb_c6_user_hp', 'aplicover', '||||||||||||||||||', NULL, NULL, '1', '1', 1576727487, 1576727487);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1576728152, 'tb_c6_user_hp', 'apli_home_image', '||||||||||||||||||', NULL, NULL, '1', '1', 1576728152, 1576728152);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1665503775, 'tb_c6_user_hp', 'aplibanner', '||||||||||||||||||', NULL, NULL, '1', '1', 1665503775, 1665503775);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1600423958, 'shiire-ya', 'btn_disp_flg', 'http://shiire-ya.com/onlinestore/', NULL, NULL, '1', '', 1600423958, 1600423958);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1461749689, 'tb_c6_user_hp', 'mypage_config', 'a:7:{s:6:''banner'';a:1:{i:-1;s:1:''1'';}s:5:''cover'';a:1:{i:-1;s:1:''1'';}s:13:''home_thum_flg'';a:1:{i:-1;s:1:''1'';}s:14:''home_image_flg'';a:4:{i:-1;s:1:''1'';i:2;s:1:''1'';i:3;s:1:''1'';i:5;s:1:''1'';}s:11:''comment_flg'';a:1:{i:-1;s:1:''1'';}s:11:''cell_status'';a:21:{i:0;s:1:''1'';i:1;s:1:''1'';i:2;s:1:''1'';i:3;s:1:''1'';i:4;s:1:''1'';i:5;s:1:''1'';i:6;s:1:''1'';i:7;s:1:''1'';i:8;s:1:''1'';i:9;s:1:''1'';i:10;s:1:''1'';i:11;s:1:''1'';i:12;s:1:''1'';i:13;s:1:''1'';i:14;s:1:''1'';i:15;s:1:''1'';i:16;s:1:''1'';i:17;s:1:''1'';i:18;s:1:''1'';i:19;s:1:''1'';i:20;s:1:''1'';}s:8:''cell_img'';a:21:{i:0;s:10:''beauty.png'';i:1;s:10:''beauty.png'';i:2;s:10:''beauty.png'';i:3;s:10:''beauty.png'';i:4;s:10:''beauty.png'';i:5;s:10:''beauty.png'';i:6;s:10:''beauty.png'';i:7;s:10:''beauty.png'';i:8;s:10:''beauty.png'';i:9;s:10:''beauty.png'';i:10;s:10:''beauty.png'';i:11;s:10:''beauty.png'';i:12;s:10:''beauty.png'';i:13;s:10:''beauty.png'';i:14;s:10:''beauty.png'';i:15;s:10:''beauty.png'';i:16;s:10:''beauty.png'';i:17;s:10:''beauty.png'';i:18;s:10:''beauty.png'';i:19;s:10:''beauty.png'';i:20;s:10:''beauty.png'';}}', NULL, NULL, '1', '1', 1461749689, 1665503848);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1665503848, 'tb_c6_user_hp', 'apli_home_thumbnail', '||||||||||||||||||', NULL, NULL, '1', '1', 1665503848, 1665503848);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1673586339, 'smatopi', 'environment', 'develop', NULL, NULL, '0', '1', 1673586338, 1673587215);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1673587215, 'smatopi', 'environment', 'production', NULL, NULL, '0', '1', 1673587215, 1673587696);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1673587696, 'smatopi', 'environment', 'develop', NULL, NULL, '1', '1', 1673587696, NULL);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1592808298001503208, 'mycolle', 'app_layout_mode', '', NULL, NULL, '1', '1', 1592808298, 1694160160);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1505902201, 'push_follow', 'last_push_date', NULL, 1793165101, NULL, '1', NULL, 1505902201, 1793165101);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1372042734001548998, 'tb_c9_yoyaku', 'free_yoyaku_disp_flg', '1', 1, NULL, '1', '1', 1372042734, 1728354518);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1372042734001189884, 'tb_c9_yoyaku', 'free_yoyaku_name', 'FREE', 1, NULL, '1', '1', 1372042734, 1728354518);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1372042734001628273, 'tb_c9_yoyaku', 'free_yoyaku_staff_select', '1', 1, NULL, '1', '1', 1372042734, 1728354518);
INSERT INTO public.contents_master (cd, category, field, text_data, int_data, float_data, disp_flg, hyouji_flg, touroku_date, update_date) VALUES (1372042734001991554, 'tb_c9_yoyaku', 'free_yoyaku_timeline_staff', '', 1, NULL, '1', '1', 1372042734, 1728354518);



INSERT INTO public.hatuban (koumoku, kazu) VALUES ('mailmag_sended_log', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('keiji', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('officialposition', 19);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('employmentsystem', 9);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('backup', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_access_mapping', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_stamp', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_staff_data', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_stamp_pattern', 79);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c2_pay_else_master', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_staff_shift', 150);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_package_master', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_digital_signage', 6);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_tomodachi_shoukai', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_play_time', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_schoolclass_config', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_schoolyear_config', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_user_card', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_blue_system', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_karute', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_setsubi', 11);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_hp_domain', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c2_item_sub_categorize', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_manage_words', 2);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_terminal_pkey', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_customer_card_pkey', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('admin_master', 2);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_customer_level', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_raiten_douki_master', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_kanji', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_free_yoyaku_rank', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('contents_master', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_kujibiki_count', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_kujibiki_log', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_mobile_template', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c2_catalog_sub', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_kengen_contents', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_module_authority', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_coupon_master', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_pos_regi_history', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_printer', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_pos_terminal_mac_address', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_flg_category_joint', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_order_main_pkey', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c2_sum_master', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c2_sum_master_pkey', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_yoyaku_order_main', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_flg_category', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_waribiki_joint', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_waribiki', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_felica_log', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_information_place', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_pos_terminal', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_place', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('belongpost', 20);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_point_rules', 11);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c2_catalog_categorys', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c2_card_master', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_shop_category', 5);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_nextouch_navi', 2);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_pos_catalog', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_karute_menu', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_karute_waribiki', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_change_model', 8);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c3_anketo2_sub', 3);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_hp_words', 2);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c3_anketo2_cate', 6);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c3_anketo2_form', 8);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_terminal', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_my_freepage', 8);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_raiten_rank_master', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_my_freepage_master', 7);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_point_setting', 5);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('kintai_tb_config', 7);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_url_list', 5);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('send_schedule', 73);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_yoyaku_config', 2);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_yoyaku_koumoku_master', 11);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_base_system_grandchild', 19);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_base_system_child', 115);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_hp_topics', 2);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_yoyaku_koumoku_category', 2);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_staff', 3);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_hp_recommend', 3);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_kingaku_rank_master', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_shukei_link', 2);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_stamp_oshirase_coupon', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_test_mail_address', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_touch_mode', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_job', 12);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_digital_catalog', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_digital_catalog_tag', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_digital_catalog_tag_items', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_display', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c7_movie_program', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_screensaver', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_dispaly_systems', 3);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_staff_works', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_sejutsu_cate', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_mobile_navi', 32);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_customer_card', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_customer_sub', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c2_category', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c2_item', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_staff_group', 2);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c2_catalog_search_form', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c2_catalog_search_cate', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c2_catalog_data', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c2_catalog_data', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c2_stock_count', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c2_stock', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c2_item_sub', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c2_shopping', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c2_how_to_sell', 5);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_mycoupon_used', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_mycoupon_print', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c3_anketo_sub', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_stamp_rules', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c3_anketo_form', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c3_anketo_cate', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c4_sitemap', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c4_contents', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c4_report', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_c2_catalog', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_customer_group', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_customer', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_card', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_coupon', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_shiire', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_information', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_recruit_category', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_recruit', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_reserve', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_hp_news', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_work_time', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_shop_work', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_hp_freepage', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_shop', 2);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_stamp_config', 2);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('mailmag', 55);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_staff_shift_kind', 22);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_mycoupon_master', 7);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('kintai_tb_hiduke', 4);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_area', 61);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_stamp_log', 2);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('raiten_rireki', 9);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_mailmag_analyze', 7);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('kintai_tb_kintai', 10);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('push_follow_master', 28);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('log', 2286);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_photo_album', 4);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_search_cate', 6);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_login_rireki', 660);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_search_form', 11);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_hp_header', 3);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_haikei', 9);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_customer_point', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_order_main', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_work_sche', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_hp_data', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_work_sche', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_order_sub', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_mail_template_category', 9);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_mail_template', 15);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_reserve_name', 2);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_nextouch_display', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_nextouch_movie_program', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_nextouch_screensaver', 1);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_bookmark', 61);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_kengen', 55);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_mycoupon', 52);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_yoyaku_uketsuke_day', 70);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_yoyaku', 120);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('tb_point_log', 49);
INSERT INTO public.hatuban (koumoku, kazu) VALUES ('push_master', 9486);



INSERT INTO public.sales_items (cd, disp_flg, hyouji_flg, touroku_date, update_date, name, display_name, display_title, discription, is_exchangeable_of_point, number_of_points_exchanged, discription_detail, available_shop_cds) VALUES (1, 1, 0, 1593397806, 1597794854, 'テスト', 'テスト', 'テスト', '<p>テスト</p>
', 0, NULL, '<p>テスト</p>
', '{1}');



INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (1, 1, 1, 1609897927001809158, -1, 1609897986, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (2, 1, 1, 1615949948001205726, -100, 1615950491, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 'mycolle:push', 4207);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (3, 1, 1, 1610793770001489815, -1, 1636529257, 0, NULL, NULL, NULL, NULL, NULL, NULL, 100, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (4, 1, NULL, 1610793770001489815, -1, 1636529271, 100, NULL, NULL, NULL, NULL, 10, NULL, NULL, NULL, NULL, NULL, 'mycolle:couponChange', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (5, 1, 1, 1610793770001489815, -1, 1636959315, 90, NULL, NULL, NULL, NULL, NULL, NULL, 123456, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (6, 1, NULL, 1610793770001489815, -1, 1636959331, 123546, NULL, NULL, NULL, NULL, 123456, NULL, NULL, NULL, NULL, NULL, 'mycolle:couponChange', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (7, 1, 1, 1609897927001809158, -1, 1659663600, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1000, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (8, 1, NULL, 1609897927001809158, -1, 1659663613, 1001, NULL, NULL, NULL, NULL, 10, NULL, NULL, NULL, NULL, NULL, 'mycolle:couponChange', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (9, 1, NULL, 1609897927001809158, -1, 1659663646, 991, NULL, NULL, NULL, NULL, 10, NULL, NULL, NULL, NULL, NULL, 'mycolle:couponChange', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (10, 1, NULL, 1609897927001809158, -1, 1659663674, 981, NULL, NULL, NULL, NULL, 10, NULL, NULL, NULL, NULL, NULL, 'mycolle:couponChange', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (11, 1, NULL, 1609897927001809158, -1, 1659663744, 971, NULL, NULL, NULL, NULL, 10, NULL, NULL, NULL, NULL, NULL, 'mycolle:couponChange', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (12, 1, NULL, 1609897927001809158, -1, 1659663805, 961, NULL, NULL, NULL, NULL, 10, NULL, NULL, NULL, NULL, NULL, 'mycolle:couponChange', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (13, 1, NULL, 1609897927001809158, -1, 1659664211, 951, NULL, NULL, NULL, NULL, 10, NULL, NULL, NULL, NULL, NULL, 'mycolle:couponChange', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (14, 1, NULL, 1609897927001809158, -1, 1659664271, 941, NULL, NULL, NULL, NULL, 10, NULL, NULL, NULL, NULL, NULL, 'mycolle:couponChange', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (15, 1, NULL, 1609897927001809158, -1, 1659664330, 931, NULL, NULL, NULL, NULL, 10, NULL, NULL, NULL, NULL, NULL, 'mycolle:couponChange', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (16, 1, NULL, 1609897927001809158, -1, 1659664374, 921, NULL, NULL, NULL, NULL, 10, NULL, NULL, NULL, NULL, NULL, 'mycolle:couponChange', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (17, 1, 1, 1616386984001912810, -1, 1688956751, 0, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (18, 1, 1, 1616386984001912810, -1, 1688956886, 2, NULL, NULL, NULL, NULL, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (19, 1, 1, 1660013029001173426, -1, 1688958152, 0, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (20, 1, 1, 1660013029001173426, -1, 1688958165, 3, NULL, NULL, NULL, NULL, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (21, 1, 1, 1660013029001173426, -1, 1688959025, 0, NULL, NULL, NULL, NULL, NULL, NULL, 10, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (22, 1, 1, 1660013029001173426, -1, 1688959047, 10, NULL, NULL, NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (23, 1, 1, 1660013029001173426, -1, 1688959135, 5, NULL, NULL, NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (24, 1, 1, 1660013029001173426, -1, 1688959205, 0, NULL, NULL, NULL, NULL, NULL, NULL, 12, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (25, 1, 1, 1660013029001173426, -1, 1688959479, 12, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (26, 1, 1, 1616386984001912810, -1, 1688960101, 0, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (27, 1, 1, 1660013029001173426, -1, 1688960254, 11, NULL, NULL, NULL, NULL, 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (28, 1, 1, 1660013029001173426, -1, 1688960430, 0, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (29, 1, 1, 1660013029001173426, -1, 1688960689, 3, NULL, NULL, NULL, NULL, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (30, 1, 1, 1616386984001912810, -1, 1688960793, 2, NULL, NULL, NULL, NULL, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (31, 1, 1, 1616386984001912810, -1, 1688960835, 0, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (32, NULL, NULL, 1610793770001489815, NULL, 1704594601, 90, NULL, NULL, NULL, NULL, 90, NULL, NULL, NULL, NULL, NULL, '7', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (33, NULL, NULL, 1615949948001205726, NULL, 1704594601, 1, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, '7', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (34, NULL, NULL, 1609897927001809158, NULL, 1704594601, 911, NULL, NULL, NULL, NULL, 911, NULL, NULL, NULL, NULL, NULL, '7', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (35, NULL, NULL, 1616386984001912810, NULL, 1704594601, 4, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, NULL, NULL, '7', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (36, 1, 1, 1660013029001173426, -1, 1721719416, 0, NULL, NULL, NULL, NULL, NULL, NULL, 100, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (37, NULL, NULL, 1660013029001173426, NULL, 1737253801, 100, NULL, NULL, NULL, NULL, 100, NULL, NULL, NULL, NULL, NULL, '7', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (38, 1, 1, 1610794236001207373, -1, 1741160088, 0, NULL, NULL, NULL, NULL, NULL, NULL, 100, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (39, 1, NULL, 1610794236001207373, -1, 1741160111, 100, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, 'mycolle:couponChange', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (40, 1, 1, 1734397808001958208, -1, 1741160128, 0, NULL, NULL, NULL, NULL, NULL, NULL, 10, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (41, 1, NULL, 1734397808001958208, -1, 1741160150, 10, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, 'mycolle:couponChange', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (42, 1, NULL, 1734397808001958208, -1, 1741160212, 9, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, 'mycolle:couponChange', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (43, 1, NULL, 1734397808001958208, -1, 1741160238, 8, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, 'mycolle:couponChange', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (44, 1, NULL, 1610794236001207373, -1, 1741161089, 99, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, 'mycolle:couponChange', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (45, 1, 1, 1756279577001085976, -1, 1756279586, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (46, NULL, NULL, 1610794236001207373, NULL, 1756693801, 98, NULL, NULL, NULL, NULL, 98, NULL, NULL, NULL, NULL, NULL, '7', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (47, NULL, NULL, 1734397808001958208, NULL, 1756693801, 7, NULL, NULL, NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, '7', NULL);
INSERT INTO public.tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, sashihiki_kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, meisai_mail, raiten_shori, disp_flg, action_flg, push_master_cd) VALUES (48, NULL, NULL, 1756279577001085976, NULL, 1771813801, 1, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, '7', NULL);



INSERT INTO public.tb_shop (cd, master_cd, kaishamei, kaishamei_kana, zip, pref, add_kana, add, add_number, add_build, add_build_kana, tel, fax, email, tantou, tantou_kana, touroku_date, update_date, shop_url, service_kubun, disp_flg, kyuyo_shimebi, kyuyo_marume, start_work_time, break_time, kaisha_data, floor_name, pic_flg, point_percentage, keisan_flg, shiyou_point_jyougen, point_percentage_genkin, point_percentage_card, hyouji_flg, stamp_percentage_genkin, stamp_percentage_card, pos_domain, shiire_id, shiire_pass, pos_package, sns, sns_info, shop_category_cd, mycolle_tel, latitude, longitude, login_point_settings, introduce_settings, customer_registration_settings, beacon_visiting_area, beacon_accounting_area, login_default_point_settings, area_cd, is_closed, is_new_store, new_store_start_date, new_store_end_date) VALUES (1, 1, 'shop', '', '', '', '', '', '', '', '', '09012345678', '', 'master@master.master', '管理者', '', '1773305449', '', 'http://localhost/hp/m/', NULL, '1', NULL, '15', '9<>00', '60', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0, '09012345678', NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0, 0, 0, '', '');







INSERT INTO public.admin_master (cd, point_shikkou_kikan, domain, application_use_flg, related_features) VALUES (1, 180, 'localhost', '1', '<><>');
