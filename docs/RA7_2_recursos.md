# 7.2. Esqueleto de la API y API Resources
<!-- {% raw %} -->

**Referencia teórica:** [API y REST](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/061_api_y_rest.html) · [Esqueleto de la API](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/062_esqueleto_API.html) · [Laravel: API Resources](https://laravel.com/docs/13.x/eloquent-resources)

La API devuelve JSON, no vistas. Antes de los controladores, dos piezas: el esqueleto de rutas en `/api/v1` y un **API Resource** por entidad, que decide qué campos salen en cada respuesta. En este apartado se crean **todos** los Resources; los controladores que los usan llegan en [7.3](./RA7_3_controladores.md).

## Conceptos clave

- `php artisan install:api` instala Sanctum y crea `routes/api.php`, con el prefijo `/api`. Los apuntes de referencia usan `php artisan breeze:install api`, que además borra las vistas y Vite porque la interfaz pasa a otro proyecto. Aquí la interfaz _Blade_ sigue en la rama principal, así que no se usa (en el [Bloque 8](./RA8_1_breezeApi.md) sí, en otra rama).
- Un **API Resource** convierte un modelo en un array. `parent::toArray($request)` devuelve todas sus columnas; se le añaden los campos calculados del [contrato](./contrato-api.md).
- `new ClienteResource($cliente)` produce `{ "data": { … } }`. `ClienteResource::collection($clientes->paginate())` produce `{ "data": [ … ], "links": { … }, "meta": { … } }`.
- Un Resource puede incluir otro: `LineaParteTrabajoResource::collection($this->lineas)` dentro del parte.

## El ejemplo: clientes

```bash
php artisan make:resource ClienteResource
```

```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'nombre_completo' => $this->nombre_completo,
        ]);
    }
}
```

Pruébalo en `tinker`:

```php
(new App\Http\Resources\ClienteResource(App\Models\Cliente::first()))->response()->getData(true);
```

## Ejercicios

1. **El esqueleto.**

    ```bash
    php artisan install:api
    ```

    Añade el _trait_ `HasApiTokens` al modelo `User`. En `routes/api.php`, todo dentro de un grupo con prefijo y nombre:

    ```php
    Route::prefix('v1')->name('api.')->group(function () {
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('user', fn (Request $request) => $request->user());
            // los recursos, en 7.3
        });
    });
    ```

    > **Importante.** Sin `->name('api.')`, los nombres de las rutas de la API (`clientes.index`) sustituirían a los de la web, y las vistas generarían URLs de la API.

2. **Los Resources.** Uno por modelo, con `parent::toArray()` y estos campos añadidos:

    Resource | Campos que se añaden
    -----|----
    `ClienteResource` | `nombre_completo`
    `TrabajadorResource` | `nombre_completo`, `horas_totales`, `total_euros` ([6.13](./RA6_13_calculos.md))
    `FaltaResource` | `dias`
    `ProveedorResource` | `total`
    `EspecialistaResource` | —
    `PresupuestoResource` | `anio`, `base_imponible`, `cuota_iva`, `total`, `proveedor_ids` y `especialista_ids` (arrays de ids, con `pluck('id')`)
    `LineaPresupuestoResource` | `total_linea`
    `ObraResource` | los indicadores de `ResumenFinanciero` ([6.13](./RA6_13_calculos.md)) con los nombres del contrato (`total`, `horas_totales`, `coste_moo`, `estado_pago`, `gastos_totales`, `beneficio`, `pendiente_cobro`, `margen_proyectado`, `porcentaje_cobrado`, `salud`) y `gasto_id`
    `GastoResource` | `horas_trabajadas`, `coste_moo`, `coste_hora`, `coste_proveedores`, `coste_especialistas`, `gastos`, `ingresos`, `beneficio_real`, `debe`, `estado`
    `IngresoResource` | `anio`, `total_ingresos` (todos los ingresos de su obra) y `debe`
    `ParteTrabajoResource` | `horas_totales`, `coste_total` y `lineas` (con `LineaParteTrabajoResource`)
    `LineaParteTrabajoResource` | `coste_subtotal`, y la `fecha` y el `obra_id` de su parte
    `ParteProveedorResource`, `ParteEspecialistaResource` | —

    Las fechas salen con formato `Y-m-d`, gracias a los _casts_ de [6.2](./RA6_2_modelos.md).

3. **Investiga.** Responde en tu _pull request_:
    - Compara en `tinker` `App\Models\Obra::first()->toArray()` con lo que devuelve su Resource. ¿Qué aporta el Resource?
    - Quita `->name('api.')` y ejecuta `php artisan route:list --name=clientes`. ¿Qué rutas comparten nombre?

## Comprueba tu solución automáticamente

Copia las clases base de los tests de la API (no son tests: aportan `actuarComo()`, `assertImporte()` y `assertListado()`) y el test del apartado:

```bash
E=/var/www/curso-laravel/evaluacion/tests
cp $E/TestCase.php tests/
cp $E/Feature/FeatureTestCase.php tests/Feature/
cp /var/www/curso-laravel/docs/materiales/tests/bloque7/RA7_2_RecursosTest.php tests/Feature/
php artisan test --filter=RA7_2
```

Resultado esperado: `Tests: 16 passed`.

## Git

Rama `RA7_2`. Commits: `feat: esqueleto de la API`, `feat: API Resources`.

---

**Siguiente:** [7.3. Controladores de recursos](./RA7_3_controladores.md)
<!-- {% endraw %} -->
