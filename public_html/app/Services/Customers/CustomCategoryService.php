<?php

namespace App\Services\Customers;

use App\Models\Customers\CustomCategory;
use App\Models\Customers\CustomerSub;
use App\Models\Customers\CustomField;
use App\Support\LegacyText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * 顧客追加情報カテゴリ設定 (legacy `customer/search_cate_*.php`) sobre `tb_search_cate` con `category = 'category1'`.
 * Texto con get_form2() (LegacyText::input) como `search_cate_new_check.php`: la vista imprime name/comment sin escapar.
 */
class CustomCategoryService
{
    public const CATEGORY = 'category1';

    public function __construct(private LegacyRankSorter $sorter) {}

    /** Filas vivas de clientes (el legacy filtra `disp_flg <> '9'`) */
    public function scope(): Builder
    {
        return CustomCategory::forCustomers()->where('disp_flg', '<>', '9');
    }

    /**
     * Página de la lista (ORDER BY rank LIMIT kensu OFFSET (page - 1) * kensu).
     *
     * @return array{items: \Illuminate\Support\Collection, total: int, page: int, kensu: int, from: int, maxPage: int}
     */
    public function paginate(int $page, int $kensu): array
    {
        $kensu = max(1, $kensu);
        $page = max(1, $page);
        $total = $this->scope()->count();
        $from = ($page - 1) * $kensu;

        return [
            'items' => $this->scope()->orderBy('rank')->offset($from)->limit($kensu)->get(),
            'total' => $total,
            'page' => $page,
            'kensu' => $kensu,
            'from' => $from,
            'maxPage' => (int) ceil($total / $kensu),
        ];
    }

    /** チェックした情報を更新する: serach_flgN / hyouji_flgN de cada fila cdN (N = 0…update_max - 1) */
    public function updateFlags(array $input): void
    {
        $value = fn (string $key) => LegacyText::input($input[$key] ?? '');
        $max = (int) ($input['update_max'] ?? 0);

        DB::transaction(function () use ($value, $max) {
            for ($row = 0; $row < $max; $row++) {
                $this->scope()->where('cd', (int) $value("cd{$row}"))->toBase()->update([
                    'serach_flg' => $value("serach_flg{$row}"),
                    'hyouji_flg' => $value("hyouji_flg{$row}"),
                ]);
            }
        });
    }

    /** ▲▼ (thisCd, thisRow = rank actual, kind = up|down) */
    public function move(int $thisCd, int $thisRow, string $kind): void
    {
        $this->sorter->move(fn () => $this->scope(), $thisCd, $thisRow, $kind);
    }

    /** search_cate_new_up.php: cd por hatuban, rank = máximo de la tabla + 1 */
    public function create(array $input): CustomCategory
    {
        return DB::transaction(fn () => CustomCategory::create($this->attributes($input) + [
            'category' => self::CATEGORY,
            'rank' => $this->nextRank(),
            'disp_flg' => '1',
        ]));
    }

    /**
     * search_cate_update_up.php. Como el legacy, el mismo `$select` del alta: la categoría editada pasa al
     * final (rank = máximo + 1) y vuelve a disp_flg = '1'.
     */
    public function update(CustomCategory $category, array $input): void
    {
        DB::transaction(function () use ($category, $input) {
            $category->fill($this->attributes($input) + [
                'category' => self::CATEGORY,
                'rank' => $this->nextRank(),
                'disp_flg' => '1',
            ])->save();
        });
    }

    /** search_cate_delete_up.php: disp_flg = 9 en sus valores (tb_customer_sub), sus campos y la categoría; luego renumera */
    public function delete(CustomCategory $category): void
    {
        DB::transaction(function () use ($category) {
            CustomerSub::where('tb_search_cate_cd', $category->cd)->toBase()->update(['disp_flg' => '9']);
            CustomField::where('tb_search_cate_cd', $category->cd)->toBase()->update(['disp_flg' => '9']);
            $category->delete();
            $this->sorter->renumber(fn () => $this->scope());
        });
    }

    /** Columnas del formulario, con get_form2() */
    private function attributes(array $input): array
    {
        return [
            'name' => LegacyText::input($input['name'] ?? ''),
            'comment' => LegacyText::input($input['comment'] ?? ''),
            'disp_column' => LegacyText::input($input['disp_column'] ?? ''),
            'record_type' => LegacyText::input($input['record_type'] ?? ''),
        ];
    }

    /** (select coalesce(max(rank),0)+1 from tb_search_cate): toda la tabla, como el legacy */
    private function nextRank(): int
    {
        return (int) DB::table('tb_search_cate')->max('rank') + 1;
    }
}
