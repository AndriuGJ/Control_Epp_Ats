<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolverValidacionEppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('epp.validar') ?? false;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['aprobar', 'rechazar'])],
            'motivo_rechazo' => ['required_if:decision,rechazar', 'nullable', 'string', 'max:500'],
        ];
    }
}
