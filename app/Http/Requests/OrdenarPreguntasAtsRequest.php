<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrdenarPreguntasAtsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('preguntas.gestionar') ?? false;
    }

    public function rules(): array
    {
        $reglas = ['etapa' => [$this->isMethod('GET') ? 'sometimes' : 'required', Rule::in(['Inicial', 'Final'])]];

        if (! $this->isMethod('GET')) {
            $reglas['orden'] = ['required', 'array', 'list', 'min:1'];
            $reglas['orden.*'] = ['required', 'integer', 'min:1', 'distinct'];
            $reglas['version_orden'] = ['required', 'string', 'size:64'];
        }

        return $reglas;
    }

    public function messages(): array
    {
        return __('validation.formularios.ordenar_preguntas');
    }
}
