<?php

namespace App\Models\Terminales;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use App\Models\Shop;
use App\Models\Terminales\Concerns\HasLegacyLists;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * 放映プログラム (`tb_c7_movie_program`). Su `cd` es el nombre del XML que lee el
 * reproductor (hp/uploads/c7/display/<cd>.xml). Los materiales van en
 * `tb_screensaver_cds` (`<>`, orden de reproducción).
 */
class SignageProgram extends LegacyModel
{
    use HasHatuban, HasLegacyLists, LegacySoftDeletes, LegacyTimestamps;

    /** disp_conf: システム選択 */
    public const CONF_FILES = '1';   // 放映ファイル選択

    public const CONF_SYSTEM = '2';  // システム内蔵

    protected $table = 'tb_c7_movie_program';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'rank' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'tb_shop_cd', 'cd');
    }

    public function usesFiles(): bool
    {
        return (string) $this->disp_conf === self::CONF_FILES;
    }

    /** cds de `tb_screensaver_cds` en orden */
    public function materialCds(): array
    {
        return $this->legacyCds('tb_screensaver_cds');
    }

    /** Materiales vigentes en el orden de `tb_screensaver_cds` (los borrados se omiten) */
    public function materials(): Collection
    {
        $cds = $this->materialCds();
        if ($cds === []) {
            return collect();
        }
        $found = SignageMaterial::query()->whereIn('cd', $cds)->get()->keyBy('cd');

        return collect($cds)->map(fn (int $cd) => $found->get($cd))->filter()->values();
    }

    /** Guarda la lista de materiales en el formato del legacy */
    public function setMaterialCds(array $cds): static
    {
        return $this->setLegacyList('tb_screensaver_cds', $cds);
    }

    public function systemCds(): array
    {
        return $this->legacyCds('tb_dispaly_systems_cds');
    }

    /** Cupones (`tb_mycoupon_master_cds` y variantes) por posición, con huecos vacíos */
    public function couponCds(string $prefix = ''): array
    {
        return $this->legacySlots($prefix.'tb_mycoupon_master_cds');
    }

    public function terminalTypes(): array
    {
        return $this->legacyList('terminal_type');
    }

    /** Displays que reproducen este programa */
    public function displays(): Collection
    {
        return SignageDisplay::query()->get()
            ->filter(fn (SignageDisplay $display) => in_array($this->cd, $display->programCds(), true))
            ->values();
    }
}
