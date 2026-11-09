@props(['porcentaje' => 0])

<div class="h-2 w-full overflow-hidden rounded-full bg-slate-200" role="progressbar"
     aria-valuenow="{{ $porcentaje }}" aria-valuemin="0" aria-valuemax="100">
    <div class="h-full rounded-full bg-amber-500" style="width: {{ min(max($porcentaje, 0), 100) }}%"></div>
</div>
