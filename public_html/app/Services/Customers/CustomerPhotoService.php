<?php

namespace App\Services\Customers;

use App\Models\Customers\Customer;
use App\Models\Customers\CustomerPhoto;
use App\Support\LegacyText;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * マイアルバム de la ficha (pestaña 7): alta y modificación (`inc/photoalbum_new.php`, `photoalbum_update.php`,
 * comprobaciones de `photoalbum_frm.php` y escritura de `photoalbum_register.php`) y borrado lógico
 * (`photoalbum_delete.php`) sobre `tb_photo_album` (numeración `hatuban`). Los archivos, en CustomerPhotoImageStore.
 */
class CustomerPhotoService
{
    /** 画像枚数 (fijo en el legacy) */
    public const PIC_COUNT = 1;

    public function __construct(private readonly CustomerPhotoImageStore $images) {}

    /** Entrada del álbum del cliente (sin filtrar disp_flg, como el legacy) o null */
    public function find(Customer $customer, int $cd): ?CustomerPhoto
    {
        return CustomerPhoto::withDeleted()->where('cd', $cd)->where('tb_customer_cd', $customer->cd)->first();
    }

    /** Valores iniciales del formulario: alta = fecha de hoy; modificación = los de la fila */
    public function formData(?CustomerPhoto $photo): array
    {
        if ($photo === null) {
            return ['hiduke' => Carbon::now('Asia/Tokyo')->format('Y/m/d'), 'comment' => ''];
        }

        return [
            'hiduke' => is_numeric($photo->disp_date) ? Carbon::createFromTimestamp((int) $photo->disp_date, 'Asia/Tokyo')->format('Y/m/d') : '',
            'comment' => (string) $photo->comment,
        ];
    }

    /** 画像フラグ de la fila (`pic_flg` separado por '<>'); alta = vacío */
    public function picFlags(?CustomerPhoto $photo): array
    {
        return $photo ? explode('<>', (string) $photo->pic_flg) : [];
    }

    /**
     * Normaliza lo enviado como el legacy: get_form() en los textos y pic_name[] solo si su temporal existe.
     *
     * @return array{hiduke: string, comment: string, pic_name: array<int, string>}
     */
    public function input(array $request): array
    {
        $picNames = [];
        for ($x = 0; $x < self::PIC_COUNT; $x++) {
            $name = $request['pic_name'][$x] ?? '';
            $picNames[$x] = is_string($name) && $this->images->tempExists($name) ? $name : '';
        }

        return [
            'hiduke' => LegacyText::form($request['hiduke'] ?? ''),
            'comment' => LegacyText::form($request['comment'] ?? ''),
            'pic_name' => $picNames,
        ];
    }

    /** Comprobaciones de photoalbum_frm.php: [campo => mensaje] ('pic_name.0', 'hiduke') */
    public function validate(array $input, array $picFlags): array
    {
        $errors = [];
        if ($input['pic_name'][0] === '' && ($picFlags[0] ?? '') === '') {
            $errors['pic_name.0'] = '※画像は必須です。';
        }

        $hiduke = $input['hiduke'];
        if ($hiduke === '') {
            $errors['hiduke'] = '※必須入力項目です。';
        } elseif ($this->date($hiduke) === null) {
            $errors['hiduke'] = '※' . $hiduke . 'は不適切な形式です。';
        }

        return $errors;
    }

    /** photoalbum_register.php: crea (photo null) o actualiza la fila y mueve las fotos subidas. Devuelve el cd */
    public function save(Customer $customer, ?CustomerPhoto $photo, array $input): int
    {
        $picFlags = $this->picFlags($photo);
        $picFlgForDb = [];
        foreach ($input['pic_name'] as $x => $name) {
            // $picFlags: lo que ya hay en la BD; $picFlgForDb: lo que se guarda ahora
            $picFlgForDb[] = $name === '' ? ($picFlags[$x] ?? '') : '1';
        }

        $cd = DB::transaction(function () use ($customer, $photo, $input, $picFlgForDb) {
            $photo ??= (new CustomerPhoto)->forceFill(['disp_flg' => '1', 'hyouji_flg' => '1']);
            $photo->forceFill([
                'tb_customer_cd' => $customer->cd,
                'comment' => $input['comment'],
                'disp_date' => (string) $this->date($input['hiduke'])->getTimestamp(),
                // El legacy hace implode() sin separador (con un solo hueco queda '1' o '')
                'pic_flg' => implode('', $picFlgForDb),
            ])->save();

            return (int) $photo->cd;
        });

        foreach ($input['pic_name'] as $x => $name) {
            if ($name !== '') {
                $this->images->moveToAlbum($name, $cd, $x);
            }
        }

        return $cd;
    }

    /** photoalbum_delete.php: disp_flg = '9' (sin tocar update_date ni los archivos, como el legacy) */
    public function delete(Customer $customer, int $cd): void
    {
        CustomerPhoto::withDeleted()->where('cd', $cd)->where('tb_customer_cd', $customer->cd)
            ->toBase()->update(['disp_flg' => '9']);
    }

    /** 'Y/m/d' válida (checkdate) como medianoche de Tokio, o null */
    private function date(string $hiduke): ?Carbon
    {
        $parts = explode('/', $hiduke);
        if (count($parts) !== 3 || ! ctype_digit($parts[0]) || ! ctype_digit($parts[1]) || ! ctype_digit($parts[2])
            || ! checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            return null;
        }

        return Carbon::create((int) $parts[0], (int) $parts[1], (int) $parts[2], 0, 0, 0, 'Asia/Tokyo');
    }
}
