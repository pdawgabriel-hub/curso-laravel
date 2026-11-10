# 7.3. Controladores de recursos
<!-- {% raw %} -->

**Referencia teórica:** [Controladores de recursos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/07_resourceController.html) · [Ejercicios de controladores de recursos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/074_ejerciciosControladoresRecursos.html) · [Laravel: Resource controllers](https://laravel.com/docs/13.x/controllers#api-resource-routes)

Un controlador por recurso del [contrato](./contrato-api.md), en `App\Http\Controllers\API`, que responde con los Resources de [7.2](./RA7_2_recursos.md) y valida con **los mismos Form Request de la web** (Bloque 6). En este apartado se crean **todos**.

## Conceptos clave

- `php artisan make:controller API/ClienteController --api --model=Cliente` genera los cinco métodos: `index`, `store`, `show`, `update` y `destroy`. `Route::apiResource('clientes', …)` crea sus rutas.
- `->parameters(['trabajadores' => 'trabajador'])`: Laravel singulariza en inglés (`trabajadore`), así que conviene fijar los nombres de los parámetros.
- **Recursos anidados:** `apiResource('obras.ingresos', …)` crea `/obras/{obra}/ingresos/{ingreso}`. Con `->scoped()`, Laravel comprueba que el hijo pertenece al padre si encuentra la relación por el nombre del parámetro (`ingreso` → `$obra->ingresos()`). Si el nombre no coincide (`parteTrabajo` → `partesTrabajo`), se comprueba en el controlador: `abort_if($parte->obra_id !== $obra->id, 404)`.
- **Códigos de respuesta:** un Resource devuelto desde `store` responde **201**; `destroy` responde **204** con `response()->noContent()`; un error de validación, **422** (lo hace el Form Request); una regla de negocio, **409**, con la `ReglaNegocioException` de [6.12](./RA6_12_transacciones.md).
- Al crear se devuelve `$modelo->refresh()`: sin él faltarían en la respuesta las columnas que no venían en la petición, como `codigo`.
- **Reutilizar los Form Request:** `store(ClienteRequest $request)` en la API valida igual que en la web, y si falla responde **422** en JSON (la petición espera JSON). Las reglas viven en un solo sitio.
- **Modificación parcial:** en la API, `update` admite enviar solo lo que cambia, así que los obligatorios pasan a `sometimes`, `required`; en la web, el formulario envía todo y siguen siendo `required`. Lo decide un método común, `obligatorio()`, en una clase base de la que heredan todos los Request.
- **Nombres de la ruta:** la web usa `{id}` (`/clientes/update/{id}`) y la API, el modelo (`/clientes/{cliente}`). En un Request, `$this->route('cliente') ?? $this->route('id')` sirve para las dos.
- **Autorizar y validar:** con un Form Request, Laravel valida **antes** de ejecutar el método del controlador. El `Gate::authorize()` de [7.6](./RA7_6_autorizacion.md) se comprueba después, así que una petición sin permiso y con datos erróneos recibe 422 en vez de 403.

## El ejemplo: clientes

La clase base, `app/Http/Requests/GestionRequest.php`:

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Base de los Request que comparten la web y la API. */
abstract class GestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // Los permisos se comprueban en el controlador (7.6)
    }

    /** En la web y al crear, obligatorio. Al modificar desde la API, solo si se envía. */
    protected function obligatorio(): array
    {
        return $this->esApi() && ! $this->isMethod('POST') ? ['sometimes', 'required'] : ['required'];
    }

    protected function esApi(): bool
    {
        return $this->is('api/*');
    }
}
```

`obligatorio()` devuelve un array y se usa con `...`: dentro de un array de reglas, cada elemento es **una** regla, y `'sometimes|required'` no funcionaría.

`ClienteRequest` (el de [6.6](./RA6_6_maestros.md)) pasa a heredar de `GestionRequest`. Su método `authorize()` sobra y se quita; `attributes()` se queda como estaba:

```php
namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ClienteRequest extends GestionRequest
{
    public function rules(): array
    {
        return [
            'nombre' => [...$this->obligatorio(), 'string', 'max:255'],
            'apellidos' => ['nullable', 'string', 'max:255'],
            'nif' => ['nullable', 'string', 'max:20', Rule::unique('clientes')->ignore($this->route('cliente') ?? $this->route('id'))],
            'direccion' => [...$this->obligatorio(), 'string', 'max:255'],
            'cod_postal' => ['nullable', 'string', 'max:10'],
            'telefono' => [...$this->obligatorio(), 'string', 'max:20'],
        ];
    }
}
```

El controlador de la API:

```php
namespace App\Http\Controllers\API;

