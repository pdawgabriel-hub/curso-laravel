{{-- Campos compartidos por create y edit. $cliente es null al crear. --}}
<div class="grid gap-4 bg-white p-4 shadow sm:grid-cols-2">
    @foreach ([
        'nombre' => ['Nombre', $cliente?->nombre],
        'apellidos' => ['Apellidos', $cliente?->apellidos],
        'nif' => ['NIF', $cliente?->nif],
        'direccion' => ['Dirección', $cliente?->direccion],
        'cod_postal' => ['Código postal', $cliente?->codPostal],
        'telefono' => ['Teléfono', $cliente?->telefono],
    ] as $campo => [$etiqueta, $valor])
        <label class="block">
            <span class="text-sm font-medium">{{ $etiqueta }}</span>
            <input type="text" name="{{ $campo }}" value="{{ old($campo, $valor) }}"
                   @class(['mt-1 w-full rounded border px-2 py-1', 'border-red-500' => $errors->has($campo)])>
            @error($campo)
                <span class="text-sm text-red-600">{{ $message }}</span>
            @enderror
        </label>
    @endforeach
</div>
