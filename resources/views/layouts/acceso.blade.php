<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Ingresar') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div
        class="fondo-login"
        style="background-image:
            radial-gradient(
                ellipse at center,
                rgba(0, 0, 0, 0.10) 0%,
                rgba(0, 0, 0, 0.55) 65%,
                rgba(0, 0, 0, 0.90) 100%
            ),
            url('{{ asset('img/logo.jpeg') }}');"
    >
        <main class="flex min-h-dvh items-center justify-center p-5 sm:p-8">
            @yield('contenido')
        </main>
    </div>
</body>
</html>
