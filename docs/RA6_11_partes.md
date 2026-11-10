# 6.11. Ejercicios: partes e ingresos
<!-- {% raw %} -->

**Referencia teórica:** [Ejercicios de relaciones](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/085_ejerciciosRelaciones.html) · [Laravel: Validating arrays](https://laravel.com/docs/13.x/validation#validating-arrays)

El trabajo y el dinero de una obra: partes de trabajo con sus líneas, partes de proveedores y especialistas, y cobros. Todo se registra desde el detalle de la obra, que pasa a organizarse en **pestañas**, como el formulario de la obra en Odoo. Con esto, la aplicación ya cubre el flujo completo; los resultados se calculan en [6.13](./RA6_13_calculos.md).

## Conceptos clave

- **Varias filas en un formulario:** `lineas[0][trabajador_id]`, `lineas[0][horas]`, `lineas[1][trabajador_id]`… llegan como un array. Se validan con `*`: `'lineas.*.horas' => 'required|numeric|between:0,24'`, y el error de la fila 0 está en `lineas.0.horas`. `<x-campo>` y `<x-selector>` ya hacen esa conversión.
- **Cabecera y líneas juntas:** `$parte = $obra->partesTrabajo()->create(...)` y, para cada fila, `$parte->lineas()->create(...)`. La tarifa se copia sola, por el evento de [6.2](./RA6_2_modelos.md).
- **Pestañas con enlaces:** `<x-pestanas>` ([6.5](./RA6_5_interfaz.md)) enlaza a la misma página con `?pestana=partes-trabajo`. El controlador lee `$request->query('pestana')`, comprueba que es una de las conocidas (si no, usa `resumen`) y carga solo lo que necesita esa pestaña. No hace falta JavaScript, y cada pestaña tiene su URL.
- **Volver a la pestaña:** al guardar o borrar un parte, se redirige a su pestaña: `redirect()->route('obras.show', [$obra->id, 'pestana' => 'partes-trabajo'])`. Los parámetros que no están en la ruta se añaden como _query string_.
- **Filas vacías:** si el formulario ofrece cuatro filas y se rellenan dos, las otras llegan vacías. Quítalas antes de validar, por ejemplo en `prepareForValidation()` del _Request_.

## El ejemplo: las pestañas de la obra

En `ObrasController`, las pestañas y lo que carga cada una:

```php
public const PESTANAS = [
    'resumen' => 'Resumen',
    'partes-trabajo' => 'Partes de trabajo',
    'partes-proveedor' => 'Proveedores',
    'partes-especialista' => 'Especialistas',
    'ingresos' => 'Ingresos',
];

public function show(Request $request, $id)
{
    $obra = Obra::findOrFail($id);
    $pestanas = self::PESTANAS;
    $pestana = in_array($request->query('pestana'), array_keys($pestanas), true) ? $request->query('pestana') : 'resumen';

    $obra->load(match ($pestana) {
        'partes-trabajo' => ['partesTrabajo.lineas.trabajador'],
        'partes-proveedor' => ['partesProveedor.proveedor'],
        'partes-especialista' => ['partesEspecialista.especialista'],
        'ingresos' => ['ingresos'],
        default => ['cliente', 'presupuestos'],
    });

    return view('obras.show', compact('obra', 'pestanas', 'pestana'));
}
```

`resources/views/obras/show.blade.php` pinta la cabecera, las pestañas y la vista parcial de la activa:

```blade
<x-layout :titulo="$obra->codigo">
    <x-cabecera :titulo="$obra->descripcion" :subtitulo="$obra->codigo">
        <x-boton :href="route('obras.edit', $obra->id)" variante="secundario">Editar</x-boton>
    </x-cabecera>

    <x-pestanas :pestanas="$pestanas" :activa="$pestana" />

    @include('obras.pestanas.' . $pestana)
</x-layout>
```

Cada pestaña es un fichero de `resources/views/obras/pestanas/`. Lo que ya mostraba el detalle de la obra (sus datos y sus presupuestos de [6.10](./RA6_10_presupuestos.md)) pasa a `resumen.blade.php`. Así queda `partes-trabajo.blade.php`:

```blade
<x-tarjeta titulo="Partes de trabajo">
    <x-slot:acciones>
        <x-boton :href="route('obras.partes-trabajo.create', $obra->id)">Nuevo parte</x-boton>
    </x-slot:acciones>

    <x-tabla :columnas="['Fecha', 'Descripción', 'Trabajadores', 'Horas', 'Coste', '']" vacio="No hay partes de trabajo.">
        @foreach ($obra->partesTrabajo as $parte)
            <tr>
                <td>{{ $parte->fecha?->format('d/m/Y') }}</td>
                <td>{{ $parte->descripcion }}</td>
                <td>{{ $parte->lineas->map(fn ($l) => $l->trabajador->nombre_completo)->join(', ') }}</td>
                <td class="text-right">{{ \App\Support\Formato::n($parte->lineas->sum('horas')) }}</td>
                <td class="text-right">{{ \App\Support\Formato::n($parte->lineas->sum(fn ($l) => $l->horas * $l->coste_hora)) }}</td>
                <td class="text-right">
                    <x-borrar :action="route('obras.partes-trabajo.destroy', [$obra->id, $parte->id])" pregunta="¿Borrar el parte?" discreto />
                </td>
            </tr>
        @endforeach
    </x-tabla>
</x-tarjeta>
```

## Ejercicios

1. **Las pestañas de la obra.** Añade el ejemplo: `PESTANAS`, el nuevo `show`, `obras/show.blade.php` y la carpeta `obras/pestanas/` con un fichero por pestaña. Columnas de cada tabla:

    | Pestaña | Columnas | Botón de alta |
    |:--|:--|:--|
    | `resumen` | Datos de la obra y sus presupuestos, como hasta ahora | |
    | `partes-trabajo` | `Fecha`, `Descripción`, `Trabajadores`, `Horas`, `Coste` | *Nuevo parte* |
    | `partes-proveedor` | `Fecha`, `Proveedor`, `Documento`, `Importe`, `Estado de pago` | *Nuevo parte de proveedor* |
    | `partes-especialista` | `Fecha`, `Especialista`, `Documento`, `Importe`, `Estado de pago` | *Nuevo parte de especialista* |
    | `ingresos` | `Código`, `Fecha`, `Tipo`, `Importe` | *Nuevo ingreso* |

    Cada fila lleva un `<x-borrar>`. Una pestaña que no existe (`?pestana=otra`) muestra el resumen.

2. **Parte de trabajo.**

    | Método | URL | Nombre |
    |---|---|---|
    | GET | `/obras/{obra}/partes-trabajo/create` | `obras.partes-trabajo.create` |
    | POST | `/obras/{obra}/partes-trabajo` | `obras.partes-trabajo.store` |
    | DELETE | `/obras/{obra}/partes-trabajo/{parte}` | `obras.partes-trabajo.destroy` |

    El formulario lleva `fecha`, `descripcion` y cuatro filas con `lineas[i][trabajador_id]` (selector) y `lineas[i][horas]`. Validación: `descripcion` obligatoria; cada `trabajador_id` existe; cada `horas` entre 0 y 24. El propietario es el usuario conectado. Al guardar y al borrar se vuelve a la pestaña *Partes de trabajo*.

3. **Partes externos.** Igual para `partes-proveedor` y `partes-especialista`, con nombres `obras.partes-proveedor.*` y `obras.partes-especialista.*`, y los campos `proveedor_id` o `especialista_id` (selector), `fecha`, `descripcion`, `documento`, `importe` (> 0) y `estado_pago` (selector, `pendiente` o `pagado`). Al guardar y al borrar se vuelve a su pestaña.

4. **Ingresos.** Desde la obra, con los nombres `obras.ingresos.create`, `obras.ingresos.store` y `obras.ingresos.destroy`, y las mismas URL que los partes. Campos: `importe`, `tipo`, `fecha`, `documento` y `num_documento`. Validación: `importe` > 0; si se envía `cliente_id`, debe ser el de la obra. Al guardar y al borrar se vuelve a la pestaña *Ingresos*.

5. **Lo de otra obra, 404.** Borrar un parte o un ingreso con la URL de otra obra responde 404.

6. **Reglas de borrado.** Un trabajador con líneas, o un proveedor o especialista con partes, no se borra: vuelve atrás con `error`.

7. **Investiga.** Responde en tu _pull request_:
    - Cambia la tarifa de un trabajador con partes. ¿Qué cambia y qué no?
    - `ingresos.obra_id` usa `restrictOnDelete`, y `partes_trabajo.obra_id`, `cascadeOnDelete`. Intenta borrar en `tinker` una obra con ingresos. ¿Qué ocurre? ¿Por qué un parte puede desaparecer con su obra y un cobro no?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_11_PartesTest.php tests/Feature/
php artisan test --filter=RA6_11
```

Resultado esperado: `Tests: 9 passed`.

## Git

Rama `RA6_11`. Commits: `feat: pestañas en el detalle de la obra`, `feat: partes de trabajo`, `feat: partes de proveedores y especialistas`, `feat: ingresos`.

---

**Siguiente:** [6.12. Transacciones y bloqueos](./RA6_12_transacciones.md)
<!-- {% endraw %} -->
