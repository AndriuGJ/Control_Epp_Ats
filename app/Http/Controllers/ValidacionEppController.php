<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnviarValidacionEppRequest;
use App\Http\Requests\ResolverValidacionEppRequest;
use App\Models\ValidacionEpp;
use App\Services\CapturaEppService;
use App\Services\ValidacionEppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ValidacionEppController extends Controller
{
    public function __construct(private ValidacionEppService $validaciones) {}

    public function index(Request $request): View
    {
        $pendientes = ValidacionEpp::query()->with(['operador', 'maquina'])
            ->where('estado', ValidacionEpp::PENDIENTE)->where('vence_en', '>', now())
            ->where('id_operador', '!=', $request->user()->getKey())
            ->orderBy('id_validacion_epp')->paginate(15);

        return view('validaciones-epp.index', compact('pendientes'));
    }

    public function store(EnviarValidacionEppRequest $request, CapturaEppService $capturas): RedirectResponse
    {
        $captura = $request->session()->get('ats.captura');
        $this->validaciones->enviar($request->user(), $request->integer('id_maquina'), $captura, $request->validated('captura_epp'));
        $request->session()->forget('ats.captura');
        $capturas->descartar($captura);

        return to_route('registros.create')->with('estado', 'Foto enviada para revisión.');
    }

    public function estado(Request $request): JsonResponse
    {
        return response()->json([
            'estado' => $this->validaciones->ultima($request->user())?->estadoActual(),
        ], headers: ['Cache-Control' => 'private, no-store']);
    }

    public function fotografia(Request $request, ValidacionEpp $validacion): StreamedResponse
    {
        $usuario = $request->user();
        abort_unless(($usuario->can('registros.crear') && $usuario->getKey() === (int) $validacion->id_operador)
            || $usuario->can('epp.validar'), 404);
        abort_unless(Storage::disk('local')->exists($validacion->fotografia), 404);

        return Storage::disk('local')->response($validacion->fotografia, null, [
            'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
        ]);
    }

    public function resolver(ResolverValidacionEppRequest $request, ValidacionEpp $validacion): RedirectResponse
    {
        $this->validaciones->resolver($validacion, $request->user(), $request->validated('decision'), $request->validated('motivo_rechazo'));

        return to_route('validaciones-epp.index')->with('estado', 'Revisión de EPP guardada.');
    }
}
