DELETE FROM tb_bookmark WHERE 1=1;
UPDATE hatuban SET kazu = 61 WHERE koumoku = 'tb_bookmark';


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	1,
	1,
	10,
	'/system/category1/customer/customer_new.php',
	'会員登録',
	'新規会員登録',
	'新規で会員登録を行う',
	'1',
	1001,
	1001,
	2
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	2,
	1,
	10,
	'/system/category1/customer/customer_list.php',
	'顧客検索',
	'顧客検索',
	'顧客データからの検索',
	'1',
	1002
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	3,
	1,
	10,
	'/system/category1/customer/form_config.php',
	'顧客項目設定',
	'表示・検索・CSV項目設定',
	'会員登録時や検索時の項目設定。及びCSV出力項目設定。',
	'1',
	1003
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	4,
	1,
	10,
	'/system/category1/shukei/shukei_list.php',
	'顧客集計',
	'顧客情報集計',
	'各種集計ツール',
	'1',
	1004
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	58,
	1,
	10,
	'/system/category1/rank/customer_rank_list.php',
	'顧客ランク設定',
	'顧客ランク設定',
	'指定した基準に準じたランクを顧客に付与するための項目設定',
	'1',
	1005
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	59,
	1,
	10,
	'/system/category1/rank/customer_rank_schedule.php',
	'顧客ランク振り分け設定',
	'顧客ランク振り分け設定',
	'指定した基準に準じたランクを顧客に付与するための振り分け設定',
	'1',
	1006
	)
;




INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	5,
	3,
	30,
	'/system/category3/mail_mag/base.php',
	'メルマガ基本情報',
	'メールマガジン基本情報',
	'会員登録状況と、モバイルQRコードなど。',
	'1',
	3001
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	6,
	3,
	30,
	'/system/category3/mail_mag/mail_customer_select.php',
	'新規メルマガ',
	'新規メール配信',
	'新規メルマガ配信を行います。',
	'1',
	3002,
	1005,
	2
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	7,
	3,
	30,
	'/system/category3/mail_mag/mail_future_list.php',
	'予定メール一覧',
	'予定メール配信一覧',
	'現在予定済みのメルマガ（通常）の一覧です。',
	'1',
	3003,
	1005,
	1
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	7,
	3,
	30,
	'/system/category3/mail_mag/mail_past.php',
	'過去メール一覧',
	'過去メール配信一覧',
	'過去に配信済みのメルマガ（通常）の一覧です。',
	'1',
	3004,
	1005,
	1
	)
;



INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	8,
	3,
	30,
	'/system/category3/mail_mag/mail_template.php',
	'メールテンプレ',
	'テンプレート管理',
	'メルマガのテンプレートを管理します。',
	'1',
	3005
	)
;



INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	8,
	3,
	30,
	'/system/category3/mail_mag/test_mail_address.php',
	'テストアドレス管理',
	'テストメールアドレス管理',
	'テスト配信する際のメールアドレスを管理します。',
	'1',
	3006
	)
;



INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	9,
	3,
	30,
	'/system/category3/mail_mag/mailmag_analyze.php',
	'カウンタ（通常メルマガ）',
	'クリックカウンタ（通常メルマガ）',
	'メルマガ配信した際のクリックカウンタです。',
	'1',
	3007
	)
;



INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	10,
	3,
	38,
	'/system/category3/decomail/mail_customer_select.php',
	'新規デコメ',
	'新規デコメール配信',
	'デコレーションを施したメルマガの新規作成。',
	'1',
	3008,
	1006,
	2
	)
;



INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	11,
	3,
	38,
	'/system/category3/decomail/mail_future_list.php',
	'予定デコメ',
	'予定配信デコメール一覧',
	'現在予定されているデコメール一覧',
	'1',
	3009,
	1006,
	1
	)
;



INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	12,
	3,
	38,
	'/system/category3/decomail/mail_past.php',
	'過去配信デコメ',
	'過去配信デコメール一覧',
	'過去に配信されたデコメール一覧',
	'1',
	3010,
	1006,
	1
	)