use App\Exceptions\ReglaNegocioException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;

class ClienteController extends Controller
{
    public function index()
    {
        return ClienteResource::collection(Cliente::orderBy('id')->paginate());
    }

    public function store(ClienteRequest $request)
    {
        $cliente = Cliente::create($request->validated());

        return new ClienteResource($cliente->refresh());
    }

    public function show(Cliente $cliente)
    {
        return new ClienteResource($cliente);
    }

    public function update(ClienteRequest $request, Cliente $cliente)
    {
        $cliente->update($request->validated());

        return new ClienteResource($cliente);
    }

    public function destroy(Cliente $cliente)
    {
        if ($cliente->presupuestos()->exists()) {
            throw new ReglaNegocioException('El cliente tiene presupuestos y no se puede borrar.');
        }
        $cliente->delete();

        return response()->noContent();
    }
}
```

```php
// routes/api.php, dentro del grupo auth:sanctum de 7.2
Route::apiResource('clientes', API\ClienteController::class)->parameters(['clientes' => 'cliente']);
```

> **Aviso.** La autorización (`Gate::authorize()`) se añade en [7.6](./RA7_6_autorizacion.md): mientras no existan las _Policies_, Laravel deniega cualquier comprobación y todas las rutas responderían 403.

## Ejercicios

1. **Clientes.** Crea `GestionRequest`, adapta `ClienteRequest`, escribe `ClienteController` y su ruta, y pruébalos en _Swagger UI_ ([7.1](./RA7_1_swagger.md)). Comprueba que el formulario web de clientes sigue validando igual.

2. **Los Request.** Todos los de la web pasan a heredar de `GestionRequest`, con `...$this->obligatorio()` donde decían `required`, y `$this->route('modelo') ?? $this->route('id')` donde usaban el id de la ruta. Además:

    | Request | Cambio para la API |
    |:--|:--|
    | `ObraRequest` | `descripcion`: obligatoria en la web, opcional en la API, como dice el contrato (`$this->esApi() ? 'nullable' : 'required'`). En `after()`: el cliente no se puede cambiar si la obra tiene presupuestos o ingresos de otro cliente (422 en `cliente_id`) |
    | `PresupuestoRequest`, `IngresoRequest`, `GastoRequest` | En `after()`, si el campo no llega (modificación parcial), compara con el valor del registro: `$this->input('cliente_id', $presupuesto?->cliente_id)` |
    | `LineaParteTrabajoRequest` (nuevo, solo API) | `trabajador_id`: obligatorio, `exists:trabajadores,id`; `horas`: obligatorio, `numeric`, `between:0,24`; `coste_hora`: `nullable`, `numeric`, `min:0` |
    | `LoteLineasRequest` (nuevo, solo API) | `trabajador_ids`: `required`, `array`, `min:1`, `'max:' . config('gestionobras.max_lineas_lote')`; `trabajador_ids.*`: `exists:trabajadores,id`, `distinct`; `horas`: `required`, `numeric`, `between:0,24` |

    Las rutas web siguen funcionando con los mismos Request: los tests del Bloque 6 deben pasar igual.

3. **Los maestros.** `TrabajadorController`, `ProveedorController` y `EspecialistaController`, con la misma forma: su Request en `store` y `update`, y las reglas de borrado (**409**) del [contrato](./contrato-api.md#maestros).

4. **Presupuestos y obras.** Con `PresupuestoRequest`, `ObraRequest`, `GastoRequest` y `VincularPresupuestoRequest`.
    - `PresupuestoController`: `proveedor_ids` y `especialista_ids` se guardan con `sync`; el cliente debe ser el de su obra (**422** en `cliente_id`).
    - `ObraController`: crea y borra con `ObraService` ([6.12](./RA6_12_transacciones.md)). La regla del cliente ya la comprueba `ObraRequest`.
    - `GastoController`: solo `index`, `show` y `update` (`apiResource(...)->only([...])`). Crear o borrar responde **405**.

5. **Los recursos anidados.** Con su Request (`FaltaRequest`, `LineaPresupuestoRequest`, `IngresoRequest`, `ParteTrabajoRequest`, `ParteProveedorRequest`, `ParteEspecialistaRequest`, `LineaParteTrabajoRequest`), el padre en la URL, el `user_id` del usuario autenticado y un 404 si el hijo es de otro padre:

    Recurso | Rutas
    -----|----
    `FaltaController` | `apiResource('trabajadores.faltas')->scoped()`
    `LineaPresupuestoController` | `apiResource('presupuestos.lineas')->scoped()`
    `IngresoController` | `apiResource('obras.ingresos')`
    `ParteTrabajoController` | `apiResource('obras.partes-trabajo')`; la cabecera y sus `lineas` se crean en una transacción
    `ParteProveedorController`, `ParteEspecialistaController` | `apiResource('obras.partes-proveedor')` y `apiResource('obras.partes-especialista')`
    `LineaParteTrabajoController` | `apiResource('partes-trabajo.lineas')->scoped()`

6. **Rutas extra.** Según el contrato:
    - Obras: `presupuestos-disponibles`, `vincular-presupuesto` y los participantes `trabajadores`, `proveedores` y `especialistas`.
    - `GET /trabajadores/{trabajador}/obras` y `/lineas-trabajo`, y `GET /proveedores/{proveedor}/obras` y `/especialistas/{especialista}/obras`.
    - Alta de líneas por lote: `POST /partes-trabajo/{parteTrabajo}/lineas/lote`. Añade la clave `max_lineas_lote` a `config/gestionobras.php`, valida con `LoteLineasRequest` y crea las líneas en una transacción. Para responder 201 con una colección: `LineaParteTrabajoResource::collection($lineas)->response()->setStatusCode(201)`.

7. **Estadísticas y calendario.** `GET /estadisticas` y `GET /calendario` con el formato del contrato. La lógica va en servicios (`App\Services\Estadisticas` y `App\Services\Calendario`), que reutilizará la web en el Bloque 9.

8. **Investiga.** Responde en tu _pull request_:
    - Haz `PUT /api/v1/clientes/1` con solo `{ "telefono": "600000000" }`. ¿Qué responde? ¿Y el formulario web si dejas el nombre vacío? ¿Qué parte de `GestionRequest` marca la diferencia?
    - Quita `refresh()` en `store` y crea un cliente sin apellidos. ¿Qué campos faltan en la respuesta?
    - Pide `/api/v1/obras/1/ingresos/7` con un ingreso de otra obra. ¿Quién responde 404: `scoped()` o tu código?
    - Prueba cada ruta desde _Swagger UI_: ¿responde lo que dice la especificación?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque7/RA7_3_*.php tests/Feature/
php artisan test --filter=RA7_3
```

Resultado esperado: `Tests: 82 passed`. `RA7_3_ClientesApiTest` comprueba el ejemplo en detalle, y `RA7_3_ControladoresTest`, los 13 recursos: listar, ver, crear (201 con el registro completo), validar (422), modificar, borrar (204), el 404 con otro padre, la ficha de Gastos, las reglas de negocio (409), las rutas extra, el lote, las estadísticas y el calendario.

## Git

Rama `RA7_3`, con un commit por grupo: `refactor: Form Request comunes a la web y la API`, `feat: API de clientes`, `feat: API de los maestros`, `feat: API de presupuestos y obras`, `feat: recursos anidados`, `feat: rutas extra`.

---

**Siguiente:** [7.4. Búsqueda, orden y paginación](./RA7_4_busqueda.md)
<!-- {% endraw %} -->
