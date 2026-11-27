--
-- PostgreSQL database dump
--

\restrict E8spK2ijzxAe8WNFUCdD3oeLWo4kBr60A2aptGvah6jubIbBEhdYx6VanOaKgmr

-- Dumped from database version 18.7 (Debian 18.7-1.pgdg13+5)
-- Dumped by pg_dump version 18.7 (Debian 18.7-1.pgdg13+5)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: admin_master; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.admin_master (
    cd bigint,
    admin_cd bigint,
    kaisyamei character varying(255),
    daihyousyamei character varying(255),
    kaisyamei_kana character varying(255),
    daihyousyamei_kana character varying(255),
    gyoushu character varying(255),
    toukibi character varying(255),
    pref character varying(255),
    zip character varying(255),
    add character varying(255),
    add_kana character varying(255),
    add_build character varying(255),
    add_number character varying(255),
    add_build_kana character varying(255),
    fax character varying(255),
    tel character varying(255),
    email character varying(255),
    tantou character varying(255),
    tantou_fax character varying(255),
    tantou_tel character varying(255),
    tantou_email character varying(255),
    id character varying(255),
    pass character varying(255),
    touroku_date character varying(255),
    update_date character varying(255),
    system_mail text,
    terminal_sound_flg character varying,
    sound_pattern integer,
    sound_select text,
    mobile_hp_template integer,
    mypage_template integer,
    mail_footer text,
    mypage_stamp_hyouji_flg text,
    shuki_limit text,
    yoyaku_time_interval text,
    mail_send_filter text,
    seireki_hissu_flg text,
    point_shikkou_kikan text,
    point_shikkou_time text,
    point_shikkou_sousa text,
    point_shikkou_alert text,
    point_shikkou_alert_time text,
    yoyaku_time_table_hyouji_jiku text,
    point_shikkou_mail_title text,
    point_shikkou_mail_naiyou text,
    point_alert_mail_title text,
    point_alert_mail_naiyou text,
    related_features text,
    stamp_shikkou_kikan text,
    stamp_shikkou_time text,
    stamp_shikkou_sousa text,
    stamp_shikkou_alert text,
    stamp_shikkou_alert_time text,
    stamp_shikkou_mail_title text,
    stamp_shikkou_mail_naiyou text,
    stamp_alert_mail_title text,
    stamp_alert_mail_naiyou text,
    terminal_logout_time text,
    kingaku_rank_shori_date text,
    kingaku_rank_shori_time text,
    raiten_rank_shori_date text,
    raiten_rank_shori_time text,
    kingaku_rank_shori_kikan text,
    raiten_rank_shori_kikan text,
    point_yukoukigen_flg text,
    customer_rank_disp_flg text,
    raiten_stamp_flg text,
    meyasu_jikan_flg text,
    stamp_update_type_flg text,
    id_raiten_type text,
    yoyaku_setsubi_flg text,
    free_yoyaku_flg text,
    pos_koubai_tb_search_form_cd bigint,
    mailmag_sended_log text,
    base_system_version_flg text,
    application_use_flg integer,
    push_api_host text,
    shop_category_flg integer,
    coming_use_beacon text,
    beacon_use_flg integer,
    ssl_flg integer,
    smtp_domain text,
    smtp_port text,
    smtp_login_id text,
    smtp_login_pass text,
    domain text,
    paid_member_settings text,
    smatopi_flg smallint,
    domain_smatopi_id text,
    seller_id smallint
);


