<?php

namespace App\Services;

use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
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
        $fotografias = [];
        foreach ($registros as $registro) {
            $fotografias[$registro->getKey()] = $this->fotografiaParaPdf($registro->fotografia_epp);
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
            'registros' => $registros, 'filtros' => $filtros, 'maquina' => $maquina, 'fotografias' => $fotografias,
            'generadoPor' => $usuario, 'fechaGeneracion' => now(),
        ])->render(), 'UTF-8');
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();
        $lienzo = $pdf->getCanvas();
        $fuente = $pdf->getFontMetrics()->getFont('DejaVu Sans');
        $lienzo->page_text($lienzo->get_width() - 175, $lienzo->get_height() - 28, 'Página {PAGE_NUM} de {PAGE_COUNT}', $fuente, 8, [0.4, 0.4, 0.4]);

        return $pdf->output();
    }

    private function fotografiaParaPdf(string $ruta): ?string
    {
        if (! preg_match('#^registros/epp/[a-zA-Z0-9._-]+\.(?:jpe?g|png)$#i', $ruta)) {
            return null;
        }
        $disco = Storage::disk('local');
        if (! $disco->exists($ruta) || $disco->size($ruta) > 4 * 1024 * 1024) {
            return null;
        }
        $contenido = $disco->get($ruta);
        $datos = is_string($contenido) ? @getimagesizefromstring($contenido) : false;
        if (! $datos || ! in_array($datos['mime'], ['image/jpeg', 'image/png'], true) || $datos[0] > 4000 || $datos[1] > 4000 || $datos[0] * $datos[1] > 12000000) {
            return null;
        }
        $original = @imagecreatefromstring($contenido);
        if ($original === false) {
            return null;
        }
        $escala = min(1, 360 / max($datos[0], $datos[1]));
        $ancho = max(1, (int) round($datos[0] * $escala));
        $alto = max(1, (int) round($datos[1] * $escala));
        $miniatura = imagecreatetruecolor($ancho, $alto);
        imagefill($miniatura, 0, 0, imagecolorallocate($miniatura, 255, 255, 255));
        imagecopyresampled($miniatura, $original, 0, 0, 0, 0, $ancho, $alto, $datos[0], $datos[1]);
        ob_start();
        imagejpeg($miniatura, null, 80);
        $jpeg = ob_get_clean();

        return is_string($jpeg) ? 'data:image/jpeg;base64,'.base64_encode($jpeg) : null;
    }

    private function autorizar(User $usuario): void
    {
        abort_unless($usuario->estado && $usuario->can('reportes.generar') && $usuario->can('registros.consultar'), 403);
    }
}
