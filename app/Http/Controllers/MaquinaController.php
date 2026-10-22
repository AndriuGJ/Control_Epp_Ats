<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarMaquinaRequest;
use App\Models\Maquina;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

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
        Maquina::create($request->validated());

        return to_route('maquinas.index')->with('estado', 'Máquina registrada correctamente.');
    }

    public function edit(Maquina $maquina): View
    {
        return view('maquinas.formulario', compact('maquina'));
    }

    public function update(GuardarMaquinaRequest $request, Maquina $maquina): RedirectResponse
    {
        $maquina->update($request->validated());

        return to_route('maquinas.index')->with('estado', 'Máquina actualizada correctamente.');
    }
}
