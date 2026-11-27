<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require_once __DIR__ . '/../Nucleo/helpers.php';

/**
 * Pruebas de マイページ con el layout y el login reales de S1: datos del núcleo (seedNucleo: tienda 1 本店,
 * staff master / secreto1, $siteName = 'shop'), la app activa si se pide y la sesión iniciada como master.
 */
function mypageLogin(bool $applicationUse = true): void
{
    seedNucleo();
    DB::table('admin_master')->where('cd', 1)->update(['application_use_flg' => $applicationUse ? 1 : 0]);
    loginAs('master', 'secreto1');
    Storage::fake('public');
}
