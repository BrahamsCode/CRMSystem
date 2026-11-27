<?php

namespace App\Services\Customers;

use App\Models\ContentsMaster;
use App\Models\Shop;
use App\Services\Mypage\AppImageResizer;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Imágenes 『ポイントについて』 de ポイント管理 y su endpoint category1/customer/ajax_apliFileUpload.php.
 *
 * Disco `public`, mismas rutas que el legacy:
 * - Temporales de la subida: `system/category1/customer/uploads/temp/<nombre>` (URL `./uploads/temp/` desde customer/).
 * - Guardadas: `hp/uploads/c1/<dir>/<n>.<ext>` y la miniatura `<n>_s.<ext>` (320×240) de sendMainData;
 *   n = 0 全店舗用, 1…N la tienda N-ésima de `tb_shop` activas por cd. URL `/hp/uploads/...` (ruta genérica del núcleo).
 * - `contents_master` (category `tb_c1_point_setting`): `mypage_config` (serialize con comillas dobles → simples,
 *   como el legacy; clave `home_image_flg[shop_cd]` = 全店舗画像を利用する) y `<dir>` (textos `||` por hueco).
 *
 * Diferencias con el legacy: `dir` limitado a las carpetas de la pantalla; el nombre temporal se valida;
 * GD en lugar de Imagick (AppImageResizer: GIF sin animación); el borrado quita también `<n>_s`.
 */
class PointSettingImageStore
{
    public const CATEGORY = 'tb_c1_point_setting';

    public const BASE = 'hp/uploads/c1';

    public const TEMP_DIR = 'system/category1/customer/uploads/temp';

    /** Carpetas que acepta `dir` (la pantalla solo usa point_setting; aplicover es el valor por defecto del legacy) */
    public const DIRS = ['point_setting', 'aplicover'];

    public const DEFAULT_DIR = 'aplicover';

