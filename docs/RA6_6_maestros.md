# 6.6. Ejercicios: los maestros en la base de datos
<!-- {% raw %} -->

**Referencia teórica:** [Ejercicios de bases de datos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/047_ejerciciosBD.html) · [Ejercicios de bases de datos de otras entidades](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/0471_BDFamiliaProfesional.html) · [Crear y modificar](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/0475_crearModificarFamiliaProfesional.html)

Las tablas, los modelos y los datos ya existen. Ahora las pantallas de **clientes, trabajadores, proveedores y especialistas** los usan, con los componentes de [6.5](./RA6_5_interfaz.md). Cada maestro sigue el mismo camino: _Request_, controlador, rutas y vistas.

## Conceptos clave

- **Del array al modelo:** `$this->arrayClientes` pasa a ser `Cliente::paginate(15)`, y `$arrayClientes[$id]` pasa a ser `Cliente::findOrFail($id)`, que responde 404 si no existe. En las vistas, `$cliente['campo']` pasa a ser `$cliente->campo`.
- **Paginación:** `paginate(15)` devuelve los registros de una página y `{{ $clientes->links() }}` pinta los enlaces `?page=2`.
- **Un Form Request por entidad**, en `app/Http/Requests/`, con las reglas de su formulario. Se crea con `php artisan make:request ProveedorRequest`; `authorize()` devuelve `true` (los permisos llegan en [7.6](./RA7_6_autorizacion.md)). Los mismos Request validarán la API en [7.3](./RA7_3_controladores.md).
- **Formato de números:** importes y tarifas con `number_format($x, 2, ',', '.')` (`1.210,00`). Para no repetirlo, un único helper `App\Support\Formato` con un método estático `n(float $x, int $decimales = 2): string`.

## Las rutas

Las mismas en los cuatro maestros, todas en el grupo `auth`: con datos reales, como NIF y teléfonos, nada debe verse sin identificarse.

| Método | URL | Nombre | Acción |
|---|---|---|---|
| GET | `/clientes` | `clientes.index` | `index` |
| GET | `/clientes/show/{id}` | `clientes.show` | `show` |
| GET | `/clientes/create` | `clientes.create` | `create` |
| POST | `/clientes/store` | `clientes.store` | `store` → listado, con `mensaje` |
| GET | `/clientes/edit/{id}` | `clientes.edit` | `edit` |
| PUT | `/clientes/update/{id}` | `clientes.update` | `update` → detalle, con `mensaje` |
| DELETE | `/clientes/destroy/{id}` | `clientes.destroy` | `destroy` → listado, con `mensaje` |

## Las vistas

| Vista | Qué lleva |
|---|---|
| `index` | `<x-cabecera>` con el botón *Nuevo*; `<x-tabla>` con las columnas de cada maestro; en cada fila, el código enlaza al detalle; `{{ $x->links() }}` en el pie |
| `show` | `<x-cabecera>` con el nombre, y los botones *Modificar* y `<x-borrar>`; `<x-tarjeta>` con los datos en `<x-dato>` |
| `create` y `edit` | `<x-formulario>` con un `<x-campo>`, `<x-area>` o `<x-selector>` por campo; en `edit`, cada campo con su valor actual |

Columnas de los listados, en `<th>` y con estos textos:

| Maestro | Columnas |
|---|---|
| Clientes | `Código`, `Nombre`, `NIF`, `Teléfono` |
| Trabajadores | `Código`, `Nombre`, `Tipo`, `Tarifa` |
| Proveedores | `Código`, `Nombre`, `Tipo`, `Total` |
| Especialistas | `Código`, `Nombre`, `Tipo`, `Teléfono` |

## Ejercicios

1. **Clientes: el controlador.** `ClientesController` pasa a usar el modelo directamente, sin repositorio:

    ```php
    public function index(): View
    {
        return view('clientes.index', ['clientes' => Cliente::orderBy('codigo')->paginate(15)]);
    }

    public function show(int $id): View
    {
        return view('clientes.show', ['cliente' => Cliente::findOrFail($id)]);
    }
    ```

    - `edit`, igual que `show`. `store` hace `Cliente::create($request->validated())`, y `update`, `findOrFail` + `update`, con las redirecciones de la tabla de rutas.
    - En `ClienteRequest`, el NIF pasa a ser único. Al modificar, debe admitir el suyo: `Rule::unique('clientes')->ignore($this->route('id'))`. Queda así:

        | Campo | Reglas |
        |:--|:--|
        | `nombre` | `required`, `string`, `max:255` |
        | `apellidos` | `nullable`, `string`, `max:255` |
        | `nif` | `nullable`, `string`, `max:20`, `Rule::unique('clientes')->ignore($this->route('id'))` |
        | `direccion` | `required`, `string`, `max:255` |
        | `cod_postal` | `nullable`, `string`, `max:10` |
        | `telefono` | `required`, `string`, `max:20` |
    - Nueva acción `destroy`, con su ruta.

