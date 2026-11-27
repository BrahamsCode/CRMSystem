-- Datos sintéticos para las fixtures de appview/* (S5), sobre legacy-base.sql + legacy-seed.sql.
-- Nada de esto es de producción.

-- Socio pendiente de vinculación (touroku_kubun = '1') para member_comp.php
INSERT INTO tb_customer (cd, id, tb_shop_cd, name, touroku_kubun, disp_flg, login_id, login_pass, touroku_date, update_date)
VALUES (1700000000001000004, '1000004', 1, '仮 登録', '1', '1', '1000004', 'pass4', '1700000600', '1700000600');
