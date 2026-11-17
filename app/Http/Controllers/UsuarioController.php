<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarUsuarioRequest;
use App\Models\Maquina;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(): View
    {
        return view('usuarios.index', ['usuarios' => User::with('roles')->orderBy('apellidos')->orderBy('id')->paginate(15)]);
    }

    public function create(): View
    {
        return view('usuarios.formulario', ['usuario' => new User(['estado' => true]), 'maquinas' => Maquina::orderBy('nombre')->get()]);
    }

    public function store(GuardarUsuarioRequest $request): RedirectResponse
    {
        $this->guardar($request, new User);

        return to_route('usuarios.index')->with('estado', 'Usuario registrado correctamente.');
    }

    public function edit(User $usuario): View
    {
        $usuario->load(['roles', 'maquinas']);

        return view('usuarios.formulario', ['usuario' => $usuario, 'maquinas' => Maquina::orderBy('nombre')->get()]);
    }

    public function update(GuardarUsuarioRequest $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user()) && (! $request->boolean('estado') || $request->validated('rol') !== 'administrador')) {
            throw ValidationException::withMessages(['rol' => 'No puedes desactivar tu propia cuenta ni retirar tu rol de administrador.']);
        }
        $this->guardar($request, $usuario);

        return to_route('usuarios.index')->with('estado', 'Usuario actualizado correctamente.');
    }

    private function guardar(GuardarUsuarioRequest $request, User $usuario): void
    {
        if ($request->validated('rol') !== 'operador' && $request->input('maquinas', []) !== []) {
            throw ValidationException::withMessages(['maquinas' => 'Las autorizaciones de máquinas se asignan únicamente a operadores.']);
        }
        DB::transaction(function () use ($request, $usuario): void {
            $datos = $request->safe()->only(['nombres', 'apellidos', 'dni', 'correo', 'contrasena', 'estado']);
            if (empty($datos['contrasena'])) {
                unset($datos['contrasena']);
            }
            $usuario->fill($datos)->save();
            $usuario->syncRoles([$request->validated('rol')]);
            $usuario->maquinas()->sync($request->validated('rol') === 'operador' ? $request->input('maquinas', []) : []);
        });
    }
}
