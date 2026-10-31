<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarPreguntaAtsRequest;
use App\Models\PreguntaAts;
use App\Services\PreguntaAtsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PreguntaAtsController extends Controller
{
    public function __construct(private PreguntaAtsService $servicio) {}

    public function index(): View
    {
        return view('preguntas.index', ['preguntas' => $this->servicio->listar()]);
    }

    public function create(): View
    {
        return view('preguntas.formulario', ['pregunta' => new PreguntaAts(['estado' => true, 'etapa' => 'Inicial']), 'siguientesPosiciones' => $this->servicio->siguientesPosiciones()]);
    }

    public function store(GuardarPreguntaAtsRequest $request): RedirectResponse
    {
        $this->servicio->guardar(new PreguntaAts, $request->validated());

        return to_route('preguntas.index')->with('estado', 'Pregunta registrada correctamente.');
    }

    public function edit(PreguntaAts $pregunta): View
    {
        return view('preguntas.formulario', ['pregunta' => $pregunta, 'siguientesPosiciones' => $this->servicio->siguientesPosiciones()]);
    }

    public function update(GuardarPreguntaAtsRequest $request, PreguntaAts $pregunta): RedirectResponse
    {
        $this->servicio->guardar($pregunta, $request->validated());

        return to_route('preguntas.index')->with('estado', 'Pregunta actualizada correctamente.');
    }
}
