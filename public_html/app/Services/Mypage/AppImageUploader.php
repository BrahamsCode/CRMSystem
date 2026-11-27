<?php

namespace App\Services\Mypage;

use Illuminate\Http\UploadedFile;

/**
 * Lógica de system/category6/user_hp/ajax_apliFileUpload.php (subida temporal, borrado y guardado
 * de las imágenes de マイコレ). Devuelve el array que el controlador manda como JSON, con las
 * mismas claves que el legacy: {status: ok|ng, imageName | res | result | errMsg}.
 *
 * Diferencias con el legacy (anotadas en Doc/diseno): la carpeta sale del `type` (el legacy aceptaba
 * cualquier `dir` del request), el nombre temporal se valida y no se puede salir de la carpeta, la
 * カバーフロー y la バナー desplazan archivos, enlaces y banderas juntos al borrar, y la カバーフロー
 * compacta también las banderas al guardar.
 */
class AppImageUploader
{
    /** type de subida => [ancho, alto] del recorte (como el legacy) */
    public const UPLOAD_SIZES = [
        'picUpload' => [800, 600],
        'picMainUpload' => [800, 600],
        'picThumbnailUpload' => [400, 400],
        'bannerUpload' => [800, 250],
    ];

    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];

    /** Miniatura `_s` de la メインイメージ (sendMainData) */
    public const MAIN_SMALL_SIZE = [320, 240];

    public function __construct(
        private readonly AppImageFiles $files,
        private readonly AppImageResizer $resizer,
        private readonly MypageConfig $config,
    ) {}

    public function handle(string $type, ?UploadedFile $file, array $input): array
    {
        $this->files->cleanTemp();

        if ($file !== null) {
            return $this->upload($type, $file);
        }

        return match ($type) {
            'del' => $this->removeListSlot(AppImageFiles::COVER, 'cover', [], (int) ($input['pic'] ?? -1)),
            'bannerdel' => $this->removeListSlot(AppImageFiles::BANNER, 'banner', ['cell_status', 'cell_img'], (int) ($input['pic'] ?? -1)),
            'delMain' => $this->deleteHome(AppImageFiles::HOME_IMAGE, $input['num'] ?? null),
            'delThumbnail' => $this->deleteHome(AppImageFiles::HOME_THUMBNAIL, $input['num'] ?? null),
            'sendData' => $this->sendCover($input),
            'sendBannerData' => $this->sendBanner($input),
            'sendMainData' => $this->sendHome(AppImageFiles::HOME_IMAGE, true, $input),
            'sendThumbnailData' => $this->sendHome(AppImageFiles::HOME_THUMBNAIL, false, $input),
            default => $this->ng('bad_type'),
        };
    }

    // --- Subida temporal ----------------------------------------------------------------------

    private function upload(string $type, UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return $this->ng('bad_extension');
        }
        if (! isset(self::UPLOAD_SIZES[$type])) {
            return $this->ng('create_failed');
        }

        [$width, $height] = self::UPLOAD_SIZES[$type];
        $output = $this->resizer->crop((string) file_get_contents($file->getRealPath()), $extension, $width, $height);
        if ($output === null) {
            return $this->ng('create_failed');
        }

        return ['status' => 'ok', 'imageName' => $this->files->storeTemp($output, $extension === 'gif' ? 'gif' : 'jpg')];
    }

    // --- Borrado ------------------------------------------------------------------------------

    /**
     * Quita el hueco $number de カバーフロー/バナー y sube un puesto los siguientes:
     * archivos, enlaces (`||`) y las claves de mypage_config indicadas.
     */
    private function removeListSlot(string $folder, string $configKey, array $extraKeys, int $number): array
    {
        if ($number < 0) {
            return $this->ng('bad_type');
        }

        $this->files->deleteNumber($folder, $number);
        foreach ($this->files->numbered($folder) as $index => $path) {
            if ($index > $number) {
                $target = $this->files->folderPath($folder) . '/' . ($index - 1) . '.' . $this->files->extension($path);
                $this->files->deleteNumber($folder, $index - 1);
                $this->files->disk()->move($path, $target);
            }
        }

        $links = $this->config->links($folder);
        if ($number < count($links)) {
            array_splice($links, $number, 1);
            $this->config->saveLinks($folder, $links);
        }

        $config = $this->config->mypageConfig();
        foreach (array_merge([$configKey], $extraKeys) as $key) {
            if (is_array($config[$key] ?? null)) {
                $config[$key] = $this->shiftDown($config[$key], $number);
            }
        }
        $this->config->saveMypageConfig($config);

        return ['status' => 'ok', 'res' => $this->text('deleted')];
    }

    /** Claves < $number y la marca -1 quedan; $number se quita; las mayores bajan uno */
    private function shiftDown(array $values, int $number): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            $key = (int) $key;
            if ($key < 0 || $key < $number) {
                $result[$key] = $value;
            } elseif ($key > $number) {
                $result[$key - 1] = $value;
            }
        }
        ksort($result);

        return $result;
    }

    /** delMain / delThumbnail: borra `<shop_cd>.*` y su `_s` */
    private function deleteHome(string $folder, mixed $number): array
    {
        if (! is_numeric($number) || (int) $number < 0) {
            return $this->ng('bad_type');
        }
        $this->files->deleteNumber($folder, (int) $number, true);

        return ['status' => 'ok', 'res' => $this->text('deleted')];
    }

    // --- Guardado -----------------------------------------------------------------------------

    /**
     * sendData (カバーフロー). La app lee las imágenes por orden (api/mpa_getCoverFlow.php), así que
     * quedan numeradas 0..n-1 sin huecos: cada hueco enviado en pic[i] pasa a la siguiente posición
     * libre junto con su enlace target_url[i] y sus banderas mypage_config[cover][i].
     */
    private function sendCover(array $input): array
    {
        $pics = $this->sortedPics($input['pic'] ?? []);
        $existing = $this->files->numbered(AppImageFiles::COVER);
        $result = '';
        $newFiles = [];
        $links = [];
        $submittedFlags = (array) ($input['mypage_config']['cover'] ?? []);
        $coverFlags = isset($submittedFlags[-1]) ? [-1 => $submittedFlags[-1]] : [];

        $position = 0;
        foreach ($pics as $slot => $value) {
            $n = $position + 1;
            if ($value === 'def') {
                if (! isset($existing[$slot])) {
                    // 'def' sin archivo (no lo genera la pantalla): se ignora para no dejar huecos
                    continue;
                }
                $newFiles[$position] = [$this->files->extension($existing[$slot]), $this->files->disk()->get($existing[$slot])];
                $result .= $this->text('kept', $n);
            } else {
                $contents = $this->files->readTemp($value);
                if ($contents === null) {
                    $result .= $this->text('upload_ng', $n);
                    continue;
                }
                $newFiles[$position] = [$this->files->extension($value), $contents];
                $result .= $this->text('upload_ok', $n);
            }
            $links[$position] = (string) ($input['target_url'][$slot] ?? '');
            if (isset($submittedFlags[$slot])) {
                $coverFlags[$position] = $submittedFlags[$slot];
            }
            $position++;
        }

        foreach ($existing as $path) {
            $this->files->disk()->delete($path);
        }
        foreach ($newFiles as $position => [$extension, $contents]) {
            $this->files->write(AppImageFiles::COVER, $position . '.' . $extension, $contents);
        }

        $this->config->saveLinks(AppImageFiles::COVER, $this->padLinks($links));
        $configValues = $input['mypage_config'] ?? [];
        $configValues['cover'] = $coverFlags;
        $this->config->mergeMypageConfig($configValues);

        return ['status' => 'ok', 'result' => $result];
    }

    /** sendBannerData: posiciones fijas; pic[i] nuevo reemplaza `<i>.*` */
    private function sendBanner(array $input): array
    {
        $pics = $this->sortedPics($input['pic'] ?? []);
        $result = '';
        $links = [];
        $count = 0;

        foreach ($pics as $slot => $value) {
            $n = ++$count;
            $links[$slot] = (string) ($input['target_url'][$slot] ?? '');
            if ($value === 'def') {
                $result .= $this->text('kept', $n);

                continue;
            }
            $contents = $this->files->readTemp($value);
            if ($contents === null) {
                $result .= $this->text('upload_ng', $n);

                continue;
            }
            $this->files->deleteNumber(AppImageFiles::BANNER, $slot);
            $this->files->write(AppImageFiles::BANNER, $slot . '.' . $this->files->extension($value), $contents);
            $result .= $this->text('upload_ok', $n);
        }

        $this->config->saveLinks(AppImageFiles::BANNER, $this->padLinks($links));
        $this->config->mergeMypageConfig($input['mypage_config'] ?? []);

        return ['status' => 'ok', 'result' => $result];
    }

    /**
     * sendMainData / sendThumbnailData: pic[<shop_cd>] (0 = 全店舗用) → `<shop_cd>.<ext>`;
     * la メインイメージ crea además `<shop_cd>_s.<ext>` (320×240) que lee la API.
     */
    private function sendHome(string $folder, bool $withSmall, array $input): array
    {
        $pics = $this->sortedPics($input['pic'] ?? []);
        $result = '';
        $count = 0;

        foreach ($pics as $key => $value) {
            $n = ++$count;
            if ($value === 'def') {
                $result .= $this->text('kept', $n);

                continue;
            }
            $contents = $this->files->readTemp($value);
            if ($contents === null) {
                $result .= $this->text('upload_ng', $n);

                continue;
            }
            $extension = $this->files->extension($value);
            $this->files->deleteNumber($folder, $key, $withSmall);
            $this->files->write($folder, $key . '.' . $extension, $contents);
            if ($withSmall) {
                [$width, $height] = self::MAIN_SMALL_SIZE;
                $small = $this->resizer->fit($contents, $extension, $width, $height);
                if ($small !== null) {
                    $this->files->write($folder, $key . '_s.' . $extension, $small);
                }
            }
            $result .= $this->text('upload_ok', $n);
        }

        $this->config->mergeMypageConfig($input['mypage_config'] ?? []);

        return ['status' => 'ok', 'result' => $result];
    }

    /** pic[] con claves enteras >= 0 y valor texto, ordenado por clave */
    private function sortedPics(mixed $pics): array
    {
        $result = [];
        foreach ((array) $pics as $key => $value) {
            if (is_numeric($key) && (int) $key >= 0 && is_string($value) && $value !== '') {
                $result[(int) $key] = $value;
            }
        }
        ksort($result);

        return $result;
    }

    /** Enlaces 0..SLOT_COUNT-1, vacíos donde no hay hueco */
    private function padLinks(array $links): array
    {
        $padded = [];
        for ($i = 0; $i < AppImageScreen::SLOT_COUNT; $i++) {
            $padded[] = str_replace('||', '', $links[$i] ?? '');
        }

        return $padded;
    }

    private function ng(string $key): array
    {
        return ['status' => 'ng', 'errMsg' => $this->text($key)];
    }

    private function text(string $key, ?int $n = null): string
    {
        return __('mypage.app_images.' . $key, $n === null ? [] : ['n' => $n], 'ja');
    }
}
