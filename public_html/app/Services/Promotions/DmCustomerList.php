<?php

namespace App\Services\Promotions;

use Illuminate\Database\Query\Builder;

/**
 * DM発行 (dm/customer_list.php): clientes del buscador común (search_set1 + search_set2 de S2) que
 * aterna_a4_12.php imprime en etiquetas.
 *
 * El legacy dejaba el WHERE en `$_SESSION['wherestr']` y la etiqueta lo leía de ahí (antes, del formulario:
 * SQL arbitrario). Aquí la búsqueda se guarda con MailAudience (descripción de CustomerSearch en
 * `customer_search.<uniqid>`, sin SQL) y viaja solo el uniqid, como en 発行条件選択.
 */
class DmCustomerList
{
    public function __construct(private readonly MailAudience $audience) {}

    /**
     * Búsqueda de la pantalla: `enter` (この条件で検索する) guarda una nueva; la paginación y el 表示件数
     * reutilizan la guardada (`search_uniqid`).
     *
     * @return array{uniqid: string, filters: array}
     */
    public function search(array $input, bool $isMaster, ?int $shopCd): array
    {
        $uniqid = (string) ($input['search_uniqid'] ?? '');
        if (! isset($input['enter']) && $this->audience->has($uniqid)) {
            return ['uniqid' => $uniqid, 'filters' => $this->audience->params($uniqid)];
        }

        $this->audience->forget($uniqid);
        $uniqid = $this->audience->store($input, $isMaster, $shopCd);

        return ['uniqid' => $uniqid, 'filters' => $this->audience->params($uniqid)];
    }

    public function has(?string $uniqid): bool
    {
        return $this->audience->has($uniqid);
    }

    public function query(string $uniqid): Builder
    {
        return $this->audience->query($uniqid);
    }

    /** Página del 顧客一覧: orden `cd DESC`, como el legacy */
    public function page(string $uniqid, int $page, int $kensu): array
    {
        $total = $this->query($uniqid)->count();
        $maxPage = (int) ceil($total / $kensu);
        $page = max(1, $page);
        $from = ($page - 1) * $kensu;

        return [
            'total' => $total,
            'maxPage' => $maxPage,
            'page' => $page,
            'from' => $from,
            'last' => min($from + $kensu, $total),
            'rows' => $this->query($uniqid)->orderByDesc('cd')->limit($kensu)->offset($from)->get(),
        ];
    }

    /** Clientes de las etiquetas: orden `cd`, como aterna_a4_12.php */
    public function labels(string $uniqid)
    {
        return $this->query($uniqid)->orderBy('cd')->cursor();
    }
}
