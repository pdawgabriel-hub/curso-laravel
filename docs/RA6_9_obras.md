# 6.9. Ejercicios: obras, ficha de Gastos y faltas
<!-- {% raw %} -->

**Referencia teórica:** [Ejercicios de relaciones](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/085_ejerciciosRelaciones.html) · [Insertar y actualizar con relaciones](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/084_DML_Relaciones.html) · [Laravel: Route model binding](https://laravel.com/docs/13.x/routing#route-model-binding)

Las primeras pantallas que usan relaciones: la obra muestra su cliente y su ficha de Gastos, y el trabajador, sus faltas.

## Conceptos clave

- **Selector de otra tabla:** `<x-selector name="cliente_id" :opciones="$clientes->pluck('nombre_completo', 'id')" />`. La validación comprueba que existe: `'cliente_id' => 'nullable|exists:clientes,id'`.
- **Recursos anidados:** una falta se crea desde su trabajador, con la URL `/trabajadores/{trabajador}/faltas`. Con _route model binding_ (`Trabajador $trabajador` en el método), Laravel busca el registro y responde 404 si no existe.
- **El padre correcto:** en `DELETE /trabajadores/3/faltas/7`, la falta 7 debe ser del trabajador 3; si no, 404: `abort_if($falta->trabajador_id !== $trabajador->id, 404)`.
- **Cargar relaciones de una vez:** `Obra::with('cliente')->paginate(15)` evita una consulta por fila ([6.8](./RA6_8_relaciones.md)).

## Ejercicios

1. **Obras.** `ObrasController` usa el modelo, con las mismas siete rutas que los maestros ([6.6](./RA6_6_maestros.md)). Elimina `$arrayObras` y la búsqueda del cliente con `Cliente::find()`: ahora es `$obra->cliente`.
    - Listado con las columnas `Código`, `Descripción` y `Cliente`, paginado de 15 en 15.
    - Alta y modificación con `ObraRequest`:

        | Campo | Reglas |
        |:--|:--|
        | `descripcion` | `required`, `string`, `max:255` |
        | `cliente_id` | `nullable`, `exists:clientes,id` (selector) |
    - **`store` crea la obra y su ficha de Gastos:** `$obra->gasto()->create()`. En [6.12](./RA6_12_transacciones.md) se hará en una transacción.
    - Detalle: código, descripción y cliente; enlaces a modificar y a su ficha de Gastos, y `<x-borrar>`. De momento no muestra el total ni el estado de pago: llegan, calculados, en [6.13](./RA6_13_calculos.md).

2. **La ficha de Gastos.** No hay alta ni borrado, porque la ficha nace y muere con su obra:

    | Método | URL | Nombre |
    |---|---|---|
    | GET | `/gastos` | `gastos.index` |
    | GET | `/gastos/show/{gasto}` | `gastos.show` |
    | GET | `/gastos/edit/{gasto}` | `gastos.edit` |
    | PUT | `/gastos/update/{gasto}` | `gastos.update` |

    - Listado con las columnas `Código`, `Obra` y `Cliente`. En el menú aparece como *Seguimiento de gastos*.
    - Detalle con la dirección y las fechas, y un enlace a su obra.
    - El formulario edita `direccion_obra`, las tres fechas y, con `<x-casilla>`, los tres booleanos, con `GastoRequest`:

        | Campo | Reglas |
        |:--|:--|
        | `direccion_obra` | `nullable`, `string`, `max:255` |
        | `inicio_obra`, `solicitud_obra` | `nullable`, `date` |
        | `fin_obra` | `nullable`, `date`, `after_or_equal:inicio_obra` |
        | `mandante`, `gestion_licencia`, `pago_icio` | `boolean` |
        | `presupuesto_id` | `nullable`, `exists:presupuestos,id`; en `after()`, debe ser de la misma obra |

        `obra_id` no está entre las reglas: la ficha no cambia de obra.

3. **Faltas.** Se crean desde el trabajador:

    | Método | URL | Nombre |
    |---|---|---|
    | GET | `/trabajadores/{trabajador}/faltas/create` | `trabajadores.faltas.create` |
    | POST | `/trabajadores/{trabajador}/faltas` | `trabajadores.faltas.store` |
    | DELETE | `/trabajadores/{trabajador}/faltas/{falta}` | `trabajadores.faltas.destroy` |
    | GET | `/faltas` | `faltas.index` |

    - Formulario con `FaltaRequest`:

        | Campo | Reglas |
        |:--|:--|
        | `fecha_inicio` | `required`, `date` |
        | `fecha_fin` | `required`, `date`, `after_or_equal:fecha_inicio` |
        | `tipo` | `nullable`, `in:vacaciones,baja_medica,personal,injustificada,otro` (selector) |
        | `motivo` | `nullable`, `string` |

        `user_id` y `trabajador_id` no están entre las reglas: salen del usuario conectado y de la URL.
    - El propietario es el usuario conectado; el `user_id` **nunca** sale del formulario ([6.8](./RA6_8_relaciones.md#propietario)).
    - Al guardar o borrar se vuelve al detalle del trabajador, que lista sus faltas en una tabla con el texto del tipo (`Vacaciones`, `Baja médica`, `Personal`, `Injustificada`, `Otro`), las fechas, los días y un `<x-borrar>`.
    - `/faltas`: todas, con las columnas `Trabajador`, `Tipo`, `Desde`, `Hasta` y `Días`.

4. **Investiga.** Responde en tu _pull request_:
    - Con `DB::enableQueryLog()`, carga el listado de obras con y sin `with('cliente')`. ¿Cuántas consultas lanza cada uno?
    - Añade `<input name="user_id" value="1">` al formulario de faltas con las herramientas de desarrollo. ¿Qué `user_id` se guarda? ¿Por qué?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_9_ObrasTest.php tests/Feature/
php artisan test --filter=RA6_9
```

Resultado esperado: `Tests: 9 passed`.

## Git

Rama `RA6_9`. Commits: `feat: obras con su ficha de Gastos`, `feat: pantallas de la ficha de Gastos`, `feat: faltas de los trabajadores`.

---

**Siguiente:** [6.10. Ejercicios: presupuestos](./RA6_10_presupuestos.md)
<!-- {% endraw %} -->
