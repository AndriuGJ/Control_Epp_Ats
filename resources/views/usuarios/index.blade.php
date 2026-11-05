@extends('layouts.app')

@section('titulo', 'Usuarios y autorizaciones')

@section('contenido')
    <div class="mb-7 flex flex-wrap items-center justify-between gap-4"><div><h1 class="text-2xl font-semibold">Usuarios y autorizaciones</h1><p class="mt-2 text-sm text-neutral-500">Cuentas de acceso y equipos permitidos para cada operador.</p></div><a href="{{ route('usuarios.create') }}" class="boton">@include('partials.icono', ['nombre' => 'agregar']) Registrar usuario</a></div>
    <div class="overflow-x-auto rounded-md border border-neutral-200 bg-white"><table class="tabla"><thead><tr><th>Usuario</th><th>DNI</th><th>Rol</th><th>Estado</th><th><span class="sr-only">Acciones</span></th></tr></thead><tbody>@forelse ($usuarios as $usuario)<tr><td><p class="font-medium">{{ $usuario->nombres }} {{ $usuario->apellidos }}</p><p class="mt-1 text-xs text-neutral-500">{{ $usuario->correo }}</p></td><td>{{ $usuario->dni ?: 'Sin registrar' }}</td><td class="capitalize">{{ $usuario->getRoleNames()->implode(', ') }}</td><td><span class="insignia {{ $usuario->estado ? 'insignia-activa' : 'insignia-inactiva' }}">{{ $usuario->estado ? 'Activo' : 'Inactivo' }}</span></td><td><a class="inline-flex items-center gap-2 font-medium text-principal hover:underline" href="{{ route('usuarios.edit', $usuario) }}">@include('partials.icono', ['nombre' => 'editar']) Editar</a></td></tr>@empty<tr><td colspan="5" class="py-10 text-center text-neutral-500">No hay usuarios registrados.</td></tr>@endforelse</tbody></table></div><div class="mt-5">{{ $usuarios->links() }}</div>
@endsection
