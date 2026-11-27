<?php

namespace App\Services\Customers;

use App\Models\AdminMaster;
use App\Models\BaseSystemGrandchild;
use App\Models\Promotions\KingakuRankMaster;
use App\Models\Promotions\RaitenRankMaster;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * 顧客ランク設定 (legacy: category1/rank/customer_rank_list.php, ajax_customer_rank_list.php,
 * *_rank_new.php / *_rank_update.php, customer_rank_frm.php y customer_rank_frm_register.php).
 * Tablas de S3 (`tb_kingaku_rank_master`, `tb_raiten_rank_master`): se usan sus modelos sin cambiarlos.
 */
class RankService
{
    public const KINGAKU = 'kingaku';

    public const RAITEN = 'raiten';

    public const MODELS = [
        self::KINGAKU => KingakuRankMaster::class,
        self::RAITEN => RaitenRankMaster::class,
    ];

    /** admin_master.customer_rank_disp_flg: 0 両方, 1 使用金額, 2 来店回数, 9 (o vacío) no mostrar en マイページ */
    public const DISP_FLAGS = ['0', '1', '2', '9'];

    /** @return class-string<Model> */
    public function modelClass(string $type): string
    {
        return self::MODELS[$type] ?? throw new \InvalidArgumentException("Tipo de rango desconocido: {$type}");
    }

    /**
     * Tipos activos según `tb_base_system_grandchild` (`system_name like '顧客ランク%'`): 「金額」 → kingaku,
     * 「来店」 → raiten, en el orden de esas filas (el legacy no ordenaba; aquí rank, cd).
     *
     * @return list<string>
     */
    public function availableTypes(): array
    {
        $types = [];
        $names = BaseSystemGrandchild::query()
            ->where('system_name', 'like', '顧客ランク%')
            ->where('system_flg', '1')
            ->orderBy('rank')->orderBy('cd')
            ->pluck('system_name');

        foreach ($names as $name) {
            if (str_contains((string) $name, '金額')) {
                $types[] = self::KINGAKU;
            }
            if (str_contains((string) $name, '来店')) {
                $types[] = self::RAITEN;
            }
        }

        return $types;
    }

    /** Lista del legacy: `disp_flg = '1' ORDER BY rank, cd` */
    public function list(string $type): Collection
    {
        return $this->modelClass($type)::query()->where('disp_flg', '1')->orderBy('rank')->orderBy('cd')->get();
    }

    /** customer_rank_frm_register.php: alta (cd con hatuban, disp_flg = '1') o edición */
    public function save(Model $rank, array $data): Model
    {
        $rank->fill([
            'name' => $data['name'],
            'jyouken1' => $data['jyouken1'] === '' ? null : (int) $data['jyouken1'],
            'jyouken2' => $data['jyouken2'] === '' ? null : (int) $data['jyouken2'],
            'hyouji_flg' => $data['hyouji_flg'],
        ]);
        if (! $rank->exists) {
            $rank->setAttribute('disp_flg', '1');
        }
        $rank->save();

        return $rank;
    }

    /** ajax type=hyouji: casilla de la lista → hyouji_flg '1' / '9' */
    public function setVisibility(Model $rank, bool $visible): void
    {
        $rank->setAttribute('hyouji_flg', $visible ? '1' : '9');
        $rank->save();
    }

    /**
     * ajax type=rank (arrastrar y soltar): cada fila activa recibe el orden enviado como `r_<cd>`;
     * las que no vienen quedan con `rank = null`, como el legacy.
     *
     * @param  array<int|string, int|string|null>  $orderByCd
     */
    public function reorder(string $type, array $orderByCd): void
    {
        $class = $this->modelClass($type);

        DB::transaction(function () use ($class, $orderByCd) {
            foreach ($class::query()->where('disp_flg', '1')->orderBy('cd')->pluck('cd') as $cd) {
                $value = $orderByCd[$cd] ?? null;
                $class::query()->whereKey($cd)->toBase()->update([
                    'rank' => ($value === null || $value === '') ? null : (int) $value,
                ]);
            }
        });
    }

    /** 削除: disp_flg = '9' y update_date */
    public function delete(Model $rank): void
    {
        $rank->delete();
    }

    /** マイページへのランク表示設定 (vacío = '9', como la pantalla del legacy) */
    public function displayFlag(): string
    {
        $flag = (string) AdminMaster::current()->customer_rank_disp_flg;

        return $flag === '' ? '9' : $flag;
    }

    public function updateDisplayFlag(string $flag): void
    {
        AdminMaster::query()->whereKey(AdminMaster::ROW_CD)->update(['customer_rank_disp_flg' => $flag]);
    }
}
