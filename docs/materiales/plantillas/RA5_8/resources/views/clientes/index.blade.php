<x-layout titulo="Clientes">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Clientes</h1>
        @auth
            <a href="{{ route('clientes.create') }}" class="rounded bg-slate-800 px-3 py-1.5 text-white">Nuevo cliente</a>
        @endauth
    </div>

    <table class="w-full bg-white text-left text-sm shadow">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-3 py-2">Nombre</th>
                <th class="px-3 py-2">NIF</th>
                <th class="px-3 py-2">Teléfono</th>
                <th class="px-3 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($clientes as $cliente)
                <tr class="border-t">
                    <td class="px-3 py-2">{{ $cliente->nombreCompleto() }}</td>
                    <td class="px-3 py-2">{{ $cliente->nif }}</td>
                    <td class="px-3 py-2">{{ $cliente->telefono }}</td>
                    <td class="px-3 py-2 text-right">
                        <a href="{{ route('clientes.show', $cliente->id) }}" class="underline">Ver</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-3 py-2">No hay clientes.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-layout>
