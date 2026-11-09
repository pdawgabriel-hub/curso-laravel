@props(['titulo' => null])

<section {{ $attributes->merge(['class' => 'rounded-xl bg-white shadow-sm ring-1 ring-slate-200']) }}>
    @if ($titulo || isset($acciones))
        <header class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-3">
            <h2 class="text-sm font-semibold text-slate-900">{{ $titulo }}</h2>
            @isset($acciones)
                <div class="flex items-center gap-2">{{ $acciones }}</div>
            @endisset
        </header>
    @endif
    <div class="p-5">{{ $slot }}</div>
</section>
