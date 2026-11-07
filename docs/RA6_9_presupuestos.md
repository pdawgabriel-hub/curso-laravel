# 6.9. Ejercicios: presupuestos
<!-- {% raw %} -->

**Referencia teórica:** [Muchos a muchos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/083_relaciones_muchosAmuchos.html) · [Insertar y actualizar con relaciones](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/084_DML_Relaciones.html)

Primera entidad con líneas y con una relación de muchos a muchos.

## Muchos a muchos

Un presupuesto incluye varios proveedores, y un proveedor aparece en varios presupuestos. Hace falta una **tabla pivote**, nombrada con los dos modelos en singular y por orden alfabético, con lo que Laravel la encuentra sola:

```php
// Presupuesto
public function proveedores(): BelongsToMany { return $this->belongsToMany(Proveedor::class); }

// Proveedor
public function presupuestos(): BelongsToMany { return $this->belongsToMany(Presupuesto::class); }
```

```php
$presupuesto->proveedores()->attach($id);     // añade
$presupuesto->proveedores()->detach($id);     // quita
$presupuesto->proveedores()->sync($ids);      // deja exactamente estos
```

## Ejercicios

1. **Las tablas.** Una migración por tabla, en este orden.

    ```bash
    php artisan make:migration create_presupuestos_table --create=presupuestos
    ```

    Campo | Tipo | nullable
    -----|----|---
    codigo | string(20), único | no
    fecha | date | no
    cliente_id | clave ajena a `clientes`, `restrictOnDelete` | no
    obra_id | clave ajena a `obras`, `nullOnDelete` | sí
    nombre_cliente | string | no
    nif | string(20) | sí
    direccion | string | sí
    telefono | string(20) | sí
    iva | decimal(5,2), **21** por defecto | no
    estado | enum: `aprobado`, `no_aprobado` (por defecto) | no

    ```bash
    php artisan make:migration create_lineas_presupuesto_table --create=lineas_presupuesto
    ```

    Campo | Tipo | nullable
    -----|----|---
    presupuesto_id | clave ajena a `presupuestos`, `cascadeOnDelete` | no
    descripcion | text | sí
    uds | entero sin signo, **1** por defecto | no
    precio | decimal(10,2), **0** por defecto | no

    Las dos tablas pivote **no llevan `id()` ni `timestamps()`**, y su clave primaria es compuesta, con `$table->primary(['presupuesto_id', 'proveedor_id'])`:

    ```bash
    php artisan make:migration create_presupuesto_proveedor_table --create=presupuesto_proveedor
    ```

    Campo | Tipo | nullable
    -----|----|---
    presupuesto_id | clave ajena a `presupuestos`, `cascadeOnDelete` | no
    proveedor_id | clave ajena a `proveedores`, `cascadeOnDelete` | no

    ```bash
    php artisan make:migration create_especialista_presupuesto_table --create=especialista_presupuesto
    ```

    Campo | Tipo | nullable
    -----|----|---
    especialista_id | clave ajena a `especialistas`, `cascadeOnDelete` | no
    presupuesto_id | clave ajena a `presupuestos`, `cascadeOnDelete` | no

    Por último, la clave ajena que quedó pendiente en [6.8](./RA6_8_relaciones.md):

    ```bash
    php artisan make:migration add_presupuesto_foreign_to_gastos_table --table=gastos
    ```

    ```php
    $table->foreign('presupuesto_id')->references('id')->on('presupuestos')->nullOnDelete();
    ```

    Su `down()` hace `$table->dropForeign(['presupuesto_id']);`.

2. **Los modelos y sus _factories_.** Las relaciones son las del [contrato](./contrato-api.md#modelos).

    - `Presupuesto`: `$table = 'presupuestos'`, `use HasFactory, TieneCodigo`, `PREFIJO = 'PREP'`, y este `$fillable`: `fecha, cliente_id, obra_id, nombre_cliente, nif, direccion, telefono, iva, estado`.
    - `LineaPresupuesto`: `$table = 'lineas_presupuesto'`, `use HasFactory`, el _accessor_ `total_linea` (`uds × precio`), y este `$fillable`: `presupuesto_id, descripcion, uds, precio`.

    Campo | Valor en `PresupuestoFactory`
    -----|----
    fecha | `fake()->dateTimeBetween('-1 year')->format('Y-m-d')`
    cliente_id | `Cliente::factory()`
    obra_id | `null`
    iva | `21`
    estado | `'no_aprobado'`

    `PresupuestoFactory` **no** rellena `nombre_cliente`, `nif`, `direccion` ni `telefono`: se copian del cliente en el ejercicio 3.

    Campo | Valor en `LineaPresupuestoFactory`
    -----|----
    presupuesto_id | `Presupuesto::factory()`
    descripcion | `fake()->sentence(4)`
    uds | `fake()->numberBetween(1, 10)`
    precio | `fake()->randomFloat(2, 10, 500)`

3. **Los datos del cliente se copian.** En el evento `creating` de `Presupuesto`, si no se indican: la fecha es hoy, y `nombre_cliente` (nombre completo), `nif`, `direccion` y `telefono` se copian del cliente. Un presupuesto entregado no debe cambiar si el cliente cambia su ficha.

4. **Las pantallas.** `PresupuestosController`, `routes/presupuestos.php` y sus vistas. Campos de los formularios: `cliente_id` y `obra_id` (selectores), `fecha`, `iva` y `estado`. `store` redirige al detalle. El detalle lista las líneas, con el formulario para añadir y un botón para quitar:

    | Método | URL | Nombre |
    |---|---|---|
    | POST | `/presupuestos/{presupuesto}/lineas` | `presupuestos.lineas.store` |
    | DELETE | `/presupuestos/{presupuesto}/lineas/{linea}` | `presupuestos.lineas.destroy` |

    Validación: `cliente_id` obligatorio; `iva` entre 0 y 100; `uds` entero ≥ 0; `precio` ≥ 0. Si el presupuesto tiene obra y la obra tiene cliente, debe ser el mismo: error en `cliente_id`. Puedes usar `Validator::after()`.

5. **Proveedores y especialistas.** Casillas en el formulario, que se guardan con `sync`. Comprueba que el presupuesto aparece en la ficha del proveedor.

6. **Vincular a la obra.** El detalle de la obra ofrece los presupuestos sin obra de su cliente, en un selector `presupuesto_id`, que envía a `POST /obras/{obra}/vincular-presupuesto` (`obras.vincular-presupuesto`). Solo se aceptan presupuestos libres y del mismo cliente; si no, error en `presupuesto_id`.

7. **Regla de borrado.** Un cliente con presupuestos no se borra: `clientes.destroy` vuelve atrás con `->with('error', 'motivo')`. Añade al _layout_ un bloque para `session('error')`, como el de `mensaje`.

8. **Investiga.** Responde en tu _pull request_:
    - Borra un presupuesto con líneas. ¿Quién ha borrado las líneas, tu código o la base de datos?
    - Comenta temporalmente la comprobación del ejercicio 7 y borra un cliente con presupuestos. ¿Qué ocurre? ¿Por qué es mejor comprobarlo antes?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_9_PresupuestosTest.php tests/Feature/
php artisan test --filter=RA6_9
```

Resultado esperado: `Tests: 9 passed`.

## Git

Rama `RA6_9`. Un commit por ejercicio.

---

**Siguiente:** [6.10. Ejercicios: partes](./RA6_10_partes.md)
<!-- {% endraw %} -->
