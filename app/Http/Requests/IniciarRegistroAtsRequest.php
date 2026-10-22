<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'fotografia_epp' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'firma_inicial' => ['required', 'string', 'max:300000'],
            'respuestas' => ['required', 'array', 'min:1', 'max:100'],
            'respuestas.*' => ['required', 'string', 'max:2000'],
            'version_preguntas' => ['required', 'string', 'size:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'fotografia_epp.required' => 'Adjunta una fotografía usando tu EPP.',
            'fotografia_epp.image' => 'La fotografía del EPP debe ser una imagen válida.',
            'fotografia_epp.mimes' => 'La fotografía debe estar en formato JPG, PNG o WEBP.',
            'fotografia_epp.max' => 'La fotografía no debe superar los 4 MB.',
            'fotografia_epp.uploaded' => 'No se pudo subir la fotografía. Revisa el tamaño del archivo.',
            'firma_inicial.required' => 'Dibuja tu firma antes de iniciar el registro.',
            'firma_inicial.max' => 'La imagen de la firma es demasiado grande. Limpia el recuadro y vuelve a firmar.',
            'respuestas.required' => 'Responde las preguntas iniciales del ATS.',
            'respuestas.min' => 'Responde las preguntas iniciales del ATS.',
            'respuestas.max' => 'El formulario admite hasta 100 preguntas iniciales.',
            'respuestas.*.required' => 'Debes responder todas las preguntas iniciales.',
            'respuestas.*.max' => 'Cada respuesta admite como máximo 2000 caracteres.',
            'version_preguntas.required' => 'Recarga el formulario antes de iniciar el registro.',
            'version_preguntas.size' => 'Recarga el formulario antes de iniciar el registro.',
        ];
    }

    public function attributes(): array
    {
        return ['id_maquina' => 'máquina', 'firma_inicial' => 'firma inicial'];
    }
}
