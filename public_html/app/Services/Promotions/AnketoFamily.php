<?php

namespace App\Services\Promotions;

use App\Models\Promotions\Anketo2Cate;
use App\Models\Promotions\Anketo2Form;
use App\Models\Promotions\Anketo2Sub;
use App\Models\Promotions\AnketoCate;
use App\Models\Promotions\AnketoForm;
use App\Models\Promotions\AnketoSub;
use Illuminate\Database\Eloquent\Builder;

/**
 * Familia de encuestas (F6): アンケート管理 (`category3/anketo/`, tablas `tb_c3_anketo_*`) o
 * お客様アンケート管理 (`category3/anketo2/`, tablas `tb_c3_anketo2_*`).
 * El legacy duplicaba las carpetas; aquí un solo juego de controladores elige modelos, tablas y textos
 * con el parámetro de ruta `family` (las FK de anketo2 se llaman igual: `tb_c3_anketo_cate_cd`/`_form_cd`).
 */
final class AnketoFamily
{
    public const FAMILIES = ['anketo', 'anketo2'];

    private function __construct(public readonly string $key) {}

    public static function from(string $key): self
    {
        abort_unless(in_array($key, self::FAMILIES, true), 404);

        return new self($key);
    }

    /** お客様アンケート (anketo2) */
    public function isCustomer(): bool
    {
        return $this->key === 'anketo2';
    }

    /** @return class-string<AnketoCate|Anketo2Cate> */
    public function cateClass(): string
    {
        return $this->isCustomer() ? Anketo2Cate::class : AnketoCate::class;
    }

    /** @return class-string<AnketoForm|Anketo2Form> */
    public function formClass(): string
    {
        return $this->isCustomer() ? Anketo2Form::class : AnketoForm::class;
    }

    /** @return class-string<AnketoSub|Anketo2Sub> */
    public function subClass(): string
    {
        return $this->isCustomer() ? Anketo2Sub::class : AnketoSub::class;
    }

    public function cates(): Builder
    {
        return ($this->cateClass())::query();
    }

    public function forms(): Builder
    {
        return ($this->formClass())::query();
    }

    public function subs(): Builder
    {
        return ($this->subClass())::query();
    }

    public function cateTable(): string
    {
        return $this->isCustomer() ? 'tb_c3_anketo2_cate' : 'tb_c3_anketo_cate';
    }

    public function formTable(): string
    {
        return $this->isCustomer() ? 'tb_c3_anketo2_form' : 'tb_c3_anketo_form';
    }

    public function subTable(): string
    {
        return $this->isCustomer() ? 'tb_c3_anketo2_sub' : 'tb_c3_anketo_sub';
    }

    /** $title / $page_title del legacy */
    public function title(): string
    {
        return $this->isCustomer() ? 'お客様アンケート' : 'アンケート';
    }

    /** Bloque del menú lateral que se abre: アンケート管理 (.ac8) o お客様アンケート管理 (.ac109) */
    public function ac(): string
    {
        return $this->isCustomer() ? '.ac109' : '.ac8';
    }

    /** URL de una pantalla de la familia: `admin.promocion.<family>.<name>` */
    public function route(string $name, array $params = []): string
    {
        return route('admin.promocion.'.$this->key.'.'.$name, $params);
    }
}
