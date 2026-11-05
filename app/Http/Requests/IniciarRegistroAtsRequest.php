<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IniciarRegistroAtsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('registros.crear') ?? false;
    }

    public function rules(): array
    {
        return [
            'id_maquina' => ['required', 'integer'],
            'captura_epp' => ['required', 'uuid'],
            'fotografia_epp' => ['prohibited'],
            'firma_inicial' => ['required', 'string', 'max:300000'],
            'respuestas' => ['required', 'array', 'min:1', 'max:100'],
            'respuestas.*' => ['required', Rule::in(['Sí', 'No'])],
            'version_preguntas' => ['required', 'string', 'size:64'],
        ];
    }

    public function messages(): array
    {
        return __('validation.formularios.iniciar_registro');
    }
}
