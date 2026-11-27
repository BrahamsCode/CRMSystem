<?php

namespace App\Services\Senalizacion;

use App\Models\Senalizacion\SignageWebContent;
use App\Models\Senalizacion\SignageWebContentCategory;
use App\Models\Senalizacion\SignageWebFile;
use App\Models\Senalizacion\SignageWebModel;
use App\Models\Senalizacion\SignageWebMovieContent;
use App\Models\Senalizacion\SignageWebMovieContentCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * 画像 / 動画 / ファイル de `signage/` (list/contents*.php, list/movie_contents*.php, list/signage.php).
 * Archivos en el disco public con la ruta del legacy: `signage/upload/<w>x<h>/<cd>.<ext>`,
 * `signage/upload/movie/<w>x<h>/<cd>.<ext>` y `signage/upload/<nombre>` (los sirve /signage/upload/…).
 */
class SignageContentService
{
    public const INVALID = 'ファイル形式が不正です';

    /** Tipo detectado en el servidor → extensión (el legacy validaba el tipo pero guardaba la extensión del cliente) */
    private const KINDS = [
        'image' => [
            'model' => SignageWebContent::class,
            'category' => SignageWebContentCategory::class,
            'column' => 'img_path',
            'dir' => 'upload/',
            'mimes' => ['image/gif' => 'gif', 'image/jpeg' => 'jpg', 'image/png' => 'png'],
        ],
        'video' => [
            'model' => SignageWebMovieContent::class,
            'category' => SignageWebMovieContentCategory::class,
            'column' => 'movie_path',
            'dir' => 'upload/movie/',
            'mimes' => [
                'video/mpeg' => 'mpeg', 'video/mp4' => 'mp4', 'video/webm' => 'webm',
                'video/ogg' => 'ogg', 'video/quicktime' => 'mov', 'video/x-msvideo' => 'avi',
            ],
        ],
    ];

    /** list/signage.php: imágenes y vídeo por extensión; sin `swf` (Flash) */
    public const FILE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'mp4', 'webm', 'mov'];

    /** @return class-string<SignageWebModel> */
    public function model(string $kind): string
    {
        return self::KINDS[$kind]['model'];
    }

    /** @return class-string<SignageWebModel> */
    public function categoryModel(string $kind): string
    {
        return self::KINDS[$kind]['category'];
    }

    public function column(string $kind): string
    {
        return self::KINDS[$kind]['column'];
    }

    /** Ruta en el disco public de un archivo de la categoría */
    public function path(string $kind, SignageWebModel $category, ?string $file): string
    {
        return SignageLegacyView::ROOT.'/'.self::KINDS[$kind]['dir']."{$category->width}x{$category->height}/{$file}";
    }

    /** Extensión según el tipo real del archivo, o null si no está permitido */
    public function extension(string $kind, UploadedFile $file): ?string
    {
        return self::KINDS[$kind]['mimes'][$file->getMimeType()] ?? null;
    }

    /**
     * Alta (en la categoría) o cambio de nombre; con archivo, lo guarda como `<cd>.<ext>` y apunta la ruta.
     * El tipo se comprueba antes de tocar la BD (como el legacy: con tipo inválido no se guarda nada).
     */
    public function save(string $kind, SignageWebModel $category, array $in, ?UploadedFile $file, ?SignageWebModel $content = null): SignageWebModel
    {
        $ext = $file ? $this->extension($kind, $file) : null;
        $model = $this->model($kind);
        $content ??= new $model(['contents_category_cd' => $category->cd, 'flg' => 1]);
        $content->name = $in['name'] ?? null;
        $content->save();

        if ($file && $ext) {
            $column = $this->column($kind);
            $name = "{$content->cd}.{$ext}";
            $old = $content->{$column};
            $file->storeAs(dirname($this->path($kind, $category, $name)), $name, 'public');
            if ($old && $old !== $name) {
                Storage::disk('public')->delete($this->path($kind, $category, $old));
            }
            $content->forceFill([$column => $name])->save();
        }

        return $content;
    }

    /** Borra la fila y su archivo (el legacy dejaba el archivo huérfano) */
    public function delete(string $kind, SignageWebModel $content): void
    {
        $category = $this->categoryModel($kind)::find($content->contents_category_cd);
        $file = $content->{$this->column($kind)};
        $content->delete();
        if ($category && $file) {
            Storage::disk('public')->delete($this->path($kind, $category, $file));
        }
    }

    /** list/signage.php: guarda el archivo con su nombre en signage/upload/ y lo apunta en `color` */
    public function storeFile(UploadedFile $file): ?SignageWebFile
    {
        $name = basename($file->getClientOriginalName());
        if (! in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::FILE_EXTENSIONS, true)) {
            return null;
        }
        $file->storeAs(SignageLegacyView::ROOT.'/upload', $name, 'public');

        return SignageWebFile::create(['flg' => 1, 'color' => $name]);
    }

    /** Borra la fila y el archivo si ninguna otra fila lo usa */
    public function deleteFile(SignageWebFile $row): void
    {
        $row->delete();
        if ($row->color && ! SignageWebFile::where('color', $row->color)->exists()) {
            Storage::disk('public')->delete(SignageLegacyView::ROOT.'/upload/'.basename($row->color));
        }
    }
}
