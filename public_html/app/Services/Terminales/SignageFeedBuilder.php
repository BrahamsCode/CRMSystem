<?php

namespace App\Services\Terminales;

use App\Enums\Terminales\SignageContentMode;
use App\Models\JobPosting;
use App\Models\Shop;
use App\Models\SignageMaterial;
use App\Models\SignageProgram;
use Illuminate\Support\Facades\Schema;

/**
 * Genera los XML que lee el reproductor de señalización digital.
 *
 * Reemplaza los archivos que escribían category7/digital_signage/xml_up.php
 * (hp/uploads/c7/display/<cd>.xml) y xml_info_up.php (floormap.xml). La forma de
 * la salida es la del legacy, espacios dobles incluidos, porque el reproductor
 * la consume tal cual. Diferencia deliberada: los valores se escapan para XML
 * (el legacy los concatenaba sin escapar y un `"` o `&` rompía el archivo).
 */
class SignageFeedBuilder
{
    private const HEADER = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";

    /**
     * XML del programa: un <p> por material en orden de reproducción.
     * Como el legacy, un programa en modo システム内蔵 da un archivo vacío.
     */
    public function program(SignageProgram $program): string
    {
        if ($program->content_mode !== SignageContentMode::Files) {
            return '';
        }

        $xml = self::HEADER."<data>\n";
        foreach ($program->materials()->active()->get() as $material) {
            $xml .= $this->materialLine($material);
        }

        return $xml."</data>\n";
    }

    /** Una línea <p> del programa (misma forma que xml_up.php) */
    public function materialLine(SignageMaterial $material): string
    {
        $images = $material->images ?? [];
        $extensions = implode(',', array_map(fn ($slot) => (string) ($slot['ext'] ?? ''), $images));
        $flags = implode(',', array_map(fn ($slot) => (string) ($slot['flg'] ?? ''), $images));
        $title = $this->e($material->title);

        return '<p cd="'.$material->id.'"'
            .' time="'.$this->e($material->duration_seconds).'"'
            .'  title="'.$title.'"'
            .'  type="'.$this->e($material->file_type?->legacyValue()).'"'
            .' pic_extension="'.$this->e($extensions).'"'
            .' pic_flg="'.$this->e($flags).'">'
            .$title."</p>\n";
    }

    /**
     * floormap.xml desde la BD: tiendas agrupadas por planta con su ficha y el
     * primer 求人 de cada una.
     *
     * Necesita en `shops` las columnas `floor_name`, `service_category` y
     * `company_details` (pedido a S1); mientras no existan devuelve el XML vacío.
     */
    public function floormap(): string
    {
        if (! Schema::hasColumn('shops', 'floor_name')) {
            return $this->renderFloormap([]);
        }

        $floors = [];
        $shops = Shop::query()
            ->active()
            ->whereNotNull('floor_name')
            ->where('floor_name', '<>', '')
            ->orderBy('floor_name')
            ->orderBy('id')
            ->get();

        foreach ($shops as $shop) {
            $floors[$shop->floor_name][] = [
                'cd' => $shop->id,
                'cate' => $shop->service_category,
                'name' => $shop->name,
                'kana' => $shop->name_kana,
                'details' => $this->companyDetails($shop->company_details),
                'posting' => JobPosting::query()->with('category')->where('shop_id', $shop->id)->orderBy('id')->first(),
            ];
        }

        return $this->renderFloormap($floors);
    }

    /**
     * Arma floormap.xml.
     *
     * @param  array<string, list<array{cd: int|string, cate: ?string, name: ?string, kana: ?string, details: list<array{0: string, 1: string}>, posting: ?JobPosting}>>  $floors
     */
    public function renderFloormap(array $floors): string
    {
        $xml = self::HEADER."<data>\n";

        foreach ($floors as $floor => $shops) {
            $xml .= '<map floor="'.$this->e($floor)."\">\n";

            foreach ($shops as $shop) {
                $xml .= '<shop cd="'.$this->e($shop['cd']).'" cate="'.$this->e($shop['cate'])
                    .'" name="'.$this->e($shop['name']).'" kana="'.$this->e($shop['kana'])."\">\n";

                $detail = '';
                foreach ($shop['details'] as [$label, $value]) {
                    if ($label !== '' && $value !== '') {
                        $detail .= "【{$label}】\n{$value}\n\n";
                    }
                }
                if ($detail !== '') {
                    $xml .= '<detail>'.$this->e($detail)."</detail>\n";
                }

                if ($shop['posting']) {
                    $recruit = $this->recruitText($shop['posting']);
                    if ($recruit !== '') {
                        $xml .= '<recruit cd="'.$shop['posting']->id.'">'.$this->e($recruit)."</recruit>\n";
                    }
                }

                $xml .= "</shop>\n";
            }

            $xml .= "</map>\n";
        }

        return $xml."</data>\n";
    }

    /**
     * Texto del 求人 dentro de <recruit>. Mismo orden y separadores que el legacy;
     * corrige 【応募資格】, que el legacy rellenaba con 雇用形態.
     */
    public function recruitText(JobPosting $posting): string
    {
        $text = '';
        if ($posting->headline) {
            $text .= "【募集見出し】\n{$posting->headline}\n";
        }
        $categoryName = $posting->category?->name;
        if ($categoryName) {
            $text .= "【募集職種】\n{$categoryName}\n\n";
        }

        $blocks = [
            '仕事内容' => $posting->description,
            '雇用形態' => $posting->employment_type,
            '応募資格' => $posting->requirements,
            '勤務地' => $posting->work_location,
            '勤務時間' => $posting->work_hours,
            '給与' => $posting->salary,
            '休日・休暇' => $posting->holidays,
            '待遇・福利厚生' => $posting->benefits,
        ];
        foreach ($blocks as $label => $value) {
            if ($value) {
                $text .= "【{$label}】\n{$value}\n\n";
            }
        }

        return $text;
    }

    /**
     * Ficha de la tienda: acepta la lista ya estructurada [[etiqueta, valor], …]
     * o el texto del legacy (kaisha_data: "etiqueta<>valor||etiqueta<>valor").
     *
     * @return list<array{0: string, 1: string}>
     */
    public function companyDetails(mixed $data): array
    {
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = is_array($decoded)
                ? $decoded
                : array_map(fn ($pair) => array_pad(explode('<>', $pair), 2, ''), explode('||', $data));
        }

        return array_values(array_map(
            fn ($pair) => [(string) ($pair[0] ?? ''), (string) ($pair[1] ?? '')],
            is_array($data) ? $data : []
        ));
    }

    private function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
