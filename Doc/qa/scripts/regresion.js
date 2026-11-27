async (page) => {
  // Regresión S1: recorre los menús (cabecera + menú lateral, con submenús) de :8081 y abre cada pantalla enlazada.
  // Solo la web LOCAL. Anota estado HTTP, página en blanco, página de error y XHR/recursos que fallan al cargar.
  const L = 'http://localhost:8081';
  const USER = '__USER__', PASS = '__PASS__';
  const SEEDS = ['/system/select.php', '/system/category0/', '/system/category1/', '/system/category3/', '/system/category5/', '/system/category6/', '/system/category7/', '/system/category9/'];
  // nunca seguir enlaces que borran, reinician, cierran sesión o están vetados por CLAUDE.md
  const SKIP = /logout|activation=1|create_customer|create_shop|[?&_\/](del|delete|reset|init|clear)[=_.\/&]|sakujo|_del\.php|del_href/i;

  page.on('dialog', d => d.dismiss().catch(() => {}));
  // sin diálogos: el MCP los intercepta y corta la ejecución
  await page.context().addInitScript(() => { window.alert = () => {}; window.confirm = () => false; window.prompt = () => null; window.print = () => {}; window.onbeforeunload = null; });
  page.context().on('page', p => { if (p !== page) p.close().catch(() => {}); });

  await page.goto(L + '/system/logout.php').catch(() => {});
  await page.goto(L + '/system/login.php');
  await page.fill('[name=admin_id]', USER);
  await page.fill('[name=admin_pass]', PASS);
  await Promise.all([page.waitForNavigation().catch(() => {}), page.click('#login')]).catch(() => {});
  const out = { user: USER, login: page.url().replace(L, ''), visited: [], problems: [], forbidden: [] };

  const collect = () => {
    const links = [];
    const norm = s => (s || '').replace(/\s+/g, ' ').trim();
    document.querySelectorAll('#header a, .leftNavi a, #leftNavi a, .leftNavi1 a, #yoyakuNavi a, .functionSelect a, .selectBox a, .categoryBox a').forEach(a => {
      const pop = /(?:openPopWindow|roid\.popUp)\('([^']+)'/.exec(a.getAttribute('onclick') || '');
      const h = pop ? pop[1] : a.getAttribute('href');
      if (!h || /^(javascript:|#|mailto:)/i.test(h)) return;
      try {
        const u = new URL(h, location.href);
        if (u.origin === location.origin) links.push([u.pathname + u.search, norm(a.textContent).slice(0, 40)]);
      } catch (e) {}
    });
    const body = document.body;
    const text = body ? norm(body.innerText) : '';
    const err = /Server Error|Internal Server Error|Whoops|Fatal error|Warning:|Exception|SQLSTATE|Undefined (variable|array key|index)/.exec(text);
    return { links, len: text.length, err: err ? text.slice(Math.max(0, err.index - 40), err.index + 160) : '' };
  };

  const queue = SEEDS.map(p => [p, '(semilla)', '']);
  const EXTRA = [["/system/category0/config/config.php","base: 基本情報確認・変更","(config/menu)"],["/system/category0/config/features_config.php","base: 機能設定","(config/menu)"],["/system/category0/config/authority.php","base: 権限管理","(config/menu)"],["/system/category0/config/bookmark.php","base: ショートカット管理","(config/menu)"],["/system/category0/config/authority_contents_list.php","base: 権限項目管理","(config/menu)"],["/system/category0/config/system.php","base: システム管理","(config/menu)"],["/system/category0/config/slim_list.php","base: CRM端末管理","(config/menu)"],["/system/category0/config/area.php","base: エリア管理","(config/menu)"],["/system/category0/shop/shop_new.php","base: 店舗新規登録","(config/menu)"],["/system/category0/shop/shop_list.php","base: 店舗検索・一覧","(config/menu)"],["/system/category0/shop/shop_category.php","base: 店舗グループ","(config/menu)"],["/system/category1/customer/customer_new.php","clientes: 顧客新規登録","(config/menu)"],["/system/category1/customer/customer_list.php","clientes: 顧客検索","(config/menu)"],["/system/category1/customer/customer_search.php","clientes: 来店処理","(config/menu)"],["/system/category1/customer/form_config.php","clientes: 登録・検索項目設定","(config/menu)"],["/system/category1/shukei/shukei_list.php","clientes: 顧客情報集計","(config/menu)"],["/system/category1/customer/point_setting.php","clientes: ポイント管理","(config/menu)"],["/system/category1/customer/point_history.php","clientes: ポイント履歴","(config/menu)"],["/system/category1/customer/customer_group_category_new.php","clientes: 顧客グループ設定","(config/menu)"],["/system/category1/customer/raiten_douki.php","clientes: 初回来店動機項目設定","(config/menu)"],["/system/category1/rank/customer_rank_list.php","clientes: 顧客ランク設定","(config/menu)"],["/system/category1/rank/customer_rank_schedule.php","clientes: 顧客ランク振り分け設定","(config/menu)"],["/system/category1/customer/search_cate_list.php","clientes: 顧客追加情報設定","(config/menu)"],["/system/category1/customer/csv_import.php","clientes: CSVインポート","(config/menu)"],["/system/category1/entrance/working_calendar.php","clientes: 入退室一覧","(config/menu)"],["/system/category1/config/felica_mode.php","clientes: カードリーダーモード設定","(config/menu)"],["/system/category1/config/card_new.php","clientes: カード管理","(config/menu)"],["/system/category1/karute_menu/karute_new.php","clientes: 売上データ作成","(config/menu)"],["/system/category1/karute_menu/karute_delete_list.php","clientes: 売上データ削除","(config/menu)"],["/system/category1/karute_menu/karute_menu.php","clientes: メニュー管理","(config/menu)"],["/system/category1/karute_menu/karute_waribiki.php","clientes: 割引管理","(config/menu)"],["/system/category1/karute_menu/keihi.php","clientes: 経費管理","(config/menu)"],["/system/category1/karute_menu/staff_total.php","clientes: スタッフ別集計","(config/menu)"],["/system/category1/karute_menu/report.php","clientes: 日報・週報・月報","(config/menu)"],["/admin/mypage/app-images/all","mypage: マイコレ管理","(config/menu)"],["/admin/mypage/app-images/cover","mypage: カバーフロー画像設定管理","(config/menu)"],["/admin/mypage/app-images/banner","mypage: バナー画像設定管理","(config/menu)"],["/admin/mypage/app-images/main","mypage: HOMEメインイメージ管理","(config/menu)"],["/admin/mypage/app-images/thumbnail","mypage: サムネイル画像管理","(config/menu)"],["/admin/mypage/recommendations","mypage: オススメ管理","(config/menu)"],["/admin/mypage/recruits/create","mypage: 求人情報新規登録","(config/menu)"],["/admin/mypage/recruits","mypage: 求人情報一覧","(config/menu)"],["/admin/mypage/recruit-categories","mypage: 求人職種管理","(config/menu)"],["/admin/operaciones/staff/create","operaciones: スタッフ新規登録","(config/menu)"],["/admin/operaciones/staff","operaciones: スタッフ検索・一覧","(config/menu)"],["/admin/operaciones/staff-groups","operaciones: スタッフグループ設定","(config/menu)"],["/admin/operaciones/staff-fields","operaciones: スタッフ検索項目設定","(config/menu)"],["/admin/operaciones/information","operaciones: インフォメーション管理","(config/menu)"],["/admin/operaciones/working/calendar","operaciones: 勤怠一覧","(config/menu)"],["/admin/operaciones/working/report","operaciones: 帳票管理","(config/menu)"],["/admin/operaciones/kintai","operaciones: 勤怠データ一覧","(config/menu)"],["/admin/operaciones/kintai/password","operaciones: 端末パスワード変更","(config/menu)"],["/admin/operaciones/kintai/config","operaciones: 各種設定","(config/menu)"],["/admin/operaciones/shifts/day","operaciones: 日別シフト管理","(config/menu)"],["/admin/operaciones/shifts/month","operaciones: 月別シフト管理","(config/menu)"],["/admin/operaciones/shift-kinds","operaciones: シフト区分管理","(config/menu)"],["/admin/operaciones/shop-work","operaciones: 営業日設定","(config/menu)"],["/system/category3/mail_mag/base.php","promocion: QRコード情報","(config/menu)"],["/system/category3/mail_mag/base_mycolle.php","promocion: マイコレ登録用QR","(config/menu)"],["/system/category3/mail_mag/base_mailmaga.php","promocion: メルマガ用QR","(config/menu)"],["/system/category3/mail_mag/base_point.php","promocion: ポイント利用QR","(config/menu)"],["/admin/promocion/mail_mag/mail_customer_select","promocion: 新規メール配信","(config/menu)"],["/admin/promocion/mail_mag/mail_future_list","promocion: 予定配信メール一覧","(config/menu)"],["/admin/promocion/mail_mag/mail_past","promocion: 過去配信メール一覧","(config/menu)"],["/admin/promocion/mail_mag/mail_template","promocion: テンプレート管理","(config/menu)"],["/admin/promocion/mail_mag/test_mail_address","promocion: テストメールアドレス管理","(config/menu)"],["/admin/promocion/mail_mag/mailmag_analyze","promocion: クリックカウンタ","(config/menu)"],["/admin/promocion/mycoupon/mycoupon_new","promocion: クーポン新規登録","(config/menu)"],["/admin/promocion/mycoupon/mycoupon_list","promocion: クーポン一覧","(config/menu)"],["/admin/promocion/mycoupon/mail_past","promocion: 過去クーポン配信一覧","(config/menu)"],["/admin/promocion/mail_mag/mail_template","promocion: テンプレート管理","(config/menu)"],["/admin/promocion/mycoupon/mycoupon_ranking","promocion: 使用ランキング","(config/menu)"],["/admin/promocion/mycoupon/mycoupon_analyze","promocion: クリックカウンタ","(config/menu)"],["/admin/promocion/mycoupon/mycoupon_stats","promocion: クーポン利用数確認","(config/menu)"],["/system/category3/anketo/anketo_cate_list.php","promocion: アンケート一覧","(config/menu)"],["/system/category3/anketo/anketo_cate_list.php?which=sended","promocion: 過去アンケート配信一覧","(config/menu)"],["/admin/promocion/mail_mag/mail_template","promocion: テンプレート管理","(config/menu)"],["/admin/promocion/anketo/anketo_analyze","promocion: クリックカウンタ","(config/menu)"],["/system/category3/smatopi/smatopi_message.php","promocion: メッセージ作成","(config/menu)"],["/system/category3/smatopi/smatopi_sent_history.php","promocion: メッセージ送信履歴","(config/menu)"],["https://smatopi.net/login","promocion: スマトピ管理画面","(config/menu)"],["/system/category3/anketo2/anketo_cate_new.php","promocion: お客様アンケート新規登録","(config/menu)"],["/system/category3/anketo2/anketo_cate_list.php","promocion: お客様アンケート一覧","(config/menu)"],["/admin/promocion/decomail/mail_customer_select","promocion: 新規デコメール配信","(config/menu)"],["/admin/promocion/decomail/mail_future_list","promocion: 予定配信デコメール一覧","(config/menu)"],["/admin/promocion/decomail/mail_past","promocion: 過去配信メール一覧","(config/menu)"],["/admin/promocion/mail_mag/mail_template","promocion: テンプレート管理","(config/menu)"],["/admin/promocion/decomail/mailmag_analyze","promocion: クリックカウンタ","(config/menu)"],["/system/category3/visit/visit_stats2.php?kikan=0","promocion: 来店履歴一覧","(config/menu)"],["/system/category3/visit/touch_config.php","promocion: カードリーダ設定","(config/menu)"],["/system/category3/reservation/reserve_customer_search.php","promocion: 予約新規登録","(config/menu)"],["/system/category3/reservation/reserve_calendar.php","promocion: 予約・来店一覧","(config/menu)"],["/system/category3/reservation/category_entry.php","promocion: 予約カテゴリ登録・変更","(config/menu)"],["/system/category3/reservation/config.php","promocion: 予約項目設定","(config/menu)"],["/system/category3/reservation/model_reservation.php","promocion: 予約機種一覧","(config/menu)"],["/system/category3/stamp/stamp_pattern.php","promocion: スタンプデザイン管理","(config/menu)"],["/system/category3/stamp/stamp_config.php","promocion: スタンプ仕様管理","(config/menu)"],["/system/category3/stamp/stamp_rules_list.php","promocion: スタンプルール一覧","(config/menu)"],["/system/category3/stamp/oshirase_coupon.php","promocion: クーポンお知らせ機能管理","(config/menu)"],["/admin/promocion/point/point_config","promocion: ポイント仕様管理","(config/menu)"],["/admin/promocion/point/point_rules_list","promocion: ポイントルール一覧","(config/menu)"],["/admin/promocion/point/point_aggregate","promocion: ポイント集計","(config/menu)"],["/admin/promocion/point/csv_grant_points","promocion: ポイント一括付与","(config/menu)"],["/admin/promocion/follow/follow_new","promocion: フォローメール新規作成","(config/menu)"],["/admin/promocion/follow/follow_mail_future_list","promocion: フォローメール設定一覧","(config/menu)"],["/admin/promocion/mail_mag/mail_template","promocion: テンプレート管理","(config/menu)"],["/admin/promocion/auto/auto_new","promocion: 自動メール新規作成","(config/menu)"],["/admin/promocion/auto/auto_mail_future_list","promocion: 自動メール設定一覧","(config/menu)"],["/admin/promocion/mail_mag/mail_template","promocion: テンプレート管理","(config/menu)"],["/system/category3/push/push_customer_select.php","promocion: プッシュ通知新規作成","(config/menu)"],["/system/category3/push/push_follow_list.php?type_flg=scheduled","promocion: 予定通知設定一覧","(config/menu)"],["/system/category3/push/push_past.php","promocion: 過去配信内容一覧","(config/menu)"],["/admin/promocion/follow/follow_new?kind=followPush","promocion: フォロー通知新規作成","(config/menu)"],["/system/category3/push/push_follow_list.php?type_flg=follow","promocion: フォロー通知設定一覧","(config/menu)"],["/admin/promocion/auto/auto_new?kind=autoPush","promocion: 自動通知新規作成","(config/menu)"],["/system/category3/push/push_follow_list.php?type_flg=auto","promocion: 自動通知設定一覧","(config/menu)"],["/system/category3/push/push_history.php","promocion: プッシュ通知集計","(config/menu)"],["/admin/promocion/reminder/reminder_new","promocion: リマインダメール新規作成","(config/menu)"],["/admin/promocion/reminder/reminder_mail_future_list","promocion: リマインダメール設定一覧","(config/menu)"],["/admin/promocion/mail_mag/mail_template","promocion: テンプレート管理","(config/menu)"],["/system/category3/reservation/model_reservation.php","promocion: 予約機種一覧","(config/menu)"],["/admin/terminales/signage-programs","terminales: 放映プログラム管理","(config/menu)"],["/admin/terminales/signage-displays","terminales: ディスプレイ管理","(config/menu)"],["/admin/terminales/paper-cards","terminales: カード管理","(config/menu)"],["/admin/terminales/felica-terminals","terminales: 端末管理","(config/menu)"],["/admin/terminales/felica-cards?sesClear=card","terminales: カード管理","(config/menu)"],["/system/category7/beacon/beacon_list.php","terminales: ビーコン管理","(config/menu)"],["/system/category2/item/list.php","ventas: 商品管理","(config/menu)"]];
  const seen = new Set();
  let n = 0;
  let extraDone = false;
  while ((queue.length || !extraDone) && n < 600) {
    if (!queue.length) { extraDone = true; EXTRA.forEach(e => queue.push(e)); continue; }
    const [p, label, from] = queue.shift();
    const key = p.replace(/[?&]t=\d+/, '');
    if (seen.has(key) || SKIP.test(p)) continue;
    seen.add(key);
    n++;
    const bad = [];
    const onResp = r => {
      const t = r.request().resourceType();
      if (r.status() >= 400 && r.url().startsWith(L) && ['xhr', 'fetch', 'script', 'stylesheet', 'document'].includes(t) && r.request().frame() === page.mainFrame()) {
        bad.push(`${t} ${r.status()} ${r.url().replace(L, '')}`);
      }
    };
    const jsErr = [];
    const onErr = e => jsErr.push(String(e.message).split('\n')[0].slice(0, 120));
    page.on('response', onResp);
    page.on('pageerror', onErr);
    let status = '?', info = { links: [], len: 0, err: '' }, title = '';
    try {
      const r = await page.goto(L + p, { waitUntil: 'load', timeout: 30000 });
      await page.waitForTimeout(400);
      status = r ? r.status() : '?';
      title = await page.title();
      info = await page.evaluate(collect);
    } catch (e) {
      status = 'ERR ' + e.message.split('\n')[0].slice(0, 100);
    }
    page.off('response', onResp);
    page.off('pageerror', onErr);
    const final = page.url().replace(L, '');
    out.visited.push(`${status} ${p}${final !== p ? ' → ' + final : ''}`);
    const docBad = bad.filter(b => !b.startsWith('document') || !b.endsWith(' ' + p));
    if (status === 403) { out.forbidden.push(p); }
    else if (status !== 200 || info.len < 30 || info.err || docBad.length || jsErr.length) {
      out.problems.push({ url: p, label, from, status, final, title, len: info.len, err: info.err, bad: docBad.slice(0, 6), js: jsErr.slice(0, 3) });
    }
    // seguir solo los menús de páginas del CRM (no de reproductores ni popups)
    if (status === 200 && /^\/(system|admin)\//.test(final)) {
      for (const [h, t] of info.links) {
        const k = h.replace(/[?&]t=\d+/, '');
        if (!seen.has(k) && !extraDone) queue.push([h, t, p]);
      }
    }
  }
  out.count = n;
  out.left = queue.length;
  return JSON.stringify(out);
}
