<x-layout titulo="Nuevo cliente">
    <h1 class="mb-4 text-2xl font-semibold">Nuevo cliente</h1>

    <form method="POST" action="{{ route('clientes.store') }}">
        @csrf
        @include('clientes._formulario', ['cliente' => null])
        <button type="submit" class="mt-4 rounded bg-slate-800 px-3 py-1.5 text-white">Guardar</button>
    </form>
</x-layout>
