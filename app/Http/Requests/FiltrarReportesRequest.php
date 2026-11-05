<?php

namespace App\Http\Requests;

class FiltrarReportesRequest extends FiltrarRegistrosAtsRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->user()->can('reportes.generar');
    }
}
