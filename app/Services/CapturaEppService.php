<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CapturaEppService
{
    public function tomar(User $usuario, UploadedFile $fotografia): array
    {
        abort_unless($usuario->estado && $usuario->can('registros.crear'), 403);
        $ruta = $fotografia->store('registros/capturas', 'local');
        if ($ruta === false) {
            throw ValidationException::withMessages(['fotografia' => 'No se pudo guardar la captura. Vuelve a tomar la foto.']);
        }

        return ['id_usuario' => $usuario->getKey(), 'token' => (string) Str::uuid(), 'ruta' => $ruta, 'fecha' => now()->toIso8601String()];
    }

    public function validar(User $usuario, ?array $captura, string $token): array
    {
        if (! $captura || ! isset($captura['id_usuario'], $captura['token'], $captura['ruta'], $captura['fecha']) || $captura['id_usuario'] !== $usuario->getKey() || ! hash_equals($captura['token'], $token)) {
            throw ValidationException::withMessages(['captura_epp' => 'Toma tu fotografía con la cámara antes de iniciar el registro.']);
        }
        if (Carbon::parse($captura['fecha'])->lt(now()->subMinutes(30)) || ! Storage::disk('local')->exists($captura['ruta'])) {
            throw ValidationException::withMessages(['captura_epp' => 'La captura venció o ya fue utilizada. Vuelve a tomar tu fotografía.']);
        }

        return $captura;
    }

    public function descartar(?array $captura): void
    {
        if (isset($captura['ruta']) && str_starts_with($captura['ruta'], 'registros/capturas/')) {
            Storage::disk('local')->delete($captura['ruta']);
        }
    }

    public function limpiarVencidas(): int
    {
        $disco = Storage::disk('local');
        $eliminadas = 0;
        foreach ($disco->allFiles('registros/capturas') as $ruta) {
            if ($disco->lastModified($ruta) < now()->subHour()->timestamp && $disco->delete($ruta)) {
                $eliminadas++;
            }
        }

        return $eliminadas;
    }
}
