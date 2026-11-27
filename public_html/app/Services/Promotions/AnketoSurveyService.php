<?php

namespace App\Services\Promotions;

use App\Models\ContentsMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cabecera de la encuesta (anketo_cate_*): alta y修正 (`anketo_cate_register.php`), 発行/削除/複製/登録
 * (`anketo_cate_action.php`), contadores de 過去アンケート配信 y la encuesta activa de お客様アンケート
 * (`ajax_set_anketo.php`, contents_master `tb_c3_anketo2` / `set_anketo_cd`).
 */
class AnketoSurveyService
{
    /** contents_master de la お客様アンケート activa (la que inserta {アンケートURL[cd]} en los envíos) */
    public const ACTIVE_CATEGORY = 'tb_c3_anketo2';

    public const ACTIVE_FIELD = 'set_anketo_cd';

    /**
     * anketo_cate_register.php. $data ya limpio (get_form). Alta: hatuban y `disp_flg = '1'` (anketo) o `''`
     * (anketo2: queda pendiente hasta «アンケートを登録する»). Edición: mismos campos; anketo2 conserva su disp_flg.
     */
    public function save(AnketoFamily $family, array $data, ?int $cd = null): Model
    {
        $row = [
            'name' => (string) ($data['name'] ?? ''),
            'comment' => (string) ($data['comment'] ?? ''),
            'start_date' => $this->day($data['start_date'] ?? '', '00:00:00'),
            'end_date' => $this->day($data['end_date'] ?? '', '23:59:59'),
        ];

        return DB::transaction(function () use ($family, $row, $cd) {
            if ($cd === null) {
                return $family->cates()->create($row + ['disp_flg' => $family->isCustomer() ? '' : '1']);
            }
            $cate = $family->cates()->withDeleted()->findOrFail($cd);
            $cate->fill($row + ($family->isCustomer() ? [] : ['disp_flg' => '1']))->save();

            return $cate;
        });
    }

    /** anketo_cate_action.php: 発行, 削除 (disp_flg = 9), 登録 (anketo2: disp_flg = 1) y 複製 (cabecera sin fechas + ítems vivos) */
    public function action(AnketoFamily $family, string $act, int $cd): void
    {
        $query = fn () => $family->cates()->withDeleted()->whereKey($cd);

        match ($act) {
            '発行' => $query()->update(['hyouji_flg' => '1']),
            '削除' => $query()->update(['disp_flg' => '9']),
            '登録' => $family->isCustomer() ? $query()->update(['disp_flg' => '1']) : null,
            '複製' => $this->duplicate($family, $cd),
            default => null,
        };
    }

    private function duplicate(AnketoFamily $family, int $cd): void
    {
        $source = $family->cates()->withDeleted()->find($cd);
        if (! $source) {
            return;
        }

        DB::transaction(function () use ($family, $source) {
            // Como el legacy: solo cd, comment, name, disp_flg y touroku_date (sin fechas, sin 発行)
            $copy = $family->cates()->create(['comment' => $source->comment, 'name' => $source->name, 'disp_flg' => '1']);

            $columns = ['rank', 'flds_row', 'name', 'titles', 'data', 'comment', 'form_type', 'hissu', 'unit', 'flds_col', 'size_w', 'size_h', 'style_class'];
            foreach ($family->forms()->where('tb_c3_anketo_cate_cd', $source->cd)->orderBy('cd')->get() as $form) {
                $family->forms()->create(collect($columns)->mapWithKeys(fn ($c) => [$c => $form->getAttribute($c)])->all() + [
                    'tb_c3_anketo_cate_cd' => $copy->cd,
                    'disp_flg' => '1',
                    'hyouji_flg' => '1',
                ]);
            }
        });
    }

    /** 回答数: el ítem con más respuestas (`GROUP BY tb_c3_anketo_form_cd ORDER BY count DESC LIMIT 1`) */
    public function respondents(AnketoFamily $family, int $cateCd): int
    {
        return (int) DB::table($family->subTable())->where('tb_c3_anketo_cate_cd', $cateCd)
            ->groupBy('tb_c3_anketo_form_cd')->selectRaw('count(*) AS count')->orderByDesc('count')->value('count');
    }

    /** El envío de la encuesta (mailmag_master con type_flg = anketo); solo existe para アンケート管理 */
    public function mail(int $cateCd): ?object
    {
        return DB::table('mailmag_master')->where('type_flg', 'anketo')->where('type_data_cd', $cateCd)->first();
    }

    /** contents_master tb_c3_anketo2 / set_anketo_cd */
    public function activeCd(): ?int
    {
        return ContentsMaster::int(self::ACTIVE_CATEGORY, self::ACTIVE_FIELD);
    }

    /**
     * ajax_set_anketo.php: guarda la お客様アンケート elegida (int_data; NULL si no llega un número).
     * Sin fila la crea con hatuban de contents_master (el legacy usaba createHatsuban de la tienda).
     */
    public function setActive(?string $anketoCd): void
    {
        $cd = $anketoCd !== null && preg_match('/^[0-9]+$/', $anketoCd) ? (int) $anketoCd : null;

        DB::transaction(function () use ($cd) {
            $updated = ContentsMaster::query()->entry(self::ACTIVE_CATEGORY, self::ACTIVE_FIELD)->update(['int_data' => $cd]);
            if ($updated === 0) {
                ContentsMaster::create([
                    'category' => self::ACTIVE_CATEGORY, 'field' => self::ACTIVE_FIELD, 'int_data' => (int) $cd,
                    'disp_flg' => '1', 'hyouji_flg' => '1',
                ]);
            }
        });
    }

    /** `Y/m/d` → epoch de ese día a la hora dada (strtotime del legacy, en Asia/Tokyo); vacío = NULL */
    private function day(string $date, string $time): ?int
    {
        if ($date === '') {
            return null;
        }
        [$y, $m, $d] = array_map('intval', array_pad(explode('/', $date), 3, 0));
        [$h, $i, $s] = array_map('intval', explode(':', $time));

        return Carbon::create($y, $m, $d, $h, $i, $s, LegacyTime::ZONE)->getTimestamp();
    }

    /** getMailmagazineName(): お知らせ con la app activa; si no, メルマガ / メール */
    public function toolNames(): array
    {
        $app = (int) \App\Models\AdminMaster::current()?->application_use_flg === 1;

        return ['magazine' => $app ? 'お知らせ' : 'メルマガ', 'mail' => $app ? 'お知らせ' : 'メール'];
    }

    /** epoch → `Y/m/d` (date() del legacy) */
    public static function date(mixed $epoch): string
    {
        return $epoch === null || $epoch === '' ? '' : Carbon::createFromTimestamp((int) $epoch, LegacyTime::ZONE)->format('Y/m/d');
    }
}
