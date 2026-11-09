@props(['name', 'etiqueta', 'type' => 'text', 'valor' => null, 'ancho' => false])

@php
    $clave = str_replace(['[', ']'], ['.', ''], $name);   // lineas[0][horas] → lineas.0.horas
    $id = str_replace('.', '_', $clave);
    $conError = $errors->has($clave);
@endphp

<div @class(['sm:col-span-2' => $ancho])>
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700">{{ $etiqueta }}</label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($clave, $valor) }}"
           @if ($conError) aria-invalid="true" aria-describedby="{{ $id }}_error" @endif
           {{ $attributes->class([
               'mt-1 block w-full rounded-md border-0 px-3 py-2 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-inset',
               'ring-slate-300 focus:ring-amber-500' => ! $conError,
               'ring-red-400 focus:ring-red-500' => $conError,
           ]) }}>
    @error($clave)
        <p id="{{ $id }}_error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
