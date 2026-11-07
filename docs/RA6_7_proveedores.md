# 6.7. Ejercicios: proveedores y especialistas

**Referencia teórica:** [Ejercicios de bases de datos de otras entidades](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/0471_BDFamiliaProfesional.html)

Dos entidades más, esta vez desde cero: no tienen controlador ni vistas de etapas anteriores, ni array de datos.

## Ejercicios

1. **Proveedores: la migración.**

    ```bash
    php artisan make:migration create_proveedores_table --create=proveedores
    ```

    Campo | Tipo | nullable
    -----|----|---
    codigo | string(20), único | no
    nombre | string | no
    direccion | string | sí
    telefono | string(20) | sí
    correo | string | sí
    tipo | string | sí
    referencia | string | sí
    documento | string | sí
    num_documento | string | sí
    base | decimal(10,2) | no
    iva | decimal(5,2) | no

2. **Proveedores: modelo, _factory_ y semilla.** Modelo `Proveedor` con `$table = 'proveedores'`, `use HasFactory, TieneCodigo`, `PREFIJO = 'PROV'`, el _accessor_ `total` (`base × (1 + iva / 100)`, redondeado a 2 decimales) y este `$fillable`: `nombre, direccion, telefono, correo, tipo, referencia, documento, num_documento, base, iva`.

    Campo | Valor en `ProveedorFactory`
    -----|----
    nombre | `fake()->company()`
    direccion | `fake()->streetAddress()`
    telefono | `fake()->numerify('9########')`
    correo | `fake()->companyEmail()`
    tipo | `'Materiales'`
    referencia | `fake()->bothify('REF-####')`
    base | `fake()->randomFloat(2, 100, 5000)`
    iva | `21`

    `ProveedoresTableSeeder` crea 20 con `Proveedor::factory(20)->create()`. Llámala desde `DatabaseSeeder`.

3. **Proveedores: pantallas.** `ProveedoresController`, `routes/proveedores.php`, `ProveedorRequest` y las vistas `index`, `show`, `create` y `edit`, con las mismas rutas que clientes. Validación: `nombre` obligatorio, `correo` con formato de email, `base` ≥ 0, `iva` entre 0 y 100.

4. **Especialistas: la migración.**

    ```bash
    php artisan make:migration create_especialistas_table --create=especialistas
    ```

    Campo | Tipo | nullable
    -----|----|---
    codigo | string(20), único | no
    tipo | string | sí
    referencia | string | sí
    comunicacion_parte | text | sí
    nombre | string | no
    notas | text | sí
    telefono | string(20) | sí
    correo | string | sí
    importe | decimal(10,2) | sí

5. **Especialistas: modelo, _factory_ y semilla.** Modelo `Especialista` con `$table = 'especialistas'`, `use HasFactory, TieneCodigo`, `PREFIJO = 'ESP'` y este `$fillable`: `tipo, referencia, comunicacion_parte, nombre, notas, telefono, correo, importe`.

    Campo | Valor en `EspecialistaFactory`
    -----|----
    tipo | `fake()->randomElement(['Arquitecto', 'Electricista'])`
    referencia | `fake()->bothify('ESP-####')`
    nombre | `fake()->name()`
    telefono | `fake()->numerify('6########')`
    correo | `fake()->safeEmail()`
    importe | `fake()->randomFloat(2, 100, 2000)`

    `EspecialistasTableSeeder` crea 20 con `Especialista::factory(20)->create()`. Llámala desde `DatabaseSeeder`.

6. **Especialistas: pantallas.** Las mismas que proveedores. Validación: `nombre` obligatorio, `correo` con formato de email, `importe` ≥ 0.

7. **Investiga.** Responde en tu _pull request_: el módulo de _Odoo_ llamaba `observaciones` al campo que aquí es `notas`. En [6.14](./RA6_14_bitacoras.md), `Especialista` tendrá además una relación `observaciones()`. Con un campo y una relación del mismo nombre, ¿qué devolvería `$especialista->observaciones`?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_7_ProveedoresEspecialistasTest.php tests/Feature/
php artisan test --filter=RA6_7
```

Resultado esperado: `Tests: 6 passed`.

## Git

Rama `RA6_7`. Commits: `feat: proveedores`, `feat: especialistas`.

---

**Siguiente:** [6.8. Relaciones: obras y faltas](./RA6_8_relaciones.md)
