<?php

namespace App\Services\Terminales;

use App\Models\Terminales\PaperCard;
use App\Support\LegacyText;
use Illuminate\Support\Facades\DB;

/**
 * ペーパーサイネージ: altas, ediciones, 稼働区分 y borrado (category7/card_signage/control.php + cUrl.php).
 *
 * control.php respondía texto plano a jQuery .load(); los mismos textos están aquí como constantes.
 * Texto guardado como el legacy:
 * - title e id: form2Db() de inc/db.php = mb_convert_kana('KV') + recorte de espacios 半角/全角 (el
 *   pg_escape_string solo escapaba para el SQL). Ni LegacyText::form() (quita comillas) ni input()
 *   (aplica entidades) dan ese texto, así que se usa LegacyText::trim() + mb_convert_kana().
 * - url: htmlspecialchars($_POST['url'], ENT_QUOTES) antes de form2Db (sin recorte): se reproduce igual.
 * El SQL del legacy concatenaba esos valores (inyección SQL por title e id): aquí va por Eloquent.
 */
class PaperCardService
{
    public const OK = '成功';

    public const IN_USE = 'ERROR:既に使用されています。';

    public const EMPTY_NEW = '入力項目が空白です。';

    public const EMPTY_UPDATE = 'ERROR:入力項目が空白です。';

    public const BAD_ID = 'アルファベット・数字・ハイフン・アンダーバーのみが使用できます。';

    public const FREE = '取得可能です。';

    public const NEED_VALUE = '値を入力してください。';

    public const DELETED = '削除しました。';

    public const CHANGED = '変更しました。';

    public const RELOAD = '<script type="text/javascript">location.reload();</script>';

    /** Respuesta de control.php según `type` (new, update, check, delete, hyouji_switch) */
    public function handle(array $input): string
    {
        return match ($input['type'] ?? null) {
            'new' => $this->create($input),
            'update' => $this->update($input),
            'check' => $this->check($input),
            'delete' => $this->delete($input),
            'hyouji_switch' => $this->switchRunning($input),
            default => '',
        };
    }

    /** cUrl::isFree(): ninguna tarjeta vigente (disp_flg = '1') con ese id */
    public function isFree(string $id): bool
    {
        return ! PaperCard::query()->where('disp_flg', '1')->where('id', $id)->exists();
    }

    /** cUrl::postIdWordCheck() */
    public function validId(string $id): bool
    {
        return preg_match('/^[a-zA-Z0-9_\-]+$/', $id) === 1;
    }

    private function create(array $input): string
    {
        if (! filled($input['url'] ?? null)) {
            return self::EMPTY_NEW;
        }

        $id = $this->text($input['id'] ?? '');
        // El legacy no validaba el id del alta (la pantalla no lo envía; solo llegaba por POST a mano)
        if ($id !== '' && ! $this->validId($id)) {
            return self::BAD_ID;
        }

        return DB::transaction(function () use ($id, $input) {
            if (! $this->isFree($id)) {
                return self::IN_USE;
            }

            $card = new PaperCard([
                'url' => $this->url($input['url']),
                'title' => $this->text($input['title'] ?? ''),
                'disp_flg' => '1',
                'hyouji_flg' => PaperCard::RUNNING,
            ]);
            $card->save(); // hatuban asigna cd
            $card->id = $id !== '' ? $id : PaperCard::autoId($card->cd);
            $card->save();

            return self::OK;
        });
    }

    /**
     * cUrl::update() volvía a poner hyouji_flg = '1' y disp_flg = '1' en cada edición (una tarjeta 停止
     * se reactivaba al guardar): aquí solo cambia id, url y título.
     */
    private function update(array $input): string
    {
        $id = $this->text($input['id'] ?? '');
        if (! ($input['cd'] ?? null) || $id === '' || ! filled($input['url'] ?? null)) {
            return self::EMPTY_UPDATE;
        }
        if (! $this->validId($id)) {
            return self::BAD_ID;
        }

        return DB::transaction(function () use ($id, $input) {
            $card = PaperCard::withDeleted()->find((int) $input['cd']);
            if (! $card || ((string) $card->id !== $id && ! $this->isFree($id))) {
                return self::IN_USE;
            }

            $card->fill(['id' => $id, 'url' => $this->url($input['url']), 'title' => $this->text($input['title'] ?? '')])->save();

            return self::OK.$this->reload($input);
        });
    }

    private function check(array $input): string
    {
        $id = $this->text($input['id'] ?? '');
        if ($id === '') {
            return self::NEED_VALUE;
        }
        if (! $this->validId($id)) {
            return self::BAD_ID;
        }
        if ($this->isFree($id)) {
            return self::FREE;
        }

        // getUrlFromId(): la url ya está guardada con entidades, se imprime tal cual como el legacy
        $url = (string) PaperCard::withDeleted()->where('id', $id)->value('url');

        return '既に使用されています。-- ID:「'.e($id).'」のURLは「'.$url.'」です。';
    }

    private function delete(array $input): string
    {
        if (! ($input['cd'] ?? null)) {
            return 'ERROR';
        }

        PaperCard::query()->find((int) $input['cd'])?->delete();

        return self::DELETED.$this->reload($input);
    }

    /** cUrl::hyoujiOff(): 稼働区分 '1' 運用 / '9' 停止 (el legacy aceptaba cualquier valor) */
    private function switchRunning(array $input): string
    {
        $flag = (string) ($input['hyouji_flg'] ?? '');
        if (! ($input['cd'] ?? null) || ! in_array($flag, [PaperCard::RUNNING, PaperCard::STOPPED], true)) {
            return 'ERROR2';
        }

        PaperCard::withDeleted()->whereKey((int) $input['cd'])->update(['hyouji_flg' => $flag]);

        return self::CHANGED.$this->reload($input);
    }

    private function reload(array $input): string
    {
        return ($input['returnfunc'] ?? null) == '1' ? self::RELOAD : '';
    }

    /** form2Db(): 半角カナ → 全角 y recorte de espacios 半角/全角 */
    private function text(mixed $value): string
    {
        return is_scalar($value) ? mb_convert_kana(LegacyText::trim((string) $value), 'KV') : '';
    }

    /** $post_url = htmlspecialchars($_POST['url'], ENT_QUOTES) */
    private function url(mixed $value): string
    {
        return htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES);
    }
}
