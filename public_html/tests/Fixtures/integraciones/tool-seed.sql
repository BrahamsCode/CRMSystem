-- Datos sintéticos para las fixtures de tool/api y CTI (tests/Fixtures/integraciones/tool/).
-- Se cargan después de legacy-base.sql y legacy-seed.sql. Nada sale del backup del legacy:
-- getStaffList devuelve tb_staff.* (contraseñas incluidas), así que aquí solo hay valores inventados.

-- Segunda tienda
INSERT INTO tb_shop (cd, master_cd, kaishamei, disp_flg) VALUES (2, 1, '支店', '1');

-- Personal: activos, uno borrado y uno con disp_flg NULL;歩合 real para ver su texto
INSERT INTO tb_staff (cd, office_cd, shop_cd, id, name, name_kana, login_id, login_pass, disp_flg, rank, kingaku_buai, touroku_date, update_date) VALUES
    (1, 1, 1, '000001', '管理者', 'カンリシャ', 'master', 'fixture-master', '1', NULL, 0, '1700000000', '1700000000'),
    (2, 1, 1, '1100002', 'スタッフA', 'スタッフエー', '1100002', 'fx-a', '1', 2, 12.5, '1700000000', '1700000000'),
    (3, 1, 2, '2100003', 'スタッフB', 'スタッフビー', '2100003', 'fx-b', '1', 1, 0.1, '1700000000', '1700000000'),
    (4, 1, 2, '2100004', '退職者', 'タイショクシャ', '2100004', 'fx-c', '9', 3, 0, '1700000000', '1700000000'),
    (5, 1, 2, '2100005', '区分なし', 'クブンナシ', '2100005', 'fx-d', NULL, 4, 0, '1700000000', '1700000000');

-- Socios: teléfono, sexo, 備考 con HTML y salto, 指名担当者 y 紹介者 (para CTI)
UPDATE tb_customer SET tel = '0612345678', kojin_sex = '男性', bikou = E'メモ<1>\n2行目', last_raiten_date = '2023/11/15',
    tb_staff_cd = 2, shoukai_tb_customer_cd = 1610793770001489815, job = '会社員'
WHERE cd = 1609897927001809158;
UPDATE tb_customer SET last_raiten_date = '2023/11/10', kojin_sex = '女性' WHERE cd = 1610793770001489815;

-- 関連機能: related_features = '<モード><1=ポイント><cate>' → categoría 10 con 3 campos
UPDATE admin_master SET related_features = '1<>1<>10' WHERE cd = 1;
INSERT INTO tb_search_cate (cd, rank, name, disp_flg, category, hyouji_flg, record_type) VALUES
    (10, 1, '施術記録', '1', 'category1', '1', '1'),
    (11, 2, '趣味', '1', 'category1', '1', '0');
INSERT INTO tb_search_form (cd, rank, flds_row, name, titles, data, form_type, disp_flg, category, tb_search_cate_cd, hissu, unit, style_class, flds_col, hyouji_flg) VALUES
    (101, 1, 0, 'メニュー', '', '', 'テキスト', '1', 'category1', 10, '', '', 'inputM', 0, '1'),
    (102, 2, 3, '部位', '頭<>肩<>腰', '頭<>肩<>腰', 'セレクト', '1', 'category1', 10, '', '', 'selectBox', 0, '2'),
    (103, 3, 2, '満足度', '高<>低', '1<>2', 'ラジオボタン', '1', 'category1', 10, '', '', '', 0, '1'),
    (104, 4, 0, '非表示', '', '', 'テキスト', '1', 'category1', 10, '', '', '', 0, '0'),
    (111, 1, 0, '好きなもの', '', '', 'テキスト', '1', 'category1', 11, '', '', 'inputL', 0, '1');
INSERT INTO tb_customer_sub (cd, rank, tb_customer_cd, tb_search_cate_cd, tb_search_form_cd, text_data, form_type, disp_flg, form_title, category_inner_group, touroku_date, update_date) VALUES
    (501, 1, 1609897927001809158, 10, 101, 'カット', 'テキスト', '1', 'メニュー', '1', '1700000000', '1700000000'),
    (502, 2, 1609897927001809158, 10, 102, '肩', 'セレクト', '1', '部位', '1', '1700000000', '1700000000'),
    (503, 1, 1609897927001809158, 11, 111, '猫 & "犬"', 'テキスト', '1', '好きなもの', '1', '1700000000', '1700000000');

-- Visitas: con registro de puntos y 関連情報, otra sin, una borrada y una de otro socio
INSERT INTO tb_point_log (cd, tb_shop_cd, tb_staff_cd, tb_customer_cd, tb_terminal_cd, touroku_date, shori_mae_point, kaikei, shiharai_houhou, percentage, shouhi_point, jidou_point, sounyu_point, disp_flg, action_flg)
VALUES (9001, 1, 2, 1609897927001809158, -2, 1700000000, 400, 3000, '現金', 1.5, 0, 45, 0, '1', 'raiten');
INSERT INTO tb_raiten_rireki (cd, user_id, user_cd, shop_cd, raiten_time, stamp, raiten_hiduke, customer_age, customer_sex, customer_job, disp_flg, manual_flg, tb_point_log_cd, tb_search_cate_cd, tb_customer_sub_inner_group, tb_stamp_log_cd) VALUES
    (801, '1000001', 1609897927001809158, 1, '1700000000', 1, '2023/11/15', 33, '男性', '会社員', NULL, '1', 9001, 10, '1', NULL),
    (802, '1000001', 1609897927001809158, 2, '1699500000', 0, '2023/11/09', 33, '男性', '会社員', '1', NULL, NULL, NULL, NULL, NULL),
    (803, '1000001', 1609897927001809158, 1, '1699000000', 0, '2023/11/03', 33, '男性', '会社員', '9', NULL, NULL, NULL, NULL, NULL),
    (804, '1000002', 1610793770001489815, 1, '1699600000', 0, '2023/11/10', 38, '女性', '', '1', NULL, NULL, NULL, NULL, NULL);
UPDATE hatuban SET kazu = 900 WHERE koumoku = 'raiten_rireki';
