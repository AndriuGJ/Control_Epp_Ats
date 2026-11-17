<?php

namespace App\Http\Requests;

use App\Services\ConfiguracionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ActualizarConfiguracionRequest extends FormRequest
{
    public function rules(ConfiguracionService $configuracion): array
    {
        return [
            'nombre_empresa' => ['required', 'string', 'max:120'],
            'area' => ['required', 'string', 'max:100'],
            'logo' => ['nullable', File::types(['png', 'jpg', 'jpeg'])->max(2048), 'dimensions:max_width=2000,max_height=2000'],
            'retirar_logo' => ['sometimes', 'boolean'],
            'avisos_epp' => ['required', 'boolean'],
            'servidor_smtp' => ['required', Rule::in(config('epp.servidores_smtp'))],
            'puerto_smtp' => ['required', 'integer', Rule::in([465, 587])],
            'usuario_smtp' => ['nullable', 'required_if:avisos_epp,1', 'email', 'max:254'],
            'contrasena_smtp' => [
                'nullable',
                Rule::requiredIf(function () use ($configuracion): bool {
                    if (! $this->boolean('avisos_epp')) {
                        return false;
                    }
                    $actual = $configuracion->actual();

                    return ! $actual->tieneContrasenaSmtp()
                        || $actual->servidor_smtp !== $this->input('servidor_smtp')
                        || $actual->usuario_smtp !== $this->input('usuario_smtp');
                }),
                'string',
                'max:255',
            ],
        ];
    }
}
