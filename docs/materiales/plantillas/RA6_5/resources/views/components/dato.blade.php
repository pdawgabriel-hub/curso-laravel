@props(['etiqueta'])

{{-- Un par etiqueta/valor; se agrupan dentro de <dl class="grid gap-4 sm:grid-cols-2">. --}}
<div>
    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $etiqueta }}</dt>
    <dd class="mt-1 text-sm text-slate-900">{{ $slot->isEmpty() ? '—' : $slot }}</dd>
</div>
