<?php

namespace App\Services\Customers;

use App\Models\Customers\CustomCategory;
use App\Models\Customers\CustomField;
use App\Services\Nucleo\LegacyUi;
use Illuminate\Support\Facades\DB;

/**
 * 表示・検索・CSV項目設定 (legacy `customer/form_config.php` + `form_config_register.php`):
 * matriz de los campos fijos del cliente (`tb_koumoku`, `tb_name = 'tb_customer'`) y de los campos
 * de las categorías adicionales de un solo registro (`tb_search_form`).
 */
class FieldSettingService
{
    public function __construct(private LegacyUi $legacyUi) {}

    /** Variables de la vista admin.clientes.field-settings.index */
    public function viewData(): array
    {
        // TODO(S1): tb_koumoku no tiene modelo todavía (tabla del núcleo compartida por varios módulos)
        $items = DB::table('tb_koumoku')->where('tb_name', 'tb_customer')->orderBy('rank')->orderBy('cd')->get();

        // Categorías visibles de un solo registro (hyouji_flg = '1', record_type vacío), como el legacy
        $categories = CustomCategory::forCustomers()
            ->where('disp_flg', '<>', '9')
            ->where('hyouji_flg', '1')
            ->whereRaw("COALESCE(record_type, '') = ''")
            ->orderBy('rank')
            ->get();
        $fields = CustomField::whereIn('tb_search_cate_cd', $categories->pluck('cd'))
            ->where('disp_flg', '<>', '9')
            ->orderBy('rank')
            ->get()
            ->groupBy('tb_search_cate_cd');

        return [
            'siteName' => $this->legacyUi->siteName(),
            'koumokuItems' => $items,
            'applicationUseFlg' => (int) $this->legacyUi->adminMaster()->application_use_flg === 1,
            'customCategories' => $categories->map(fn (CustomCategory $c) => [
                'category' => $c,
                'fields' => $fields->get($c->cd, collect())->values(),
            ]),
        ];
    }

    /**
     * form_config_register.php: item_cd1_N…item_cd4_N → koumoku_disp, koumoku_check, mail_mag_select, koumoku_select
     * (N = 1…item_max_no) y tb_search_form_{disp,hissu,search,csv}_N → hyouji_flg, hissu, serach_flg, csv_flg
     * (N = 0…send_search_form_max - 1). Una casilla sin marcar llega vacía y guarda ''.
     */
    public function update(array $input): void
    {
        $value = fn (string $key) => is_scalar($input[$key] ?? null) ? (string) $input[$key] : '';
        $customFieldCds = CustomField::whereIn('tb_search_cate_cd', CustomCategory::forCustomers()->select('cd'))->pluck('cd')->map(fn ($cd) => (int) $cd)->all();

        DB::transaction(function () use ($input, $value, $customFieldCds) {
            $itemMax = (int) ($input['item_max_no'] ?? 0);
            for ($no = 1; $no <= $itemMax; $no++) {
                // TODO(S1): tb_koumoku sin modelo
                DB::table('tb_koumoku')
                    ->where('tb_name', 'tb_customer')
                    ->where('cd', (int) $value("item_cd_{$no}"))
                    ->update([
                        'koumoku_disp' => $value("item_cd1_{$no}"),
                        'koumoku_check' => $value("item_cd2_{$no}"),
                        'mail_mag_select' => $value("item_cd3_{$no}"),
                        'koumoku_select' => $value("item_cd4_{$no}"),
                    ]);
            }

            $formMax = (int) ($input['send_search_form_max'] ?? 0);
            for ($row = 0; $row < $formMax; $row++) {
                $cd = (int) $value("tb_search_form_cd_{$row}");
                // Solo campos de categorías de clientes (el legacy no lo comprobaba)
                if (! in_array($cd, $customFieldCds, true)) {
                    continue;
                }
                CustomField::where('cd', $cd)->toBase()->update([
                    'hyouji_flg' => $value("tb_search_form_disp_{$row}"),
                    'hissu' => $value("tb_search_form_hissu_{$row}"),
                    'serach_flg' => $value("tb_search_form_search_{$row}"),
                    'csv_flg' => $value("tb_search_form_csv_{$row}"),
                ]);
            }
        });
    }
}
