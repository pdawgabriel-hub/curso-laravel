# 6.6. Ejercicios: trabajadores

**Referencia teórica:** [Ejercicios de bases de datos de otras entidades](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/0471_BDFamiliaProfesional.html)

El mismo camino de [6.5](./RA6_5_clientes.md), con menos ayuda.

## Ejercicios

1. **La migración.**

    ```bash
    php artisan make:migration create_trabajadores_table --create=trabajadores
    ```

    Campo | Tipo | nullable
    -----|----|---
    codigo | string(20), único | no
    tipo | string | sí
    nombre | string | no
    apellido | string | no
    telefono | string(20) | sí
    coste_hora_estandar | decimal(8,2), **20** por defecto | no

2. **El modelo.** `Trabajador`, con `$table = 'trabajadores'` (Laravel buscaría `trabajadors`), `use HasFactory, TieneCodigo`, `PREFIJO = 'TRB'`, el _cast_ de `coste_hora_estandar` a `float`, el valor por defecto en `$attributes`, el _accessor_ `nombre_completo` y este `$fillable`: `tipo, nombre, apellido, telefono, coste_hora_estandar`.

3. **La _factory_ y la semilla.** `TrabajadorFactory`:

    Campo | Valor en `TrabajadorFactory`
    -----|----
    tipo | `'Peón'`
    nombre | `fake()->firstName()`
    apellido | `fake()->lastName()`
    telefono | `fake()->numerify('6########')`
    coste_hora_estandar | `20`

    `TrabajadoresTableSeeder` crea 20 trabajadores con `Trabajador::factory(20)->create()`. Llámala desde `DatabaseSeeder`.

4. **El controlador y las vistas.** `TrabajadoresController` usa el modelo, con `findOrFail`, y deja de tener `$arrayTrabajadores`. Las vistas pasan a `$trabajador->campo`. Añade `destroy` y su ruta `DELETE /trabajadores/destroy/{id}`. El detalle muestra el código, el tipo y la tarifa.

5. **Investiga.** Responde en tu _pull request_: en `tinker`, compara `(new App\Models\Trabajador)->coste_hora_estandar` con el valor de un trabajador recién leído de la base de datos. ¿Qué aporta cada una de las dos definiciones del valor 20, la de la migración y la de `$attributes`?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_6_TrabajadoresTest.php tests/Feature/
php artisan test --filter=RA6_6
```

Resultado esperado: `Tests: 6 passed`.

## Git

Rama `RA6_6`, commit `feat: trabajadores en la base de datos`.

---

**Siguiente:** [6.7. Ejercicios: proveedores y especialistas](./RA6_7_proveedores.md)
