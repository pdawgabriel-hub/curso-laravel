@props(['action', 'metodo' => 'POST', 'boton' => 'Guardar', 'cancelar' => null, 'ficheros' => false])

{{-- Tarjeta con el formulario: @csrf, el método (PUT, DELETE) y los botones ya están incluidos. --}}
<form method="POST" action="{{ $action }}" @if ($ficheros) enctype="multipart/form-data" @endif
      {{ $attributes->merge(['class' => 'rounded-xl bg-white shadow-sm ring-1 ring-slate-200']) }}>
    @csrf
    @if (strtoupper($metodo) !== 'POST')
        @method($metodo)
    @endif
    <div class="grid gap-5 p-5 sm:grid-cols-2">{{ $slot }}</div>
    <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-3">
        @if ($cancelar)
            <x-boton :href="$cancelar" variante="secundario">Cancelar</x-boton>
        @endif
        <x-boton>{{ $boton }}</x-boton>
    </div>
</form>
