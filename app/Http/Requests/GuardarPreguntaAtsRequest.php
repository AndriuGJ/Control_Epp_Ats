<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarPreguntaAtsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('preguntas.gestionar') ?? false;
    }

    public function rules(): array
    {
        return [
            'pregunta' => ['required', 'string', 'max:255'],
            'etapa' => ['required', Rule::in(['Inicial', 'Final'])],
            'posicion' => ['required', 'integer', 'min:1', 'max:999'],
            'estado' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['posicion.min' => 'La posición debe ser al menos 1.', 'posicion.max' => 'La posición no debe superar 999.'];
    }
}
