<?php

namespace App\Services;

use App\Models\Configuracion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ConfiguracionService
{
    public function actual(): Configuracion
    {
        return Configuracion::query()->findOrFail(1);
    }

    public function identidad(): array
    {
        $configuracion = $this->actual();

        return [
            'empresa' => $configuracion->nombre_empresa,
            'area' => $configuracion->area,
            'logo' => $configuracion->logo ? route('marca.logo', ['version' => basename($configuracion->logo)]) : null,
        ];
    }

    public function guardar(array $datos, ?UploadedFile $logo, bool $retirarLogo): void
    {
        $ruta = $logo?->store('marca', 'local');
        if ($ruta === false) {
            throw ValidationException::withMessages(['logo' => 'No se pudo guardar el logo. Inténtalo nuevamente.']);
        }

        try {
            $anterior = DB::transaction(function () use ($datos, $ruta, $retirarLogo): ?string {
                $configuracion = Configuracion::query()->lockForUpdate()->findOrFail(1);
                $anterior = $configuracion->logo;
                if (blank($datos['contrasena_smtp'] ?? null)) {
                    if ($configuracion->servidor_smtp === $datos['servidor_smtp']
                        && $configuracion->usuario_smtp === ($datos['usuario_smtp'] ?? null)) {
                        unset($datos['contrasena_smtp']);
                    } else {
                        $datos['contrasena_smtp'] = null;
                    }
                }
                if ($ruta !== null || $retirarLogo) {
                    $datos['logo'] = $ruta;
                }
                $configuracion->update($datos);

                return $anterior !== $configuracion->logo ? $anterior : null;
            });
        } catch (Throwable $error) {
            if ($ruta !== null) {
                Storage::disk('local')->delete($ruta);
            }
            throw $error;
        }

        if ($anterior !== null) {
            rescue(fn () => Storage::disk('local')->delete($anterior));
        }
    }

    public function logoParaPdf(Configuracion $configuracion): ?string
    {
        $disco = Storage::disk('local');
        if (! $configuracion->logo || ! $disco->exists($configuracion->logo)) {
            return null;
        }

        $contenido = $disco->get($configuracion->logo);
        if ($contenido === null) {
            return null;
        }
        $mime = str_ends_with($configuracion->logo, '.png') ? 'image/png' : 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode($contenido);
    }
}
