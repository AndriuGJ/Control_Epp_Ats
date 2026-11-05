<?php

namespace App\Http\Controllers;

use App\Http\Requests\CapturarEppRequest;
use App\Services\CapturaEppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class CapturaEppController extends Controller
{
    public function __invoke(CapturarEppRequest $request, CapturaEppService $servicio): JsonResponse
    {
        $captura = $servicio->tomar($request->user(), $request->file('fotografia'));
        $anterior = $request->session()->get('ats.captura');
        $request->session()->put('ats.captura', $captura);
        $servicio->descartar($anterior);

        return response()->json([
            'captura' => $captura['token'],
            'fecha_hora' => Carbon::parse($captura['fecha'])->timezone(config('app.timezone'))->format('d/m/Y H:i:s'),
            'zona_horaria' => config('app.timezone'),
        ]);
    }
}
