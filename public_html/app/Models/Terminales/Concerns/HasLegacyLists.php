<?php

namespace App\Models\Terminales\Concerns;

/**
 * Listas del legacy guardadas en una sola columna con separador `<>`
 * (`tb_screensaver_cds`, `terminal_type`, `pic_flg`…). Se siguen guardando igual
 * porque la BD es la del legacy; estos métodos solo las parten y las unen.
 */
trait HasLegacyLists
{
    /** Elementos de la lista, sin vacíos (el legacy deja un `<>` final) */
    public function legacyList(string $column): array
    {
        $value = (string) $this->getAttribute($column);

        return $value === '' ? [] : array_values(array_filter(explode('<>', $value), fn ($item) => $item !== ''));
    }

    /** Elementos de la lista conservando los huecos vacíos (pic_flg / pic_extension por posición) */
    public function legacySlots(string $column): array
    {
        $value = $this->getAttribute($column);

        return $value === null || $value === '' ? [] : explode('<>', (string) $value);
    }

    /** Códigos numéricos de una lista `<>` de cds */
    public function legacyCds(string $column): array
    {
        return array_map('intval', array_values(array_filter($this->legacyList($column), 'ctype_digit')));
    }

    /**
     * Guarda una lista con el formato del legacy (`implode('<>', …)`, sin `<>` final).
     * Los huecos vacíos se conservan: algunas listas son posicionales (cupones por fila).
     */
    public function setLegacyList(string $column, array $items): static
    {
        $this->setAttribute($column, implode('<>', array_map(fn ($item) => (string) $item, $items)));

        return $this;
    }
}
