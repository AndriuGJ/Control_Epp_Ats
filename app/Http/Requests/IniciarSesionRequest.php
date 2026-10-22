<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IniciarSesionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('correo'))) {
            $this->merge(['correo' => mb_strtolower(trim($this->input('correo')))]);
        }
    }

    public function rules(): array
    {
        return ['correo' => ['required', 'email', 'max:255'], 'contrasena' => ['required', 'string'], 'recordarme' => ['sometimes', 'boolean']];
    }

    public function messages(): array
    {
        return ['correo.required' => 'Ingresa tu correo electrónico.', 'correo.email' => 'Ingresa un correo electrónico válido.', 'contrasena.required' => 'Ingresa tu contraseña.'];
    }
}
