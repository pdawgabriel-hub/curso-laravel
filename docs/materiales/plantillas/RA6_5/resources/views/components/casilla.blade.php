@props(['name', 'etiqueta', 'marcado' => false])

{{-- El campo oculto envía 0 cuando la casilla no está marcada. --}}
<div class="flex items-center gap-2">
    <input type="hidden" name="{{ $name }}" value="0">
    <input id="{{ $name }}" name="{{ $name }}" type="checkbox" value="1" @checked(old($name, $marcado))
           class="size-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500">
    <label for="{{ $name }}" class="text-sm text-slate-700">{{ $etiqueta }}</label>
</div>
