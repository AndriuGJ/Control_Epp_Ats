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
        return [
            'firma_final.required' => 'Dibuja tu firma final antes de finalizar la labor.',
            'firma_final.max' => 'La firma es demasiado grande. Limpia el recuadro y vuelve a firmar.',
            'respuestas.required' => 'Responde las preguntas finales del ATS.',
            'respuestas.min' => 'Responde las preguntas finales del ATS.',
            'respuestas.max' => 'El formulario admite hasta 100 preguntas finales.',
            'respuestas.*.required' => 'Marca Sí o No en todas las preguntas finales.',
            'respuestas.*.in' => 'Selecciona únicamente Sí o No en cada pregunta.',
            'version_preguntas.required' => 'Recarga el formulario antes de finalizar.',
            'version_preguntas.size' => 'Recarga el formulario antes de finalizar.',
        ];
    }
}
