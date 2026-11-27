<?php

namespace App\Services\Terminales;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Genera los XML que lee el reproductor de señalización digital, igual que
 * category7/digital_signage/xml_up.php (hp/uploads/c7/display/<cd>.xml, uno por
 * 放映プログラム) y xml_info_up.php (floormap.xml).
 *
 * Como el legacy, el archivo se escribe al guardar un programa (o la info de
 * tiendas/求人) en el disco public bajo la misma ruta, y lo sirve la redirección
 * genérica /hp/uploads/... de S1: el reproductor sigue comparando la fecha del
 * archivo.
 *
 * La forma de la salida es la del legacy, espacios dobles incluidos. Diferencias
 * deliberadas: los valores se escapan para XML (un `"` o `&` rompía el archivo),
 * un material borrado o inexistente se omite (el legacy emitía un <p> vacío) y
 * 【応募資格】 sale de oubo_shikaku (el legacy repetía koyou_keitai).
 *
 * Lee las tablas con el query builder: los modelos Eloquent de tb_* llegan con
 * las pantallas (Doc/diseno/contenido-terminales.md §2).
 */
class SignageFeedBuilder
{
    /** Carpeta del legacy dentro del disco public */
    public const DIR = 'hp/uploads/c7/display';

    private const HEADER = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";

    /**
     * XML de un programa (tb_c7_movie_program.cd). Con disp_conf distinto de "1"
     * (システム内蔵) el legacy escribía un archivo vacío.
     */
    public function program(int $programCd): string
    {
        $program = DB::table('tb_c7_movie_program')
            ->where('cd', $programCd)
            ->where(fn ($q) => $q->whereNull('disp_flg')->orWhere('disp_flg', '<>', '9'))
            ->first();

        if (! $program || (string) $program->disp_conf !== '1') {
            return '';
        }

        $materialCds = array_values(array_filter(
            explode('<>', (string) $program->tb_screensaver_cds),
            fn ($cd) => ctype_digit($cd)
        ));
        $materials = DB::table('tb_screensaver')
            ->whereIn('cd', $materialCds ?: [0])
            ->where(fn ($q) => $q->whereNull('disp_flg')->orWhere('disp_flg', '<>', '9'))
            ->get()
            ->keyBy(fn ($row) => (string) $row->cd);

        $xml = self::HEADER."<data>\n";
        foreach ($materialCds as $cd) {
            if ($materials->has($cd)) {
                $xml .= $this->materialLine((array) $materials[$cd]);
            }
        }

        return $xml."</data>\n";
    }

    /** Escribe hp/uploads/c7/display/<cd>.xml como xml_up.php */
    public function writeProgram(int $programCd): string
    {
        $path = self::DIR."/{$programCd}.xml";
        Storage::disk('public')->put($path, $this->program($programCd));

        return $path;
    }

    /**
     * Una línea <p> del programa a partir de una fila de tb_screensaver
     * (pic_extension y pic_flg con `<>` pasan a `,`).
     *
     * @param  array<string, mixed>  $row
     */
    public function materialLine(array $row): string
    {
        $title = $this->e($row['title'] ?? '');

        return '<p cd="'.$this->e($row['cd'] ?? '').'"'
            .' time="'.$this->e($row['play_frame_sec'] ?? '').'"'
            .'  title="'.$title.'"'
            .'  type="'.$this->e($row['filetype'] ?? '').'"'
            .' pic_extension="'.$this->e(str_replace('<>', ',', (string) ($row['pic_extension'] ?? ''))).'"'
            .' pic_flg="'.$this->e(str_replace('<>', ',', (string) ($row['pic_flg'] ?? ''))).'">'
            .$title."</p>\n";
    }

    /** floormap.xml desde tb_shop, tb_recruit y tb_recruit_category */
    public function floormap(): string
    {
        $shops = DB::table('tb_shop')
            ->where(fn ($q) => $q->whereNull('disp_flg')->orWhere('disp_flg', '<>', '9'))
            ->whereRaw("COALESCE(floor_name, '') <> ''")
            ->orderBy('floor_name')
            ->orderBy('cd')
            ->get();

        $floors = [];
        foreach ($shops as $shop) {
            // Como el legacy: el primer 求人 vigente de la tienda
            $recruit = DB::table('tb_recruit')
                ->where('tb_shop_cd', $shop->cd)
                ->where(fn ($q) => $q->whereNull('disp_flg')->orWhere('disp_flg', '<>', '9'))
                ->orderBy('cd')
                ->first();
            if ($recruit) {
                $recruit->boshu_shokushu_name = DB::table('tb_recruit_category')
                    ->where('cd', $recruit->boshu_shokushu)
                    ->where('disp_flg', '1')
                    ->value('shokushu');
            }

            $floors[$shop->floor_name][] = [
                'cd' => $shop->cd,
                'cate' => $shop->service_kubun,
                'name' => $shop->kaishamei,
                'kana' => $shop->kaishamei_kana,
                'details' => $this->companyDetails($shop->kaisha_data),
                'recruit' => $recruit ? (array) $recruit : null,
            ];
        }

        return $this->renderFloormap($floors);
    }

    /** Escribe hp/uploads/c7/display/floormap.xml como xml_info_up.php */
    public function writeFloormap(): string
    {
        $path = self::DIR.'/floormap.xml';
        Storage::disk('public')->put($path, $this->floormap());

        return $path;
    }

    /**
     * Arma floormap.xml.
     *
     * @param  array<string, list<array{cd: mixed, cate: ?string, name: ?string, kana: ?string, details: list<array{0: string, 1: string}>, recruit: ?array<string, mixed>}>>  $floors
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

                if ($shop['recruit']) {
                    $text = $this->recruitText($shop['recruit']);
                    if ($text !== '') {
                        $xml .= '<recruit cd="'.$this->e($shop['recruit']['cd']).'">'.$this->e($text)."</recruit>\n";
                    }
                }

                $xml .= "</shop>\n";
            }

            $xml .= "</map>\n";
        }

        return $xml."</data>\n";
    }

    /**
     * Texto de <recruit> a partir de una fila de tb_recruit (+ boshu_shokushu_name).
     * Mismo orden y separadores que xml_info_up.php.
     *
     * @param  array<string, mixed>  $row
     */
    public function recruitText(array $row): string
    {
        $text = '';
        if (! empty($row['boshu_headline'])) {
            $text .= "【募集見出し】\n{$row['boshu_headline']}\n";
        }

        $blocks = [
            '募集職種' => 'boshu_shokushu_name',
            '仕事内容' => 'shigoto_naiyou',
            '雇用形態' => 'koyou_keitai',
            '応募資格' => 'oubo_shikaku',
            '勤務地' => 'kinmu_chi',
            '勤務時間' => 'kinmu_jikan',
            '給与' => 'kyuyo',
            '休日・休暇' => 'kyuka',
            '待遇・福利厚生' => 'taigu',
        ];
        foreach ($blocks as $label => $column) {
            if (! empty($row[$column])) {
                $text .= "【{$label}】\n{$row[$column]}\n\n";
            }
        }

        return $text;
    }

    /**
     * tb_shop.kaisha_data ("etiqueta<>valor||etiqueta<>valor") en pares.
     *
     * @return list<array{0: string, 1: string}>
     */
    public function companyDetails(?string $data): array
    {
        if ($data === null || $data === '') {
            return [];
        }

        return array_map(
            fn ($pair) => array_map('strval', array_slice(array_pad(explode('<>', $pair), 2, ''), 0, 2)),
            explode('||', $data)
        );
    }

    private function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
