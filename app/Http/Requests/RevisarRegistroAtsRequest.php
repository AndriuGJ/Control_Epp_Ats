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
        return [
            'firma_revisor.required' => 'Dibuja tu firma antes de guardar la revisión.',
            'firma_revisor.string' => 'Dibuja una firma válida en el recuadro.',
            'firma_revisor.max' => 'La firma es demasiado grande. Limpia el recuadro y vuelve a firmar.',
        ];
    }
}
