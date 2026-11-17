<?php

namespace App\Http\Controllers;

use App\Http\Requests\IniciarSesionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SesionController extends Controller
{
    public function create(): View
    {
        return view('auth.ingresar');
    }

    public function store(IniciarSesionRequest $request): RedirectResponse
    {
        $clave = Str::transliterate($request->string('correo').'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($clave, 5)) {
            throw ValidationException::withMessages(['correo' => 'Demasiados intentos. Intenta nuevamente en '.RateLimiter::availableIn($clave).' segundos.']);
        }
        if (! Auth::attempt(['correo' => $request->validated('correo'), 'password' => $request->validated('contrasena'), 'estado' => true], $request->boolean('recordarme'))) {
            RateLimiter::hit($clave, 60);
            throw ValidationException::withMessages(['correo' => 'Los datos de acceso no son válidos o la cuenta está inactiva.']);
        }
        RateLimiter::clear($clave);
        $request->session()->regenerate();

        return redirect()->intended(route('panel'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}
