<!-- {% raw %} -->
# 6.9. Ejercicios: presupuestos

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

1. **Las tablas.** Migración `create_presupuestos_table`:

    Campo | Tipo | nullable
    -----|----|---
    codigo | string(20), único | no
    fecha | date | no
    cliente_id | clave ajena, `restrictOnDelete` | no
    obra_id | clave ajena, `nullOnDelete` | sí
    nombre_cliente | string | no
    nif, direccion, telefono | string | sí
    iva | decimal(5,2), **21** por defecto | no
    estado | enum: `aprobado`, `no_aprobado` (por defecto) | no

    En la misma migración: `lineas_presupuesto` (`presupuesto_id` con `cascadeOnDelete`, `descripcion` opcional, `uds`, `precio` en `decimal(10,2)`), y las pivotes `presupuesto_proveedor` y `especialista_presupuesto`, con clave primaria compuesta. Añade también la clave ajena de `gastos.presupuesto_id`, con `nullOnDelete`.

2. **Los modelos.** `Presupuesto` (`PREFIJO = 'PREP'`) y `LineaPresupuesto` (`$table = 'lineas_presupuesto'`), con sus relaciones del [contrato](./contrato-api.md#modelos) y sus _factories_. `PresupuestoFactory` **no** rellena `nombre_cliente`. El _accessor_ `total_linea` es `uds × precio`.

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