;



INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	13,
	3,
	38,
	'/system/category3/decomail/mailmag_analyze.php',
	'カウンタ（デコメール）',
	'クリックカウンタ（デコメール）',
	'メルマガ配信した際のクリックカウンタです。',
	'1',
	3011
	)
;



INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	14,
	3,
	103,
	'/system/category3/follow/follow_new.php',
	'新規フォローメール',
	'フォローメール新規作成',
	'フォローメールを新規作成します。',
	'1',
	3012,
	1007,
	2
	)
;



INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	15,
	3,
	103,
	'/system/category3/follow/follow_mail_future_list.php',
	'フォローメール一覧',
	'フォローメール設定一覧',
	'現在設定されているフォローメールの一覧です。',
	'1',
	3013,
	1007,
	1
	)
;



INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	16,
	3,
	104,
	'/system/category3/auto/auto_new.php',
	'自動メール新規',
	'自動メール新規作成',
	'自動メールを新規作成します。',
	'1',
	3014,
	1008,
	2
	)
;



INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	17,
	3,
	104,
	'/system/category3/auto/auto_mail_future_list.php',
	'自動メール一覧',
	'自動メール設定一覧',
	'現在設定されている自動メールの一覧です。',
	'1',
	3015,
	1008,
	1
	)
;



INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	18,
	3,
	32,
	'/system/category3/mycoupon/mycoupon_new.php',
	'クーポン新規',
	'マイクーポン新規登録',
	'クーポンを新規作成します。',
	'1',
	3016,
	1009,
	2
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	19,
	3,
	32,
	'/system/category3/mycoupon/mycoupon_list.php',
	'クーポン一覧',
	'マイクーポン一覧',
	'現在設定されているクーポンの一覧です。',
	'1',
	3017,
	1009,
	1
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	20,
	3,
	32,
	'/system/category3/mycoupon/mail_past.php',
	'過去クーポンメール一覧',
	'過去クーポン配信メール一覧',
	'過去に配信されたクーポンメールの一覧です。',
	'1',
	3018,
	1009,
	1
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	21,
	3,
	32,
	'/system/category3/mycoupon/mycoupon_ranking.php',
	'使用ランキング',
	'使用ランキング',
	'使用されたクーポンのランキングです。',
	'1',
	3019
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	22,
	3,
	32,
	'/system/category3/mycoupon/mycoupon_analyze.php',
	'カウンタ（クーポン）',
	'クリックカウンタ（クーポン）',
	'クーポンメールを配信した際のクリックカウンタです。',
	'1',
	3020
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	23,
	3,
	33,
	'/system/category3/stamp/stamp_pattern.php',
	'スタンプデザイン',
	'スタンプデザイン管理',
	'スタンプのデザインを選択できます。',
	'1',
	3021,
	1010,
	1
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	24,
	3,
	33,
	'/system/category3/stamp/stamp_config.php',
	'スタンプ仕様',
	'スタンプ仕様管理',
	'スタンプの上限やインターバルを設定できます。',
	'1',
	3022,
	1010,
	2
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	25,
	3,
	33,
	'/system/category3/stamp/stamp_rules_list.php',
	'スタンプルール',
	'スタンプルール一覧',
	'現在設定されているスタンプルールの一覧です。',
	'1',
	3023,
	1010,
	3
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	26,
	3,
	33,
	'/system/category3/stamp/oshirase_coupon.php',
	'クーポンお知らせ',
	'クーポンお知らせ機能管理',
	'スタンプで交換できるクーポンのお知らせ内容を設定できます。',
	'1',
	3024,
	1010,
	4
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	27,
	3,
	34,
	'/system/category3/anketo/anketo_cate_list.php',
	'アンケート一覧',
	'アンケート一覧',
	'現在設定されているアンケートの一覧です。',
	'1',
	3025,
	1011,
	1
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	28,
	3,
	34,
	'/system/category3/anketo/anketo_cate_list.php?which=sended',
	'過去アンケート一覧',
	'過去アンケート配信一覧',
	'過去に配信されたアンケートの一覧です。',
	'1',
	3026,
	1011,
	2
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	29,
	3,
	34,
	'/system/category3/anketo/anketo_analyze.php',
	'カウンタ（アンケート）',
	'クリックカウンタ（アンケート）',
	'アンケートを配信した際のクリックカウンタです。',
	'1',
	3027
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	30,
	3,
	36,
	'/system/category3/visit/visit_stats2.php',
	'来店履歴一覧',
	'来店履歴一覧',
	'来店履歴の一覧です。',
	'1',
	3028,
	1012,
	1
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	31,
	3,
	36,
	'/system/category3/visit/touch_config.php',
	'カードリーダ設定',
	'カードリーダ設定',
	'リーダのインターバルを設定できます。',
	'1',
	3029,
	1012,
	2
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	32,
	3,
	37,
	'/system/category3/reservation/reserve_customer_search.php',
	'予約新規登録',
	'予約新規登録',
	'予約の新規登録ができます。',
	'1',
	3030,
	1013,
	1
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	33,
	3,
	37,
	'/system/category3/reservation/reserve_calendar.php',
	'予約・来店一覧',
	'予約・来店一覧',
	'予約の確認ができます。',
	'1',
	3031,
	1013,
	2
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	34,
	3,
	37,
	'/system/category3/reservation/category_entry.php',
	'予約カテゴリ編集',
	'予約カテゴリ登録・変更',
	'予約のメニューをカテゴリーとして登録変更できます。',
	'1',
	3032,
	1013,
	4
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	35,
	3,
	37,
	'/system/category3/reservation/config.php',
	'予約項目設定',
	'予約項目設定',
	'予約時に必要な入力項目を設定できます。',
	'1',
	3033,
	1013,
	4
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	36,
	6,
	60,
	'/system/category6/user_hp/mobile_hp_edit.php',
	'マイページ管理',
	'マイページデザイン管理',
	'マイページのデザインをテンプレートより選択して設定できます。',
	'1',
	6001,
	1014,
	1
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	37,
	6,
	60,
	'/system/category6/user_hp/topics.php',
	'トピックス管理',
	'トピックス管理',
	'マイページのトピックス記事の編集ができます。',
	'1',
	6002,
	1015,
	1
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	38,
	6,
	60,
	'/system/category6/user_hp/recommend.php',
	'オススメ管理',
	'オススメ管理',
	'マイページのオススメ記事の編集ができます。',
	'1',
	6003,
	1016,
	1
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	39,
	6,
	60,
	'/system/category6/user_hp/header.php',
	'ヘッダ画像管理（マイページ）',
	'ヘッダ画像管理（マイページ）',
	'マイページのヘッダ画像の登録ができます。',
	'1',
	6004,
	1017,
	1
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	40,
	6,
	61,
	'/system/category6/shop_hp/mobile_hp_edit.php',
	'携帯HP管理',
	'携帯HPデザイン管理',
	'携帯HPのデザインをテンプレートより選択して設定できます。',
	'1',
	6005,
	1018,
	1
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	41,
	6,
	61,
	'/system/category6/shop_hp/whatsnew.php',
	'新着情報管理',
	'新着情報管理',
	'携帯HPの新着情報を編集できます。',
	'1',
	6006,
	1019,
	1
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	42,
	6,
	61,
	'/system/category6/shop_hp/header.php',
	'ヘッダ画像管理（携帯HP）',
	'ヘッダ画像管理（携帯HP）',
	'携帯HPのヘッダ画像の登録ができます。',
	'1',
	6007,
	1020,
	1
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	43,
	6,
	61,
	'/system/category6/shop_hp/company.php',
	'店舗詳細管理',
	'店舗詳細管理',
	'携帯HPの店舗詳細情報の編集ができます。',
	'1',
	6008,
	1021,
	1
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	44,
	6,
	61,
	'/system/category6/shop_hp/freepage.php',
	'フリーページ管理',
	'フリーページ管理',
	'携帯HPのフリーページ情報の編集ができます。',
	'1',
	6009,
	1022,
	1
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	45,
	6,
	62,
	'/system/category6/recruit/recruit_new.php',
	'求人新規登録',
	'求人情報新規登録',
	'携帯HPの求人情報の登録ができます。',
	'1',
	6010,
	1023,
	1
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	46,
	6,
	62,
	'/system/category6/recruit/recruit_list.php',
	'求人情報一覧',
	'求人情報一覧',
	'現在設定されている求人情報の一覧です。',
	'1',
	6011,
	1023,
	1
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	47,
	6,
	62,
	'/system/category6/recruit/recruit_category.php',
	'求人職種管理',
	'求人職種管理',
	'募集する職種の登録ができます。',
	'1',
	6012,
	1023,
	2
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank,
	tb_kengen_contents_cd,
	tb_kengen_contents_node
	)
