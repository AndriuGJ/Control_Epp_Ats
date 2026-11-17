<?php

namespace App\Providers;

use App\Services\ConfiguracionService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as BladeView;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['layouts.app', 'layouts.acceso', 'auth.ingresar', 'reportes.index'], function (BladeView $vista): void {
            $vista->with('marca', once(fn (): array => app(ConfiguracionService::class)->identidad()));
        });
    }
}
