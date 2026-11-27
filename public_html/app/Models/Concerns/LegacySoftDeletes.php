<?php

namespace App\Models\Concerns;

/**
 * Borrado lógico del legacy: `delete()` pone `disp_flg = '9'` y una scope global oculta esas filas.
 * Reemplaza a SoftDeletes (CONVENCIONES §0). `forceDelete()` borra la fila de verdad y
 * `restore()` vuelve a `disp_flg = '1'`. Consultas: Model::withDeleted(), Model::onlyDeleted().
 *
 * El modelo puede cambiar la columna con `protected string $deletedFlagColumn = '...'`.
 */
trait LegacySoftDeletes
{
    public const DELETED_FLAG = '9';

    public const ACTIVE_FLAG = '1';

    protected bool $forceDeleting = false;

    public static function bootLegacySoftDeletes(): void
    {
        static::addGlobalScope(new LegacySoftDeletingScope);
    }

    public function getDeletedFlagColumn(): string
    {
        return property_exists($this, 'deletedFlagColumn') ? $this->deletedFlagColumn : 'disp_flg';
    }

    public function forceDelete(): ?bool
    {
        $this->forceDeleting = true;

        try {
            return $this->delete();
        } finally {
            $this->forceDeleting = false;
        }
    }

    protected function performDeleteOnModel(): void
    {
        if ($this->forceDeleting) {
            $this->setKeysForSaveQuery($this->newModelQuery())->delete();
            $this->exists = false;

            return;
        }

        $this->setAttribute($this->getDeletedFlagColumn(), static::DELETED_FLAG);
        if ($this->usesTimestamps() && $this->getUpdatedAtColumn()) {
            $this->setUpdatedAt($this->freshTimestamp());
        }

        $this->setKeysForSaveQuery($this->newModelQuery())->update($this->getDirty());
        $this->syncOriginal();
    }

    public function restore(): bool
    {
        $this->setAttribute($this->getDeletedFlagColumn(), static::ACTIVE_FLAG);

        return $this->save();
    }

    public function isLegacyDeleted(): bool
    {
        return (string) $this->getAttribute($this->getDeletedFlagColumn()) === static::DELETED_FLAG;
    }
}
