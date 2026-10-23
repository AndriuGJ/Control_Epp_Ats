<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarMaquinaRequest;
use App\Models\Maquina;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class MaquinaController extends Controller
{
    public function index(): View
    {
        return view('maquinas.index', ['maquinas' => Maquina::withCount('operadores')->orderBy('nombre')->orderBy('id_maquina')->paginate(15)]);
    }

    public function create(): View
    {
        return view('maquinas.formulario', ['maquina' => new Maquina(['estado' => true])]);
    }

    public function store(GuardarMaquinaRequest $request): RedirectResponse
    {
        $this->guardar($request, new Maquina);

        return to_route('maquinas.index')->with('estado', 'Máquina registrada correctamente.');
    }

    public function edit(Maquina $maquina): View
    {
        return view('maquinas.formulario', compact('maquina'));
    }

    public function update(GuardarMaquinaRequest $request, Maquina $maquina): RedirectResponse
    {
        $this->guardar($request, $maquina);

        return to_route('maquinas.index')->with('estado', 'Máquina actualizada correctamente.');
    }

    public function fotografia(Maquina $maquina): StreamedResponse
    {
        abort_unless($maquina->fotografia && Storage::disk('local')->exists($maquina->fotografia), 404);

        return Storage::disk('local')->response($maquina->fotografia, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function guardar(GuardarMaquinaRequest $request, Maquina $maquina): void
    {
        $datos = $request->safe()->except('fotografia');
        $fotografiaAnterior = $maquina->fotografia;
        $fotografiaNueva = null;

        if ($request->hasFile('fotografia')) {
            $fotografiaNueva = $request->file('fotografia')->store('maquinas', 'local');
            if ($fotografiaNueva === false) {
                throw ValidationException::withMessages(['fotografia' => 'No se pudo guardar la fotografía. Inténtalo nuevamente.']);
            }
            $datos['fotografia'] = $fotografiaNueva;
        }

        try {
            $maquina->fill($datos)->save();
        } catch (Throwable $excepcion) {
            if ($fotografiaNueva !== null) {
                Storage::disk('local')->delete($fotografiaNueva);
            }
            throw $excepcion;
        }

        if ($fotografiaNueva !== null && $fotografiaAnterior) {
            Storage::disk('local')->delete($fotografiaAnterior);
        }
    }
}
