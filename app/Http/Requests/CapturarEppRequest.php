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
        return __('validation.formularios.capturar_epp');
    }
}
