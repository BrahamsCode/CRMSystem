<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerPhoto;
use App\Services\Customers\CustomerPhotoImageStore;
use App\Services\Customers\CustomerPhotoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * マイアルバム de la pestaña 7 de la ficha: `system/category1/inc/photoalbum_new.php` / `photoalbum_update.php`
 * (formulario `photoalbum_frm.php`), `photoalbum_register.php`, `photoalbum_delete.php`, y la subida
 * AjaxUpload `inc/ajax_picupload.php` (URL legacy que llama `inc/js/pic_upload.js`) con sus temporales.
 */
class CustomerPhotoController extends Controller
{
    public function __construct(private readonly CustomerPhotoService $photos) {}

    /** 「アルバムを追加する」 */
    public function create(Request $request, int $customer): View
    {
        $customer = $this->customer($customer);

        return $this->form($request, $customer, null, $this->photos->formData(null), [], []);
    }

    /** 「アルバムを修正する」 */
    public function edit(Request $request, int $customer, int $photo): View
    {
        $customer = $this->customer($customer);
        $album = $this->album($customer, $photo);

        return $this->form($request, $customer, $album, $this->photos->formData($album), [], []);
    }

    /** 「この内容で登録する」 (alta) */
    public function store(Request $request, int $customer): View
    {
        return $this->register($request, $this->customer($customer), null);
    }

    /** 「この内容で登録する」 (modificación) */
    public function update(Request $request, int $customer, int $photo): View
    {
        $customer = $this->customer($customer);

        return $this->register($request, $customer, $this->album($customer, $photo));
    }

    /** 「アルバムを削除する」: pantalla 「削除中です。」 que vuelve a la ficha */
    public function destroy(Request $request, int $customer, int $photo): View
    {
        $customer = $this->customer($customer);
        $this->photos->delete($customer, $photo);

        return view('admin.clientes.photos.processing', [
            'title' => '顧客登録情報 - ' . config('app.name'),
            'heading' => '顧客登録情報',
            'message' => '削除中です。',
            'seconds' => 2,
            'detailUrl' => route('admin.clientes.show', [$customer->cd, 'kaiin_cd' => $customer->cd, 'tab' => (string) $request->input('tab', '')]),
        ]);
    }

    /**
     * inc/ajax_picupload.php: con `up` guarda la subida (responde el nombre `<temp>.jpg` o `error:<mensaje>`);
     * sin `up` responde el `<img>` de la miniatura de `f`.
     */
    public function upload(Request $request, CustomerPhotoImageStore $images): Response
    {
        if (! $request->has('up')) {
            return response($this->thumbnailTag($request));
        }

        $limit = min(ini_get('post_max_size'), ini_get('upload_max_filesize'));
        $tempName = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $request->input('tempfilename', ''));
        if ($tempName === '') {
            return response("error:エラーが発生しました。\n{$limit}以上のファイルはアップロード出来ません。\nファイルを確認して、もう一度アップロードしてください。");
        }

        $field = (string) $request->input('file', '');
        $file = preg_match('/^picfile_\d{1,2}$/', $field) === 1 ? $request->file($field) : null;
        if ($file === null || ! $file->isValid()) {
            $error = $file && in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? "{$limit}以上のファイルはアップロード出来ません。"
                : 'エラーが発生しました。もう一度アップロードしてください。';

            return response('error:' . $error);
        }

        $name = $images->storeTemp($file, substr($tempName, 0, 100));

        return response($name ?? 'error:エラーが発生しました。もう一度アップロードしてください。');
    }

    /** /system/category1/customer/uploads/<archivo>: temporales de la subida (carpeta pública en el legacy) */
    public function tempImage(string $file, CustomerPhotoImageStore $images): StreamedResponse
    {
        $path = $images->tempPath($file);
        abort_if($path === null || ! Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }

    // ---------------------------------------------------------------- auxiliares

    private function register(Request $request, Customer $customer, ?CustomerPhoto $album): View
    {
        $input = $this->photos->input($request->all());
        $errors = $this->photos->validate($input, $this->photos->picFlags($album));
        if ($errors !== []) {
            return $this->form($request, $customer, $album, $input, $input['pic_name'], $errors);
        }

        // ダブルクリックを防ぐ: el mismo add_record_verify no se registra dos veces
        $verify = (string) $request->input('add_record_verify', '');
        $cd = $album?->cd;
        if ($request->session()->get('add_record_verify') !== $verify) {
            $request->session()->put('add_record_verify', $verify);
            $cd = $this->photos->save($customer, $album, $input);
        }

        $params = [$customer->cd, 'kaiin_cd' => $customer->cd, 'tab' => (string) $request->input('tab', ''), 'from' => 'reg'];
        if ($cd !== null) {
            $params['photoalbum_cd'] = $cd;
        }

        return view('admin.clientes.photos.processing', [
            'title' => 'フォトアルバム ' . ($album ? '更新' : '新規登録'),
            'heading' => 'フォトアルバム ' . ($album ? '更新' : '新規登録'),
            'message' => '登録中です。',
            'seconds' => 1,
            'detailUrl' => route('admin.clientes.show', $params),
        ]);
    }

    /** photoalbum_frm.php */
    private function form(Request $request, Customer $customer, ?CustomerPhoto $album, array $values, array $picNames, array $errors): View
    {
        return view('admin.clientes.photos.form', [
            'customer' => $customer,
            'album' => $album,
            'mode' => $album ? '更新' : '新規登録',
            'values' => $values,
            'picNames' => $picNames,
            'picFlags' => $this->photos->picFlags($album),
            'picCount' => CustomerPhotoService::PIC_COUNT,
            'fieldErrors' => $errors,
            'hidden' => [
                'kaiin_cd' => (string) $customer->cd,
                'tab' => (string) $request->input('tab', ''),
                'page' => (string) $request->input('page', ''),
            ],
            'verifyToken' => (string) time(),
        ]);
    }

    /** `<img [height] src="/system/category1/customer/uploads/<archivo>th.<ext>?t=…" />` */
    private function thumbnailTag(Request $request): string
    {
        [$file, $ext] = array_pad(explode('.', (string) $request->input('f', '')), 2, '');
        $src = '/' . CustomerPhotoImageStore::TEMP_DIR . '/' . $file . 'th.' . $ext . '?t=' . time();
        $height = (string) $request->input('h', '') !== '' ? 'height="' . e((string) $request->input('h')) . '" ' : '';

        return '<img ' . $height . 'src="' . e($src) . '" />';
    }

    private function customer(int $cd): Customer
    {
        $customer = Customer::withDeleted()->find($cd);
        abort_if($customer === null, 404);

        return $customer;
    }

    private function album(Customer $customer, int $cd): CustomerPhoto
    {
        $album = $this->photos->find($customer, $cd);
        abort_if($album === null, 404);

        return $album;
    }
}
