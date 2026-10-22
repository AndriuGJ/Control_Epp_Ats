@if (session('estado'))
    <div role="status" class="mb-6 rounded-xl border border-teal-200 bg-teal-50 px-5 py-4 text-sm text-teal-900">{{ session('estado') }}</div>
@endif
@if ($errors->any())
    <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-900">
        <p class="font-semibold">Revisa la información ingresada:</p>
        <ul class="mt-2 list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
