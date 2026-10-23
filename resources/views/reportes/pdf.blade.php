<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de registros ATS y EPP</title>
    <style>
        @page { margin: 30px 28px 45px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #292d2f; }
        h1 { margin: 0 0 5px; font-size: 19px; }
        .subtitulo { margin: 0 0 15px; color: #666; font-size: 11px; }
        .datos { margin-bottom: 12px; line-height: 1.7; }
        .filtros { padding: 9px 11px; margin-bottom: 13px; background: #f5f6f6; border: 1px solid #ddd; line-height: 1.7; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        th { background: #eef2ff; font-weight: bold; text-align: left; }
        th, td { padding: 7px 5px; border: 1px solid #ddd; vertical-align: top; overflow-wrap: break-word; }
        tr { page-break-inside: avoid; }
        .secundario { color: #666; font-size: 8px; }
        .nota { margin-top: 12px; color: #666; font-size: 8px; }
        .vacio { padding: 20px; text-align: center; }
        .fotografia { text-align: center; }
        .fotografia img { max-width: 115px; max-height: 115px; }
        .fecha-foto { margin-top: 5px; font-size: 7px; color: #666; }
    </style>
</head>
<body>
    <h1>Reporte de registros ATS y EPP</h1>
    <p class="subtitulo">Logística - Makro</p>
    <div class="datos">
        <strong>Generado por:</strong> {{ $generadoPor->nombres }} {{ $generadoPor->apellidos }}<br>
        <strong>Fecha de emisión:</strong> {{ $fechaGeneracion->format('d/m/Y H:i:s') }} (Perú)<br>
        <strong>Total de registros:</strong> {{ $registros->count() }}
    </div>
    <div class="filtros">
        <strong>Inicio de la labor:</strong> desde {{ $filtros['fecha_desde'] ?? 'sin límite' }} hasta {{ $filtros['fecha_hasta'] ?? 'sin límite' }}<br>
        <strong>Trabajador:</strong> {{ $filtros['trabajador'] ?? 'Todos' }} &nbsp; | &nbsp;
        <strong>Máquina:</strong> {{ empty($filtros['id_maquina']) ? 'Todas' : ($maquina?->nombre ?? 'Sin coincidencias') }}<br>
        <strong>Estado:</strong> {{ $filtros['estado'] ?? 'Todos' }} &nbsp; | &nbsp;
        <strong>Revisión:</strong> {{ ($filtros['revision'] ?? '') === 'Pendiente' ? 'Pendiente de revisión' : ($filtros['revision'] ?? 'Todas') }}
    </div>
    @foreach ($registros->isEmpty() ? collect([collect()]) : $registros->chunk(50) as $bloque)
    <table>
        <thead>
            <tr><th style="width: 5%">N.°</th><th style="width: 15%">Trabajador / DNI</th><th style="width: 12%">Máquina</th><th style="width: 9%">Inicio</th><th style="width: 9%">Finalización</th><th style="width: 9%">Estado</th><th style="width: 10%">Revisión</th><th style="width: 13%">Revisor / Fecha</th><th style="width: 18%">Fotografía EPP</th></tr>
        </thead>
        <tbody>
            @forelse ($bloque as $registro)
                <tr>
                    <td>#{{ $registro->id_registro }}</td>
                    <td>{{ $registro->operador->nombres }} {{ $registro->operador->apellidos }}<br><span class="secundario">DNI: {{ $registro->operador->dni }}</span></td>
                    <td>{{ $registro->maquina->nombre }}</td>
                    <td>{{ $registro->fecha_inicio->format('d/m/Y') }}<br>{{ $registro->fecha_inicio->format('H:i') }}</td>
                    <td>@if ($registro->fecha_finalizacion){{ $registro->fecha_finalizacion->format('d/m/Y') }}<br>{{ $registro->fecha_finalizacion->format('H:i') }}@else Sin finalizar @endif</td>
                    <td>{{ $registro->estado }}</td>
                    <td>{{ $registro->fecha_revision ? 'Revisado' : ($registro->estado === 'Finalizado' ? 'Pendiente de revisión' : 'Sin finalizar') }}</td>
                    <td>@if ($registro->revisor){{ $registro->revisor->nombres }} {{ $registro->revisor->apellidos }}<br>@endif<span class="secundario">{{ $registro->fecha_revision?->format('d/m/Y H:i') ?? 'Sin revisión' }}</span></td>
                    <td class="fotografia">
                        @if ($fotografias[$registro->getKey()] ?? null)
                            <img src="{{ $fotografias[$registro->getKey()] }}" alt="Fotografía EPP del registro #{{ $registro->id_registro }}">
                        @else
                            <span class="secundario">Fotografía no disponible</span>
                        @endif
                        <div class="fecha-foto">Captura: {{ $registro->fecha_fotografia->format('d/m/Y H:i:s') }}</div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="vacio">No se encontraron registros con los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table>
    @endforeach
    <p class="nota">Este reporte incluye las fotografías del EPP y su fecha de captura. Las respuestas del ATS y las firmas se consultan desde el detalle de cada registro en el sistema.</p>
</body>
</html>
