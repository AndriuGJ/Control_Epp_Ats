<?php

namespace App\Jobs;

use App\Mail\AvisoValidacionEpp;
use App\Models\User;
use App\Models\ValidacionEpp;
use App\Services\ConfiguracionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class EnviarAvisoValidacionEpp implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 30;

    public function __construct(public int $idValidacion, public bool $recordatorio = false) {}

    public function handle(ConfiguracionService $configuracionService): void
    {
        $configuracion = $configuracionService->actual();
        if (! $configuracion->avisos_epp || ! $configuracion->usuario_smtp || ! $configuracion->tieneContrasenaSmtp()
            || ! in_array($configuracion->servidor_smtp, config('epp.servidores_smtp'), true)) {
            return;
        }

        $validacion = ValidacionEpp::query()->with(['operador', 'maquina'])->find($this->idValidacion);
        if (! $validacion || $validacion->estadoActual() !== ValidacionEpp::PENDIENTE
            || ValidacionEpp::query()->where('id_operador', $validacion->id_operador)
                ->where('id_validacion_epp', '>', $validacion->getKey())->exists()) {
            return;
        }

        $destinatarios = User::query()->role(['supervisor', 'administrador'])
            ->where('estado', true)->whereKeyNot($validacion->id_operador)->with(['roles', 'permissions'])->get()
            ->filter(fn (User $usuario): bool => $usuario->can('epp.validar') && filter_var($usuario->correo, FILTER_VALIDATE_EMAIL) !== false)
            ->pluck('correo')->unique()->values();

        if ($destinatarios->isEmpty()) {
            return;
        }

        try {
            Mail::build([
                'transport' => 'smtp',
                'scheme' => $configuracion->puerto_smtp === 465 ? 'smtps' : 'smtp',
                'require_tls' => true,
                'host' => $configuracion->servidor_smtp,
                'port' => $configuracion->puerto_smtp,
                'username' => $configuracion->usuario_smtp,
                'password' => $configuracion->contrasena_smtp,
                'timeout' => 15,
            ])->bcc($destinatarios->all())->send(
                (new AvisoValidacionEpp($validacion, $this->recordatorio))
                    ->from($configuracion->usuario_smtp, $configuracion->nombre_empresa),
            );
        } catch (TransportExceptionInterface $error) {
            throw new RuntimeException('No se pudo entregar el aviso de EPP por SMTP.', (int) $error->getCode());
        }
    }
}
