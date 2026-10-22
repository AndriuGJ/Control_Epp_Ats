<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class GuardarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('usuarios.gestionar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('correo'))) {
            $this->merge(['correo' => mb_strtolower(trim($this->input('correo')))]);
        }
    }

    public function rules(): array
    {
        return [
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'dni' => ['required', 'digits:8', Rule::unique('users', 'dni')->ignore($this->route('usuario'))],
            'correo' => ['required', 'email', 'max:255', Rule::unique('users', 'correo')->ignore($this->route('usuario'))],
            'contrasena' => [$this->isMethod('post') ? 'required' : 'nullable', 'confirmed', Password::min(12)->letters()->numbers()],
            'estado' => ['required', 'boolean'],
            'rol' => ['required', Rule::in(['administrador', 'supervisor', 'operador'])],
            'maquinas' => ['sometimes', 'array'],
            'maquinas.*' => ['integer', 'distinct', Rule::exists('maquinas', 'id_maquina')],
        ];
    }

    public function attributes(): array
    {
        return ['contrasena' => 'contraseña', 'correo' => 'correo electrónico', 'dni' => 'DNI'];
    }
}
