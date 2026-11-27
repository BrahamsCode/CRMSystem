<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * 端末管理 (S4). Provisional hasta que el núcleo (S1) comparta $siteName: las vistas
 * admin.terminales.* lo reciben como el $site_name de inc/db.php (tb_office cd = 1:
 * hp_hyouji_name o, si está vacío, kaishamei). Si otra parte ya lo pasa, no se pisa.
 */
class TerminalesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('admin.terminales.*', function ($view) {
            if (! $view->offsetExists('siteName')) {
                $view->with('siteName', $this->siteName());
            }
        });
    }

    private function siteName(): string
    {
        static $name = null;
        if ($name === null) {
            try {
                $office = DB::table('tb_office')->where('cd', 1)->first(['hp_hyouji_name', 'kaishamei']);
                $name = (string) ($office?->hp_hyouji_name ?: $office?->kaishamei);
            } catch (\Throwable) {
                $name = '';
            }
        }

        return $name;
    }
}
