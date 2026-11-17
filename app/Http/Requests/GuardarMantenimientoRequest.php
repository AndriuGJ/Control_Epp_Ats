<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuardarMantenimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('mantenimientos.gestionar') ?? false;
    }

    public function rules(): array
    {
        return [
            'fecha_mantenimiento' => ['required', 'date_format:Y-m-d\TH:i,Y-m-d\TH:i:s', 'before_or_equal:now'],
            'observaciones' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_mantenimiento.date_format' => 'Ingresa una fecha y hora de mantenimiento válidas.',
            'fecha_mantenimiento.before_or_equal' => 'La fecha del mantenimiento no puede estar en el futuro.',
        ];
    }
}
