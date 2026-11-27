<?php

namespace App\Http\Controllers\Publico\Integraciones\Appview;

use App\Http\Controllers\Controller;
use App\Models\Promotions\Mycoupon;
use App\Services\Integraciones\Appview\MobileTheme;
use App\Services\Integraciones\ToolApi\ToolApiOutput as Out;
use App\Services\Promotions\CouponService;
use App\Support\LegacyText;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * appview: マイクーポン del socio (coupon.php, coupon_detail.php, coupon_control.php, coupon_use.php,
 * used_coupon_list.php). Sin autenticación, por customer_cd (contrato del legacy). El uso y la impresión pasan
 * por el CouponService de S3.
 */
class CouponController extends Controller
{
    use RendersAppview;

    /** $_SESSION['mobile'] del legacy */
    private const SESSION = 'appview.mobile';

    private const MASTER_COLUMNS = ['cd', 'tb_shop_cd', 'title', 'comment', 'attention'];

    /**
     * coupon.php: cupones vigentes del socio (paginados con kensu/page). Como el legacy, antes borra los
     * cupones caducados de todos los socios (time_limit < ahora − 5 h: valen hasta las 04:59:59 del día siguiente).
     */
    public function index(Request $request, MobileTheme $theme): Response
    {
        $in = MobileTheme::input($request, LegacyText::form(...));
        $customerCd = $in['customer_cd'] ?? null;
        if ((int) $customerCd == 0) {
            return Out::text('');
        }
        if (! DB::table('tb_customer')->where('cd', (int) $customerCd)->exists()) {
            return Out::text('該当の顧客データは存在しません。');
        }

        $now = time() - 5 * 60 * 60;
        $this->silently(fn () => Mycoupon::query()->whereRaw('time_limit::bigint < ?', [$now])->delete());

        $active = fn () => DB::table('tb_mycoupon')->where('tb_customer_cd', (int) $customerCd)->whereRaw("COALESCE(used_flg, '') != '1'");
        $checkCount = DB::table('tb_mycoupon as tb1')->join('tb_mycoupon_master as tb2', 'tb1.tb_mycoupon_master_cd', '=', 'tb2.cd')
            ->where('tb1.tb_customer_cd', (int) $customerCd)->whereRaw("COALESCE(tb1.used_flg, '') != '1'")
            ->distinct()->count('tb2.tb_shop_cd');

        [$rows, $pageControl] = $this->paginate($in, $active()->count(), '/appview/coupon.php', false,
            fn (int $limit, int $offset) => $active()->orderBy('time_limit')->limit($limit)->offset($offset)->get());

        return $this->page('coupon', $request, $this->themeData($theme) + [
            'customer_cd' => $customerCd,
            'check_count' => $checkCount,
            'mycoupon_max' => count($rows),
            'coupons' => $this->withMasters($rows),
            'page_control' => $pageControl,
        ]);
    }

    /**
     * coupon_detail.php?cd=&customer_cd=: detalle con el botón 使用する (y お店で印刷する si el sistema 印刷 está
     * activo). Si el cupón no es del socio responde 302 a coupon.php pero, como el legacy, con la página vacía.
     */
    public function show(Request $request, MobileTheme $theme): Response
    {
        $in = MobileTheme::input($request, LegacyText::input(...));
        $customerCd = $in['customer_cd'] ?? null;
        if (empty($in['cd'] ?? null) || (int) $customerCd == 0) {
            return Out::text('');
        }

        $coupon = DB::table('tb_mycoupon')->where('cd', (int) $in['cd'])->where('tb_customer_cd', (int) $customerCd)->first();
        $master = $coupon ? DB::table('tb_mycoupon_master')->where('cd', $coupon->tb_mycoupon_master_cd)->first() : null;
        $master = self::row($master, self::MASTER_COLUMNS);

        $shopName = ['kaishamei' => null];
        if ($master['tb_shop_cd'] == '-1') {
            $shopName['kaishamei'] = '全店舗';
        } elseif ($master['tb_shop_cd'] !== null) {
            $name = DB::table('tb_shop')->where('cd', (int) $master['tb_shop_cd'])->where('disp_flg', '!=', '9')->value('kaishamei');
            $shopName['kaishamei'] = $name;
        }

        // Ninguna opción queda seleccionada: el legacy comparaba con una variable sin asignar
        $options = null;
        foreach (DB::table('tb_shop')->where('disp_flg', '!=', '9')->orderBy('cd')->get(['cd', 'kaishamei']) as $shop) {
            $options .= "<option value=\"{$shop->cd}\" >".$shop->kaishamei."</option>\n";
        }

        $html = $this->page('coupon-detail', $request, $this->themeData($theme) + [
            'customer_cd' => $customerCd,
            'mycoupon_array' => self::row($coupon, ['cd', 'time_limit', 'tb_mycoupon_master_cd']),
            'tb_mycoupon_master_array' => $master,
            'tb_shop_name_array' => $shopName,
            'shop_option_list' => $options,
            'shop_max' => DB::table('tb_shop')->count(),
            'print_count' => DB::table('tb_base_system_grandchild')->where('system_name', '印刷')->where('system_flg', '1')->count(),
        ]);
        if (! $coupon) {
            $html->setStatusCode(302)->headers->set('Location', './coupon.php?customer_cd='.$customerCd);
        }

        return $html;
    }

