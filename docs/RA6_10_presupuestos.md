# 6.10. Ejercicios: presupuestos
<!-- {% raw %} -->

**Referencia teórica:** [Muchos a muchos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/083_relaciones_muchosAmuchos.html) · [Insertar y actualizar con relaciones](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/084_DML_Relaciones.html) · [Laravel: Validation after hooks](https://laravel.com/docs/13.x/validation#performing-additional-validation)

Las pantallas del presupuesto: sus líneas, sus proveedores y especialistas, y la vinculación con la obra.

## Conceptos clave

- **Casillas de muchos a muchos:** `<input type="checkbox" name="proveedor_ids[]" value="3">`, una por proveedor. Llega un array de ids, que se valida (`'proveedor_ids' => 'sometimes|array'`, `'proveedor_ids.*' => 'exists:proveedores,id'`) y se guarda con `$presupuesto->proveedores()->sync($ids)`. Si no se marca ninguna, el campo no llega: hay que hacer `sync([])`.
- **Una validación que depende de otra tabla** se añade con `Validator::after()` o con el método `after()` del _Request_, y el error se asigna a un campo: `$v->errors()->add('cliente_id', '…')`.
- **Reglas de negocio en la web:** cuando algo no se puede hacer, se vuelve a la página anterior con el motivo: `back()->with('error', '…')`. El layout lo muestra con `role="alert"`.

## Ejercicios

1. **Las pantallas.** `PresupuestosController` y `routes/presupuestos.php`, con las siete rutas de los maestros. `store` redirige al **detalle**, para añadir las líneas a continuación.
    - Listado con las columnas `Código`, `Fecha`, `Cliente`, `Estado` y `Total` (el total se muestra en [6.13](./RA6_13_calculos.md); hasta entonces, la columna puede quedar vacía). El estado se muestra como `Aprobado` o `No aprobado`, con `<x-insignia>`.
    - Formulario: `cliente_id` y `obra_id` (selectores), `fecha`, `iva` y `estado`. Validación: `cliente_id` obligatorio; `iva` entre 0 y 100. Si el presupuesto tiene obra y la obra tiene cliente, debe ser el mismo: error en `cliente_id`.

2. **Las líneas.** El detalle lista las líneas en una tabla (`Descripción`, `Uds.`, `Precio`, `Total`) con un botón de quitar en cada una, y un formulario debajo para añadir otra:

    | Método | URL | Nombre |
    |---|---|---|
    | POST | `/presupuestos/{presupuesto}/lineas` | `presupuestos.lineas.store` |
    | DELETE | `/presupuestos/{presupuesto}/lineas/{linea}` | `presupuestos.lineas.destroy` |

    Validación: `uds` entero ≥ 0; `precio` ≥ 0. Las dos vuelven al detalle. Una línea de otro presupuesto da 404.

3. **Proveedores y especialistas.** Casillas `proveedor_ids[]` y `especialista_ids[]` en el formulario, marcadas con los que ya tiene, que se guardan con `sync`. El detalle del proveedor y del especialista lista sus presupuestos.

4. **Vincular a la obra.** El detalle de la obra ofrece, en un selector `presupuesto_id`, los presupuestos **sin obra y de su cliente**, y lo envía a:

    | Método | URL | Nombre |
    |---|---|---|
    | POST | `/obras/{obra}/vincular-presupuesto` | `obras.vincular-presupuesto` |

    Solo se aceptan presupuestos libres y del mismo cliente; si no, error en `presupuesto_id`. Vuelve al detalle de la obra, que lista sus presupuestos. (En [6.11](./RA6_11_partes.md), el detalle se divide en pestañas, y esto queda en *Resumen*.)

5. **Regla de borrado.** Un cliente con presupuestos no se borra: `clientes.destroy` vuelve atrás con `->with('error', 'motivo')`.

6. **Investiga.** Responde en tu _pull request_:
    - Desmarca todos los proveedores de un presupuesto y guarda. ¿Qué llega en la petición? ¿Qué pasaría si no hicieras `sync([])` en ese caso?
    - Comenta temporalmente la comprobación del ejercicio 5 y borra un cliente con presupuestos. ¿Qué ocurre? ¿Por qué es mejor comprobarlo antes?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_10_PresupuestosTest.php tests/Feature/
php artisan test --filter=RA6_10
```

Resultado esperado: `Tests: 6 passed`.

## Git

Rama `RA6_10`. Commits: `feat: pantallas de presupuestos`, `feat: líneas del presupuesto`, `feat: vincular presupuestos a la obra`.

---

**Siguiente:** [6.11. Ejercicios: partes e ingresos](./RA6_11_partes.md)
<!-- {% endraw %} -->
