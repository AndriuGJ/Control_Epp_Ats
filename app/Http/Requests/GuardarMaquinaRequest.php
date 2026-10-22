<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuardarMaquinaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('maquinas.gestionar') ?? false;
    }

    public function rules(): array
    {
        return ['nombre' => ['required', 'string', 'max:100'], 'descripcion' => ['nullable', 'string', 'max:3000'], 'estado' => ['required', 'boolean']];
    }
}
