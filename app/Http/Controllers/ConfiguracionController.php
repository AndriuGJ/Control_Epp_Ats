<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarConfiguracionRequest;
use App\Services\ConfiguracionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConfiguracionController extends Controller
{
    public function edit(ConfiguracionService $configuracion): View
    {
        return view('configuracion.editar', ['configuracion' => $configuracion->actual()]);
    }

    public function update(ActualizarConfiguracionRequest $request, ConfiguracionService $configuracion): RedirectResponse
    {
        $configuracion->guardar(
            $request->safe()->except(['logo', 'retirar_logo']),
            $request->file('logo'),
            $request->boolean('retirar_logo'),
        );

        return to_route('configuracion.edit')->with('estado', 'Configuración guardada.');
    }

    public function logo(ConfiguracionService $configuracion): StreamedResponse
    {
        $logo = $configuracion->actual()->logo;
        $disco = Storage::disk('local');
        abort_unless($logo && $disco->exists($logo), 404);

        return $disco->response($logo, headers: ['X-Content-Type-Options' => 'nosniff']);
    }
}
