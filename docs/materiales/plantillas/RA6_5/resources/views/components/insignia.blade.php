@props(['color' => 'gris'])

<span @class([
    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
    'bg-slate-50 text-slate-700 ring-slate-200' => $color === 'gris',
    'bg-emerald-50 text-emerald-700 ring-emerald-200' => $color === 'verde',
    'bg-amber-50 text-amber-800 ring-amber-200' => $color === 'ambar',
    'bg-red-50 text-red-700 ring-red-200' => $color === 'rojo',
    'bg-sky-50 text-sky-700 ring-sky-200' => $color === 'azul',
])>{{ $slot }}</span>
