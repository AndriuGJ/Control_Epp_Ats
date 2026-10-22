<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinalizarRegistroAtsRequest;
use App\Http\Requests\IniciarRegistroAtsRequest;
use App\Services\CapturaEppService;
use App\Services\RegistroAtsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistroAtsController extends Controller
{
    public function __construct(private RegistroAtsService $servicio) {}

    public function index(Request $request): View
    {
        return view('registros.index', ['registros' => $this->servicio->listar($request->user())]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $registro = $this->servicio->enCurso($request->user());
        if ($registro) {
            return to_route('registros.show', $registro)->with('estado', 'Ya tienes un registro en curso.');
        }

        return view('registros.iniciar', $this->servicio->prepararInicio($request->user()));
    }

    public function store(IniciarRegistroAtsRequest $request, CapturaEppService $capturas): RedirectResponse
    {
        $captura = $request->session()->get('ats.captura');
        $registro = $this->servicio->iniciar($request->user(), $request->validated(), $captura);
        $request->session()->forget('ats.captura');
        $capturas->descartar($captura);

        return to_route('registros.show', $registro)->with('estado', 'Registro iniciado correctamente.');
    }

    public function show(Request $request, int $registro): View
    {
        return view('registros.mostrar', ['registro' => $this->servicio->obtener($request->user(), $registro)]);
    }

    public function fotografia(Request $request, int $registro): StreamedResponse
    {
        return $this->archivo($request, $registro, 'fotografia_epp');
    }

    public function firma(Request $request, int $registro): StreamedResponse
    {
        return $this->archivo($request, $registro, 'firma_inicial');
    }

    public function finalizar(Request $request, int $registro): View|RedirectResponse
    {
        $datos = $this->servicio->prepararFinalizacion($request->user(), $registro);
        if ($datos['registro']->estado !== 'En curso') {
            return to_route('registros.show', $registro)->with('estado', 'Este registro ya está finalizado.');
        }

        return view('registros.finalizar', $datos);
    }

    public function guardarFinalizacion(FinalizarRegistroAtsRequest $request, int $registro): RedirectResponse
    {
        $registroFinalizado = $this->servicio->finalizar($request->user(), $registro, $request->validated());

        return to_route('registros.show', $registroFinalizado)->with('estado', 'Labor finalizada correctamente.');
    }

    public function firmaFinal(Request $request, int $registro): StreamedResponse
    {
        return $this->archivo($request, $registro, 'firma_final');
    }

    private function archivo(Request $request, int $id, string $campo): StreamedResponse
    {
        $registro = $this->servicio->obtener($request->user(), $id);
        $ruta = $registro->{$campo};
        abort_unless($ruta && Storage::disk('local')->exists($ruta), 404);

        return Storage::disk('local')->response($ruta, null, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
