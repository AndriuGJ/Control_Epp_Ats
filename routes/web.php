<?php

use App\Http\Controllers\MaquinaController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\PreguntaAtsController;
use App\Http\Controllers\RegistroAtsController;
use App\Http\Controllers\SesionController;
use App\Http\Controllers\UsuarioController;
use App\Http\Middleware\VerificarUsuarioActivo;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/panel');
Route::middleware('guest')->group(function (): void {
    Route::get('/ingresar', [SesionController::class, 'create'])->name('login');
    Route::post('/ingresar', [SesionController::class, 'store'])->name('sesion.store');
});
Route::middleware(['auth', VerificarUsuarioActivo::class])->group(function (): void {
    Route::get('/panel', PanelController::class)->name('panel');
    Route::resource('preguntas', PreguntaAtsController::class)->parameters(['preguntas' => 'pregunta'])->only(['index', 'create', 'store', 'edit', 'update'])->middleware('can:preguntas.gestionar');
    Route::get('/registros/create', [RegistroAtsController::class, 'create'])->middleware('can:registros.crear')->name('registros.create');
    Route::post('/registros', [RegistroAtsController::class, 'store'])->middleware('can:registros.crear')->name('registros.store');
    Route::middleware('can:registros.consultar')->group(function (): void {
        Route::get('/registros', [RegistroAtsController::class, 'index'])->name('registros.index');
        Route::get('/registros/{registro}', [RegistroAtsController::class, 'show'])->whereNumber('registro')->name('registros.show');
        Route::get('/registros/{registro}/fotografia', [RegistroAtsController::class, 'fotografia'])->whereNumber('registro')->name('registros.fotografia');
        Route::get('/registros/{registro}/firma-inicial', [RegistroAtsController::class, 'firma'])->whereNumber('registro')->name('registros.firma');
    });
    Route::post('/salir', [SesionController::class, 'destroy'])->name('logout');
    Route::view('/reportes', 'reportes.index')->middleware('can:reportes.generar')->name('reportes.index');
    Route::get('/maquinas/{maquina}/fotografia', [MaquinaController::class, 'fotografia'])->middleware('can:maquinas.gestionar')->name('maquinas.fotografia');
    Route::resource('usuarios', UsuarioController::class)->parameters(['usuarios' => 'usuario'])->only(['index', 'create', 'store', 'edit', 'update'])->middleware('can:usuarios.gestionar');
    Route::resource('maquinas', MaquinaController::class)->parameters(['maquinas' => 'maquina'])->only(['index', 'create', 'store', 'edit', 'update'])->middleware('can:maquinas.gestionar');
});
