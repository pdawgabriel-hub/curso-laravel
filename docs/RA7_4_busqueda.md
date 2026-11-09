# 7.4. Búsqueda, orden y paginación

**Referencia teórica:** [Helper para la búsqueda](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/073_helperBusqueda.html) · [Laravel: Pagination](https://laravel.com/docs/13.x/pagination)

Un único helper que aplique a todos los listados la búsqueda multipalabra, el orden, la paginación y los filtros por igualdad.

## Conceptos clave

- Búsqueda **multipalabra**: se divide `search` en palabras; cada palabra se exige con `AND` y debe aparecer en **alguno** de los campos buscables (`OR`). Así, "Juan Pérez" encuentra a un cliente con `nombre = Juan` y `apellidos = Pérez López`.
- El campo de orden se valida contra una lista permitida: nunca se pasa la entrada del usuario directamente a `orderBy`.
- `withQueryString()` conserva los parámetros en los enlaces de `links`.

```php
foreach (preg_split('/\s+/', trim($request->search)) as $palabra) {
    $query->where(function ($q) use ($campos, $palabra) {
        foreach ($campos as $campo) {
            $q->orWhere($campo, 'like', "%{$palabra}%");
        }
    });
}
```

## El ejemplo: el _helper_ y clientes

```php
namespace App\Helpers;

use Illuminate\Http\Request;

class FilterHelper
{
    public static function applyFilter($query, Request $request, array $buscables, array $filtros = [])
    {
        foreach ($filtros as $filtro) {
            if ($request->filled($filtro)) {
                $query->where($filtro, $request->input($filtro));
            }
        }

        if ($request->filled('search')) {
            foreach (preg_split('/\s+/', trim($request->search)) as $palabra) {
                $query->where(function ($q) use ($buscables, $palabra) {
                    foreach ($buscables as $campo) {
                        $q->orWhere($campo, 'like', "%{$palabra}%");
                    }
                });
            }
        }

        $permitidos = array_merge(['id', 'codigo', 'created_at'], $query->getModel()->getFillable());
        $sort = in_array($request->sort, $permitidos) ? $request->sort : 'id';

        return $query->orderBy($sort, $request->order === 'desc' ? 'desc' : 'asc')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();
    }
}
```

```php
// Cliente
public const BUSCABLES = ['codigo', 'nombre', 'apellidos', 'nif', 'telefono'];

// ClienteController
public function index(Request $request)
{
    return ClienteResource::collection(FilterHelper::applyFilter(Cliente::query(), $request, Cliente::BUSCABLES));
}
```

## Ejercicios

1. **El _helper_.** Empieza haciéndolo en el `index()` de `ClienteController` y refactoriza después a `app/Helpers/FilterHelper.php`, como en la referencia teórica. Este proyecto usa el parámetro `search`, no `q`.

2. **Los campos buscables y los filtros.** Cada modelo declara su constante `BUSCABLES`, y cada `index` de la API usa el _helper_ con sus filtros:

    Modelo | `BUSCABLES` | Filtros
    -----|-----|----
    `Cliente` | `codigo, nombre, apellidos, nif, telefono` | —
    `Trabajador` | `codigo, nombre, apellido` | —
    `Proveedor` | `codigo, nombre` | —
    `Especialista` | `codigo, nombre, tipo` | —
    `Presupuesto` | `codigo, nombre_cliente` | `obra_id`, `cliente_id`, `estado`
    `Obra` | `codigo, descripcion` | `cliente_id`
    `Gasto` | `codigo, direccion_obra` | `obra_id`
    `Ingreso` | `codigo, nombre_obra, tipo` | —
    `Falta` | `motivo` | `tipo`
    `ParteTrabajo` | `descripcion` | —
    `ParteProveedor`, `ParteEspecialista` | `descripcion, documento` | `proveedor_id` o `especialista_id`, `estado_pago`

    En los recursos anidados, la consulta de partida es la del padre: `FilterHelper::applyFilter($obra->ingresos(), …)`.

3. **También en la web.** Añade un buscador a los listados de clientes, obras y presupuestos, con el mismo _helper_ y un `<x-campo name="search">` en un formulario `GET`.

4. **Investiga.** Responde en tu _pull request_: pide `/api/v1/clientes?sort=password`. ¿Qué pasaría si `sort` llegara directamente a `orderBy` sin comprobarlo? Mira también `links` en la respuesta con `?per_page=2&search=a`: ¿qué conserva `withQueryString()`?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque7/RA7_4_BusquedaTest.php tests/Feature/
php artisan test --filter=RA7_4
```

Resultado esperado: `Tests: 13 passed`. Además de los casos de clientes, el test busca en un campo de cada recurso y comprueba los filtros.

## Git

Rama `RA7_4`, commit `feat: búsqueda multipalabra, orden y paginación comunes`.

---

**Siguiente:** [7.5. Autenticación con _Sanctum_](./RA7_5_sanctum.md)
