<x-layout titulo="Modificar cliente">
    <h1 class="mb-4 text-2xl font-semibold">Modificar cliente: {{ $cliente['nombre'] }} {{ $cliente['apellidos'] }}</h1>

    <form method="POST" action="{{ route('clientes.update', $id) }}">
        @csrf
        @method('PUT')
        @include('clientes._formulario')
        <button type="submit" class="mt-4 rounded bg-slate-800 px-3 py-1.5 text-white">Guardar cambios</button>
    </form>
</x-layout>
