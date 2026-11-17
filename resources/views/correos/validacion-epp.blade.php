<x-mail::message>
# {{ $recordatorio ? 'Recordatorio de revisión de EPP' : 'Nueva solicitud de revisión de EPP' }}

{{ $recordatorio ? 'La foto de EPP sigue pendiente de revisión.' : 'Un operador envió su foto de EPP para revisión.' }}

**Operador:** {{ $validacion->operador->nombres }} {{ $validacion->operador->apellidos }}

**Máquina:** {{ $validacion->maquina->nombre }}

**Revisar antes de:** {{ $validacion->vence_en->format('d/m/Y H:i') }}

<x-mail::button :url="route('validaciones-epp.index')">
Ir a validaciones de EPP
</x-mail::button>

El operador podrá iniciar el ATS cuando se apruebe la foto.
</x-mail::message>
