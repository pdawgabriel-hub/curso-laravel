@props(['tipo' => 'exito'])

<div role="{{ $tipo === 'error' ? 'alert' : 'status' }}"
     @class([
         'rounded-lg border px-4 py-3 text-sm',
         'border-emerald-200 bg-emerald-50 text-emerald-800' => $tipo === 'exito',
         'border-red-200 bg-red-50 text-red-800' => $tipo === 'error',
     ])>
    {{ $slot }}
</div>