    /** type de subida => [ancho, alto] del recorte (como el legacy) */
    public const UPLOAD_SIZES = [
        'picUpload' => [800, 600],
        'picMainUpload' => [800, 600],
        'picThumbnailUpload' => [400, 400],
        'bannerUpload' => [800, 250],
    ];

    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];

    public const SMALL_SIZE = [320, 240];

    public const TEMP_NAME_PATTERN = '/^\d+_[A-Za-z0-9]+\.(jpg|gif)$/';

    /** Mensajes JSON del legacy (el JS los muestra tal cual) */
    public const MESSAGES = [
        'bad_extension' => '許可されているファイル拡張子ではありません。',
        'create_failed' => 'ファイル生成に失敗しました。',
        'bad_type' => 'TYPEデータが正しくありません。',
        'deleted' => '削除しました。',
    ];

    public function __construct(private readonly AppImageResizer $resizer) {}

    public function disk(): Filesystem
    {
        return Storage::disk('public');
    }

    // --- Pantalla --------------------------------------------------------------------------

    /**
     * Huecos de 『ポイントについて』用画像: 0 = 全店舗用 y uno por tienda activa (orden cd).
     * Cada uno: index, shop (?Shop), picCd (cd de la tienda o 0, como el legacy en pic[] / target), image (URL o null),
     * useAllShopImage (home_image_flg[shop_cd] == 1).
     */
    public function frames(string $dir = 'point_setting'): array
    {
        $shops = Shop::query()->where('disp_flg', '1')->orderBy('cd')->get();
        $files = $this->files($dir);
        $flags = (array) ($this->mypageConfig()['home_image_flg'] ?? []);

        $frames = [];
        for ($i = 0; $i <= $shops->count(); $i++) {
            $shop = $i > 0 ? $shops[$i - 1] : null;
            $path = $files[(string) $i] ?? null;
            $frames[] = [
                'index' => $i,
                'shop' => $shop,
                'picCd' => $shop?->cd ?? 0,
                'image' => $path ? asset($path) . '?t=' . $this->disk()->lastModified($path) : null,
                'useAllShopImage' => $shop !== null && (string) ($flags[$shop->cd] ?? '') === '1',
            ];
        }

        return $frames;
    }

    /** Archivos de la carpeta por nombre sin extensión (`0`, `0_s`, `1`…) */
    private function files(string $dir): array
    {
        $files = [];
        foreach ($this->disk()->files(self::BASE . '/' . $dir) as $path) {
            $files[strstr(basename($path), '.', true) ?: basename($path)] = $path;
        }

        return $files;
    }

    // --- ajax_apliFileUpload.php -----------------------------------------------------------

    /** Devuelve el array del JSON del legacy: {status: ok|ng, imageName | res | result | errMsg} */
    public function handle(string $type, ?UploadedFile $file, array $input): array
    {
        $this->cleanTemp();

        $dir = (string) ($input['dir'] ?? '') !== '' ? (string) $input['dir'] : self::DEFAULT_DIR;
        if (! in_array($dir, self::DIRS, true)) {
            return $this->ng('bad_type');
        }

        if ($file !== null) {
            return $this->upload($type, $file);
        }

        return match ($type) {
            'delMain', 'delThumbnail' => $this->delete($dir, $input['num'] ?? null),
            'sendMainData' => $this->send($dir, true, $input),
            'sendThumbnailData' => $this->send($dir, false, $input),
            // del / bannerdel / sendData / sendBannerData (カバーフロー y バナー) no tienen pantalla en category1
            default => $this->ng('bad_type'),
        };
    }

    /** Subida temporal: recorte al centro W×H (JPEG 90, GIF se queda en GIF) */
    private function upload(string $type, UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, self::EXTENSIONS, true)) {
            return $this->ng('bad_extension');
        }
        if (! isset(self::UPLOAD_SIZES[$type])) {
            return $this->ng('create_failed');
        }

        [$width, $height] = self::UPLOAD_SIZES[$type];
        $output = $this->resizer->crop((string) file_get_contents((string) $file->getRealPath()), $extension, $width, $height);
        if ($output === null) {
            return $this->ng('create_failed');
        }

        $name = time() . '_' . bin2hex(random_bytes(4)) . '.' . ($extension === 'gif' ? 'gif' : 'jpg');
        $this->disk()->put(self::TEMP_DIR . '/' . $name, $output);

        return ['status' => 'ok', 'imageName' => $name];
    }

    /** delMain / delThumbnail: borra `<num>.*` (y su `<num>_s.*`) de la carpeta */
    private function delete(string $dir, mixed $number): array
    {
        if (! is_numeric($number) || (int) $number < 0) {
            return $this->ng('bad_type');
        }
        $this->deleteNumber($dir, (int) $number);

        return ['status' => 'ok', 'res' => self::MESSAGES['deleted']];
    }

    /**
     * sendMainData / sendThumbnailData: pic[n] = temporal → `<n>.<ext>` (+ `<n>_s` 320×240 en la principal);
     * 'def' = 現状保存. Después mezcla mypage_config (claves de primer nivel) y reescribe el campo `<dir>`
     * de contents_master con los textos target_url[n] separados por `||` (huecos 0…9 vacíos por defecto).
     */
    private function send(string $dir, bool $withSmall, array $input): array
    {
        $pics = (array) ($input['pic'] ?? []);
        if ($pics === [] && ! isset($input['mypage_config'])) {
            // El legacy no respondía nada; el JS espera JSON
            return ['status' => 'ok', 'result' => ''];
        }

        $result = '';
        $comments = array_fill(0, 10, '');
        $count = 0;
        foreach ($pics as $key => $value) {
            $n = ++$count;
            $comments[$key] = is_string($input['target_url'][$key] ?? null) ? str_replace('||', '', $input['target_url'][$key]) : '';
            if (! is_numeric($key) || (int) $key < 0 || ! is_string($value)) {
                $result .= "{$n}枚目アップロード失敗。";

                continue;
            }
            if ($value === 'def') {
                $result .= "{$n}枚目は現状保存。";

                continue;
            }
            $contents = $this->readTemp($value);
            if ($contents === null) {
                $result .= "{$n}枚目アップロード失敗。";

                continue;
            }

            $key = (int) $key;
            $extension = strtolower(pathinfo($value, PATHINFO_EXTENSION));
            $this->deleteNumber($dir, $key);
            $this->disk()->put(self::BASE . "/{$dir}/{$key}.{$extension}", $contents);
            if ($withSmall) {
                [$width, $height] = self::SMALL_SIZE;
                $small = $this->resizer->fit($contents, $extension, $width, $height);
                if ($small !== null) {
                    $this->disk()->put(self::BASE . "/{$dir}/{$key}_s.{$extension}", $small);
                }
            }
            $result .= "{$n}枚目アップロード成功。";
        }

        if (isset($input['mypage_config']) && is_array($input['mypage_config'])) {
            $this->mergeMypageConfig($input['mypage_config']);
        }

        // 一旦消す + INSERT
        ContentsMaster::query()->where('category', self::CATEGORY)->where('field', $dir)->delete();
        ContentsMaster::create([
            'category' => self::CATEGORY, 'field' => $dir, 'text_data' => implode('||', $comments),
            'disp_flg' => '1', 'hyouji_flg' => '1',
        ]);

        return ['status' => 'ok', 'result' => $result];
    }

    private function deleteNumber(string $dir, int $number): void
    {
        foreach ($this->disk()->files(self::BASE . '/' . $dir) as $path) {
            $base = strstr(basename($path), '.', true);
            if ($base === (string) $number || $base === $number . '_s') {
                $this->disk()->delete($path);
            }
        }
    }

    // --- Temporales ------------------------------------------------------------------------

    /** Ruta en el disco de un temporal pedido por URL, o null si el nombre no es válido */
    public function tempPath(string $name): ?string
    {
        return preg_match(self::TEMP_NAME_PATTERN, $name) === 1 ? self::TEMP_DIR . '/' . $name : null;
    }

    private function readTemp(string $name): ?string
    {
        $path = $this->tempPath($name);

        return $path !== null && $this->disk()->exists($path) ? $this->disk()->get($path) : null;
    }

    /** Borra los temporales de más de una semana (el legacy lo hacía en cada llamada) */
    public function cleanTemp(): void
    {
        $limit = now()->subWeek()->getTimestamp();
        foreach ($this->disk()->files(self::TEMP_DIR) as $path) {
            if ($this->disk()->lastModified($path) < $limit) {
                $this->disk()->delete($path);
            }
        }
    }

    // --- mypage_config ---------------------------------------------------------------------

    /** `mypage_config` (última fila, ORDER BY cd DESC) deserializado; [] si falta o no se entiende */
    public function mypageConfig(): array
    {
        $raw = $this->configRow()?->text_data;
        if ($raw === null || $raw === '') {
            return [];
        }
        $value = @unserialize(str_replace("'", '"', $raw), ['allowed_classes' => false]);

        return is_array($value) ? $value : [];
    }

    /**
     * Como el legacy (frm y ajax): reemplaza las claves de primer nivel enviadas y guarda con UPDATE de todas las
     * filas del campo; si no hay ninguna, INSERT.
     */
    public function mergeMypageConfig(array $values): void
    {
        $config = $this->mypageConfig();
        foreach ($values as $key => $value) {
            $config[$key] = $value;
        }
        $text = str_replace('"', "'", serialize($config));

        $rows = ContentsMaster::query()->where('category', self::CATEGORY)->where('field', 'mypage_config');
        if ($rows->exists()) {
            $rows->get()->each(fn (ContentsMaster $row) => $row->fill(['text_data' => $text])->save());

            return;
        }

        ContentsMaster::create([
            'category' => self::CATEGORY, 'field' => 'mypage_config', 'text_data' => $text,
            'disp_flg' => '1', 'hyouji_flg' => '1',
        ]);
    }

    private function configRow(): ?ContentsMaster
    {
        return ContentsMaster::query()
            ->where('category', self::CATEGORY)
            ->where('field', 'mypage_config')
            ->orderByDesc('cd')
            ->first();
    }

    private function ng(string $key): array
    {
        return ['status' => 'ng', 'errMsg' => self::MESSAGES[$key]];
    }
}
