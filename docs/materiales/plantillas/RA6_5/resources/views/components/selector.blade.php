@props(['name', 'etiqueta', 'opciones' => [], 'valor' => null, 'vacio' => '— Selecciona —'])

{{-- $opciones: [valor => texto], por ejemplo $clientes->pluck('nombre_completo', 'id'). --}}
@php
    $clave = str_replace(['[', ']'], ['.', ''], $name);
    $id = str_replace('.', '_', $clave);
    $conError = $errors->has($clave);
    $actual = (string) old($clave, $valor);
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700">{{ $etiqueta }}</label>
    <select id="{{ $id }}" name="{{ $name }}"
            @if ($conError) aria-invalid="true" aria-describedby="{{ $id }}_error" @endif
            @class([
                'mt-1 block w-full rounded-md border-0 bg-white px-3 py-2 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-inset',
                'ring-slate-300 focus:ring-amber-500' => ! $conError,
                'ring-red-400 focus:ring-red-500' => $conError,
            ])>
        @if ($vacio !== null)
            <option value="">{{ $vacio }}</option>
        @endif
        @foreach ($opciones as $valorOpcion => $texto)
            <option value="{{ $valorOpcion }}" @selected($actual === (string) $valorOpcion)>{{ $texto }}</option>
        @endforeach
    </select>
    @error($clave)
        <p id="{{ $id }}_error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
