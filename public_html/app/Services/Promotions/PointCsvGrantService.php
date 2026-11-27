<?php

namespace App\Services\Promotions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * ポイント一括付与 (legacy system/category3/point/csv_grant_points.php): CSV SJIS-win `顧客番号,付与ポイント`
 * con cabecera; a cada 顧客番号 (tb_customer.id) activo y registrado (touroku_kubun = 2) le suma los puntos
 * con PointService (personal 1, como el legacy). Mismos mensajes que el legacy.
 *
 * Diferencia con el legacy: todo va en una transacción; si una fila es inválida no se da ningún punto
 * (el legacy dejaba dados los de las filas anteriores).
 */
class PointCsvGrantService
{
    /** Cabecera de la ひな形 (base.csv) */
    public const TEMPLATE = "顧客番号,付与ポイント\n";

    /** tb_staff_cd que el legacy pasa siempre a Point::setPoint */
    public const STAFF_CD = 1;

    public function __construct(private readonly PointService $points) {}

    /**
     * @return array{successIds: list<string>, errorIds: list<string>}
     *
     * @throws RuntimeException con el mensaje del legacy para mostrarlo en pantalla
     */
    public function grant(?UploadedFile $file, int $shopCd): array
    {
        $this->check($file);

        $lines = @file($file->getRealPath());
        if ($lines === false) {
            throw new RuntimeException('不明なエラー発生しました。もう一度アップロードしてください。');
        }

        return DB::transaction(function () use ($lines, $shopCd) {
            $successIds = $errorIds = [];

            // La primera línea es la cabecera
            for ($i = 1; $i < count($lines); $i++) {
                $row = $this->parseLine($lines[$i], $i + 1);
                if ($row === null) {
                    continue;
                }
                [$customerId, $points] = $row;

                $customerCd = DB::table('tb_customer')
                    ->where('id', $customerId)->where('disp_flg', '1')->where('touroku_kubun', '2')
                    ->value('cd');
                if ($customerCd !== null
                    && $this->points->setPoint((int) $points, PointService::ADD, $shopCd, self::STAFF_CD, (int) $customerCd)['status'] === 'ok') {
                    $successIds[] = $customerId;

                    continue;
                }

                // ポイント付与できなかった顧客
                $errorIds[] = $customerId;
            }

            return ['successIds' => $successIds, 'errorIds' => $errorIds];
        });
    }

    /** Errores del archivo, en el orden del legacy */
    private function check(?UploadedFile $file): void
    {
        if ($file === null || $file->getClientOriginalName() === '') {
            throw new RuntimeException('ファイルを選択して下さい。');
        }
        if (preg_match('/^image\//', (string) $file->getClientMimeType())) {
            throw new RuntimeException('ファイルの形式が異常です。');
        }
        if (! $file->isValid()) {
            if (in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                throw new RuntimeException(min(ini_get('post_max_size'), ini_get('upload_max_filesize')).'以上のファイルはアップロード出来ません。');
            }

            throw new RuntimeException('不明なエラー発生しました。もう一度アップロードしてください。');
        }
    }

    /**
     * Línea del CSV → [顧客番号, 付与ポイント]; null si queda vacía. Mismo orden que el legacy: quita saltos y
     * espacios, pasa de SJIS-win a UTF-8 y los alfanuméricos de ancho completo a medio ancho.
     *
     * @return array{0: string, 1: string}|null
     */
    private function parseLine(string $line, int $lineNo): ?array
    {
        $row = mb_convert_encoding(str_replace(["\r", "\n", ' ', '　'], '', $line), 'UTF-8', 'SJIS-win');
        if ($row === '' || $row === '0') {
            return null;
        }

        $cols = explode(',', mb_convert_kana($row, 'a'));
        if (count($cols) !== 2 || ! preg_match('/^[1-9][0-9]*$/', $cols[0]) || ! preg_match('/^[1-9][0-9]*$/', $cols[1])) {
            throw new RuntimeException($lineNo.'行目に異常があります。');
        }

        return [$cols[0], $cols[1]];
    }
}
