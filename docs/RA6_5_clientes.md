# 6.5. Ejercicios: clientes en la base de datos
<!-- {% raw %} -->

**Referencia teórica:** [Ejercicios de bases de datos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/047_ejerciciosBD.html) · [Crear y modificar](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/0475_crearModificarFamiliaProfesional.html)

Primera entidad completa, paso a paso. Los **clientes** pasan del repositorio de [5.8](./RA5_8_proyecto.md) a la base de datos. Las demás entidades seguirán este mismo camino: migración, modelo, _factory_, semilla, _Request_, controlador, rutas y vistas.

## Ejercicios

1. **Las migraciones.** Todas las tablas de este bloque llevan, además de sus campos, `id()` y `timestamps()`; la excepción son las tablas pivote, que se indican en [6.9](./RA6_9_presupuestos.md).

    ```bash
    php artisan make:migration create_clientes_table --create=clientes
    ```

    Campo | Tipo | nullable
    -----|----|---
    codigo | string(20), único | no
    nombre | string | no
    apellidos | string | sí
    nif | string(20), único | sí
    direccion | string | no
    cod_postal | string(10) | sí
    telefono | string(20) | no

    ```bash
    php artisan make:migration create_secuencias_table --create=secuencias
    ```

    Campo | Tipo | nullable
    -----|----|---
    prefijo | string(10), único | no
    siguiente | entero sin signo, **1** por defecto | no

    Aplícalas y compruébalas en phpMyAdmin.

2. **El modelo.** Crea `App\Models\Cliente` con `$table = 'clientes'`, `use HasFactory`, el _accessor_ `nombre_completo` y este `$fillable`, en este orden: `nombre, apellidos, nif, direccion, cod_postal, telefono`.

3. **Los códigos correlativos.** `codigo` no está en `$fillable`: lo asigna la aplicación.
    - Modelo `Secuencia` (`$fillable`: `prefijo`, `siguiente`), con el método estático `siguiente(string $prefijo): string`, que devuelve `CLI0001`, `CLI0002`… y crea la fila del prefijo si no existe. En [6.13](./RA6_13_transacciones.md) se hará seguro frente a accesos simultáneos.
    - _Trait_ `App\Models\Concerns\TieneCodigo`:

        ```php
        trait TieneCodigo
        {
            protected static function bootTieneCodigo(): void
            {
                static::creating(function ($modelo) {
                    $modelo->codigo ??= Secuencia::siguiente(static::PREFIJO);
                });
            }
        }
        ```

    - En `Cliente`: `use TieneCodigo;` y `public const PREFIJO = 'CLI';`.

    Pruébalo en `php artisan tinker`:

    ```php
    $c = App\Models\Cliente::create(['nombre' => 'Ana', 'direccion' => 'C/ Sol 1', 'telefono' => '600000000']);
    $c->codigo;
    ```

4. **La _factory_ y la semilla.**

    ```bash
    php artisan make:factory ClienteFactory --model=Cliente
    php artisan make:seeder ClientesTableSeeder
    ```

    Campo | Valor en `ClienteFactory`
    -----|----
    nombre | `fake()->firstName()`
    apellidos | `fake()->lastName()`
    nif | `fake()->unique()->numerify('########') . fake()->randomLetter()`
    direccion | `fake()->streetAddress()`
    cod_postal | `fake()->numerify('#####')`
    telefono | `fake()->numerify('6########')`

    `codigo` no va en la _factory_: lo asigna `TieneCodigo`.

    `ClientesTableSeeder` crea 20 clientes con `Cliente::factory(20)->create()`. Llámala desde `DatabaseSeeder` y ejecuta:

    ```bash
    php artisan migrate:fresh --seed
    ```

5. **El controlador.** `ClientesController` pasa a usar el modelo directamente, sin repositorio:

    ```php
    public function index(): View
    {
        return view('clientes.index', ['clientes' => Cliente::paginate(15)]);
    }

    public function show(int $id): View
    {
        return view('clientes.show', ['cliente' => Cliente::findOrFail($id)]);
    }
    ```

    - `edit`, igual que `show`. `store` hace `Cliente::create($request->validated())`, y `update`, `findOrFail` + `update`, con las redirecciones que ya tenía.
    - En `ClienteRequest`, el NIF pasa a ser único. Al modificar, debe admitir el suyo: `Rule::unique('clientes')->ignore($this->route('id'))`.
    - Nueva acción `destroy`, con la ruta `DELETE /clientes/destroy/{id}` (`clientes.destroy`), que redirige al listado con `mensaje`, y un botón en el detalle.

6. **Limpieza.** Lo que hiciste en 5.8 ya no hace falta, porque _Eloquent_ es _Active Record_. Borra `app/Dominio`, `app/Repositorios`, su registro en `AppServiceProvider`, `storage/app/clientes.json` y `resources/datos/array_clientes.php`, porque los datos ya salen de la _factory_. `ObrasController` deja de recibir el repositorio: de momento, busca al cliente con `Cliente::find()`.

7. **Las vistas.** Usa `$cliente->nombre_completo` en lugar de `nombreCompleto()`, y `$cliente->cod_postal` en lugar de `codPostal`. El detalle muestra el `codigo` y ya no muestra `creadoPor`. Añade `{{ $clientes->links() }}` al listado.

8. **Solo con sesión.** Con datos reales, como NIF y teléfonos, nada debe verse sin identificarse. En `routes/clientes.php`, mete también `index` y `show` en el grupo `auth`. Haz lo mismo en `routes/obras.php` y `routes/trabajadores.php`.

9. **Investiga.** Responde en tu _pull request_:
    - Ejecuta en `tinker` `App\Models\Cliente::create(['nombre' => 'A', 'direccion' => 'B', 'telefono' => 'C', 'codigo' => 'X'])->codigo`. ¿Qué código tiene? ¿Por qué?
    - Da de alta un cliente, bórralo y crea otro. ¿Se reutiliza su código? ¿Dónde está guardado el siguiente?
    - ¿Qué pasaría en el _trait_ si dos peticiones pidieran el código a la vez? Lo resolveremos en 6.13.

## Comprueba tu solución automáticamente

```bash
rm tests/Feature/RA5_*.php     # comprobaban la etapa de arrays y repositorios
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_5_ClientesTest.php tests/Feature/
php artisan test --filter=RA6_5
```

Resultado esperado: `Tests: 11 passed`.

## Git

Rama `RA6_5`. Un commit por ejercicio: `feat: tabla y modelo de clientes`, `feat: códigos correlativos`, `feat: semilla de clientes`, `refactor: clientes con Eloquent`.

---

**Siguiente:** [6.6. Ejercicios: trabajadores](./RA6_6_trabajadores.md)
<!-- {% endraw %} -->
