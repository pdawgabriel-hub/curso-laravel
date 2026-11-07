<x-layout :titulo="$cliente->nombre">
    <h1 class="mb-4 text-2xl font-semibold">{{ $cliente->nombreCompleto() }}</h1>

    <dl class="grid grid-cols-[10rem_1fr] gap-2 bg-white p-4 shadow">
        <dt class="font-medium">NIF</dt>        <dd>{{ $cliente->nif ?? '—' }}</dd>
        <dt class="font-medium">Dirección</dt>  <dd>{{ $cliente->direccion }}</dd>
        <dt class="font-medium">C. postal</dt>  <dd>{{ $cliente->codPostal ?? '—' }}</dd>
        <dt class="font-medium">Teléfono</dt>   <dd>{{ $cliente->telefono }}</dd>
        <dt class="font-medium">Alta de</dt>    <dd>{{ $cliente->creadoPor ?? '—' }}</dd>
    </dl>

    <div class="mt-4 flex gap-4">
        <a href="{{ route('clientes.edit', $cliente->id) }}" class="underline">Modificar</a>
        <a href="{{ route('clientes.index') }}" class="underline">Volver al listado</a>
    </div>
</x-layout>
