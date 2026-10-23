    <form method="GET" action="{{ $rutaFiltros }}" class="tarjeta mb-6 space-y-5" aria-label="Buscar registros">
        <div>
            <h2 class="font-semibold">Buscar registros</h2>
            <p class="mt-1 text-sm text-neutral-500">Puedes combinar los filtros. Las fechas corresponden al inicio de la labor.</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="etiqueta" for="trabajador">Trabajador</label>
                <input class="campo" id="trabajador" name="trabajador" type="search" maxlength="100" placeholder="Nombres, apellidos o DNI" value="{{ $filtros['trabajador'] ?? '' }}">
            </div>
            <div>
                <label class="etiqueta" for="fecha_desde">Desde</label>
                <input class="campo" id="fecha_desde" name="fecha_desde" type="date" value="{{ $filtros['fecha_desde'] ?? '' }}">
            </div>
            <div>
                <label class="etiqueta" for="fecha_hasta">Hasta</label>
                <input class="campo" id="fecha_hasta" name="fecha_hasta" type="date" value="{{ $filtros['fecha_hasta'] ?? '' }}">
            </div>
            <div>
                <label class="etiqueta" for="id_maquina">Máquina</label>
                <select class="campo" id="id_maquina" name="id_maquina">
                    <option value="">Todas las máquinas</option>
                    @foreach ($maquinas as $maquina)
                        <option value="{{ $maquina->id_maquina }}" @selected((string) ($filtros['id_maquina'] ?? '') === (string) $maquina->id_maquina)>{{ $maquina->nombre }}{{ $maquina->estado ? '' : ' (inactiva)' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="etiqueta" for="estado">Estado de la labor</label>
                <select class="campo" id="estado" name="estado">
                    <option value="">Todos los estados</option>
                    <option value="En curso" @selected(($filtros['estado'] ?? '') === 'En curso')>En curso</option>
                    <option value="Finalizado" @selected(($filtros['estado'] ?? '') === 'Finalizado')>Finalizado</option>
                </select>
            </div>
            <div>
                <label class="etiqueta" for="revision">Revisión del supervisor</label>
                <select class="campo" id="revision" name="revision">
                    <option value="">Todas las revisiones</option>
                    <option value="Pendiente" @selected(($filtros['revision'] ?? '') === 'Pendiente')>Pendiente de revisión</option>
                    <option value="Revisado" @selected(($filtros['revision'] ?? '') === 'Revisado')>Revisado</option>
                </select>
                <p class="mt-1 text-xs text-neutral-500">Este filtro consulta únicamente labores finalizadas.</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <button class="boton" type="submit">@include('partials.icono', ['nombre' => 'buscar']) Buscar</button>
            <a class="boton-secundario gap-2" href="{{ $rutaFiltros }}">@include('partials.icono', ['nombre' => 'cerrar']) Limpiar filtros</a>
        </div>
    </form>
