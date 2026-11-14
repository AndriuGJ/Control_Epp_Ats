<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarMantenimientoRequest;
use App\Models\Maquina;
use App\Services\MantenimientoService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MantenimientoController extends Controller
{
    public function __construct(private MantenimientoService $mantenimientos) {}

    public function index(): View
    {
        $maquinas = Maquina::query()->orderBy('nombre')->orderBy('id_maquina')->paginate(15);

        return view('mantenimientos.index', [
            'maquinas' => $maquinas,
            'resumenes' => $this->mantenimientos->resumen($maquinas->getCollection()),
        ]);
    }

    public function show(Maquina $maquina): View
    {
        return view('mantenimientos.mostrar', [
            'maquina' => $maquina,
            'resumen' => $this->mantenimientos->resumen($maquina->newCollection([$maquina]))[$maquina->getKey()],
            'historial' => $maquina->mantenimientos()->with('usuario:id,nombres,apellidos')->latest('fecha_mantenimiento')->paginate(15),
        ]);
    }

    public function store(GuardarMantenimientoRequest $request, Maquina $maquina): RedirectResponse
    {
        $this->mantenimientos->registrar(
            $maquina,
            $request->user(),
            CarbonImmutable::parse($request->validated('fecha_mantenimiento')),
            $request->validated('observaciones'),
        );

        return to_route('mantenimientos.show', $maquina)->with('estado', 'Mantenimiento registrado. El nuevo conteo empieza en la fecha indicada.');
    }
}
