<?php

namespace App\Providers;

use App\Models\AdminMaster;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * 端末管理 (S4): Gates del menú lateral (config/menu/terminales.php).
 */
class TerminalesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // left_navi7: ペーパーサイネージ solo con la UI CRM2 ($crmVersion == 2 = admin_master.base_system_version_flg)
        Gate::define('terminales.paper-signage', fn ($staff) => (string) AdminMaster::current()?->base_system_version_flg === '2');
    }
}
