<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnviarValidacionEppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('registros.crear') ?? false;
    }

    public function rules(): array
    {
        return [
            'id_maquina' => ['required', 'integer', 'min:1'],
            'captura_epp' => ['required', 'uuid'],
        ];
    }
}
