<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinalizarRegistroAtsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('registros.crear') ?? false;
    }

    public function rules(): array
    {
        return [
            'firma_final' => ['required', 'string', 'max:300000'],
            'respuestas' => ['required', 'array', 'min:1', 'max:100'],
            'respuestas.*' => ['required', Rule::in(['Sí', 'No'])],
            'version_preguntas' => ['required', 'string', 'size:64'],
        ];
    }

    public function messages(): array
    {
        return __('validation.formularios.finalizar_registro');
    }
}
