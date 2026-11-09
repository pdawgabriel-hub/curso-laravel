@props(['href' => null, 'variante' => 'primario', 'type' => 'submit'])

@php
    $clases = 'inline-flex items-center justify-center gap-2 rounded-md px-3.5 py-2 text-sm font-semibold shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 ' . match ($variante) {
        'secundario' => 'bg-white text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus-visible:ring-slate-400',
        'peligro' => 'bg-red-600 text-white hover:bg-red-500 focus-visible:ring-red-500',
        default => 'bg-amber-500 text-slate-900 hover:bg-amber-400 focus-visible:ring-amber-500',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $clases]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $clases]) }}>{{ $slot }}</button>
@endif