2. **Clientes: limpieza.** Lo que hiciste en [5.8](./RA5_8_proyecto.md) ya no hace falta, porque _Eloquent_ es _Active Record_. Borra `app/Dominio`, `app/Repositorios`, su registro en `AppServiceProvider`, `storage/app/clientes.json` y `resources/datos/array_clientes.php`. `ObrasController` deja de recibir el repositorio: de momento, busca al cliente con `Cliente::find()`.

3. **Clientes: las vistas.** Con los componentes y la tabla de vistas. `$cliente->nombre_completo` en lugar de `nombreCompleto()`, y `$cliente->cod_postal` en lugar de `codPostal`. El detalle muestra el `codigo` y ya no muestra `creadoPor`.

4. **Solo con sesión.** En `routes/clientes.php`, mete también `index` y `show` en el grupo `auth`. Haz lo mismo con las rutas de obras y trabajadores.

5. **Trabajadores.** El mismo camino: `TrabajadoresController` con el modelo y `findOrFail`, sin `$arrayTrabajadores`; `destroy` y su ruta; vistas con componentes. La tarifa se muestra con dos decimales (`22,00 €/h`), con el helper `Formato`. `TrabajadorRequest`, que ya existe desde [5.6](./RA5_6_formularios.md), queda así:

    | Campo | Reglas |
    |:--|:--|
    | `nombre`, `apellido` | `required`, `string`, `max:255` |
    | `tipo`, `telefono` | `nullable`, `string`, `max:255` |
    | `coste_hora_estandar` | `filled`, `numeric`, `min:0` |

    `filled` obliga a rellenarlo si se envía, pero deja que no se envíe: entonces la columna toma su valor por defecto (20).

6. **Proveedores.** Esta vez desde cero, porque no hay controlador ni vistas de etapas anteriores: `ProveedoresController`, `routes/proveedores.php`, `ProveedorRequest` y las cuatro vistas. La ficha solo guarda los datos del proveedor: lo que se le paga son sus partes ([6.11](./RA6_11_partes.md)). `ProveedorRequest`:

    | Campo | Reglas |
    |:--|:--|
    | `nombre` | `required`, `string`, `max:255` |
    | `direccion`, `telefono`, `tipo`, `referencia`, `documento`, `num_documento` | `nullable`, `string`, `max:255` |
    | `correo` | `nullable`, `email` |

7. **Especialistas.** Lo mismo que proveedores, con `EspecialistaRequest`:

    | Campo | Reglas |
    |:--|:--|
    | `nombre` | `required`, `string`, `max:255` |
    | `tipo`, `referencia`, `comunicacion_parte`, `telefono` | `nullable`, `string`, `max:255` |
    | `notas` | `nullable`, `string` |
    | `correo` | `nullable`, `email` |

8. **El menú.** Comprueba que el menú lateral muestra ya las cuatro secciones, y que la sección en la que estás aparece marcada.

9. **Investiga.** Responde en tu _pull request_:
    - Ejecuta en `tinker` `App\Models\Cliente::paginate(15)` y mira lo que devuelve. ¿Qué información, además de los clientes, necesita `links()`?
    - Envía el formulario de alta vacío. ¿Por qué los campos que sí rellenaste siguen escritos? ¿Qué parte de `<x-campo>` lo consigue?
    - El especialista tiene un campo `notas`. En [6.14](./RA6_14_bitacoras.md) tendrá además una relación `observaciones()`. Con un campo y una relación del mismo nombre, ¿qué devolvería `$especialista->observaciones`?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_6_MaestrosTest.php tests/Feature/
php artisan test --filter=RA6_6
```

Resultado esperado: `Tests: 19 passed`.

Además de guardar y validar, el test comprueba las vistas: las columnas de cada listado, la paginación de 15 en 15, el mensaje cuando no hay registros, la entrada del menú marcada, el botón de borrar con confirmación y que, tras un error, el formulario conserva lo escrito y marca el campo con `aria-invalid`.

## Git

Rama `RA6_6`. Un commit por maestro: `refactor: clientes con Eloquent`, `feat: trabajadores en la base de datos`, `feat: proveedores`, `feat: especialistas`.

---

**Siguiente:** [6.7. Usuarios: rol, administrador y semilla](./RA6_7_usuarios.md)
<!-- {% endraw %} -->
