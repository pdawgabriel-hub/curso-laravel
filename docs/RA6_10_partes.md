# 6.10. Ejercicios: partes

**Referencia teórica:** [Relaciones](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/08_relaciones.html) · [Laravel: Has Many Through](https://laravel.com/docs/13.x/eloquent-relationships#has-many-through)

Los partes registran el trabajo de una obra: horas propias y costes externos. Aparecen dos reglas importantes: la tarifa congelada y los participantes deducidos.

## Relaciones a través de otra

Una obra tiene líneas de trabajo **a través de** sus partes, y `whereHas` busca según lo que hay en una relación:

```php
// Obra
public function lineasTrabajo(): HasManyThrough
{
    return $this->hasManyThrough(LineaParteTrabajo::class, ParteTrabajo::class);
}

public function trabajadoresParticipantes(): Builder
{
    return Trabajador::whereHas('lineasTrabajo.parte', fn ($q) => $q->where('obra_id', $this->id));
}
```

## Ejercicios

1. **Las tablas.** Una migración por tabla, en este orden.

    ```bash
    php artisan make:migration create_partes_trabajo_table --create=partes_trabajo
    ```

    Campo | Tipo | nullable
    -----|----|---
    obra_id | clave ajena a `obras`, `cascadeOnDelete` | no
    fecha | date | no
    descripcion | text | no
    user_id | clave ajena a `users`, `nullOnDelete` | sí

    ```bash
    php artisan make:migration create_lineas_parte_trabajo_table --create=lineas_parte_trabajo
    ```

    Campo | Tipo | nullable
    -----|----|---
    parte_trabajo_id | clave ajena a `partes_trabajo`, `cascadeOnDelete` | no
    trabajador_id | clave ajena a `trabajadores`, `restrictOnDelete` | no
    horas | decimal(5,2), **0** por defecto | no
    coste_hora | decimal(8,2), **0** por defecto | no

    ```bash
    php artisan make:migration create_partes_proveedor_table --create=partes_proveedor
    ```

    Campo | Tipo | nullable
    -----|----|---
    obra_id | clave ajena a `obras`, `cascadeOnDelete` | no
    proveedor_id | clave ajena a `proveedores`, `restrictOnDelete` | no
    fecha | date | no
    descripcion | text | sí
    documento | string | sí
    importe | decimal(10,2) | no
    estado_pago | enum: `pendiente` (por defecto), `pagado` | no
    user_id | clave ajena a `users`, `nullOnDelete` | sí

    ```bash
    php artisan make:migration create_partes_especialista_table --create=partes_especialista
    ```

    Campo | Tipo | nullable
    -----|----|---
    obra_id | clave ajena a `obras`, `cascadeOnDelete` | no
    especialista_id | clave ajena a `especialistas`, `restrictOnDelete` | no
    fecha | date | no
    descripcion | text | sí
    documento | string | sí
    importe | decimal(10,2) | no
    estado_pago | enum: `pendiente` (por defecto), `pagado` | no
    user_id | clave ajena a `users`, `nullOnDelete` | sí

2. **Los modelos y sus _factories_.** Todos con `use HasFactory` y su `$table`, y las relaciones del [contrato](./contrato-api.md#modelos). La fecha por defecto es hoy.

    - `ParteTrabajo`: `$fillable` `obra_id, fecha, descripcion, user_id`.
    - `LineaParteTrabajo`: `$fillable` `parte_trabajo_id, trabajador_id, horas, coste_hora`; la relación `parte()`, indicando la clave: `belongsTo(ParteTrabajo::class, 'parte_trabajo_id')`, y el _accessor_ `coste_subtotal`.
    - `ParteProveedor`: `$fillable` `obra_id, proveedor_id, fecha, descripcion, documento, importe, estado_pago, user_id`.
    - `ParteEspecialista`: `$fillable` `obra_id, especialista_id, fecha, descripcion, documento, importe, estado_pago, user_id`.
    - En `Trabajador`, `lineasTrabajo()`; en `Proveedor` y `Especialista`, `partes()`; en `Obra`, las de cada tipo de parte y `lineasTrabajo()`.

    Campo | Valor en `ParteTrabajoFactory`
    -----|----
    obra_id | `Obra::factory()`
    fecha | `fake()->dateTimeBetween('-1 year')->format('Y-m-d')`
    descripcion | `fake()->sentence()`

    Campo | Valor en `LineaParteTrabajoFactory`
    -----|----
    parte_trabajo_id | `ParteTrabajo::factory()`
    trabajador_id | `Trabajador::factory()`
    horas | `fake()->numberBetween(1, 8)`

    Campo | Valor en `ParteProveedorFactory`
    -----|----
    obra_id | `Obra::factory()`
    proveedor_id | `Proveedor::factory()`
    fecha | `fake()->dateTimeBetween('-1 year')->format('Y-m-d')`
    descripcion | `fake()->sentence()`
    documento | `fake()->bothify('FAC-####')`
    importe | `fake()->randomFloat(2, 50, 1000)`
    estado_pago | `'pendiente'`

    Campo | Valor en `ParteEspecialistaFactory`
    -----|----
    obra_id | `Obra::factory()`
    especialista_id | `Especialista::factory()`
    fecha | `fake()->dateTimeBetween('-1 year')->format('Y-m-d')`
    descripcion | `fake()->sentence()`
    documento | `fake()->bothify('FAC-####')`
    importe | `fake()->randomFloat(2, 50, 1000)`
    estado_pago | `'pendiente'`

    `user_id` no va en ninguna _factory_: queda vacío salvo que el test lo indique.

3. **La tarifa se congela.** En el evento `creating` de `LineaParteTrabajo`, si no se indica `coste_hora`, se copia el `coste_hora_estandar` del trabajador. Si mañana sube la tarifa, los partes antiguos no cambian. `LineaParteTrabajoFactory` **no** rellena `coste_hora`.

4. **Los participantes.** `trabajadoresParticipantes()`, `proveedoresParticipantes()` y `especialistasParticipantes()` en `Obra`, y `obrasParticipadas()` en `Trabajador`, `Proveedor` y `Especialista`. Todos devuelven un `Builder`. No hay ninguna lista que mantener a mano.

5. **Parte de trabajo desde la obra.**

    | Método | URL | Nombre |
    |---|---|---|
    | GET | `/obras/{obra}/partes-trabajo/create` | `obras.partes-trabajo.create` |
    | POST | `/obras/{obra}/partes-trabajo` | `obras.partes-trabajo.store` |
    | DELETE | `/obras/{obra}/partes-trabajo/{parte}` | `obras.partes-trabajo.destroy` |

    El formulario lleva `fecha`, `descripcion` y varias filas con `lineas[0][trabajador_id]` (un selector) y `lineas[0][horas]`. Validación: `descripcion` obligatoria; `lineas.*.trabajador_id` existe; `lineas.*.horas` entre 0 y 24. El propietario es el usuario conectado. Se vuelve al detalle de la obra, que lista los partes con sus trabajadores.

6. **Partes externos.** Igual para `partes-proveedor` y `partes-especialista`, con nombres `obras.partes-proveedor.*` y `obras.partes-especialista.*`, y los campos `proveedor_id` o `especialista_id`, `fecha`, `descripcion`, `documento`, `importe` (> 0) y `estado_pago`. Un parte de otra obra da 404.

7. **Reglas de borrado.** Un trabajador con líneas, o un proveedor o especialista con partes, no se borra: vuelve atrás con `error`.

8. **Investiga.** Responde en tu _pull request_:
    - Cambia la tarifa de un trabajador con partes. ¿Qué cambia y qué no?
    - ¿Por qué `coste_hora` se guarda en la línea, mientras que `nombre_completo` del trabajador no se guarda en ningún sitio?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_10_PartesTest.php tests/Feature/
php artisan test --filter=RA6_10
```

Resultado esperado: `Tests: 8 passed`.

## Git

Rama `RA6_10`. Un commit por ejercicio.

---

**Siguiente:** [6.11. Ejercicios: ingresos y ficha de gastos](./RA6_11_ingresos.md)
