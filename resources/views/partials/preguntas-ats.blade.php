@foreach ($preguntas as $pregunta)
    <fieldset class="space-y-3">
        <legend class="text-sm font-medium text-neutral-700">{{ $loop->iteration }}. {{ $pregunta->pregunta }}</legend>
        <div class="flex flex-wrap gap-3">
            @foreach (['Sí', 'No'] as $opcion)
                <label class="inline-flex cursor-pointer items-center gap-3 rounded-md border border-neutral-300 bg-white px-5 py-3 text-sm hover:bg-neutral-50">
                    <input type="radio" class="size-4 accent-acento" name="respuestas[{{ $pregunta->getKey() }}]"
                        value="{{ $opcion }}" @checked(old('respuestas.'.$pregunta->getKey()) === $opcion) required>
                    {{ $opcion }}
                </label>
            @endforeach
        </div>
    </fieldset>
@endforeach
