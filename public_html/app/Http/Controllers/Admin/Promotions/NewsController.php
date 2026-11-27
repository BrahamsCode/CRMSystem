<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Models\Promotions\DigitalSignageNews;
use App\Services\Promotions\DigitalSignageNewsStore;
use App\Support\LegacyText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 新着情報 (M9): category3/digital_signage/digital_signage_entry.php, digital_signage_register.php,
 * inc/upload.php e inc/disp_pic.php. Como el legacy: el listado muestra los 10 últimos por `update`,
 * el formulario solo aparece con ?cd= (no hay alta: el INSERT estaba comentado) y guardar cambia la
 * タイトル, `update` y disp_flg = '1', más el archivo si se subió uno.
 *
 * Diferencias: el campo タイトル sale relleno (el legacy leía una variable aún sin definir); 削除 borra
 * de verdad la fila de tb_digital_signage (disp_flg = '9'): el legacy enviaba a hp/…/whatsnew_delete_register.php,
 * fuera de alcance y de otra tabla; sin Flash, la vista previa es un enlace al archivo.
 */
class NewsController extends Controller
{
    public function __construct(private readonly DigitalSignageNewsStore $files) {}

    public function index(Request $request): View
    {
        $news = DigitalSignageNews::query()->orderByDesc('update')->limit(10)->get();
        $editing = $request->filled('cd') ? DigitalSignageNews::query()->find((int) $request->input('cd')) : null;

        return view('admin.promocion.digital_signage.digital_signage_entry', [
            'news' => $news,
            'editing' => $editing,
            'requestedCd' => $request->filled('cd'),
            'picName' => (string) $request->input('pic_name', ''),
            'previews' => $news->mapWithKeys(fn ($row) => [$row->cd => $this->files->previewUrl((int) $row->cd)]),
        ]);
    }

    public function store(Request $request): View
    {
        $row = $request->input('act') === 'up' ? DigitalSignageNews::query()->find((int) $request->input('cd')) : null;

        if ($row) {
            $row->forceFill([
                'title' => LegacyText::db($request->input('title')),
                'update' => (string) now()->getTimestamp(),
                'disp_flg' => '1',
            ])->save();

            $pic = (string) $request->input('pic_name', '');
            if ($pic !== '') {
                $this->files->publish($pic, (int) $row->cd);
            }
        }

        return view('admin.promocion.digital_signage.digital_signage_register');
    }

    public function destroy(Request $request): RedirectResponse
    {
        DigitalSignageNews::query()->find((int) $request->input('cd'))?->delete();

        return redirect()->route('admin.promocion.news.index');
    }

    /** inc/upload.php (AjaxUpload): "success" / "error" */
    public function upload(Request $request): Response
    {
        $file = $request->file('userfile');
        $ok = $file && $this->files->storeTemp($file, (string) $request->input('ext'), (string) $request->input('time'),
            $request->input('lastTime'), $request->input('lastExt'));

        return response($ok ? 'success' : 'error');
    }

    /** inc/disp_pic.php: el legacy incrustaba el .swf con Flash; aquí, un enlace al archivo subido */
    public function picture(Request $request): Response
    {
        $url = $this->files->tempUrl((string) $request->input('t'));

        return response($url === null ? '' : '<a href="'.e($url).'" target="_blank">'.e(basename($url)).'</a><br />');
    }
}
