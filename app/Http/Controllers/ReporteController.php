<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiltrarReportesRequest;
use App\Services\ReporteService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReporteController extends Controller
{
    public function __construct(private ReporteService $servicio) {}

    public function index(FiltrarReportesRequest $request): View
    {
        return view('reportes.index', $this->servicio->preparar($request->user(), $request->safe()->except('page')));
    }

    public function pdf(FiltrarReportesRequest $request): Response
    {
        $contenido = $this->servicio->generarPdf($request->user(), $request->safe()->except('page'));

        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte-ats-epp-'.now()->format('Ymd-His').'.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
