<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RevisarRegistroAtsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('registros.revisar') ?? false;
    }

    public function rules(): array
    {
        return ['firma_revisor' => ['required', 'string', 'max:300000']];
    }

    public function messages(): array
    {
        return __('validation.formularios.revisar_registro');
    }
}
