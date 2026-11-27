<?php

namespace App\Providers;

use App\Models\AdminMaster;
use App\Models\Staff;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * マイページ管理 (S4): Gates del menú lateral (config/menu/mypage.php).
 */
class MypageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // left_navi6: las 5 pantallas de マイコレ salen con la app activa y el permiso 1035 (閲覧).
        // Ojo: el Gate::before del núcleo deja pasar a master sin mirar la app (ver Pedidos del diseño).
        Gate::define('mypage.mycolle', function (Staff $staff) {
            return (int) AdminMaster::current()?->application_use_flg === 1
                && Gate::forUser($staff)->allows('kengen.1035.1');
        });
    }
}
