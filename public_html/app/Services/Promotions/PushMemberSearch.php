<?php

namespace App\Services\Promotions;

use Illuminate\Support\Facades\DB;

/**
 * ajaxSearchMember.php: socios con la app (registration_id no vacío) para la prueba de un push.
 * Cada palabra (separadas por espacio, también el de ancho completo) debe aparecer en el nombre, la カナ
 * (convertida a カタカナ), el ID, el teléfono o el correo (en ancho medio), el cumpleaños, el 管理番号 o el móvil.
 * Máximo 200, del más nuevo al más antiguo. El legacy concatenaba las palabras en el SQL; aquí van como parámetros.
 */
class PushMemberSearch
{
    public const NO_DATA = 'データがありません。';

    /** @return list<object{cd: int, name: ?string, name_kana: ?string, id: ?string, kanri_id: ?string}> */
    public function search(string $words): array
    {
        $query = DB::table('tb_customer')
            ->where('disp_flg', '1')
            ->where('touroku_kubun', '2')
            ->whereRaw("COALESCE(registration_id, '') NOT IN ('[]', '')");

        foreach (explode(' ', str_replace('　', ' ', $words)) as $word) {
            if ($word === '') {
                continue;
            }
            $kana = '%'.mb_convert_kana($word, 'KVC').'%';
            $half = '%'.mb_convert_kana($word, 'a').'%';
            $like = '%'.$word.'%';
            $query->where(fn ($q) => $q->where('name', 'like', $like)
                ->orWhere('name_kana', 'like', $kana)
                ->orWhere('id', 'like', $like)
                ->orWhere('tel', 'like', $half)
                ->orWhere('email1', 'like', $half)
                ->orWhere('kojin_birth', 'like', $like)
                ->orWhere('kanri_id', 'like', $like)
                ->orWhere('kojin_mobile', 'like', $like));
        }

        return $query->orderByDesc('cd')->limit(200)->get(['cd', 'name', 'name_kana', 'id', 'kanri_id'])->all();
    }
}
