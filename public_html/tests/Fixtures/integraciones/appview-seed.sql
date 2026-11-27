-- Datos sintéticos para las fixtures de appview/* (S5), sobre legacy-base.sql + legacy-seed.sql.
-- Nada de esto es de producción.

-- Socio pendiente de vinculación (touroku_kubun = '1') para member_comp.php
INSERT INTO tb_customer (cd, id, tb_shop_cd, name, touroku_kubun, disp_flg, login_id, login_pass, touroku_date, update_date)
VALUES (1700000000001000004, '1000004', 1, '仮 登録', '1', '1', '1000004', 'pass4', '1700000600', '1700000600');

-- Plantilla del sitio móvil (hp/inc_m/kihon.php) para cupones y sellos: colores 'shop' y patrón 1 (+ -1)
UPDATE admin_master SET mobile_hp_template = 1, stamp_shikkou_sousa = '1' WHERE cd = 1;
INSERT INTO tb_hp_color (cd, contents, bg_color, link_color, alink_color, vlink_color, font_color)
VALUES (1, 'shop', 'FFFFFF', '0000FF', 'FF0000', '800080', '333333');
INSERT INTO tb_mobile_template (cd, pattern, use_case, color, background, pic_flg) VALUES
    (1, 1, 'h2', 'FFFFFF', 'FF6600', '0'),
    (2, 1, 'hr', 'CC0000', '', '0'),
    (3, -1, '帯', '', '', '0');

-- Cupones: uno de la tienda 1, uno de todas las tiendas (-1) y uno caducado (coupon.php lo borra)
INSERT INTO tb_mycoupon_master (cd, tb_shop_cd, title, comment, attention, disp_flg, hyouji_flg) VALUES
    (10, 1, 'カット10%OFF', 'ご来店時にこの画面をご提示ください。' || chr(10) || '他の割引と併用できません。', '1回のみ有効<><>要予約', '1', '1'),
    (11, -1, '全店舗ドリンク券', 'ドリンク1杯無料', '', '1', '1');
INSERT INTO tb_mycoupon (cd, tb_mycoupon_master_cd, tb_shop_cd, tb_customer_cd, time_limit, used_flg, touroku_date) VALUES
    (100, 10, 1, 1609897927001809158, '1893423600', '', '1700000000'),
    (101, 11, -1, 1609897927001809158, '1893423600', NULL, '1700000000'),
    (102, 10, 1, 1609897927001809158, '1000000000', '', '1700000000');
INSERT INTO tb_mycoupon_used (cd, tb_mycoupon_master_cd, tb_shop_cd, tb_customer_cd, time_limit, used_flg, touroku_date, used_date) VALUES
    (90, 10, 1, 1609897927001809158, '1893423600', '', '1700000000', '1700003600');
INSERT INTO tb_base_system_grandchild (cd, tb_base_system_cd, tb_base_system_child_cd, rank, system_flg, system_name)
VALUES (900, 1, 1, 1, '1', '印刷');

-- Sellos: máximo 10 en la tienda 1; patrón por defecto 2; el primer socio tiene 3, el segundo 12 (pasa del máximo)
INSERT INTO tb_stamp_config (cd, tb_shop_cd, limit_flg, bonus_flg, stamp_max, touroku_bonus) VALUES (1, 1, '0', '0', 10, 0);
INSERT INTO tb_stamp_pattern (cd, name, pic_default, disp_flg) VALUES (2, '標準', '1', '1');
UPDATE tb_customer SET stamp_count = 3, stamp_yukoukigen = 1893423599 WHERE cd = 1609897927001809158;
UPDATE tb_customer SET stamp_count = 12, tb_stamp_pattern_cd = 5 WHERE cd = 1610793770001489815;
