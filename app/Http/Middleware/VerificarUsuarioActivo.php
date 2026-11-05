<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarUsuarioActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->estado) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['correo' => 'Tu cuenta está inactiva. Comunícate con el administrador.']);
        }

        return $next($request);
    }
}
