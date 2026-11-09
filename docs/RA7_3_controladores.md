# 7.3. Controladores de recursos
<!-- {% raw %} -->

**Referencia teórica:** [Controladores de recursos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/07_resourceController.html) · [Ejercicios de controladores de recursos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/074_ejerciciosControladoresRecursos.html) · [Laravel: Resource controllers](https://laravel.com/docs/13.x/controllers#api-resource-routes)

Un controlador por recurso del [contrato](./contrato-api.md), en `App\Http\Controllers\API`, que responde con los Resources de [7.2](./RA7_2_recursos.md). En este apartado se crean **todos**.

## Conceptos clave

- `php artisan make:controller API/ClienteController --api --model=Cliente` genera los cinco métodos: `index`, `store`, `show`, `update` y `destroy`. `Route::apiResource('clientes', …)` crea sus rutas.
- `->parameters(['trabajadores' => 'trabajador'])`: Laravel singulariza en inglés (`trabajadore`), así que conviene fijar los nombres de los parámetros.
- **Recursos anidados:** `apiResource('obras.ingresos', …)` crea `/obras/{obra}/ingresos/{ingreso}`. Con `->scoped()`, Laravel comprueba que el hijo pertenece al padre si encuentra la relación por el nombre del parámetro (`ingreso` → `$obra->ingresos()`). Si el nombre no coincide (`parteTrabajo` → `partesTrabajo`), se comprueba en el controlador: `abort_if($parte->obra_id !== $obra->id, 404)`.
- **Códigos de respuesta:** un Resource devuelto desde `store` responde **201**; `destroy` responde **204** con `response()->noContent()`; un error de validación, **422** (lo hace `validate()`); una regla de negocio, **409**, con la `ReglaNegocioException` de [6.12](./RA6_12_transacciones.md).
- Al crear se devuelve `$modelo->refresh()`: sin él faltarían en la respuesta las columnas que no venían en la petición, como `codigo`.
- **Modificación parcial:** en `update`, los campos obligatorios se validan con `sometimes|required`, para que se pueda enviar solo lo que cambia.

## El ejemplo: clientes

```php
namespace App\Http\Controllers\API;

use App\Exceptions\ReglaNegocioException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index()
    {
        return ClienteResource::collection(Cliente::orderBy('id')->paginate());
    }

    public function store(Request $request)
    {
        $cliente = Cliente::create($request->validate($this->reglas()));

        return new ClienteResource($cliente->refresh());
    }

    public function show(Cliente $cliente)
    {
        return new ClienteResource($cliente);
    }

    public function update(Request $request, Cliente $cliente)
    {
        $cliente->update($request->validate($this->reglas($cliente)));

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

    private function reglas(?Cliente $cliente = null): array
    {
        $obligatorio = $cliente ? 'sometimes|required' : 'required';

        return [
            'nombre' => "$obligatorio|string|max:255",
            'apellidos' => 'nullable|string|max:255',
            'nif' => ['nullable', 'string', 'max:20', Rule::unique('clientes')->ignore($cliente)],
            'direccion' => "$obligatorio|string|max:255",
            'cod_postal' => 'nullable|string|max:10',
            'telefono' => "$obligatorio|string|max:20",
        ];
    }
}
```

```php
// routes/api.php, dentro del grupo auth:sanctum de 7.2
Route::apiResource('clientes', API\ClienteController::class)->parameters(['clientes' => 'cliente']);
```

> **Aviso.** La autorización (`Gate::authorize()`) se añade en [7.6](./RA7_6_autorizacion.md): mientras no existan las _Policies_, Laravel deniega cualquier comprobación y todas las rutas responderían 403.

## Ejercicios

1. **Clientes.** Escribe `ClienteController` y su ruta, y pruébalos en _Swagger UI_ ([7.1](./RA7_1_swagger.md)).

2. **Los maestros.** `TrabajadorController`, `ProveedorController` y `EspecialistaController`, con la misma forma. Validación y reglas de borrado (**409**) del [contrato](./contrato-api.md#maestros).

3. **Presupuestos y obras.**
    - `PresupuestoController`: `proveedor_ids` y `especialista_ids` se guardan con `sync`; el cliente debe ser el de su obra (**422** en `cliente_id`).
    - `ObraController`: crea y borra con `ObraService` ([6.12](./RA6_12_transacciones.md)); el cliente no se puede cambiar si la obra tiene presupuestos o ingresos de otro.
    - `GastoController`: solo `index`, `show` y `update` (`apiResource(...)->only([...])`). Crear o borrar responde **405**.

4. **Los recursos anidados.** Con el padre en la URL, el `user_id` del usuario autenticado y un 404 si el hijo es de otro padre:

    Recurso | Rutas
    -----|----
    `FaltaController` | `apiResource('trabajadores.faltas')->scoped()`
    `LineaPresupuestoController` | `apiResource('presupuestos.lineas')->scoped()`
    `IngresoController` | `apiResource('obras.ingresos')`
    `ParteTrabajoController` | `apiResource('obras.partes-trabajo')`; la cabecera y sus `lineas` se crean en una transacción
    `ParteProveedorController`, `ParteEspecialistaController` | `apiResource('obras.partes-proveedor')` y `apiResource('obras.partes-especialista')`
    `LineaParteTrabajoController` | `apiResource('partes-trabajo.lineas')->scoped()`

5. **Rutas extra.** Según el contrato:
    - Obras: `presupuestos-disponibles`, `vincular-presupuesto` y los participantes `trabajadores`, `proveedores` y `especialistas`.
    - `GET /trabajadores/{trabajador}/obras` y `/lineas-trabajo`, y `GET /proveedores/{proveedor}/obras` y `/especialistas/{especialista}/obras`.
    - Alta de líneas por lote: `POST /partes-trabajo/{parteTrabajo}/lineas/lote`. Añade la clave `max_lineas_lote` a `config/gestionobras.php`, valida el array y cada elemento (`trabajador_ids.*`, `distinct`) y crea las líneas en una transacción. Para responder 201 con una colección: `LineaParteTrabajoResource::collection($lineas)->response()->setStatusCode(201)`.

6. **Estadísticas y calendario.** `GET /estadisticas` y `GET /calendario` con el formato del contrato. La lógica va en servicios (`App\Services\Estadisticas` y `App\Services\Calendario`), que reutilizará la web en el Bloque 9.

7. **Investiga.** Responde en tu _pull request_:
    - Quita `refresh()` en `store` y crea un cliente sin apellidos. ¿Qué campos faltan en la respuesta?
    - Pide `/api/v1/obras/1/ingresos/7` con un ingreso de otra obra. ¿Quién responde 404: `scoped()` o tu código?
    - Prueba cada ruta desde _Swagger UI_: ¿responde lo que dice la especificación?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque7/RA7_3_*.php tests/Feature/
php artisan test --filter=RA7_3
```

Resultado esperado: `Tests: 80 passed`. `RA7_3_ClientesApiTest` comprueba el ejemplo en detalle, y `RA7_3_ControladoresTest`, los 13 recursos: listar, ver, crear (201 con el registro completo), validar (422), modificar, borrar (204), el 404 con otro padre, la ficha de Gastos, las reglas de negocio (409), las rutas extra, el lote, las estadísticas y el calendario.

## Git

Rama `RA7_3`, con un commit por grupo: `feat: API de clientes`, `feat: API de los maestros`, `feat: API de presupuestos y obras`, `feat: recursos anidados`, `feat: rutas extra`.

---

**Siguiente:** [7.4. Búsqueda, orden y paginación](./RA7_4_busqueda.md)
<!-- {% endraw %} -->