    /**
     * coupon_control.php: act=use marca el cupón como usado en tb_shop_cd y lleva a coupon_use.php; act=print lo
     * deja pendiente de imprimir en esa tienda y lleva a coupon_print.php (no existe en el legacy). En crm2-php8
     * act=use da un fatal (constante `N` sin definir) y no usa el cupón: aquí se usa, como en PHP 7.
     */
    public function use(Request $request, CouponService $coupons): Response
    {
        $in = MobileTheme::input($request, LegacyText::input(...));
        $customerCd = $in['customer_cd'] ?? null;
        if ((int) $customerCd == 0) {
            return Out::text('');
        }

        $coupon = Mycoupon::query()->find((int) ($in['my_coupon_cd'] ?? 0));
        if (! $coupon) {
            return Out::text('', 302, ['Location' => './coupon.php?customer_cd='.$customerCd]);
        }

        $act = $in['act'] ?? null;
        if ($act == 'use' && $customerCd) {
            $coupons->markUsed($coupon, (int) ($in['tb_shop_cd'] ?? 0));
            $target = 'coupon_use.php';
        } elseif ($act == 'print' && ! empty($in['tb_shop_cd'] ?? null) && $customerCd) {
            $coupon->used_date = (string) now()->getTimestamp();
            $coupons->markForPrint($coupon, (int) $in['tb_shop_cd']);
            $target = 'coupon_print.php';
        } else {
            return Out::text('');
        }

        $request->session()->put(self::SESSION.'.coupon_ses', ['coupon_cd' => $in['coupon_cd'] ?? null, 'my_coupon_cd' => $in['my_coupon_cd'] ?? null]);
        $request->session()->put(self::SESSION.'.customer_cd', $customerCd);

        return Out::text('', 302, ['Location' => $target.'?PHPSESSID='.$request->session()->getId()]);
    }

    /** coupon_use.php: el cupón ya usado (de la sesión que deja coupon_control, o por POST) para enseñarlo en caja */
    public function used(Request $request, MobileTheme $theme): Response
    {
        $post = [];
        foreach ($request->post() as $key => $value) {
            // Filtro de kihon.php sobre $_POST (antes de leer la sesión)
            $post[$key] = preg_match('/_cd$/', (string) $key) && (! is_string($value) || ! preg_match('/^\-?[1-9][0-9]*$/', $value))
                ? (is_array($value) ? $value : intval($value)) : $value;
        }
        $session = $request->session()->get(self::SESSION.'.coupon_ses');
        if ($session) {
            $post['coupon_cd'] = $session['coupon_cd'] ?? null;
            $post['my_coupon_cd'] = $session['my_coupon_cd'] ?? null;
        }
        $post = array_map(fn ($v) => is_array($v) ? $v : LegacyText::input((string) $v), $post);

        $coupon = DB::table('tb_mycoupon_used')->where('cd', (int) ($post['my_coupon_cd'] ?? 0))->first(['cd', 'time_limit', 'tb_shop_cd', 'used_date']);
        $master = DB::table('tb_mycoupon_master')->where('cd', (int) ($post['coupon_cd'] ?? 0))->first();

        $customerCd = $request->session()->get(self::SESSION.'.customer_cd');
        $user = ['user_id' => '', 'name' => ''];
        if ($customerCd) {
            // tb_customer no tiene user_id: el legacy imprimía 会員番号 vacío
            $user = ['user_id' => null, 'name' => DB::table('tb_customer')->where('cd', (int) $customerCd)->value('name')];
        }

        return $this->page('coupon-use', $request, $this->themeData($theme) + [
            'coupon_array' => self::row($coupon, ['cd', 'time_limit', 'tb_shop_cd', 'used_date']),
            'tb_mycoupon_master_array' => self::row($master, self::MASTER_COLUMNS),
            'user_id' => $user['user_id'],
            'user_name' => $user['name'],
            'customer_cd' => $customerCd,
        ]);
    }

