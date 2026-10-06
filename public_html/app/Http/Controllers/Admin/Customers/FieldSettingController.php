<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Models\CustomCategory;
use App\Models\FieldSetting;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/** Campos de registro, búsqueda y CSV */
class FieldSettingController extends ModuleController
{
    /** Orden de las cuatro columnas de la matriz, igual que el código del catálogo */
    private const COLUMNAS = ['mobile_display_flg', 'mobile_required_flg', 'search_flg', 'csv_flg'];

    public function index(): View
    {
        $shop = $this->shop();

        $settings = FieldSetting::where('shop_id', $shop?->id)->get()->keyBy('field_name');

        return view('admin.customers.field-settings', [
            'groups' => $this->matriz($settings),
            'familyFields' => $this->matrizFamilia($settings),
            'columns' => config('crm.field_columns'),
            // Las categorías personalizadas traen sus propios campos, que se
            // configuran en «Información adicional», no aquí
            'categories' => CustomCategory::with('fields')
                ->where('shop_id', $shop?->id)
                ->ordered()
                ->get(),
        ]);
    }

    /**
     * Combina el catálogo de campos con la configuración guardada.
     *
     * El catálogo dice qué campos existen y qué columnas aplican a cada uno; la
     * tabla `field_settings` dice cómo están marcados. Sin fila guardada se usa
     * el valor por defecto del propio código.
     */
    private function matriz(Collection $settings): array
    {
        $groups = [];

        foreach (config('crm.customer_fields') as $title => $rows) {
            foreach ($rows as [$id, $label, $codigo]) {
                $cells = [];

                foreach (str_split($codigo) as $i => $k) {
                    $cells[] = match ($k) {
                        'x' => ['type' => 'na'],
                        'R' => ['type' => 'fijo'],
                        default => [
                            'type' => 'check',
                            'key' => "{$id}:{$i}",
                            'on' => (bool) ($settings[$id]->{self::COLUMNAS[$i]} ?? $k === '1'),
                        ],
                    };
                }

                $groups[$title][] = ['id' => $id, 'label' => $label, 'cells' => $cells];
            }
        }

        return $groups;
    }

    /** Información familiar: tres columnas (obligatorio, búsqueda, CSV) */
    private function matrizFamilia(Collection $settings): array
    {
        $columns = ['mobile_required_flg', 'search_flg', 'csv_flg'];

        return collect(config('crm.family_fields'))
            ->map(function (array $field) use ($settings, $columns) {
                [$id, $label, $codigo] = $field;
                $cells = [];

                foreach (str_split($codigo) as $i => $k) {
                    $cells[] = [
                        'key' => "{$id}:{$i}",
                        'on' => (bool) ($settings[$id]->{$columns[$i]} ?? $k === '1'),
                    ];
                }

                return [
                    'id' => $id,
                    'label' => $label,
                    'solo_admin' => true,
                    'cells' => $cells,
                ];
            })
            ->all();
    }
}
