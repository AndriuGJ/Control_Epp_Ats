<?php

use App\Services\CapturaEppService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (CapturaEppService $servicio): void {
    $servicio->limpiarVencidas();
})->hourly()->name('limpiar-capturas-epp')->withoutOverlapping();
