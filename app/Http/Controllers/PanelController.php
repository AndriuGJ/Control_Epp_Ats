<?php

namespace App\Http\Controllers;

use App\Models\RegistroAtsEpp;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function __invoke(Request $request): View
    {
        $usuario = $request->user();
        $registros = RegistroAtsEpp::query();
        if (! $usuario->can('registros.revisar')) {
            $registros->where('id_operador', $usuario->getKey());
        }

        return view('panel', [
            'enCurso' => (clone $registros)->where('estado', 'En curso')->count(),
            'finalizados' => (clone $registros)->where('estado', 'Finalizado')->count(),
            'pendientesRevision' => (clone $registros)->where('estado', 'Finalizado')->whereNull('fecha_revision')->count(),
            'maquinasDisponibles' => $usuario->maquinas()->where('estado', true)->orderBy('nombre')->get(),
            'ultimosRegistros' => $registros->with(['maquina', 'operador'])->orderByDesc('fecha_inicio')->orderByDesc('id_registro')->limit(8)->get(),
        ]);
    }
}
