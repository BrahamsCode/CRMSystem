-- Datos de prueba para las fixtures de contrato de integraciones (S5).
-- Se cargan sobre el esquema del legacy + bd-legacy.sh (tb_shop cd=1, tb_point_log de ejemplo).
-- Los mismos datos sirven al legacy (para grabar las respuestas) y a Laravel (para compararlas).
-- Nada de esto es de producción.

-- Socios (touroku_kubun = '2'). Los cd coinciden con filas de ejemplo de tb_point_log del backup.
INSERT INTO tb_customer (cd, id, tb_shop_cd, name, name_kana, zip, pref, add, add_number, add_build,
    email1, kojin_birth, kojin_mobile, touroku_kubun, disp_flg, login_id, login_pass, kanri_id,
    touroku_date, update_date, point_yukoukigen)
VALUES
    (1609897927001809158, '1000001', 1, '山田 太郎', 'ヤマダ タロウ', '5300001', '大阪府', '大阪市北区梅田', '1-1-1', 'テストビル101',
     'taro@example.test', '1990/01/02', '09012345678', '2', '1', '1000001', 'pass1', 'K-001',
     '1700000000', '1700000100', 0),
    (1610793770001489815, '1000002', 1, '佐藤 花子', 'サトウ ハナコ', '1000001', '東京都', '千代田区千代田', '1-1', '',
     'hanako@example.test', '1985/12/31', '08098765432', '2', '1', '1000002', 'pass2', '',
     '1700000200', '1700000300', 0),
    -- Dado de baja: no debe salir en ninguna respuesta
    (1616386984001912810, '1000003', 1, '削除 済み', 'サクジョ ズミ', '', '', '', '', '',
     'deleted@example.test', '', '07000000000', '2', '9', '1000003', 'pass3', '',
     '1700000400', '1700000500', 0);

-- Saldo de puntos del primer socio
INSERT INTO tb_customer_point (cd, tb_customer_cd, service_point, point_yukoukigen, touroku_date, update_date)
VALUES (1, 1609897927001809158, 500, 1893423600, '1700000000', '1700000000');

-- Passcode de la tienda móvil de la tienda 1 (contents_master, como features_config.php)
INSERT INTO contents_master (category, field, int_data, text_data, update_date)
SELECT 'mobileshop', 'mobileshop_auth_passcode', 1, '4321', '1700000000'
WHERE NOT EXISTS (SELECT 1 FROM contents_master WHERE category = 'mobileshop' AND field = 'mobileshop_auth_passcode');

-- Producto canjeable por puntos en la tienda 1
INSERT INTO sales_items (cd, disp_flg, hyouji_flg, touroku_date, update_date, name, display_name, display_title,
    discription, is_exchangeable_of_point, number_of_points_exchanged, discription_detail, available_shop_cds)
VALUES (2, 1, 1, 1700000000, 1700000000, 'コーヒー券', 'コーヒー券', 'ドリンク', '<p>コーヒー 1 杯</p>', 1, 100, '<p>詳細</p>', '{1}');

UPDATE hatuban SET kazu = 2 WHERE koumoku = 'tb_customer_point';
