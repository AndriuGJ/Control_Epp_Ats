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
        return [
            'fecha_desde.date_format' => 'Selecciona una fecha de inicio válida.',
            'fecha_hasta.date_format' => 'Selecciona una fecha de fin válida.',
            'fecha_hasta.after_or_equal' => 'La fecha hasta debe ser igual o posterior a la fecha desde.',
            'trabajador.string' => 'Escribe el nombre, los apellidos o el DNI del trabajador.',
            'trabajador.max' => 'La búsqueda del trabajador admite hasta 100 caracteres.',
            'id_maquina.integer' => 'Selecciona una máquina válida.',
            'id_maquina.min' => 'Selecciona una máquina válida.',
            'estado.in' => 'Selecciona un estado válido.',
            'revision.in' => 'Selecciona una opción de revisión válida.',
            'page.integer' => 'La página debe ser un número entero positivo.',
            'page.min' => 'La página debe ser un número entero positivo.',
        ];
    }
}