    /** used_coupon_list.php: cupones usados del socio, del más reciente */
    public function usedList(Request $request, MobileTheme $theme): Response
    {
        $in = MobileTheme::input($request, LegacyText::form(...));
        $customerCd = $in['customer_cd'] ?? null;
        if ((int) $customerCd == 0) {
            return Out::text('');
        }

        $checkCount = DB::table('tb_mycoupon as tb1')->join('tb_mycoupon_master as tb2', 'tb1.tb_mycoupon_master_cd', '=', 'tb2.cd')
            ->where('tb1.tb_customer_cd', (int) $customerCd)->whereRaw("COALESCE(tb1.used_flg, '') != '1'")
            ->distinct()->count('tb2.tb_shop_cd');
        $used = fn () => DB::table('tb_mycoupon_used')->where('tb_customer_cd', (int) $customerCd);

        [$rows, $pageControl] = $this->paginate($in, $used()->count(), '/appview/used_coupon_list.php', true,
            fn (int $limit, int $offset) => $used()->orderByDesc('used_date')->limit($limit)->offset($offset)->get());

        return $this->page('used-coupons', $request, $this->themeData($theme) + [
            'customer_cd' => $customerCd,
            'check_count' => $checkCount,
            'mycoupon_max' => count($rows),
            'coupons' => $this->withMasters($rows),
            'page_control' => $pageControl,
        ]);
    }

    /** Variables de hp/inc_m/kihon.php que usan las plantillas */
    private function themeData(MobileTheme $theme): array
    {
        return [
            'theme' => $theme,
            'templateData' => $theme->templateData,
            'h2Color' => $theme->h2Color,
            'h2BGColor' => $theme->h2BGColor,
            'emoji' => $theme->emoji(...),
        ];
    }

    /**
     * Paginación de coupon.php / used_coupon_list.php (kensu por defecto 5, page 1; la página activa sin enlace).
     *
     * @param  callable(int, int): iterable  $fetch
     * @return array{0: list<object>, 1: string}
     */
    private function paginate(array $in, int $total, string $self, bool $spanCurrent, callable $fetch): array
    {
        $kensu = ($in['kensu'] ?? null) != '' ? $in['kensu'] : 5;
        $page = ($in['page'] ?? null) != '' ? $in['page'] : 1;

        $maxpage = $kensu ? ceil($total / $kensu) : 0;
        if ($page > $maxpage) {
            $page = $maxpage;
        }
        $from = $page > 0 ? ($page - 1) * $kensu : 0;

        $control = '';
        if ($page > 1) {
            $control .= '<a href="'.$self.'?page='.($page - 1).'" accesskey="*">&lt;&lt;*.前へ</a>';
        }
        if ($maxpage > 1) {
            $start = $page - 5 < 1 ? 1 : $page - 5;
            $end = $page + 5 > $maxpage ? $maxpage : $page + 5;
            for ($l = $start; $l <= $end; $l++) {
                $control .= $l == $page
                    ? ($spanCurrent ? '<span>'.$l.'</span>' : (string) $l)
                    : ' <a href="'.$self.'?page='.$l.'">'.$l.'</a> ';
            }
        }
        if ($page < $maxpage) {
            $control .= '<a href="'.$self.'?page='.($page + 1).'" accesskey="#">#.次へ&gt;&gt;</a>';
        }

        // LIMIT negativo u OFFSET negativo: la consulta del legacy fallaba y no listaba nada
        $limit = (int) $kensu;
        $rows = $limit < 0 || $from < 0 ? [] : $this->silently(fn () => collect($fetch($limit, (int) $from))->all()) ?? [];

        return [$rows, $control];
    }

    /**
     * Cada fila con su tb_mycoupon_master y el nombre de la tienda (全店舗 para -1). El legacy recalculaba el
     * nombre una vez por cada tienda de tb_shop: sin tiendas conservaba el de la vuelta anterior.
     *
     * @return list<array{0: array, 1: array, 2: ?string}>
     */
    private function withMasters(array $rows): array
    {
        $hasShops = DB::table('tb_shop')->exists();
        $shopName = null;
        $list = [];
        foreach ($rows as $row) {
            $master = self::row(DB::table('tb_mycoupon_master')->where('cd', $row->tb_mycoupon_master_cd)->first(), self::MASTER_COLUMNS);
            if ($hasShops) {
                $shopName = $master['tb_shop_cd'] != '-1'
                    ? ($master['tb_shop_cd'] === null ? '' : (string) DB::table('tb_shop')->where('cd', (int) $master['tb_shop_cd'])->value('kaishamei'))
                    : '全店舗';
            }
            $list[] = [self::row($row), $master, $shopName];
        }

        return $list;
    }

    /** Ejecuta una consulta que en el legacy podía fallar en silencio (@pg_query) dentro de un savepoint */
    private function silently(callable $query): mixed
    {
        try {
            return DB::transaction($query);
        } catch (Throwable) {
            return null;
        }
    }
}
