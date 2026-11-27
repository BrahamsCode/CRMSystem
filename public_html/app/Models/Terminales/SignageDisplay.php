<?php

namespace App\Models\Terminales;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use App\Models\Shop;
use App\Models\Terminales\Concerns\HasLegacyLists;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ディスプレイ (`tb_display`): pantalla física. Su `cd` es el `display_cd` que pide
 * el reproductor (/digitalsignage/?shop_cd=&display_cd=). El formulario elige un
 * programa, guardado en `tb_c7_movie_program_cds`.
 */
class SignageDisplay extends LegacyModel
{
    use HasHatuban, HasLegacyLists, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_display';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'rank' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'tb_shop_cd', 'cd');
    }

    /** Materiales asignados al display (`tb_screensaver.tb_display_cd`) */
    public function materials(): HasMany
    {
        return $this->hasMany(SignageMaterial::class, 'tb_display_cd', 'cd');
    }

    public function programCds(): array
    {
        return $this->legacyCds('tb_c7_movie_program_cds');
    }

    /** El programa que reproduce (el primero de la lista, como el formulario) */
    public function program(): ?SignageProgram
    {
        $cd = $this->programCds()[0] ?? null;

        return $cd ? SignageProgram::query()->find($cd) : null;
    }

    public function systemCds(): array
    {
        return $this->legacyCds('tb_dispaly_systems_cds');
    }

    public function couponCds(): array
    {
        return $this->legacySlots('tb_mycoupon_master_cds');
    }

    public function terminalTypes(): array
    {
        return $this->legacyList('terminal_type');
    }
}
