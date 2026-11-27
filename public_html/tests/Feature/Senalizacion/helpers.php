<?php

use App\Models\Senalizacion\SignageWebContent;
use App\Models\Senalizacion\SignageWebContentCategory;
use App\Models\Senalizacion\SignageWebCycle;
use App\Models\Senalizacion\SignageWebPackage;
use App\Models\Senalizacion\SignageWebTerminal;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../Terminales/helpers.php';

/**
 * BD `admin` de signage/ para las pruebas: la conexión `signage` de S1 (sin DB_SIGNAGE_*: la BD de pruebas
 * con search_path = signage) y el esquema `signage` recreado desde database/schema/signage-admin.sql.
 * RefreshDatabase solo envuelve la conexión por defecto, así que el esquema se recrea en cada prueba.
 */
function snSignageDatabase(): void
{
    // Conexión `signage` de S1 (config/database.php), en la conexión signage (fuera de la transacción de RefreshDatabase, que no la vería)
    $signage = DB::connection('signage');
    $signage->unprepared('DROP SCHEMA IF EXISTS signage CASCADE; CREATE SCHEMA signage;');
    $signage->unprepared(file_get_contents(base_path('database/schema/signage-admin.sql')));
}

/** Paquete h_1_1 con un terminal, una talla 1920x1080, una imagen y sus ciclos (todos «siempre») */
function snSignageFixture(array $cycle = [], int $cycles = 1): array
{
    $category = SignageWebContentCategory::create(['name' => 'フルHD', 'width' => 1920, 'height' => 1080, 'flg' => 1]);
    $content = SignageWebContent::create(['contents_category_cd' => $category->cd, 'name' => '春のバナー', 'img_path' => '1.jpg', 'flg' => 1]);
    $package = SignageWebPackage::create([
        'name' => 'ロビー', 'flg' => 1, 'cycle' => $cycles,
        'display_direction' => '横', 'display_number' => 1, 'display_type' => 'h_1_1',
    ]);
    $rows = [];
    for ($i = 0; $i < $cycles; $i++) {
        $rows[] = SignageWebCycle::create($cycle + [
            'packages_cd' => $package->cd, 'flg' => 1, 'status' => 1, 'flame_cd' => 1,
            'schedule_repeat_type' => SignageWebCycle::ALWAYS,
            'img_path_ary' => serialize([[0 => (string) $content->cd, 1 => 'blank']]),
            'action_ary' => serialize([['action' => '3']]),
        ]);
    }
    $terminal = SignageWebTerminal::create([
        'name' => '入口', 'flg' => 1, 'packages_cd' => $package->cd,
        'display_direction' => '横', 'display_number' => 1, 'display_type' => 'h_1_1',
    ]);

    return compact('category', 'content', 'package', 'terminal') + ['cycles' => $rows];
}
