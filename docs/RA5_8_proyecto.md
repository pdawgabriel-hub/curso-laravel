# 5.8. Proyecto: POO y patrones de diseño
<!-- {% raw %} -->

**Referencia teórica:** [Proyecto: POO y patrones de diseño](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/RA5_8_proyecto.html) · [Laravel: Service Container](https://laravel.com/docs/13.x/container)

Cierre del bloque: los **clientes** dejan de ser un array dentro del controlador. Se aplican varios **patrones de diseño** (repositorio, inyección de dependencias y _Singleton_) y, por primera vez, el alta guarda de verdad, aunque todavía sin base de datos.

Solo cambian los clientes. Las obras y los trabajadores se quedan con sus arrays hasta el Bloque 6.

> **Aviso.** Lo que se construye aquí es para aprender los patrones. En [6.5](./RA6_5_clientes.md), `ClientesController` pasa a usar el modelo de _Eloquent_ directamente, y `app/Dominio` y `app/Repositorios` se borran.

## MVC, por fin completo

| Pieza | Responsabilidad | En este proyecto |
|---|---|---|
| **Modelo** | Los datos y sus reglas | Hasta ahora, un array dentro de `ClientesController` |
| **Vista** | La presentación | `resources/views/`, con _Blade_ |
| **Controlador** | Recibir la petición, pedir los datos, elegir la vista | `app/Http/Controllers/` |

Un array dentro del controlador no es un modelo. Aquí tendrá dos partes:

- **Una clase del dominio**, `Cliente`, que representa un cliente.
- **Un repositorio**, que sabe guardarlos y recuperarlos.

## La clase del dominio

`app/Dominio/Cliente.php`. No va en `app/Models/` porque ahí vivirá, en el Bloque 6, el modelo de _Eloquent_ con el mismo nombre.

```php
<?php

namespace App\Dominio;

final class Cliente
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $nombre,
        public readonly ?string $apellidos,
        public readonly ?string $nif,
        public readonly string $direccion,
        public readonly ?string $codPostal,
        public readonly string $telefono,
        public readonly ?string $creadoPor = null,
    ) {
    }

    public function nombreCompleto(): string
    {
        return trim($this->nombre . ' ' . $this->apellidos);
    }
}
```

## El patrón repositorio

Un **repositorio** guarda y recupera los objetos de un tipo. Quien lo usa no sabe dónde se guardan de verdad. Para eso se define primero como una **interfaz**, `app/Repositorios/ClienteRepository.php`:

```php
<?php

namespace App\Repositorios;

use App\Dominio\Cliente;

interface ClienteRepository
{
    /** @return list<Cliente> */
    public function todos(): array;

    public function porId(int $id): ?Cliente;

    /** Da de alta el cliente si no tiene id, o sustituye el que ya existe. Devuelve el cliente guardado. */
    public function guardar(Cliente $cliente): Cliente;
}
```

La implementación más sencilla es un array, `app/Repositorios/ArrayClienteRepository.php`. Los 10 clientes de `array_clientes.php` salen del controlador y pasan aquí:

```php
<?php

namespace App\Repositorios;

use App\Dominio\Cliente;

final class ArrayClienteRepository implements ClienteRepository
{
    /** @var array<int, Cliente> Los clientes, indexados por su id. */
    private array $clientes = [];

    public function __construct()
    {
        foreach (require resource_path('datos/array_clientes.php') as $id => $c) {
            $this->clientes[$id] = new Cliente($id, $c['nombre'], $c['apellidos'], $c['nif'], $c['direccion'], $c['cod_postal'], $c['telefono']);
        }
    }

    public function todos(): array
    {
        return array_values($this->clientes);
    }

    public function porId(int $id): ?Cliente
    {
        return $this->clientes[$id] ?? null;
    }

    public function guardar(Cliente $cliente): Cliente
    {
        $id = $cliente->id ?? max([0, ...array_keys($this->clientes)]) + 1;

        return $this->clientes[$id] = new Cliente($id, $cliente->nombre, $cliente->apellidos, $cliente->nif, $cliente->direccion, $cliente->codPostal, $cliente->telefono, $cliente->creadoPor);
    }
}
```

## Inyección de dependencias

El controlador no crea el repositorio: lo **pide** en su constructor, y _Laravel_ se lo entrega. Es lo mismo que ya hacía con `Request` y con los _Form Requests_.

```php
class ClientesController extends Controller
{
    public function __construct(
        private readonly ClienteRepository $clientes,
    ) {
    }

    public function index(): View
    {
        return view('clientes.index', ['clientes' => $this->clientes->todos()]);
    }
}
```

A una interfaz no se le puede hacer `new`, así que hay que decirle al **contenedor de servicios** qué implementación entregar. Se hace en `register()` de `app/Providers/AppServiceProvider.php`:

```php
public function register(): void
{
    $this->app->singleton(ClienteRepository::class, ArrayClienteRepository::class);
}
```

## El patrón _Singleton_

`singleton()` significa "entrega siempre **el mismo** objeto". Es el patrón _Singleton_: una sola instancia, compartida por quienes la usan. Si `ClientesController` y `ObrasController` tuvieran cada uno su repositorio, cada uno vería solo sus datos.

| _Singleton_ clásico | _Singleton_ del contenedor |
|---|---|
| Constructor privado y método estático: `Configuracion::instancia()` | La clase es normal; el contenedor la comparte |
| Quien la usa queda atado a esa clase | Quien la usa pide la interfaz y no sabe qué clase recibe |
| En los tests no se puede sustituir | En los tests se sustituye con una línea |

PHP empieza de cero en **cada petición**: esa instancia única lo es durante una petición, no para siempre.

## Un repositorio que recuerda

Con el array, el alta parece funcionar, pero al recargar el cliente ha desaparecido: un array dura lo que una petición. Sin base de datos, lo que sobrevive entre peticiones es un **fichero**. `app/Repositorios/JsonClienteRepository.php`:

```php
<?php

namespace App\Repositorios;

use App\Dominio\Cliente;

final class JsonClienteRepository implements ClienteRepository
{
    public function __construct(
        private readonly string $fichero,
        private readonly ClienteRepository $iniciales,
    ) {
    }

    public function todos(): array
    {
        return array_values($this->leer());
    }

    public function porId(int $id): ?Cliente
    {
        return $this->leer()[$id] ?? null;
    }

    public function guardar(Cliente $cliente): Cliente
    {
        $todos = $this->leer();
        $id = $cliente->id ?? max([0, ...array_keys($todos)]) + 1;
        $todos[$id] = new Cliente($id, $cliente->nombre, $cliente->apellidos, $cliente->nif, $cliente->direccion, $cliente->codPostal, $cliente->telefono, $cliente->creadoPor);

        // LOCK_EX: si dos peticiones escriben a la vez, la segunda espera a la primera.
        file_put_contents($this->fichero, json_encode(array_map(fn (Cliente $c) => get_object_vars($c), $todos), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

        return $todos[$id];
    }

    /** @return array<int, Cliente> */
    private function leer(): array
    {
        // Mientras no exista el fichero, se parte de los datos de ejemplo.
        if (! is_file($this->fichero)) {
            $clientes = [];
            foreach ($this->iniciales->todos() as $c) {
                $clientes[$c->id] = $c;
            }

            return $clientes;
        }

        return array_map(fn (array $c) => new Cliente(...$c), json_decode((string) file_get_contents($this->fichero), true) ?? []);
    }
}
```

Para usarlo, se cambia **una línea** del contenedor, y los controladores no se tocan:

```php
$this->app->singleton(ClienteRepository::class, fn () => new JsonClienteRepository(
    storage_path('app/clientes.json'),
    new ArrayClienteRepository(),
));
```

## Repositorio, DAO y _Active Record_

| | Repositorio | DAO | _Active Record_ |
|---|---|---|---|
| Quién guarda | El repositorio | El DAO | El propio objeto: `$cliente->save()` |
| Piensa en | Objetos, como una colección | Filas de una tabla | Objetos que conocen su tabla |
| Cambiar el almacenamiento | Otra implementación de la interfaz | Otro DAO | Difícil: el objeto está atado a la base de datos |
| En _Laravel_ | Lo escribimos nosotros (este apartado) | Se parece al _Query Builder_ | Los modelos de _Eloquent_ (Bloque 6) |

El Bloque 6 usa _Active Record_ con _Eloquent_: por eso allí el repositorio deja de hacer falta.

## Ejercicios

1. **El dominio.** Crea `app/Dominio/Cliente.php` como en este apartado. Compruébalo en `php artisan tinker`:

    ```php
    (new App\Dominio\Cliente(1, 'Ana', 'Ruiz', null, 'Calle Sol 1', null, '600000000'))->nombreCompleto();
    ```

2. **El repositorio.** Copia los datos, crea en `app/Repositorios/` la interfaz y `ArrayClienteRepository`, y regístrala como _singleton_.

    ```bash
    mkdir -p resources/datos
    cp /var/www/curso-laravel/docs/materiales/plantillas/RA5_5/datos/array_clientes.php resources/datos/
    ```

3. **El controlador.** `ClientesController` recibe el repositorio por el constructor, y elimina `$arrayClientes`.
    - `index`, `show` y `edit` usan `todos()` y `porId()`. Si `porId()` devuelve `null`, 404. `show` y `edit` pasan a la vista solo `cliente`.
    - `store` crea un `new Cliente(null, …)` con los datos validados y lo guarda. `creadoPor` es `$request->user()->name`.
    - `update` guarda un `new Cliente($id, …)`, y conserva el `creadoPor` que ya tuviera.
    - `ObrasController::show` recibe también el repositorio, para mostrar `nombreCompleto()` del cliente de la obra.

4. **Las vistas.** Ahora reciben objetos, no arrays: `$cliente->nombre` en lugar de `$cliente['nombre']`, y `$cliente->id` para los enlaces. Copia las vistas ya adaptadas:

    ```bash
    cp /var/www/curso-laravel/docs/materiales/plantillas/RA5_8/resources/views/clientes/* resources/views/clientes/
    ```

    En `create.blade.php`, el `@include` sigue pasando `['cliente' => null]`.

5. **Un repositorio que recuerda.** Da de alta un cliente y recarga el listado. ¿Qué ha pasado? Crea `JsonClienteRepository`, cambia el registro del contenedor y vuelve a probarlo. Abre `storage/app/clientes.json`. ¿Has tenido que cambiar algún controlador?

6. **Investiga.** Responde en tu _pull request_:
    - En `ClientesController`, ¿quién llama al constructor y de dónde sale el repositorio que recibe?
    - Con las herramientas de desarrollo, añade al formulario `<input name="creado_por" value="Otra Persona">` y envíalo. ¿Qué nombre se guarda? ¿Por qué?
    - ¿Qué ocurriría si registraras `JsonClienteRepository` con `bind()` en lugar de `singleton()`?
    - ¿Por qué `storage/app/clientes.json` no debe subirse al repositorio? Compruébalo con `git status`.

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque5/RA5_8_ProyectoTest.php tests/Feature/
php artisan test --filter=RA5_8
```

El test sustituye tu repositorio por uno en memoria, así que no toca tu `clientes.json`. Comprueba:

- la clase del dominio y las del repositorio;
- que el contenedor entrega siempre el mismo `JsonClienteRepository`;
- que el fichero JSON guarda, recuerda y parte de los datos de ejemplo;
- que `ClientesController` recibe el repositorio y ya no tiene el array;
- el listado, el alta con el autor de la sesión aunque se intente falsear, y la modificación;
- el nombre del cliente en el detalle de una obra.

Resultado esperado: `Tests: 9 passed`.

### Todos los tests del Bloque 5 a la vez

```bash
php artisan test --filter=RA5_
```

> **Aviso.** Los tests de 5.3 y 5.4 ya se borraron en su momento, porque comprobaban una etapa anterior.

## Git

Rama `RA5_8`. Al fusionar: `git tag -a bloque-5 -m "Bloque 5: introducción a Laravel" && git push --tags`.

---

**Siguiente:** [6.1. Configuración, migraciones y _Schema Builder_](./RA6_1_migraciones.md)
<!-- {% endraw %} -->
