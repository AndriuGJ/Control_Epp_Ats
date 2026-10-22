<svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
    fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"
    stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($nombre)
        @case('panel')
            <rect x="3" y="3" width="7" height="7" rx="1" />
            <rect x="14" y="3" width="7" height="7" rx="1" />
            <rect x="3" y="14" width="7" height="7" rx="1" />
            <rect x="14" y="14" width="7" height="7" rx="1" />
            @break
        @case('usuarios')
            <circle cx="9" cy="8" r="3" />
            <path d="M3 21v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6M18 15a5 5 0 0 1 3 4v2" />
            @break
        @case('maquinas')
            <path d="M3 16v-4h3l2-5h6v9M7 12h7M17 3v15h4M14 10h3" />
            <circle cx="6" cy="18" r="2" />
            <circle cx="13" cy="18" r="2" />
            @break
        @case('reportes')
            <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9zM14 3v6h6M8 17v-3M12 17v-5M16 17v-2" />
            @break
        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16" />
            @break
        @case('cerrar')
            <path d="m6 6 12 12M18 6 6 18" />
            @break
        @case('salir')
            <path d="M9 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4M9 12h12m-5-5 5 5-5 5" />
            @break
        @case('agregar')
            <path d="M12 5v14M5 12h14" />
            @break
        @case('editar')
            <path d="m15 5 4 4M4 20l5-1L20 8a2.8 2.8 0 0 0-4-4L5 15z" />
            @break
        @case('volver')
            <path d="M20 12H4m7-7-7 7 7 7" />
            @break
        @case('ingresar')
            <path d="M15 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4M3 12h12m-5-5 5 5-5 5" />
            @break
        @case('guardar')
            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h12l4 4v12a2 2 0 0 1-2 2zM7 3v6h10V3M7 21v-8h10v8" />
            @break
    @endswitch
</svg>