VALUES (
	48,
	5,
	50,
	'/system/category5/staff/staff_entry.php',
	'スタッフ新規登録',
	'スタッフ新規登録',
	'スタッフの新規登録ができます。',
	'1',
	5001,
	1024,
	2
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	49,
	5,
	50,
	'/system/category5/staff/staff_list.php',
	'スタッフ検索・一覧',
	'スタッフ検索・一覧',
	'スタッフの検索及び情報の一覧です。',
	'1',
	5002
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	51,
	5,
	50,
	'/system/category5/staff/form_config.php',
	'スタッフ検索項目',
	'スタッフ検索項目設定',
	'スタッフ検索時の項目が設定できます。',
	'1',
	5004
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	52,
	5,
	50,
	'/system/category5/staff/information.php',
	'インフォメーション',
	'インフォメーション管理',
	'CRMガジェットのインフォメーション枠に情報を投稿できます。',
	'1',
	5005
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	53,
	7,
	106,
	'/system/category7/kiosk/movie_program_list.php',
	'映像管理',
	'映像管理',
	'サイネージで表示する映像の管理ができます。',
	'1',
	7001
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	54,
	7,
	106,
	'/system/category7/kiosk/display_list.php',
	'映像振り分け',
	'映像振り分け管理',
	'サイネージを表示する端末の管理ができます。',
	'1',
	7002
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	55,
	6,
	107,
	'/system/category6/digital_catalog/digital_catalog_new.php',
	'フォトギャラリー登録',
	'フォトギャラリー新規登録',
	'フォトギャラリーの新規掲載分を登録します。',
	'1',
	6013
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	56,
	6,
	107,
	'/system/category6/digital_catalog/digital_catalog_list.php',
	'フォトギャラリー一覧',
	'フォトギャラリー掲載一覧',
	'現在掲載中のフォトギャラリーの一覧です。',
	'1',
	6014
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	57,
	6,
	107,
	'/system/category6/digital_catalog/digital_catalog_tag.php',
	'フォトギャラリーカテゴリ',
	'フォトギャラリーカテゴリ管理',
	'フォトギャラリーで使用しているカテゴリの管理です。',
	'1',
	6015
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	58,
	1,
	10,
	'/system/category1/rank/customer_rank_list.php',
	'顧客ランク設定',
	'顧客ランク設定',
	'指定した基準に準じたランクを顧客に付与するための項目設定。',
	'1',
	1005
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	59,
	1,
	10,
	'/system/category1/rank/customer_rank_schedule.php',
	'顧客ランク集計',
	'顧客ランク集計',
	'指定した基準に準じたランクを顧客に付与するための集計設定。',
	'1',
	1006
	)
;


INSERT INTO tb_bookmark (
	cd,
	tb_base_system_cd,
	tb_base_system_child_cd,
	url,
	bookmark_name,
	title,
	naiyou,
	disp_flg,
	rank
	)
VALUES (
	60,
	3,
	32,
	'/system/category3/mycoupon/mycoupon_stats.php',
	'クーポン利用数',
	'クーポン利用数確認',
	'利用されたクーポンの店舗ごと、期間ごとの統計を確認できます。',
	'1',
	3020
	)
;