--
-- Name: COLUMN admin_master.push_api_host; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.admin_master.push_api_host IS 'スマホアプリへプッシュする時に利用するapiサーバ
「空白」・「0」: 開発apiサーバ (http://b-develop.g-api.net)
「1」: 本番apiサーバ (http://b.g-api.net)';


--
-- Name: COLUMN admin_master.seller_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.admin_master.seller_id IS '販売会社。NULL 又は 0 = エクストリンク、1 = クラフトフィックス';


--
-- Name: backup; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.backup (
    cd integer,
    name text,
    memo text,
    backup_dir text,
    backup_date timestamp without time zone,
    flg integer
);


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: contents_master; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contents_master (
    cd bigint,
    category text,
    field text,
    text_data text,
    int_data bigint,
    float_data double precision,
    disp_flg character varying,
    hyouji_flg character varying,
    touroku_date bigint,
    update_date bigint
);


--
-- Name: doctrine_migration_versions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.doctrine_migration_versions (
    version character varying(14) NOT NULL,
    executed_at timestamp(0) without time zone NOT NULL
);


--
-- Name: COLUMN doctrine_migration_versions.executed_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.doctrine_migration_versions.executed_at IS '(DC2Type:datetime_immutable)';


--
-- Name: doctrine_migration_versions_pkey; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.doctrine_migration_versions_pkey (
    version character varying
);


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: form_item_flg; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.form_item_flg (
    cd bigint,
    table_name text,
    item_flg character varying(255)
);


--
-- Name: gps_history; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.gps_history (
    cd bigint,
    lat double precision,
    lng double precision,
    touroku_date bigint,
    login_id character varying(50)
);


--
-- Name: haitatsu_sche; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.haitatsu_sche (
    "年度" character varying(50),
    "月度" character varying(50),
    day1 character varying(50),
    day2 character varying(50),
    day3 character varying(50),
    day4 character varying(50),
    day5 character varying(50),
    day6 character varying(50),
    day7 character varying(50),
    day8 character varying(50),
    day9 character varying(50),
    day10 character varying(50),
    day11 character varying(50),
    day12 character varying(50),
    day13 character varying(50),
    day14 character varying(50),
    day15 character varying(50),
    day16 character varying(50),
    day17 character varying(50),
    day18 character varying(50),
    day19 character varying(50),
    day20 character varying(50),
    day21 character varying(50),
    day22 character varying(50),
    day23 character varying(50),
    day24 character varying(50),
    day25 character varying(50),
    day26 character varying(50),
    day27 character varying(50),
    day28 character varying(50),
    day29 character varying(50),
    day30 character varying(50),
    day31 character varying(50),
    tb_shop_cd bigint
);


--
-- Name: hatuban; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hatuban (
    koumoku character varying(255),
    kazu integer
);


--
-- Name: industry_master; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.industry_master (
    cd bigint,
    "業種" character varying(50),
    "業種アル" character varying(50),
    "業種フォルダ名" character varying(50),
    "カラー" character varying(50),
    "ランク" integer,
    "表示有無" character varying(50)
);


--
-- Name: infomation; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.infomation (
    cd bigint,
    title text,
    comment text,
    belongpost text,
    employmentsystem text,
    officialposition text,
    update text
);


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: kintai_tb_config; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.kintai_tb_config (
    cd bigint,
    tb_shop_cd bigint,
    start_time text,
    end_time text,
    password text,
    pic_name text,
    kijun_time text
);


--
-- Name: kintai_tb_hiduke; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.kintai_tb_hiduke (
    cd bigint,
    hiduke character varying,
    kintai_flg character varying,
    felica_no text,
    tb_staff_cd bigint,
    disp_flg character varying,
    hyouji_flg character varying,
    bikou text
);


--
-- Name: kintai_tb_kintai; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.kintai_tb_kintai (
    cd bigint,
    kintai_flg character varying,
    kintai_time bigint,
    felica_no text,
    tb_staff_cd bigint,
    disp_flg character varying,
    hyouji_flg character varying,
    kintai_tb_hiduke_cd bigint
);


--
-- Name: log; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.log (
    cd bigint,
    record_date bigint,
    type1 text,
    type2 text,
    type3 integer,
    log_message text,
    log_data text,
    referer text,
    script_name text,
    execution_row integer,
    variable text
);


--
-- Name: mailmag_master; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mailmag_master (
    cd bigint,
    "店id" character varying(50),
    "投稿日時" character varying(100),
    "投稿日" character varying(50),
    "投稿時間" character varying(50),
    "タイトル" text,
    "pc内容" text,
    "docomo内容" text,
    "au内容" text,
    "vodafone内容" text,
    "その他内容" text,
    "配信日時" character varying(100),
    "配信日" character varying(50),
    "配信時間" character varying(50),
    "配信キャリア" character varying(50),
    "pc配信数" integer,
    "docomo配信数" integer,
    "au配信数" integer,
    "vodafone配信数" integer,
    "その他配信数" integer,
    "配信総数" integer,
    "登録者" text,
    "備考" text,
    "受付メールアドレス" text,
    "配信状況" character varying(50),
    "画像有無" character varying(50),
    "画像名大" character varying(254),
    "画像名小" character varying(254),
    "画像タイトル" text,
    "画像拡張子" character varying(50),
    "画像タイプ" character varying(50),
    "画像サイズ大" character varying(254),
    "画像サイズ小" character varying(254),
    "画像表示有無" character varying(50),
    "振分配信" text,
    "振分条件" text,
    type_flg character varying,
    type_data_cd bigint,
    send_parameter text,
    search_display text,
    disp_flg text,
    pos_search text,
    pos_tb_shop_cd integer,
    send_count integer,
    last_send_date integer
);


--
-- Name: mailmag_sended_log; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mailmag_sended_log (
    mailmag_master_cd bigint,
    tb_customer_cd bigint,
    email text
);


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: pit_coupon; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pit_coupon (
    coupon_cd bigint,
    flg character varying(255),
    shop_cd bigint,
    flg2 character varying(50)
);


--
-- Name: point_shikkou_alert; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.point_shikkou_alert (
    cd bigint,
    tb_shop_cd bigint,
    flg text,
    title text,
    message text,
    alert_day text,
    alert_time text,
    touroku_date integer,
    sort integer
);


--
-- Name: push_follow_master; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.push_follow_master (
    cd bigint,
    tb_shop_cd bigint,
    title text,
    message text,
    wherestr text,
    search_display text,
    disp_flg text,
    post_point integer,
    point_expiration_days integer,
    type_flg text,
    send_time text,
    send_parameter text,
    next_send_date integer,
    touroku_date integer,
    update_date integer,
    pos_search text,
    pos_tb_shop_cd integer,
    pic_link text
);


--
-- Name: push_master; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.push_master (
    cd bigint,
    tb_shop_cd bigint,
    title text,
    message text,
    img_flg text,
    wherestr text,
    search_display text,
    disp_flg text,
    touroku_date integer,
    update_date integer,
    pos_search text,
    pos_tb_shop_cd integer,
    version integer,
    post_type text,
    point_expiration_date integer,
    post_point integer,
    extra_data text,
    type_flg text,
    push_follow_master_cd bigint,
    pic_link text
);


--
-- Name: COLUMN push_master.wherestr; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.push_master.wherestr IS '検索条件';


--
-- Name: push_notifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.push_notifications (
    cd bigint,
    type text,
    type_master_cd bigint,
    tb_customer_cd bigint,
    registration_id text,
    touroku_date integer,
    open_cnt integer,
    open_date integer,
    extra_data text
);


--
-- Name: sales_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sales_items (
    cd integer NOT NULL,
    disp_flg smallint,
    hyouji_flg smallint,
    touroku_date integer,
    update_date integer,
    name text,
    display_name text,
    display_title text,
    discription text,
    is_exchangeable_of_point smallint,
    number_of_points_exchanged integer,
    discription_detail text,
    available_shop_cds integer[]
);


--
-- Name: sales_items_cd_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sales_items_cd_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sales_items_cd_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sales_items_cd_seq OWNED BY public.sales_items.cd;


--
-- Name: send_schedule; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.send_schedule (
    cd bigint,
    mag_cd bigint,
    "配信時間" text,
    flg text,
    "店id" text
);


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: smatopi_message; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.smatopi_message (
    cd integer NOT NULL,
    title text,
    message text,
    request_data text,
    record_date timestamp without time zone,
    query text,
    shop_cd integer
);


--
-- Name: COLUMN smatopi_message.query; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.smatopi_message.query IS '顧客取得した時のクエリ';


--
-- Name: smatopi_message_cd_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.smatopi_message_cd_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: smatopi_message_cd_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.smatopi_message_cd_seq OWNED BY public.smatopi_message.cd;


--
-- Name: tb_access_mapping; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_access_mapping (
    cd bigint,
    subdomain text,
    tb_shop_cd bigint,
    hyouji_flg text,
    disp_flg text,
    touroku_date text,
    update_date text,
    ninshou_flg text
);


--
-- Name: tb_area; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_area (
    cd bigint,
    pid integer,
    name text,
    comment text,
    type text,
    row_lis integer,
    flg character varying,
    pic_flg character varying,
    pic_comment text,
    pic_extension character varying,
    date_data text,
    linkwhich character varying,
    linkto text,
    linktarget character varying,
    page character varying,
    pic_maisu integer,
    pic_scale integer,
    sizew text,
    sizeh text,
    pic_text text,
    oid integer,
    tb_shop_cd bigint,
    owner_cd bigint,
    owner_flg character varying,
    touroku_date text,
    update_date text,
    disp_flg character varying,
    kind_flg text,
    child_contents text,
    child_contents_all text,
    child_report text,
    child_report_all text,
    page_type character varying,
    tb_hp_domain_cd bigint
);


--
-- Name: tb_base_system; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_base_system (
    cd bigint,
    system_name text,
    system_flg character varying(50),
    narabi integer,
    sys_elements text,
    sys_attributes text,
    sys_keywords text,
    sys_values text,
    sys_types text,
    sys_attribute_to text
);


--
-- Name: tb_base_system_child; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_base_system_child (
    cd bigint,
    tb_base_system_cd bigint,
    system_name text,
    system_flg character varying,
    rank integer
);


--
-- Name: tb_base_system_grandchild; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_base_system_grandchild (
    cd bigint,
    tb_base_system_cd bigint,
    tb_base_system_child_cd bigint,
    rank integer,
    system_flg character varying,
    system_name text
);


--
-- Name: tb_beacon_history; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_beacon_history (
    cd bigint,
    beacon_cd bigint,
    tb_customer_id text,
    touroku_date text,
    tb_shop_cd bigint
);


--
-- Name: tb_belongpost; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_belongpost (
    cd bigint,
    belongpost text
);


--
-- Name: tb_blue_system; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_blue_system (
    cd bigint,
    distinguish_cd bigint,
    admin_tb_category1_cd bigint,
    master_localhost text,
    master_kanri_domain text,
    name text,
    comment text,
    disp_flg character varying
);


--
-- Name: tb_bookmark; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_bookmark (
    cd bigint,
    tb_base_system_cd bigint,
    tb_base_system_child_cd bigint,
    url text,
    bookmark_name text,
    title text,
    naiyou text,
    bookmark_flg text,
    disp_flg text,
    rank integer,
    user_rank integer,
    tb_kengen_contents_cd bigint,
    tb_kengen_contents_node integer
);


--
-- Name: tb_c3_anketo2_cate; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_c3_anketo2_cate (
    cd bigint,
    rank integer,
    comment text,
    name text,
    category text,
    disp_flg character varying,
    serach_flg text,
    disp_column character varying,
    hyouji_flg character varying,
    record_type character varying,
    start_date integer,
    end_date integer,
    update_date character varying,
    touroku_date character varying,
    hakkou_date character varying,
    attached_cd bigint
);


--
-- Name: tb_c3_anketo2_form; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_c3_anketo2_form (
    cd bigint,
    rank integer,
    flds_row integer,
    name text,
    titles text,
    data text,
    comment text,
    form_type text,
    disp_flg character varying,
    category text,
    tb_c3_anketo_cate_cd bigint,
    serach_flg text,
    hissu text,
    unit text,
    style_class text,
    flds_col integer,
    hyouji_flg character varying,
    size_w text,
    size_h text
);


--
-- Name: tb_c3_anketo2_sub; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_c3_anketo2_sub (
    cd bigint,
    rank integer,
    tb_customer_cd bigint,
    tb_c3_anketo_cate_cd bigint,
    tb_c3_anketo_form_cd bigint,
    float_data double precision,
    text_data text,
    array_data text,
    form_type text,
    disp_flg character varying,
    form_title text,
    category_inner_group character varying,
    touroku_date character varying,
    update_date character varying,
    pic_flg character varying
);


--
-- Name: tb_c3_anketo_cate; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_c3_anketo_cate (
    cd bigint,
    rank integer,
    comment text,
    name text,
    category text,
    disp_flg character varying,
    serach_flg text,
    disp_column character varying,
    hyouji_flg character varying,
    record_type character varying,
    start_date integer,
    end_date integer,
    update_date character varying,
    touroku_date character varying,
    hakkou_date character varying,
    attached_cd bigint
);


--
-- Name: tb_c3_anketo_form; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_c3_anketo_form (
    cd bigint,
    rank integer,
    flds_row integer,
    name text,
    titles text,
    data text,
    comment text,
    form_type text,
    disp_flg character varying,
    category text,
    tb_c3_anketo_cate_cd bigint,
    serach_flg text,
    hissu text,
    unit text,
    style_class text,
    flds_col integer,
    hyouji_flg character varying,
    size_w text,
    size_h text
);


--
-- Name: tb_c3_anketo_sub; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_c3_anketo_sub (
    cd bigint,
    rank integer,
    tb_customer_cd bigint,
    tb_c3_anketo_cate_cd bigint,
    tb_c3_anketo_form_cd bigint,
    float_data double precision,
    text_data text,
    array_data text,
    form_type text,
    disp_flg character varying,
    form_title text,
    category_inner_group character varying,
    touroku_date character varying,
    update_date character varying,
    pic_flg character varying
);


--
-- Name: tb_c4_contents; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_c4_contents (
    cd bigint,
    tb_c4_sitemap_cd bigint,
    name text,
    contents_id text,
    price integer,
    comment text,
    narabi integer,
    flg character varying(50),
    pic_flg character varying(100),
    pic_comment text,
    checkbox_title text,
    checkbox_row text,
    checkbox_send text,
    checkbox_data text,
    earchcheckbox_row text,
    text_row text,
    text_title text,
    text_data text,
    pic_name text,
    tb_shop_cd bigint,
    owner_cd bigint,
    owner_flg character varying(50),
    disp_flg character varying(50),
    office_cd bigint,
    kind_flg character varying(20),
    touroku_date text,
    update_date text,
    type_pic_count integer,
    type_pic_size text,
    type_pic_text text,
    text_cols integer,
    tb_hp_domain_cd bigint,
    type_pic_link_flg text
);


--
-- Name: tb_c4_report; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_c4_report (
    cd bigint,
    tb_c4_contents_cd bigint,
    name text,
    item_id text,
    price integer,
    comment text,
    narabi integer,
    flg character varying(50),
    pic_flg character varying(100),
    pic_comment text,
    checkbox_title text,
    checkbox_row text,
    checkbox_send text,
    checkbox_data text,
    earchcheckbox_row text,
    text_row text,
    text_title text,
    text_data text,
    pic_name text,
    tb_shop_cd bigint,
    owner_cd bigint,
    owner_flg character varying(50),
    disp_flg character varying(50),
    office_cd bigint,
    kind_flg character varying(20),
    touroku_date text,
    update_date text,
    contact_hissu character varying(50),
    contact_type character varying(50),
    gaiyou_map text,
    gaiyou_map2 text,
    pic_row integer,
    tb_c4_content_kind_flg character varying(50),
    tb_hp_domain_cd bigint,
    pdf_flg character varying,
    pic_link text
);


--
-- Name: tb_c4_sitemap; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_c4_sitemap (
    cd bigint,
    pid integer,
    name text,
    comment text,
    type text,
    row_lis integer,
    flg character varying(50),
    pic_flg character varying(100),
    pic_comment text,
    pic_extension character varying(100),
    date_data text,
    linkwhich character varying(10),
    linkto text,
    linktarget character varying(10),
    page character varying(20),
    pic_maisu integer,
    pic_scale integer,
    sizew text,
    sizeh text,
    pic_text text,
    oid integer,
    tb_shop_cd bigint,
    owner_cd bigint,
    owner_flg character varying(50),
    touroku_date text,
    update_date text,
    disp_flg character varying(20),
    kind_flg text,
    child_contents text,
    child_contents_all text,
    child_report text,
    child_report_all text,
    page_type character varying(50),
    tb_hp_domain_cd bigint
);


--
-- Name: tb_c7_movie_program; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_c7_movie_program (
    cd bigint,
    tb_shop_cd bigint,
    rank integer,
    terminal_id text,
    disp_flg text,
    terminal_type text,
    touroku_date text,
    update_date text,
    comment text,
    name text,
    tb_screensaver_cds text,
    tb_master_cd bigint,
    act_flg text,
    kaiin_flg text,
    tb_mycoupon_master_cds text,
    disp_conf text,
    tb_dispaly_systems_cds text,
    feli_act_flg text,
    mypage_act_flg text,
    feli_tb_mycoupon_master_cds text,
    mypage_tb_mycoupon_master_cds text,
    system_tb_mycoupon_master_cds text,
    telop_comment text,
    vertical_setting text
);


--
-- Name: tb_card; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_card (
    cd bigint,
    card_no text,
    felica_no text,
    staff_cd text,
    update text,
    card_flg text
);


--
-- Name: tb_change_model; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_change_model (
    cd bigint,
    next_model text,
    resevation_type text,
    tb_customer_cd text,
    rank integer,
    disp_flg text,
    touroku_date text,
    update_date text
);


--
-- Name: tb_coupon; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_coupon (
    cd bigint NOT NULL,
    admin_cd bigint,
    title text,
    title_color character varying(255),
    title_pos character varying(255),
    title_size character varying(255),
    naiyou text,
    naiyou_color character varying(255),
    naiyou_pos character varying(255),
    naiyou_size character varying(255),
    bangou character varying(255),
    bangou_color character varying(255),
    bangou_pos character varying(255),
    bangou_size character varying(255),
    kigen character varying(255),
    kigen_color character varying(255),
    kigen_pos character varying(255),
    kigen_size character varying(255),
    flg character varying(255),
    pic_flg character varying(255),
    uptime character varying(255),
    narabi integer,
    tb_haikei_cd bigint,
    shop_cd bigint,
    pit_flg character varying(50)
);


--
-- Name: tb_coupon_master; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_coupon_master (
    cd bigint,
    tb_shop_cd bigint,
    title text,
    comment text,
    attention text,
    type_flg character varying,
    coupon_kubun character varying,
    limit_flg character varying,
    time_limit character varying,
    touroku_date character varying,
    update_date character varying,
    disp_flg character varying,
    other_use_flg character varying
);


--
-- Name: tb_customer; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_customer (
    cd bigint,
    id character varying(255),
    touroku character varying(255),
    tb_shop_cd bigint,
    agency character varying(255),
    type character varying(255),
    name text,
    name_kana text,
    pref character varying(255),
    zip character varying(255),
    add character varying(255),
    add_kana character varying(255),
    add_build character varying(255),
    add_number character varying(255),
    add_build_kana character varying(255),
    email1 character varying(255),
    email2 character varying(255),
    fax character varying(255),
    tel character varying(255),
    houjin_setsuritsu character varying(255),
    daihyousha_name character varying(255),
    houjin_shihon character varying(255),
    daihyousha_birth character varying(255),
    daihyousha_name_kana character varying(255),
    daihyousha_sex character varying(255),
    houjin_busho character varying(255),
    houjin_gyoushu character varying(255),
    tantou_name character varying(255),
    tantou_name_kana character varying(255),
    tantou_fax character varying(255),
    tantou_tel character varying(255),
    kojin_birth character varying(255),
    tantou_mail character varying(255),
    kojin_bloodtype character varying(255),
    kojin_sex character varying(255),
    kojin_mail character varying(255),
    kojin_mobile character varying(255),
    kinmusaki_name character varying(255),
    kinmusaki_gyoushu character varying(255),
    kinmusaki_name_kana character varying(255),
    kinmusaki_fax character varying(255),
    kinmusaki_tel character varying(255),
    mailmaga_email_type character varying(255),
    mailmaga_flg character varying(255),
    hobby character varying(255),
    married character varying(255),
    haishin_error character varying(255),
    touroku_kubun character varying(255),
    felica_no character varying(255),
    service_point character varying(255),
    bikou text,
    touroku_date character varying(255),
    update_date character varying(255),
    login_id character varying(50),
    login_pass character varying(50),
    login_rank character varying(50),
    touroku_flg character varying(20),
    error_send_cnt integer,
    disp_flg character varying(50),
    mobile_id text,
    easy_login character varying(50),
    mobile_kishu text,
    tb_stamp_pattern_cd bigint,
    stamp_count integer,
    shopping_point integer,
    kojin_birth_date integer,
    job text,
    tb_customer_level_cd bigint,
    tb_customer_group_cd bigint,
    last_login_time character varying,
    terminal_no character varying,
    tb_staff_cd bigint,
    last_stamp_time integer,
    kanri_id text,
    shuki_heikin integer,
    last_raiten_date text,
    point_yukoukigen bigint,
    stamp_yukoukigen bigint,
    kingaku_rank_now integer,
    kingaku_rank_pre integer,
    raiten_rank_now integer,
    raiten_rank_pre integer,
    synchronize_flg text,
    tb_raiten_douki_master_cd bigint,
    local_sync_flg text,
    shoukai_tb_customer_cd bigint,
    stop_yoyaku_reminder_mail_flg text,
    local_dl_sync_flg text,
    tel2 text,
    sairai_non_flg text,
    registration_id text,
    android_uuid text,
    apple_uuid text,
    push_device_id text,
    push_device_type text,
    mycolle_touroku_date text,
    imei text,
    paid_member_flg smallint,
    paid_member_flg_confirmation_time integer,
    paid_member_service_start_date integer,
    line_id text,
    line_blocked_flg smallint
);


--
-- Name: tb_customer_card; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_customer_card (
    cd bigint,
    tb_shop_cd bigint,
    tb_terminal_cd bigint,
    tb_customer_cd bigint,
    tb_staff_cd bigint,
    point integer,
    lost integer,
    name text,
    comment text,
    tid character varying,
    cid character varying,
    typ character varying,
    typ_exp character varying,
    sts character varying,
    type text,
    flg character varying,
    limit_date text,
    touroku_date text,
    update_date text,
    terminal_no text
);


--
-- Name: tb_customer_card_pkey; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_customer_card_pkey (
    cd integer
);


--
-- Name: tb_customer_group; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_customer_group (
    cd bigint,
    name text,
    rank integer,
    default_flg character varying,
    disp_flg character varying,
    touroku_date character varying,
    update_date character varying,
    synchronize_flg text,
    shiyou_point_jyougen integer,
    point_percentage_genkin real,
    point_percentage_card real,
    keisan_flg text
);


--
-- Name: tb_customer_level; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_customer_level (
    cd bigint,
    name text,
    rank integer,
    disp_flg character varying,
    touroku_date character varying,
    update_date character varying
);


--
-- Name: tb_customer_point; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_customer_point (
    cd bigint,
    tb_customer_cd bigint,
    service_point bigint,
    point_yukoukigen integer,
    touroku_date text,
    update_date text
);


--
-- Name: tb_customer_sub; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_customer_sub (
    cd bigint,
    rank integer,
    tb_customer_cd bigint,
    tb_search_cate_cd bigint,
    tb_search_form_cd bigint,
    float_data double precision,
    text_data text,
    array_data text,
    form_type text,
    disp_flg character varying,
    form_title text,
    category_inner_group character varying,
    touroku_date character varying,
    update_date character varying,
    pic_flg character varying,
    int_data integer,
    synchronize_flg text,
    local_sync_flg text
);


--
-- Name: tb_digital_catalog; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_digital_catalog (
    cd bigint,
    pic_flg text,
    comment text,
    hyouji_date text,
    disp_flg text,
    hyouji_flg text,
    touroku_date text,
    update_date text,
    star text
);


--
-- Name: tb_digital_catalog_tag; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_digital_catalog_tag (
    cd bigint,
    name text,
    rank integer
);


--
-- Name: tb_digital_catalog_tag_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_digital_catalog_tag_items (
    tb_digital_catalog_cd bigint,
    tb_digital_catalog_tag_cd bigint
);


--
-- Name: tb_digital_signage; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_digital_signage (
    cd bigint,
    title text,
    play_time integer,
    rank integer,
    update character varying(255),
    disp_flg character varying(10)
);


--
-- Name: tb_dispaly_systems; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_dispaly_systems (
    cd bigint,
    tb_master_cd bigint,
    tb_shop_cd bigint,
    rank integer,
    name text,
    system_type text,
    system_flg text,
    disp_flg character varying,
    touroku_date text,
    non_coupon_flg text
);


--
-- Name: tb_display; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_display (
    cd bigint,
    tb_shop_cd bigint,
    terminal_id text,
    terminal_type text,
    disp_flg text,
    touroku_date text,
    update_date text,
    comment text,
    name text,
    rank integer,
    disp_conf text,
    tb_dispaly_systems_cds text,
    tb_c7_movie_program_cds text,
    tb_master_cd bigint,
    tb_mycoupon_master_cds text,
    act_flg text,
    kaiin_flg text
);


--
-- Name: tb_employmentsystem; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_employmentsystem (
    cd bigint,
    employmentsystem text
);


--
-- Name: tb_felica_log; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_felica_log (
    action_flg integer,
    action_sub_flg integer,
    felica_no text,
    tb_customer_cd bigint,
    url text,
    terminal_no text,
    touroku_date text
);


--
-- Name: tb_felica_mode; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_felica_mode (
    cd bigint,
    mode text,
    update text,
    nyushitsu_komento text,
    taishitsu_komento text,
    interval_time text
);


--
-- Name: tb_flg_category; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_flg_category (
    cd bigint,
    rank integer,
    pid integer,
    touroku_date integer,
    update_date integer,
    name text,
    hyouji_flg character varying,
    disp_flg character varying,
    tb_shop_cd bigint,
    comment text,
    defcd integer
);


--
-- Name: tb_flg_category_joint; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_flg_category_joint (
    cd bigint,
    tb_flg_category_cd bigint,
    child_tb_name text,
    child_cd bigint,
    tb_flg_category_data text
);


--
-- Name: tb_free_yoyaku_rank; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_free_yoyaku_rank (
    cd bigint,
    tb_shop_cd bigint,
    tb_staff_cd bigint,
    rank integer,
    disp_flg character varying,
    hyouji_flg character varying,
    touroku_date bigint,
    update_date bigint
);


--
-- Name: tb_haikei; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_haikei (
    cd bigint NOT NULL,
    admin_cd bigint,
    title text,
    flg character varying(255),
    pic_flg character varying(255),
    uptime character varying(255),
    narabi integer
);


--
-- Name: tb_hp_color; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_hp_color (
    cd bigint,
    contents text,
    bg_color text,
    link_color text,
    alink_color text,
    vlink_color text,
    font_color text
);


--
-- Name: tb_hp_contents; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_hp_contents (
    cd bigint,
    contents_cate text,
    contents_name text,
    contents_link text,
    emoji_cd text,
    disp_flg text
);


--
-- Name: tb_hp_data; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_hp_data (
    cd bigint,
    tb_shop_cd bigint,
    disp_date text,
    title text,
    naiyou text,
    img_flg text,
    update text,
    disp_flg text,
    header_pic_flg character varying(50),
    footer_pic_flg character varying(50),
    header_comment text,
    header_h1 text,
    header_pic_size character varying(50),
    footer_pic_size character varying(50),
    footer_comment text,
    footer_link_text text,
    footer_copy_right text,
    background_pic_flg text,
    tb_hp_domain_cd bigint
);


--
-- Name: tb_hp_domain; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_hp_domain (
    cd bigint,
    title text,
    domain_name text,
    comment text,
    disp_flg character varying
);


--
-- Name: tb_hp_freepage; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_hp_freepage (
    cd bigint,
    tb_shop_cd bigint,
    title text,
    naiyou text,
    img_flg text,
    update text,
    disp_flg text,
    rank integer,
    link text,
    hyouji_flg text
);


--
-- Name: tb_hp_header; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_hp_header (
    cd bigint,
    tb_shop_cd bigint,
    disp_date text,
    title text,
    naiyou text,
    img_flg text,
    update text,
    disp_flg text,
    common_flg text
);


--
-- Name: tb_hp_news; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_hp_news (
    cd bigint,
    tb_shop_cd bigint,
    disp_date text,
    title text,
    naiyou text,
    img_flg text,
    update text,
    disp_flg text,
    link text,
    hyouji_flg text,
    link_type text
);


--
-- Name: tb_hp_recommend; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_hp_recommend (
    cd bigint,
    tb_shop_cd bigint,
    disp_date text,
    title text,
    naiyou text,
    img_flg text,
    update text,
    disp_flg text,
    link text,
    hyouji_flg text,
    link_type text,
    img_type text,
    pic_link text
);


--
-- Name: tb_hp_topics; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_hp_topics (
    cd bigint,
    tb_shop_cd bigint,
    disp_date text,
    title text,
    naiyou text,
    img_flg text,
    update text,
    disp_flg text,
    link text,
    hyouji_flg text,
    link_type text
);


--
-- Name: tb_hp_words; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_hp_words (
    cd bigint,
    tb_shop_cd bigint,
    c6_user_top text
);


--
-- Name: tb_information; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_information (
    cd bigint,
    shop_cd bigint,
    title text,
    comment text,
    belongpost text,
    employmentsystem text,
    officialposition text,
    update text,
    staff_name text,
    disp_flg character varying(50)
);


--
-- Name: tb_information_place; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_information_place (
    cd bigint,
    tb_shop_cd bigint,
    tb_staff_cd bigint,
    tb_customer_cd bigint,
    tb_place_cd bigint,
    start_time integer,
    end_time integer,
    extension_time integer,
    people integer,
    people_num text,
    comment text,
    disp_flg text,
    status_flg text,
    hyouji_flg text,
    touroku_date text,
    update_date text,
    disp_name text,
    available_time integer,
    last_flg text,
    tb_c2_sum_master_cd bigint,
    tb_c2_sum_master_cd_2 integer
);


--
-- Name: tb_job; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_job (
    cd bigint,
    name text,
    comment text,
    rank integer,
    disp_flg character varying
);


--
-- Name: tb_kanji; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_kanji (
    cd bigint,
    kanji character varying,
    yomi character varying,
    yomi_alp character varying,
    kakusu integer,
    english text,
    disp_flg character varying
);


--
-- Name: tb_karute; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_karute (
    cd bigint,
    tb_customer_cd bigint,
    tb_staff_cd bigint,
    tb_shop_cd bigint,
    tb_sejutsu_cate_cd bigint,
    comment text,
    pic_flg text,
    uketsuke_date integer,
    touroku_date integer,
    update_date integer,
    disp_flg text
);


--
-- Name: tb_karute_menu; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_karute_menu (
    cd bigint,
    tb_shop_cd bigint,
    name text,
    kingaku integer,
    type text,
    rank integer,
    disp_flg character varying,
    hyouji_flg character varying,
    touroku_date character varying,
    update_date character varying
);


--
-- Name: tb_karute_waribiki; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_karute_waribiki (
    cd bigint,
    tb_shop_cd bigint,
    name text,
    value integer,
    type text,
    buai_flg text,
    rank integer,
    disp_flg character varying,
    hyouji_flg character varying,
    touroku_date character varying,
    update_date character varying
);


--
-- Name: tb_kengen; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_kengen (
    cd bigint,
    tb_staff_group_cd bigint,
    tb_kengen_contents_cd bigint,
    etsuran_flg text,
    shinki_flg text,
    shusei_flg text,
    sakujo_flg text,
    touroku_date text,
    update_date text
);


--
-- Name: tb_kengen_contents; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_kengen_contents (
    cd bigint,
    name text,
    tb_base_system_cd bigint,
    comment text,
    hyouji_flg text,
    touroku_date text,
    update_date text
);


--
-- Name: tb_kingaku_rank_master; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_kingaku_rank_master (
    cd bigint,
    name text,
    jyouken1 integer,
    jyouken2 integer,
    disp_flg text,
    hyouji_flg text,
    touroku_date text,
    update_date text,
    rank integer
);


--
-- Name: tb_koumoku; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_koumoku (
    cd bigint,
    system_cate character varying(50),
    tb_name character varying(50),
    tb_koumoku text,
    tb_koumoku_name text,
    koumoku_disp character varying(50),
    koumoku_check character varying(50),
    mail_mag_select character varying(50),
    koumoku_select character varying(50),
    rank integer
);


--
-- Name: tb_kujibiki_count; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_kujibiki_count (
    cd bigint,
    tb_customer_cd bigint,
    int_data bigint,
    disp_flg character varying,
    hyouji_flg character varying,
    touroku_date bigint,
    update_date bigint
);


--
-- Name: tb_kujibiki_log; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_kujibiki_log (
    cd bigint,
    tb_customer_cd bigint,
    kujibiki_name text,
    tb_mycoupon_master_cd bigint,
    mycoupon_flg character varying,
    time_limit bigint,
    disp_flg character varying,
    hyouji_flg character varying,
    touroku_date bigint,
    update_date bigint
);


--
-- Name: tb_login_rireki; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_login_rireki (
    cd bigint,
    staff_cd bigint,
    staff_name text,
    update text,
    komento text
);


--
-- Name: tb_mail_template; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_mail_template (
    cd bigint,
    tb_mail_template_category_cd bigint,
    title text,
    naiyou text,
    hyouji_flg text,
    disp_flg text,
    touroku_date text,
    update_date text,
    mail_type text,
    rank integer
);


--
-- Name: tb_mail_template_category; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_mail_template_category (
    cd bigint,
    hyouji_flg text,
    disp_flg text,
    touroku_date text,
    update_date text,
    name text,
    rank integer
);


--
-- Name: tb_mailmag_analyze; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_mailmag_analyze (
    cd bigint,
    mailmag_master_cd bigint,
    tb_customer_cd bigint,
    touroku_date integer,
    customer_age integer,
    customer_sex text,
    customer_job text,
    mailmag_type text
);


--
-- Name: tb_manage_words; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_manage_words (
    cd bigint,
    content_name text,
    naiyou text,
    hyouji_flg character varying,
    touroku_date text,
    update_date text
);


--
-- Name: tb_mobile_navi; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_mobile_navi (
    cd bigint,
    tb_master_cd bigint,
    rank integer,
    name text,
    link text,
    icon_no text,
    hyouji_flg character varying,
    disp_flg character varying,
    use_case_flg character varying,
    display_group text
);


--
-- Name: tb_mobile_template; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_mobile_template (
    cd bigint,
    pattern integer,
    use_case text,
    color text,
    background text,
    pic_flg text
);


--
-- Name: tb_my_freepage; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_my_freepage (
    cd bigint,
    tb_my_freepage_master_cd bigint,
    title text,
    naiyou text,
    link text,
    rank integer,
    pic_flg character varying,
    disp_flg character varying,
    hyouji_flg character varying,
    touroku_date character varying,
    update_date character varying
);


--
-- Name: tb_my_freepage_master; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_my_freepage_master (
    cd bigint,
    tb_shop_cd bigint,
    page_title text,
    rank integer,
    disp_flg character varying,
    hyouji_flg character varying,
    touroku_date character varying,
    update_date character varying
);


--
-- Name: tb_mycoupon; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_mycoupon (
    cd bigint,
    tb_mycoupon_master_cd bigint,
    tb_shop_cd bigint,
    tb_customer_cd bigint,
    time_limit character varying,
    used_flg character varying,
    touroku_date character varying,
    used_date character varying,
    coupon_kubun character varying,
    print_flg character varying,
    kakunin_flg character varying
);


--
-- Name: tb_mycoupon_master; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_mycoupon_master (
    cd bigint,
    tb_shop_cd bigint,
    title text,
    comment text,
    attention text,
    type_flg character varying,
    coupon_kubun character varying,
    limit_flg character varying,
    time_limit character varying,
    touroku_date character varying,
    update_date character varying,
    disp_flg character varying,
    other_use_flg character varying,
    shouhi_point integer,
    hyouji_flg character varying,
    saitouroku_flg character varying
);


--
-- Name: tb_mycoupon_print; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_mycoupon_print (
    cd bigint,
    tb_shop_cd bigint,
    tb_customer_cd bigint,
    felica_no character varying,
    touroku_date character varying,
    tid character varying,
    typ character varying,
    tim character varying,
    sts character varying
);


--
-- Name: tb_mycoupon_used; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_mycoupon_used (
    cd bigint,
    tb_mycoupon_master_cd bigint,
    tb_shop_cd bigint,
    tb_customer_cd bigint,
    time_limit character varying,
    used_flg character varying,
    touroku_date character varying,
    used_date character varying,
    coupon_kubun character varying,
    print_flg character varying,
    kakunin_flg character varying
);


--
-- Name: tb_nextouch_display; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_nextouch_display (
    cd bigint,
    tb_shop_cd bigint,
    terminal_id text,
    terminal_type text,
    disp_flg text,
    touroku_date text,
    update_date text,
    comment text,
    name text,
    rank integer,
    disp_conf text,
    tb_dispaly_systems_cds text,
    tb_c7_movie_program_cds text,
    tb_master_cd bigint,
    tb_mycoupon_master_cds text,
    act_flg text,
    kaiin_flg text,
    wait_time text,
    touch_area text,
    sound_flg text
);


--
-- Name: tb_nextouch_movie_program; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_nextouch_movie_program (
    cd bigint,
    tb_shop_cd bigint,
    rank integer,
    terminal_id text,
    disp_flg text,
    terminal_type text,
    touroku_date text,
    update_date text,
    comment text,
    name text,
    tb_screensaver_cds text,
    tb_master_cd bigint,
    act_flg text,
    kaiin_flg text,
    tb_mycoupon_master_cds text,
    disp_conf text,
    tb_dispaly_systems_cds text,
    feli_act_flg text,
    mypage_act_flg text,
    feli_tb_mycoupon_master_cds text,
    mypage_tb_mycoupon_master_cds text,
    system_tb_mycoupon_master_cds text,
    telop_comment text,
    vertical_setting text,
    kirikae_time text,
    pic_name text,
    pic_flg text,
    pic_extension text,
    obi_flg text,
    tb_screensaver_active_time text
);


--
-- Name: tb_nextouch_navi; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_nextouch_navi (
    cd bigint,
    name text,
    hyouji_flg text
);


--
-- Name: tb_nextouch_screensaver; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_nextouch_screensaver (
    cd bigint,
    type_flg text,
    data text,
    select_flg text,
    touroku_date text,
    update_date text,
    comment text,
    pic_extension text,
    pic_flg text,
    title text,
    disp_flg text,
    filetype text,
    tb_shop_cd bigint,
    play_frame_sec text,
    tb_display_cd bigint,
    rank integer,
    categorize_value_0 text,
    tb_master_cd bigint
);


--
-- Name: tb_office; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_office (
    cd bigint,
    admin_cd bigint,
    kaishamei character varying(255),
    daihyoushamei character varying(255),
    kaishamei_kana character varying(255),
    daihyoushamei_kana character varying(255),
    gyoushu character varying(255),
    toukibi character varying(255),
    pref character varying(255),
    zip character varying(255),
    add character varying(255),
    add_kana character varying(255),
    add_build character varying(255),
    add_number character varying(255),
    add_build_kana character varying(255),
    fax character varying(255),
    tel character varying(255),
    email character varying(255),
    tantou character varying(255),
    tantou_fax character varying(255),
    tantou_tel character varying(255),
    tantou_email character varying(255),
    id character varying(255),
    pass character varying(255),
    touroku_date character varying(255),
    update_date character varying(255),
    kyuyo_shimebi text,
    kyuyo_marume text,
    shop_url text,
    start_work_time character varying(255),
    break_time character varying(255),
    hp_hyouji_name text,
    hp_hyouji_tel text
);


--
-- Name: tb_officialposition; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_officialposition (
    cd bigint,
    officialposition text
);


--
-- Name: tb_order_main; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_order_main (
    cd bigint,
    tb_shop_cd bigint,
    tb_customer_cd bigint,
    daibiki_price integer,
    order_sum integer,
    tax integer,
    souryou_sum integer,
    order_y integer,
    order_m integer,
    order_d integer,
    order_date text,
    haitatsu_date text,
    to_name text,
    to_zip text,
    to_pref text,
    to_address1 text,
    to_address2 text,
    to_address3 text,
    to_tel text,
    to_mail text,
    to_comment text,
    hassou_flg text,
    card_num text,
    card_limit_date text,
    card_num_sub text,
    card_name text,
    card_kind text,
    birth_date text,
    payment_flg text,
    approvalno text,
    kessaibi text,
    order_comp_flg text,
    ordernum text,
    forwarding_office text,
    how_to_pay text,
    memo text,
    shitei_day text,
    shitei_hour text,
    connect_domain text,
    send_mail_flg character varying(50),
    tb_user_cd bigint,
    owner_cd bigint,
    owner_flg character varying(50),
    kind_flg text,
    nyukin_price integer,
    waribki integer,
    uchikin integer,
    tb_reserve_cd bigint,
    disp_flg text,
    order_sum_all integer,
    tb_staff_cd bigint,
    tb_c2_customer_cd bigint,
    used_points integer,
    to_tel_m text,
    comment text,
    tb_master_cd text,
    delivery_bikou text,
    tb_c2_shopping_cd bigint,
    gift_card integer,
    tax_per integer,
    waribki_per integer,
    tb_c2_card_master_cd bigint,
    unix_date integer,
    cancellation_flg text,
    cancellation_date integer,
    tb_pos_terminal_cd bigint,
    tb_c2_sum_master_cd bigint,
    credit_card integer,
    tax_flg text,
    coming_tb_staff_cd bigint,
    stay_tb_staff_cd bigint,
    print_flg text,
    tb_waribiki_cd bigint,
    tb_waribiki_name text,
    private_order_cd bigint
);


--
-- Name: tb_order_main_pkey; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_order_main_pkey (
    cd integer
);


--
-- Name: tb_order_sub; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_order_sub (
    cd bigint,
    tb_item_cd bigint,
    tb_order_main_cd bigint,
    order_kosu integer,
    tax integer,
    send_price integer,
    little_sum integer,
    tanka integer,
    item_name text,
    item_code text,
    item_id text,
    comment text,
    office_name text,
    order_size text,
    order_ditail text,
    zaiko text,
    price_change text,
    bought_together text,
    item_sub_id text,
    tb_c2_item_sub_cd bigint,
    tb_c2_catalog_cd bigint,
    member_price text,
    sale_flg text,
    sale_parcent text,
    tb_c2_catalog_item_id text,
    order_count integer,
    variation text,
    print_flg text,
    free_limit integer,
    free_master_flg text,
    free_end_unix integer,
    service_kosu integer,
    waribiki_nashi_flg text
);


--
-- Name: tb_package_master; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_package_master (
    cd bigint,
    package_name text,
    version character varying
);


--
-- Name: tb_photo_album; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_photo_album (
    cd bigint,
    tb_customer_cd bigint,
    comment text,
    disp_date text,
    pic_flg text,
    hyouji_flg text,
    disp_flg text,
    touroku_date integer,
    update_date integer
);


--
-- Name: tb_place; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_place (
    cd bigint,
    tb_shop_cd bigint,
    people integer,
    people_child integer,
    available_time integer,
    rank integer,
    name text,
    comment text,
    pic_flg text,
    pic_extension text,
    pic_comment text,
    pic_text text,
    disp_flg text,
    status_flg text,
    hyouji_flg text,
    touroku_date text,
    update_date text,
    tb_printer_cd bigint
);


--
-- Name: tb_play_time; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_play_time (
    cd bigint,
    shop_cd bigint,
    staff_cd bigint,
    felica_no text,
    syukkinbi text,
    syukkin_time_1 text,
    taikin_time_1 text,
    gaisyutu_time_1 text,
    kisha_time_1 text,
    syukkin_flg_1 text,
    syukkin_kubun_1 text,
    komment_1 text,
    etc1_1 text,
    syukkin_time_2 text,
    taikin_time_2 text,
    gaisyutu_time_2 text,
    kisha_time_2 text,
    syukkin_flg_2 text,
    syukkin_kubun_2 text,
    komment_2 text,
    etc1_2 text,
    syukkin_time_3 text,
    taikin_time_3 text,
    gaisyutu_time_3 text,
    kisha_time_3 text,
    syukkin_flg_3 text,
    syukkin_kubun_3 text,
    komment_3 text,
    etc1_3 text,
    syukkin_time_4 text,
    taikin_time_4 text,
    gaisyutu_time_4 text,
    kisha_time_4 text,
    syukkin_flg_4 text,
    syukkin_kubun_4 text,
    komment_4 text,
    etc1_4 text,
    syukkin_time_5 text,
    taikin_time_5 text,
    gaisyutu_time_5 text,
    kisha_time_5 text,
    syukkin_flg_5 text,
    syukkin_kubun_5 text,
    komment_5 text,
    etc1_5 text,
    syukkin_time_6 text,
    taikin_time_6 text,
    gaisyutu_time_6 text,
    kisha_time_6 text,
    syukkin_flg_6 text,
    syukkin_kubun_6 text,
    komment_6 text,
    etc1_6 text,
    syukkin_time_7 text,
    taikin_time_7 text,
    gaisyutu_time_7 text,
    kisha_time_7 text,
    syukkin_flg_7 text,
    syukkin_kubun_7 text,
    komment_7 text,
    etc1_7 text,
    syukkin_time_8 text,
    taikin_time_8 text,
    gaisyutu_time_8 text,
    kisha_time_8 text,
    syukkin_flg_8 text,
    syukkin_kubun_8 text,
    komment_8 text,
    etc1_8 text,
    syukkin_time_9 text,
    taikin_time_9 text,
    gaisyutu_time_9 text,
    kisha_time_9 text,
    syukkin_flg_9 text,
    syukkin_kubun_9 text,
    komment_9 text,
    etc1_9 text,
    syukkin_time_10 text,
    taikin_time_10 text,
    gaisyutu_time_10 text,
    kisha_time_10 text,
    syukkin_flg_10 text,
    syukkin_kubun_10 text,
    komment_10 text,
    etc1_10 text,
    syukkin_time_11 text,
    taikin_time_11 text,
    gaisyutu_time_11 text,
    kisha_time_11 text,
    syukkin_flg_11 text,
    syukkin_kubun_11 text,
    komment_11 text,
    etc1_11 text,
    syukkin_time_12 text,
    taikin_time_12 text,
    gaisyutu_time_12 text,
    kisha_time_12 text,
    syukkin_flg_12 text,
    syukkin_kubun_12 text,
    komment_12 text,
    etc1_12 text,
    syukkin_time_13 text,
    taikin_time_13 text,
    gaisyutu_time_13 text,
    kisha_time_13 text,
    syukkin_flg_13 text,
    syukkin_kubun_13 text,
    komment_13 text,
    etc1_13 text,
    syukkin_time_14 text,
    taikin_time_14 text,
    gaisyutu_time_14 text,
    kisha_time_14 text,
    syukkin_flg_14 text,
    syukkin_kubun_14 text,
    komment_14 text,
    etc1_14 text,
    syukkin_time_15 text,
    taikin_time_15 text,
    gaisyutu_time_15 text,
    kisha_time_15 text,
    syukkin_flg_15 text,
    syukkin_kubun_15 text,
    komment_15 text,
    etc1_15 text,
    syukkin_time_16 text,
    taikin_time_16 text,
    gaisyutu_time_16 text,
    kisha_time_16 text,
    syukkin_flg_16 text,
    syukkin_kubun_16 text,
    komment_16 text,
    etc1_16 text,
    syukkin_time_17 text,
    taikin_time_17 text,
    gaisyutu_time_17 text,
    kisha_time_17 text,
    syukkin_flg_17 text,
    syukkin_kubun_17 text,
    komment_17 text,
    etc1_17 text,
    syukkin_time_18 text,
    taikin_time_18 text,
    gaisyutu_time_18 text,
    kisha_time_18 text,
    syukkin_flg_18 text,
    syukkin_kubun_18 text,
    komment_18 text,
    etc1_18 text,
    syukkin_time_19 text,
    taikin_time_19 text,
    gaisyutu_time_19 text,
    kisha_time_19 text,
    syukkin_flg_19 text,
    syukkin_kubun_19 text,
    komment_19 text,
    etc1_19 text,
    syukkin_time_20 text,
    taikin_time_20 text,
    gaisyutu_time_20 text,
    kisha_time_20 text,
    syukkin_flg_20 text,
    syukkin_kubun_20 text,
    komment_20 text,
    etc1_20 text,
    syukkin_time_21 text,
    taikin_time_21 text,
    gaisyutu_time_21 text,
    kisha_time_21 text,
    syukkin_flg_21 text,
    syukkin_kubun_21 text,
    komment_21 text,
    etc1_21 text,
    syukkin_time_22 text,
    taikin_time_22 text,
    gaisyutu_time_22 text,
    kisha_time_22 text,
    syukkin_flg_22 text,
    syukkin_kubun_22 text,
    komment_22 text,
    etc1_22 text,
    syukkin_time_23 text,
    taikin_time_23 text,
    gaisyutu_time_23 text,
    kisha_time_23 text,
    syukkin_flg_23 text,
    syukkin_kubun_23 text,
    komment_23 text,
    etc1_23 text,
    syukkin_time_24 text,
    taikin_time_24 text,
    gaisyutu_time_24 text,
    kisha_time_24 text,
    syukkin_flg_24 text,
    syukkin_kubun_24 text,
    komment_24 text,
    etc1_24 text,
    syukkin_time_25 text,
    taikin_time_25 text,
    gaisyutu_time_25 text,
    kisha_time_25 text,
    syukkin_flg_25 text,
    syukkin_kubun_25 text,
    komment_25 text,
    etc1_25 text,
    syukkin_time_26 text,
    taikin_time_26 text,
    gaisyutu_time_26 text,
    kisha_time_26 text,
    syukkin_flg_26 text,
    syukkin_kubun_26 text,
    komment_26 text,
    etc1_26 text,
    syukkin_time_27 text,
    taikin_time_27 text,
    gaisyutu_time_27 text,
    kisha_time_27 text,
    syukkin_flg_27 text,
    syukkin_kubun_27 text,
    komment_27 text,
    etc1_27 text,
    syukkin_time_28 text,
    taikin_time_28 text,
    gaisyutu_time_28 text,
    kisha_time_28 text,
    syukkin_flg_28 text,
    syukkin_kubun_28 text,
    komment_28 text,
    etc1_28 text,
    syukkin_time_29 text,
    taikin_time_29 text,
    gaisyutu_time_29 text,
    kisha_time_29 text,
    syukkin_flg_29 text,
    syukkin_kubun_29 text,
    komment_29 text,
    etc1_29 text,
    syukkin_time_30 text,
    taikin_time_30 text,
    gaisyutu_time_30 text,
    kisha_time_30 text,
    syukkin_flg_30 text,
    syukkin_kubun_30 text,
    komment_30 text,
    etc1_30 text,
    syukkin_time_31 text,
    taikin_time_31 text,
    gaisyutu_time_31 text,
    kisha_time_31 text,
    syukkin_flg_31 text,
    syukkin_kubun_31 text,
    komment_31 text,
    etc1_31 text
);


--
-- Name: tb_point_log; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_point_log (
    cd bigint,
    tb_shop_cd bigint,
    tb_staff_cd bigint,
    tb_customer_cd bigint,
    tb_terminal_cd bigint,
    touroku_date bigint,
    shori_mae_point integer,
    kaikei integer,
    sashihiki_kaikei integer,
    shiharai_houhou text,
    percentage real,
    shouhi_point integer,
    jidou_point integer,
    sounyu_point integer,
    meisai_mail text,
    raiten_shori text,
    disp_flg text,
    action_flg text,
    push_master_cd bigint
);


--
-- Name: tb_point_rules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_point_rules (
    cd bigint,
    tb_shop_cd bigint,
    type_flg character varying,
    start_time integer,
    end_time integer,
    day_of_week integer,
    point_count integer,
    touroku_date integer,
    update_date integer,
    hyouji_flg character varying,
    tb_raiten_rank_master_cds text,
    tb_kingaku_rank_master_cds text
);


--
-- Name: tb_point_setting; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_point_setting (
    cd bigint,
    name text,
    rank integer,
    disp_flg text,
    touroku_date text,
    update_date text,
    point integer
);


--
-- Name: tb_pos_catalog; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_pos_catalog (
    cd bigint,
    tb_shop_cd bigint,
    pos_catalog_cd bigint,
    pos_catalog_name text,
    disp_flg text,
    synchronize_flg text,
    synchronize_date text,
    tb_c2_catalog_cd bigint,
    local_sync_flg text
);


--
-- Name: tb_pos_regi_history; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_pos_regi_history (
    cd bigint,
    tb_staff_cd bigint,
    tb_shop_cd bigint,
    tb_pos_terminal_cd bigint,
    youto text,
    touroku_date_text text,
    touroku_date integer,
    date_y integer,
    date_m integer,
    date_d integer
);


--
-- Name: tb_pos_terminal; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_pos_terminal (
    cd bigint,
    tb_shop_cd bigint,
    pos_no integer,
    terminal_no text,
    rank integer,
    disp_flg text,
    md5_key text,
    name text,
    hyouji_flg text,
    comment text,
    touroku_date text,
    tb_printer_cd bigint
);


--
-- Name: tb_pos_terminal_mac_address; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_pos_terminal_mac_address (
    tb_shop_cd bigint,
    mac_address text
);


--
-- Name: tb_pref; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_pref (
    cd bigint,
    area text,
    chiiki text,
    rank integer,
    flg character varying(50),
    souryou integer
);


--
-- Name: tb_printer; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_printer (
    cd bigint,
    rank integer,
    tb_shop_cd bigint,
    host_name text,
    disp_flg text,
    name text,
    hyouji_flg text,
    comment text,
    touroku_date text,
    update_date text,
    printer_type text
);


--
-- Name: tb_raiten_douki_master; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_raiten_douki_master (
    cd bigint,
    name text,
    rank integer,
    disp_flg text,
    touroku_date text,
    update_date integer,
    synchronize_flg text
);


--
-- Name: tb_raiten_rank_master; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_raiten_rank_master (
    cd bigint,
    name text,
    jyouken1 integer,
    jyouken2 integer,
    disp_flg text,
    hyouji_flg text,
    touroku_date text,
    update_date text,
    rank integer
);


--
-- Name: tb_raiten_rireki; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_raiten_rireki (
    cd bigint,
    user_id text,
    user_cd bigint,
    shop_cd bigint,
    raiten_time text,
    stamp integer,
    raiten_hiduke text,
    customer_age integer,
    customer_sex text,
    customer_job text,
    disp_flg text,
    manual_flg text,
    tb_point_log_cd bigint,
    tb_search_cate_cd bigint,
    tb_customer_sub_inner_group text,
    tb_stamp_log_cd bigint
);


--
-- Name: tb_recruit; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_recruit (
    cd bigint,
    boshu_headline text,
    boshu_shokushu integer,
    shigoto_naiyou text,
    koyou_keitai text,
    oubo_shikaku text,
    kinmu_chi text,
    kinmu_jikan text,
    kyuyo text,
    kyuka text,
    taigu text,
    pic_flg1 text,
    pic_flg2 text,
    kaisha_name text,
    add text,
    tel text,
    fax text,
    jigyou_naiyou text,
    oubo_houhou text,
    mensetsuchi text,
    tantou text,
    disp_flg text,
    touroku_date text,
    update_date text,
    tb_shop_cd bigint
);


--
-- Name: tb_recruit_category; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_recruit_category (
    cd bigint,
    tb_shop_cd bigint,
    shokushu text,
    disp_flg text,
    touroku_date text,
    update_date text
);


--
-- Name: tb_reserve; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_reserve (
    cd bigint,
    reserve_time text,
    reserve_cate text,
    reserve_people text,
    reserve_nomination text,
    user_cd text,
    user_name text,
    user_mail text,
    user_tel text,
    shop_cd text,
    update text,
    reserve_flg text,
    raiten_flg character varying(50)
);


--
-- Name: tb_reserve_name; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_reserve_name (
    cd bigint,
    reserve_cate_name text,
    tb_shop_cd bigint,
    disp_flg character varying(50)
);


--
-- Name: tb_schoolclass_config; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_schoolclass_config (
    cd bigint,
    cate_name text,
    cate_order integer,
    disp_flg text
);


--
-- Name: tb_schoolyear_config; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_schoolyear_config (
    cd bigint,
    cate_name text,
    cate_order integer,
    disp_flg text
);


--
-- Name: tb_screensaver; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_screensaver (
    cd bigint,
    type_flg character varying,
    data text,
    select_flg character varying,
    touroku_date text,
    update_date text,
    comment text,
    pic_extension text,
    pic_flg text,
    title text,
    disp_flg text,
    filetype text,
    tb_shop_cd bigint,
    play_frame_sec text,
    tb_display_cd bigint,
    rank integer,
    categorize_value_0 text,
    tb_master_cd bigint
);


--
-- Name: tb_search_cate; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_search_cate (
    cd bigint,
    rank integer,
    flds_row integer,
    crm_type text,
    tb_name text,
    titles text,
    name text,
    data text,
    form_type text,
    disp_flg character varying,
    comment text,
    category text,
    serach_flg text,
    disp_column character varying,
    hyouji_flg character varying,
    record_type character varying
);


--
-- Name: tb_search_form; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_search_form (
    cd bigint,
    rank integer,
    flds_row integer,
    name text,
    titles text,
    data text,
    comment text,
    form_type text,
    disp_flg character varying,
    category text,
    tb_search_cate_cd bigint,
    serach_flg text,
    hissu text,
    unit text,
    style_class text,
    flds_col integer,
    hyouji_flg character varying,
    csv_flg text,
    mail_word_flg text
);


--
-- Name: tb_sejutsu_cate; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_sejutsu_cate (
    cd bigint,
    name text,
    tb_shop_cd bigint,
    disp_flg text
);


--
-- Name: tb_setsubi; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_setsubi (
    cd bigint,
    tb_shop_cd bigint,
    name text,
    quantity integer,
    disp_flg text,
    touroku_date integer,
    update_date integer
);


--
-- Name: tb_shiire; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_shiire (
    cd bigint,
    kaishamei_kana text,
    kaishamei text,
    daihyoushamei_kana text,
    daihyoushamei text,
    gyoushu text,
    zip text,
    pref text,
    add_kana text,
    add text,
    add_number text,
    add_build_kana text,
    add_build text,
    tel text,
    fax text,
    email text,
    tantou_kana text,
    tantou_tel text,
    tantou_fax text,
    tantou_email text,
    tantou text,
    touroku_date character varying(20),
    update_date character varying(20),
    disp_flg character varying(5)
);


--
-- Name: tb_shop; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_shop (
    cd bigint,
    master_cd bigint,
    kaishamei character varying(255),
    kaishamei_kana character varying(255),
    zip character varying(255),
    pref character varying(255),
    add_kana character varying(255),
    add character varying(255),
    add_number character varying(255),
    add_build character varying(255),
    add_build_kana character varying(255),
    tel character varying(255),
    fax character varying(255),
    email character varying(255),
    tantou character varying(255),
    tantou_kana character varying(255),
    touroku_date character varying(255),
    update_date character varying(255),
    shop_url text,
    service_kubun character varying(50),
    disp_flg character varying(50),
    kyuyo_shimebi text,
    kyuyo_marume text,
    start_work_time character varying(255),
    break_time character varying(255),
    kaisha_data text,
    floor_name text,
    pic_flg text,
    point_percentage integer,
    keisan_flg text,
    shiyou_point_jyougen integer,
    point_percentage_genkin real,
    point_percentage_card real,
    hyouji_flg text,
    stamp_percentage_genkin real,
    stamp_percentage_card real,
    pos_domain text,
    shiire_id text,
    shiire_pass text,
    pos_package text,
    sns text,
    sns_info text,
    shop_category_cd integer,
    mycolle_tel text,
    latitude double precision,
    longitude double precision,
    login_point_settings text,
    introduce_settings text,
    customer_registration_settings text,
    beacon_visiting_area integer,
    beacon_accounting_area integer,
    login_default_point_settings text,
    area_cd integer,
    is_closed integer,
    is_new_store integer,
    new_store_start_date text,
    new_store_end_date text
);


--
-- Name: COLUMN tb_shop.sns; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.tb_shop.sns IS 'SNSのアクセストークン格納カラム(json形式)';


--
-- Name: tb_shop_category; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_shop_category (
    cd bigint,
    title text,
    flg integer,
    sort integer,
    record_date timestamp without time zone,
    update_date timestamp without time zone
);


--
-- Name: tb_shop_work; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_shop_work (
    cd bigint,
    shop_cd bigint,
    syukkinbi text,
    syukkin_time_1 text,
    syukkin_time_2 text,
    syukkin_time_3 text,
    syukkin_time_4 text,
    syukkin_time_5 text,
    syukkin_time_6 text,
    syukkin_time_7 text,
    syukkin_time_8 text,
    syukkin_time_9 text,
    syukkin_time_10 text,
    syukkin_time_11 text,
    syukkin_time_12 text,
    syukkin_time_13 text,
    syukkin_time_14 text,
    syukkin_time_15 text,
    syukkin_time_16 text,
    syukkin_time_17 text,
    syukkin_time_18 text,
    syukkin_time_19 text,
    syukkin_time_20 text,
    syukkin_time_21 text,
    syukkin_time_22 text,
    syukkin_time_23 text,
    syukkin_time_24 text,
    syukkin_time_25 text,
    syukkin_time_26 text,
    syukkin_time_27 text,
    syukkin_time_28 text,
    syukkin_time_29 text,
    syukkin_time_30 text,
    syukkin_time_31 text
);


--
-- Name: tb_shukei_link; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_shukei_link (
    cd bigint,
    rank integer,
    disp_flg text,
    url text,
    title text,
    naiyou text
);


--
-- Name: tb_staff; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_staff (
    cd bigint,
    office_cd bigint,
    shop_cd bigint,
    id character varying(255),
    name character varying(255),
    name_kana character varying(255),
    sex character varying(255),
    bloodtype character varying(255),
    email1 character varying(255),
    email2 character varying(255),
    mmail1 character varying(255),
    mmail2 character varying(255),
    entranceday character varying(255),
    retirementday character varying(255),
    belongpost character varying(255),
    employmentsystem character varying(255),
    officialposition character varying(255),
    salary character varying(255),
    paidholiday character varying(255),
    birthday character varying(255),
    zip character varying(255),
    pref character varying(255),
    add character varying(255),
    add_kana character varying(255),
    add_number character varying(255),
    add_build character varying(255),
    add_build_kana character varying(255),
    tel character varying(255),
    fax character varying(255),
    mtel1 character varying(255),
    mtel2 character varying(255),
    near_station1 character varying(255),
    near_station2 character varying(255),
    near_station3 character varying(255),
    near_station4 character varying(255),
    remarks character varying(255),
    traf_transport character varying(255),
    traf_section1_1 character varying(255),
    traf_section1_2 character varying(255),
    traf_charge character varying(255),
    traf_regularfee character varying(255),
    traf_remarks character varying(255),
    login_id character varying(255),
    login_pass character varying(255),
    rank_pass text,
    pic_name character varying(50),
    disp_flg character varying(50),
    felica_no text,
    touroku_date text,
    update_date text,
    reserve_flg character varying(10),
    age integer,
    payment_hour text,
    payment_hour_time text,
    master_flg text,
    hyouji_flg character varying,
    photo_hyouji_flg character varying,
    comment text,
    common_flg text,
    tb_staff_group_cd bigint,
    rank integer,
    pos_rank integer,
    kingaku_kihon integer,
    kingaku_buai real,
    synchronize_flg text,
    local_sync_flg text
);


--
-- Name: tb_staff_group; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_staff_group (
    cd bigint,
    name text,
    rank integer,
    disp_flg text,
    touroku_date text,
    update_date text
);


--
-- Name: tb_staff_shift; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_staff_shift (
    cd bigint,
    shop_cd bigint,
    staff_cd bigint,
    felica_no text,
    syukkinbi text,
    syukkin_time_1 text,
    taikin_time_1 text,
    gaisyutu_time_1 text,
    kisha_time_1 text,
    syukkin_flg_1 text,
    syukkin_kubun_1 text,
    komment_1 text,
    etc1_1 text,
    syukkin_time_2 text,
    taikin_time_2 text,
    gaisyutu_time_2 text,
    kisha_time_2 text,
    syukkin_flg_2 text,
    syukkin_kubun_2 text,
    komment_2 text,
    etc1_2 text,
    syukkin_time_3 text,
    taikin_time_3 text,
    gaisyutu_time_3 text,
    kisha_time_3 text,
    syukkin_flg_3 text,
    syukkin_kubun_3 text,
    komment_3 text,
    etc1_3 text,
    syukkin_time_4 text,
    taikin_time_4 text,
    gaisyutu_time_4 text,
    kisha_time_4 text,
    syukkin_flg_4 text,
    syukkin_kubun_4 text,
    komment_4 text,
    etc1_4 text,
    syukkin_time_5 text,
    taikin_time_5 text,
    gaisyutu_time_5 text,
    kisha_time_5 text,
    syukkin_flg_5 text,
    syukkin_kubun_5 text,
    komment_5 text,
    etc1_5 text,
    syukkin_time_6 text,
    taikin_time_6 text,
    gaisyutu_time_6 text,
    kisha_time_6 text,
    syukkin_flg_6 text,
    syukkin_kubun_6 text,
    komment_6 text,
    etc1_6 text,
    syukkin_time_7 text,
    taikin_time_7 text,
    gaisyutu_time_7 text,
    kisha_time_7 text,
    syukkin_flg_7 text,
    syukkin_kubun_7 text,
    komment_7 text,
    etc1_7 text,
    syukkin_time_8 text,
    taikin_time_8 text,
    gaisyutu_time_8 text,
    kisha_time_8 text,
    syukkin_flg_8 text,
    syukkin_kubun_8 text,
    komment_8 text,
    etc1_8 text,
    syukkin_time_9 text,
    taikin_time_9 text,
    gaisyutu_time_9 text,
    kisha_time_9 text,
    syukkin_flg_9 text,
    syukkin_kubun_9 text,
    komment_9 text,
    etc1_9 text,
    syukkin_time_10 text,
    taikin_time_10 text,
    gaisyutu_time_10 text,
    kisha_time_10 text,
    syukkin_flg_10 text,
    syukkin_kubun_10 text,
    komment_10 text,
    etc1_10 text,
    syukkin_time_11 text,
    taikin_time_11 text,
    gaisyutu_time_11 text,
    kisha_time_11 text,
    syukkin_flg_11 text,
    syukkin_kubun_11 text,
    komment_11 text,
    etc1_11 text,
    syukkin_time_12 text,
    taikin_time_12 text,
    gaisyutu_time_12 text,
    kisha_time_12 text,
    syukkin_flg_12 text,
    syukkin_kubun_12 text,
    komment_12 text,
    etc1_12 text,
    syukkin_time_13 text,
    taikin_time_13 text,
    gaisyutu_time_13 text,
    kisha_time_13 text,
    syukkin_flg_13 text,
    syukkin_kubun_13 text,
    komment_13 text,
    etc1_13 text,
    syukkin_time_14 text,
    taikin_time_14 text,
    gaisyutu_time_14 text,
    kisha_time_14 text,
    syukkin_flg_14 text,
    syukkin_kubun_14 text,
    komment_14 text,
    etc1_14 text,
    syukkin_time_15 text,
    taikin_time_15 text,
    gaisyutu_time_15 text,
    kisha_time_15 text,
    syukkin_flg_15 text,
    syukkin_kubun_15 text,
    komment_15 text,
    etc1_15 text,
    syukkin_time_16 text,
    taikin_time_16 text,
    gaisyutu_time_16 text,
    kisha_time_16 text,
    syukkin_flg_16 text,
    syukkin_kubun_16 text,
    komment_16 text,
    etc1_16 text,
    syukkin_time_17 text,
    taikin_time_17 text,
    gaisyutu_time_17 text,
    kisha_time_17 text,
    syukkin_flg_17 text,
    syukkin_kubun_17 text,
    komment_17 text,
    etc1_17 text,
    syukkin_time_18 text,
    taikin_time_18 text,
    gaisyutu_time_18 text,
    kisha_time_18 text,
    syukkin_flg_18 text,
    syukkin_kubun_18 text,
    komment_18 text,
    etc1_18 text,
    syukkin_time_19 text,
    taikin_time_19 text,
    gaisyutu_time_19 text,
    kisha_time_19 text,
    syukkin_flg_19 text,
    syukkin_kubun_19 text,
    komment_19 text,
    etc1_19 text,
    syukkin_time_20 text,
    taikin_time_20 text,
    gaisyutu_time_20 text,
    kisha_time_20 text,
    syukkin_flg_20 text,
    syukkin_kubun_20 text,
    komment_20 text,
    etc1_20 text,
    syukkin_time_21 text,
    taikin_time_21 text,
    gaisyutu_time_21 text,
    kisha_time_21 text,
    syukkin_flg_21 text,
    syukkin_kubun_21 text,
    komment_21 text,
    etc1_21 text,
    syukkin_time_22 text,
    taikin_time_22 text,
    gaisyutu_time_22 text,
    kisha_time_22 text,
    syukkin_flg_22 text,
    syukkin_kubun_22 text,
    komment_22 text,
    etc1_22 text,
    syukkin_time_23 text,
    taikin_time_23 text,
    gaisyutu_time_23 text,
    kisha_time_23 text,
    syukkin_flg_23 text,
    syukkin_kubun_23 text,
    komment_23 text,
    etc1_23 text,
    syukkin_time_24 text,
    taikin_time_24 text,
    gaisyutu_time_24 text,
    kisha_time_24 text,
    syukkin_flg_24 text,
    syukkin_kubun_24 text,
    komment_24 text,
    etc1_24 text,
    syukkin_time_25 text,
    taikin_time_25 text,
    gaisyutu_time_25 text,
    kisha_time_25 text,
    syukkin_flg_25 text,
    syukkin_kubun_25 text,
    komment_25 text,
    etc1_25 text,
    syukkin_time_26 text,
    taikin_time_26 text,
    gaisyutu_time_26 text,
    kisha_time_26 text,
    syukkin_flg_26 text,
    syukkin_kubun_26 text,
    komment_26 text,
    etc1_26 text,
    syukkin_time_27 text,
    taikin_time_27 text,
    gaisyutu_time_27 text,
    kisha_time_27 text,
    syukkin_flg_27 text,
    syukkin_kubun_27 text,
    komment_27 text,
    etc1_27 text,
    syukkin_time_28 text,
    taikin_time_28 text,
    gaisyutu_time_28 text,
    kisha_time_28 text,
    syukkin_flg_28 text,
    syukkin_kubun_28 text,
    komment_28 text,
    etc1_28 text,
    syukkin_time_29 text,
    taikin_time_29 text,
    gaisyutu_time_29 text,
    kisha_time_29 text,
    syukkin_flg_29 text,
    syukkin_kubun_29 text,
    komment_29 text,
    etc1_29 text,
    syukkin_time_30 text,
    taikin_time_30 text,
    gaisyutu_time_30 text,
    kisha_time_30 text,
    syukkin_flg_30 text,
    syukkin_kubun_30 text,
    komment_30 text,
    etc1_30 text,
    syukkin_time_31 text,
    taikin_time_31 text,
    gaisyutu_time_31 text,
    kisha_time_31 text,
    syukkin_flg_31 text,
    syukkin_kubun_31 text,
    komment_31 text,
    etc1_31 text
);


--
-- Name: tb_staff_shift_kind; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_staff_shift_kind (
    cd bigint,
    name text,
    rank integer,
    disp_flg text,
    touroku_date text,
    update_date text,
    color text
);


--
-- Name: tb_staff_works; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_staff_works (
    cd bigint,
    tb_staff_cd bigint,
    tb_yoyaku_koumoku_master_cd bigint,
    shoyou_time integer,
    hyouji_flg text,
    disp_flg text,
    touroku_date text,
    update_date text,
    mypage_flg text
);


--
-- Name: tb_stamp_config; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_stamp_config (
    cd bigint,
    tb_shop_cd bigint,
    limit_flg character varying(255),
    bonus_flg character varying(255),
    update_date character varying(255),
    stamp_max integer,
    touroku_bonus integer
);


--
-- Name: tb_stamp_log; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_stamp_log (
    cd bigint,
    tb_shop_cd bigint,
    tb_staff_cd bigint,
    tb_customer_cd bigint,
    tb_terminal_cd bigint,
    touroku_date bigint,
    shori_mae_stamp integer,
    kaikei integer,
    shiharai_houhou text,
    percentage real,
    jidou_stamp integer,
    sounyu_stamp integer,
    meisai_mail text,
    raiten_shori text,
    shouhi_stamp integer,
    action_flg text,
    disp_flg text
);


--
-- Name: tb_stamp_oshirase_coupon; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_stamp_oshirase_coupon (
    cd bigint,
    hyouji_flg text,
    disp_flg text,
    touroku_date text,
    update_date text,
    rank integer,
    tb_shop_cd bigint,
    stamp_count integer,
    tb_mycoupon_master_cd text,
    naiyou text
);


--
-- Name: tb_stamp_pattern; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_stamp_pattern (
    cd bigint,
    name text,
    pic_default text,
    pic_shop_flg text,
    narabi text,
    disp_flg text,
    update_date text
);


--
-- Name: tb_stamp_rules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_stamp_rules (
    cd bigint,
    tb_shop_cd bigint,
    type_flg character varying,
    start_time integer,
    end_time integer,
    day_of_week integer,
    stamp_count integer,
    touroku_date integer,
    update_date integer,
    hyouji_flg character varying
);


--
-- Name: tb_terminal; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_terminal (
    cd bigint,
    tb_shop_cd bigint,
    rank integer,
    name text,
    comment text,
    type text,
    model text,
    terminal_no text,
    mac_address text,
    flg character varying,
    touroku_date text,
    update_date text,
    application_flg text,
    latitude text,
    longitude text,
    gps_taiki_flg text,
    gps_raiten_tb_customer_cd bigint,
    gps_disp_flg text,
    gps_hyouji_flg text,
    latlng point,
    cover_text_flg text,
    mycode_disp_flg text
);


--
-- Name: tb_terminal_pkey; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_terminal_pkey (
    cd integer
);


--
-- Name: tb_test_mail_address; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_test_mail_address (
    cd bigint,
    tb_shop_cd bigint,
    name text,
    email text,
    disp_flg text,
    touroku_date text,
    update_date text,
    rank integer
);


--
-- Name: tb_tomodachi_shoukai; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_tomodachi_shoukai (
    tb_customer_cd bigint,
    tomodachi_mail text,
    touroku_date character varying
);


--
-- Name: tb_touch_mode; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_touch_mode (
    cd bigint,
    tb_shop_cd bigint,
    mode character varying,
    interval_time text,
    update_date text
);


--
-- Name: tb_url_list; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_url_list (
    cd integer,
    id text,
    disp_flg text,
    hyouji_flg text,
    url text,
    title text
);


--
-- Name: tb_user_card; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_user_card (
    cd bigint,
    card_no text,
    felica_no text,
    staff_cd text,
    update text,
    card_flg text
);


--
-- Name: tb_waribiki; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_waribiki (
    cd bigint,
    rank integer,
    name text,
    price integer,
    type text,
    waribiki_year integer,
    waribiki_mon integer,
    waribiki_day integer,
    status_flg character varying,
    hyouji_flg character varying,
    disp_flg character varying,
    tb_shop_cd bigint,
    waribiki_nissu integer,
    start_time text,
    end_time text,
    comment text,
    touroku_date text,
    update_date text,
    waribiki_kikan_01 text,
    waribiki_kikan_02 text,
    waribiki_week text,
    coupon_title text,
    coupon_naiyou text,
    coupon_kikan text,
    coupon_notice text,
    coupon_notice_sub text
);


--
-- Name: tb_waribiki_joint; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_waribiki_joint (
    cd bigint,
    tb_waribiki_cd bigint,
    child_tb_name text,
    child_cd bigint
);


--
-- Name: tb_work_sche; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_work_sche (
    cd bigint,
    staffcd integer,
    "名前" text,
    "店舗" character varying(50),
    "年月" character varying(50),
    "出勤時間_1" text,
    "退勤時間_1" text,
    "出勤flg_1" text,
    "出勤アイコンflg_1" text,
    "待ち時間_1" text,
    "出勤区分_1" text,
    "出勤時間_2" text,
    "退勤時間_2" text,
    "出勤flg_2" text,
    "出勤アイコンflg_2" text,
    "待ち時間_2" text,
    "出勤区分_2" text,
    "出勤時間_3" text,
    "退勤時間_3" text,
    "出勤flg_3" text,
    "出勤アイコンflg_3" text,
    "待ち時間_3" text,
    "出勤区分_3" text,
    "出勤時間_4" text,
    "退勤時間_4" text,
    "出勤flg_4" text,
    "出勤アイコンflg_4" text,
    "待ち時間_4" text,
    "出勤区分_4" text,
    "出勤時間_5" text,
    "退勤時間_5" text,
    "出勤flg_5" text,
    "出勤アイコンflg_5" text,
    "待ち時間_5" text,
    "出勤区分_5" text,
    "出勤時間_6" text,
    "退勤時間_6" text,
    "出勤flg_6" text,
    "出勤アイコンflg_6" text,
    "待ち時間_6" text,
    "出勤区分_6" text,
    "出勤時間_7" text,
    "退勤時間_7" text,
    "出勤flg_7" text,
    "出勤アイコンflg_7" text,
    "待ち時間_7" text,
    "出勤区分_7" text,
    "出勤時間_8" text,
    "退勤時間_8" text,
    "出勤flg_8" text,
    "出勤アイコンflg_8" text,
    "待ち時間_8" text,
    "出勤区分_8" text,
    "出勤時間_9" text,
    "退勤時間_9" text,
    "出勤flg_9" text,
    "出勤アイコンflg_9" text,
    "待ち時間_9" text,
    "出勤区分_9" text,
    "出勤時間_10" text,
    "退勤時間_10" text,
    "出勤flg_10" text,
    "出勤アイコンflg_10" text,
    "待ち時間_10" text,
    "出勤区分_10" text,
    "出勤時間_11" text,
    "退勤時間_11" text,
    "出勤flg_11" text,
    "出勤アイコンflg_11" text,
    "待ち時間_11" text,
    "出勤区分_11" text,
    "出勤時間_12" text,
    "退勤時間_12" text,
    "出勤flg_12" text,
    "出勤アイコンflg_12" text,
    "待ち時間_12" text,
    "出勤区分_12" text,
    "出勤時間_13" text,
    "退勤時間_13" text,
    "出勤flg_13" text,
    "出勤アイコンflg_13" text,
    "待ち時間_13" text,
    "出勤区分_13" text,
    "出勤時間_14" text,
    "退勤時間_14" text,
    "出勤flg_14" text,
    "出勤アイコンflg_14" text,
    "待ち時間_14" text,
    "出勤区分_14" text,
    "出勤時間_15" text,
    "退勤時間_15" text,
    "出勤flg_15" text,
    "出勤アイコンflg_15" text,
    "待ち時間_15" text,
    "出勤区分_15" text,
    "出勤時間_16" text,
    "退勤時間_16" text,
    "出勤flg_16" text,
    "出勤アイコンflg_16" text,
    "待ち時間_16" text,
    "出勤区分_16" text,
    "出勤時間_17" text,
    "退勤時間_17" text,
    "出勤flg_17" text,
    "出勤アイコンflg_17" text,
    "待ち時間_17" text,
    "出勤区分_17" text,
    "出勤時間_18" text,
    "退勤時間_18" text,
    "出勤flg_18" text,
    "出勤アイコンflg_18" text,
    "待ち時間_18" text,
    "出勤区分_18" text,
    "出勤時間_19" text,
    "退勤時間_19" text,
    "出勤flg_19" text,
    "出勤アイコンflg_19" text,
    "待ち時間_19" text,
    "出勤区分_19" text,
    "出勤時間_20" text,
    "退勤時間_20" text,
    "出勤flg_20" text,
    "出勤アイコンflg_20" text,
    "待ち時間_20" text,
    "出勤区分_20" text,
    "出勤時間_21" text,
    "退勤時間_21" text,
    "出勤flg_21" text,
    "出勤アイコンflg_21" text,
    "待ち時間_21" text,
    "出勤区分_21" text,
    "出勤時間_22" text,
    "退勤時間_22" text,
    "出勤flg_22" text,
    "出勤アイコンflg_22" text,
    "待ち時間_22" text,
    "出勤区分_22" text,
    "出勤時間_23" text,
    "退勤時間_23" text,
    "出勤flg_23" text,
    "出勤アイコンflg_23" text,
    "待ち時間_23" text,
    "出勤区分_23" text,
    "出勤時間_24" text,
    "退勤時間_24" text,
    "出勤flg_24" text,
    "出勤アイコンflg_24" text,
    "待ち時間_24" text,
    "出勤区分_24" text,
    "出勤時間_25" text,
    "退勤時間_25" text,
    "出勤flg_25" text,
    "出勤アイコンflg_25" text,
    "待ち時間_25" text,
    "出勤区分_25" text,
    "出勤時間_26" text,
    "退勤時間_26" text,
    "出勤flg_26" text,
    "出勤アイコンflg_26" text,
    "待ち時間_26" text,
    "出勤区分_26" text,
    "出勤時間_27" text,
    "退勤時間_27" text,
    "出勤flg_27" text,
    "出勤アイコンflg_27" text,
    "待ち時間_27" text,
    "出勤区分_27" text,
    "出勤時間_28" text,
    "退勤時間_28" text,
    "出勤flg_28" text,
    "出勤アイコンflg_28" text,
    "待ち時間_28" text,
    "出勤区分_28" text,
    "出勤時間_29" text,
    "退勤時間_29" text,
    "出勤flg_29" text,
    "出勤アイコンflg_29" text,
    "待ち時間_29" text,
    "出勤区分_29" text,
    "出勤時間_30" text,
    "退勤時間_30" text,
    "出勤flg_30" text,
    "出勤アイコンflg_30" text,
    "待ち時間_30" text,
    "出勤区分_30" text,
    "出勤時間_31" text,
    "退勤時間_31" text,
    "出勤flg_31" text,
    "出勤アイコンflg_31" text,
    "待ち時間_31" text,
    "出勤区分_31" text
);


--
-- Name: tb_work_time; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_work_time (
    cd bigint,
    shop_cd bigint,
    staff_cd bigint,
    felica_no text,
    syukkinbi text,
    syukkin_time_1 text,
    taikin_time_1 text,
    gaisyutu_time_1 text,
    kisha_time_1 text,
    syukkin_flg_1 text,
    syukkin_kubun_1 text,
    komment_1 text,
    etc1_1 text,
    syukkin_time_2 text,
    taikin_time_2 text,
    gaisyutu_time_2 text,
    kisha_time_2 text,
    syukkin_flg_2 text,
    syukkin_kubun_2 text,
    komment_2 text,
    etc1_2 text,
    syukkin_time_3 text,
    taikin_time_3 text,
    gaisyutu_time_3 text,
    kisha_time_3 text,
    syukkin_flg_3 text,
    syukkin_kubun_3 text,
    komment_3 text,
    etc1_3 text,
    syukkin_time_4 text,
    taikin_time_4 text,
    gaisyutu_time_4 text,
    kisha_time_4 text,
    syukkin_flg_4 text,
    syukkin_kubun_4 text,
    komment_4 text,
    etc1_4 text,
    syukkin_time_5 text,
    taikin_time_5 text,
    gaisyutu_time_5 text,
    kisha_time_5 text,
    syukkin_flg_5 text,
    syukkin_kubun_5 text,
    komment_5 text,
    etc1_5 text,
    syukkin_time_6 text,
    taikin_time_6 text,
    gaisyutu_time_6 text,
    kisha_time_6 text,
    syukkin_flg_6 text,
    syukkin_kubun_6 text,
    komment_6 text,
    etc1_6 text,
    syukkin_time_7 text,
    taikin_time_7 text,
    gaisyutu_time_7 text,
    kisha_time_7 text,
    syukkin_flg_7 text,
    syukkin_kubun_7 text,
    komment_7 text,
    etc1_7 text,
    syukkin_time_8 text,
    taikin_time_8 text,
    gaisyutu_time_8 text,
    kisha_time_8 text,
    syukkin_flg_8 text,
    syukkin_kubun_8 text,
    komment_8 text,
    etc1_8 text,
    syukkin_time_9 text,
    taikin_time_9 text,
    gaisyutu_time_9 text,
    kisha_time_9 text,
    syukkin_flg_9 text,
    syukkin_kubun_9 text,
    komment_9 text,
    etc1_9 text,
    syukkin_time_10 text,
    taikin_time_10 text,
    gaisyutu_time_10 text,
    kisha_time_10 text,
    syukkin_flg_10 text,
    syukkin_kubun_10 text,
    komment_10 text,
    etc1_10 text,
    syukkin_time_11 text,
    taikin_time_11 text,
    gaisyutu_time_11 text,
    kisha_time_11 text,
    syukkin_flg_11 text,
    syukkin_kubun_11 text,
    komment_11 text,
    etc1_11 text,
    syukkin_time_12 text,
    taikin_time_12 text,
    gaisyutu_time_12 text,
    kisha_time_12 text,
    syukkin_flg_12 text,
    syukkin_kubun_12 text,
    komment_12 text,
    etc1_12 text,
    syukkin_time_13 text,
    taikin_time_13 text,
    gaisyutu_time_13 text,
    kisha_time_13 text,
    syukkin_flg_13 text,
    syukkin_kubun_13 text,
    komment_13 text,
    etc1_13 text,
    syukkin_time_14 text,
    taikin_time_14 text,
    gaisyutu_time_14 text,
    kisha_time_14 text,
    syukkin_flg_14 text,
    syukkin_kubun_14 text,
    komment_14 text,
    etc1_14 text,
    syukkin_time_15 text,
    taikin_time_15 text,
    gaisyutu_time_15 text,
    kisha_time_15 text,
    syukkin_flg_15 text,
    syukkin_kubun_15 text,
    komment_15 text,
    etc1_15 text,
    syukkin_time_16 text,
    taikin_time_16 text,
    gaisyutu_time_16 text,
    kisha_time_16 text,
    syukkin_flg_16 text,
    syukkin_kubun_16 text,
    komment_16 text,
    etc1_16 text,
    syukkin_time_17 text,
    taikin_time_17 text,
    gaisyutu_time_17 text,
    kisha_time_17 text,
    syukkin_flg_17 text,
    syukkin_kubun_17 text,
    komment_17 text,
    etc1_17 text,
    syukkin_time_18 text,
    taikin_time_18 text,
    gaisyutu_time_18 text,
    kisha_time_18 text,
    syukkin_flg_18 text,
    syukkin_kubun_18 text,
    komment_18 text,
    etc1_18 text,
    syukkin_time_19 text,
    taikin_time_19 text,
    gaisyutu_time_19 text,
    kisha_time_19 text,
    syukkin_flg_19 text,
    syukkin_kubun_19 text,
    komment_19 text,
    etc1_19 text,
    syukkin_time_20 text,
    taikin_time_20 text,
    gaisyutu_time_20 text,
    kisha_time_20 text,
    syukkin_flg_20 text,
    syukkin_kubun_20 text,
    komment_20 text,
    etc1_20 text,
    syukkin_time_21 text,
    taikin_time_21 text,
    gaisyutu_time_21 text,
    kisha_time_21 text,
    syukkin_flg_21 text,
    syukkin_kubun_21 text,
    komment_21 text,
    etc1_21 text,
    syukkin_time_22 text,
    taikin_time_22 text,
    gaisyutu_time_22 text,
    kisha_time_22 text,
    syukkin_flg_22 text,
    syukkin_kubun_22 text,
    komment_22 text,
    etc1_22 text,
    syukkin_time_23 text,
    taikin_time_23 text,
    gaisyutu_time_23 text,
    kisha_time_23 text,
    syukkin_flg_23 text,
    syukkin_kubun_23 text,
    komment_23 text,
    etc1_23 text,
    syukkin_time_24 text,
    taikin_time_24 text,
    gaisyutu_time_24 text,
    kisha_time_24 text,
    syukkin_flg_24 text,
    syukkin_kubun_24 text,
    komment_24 text,
    etc1_24 text,
    syukkin_time_25 text,
    taikin_time_25 text,
    gaisyutu_time_25 text,
    kisha_time_25 text,
    syukkin_flg_25 text,
    syukkin_kubun_25 text,
    komment_25 text,
    etc1_25 text,
    syukkin_time_26 text,
    taikin_time_26 text,
    gaisyutu_time_26 text,
    kisha_time_26 text,
    syukkin_flg_26 text,
    syukkin_kubun_26 text,
    komment_26 text,
    etc1_26 text,
    syukkin_time_27 text,
    taikin_time_27 text,
    gaisyutu_time_27 text,
    kisha_time_27 text,
    syukkin_flg_27 text,
    syukkin_kubun_27 text,
    komment_27 text,
    etc1_27 text,
    syukkin_time_28 text,
    taikin_time_28 text,
    gaisyutu_time_28 text,
    kisha_time_28 text,
    syukkin_flg_28 text,
    syukkin_kubun_28 text,
    komment_28 text,
    etc1_28 text,
    syukkin_time_29 text,
    taikin_time_29 text,
    gaisyutu_time_29 text,
    kisha_time_29 text,
    syukkin_flg_29 text,
    syukkin_kubun_29 text,
    komment_29 text,
    etc1_29 text,
    syukkin_time_30 text,
    taikin_time_30 text,
    gaisyutu_time_30 text,
    kisha_time_30 text,
    syukkin_flg_30 text,
    syukkin_kubun_30 text,
    komment_30 text,
    etc1_30 text,
    syukkin_time_31 text,
    taikin_time_31 text,
    gaisyutu_time_31 text,
    kisha_time_31 text,
    syukkin_flg_31 text,
    syukkin_kubun_31 text,
    komment_31 text,
    etc1_31 text
);


--
-- Name: tb_yoyaku; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_yoyaku (
    cd bigint,
    tb_yoyaku_uketsuke_day_cd bigint,
    tb_customer_cd bigint,
    start_time integer,
    end_time integer,
    hyouji_flg text,
    disp_flg text,
    touroku_date text,
    update_date text,
    name text,
    tel text,
    people integer,
    base_tb_yoyaku_koumoku_master_cd text,
    option_tb_yoyaku_koumoku_master_cd text,
    comment text,
    mail text,
    print_flg text,
    name_kana text,
    free_koumoku text,
    pos_menu_cate text,
    pos_menu text,
    pos_menu_tantousha text,
    entry_type text,
    mark text
);


--
-- Name: tb_yoyaku_config; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_yoyaku_config (
    cd bigint,
    tb_shop_cd bigint,
    start_time text,
    end_time text,
    shimekiri text,
    automail text,
    automail_time text,
    touroku_date text,
    update_date text,
    shimekiri_time text,
    uketuke_type text,
    timeline_sort_type text,
    setsubi_capacity integer,
    hide_price_flg text
);


--
-- Name: tb_yoyaku_koumoku_category; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_yoyaku_koumoku_category (
    cd bigint,
    name text,
    hyouji_flg text,
    disp_flg text,
    touroku_date text,
    update_date text,
    rank integer
);


--
-- Name: tb_yoyaku_koumoku_master; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_yoyaku_koumoku_master (
    cd bigint,
    name text,
    shoyou_time integer,
    hyouji_flg text,
    disp_flg text,
    touroku_date text,
    update_date text,
    naiyou text,
    tb_shop_cd bigint,
    type_flg text,
    rank integer,
    tb_yoyaku_koumoku_category_cd bigint,
    menu_end_time_flg text,
    menu_end_time integer,
    tb_setsubi_cd bigint
);


--
-- Name: tb_yoyaku_order_main; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_yoyaku_order_main (
    cd bigint,
    tb_order_main_cd bigint,
    tb_yoyaku_cd bigint,
    tb_raiten_rireki_cd bigint
);


--
-- Name: tb_yoyaku_uketsuke_day; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tb_yoyaku_uketsuke_day (
    cd bigint,
    tb_staff_cd bigint,
    moushikomi_date text,
    start_time integer,
    end_time integer,
    people integer,
    hyouji_flg text,
    disp_flg text,
    touroku_date text,
    update_date text,
    status_flg text,
    start_break_time text,
    end_break_time text
);


--
-- Name: url_transfer_data; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.url_transfer_data (
    access timestamp without time zone,
    tb_customer_cd bigint,
    data_id text
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: yoyaku; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.yoyaku (
    cd bigint,
    user_cd bigint,
    user_name character varying(255),
    course_cd bigint,
    course_name character varying(255),
    course_time integer,
    course_extension integer,
    course_starttime integer,
    gals_cd bigint,
    gals_name character varying(255),
    "店id" integer,
    user_etc text,
    uketuke_time integer
);


--
-- Name: zip; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.zip (
    zip character varying(255),
    pref character varying(255),
    city character varying(255),
    town character varying(255),
    pref1 character varying(255),
    city1 character varying(255),
    town1 character varying(255)
);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: sales_items cd; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sales_items ALTER COLUMN cd SET DEFAULT nextval('public.sales_items_cd_seq'::regclass);


--
-- Name: smatopi_message cd; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.smatopi_message ALTER COLUMN cd SET DEFAULT nextval('public.smatopi_message_cd_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: sales_items sales_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sales_items
    ADD CONSTRAINT sales_items_pkey PRIMARY KEY (cd);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: tb_coupon tb_coupon_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tb_coupon
    ADD CONSTRAINT tb_coupon_pkey PRIMARY KEY (cd);


--
-- Name: tb_haikei tb_haikei_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tb_haikei
    ADD CONSTRAINT tb_haikei_pkey PRIMARY KEY (cd);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: tb_koumoku_pkey; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX tb_koumoku_pkey ON public.tb_koumoku USING btree (cd);


--
-- PostgreSQL database dump complete
--

\unrestrict E8spK2ijzxAe8WNFUCdD3oeLWo4kBr60A2aptGvah6jubIbBEhdYx6VanOaKgmr

--
-- PostgreSQL database dump
--

\restrict E3NiKPLILfUDU17mmlkpFvjFPh8Vrry3m9Ke1ekFDCT7FZX0OwjyytBMPUvHoZw

-- Dumped from database version 18.7 (Debian 18.7-1.pgdg13+5)
-- Dumped by pg_dump version 18.7 (Debian 18.7-1.pgdg13+5)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.migrations VALUES (1, '0001_01_01_000000_create_users_table', 1);
INSERT INTO public.migrations VALUES (2, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO public.migrations VALUES (3, '0001_01_01_000002_create_jobs_table', 1);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 3, true);


--
-- PostgreSQL database dump complete
--

\unrestrict E3NiKPLILfUDU17mmlkpFvjFPh8Vrry3m9Ke1ekFDCT7FZX0OwjyytBMPUvHoZw

