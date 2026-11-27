<?php

namespace App\Http\Controllers\Admin\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\Operaciones\KintaiConfig;
use App\Models\Shop;
use App\Services\Operaciones\KintaiBackground;
use App\Services\Operaciones\KintaiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 勤怠管理, solo master (el menú del legacy solo los muestra al master):
 * 端末パスワード変更 (password_update.php + _complete) y 各種設定 (config.php: 日付変更基準時間, 始業/終業時間,
 * CSV mensual y 勤怠打刻画面の背景画像). La sección シフト区分管理 de config.php es su propia pantalla.
 * La contraseña sigue en texto plano: la lee el terminal (anotado para Japón).
 */
class KintaiConfigController extends Controller
{
    public function editPassword(Request $request): View
    {
        return $this->passwordForm($request);
    }

    public function updatePassword(Request $request): View|RedirectResponse
    {
        $shopCd = $this->shop($request);
        $before = (string) $request->input('passBefore', '');
        $after = (string) $request->input('passAfter', '');

        $error = match (true) {
            $before === '' => '現在のパスワードを入力してください。',
            ! self::alnum($before) => '現在のパスワードは半角英数字で入力してください。',
            ! KintaiConfig::query()->where('tb_shop_cd', $shopCd)->where('password', $before)->exists() => '現在のパスワードが正しくありません。',
            $after === '' => '新しいパスワードを入力してください。',
            ! self::alnum($after) => '新しいパスワードは半角英数字で入力してください。',
            default => null,
        };
        if ($error !== null) {
            return $this->passwordForm($request, $error);
        }

        KintaiConfig::query()->where('tb_shop_cd', $shopCd)->update(['password' => $after]);

        return to_route('admin.operaciones.kintai.password.complete', ['shop' => $shopCd]);
    }

    public function passwordComplete(Request $request): View
    {
        return view('admin.operaciones.kintai.password-complete', ['shopCd' => $this->shop($request)]);
    }

    public function edit(Request $request): View
    {
        $shopCd = $this->shop($request);
        $config = KintaiConfig::query()->where('tb_shop_cd', $shopCd)->orderByDesc('cd')->first() ?? KintaiConfig::forShop($shopCd);
        [$yy, $mm] = KintaiService::requestDate($request->query('yy'), $request->query('mm'), $request->query('dd'));

        return view('admin.operaciones.kintai.config', [
            'shopCd' => $shopCd,
            'shops' => Shop::query()->orderBy('cd')->pluck('kaishamei', 'cd'),
            'times' => [
                'kijun' => KintaiConfig::splitTime($config->kijun_time),
                'start' => KintaiConfig::splitTime($config->start_time),
                'end' => KintaiConfig::splitTime($config->end_time),
            ],
            'staff' => KintaiService::staffOptions($shopCd),
            'yy' => $yy, 'mm' => $mm,
            'thumb' => (string) $config->pic_name !== '' ? KintaiBackground::thumbSize($shopCd) : null,
            'error' => (string) session('kintaiConfigError', ''),
        ]);
    }

    /** 登録: horas (si las seis son dígitos) e imagen; vuelve a la pantalla (el legacy la volvía a pintar) */
    public function update(Request $request, KintaiBackground $background): RedirectResponse
    {
        $shopCd = $this->shop($request);
        $config = KintaiConfig::query()->where('tb_shop_cd', $shopCd)->orderByDesc('cd')->first() ?? KintaiConfig::forShop($shopCd);

        $keys = ['kijun_time_h', 'kijun_time_m', 'start_time_h', 'start_time_m', 'end_time_h', 'end_time_m'];
        $values = array_map(fn ($key) => $request->input($key), $keys);
        if (KintaiService::digits(...$values)) {
            $config->update([
                'kijun_time' => $values[0].'<>'.$values[1],
                'start_time' => $values[2].'<>'.$values[3],
                'end_time' => $values[4].'<>'.$values[5],
            ]);
        }

        $error = '';
        $file = $request->file('upfile');
        if ($file !== null && ! is_array($file)) {
            $ext = $file->isValid() ? KintaiBackground::extension($file) : null;
            if (! $file->isValid()) {
                $error = '※画像ファイルをアップロード出来ません。<br />';
            } elseif ($ext === null) {
                $error = '※jpg,bmp,gif,png以外の画像ファイルはアップロード出来ません。<br />';
            } elseif (($picName = $background->store($shopCd, $file, $ext)) !== null) {
                $config->update(['pic_name' => $picName]);
            } else {
                $error = '※画像ファイルをアップロード出来ません。<br />';
            }
        }

        return to_route('admin.operaciones.kintai.config.edit', ['shop' => $shopCd])->with('kintaiConfigError', $error);
    }

    private function passwordForm(Request $request, string $error = ''): View
    {
        return view('admin.operaciones.kintai.password', [
            'shopCd' => $this->shop($request),
            'shops' => Shop::query()->orderBy('cd')->pluck('kaishamei', 'cd'),
            'error' => $error,
            'passBefore' => (string) $request->input('passBefore', ''),
            'passAfter' => (string) $request->input('passAfter', ''),
        ]);
    }

    private function shop(Request $request): int
    {
        return KintaiService::shopFor(auth('admin')->user(), $request->input('shop'));
    }

    private static function alnum(string $value): bool
    {
        return preg_match('/^[a-zA-Z0-9]+$/', $value) === 1;
    }
}
