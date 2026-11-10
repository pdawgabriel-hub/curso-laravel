@props(['pestanas' => [], 'activa' => null, 'parametro' => 'pestana'])

{{-- Cada pestaña es un enlace a la misma página con ?pestana=clave: no hace falta JavaScript y cada una tiene su URL. --}}
<nav aria-label="Pestañas" {{ $attributes->merge(['class' => 'border-b border-slate-200']) }}>
    <ul class="-mb-px flex gap-6 overflow-x-auto">
        @foreach ($pestanas as $clave => $texto)
            <li class="shrink-0">
                <a href="{{ request()->fullUrlWithQuery([$parametro => $clave, 'page' => null]) }}"
                   @if ($clave === $activa) aria-current="page" @endif
                   @class([
                       'inline-block border-b-2 px-1 pb-3 pt-1 text-sm font-medium transition',
                       'border-amber-500 text-slate-900' => $clave === $activa,
                       'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' => $clave !== $activa,
                   ])>{{ $texto }}</a>
            </li>
        @endforeach
    </ul>
</nav>
