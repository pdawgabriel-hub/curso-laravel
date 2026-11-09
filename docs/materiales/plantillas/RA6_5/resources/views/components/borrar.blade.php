@props(['action', 'pregunta' => '¿Seguro que quieres borrarlo? No se puede deshacer.', 'texto' => 'Borrar', 'discreto' => false])

<form method="POST" action="{{ $action }}" data-pregunta="{{ $pregunta }}" onsubmit="return confirm(this.dataset.pregunta)" class="inline">
    @csrf
    @method('DELETE')
    @if ($discreto)
        <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">{{ $texto }}</button>
    @else
        <x-boton variante="peligro">{{ $texto }}</x-boton>
    @endif
</form>
