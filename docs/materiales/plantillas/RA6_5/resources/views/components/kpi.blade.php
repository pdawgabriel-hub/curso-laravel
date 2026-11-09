@props(['etiqueta', 'tono' => 'neutro'])

<div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $etiqueta }}</p>
    <p @class([
        'mt-2 text-2xl font-semibold tabular-nums',
        'text-slate-900' => $tono === 'neutro',
        'text-emerald-600' => $tono === 'positivo',
        'text-red-600' => $tono === 'negativo',
    ])>{{ $slot }}</p>
</div>
