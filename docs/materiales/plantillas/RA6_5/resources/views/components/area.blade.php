@props(['name', 'etiqueta', 'valor' => null, 'filas' => 3])

@php $conError = $errors->has($name); @endphp

<div class="sm:col-span-2">
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700">{{ $etiqueta }}</label>
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $filas }}"
              @if ($conError) aria-invalid="true" aria-describedby="{{ $name }}_error" @endif
              @class([
                  'mt-1 block w-full rounded-md border-0 px-3 py-2 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-inset',
                  'ring-slate-300 focus:ring-amber-500' => ! $conError,
                  'ring-red-400 focus:ring-red-500' => $conError,
              ])>{{ old($name, $valor) }}</textarea>
    @error($name)
        <p id="{{ $name }}_error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
