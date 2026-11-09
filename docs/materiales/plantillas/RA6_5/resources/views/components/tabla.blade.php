@props(['columnas' => [], 'vacio' => 'No hay registros todavía.'])

{{-- Las filas van en el contenido: <tr><td>...</td></tr>. Sin filas, se muestra el mensaje $vacio. --}}
<div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm [&_td]:whitespace-nowrap [&_td]:px-4 [&_td]:py-3 [&_tbody_tr:hover]:bg-slate-50">
            <thead class="bg-slate-50">
                <tr>
                    @foreach ($columnas as $columna)
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $columna }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @if ($slot->isEmpty())
                    <tr>
                        <td colspan="{{ max(count($columnas), 1) }}" class="!py-10 text-center text-slate-500">{{ $vacio }}</td>
                    </tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>
    </div>
    @isset($pie)
        <div class="border-t border-slate-200 px-4 py-3">{{ $pie }}</div>
    @endisset
</div>
