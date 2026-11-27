--
-- BD `admin` de la señalización web (`signage/` del legacy): ESQUEMA MÍNIMO SOLO PARA DESARROLLO Y PRUEBAS.
-- La verdad la dará el volcado de Antony: cuando llegue, este archivo se sustituye por él.
--
-- Reconstruido de las consultas del legacy (crm2-php8/public_html/signage):
--   inc/function.php (insertSQL con RETURNING cd, updateSQL, deleteSQL; "NOW()" como valor literal),
--   inc/files.php, index.php, list/*.php, view/*.php, mail/index.php y pdf/*.php.
-- Sin esquema explícito: se crea en el search_path de la conexión `signage`.
-- Tipos: `cd` serial; banderas y números como integer (el legacy compara con = 1 sin comillas);
-- fechas como timestamp (NOW(), to_char(...,'hh24:mm'), comparación con NOW()); el resto text.
--

-- 端末 (list/terminal.php, index.php, view/index.php, view/ajax.php)
CREATE TABLE terminal (
    cd serial PRIMARY KEY,
    name text,
    flg integer DEFAULT 1,
    packages_cd bigint,
    display_direction text,
    display_number integer,
    display_type text,
    insert_date timestamp,
    update_date timestamp
);

-- パッケージ (list/packages.php, list/cycle.php, view/index.php, view/packages.php)
CREATE TABLE packages (
    cd serial PRIMARY KEY,
    name text,
    flg integer DEFAULT 1,
    terminal_cd bigint,
    cycle integer,
    display_direction text,
    display_number integer,
    display_type text,
    insert_date timestamp,
    update_date timestamp
);

-- サイクル (list/packages.php, list/cycle.php, view/index.php, view/ajax.php)
-- img_path_ary / action_ary: serialize() de PHP
CREATE TABLE cycle (
    cd serial PRIMARY KEY,
    packages_cd bigint,
    flg integer DEFAULT 1,
    status integer,
    flame_cd integer,
    schedule_repeat_type integer,
    schedule_start_time timestamp,
    schedule_end_time timestamp,
    img_path_ary text,
    action_ary text,
    insert_date timestamp,
    update_date timestamp
);

-- 画像サイズ (list/contents_category.php, list/contents.php, list/cycle.php, view/index.php)
CREATE TABLE contents_category (
    cd serial PRIMARY KEY,
    name text,
    width integer,
    height integer,
    flg integer DEFAULT 1,
    insert_date timestamp,
    update_date timestamp
);

-- 画像 (list/contents.php, list/contents_detail.php, list/cycle.php, view/index.php)
CREATE TABLE contents (
    cd serial PRIMARY KEY,
    contents_category_cd bigint,
    name text,
    img_path text,
    flg integer DEFAULT 1,
    insert_date timestamp,
    update_date timestamp
);

-- 動画のサイズ (list/movie_contents.php, list/movie_contents_detail.php)
CREATE TABLE movie_contents_category (
    cd serial PRIMARY KEY,
    name text,
    width integer,
    height integer,
    flg integer DEFAULT 1,
    insert_date timestamp,
    update_date timestamp
);

-- 動画 (list/movie_contents.php, list/movie_contents_detail.php, list/movie_contents_category.php)
CREATE TABLE movie_contents (
    cd serial PRIMARY KEY,
    contents_category_cd bigint,
    name text,
    movie_path text,
    flg integer DEFAULT 1,
    insert_date timestamp,
    update_date timestamp
);

-- ファイル (list/signage.php: el nombre del archivo subido va en `color`; `end_color` solo en HTML comentado)
CREATE TABLE signage (
    cd serial PRIMARY KEY,
    color text,
    end_color text,
    flg integer DEFAULT 1,
    insert_date timestamp,
    update_date timestamp
);

-- PROVISIONAL (Antony, 2026-11-28, hasta el volcado): customer, shop y contact reconstruidas de las consultas del legacy.
-- 顧客 (mail/index.php, pdf/invoice.php, pdf/receipt.php: SELECT * … cd, name, master_option_cd {a,b})
CREATE TABLE customer (
    cd serial PRIMARY KEY,
    name text,
    master_option_cd text,
    flg integer DEFAULT 1,
    insert_date timestamp,
    update_date timestamp
);

-- 店舗 (PROVISIONAL; inc/files.php: login ?s=md5(email01 || '_' || pass) con flg = 1)
CREATE TABLE shop (
    cd serial PRIMARY KEY,
    name text,
    email01 text,
    pass text,
    flg integer DEFAULT 1,
    insert_date timestamp,
    update_date timestamp
);

-- お問い合わせ (PROVISIONAL; mail/index.php: insertSQL sin RETURNING)
CREATE TABLE contact (
    cd serial PRIMARY KEY,
    subject text,
    name text,
    message text,
    insert_date timestamp
);
