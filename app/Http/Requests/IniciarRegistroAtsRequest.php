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
        return [
            'firma_inicial.required' => 'Dibuja tu firma antes de iniciar el registro.',
            'firma_inicial.max' => 'La imagen de la firma es demasiado grande. Limpia el recuadro y vuelve a firmar.',
            'respuestas.required' => 'Responde las preguntas iniciales del ATS.',
            'respuestas.min' => 'Responde las preguntas iniciales del ATS.',
            'respuestas.max' => 'El formulario admite hasta 100 preguntas iniciales.',
            'respuestas.*.required' => 'Debes responder todas las preguntas iniciales.',
            'respuestas.*.in' => 'Selecciona únicamente Sí o No en cada pregunta.',
            'captura_epp.required' => 'Toma tu fotografía con la cámara antes de iniciar el registro.',
            'captura_epp.uuid' => 'Vuelve a tomar la fotografía con la cámara.',
            'fotografia_epp.prohibited' => 'Usa la cámara del formulario para tomar la fotografía.',
            'version_preguntas.required' => 'Recarga el formulario antes de iniciar el registro.',
            'version_preguntas.size' => 'Recarga el formulario antes de iniciar el registro.',
        ];
    }

    public function attributes(): array
    {
        return ['id_maquina' => 'máquina', 'firma_inicial' => 'firma inicial'];
    }
}
