<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FiltrarRegistrosAtsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('registros.consultar') ?? false;
    }

    public function rules(): array
    {
        return [
            'fecha_desde' => ['nullable', 'date_format:Y-m-d'],
            'fecha_hasta' => ['nullable', 'date_format:Y-m-d', Rule::when($this->filled('fecha_desde'), ['after_or_equal:fecha_desde'])],
            'trabajador' => ['nullable', 'string', 'max:100'],
            'id_maquina' => ['nullable', 'integer', 'min:1'],
            'estado' => ['nullable', Rule::in(['En curso', 'Finalizado'])],
            'revision' => ['nullable', Rule::in(['Pendiente', 'Revisado'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return __('validation.formularios.filtrar_registros');
    }
}
