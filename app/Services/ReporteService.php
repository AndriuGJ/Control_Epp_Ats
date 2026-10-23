<?php

namespace App\Services;

use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Validation\ValidationException;

class ReporteService
{
    public const int MAXIMO_REGISTROS = 1000;

    public function __construct(private RegistroAtsService $registros) {}

    public function preparar(User $usuario, array $filtros = []): array
    {
        $this->autorizar($usuario);

        return [
            'registros' => $this->registros->consultar($usuario, $filtros)->with('revisor')->paginate(15)->appends($filtros),
            'maquinas' => $this->registros->maquinasParaConsulta($usuario),
            'filtros' => $filtros,
            'maximoRegistros' => self::MAXIMO_REGISTROS,
        ];
    }

    public function generarPdf(User $usuario, array $filtros = []): string
    {
        $this->autorizar($usuario);
        $registros = $this->registros->consultar($usuario, $filtros)->with('revisor')->limit(self::MAXIMO_REGISTROS + 1)->get();
        if ($registros->count() > self::MAXIMO_REGISTROS) {
            throw ValidationException::withMessages(['reporte' => 'El PDF admite hasta 1000 registros. Reduce el intervalo de fechas o aplica otros filtros.']);
        }
        $opciones = new Options;
        $opciones->setDefaultFont('DejaVu Sans');
        $opciones->setIsRemoteEnabled(false);
        $opciones->setIsPhpEnabled(false);
        $opciones->setIsJavascriptEnabled(false);
        $opciones->setChroot(base_path('vendor/dompdf/dompdf/lib/fonts'));
        $pdf = new Dompdf($opciones);
        $maquina = empty($filtros['id_maquina']) ? null : $this->registros->maquinasParaConsulta($usuario)->firstWhere('id_maquina', $filtros['id_maquina']);
        $pdf->loadHtml(view('reportes.pdf', [
            'registros' => $registros, 'filtros' => $filtros, 'maquina' => $maquina,
            'generadoPor' => $usuario, 'fechaGeneracion' => now(),
        ])->render(), 'UTF-8');
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();
        $lienzo = $pdf->getCanvas();
        $fuente = $pdf->getFontMetrics()->getFont('DejaVu Sans');
        $lienzo->page_text($lienzo->get_width() - 175, $lienzo->get_height() - 28, 'Página {PAGE_NUM} de {PAGE_COUNT}', $fuente, 8, [0.4, 0.4, 0.4]);

        return $pdf->output();
    }

    private function autorizar(User $usuario): void
    {
        abort_unless($usuario->estado && $usuario->can('reportes.generar') && $usuario->can('registros.consultar'), 403);
    }
}
