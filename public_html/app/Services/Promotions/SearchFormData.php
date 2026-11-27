<?php

namespace App\Services\Promotions;

use App\Models\Staff;
use Illuminate\Support\Facades\DB;

/**
 * Variables de los parciales de búsqueda de S2 para 発行条件選択.
 * Usa App\Services\Customers\CustomerSearchForm::viewData() de S2 cuando está integrado;
 * hasta entonces da un juego mínimo (tiendas y sesión, sin bloques opcionales) para que la pantalla funcione.
 * La カスタマイズ検索 sale de `category1`, como `$cateFolder` de inc/mail_customer_select_frm.php.
 */
class SearchFormData
{
    public const CATEGORY = 'category1';

    public function __construct(private readonly StaffContext $staff) {}

    public function for(array $filters = []): array
    {
        $form = 'App\\Services\\Customers\\CustomerSearchForm';
        $user = $this->staff->user();
        if (class_exists($form)) {
            return app($form)->viewData($user instanceof Staff ? $user : null, self::CATEGORY, $filters);
        }

        $shops = DB::table('tb_shop')->where('disp_flg', '<>', '9')->orderBy('cd')->get(['cd', 'kaishamei'])
            ->map(fn ($s) => ['cd' => $s->cd, 'kaishamei' => $s->kaishamei, 'name' => $s->kaishamei])->all();

        return [
            'filters' => $filters,
            'hiddenSearchMenus' => [],
            'cellTag' => 'th',
            'isMaster' => $this->staff->isMaster(),
            'shopCd' => $this->staff->shopCd(),
            'shopName' => collect($shops)->firstWhere('cd', $this->staff->shopCd())['kaishamei'] ?? '',
            'shops' => $shops,
            'areaCheckUi' => '',
            'searchItems' => [],
            'jobs' => [],
            'customerGroups' => [],
            'raitenDoukis' => [],
            'terminalNos' => [],
            'staffs' => [],
            'raitenRanks' => [],
            'kingakuRanks' => [],
            'nameInputModeFlg' => false,
            'applicationUseFlg' => false,
            'customSearchCategories' => [],
            'yoyakuMenus' => [],
            'raitenHistoryEnabled' => false,
            'kaikeiResource' => null,
            'minYear' => (int) now(LegacyTime::ZONE)->year,
        ];
    }
}
