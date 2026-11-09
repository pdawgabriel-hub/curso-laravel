# 6.11. Ejercicios: partes e ingresos
<!-- {% raw %} -->

**Referencia teórica:** [Ejercicios de relaciones](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/085_ejerciciosRelaciones.html) · [Laravel: Validating arrays](https://laravel.com/docs/13.x/validation#validating-arrays)

El trabajo y el dinero de una obra: partes de trabajo con sus líneas, partes de proveedores y especialistas, y cobros. Todo se registra desde el detalle de la obra. Con esto, la aplicación ya cubre el flujo completo; los resultados se calculan en [6.13](./RA6_13_calculos.md).

## Conceptos clave

- **Varias filas en un formulario:** `lineas[0][trabajador_id]`, `lineas[0][horas]`, `lineas[1][trabajador_id]`… llegan como un array. Se validan con `*`: `'lineas.*.horas' => 'required|numeric|between:0,24'`, y el error de la fila 0 está en `lineas.0.horas`. `<x-campo>` y `<x-selector>` ya hacen esa conversión.
- **Cabecera y líneas juntas:** `$parte = $obra->partesTrabajo()->create(...)` y, para cada fila, `$parte->lineas()->create(...)`. La tarifa se copia sola, por el evento de [6.2](./RA6_2_modelos.md).
- **Filas vacías:** si el formulario ofrece cuatro filas y se rellenan dos, las otras llegan vacías. Quítalas antes de validar, por ejemplo en `prepareForValidation()` del _Request_.

## Ejercicios

1. **El detalle de la obra.** Una `<x-tarjeta>` para cada cosa que cuelga de la obra, con su botón de alta en `acciones`: partes de trabajo, partes externos e ingresos. Cada fila lleva un `<x-borrar>`.

2. **Parte de trabajo.**

    | Método | URL | Nombre |
    |---|---|---|
    | GET | `/obras/{obra}/partes-trabajo/create` | `obras.partes-trabajo.create` |
    | POST | `/obras/{obra}/partes-trabajo` | `obras.partes-trabajo.store` |
    | DELETE | `/obras/{obra}/partes-trabajo/{parte}` | `obras.partes-trabajo.destroy` |

    El formulario lleva `fecha`, `descripcion` y cuatro filas con `lineas[i][trabajador_id]` (selector) y `lineas[i][horas]`. Validación: `descripcion` obligatoria; cada `trabajador_id` existe; cada `horas` entre 0 y 24. El propietario es el usuario conectado. Se vuelve al detalle de la obra, que lista los partes con su fecha, su descripción y sus trabajadores.

3. **Partes externos.** Igual para `partes-proveedor` y `partes-especialista`, con nombres `obras.partes-proveedor.*` y `obras.partes-especialista.*`, y los campos `proveedor_id` o `especialista_id` (selector), `fecha`, `descripcion`, `documento`, `importe` (> 0) y `estado_pago` (selector, `pendiente` o `pagado`). El detalle de la obra los lista con quién, cuándo y cuánto.

4. **Ingresos.** Desde la obra, con los nombres `obras.ingresos.create`, `obras.ingresos.store` y `obras.ingresos.destroy`, y las mismas URL que los partes. Campos: `importe`, `tipo`, `fecha`, `documento` y `num_documento`. Validación: `importe` > 0; si se envía `cliente_id`, debe ser el de la obra. El detalle de la obra los lista con su código.

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

Resultado esperado: `Tests: 7 passed`.

## Git

Rama `RA6_11`. Commits: `feat: partes de trabajo`, `feat: partes de proveedores y especialistas`, `feat: ingresos`.

---

**Siguiente:** [6.12. Transacciones y bloqueos](./RA6_12_transacciones.md)
<!-- {% endraw %} -->
