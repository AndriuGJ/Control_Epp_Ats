<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CapturarEppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('registros.crear') ?? false;
    }

    public function rules(): array
    {
        return ['fotografia' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:4096']];
    }

    public function messages(): array
    {
        return [
            'fotografia.required' => 'Toma una fotografía con la cámara.',
            'fotografia.image' => 'La captura debe ser una imagen válida.',
            'fotografia.mimes' => 'La cámara debe generar una imagen JPG o PNG.',
            'fotografia.max' => 'La captura no debe superar los 4 MB.',
            'fotografia.uploaded' => 'No se pudo enviar la captura. Inténtalo nuevamente.',
        ];
    }
}
