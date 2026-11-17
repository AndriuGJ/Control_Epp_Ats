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
            'estado' => ['required', 'boolean'],
        ];
    }
}
