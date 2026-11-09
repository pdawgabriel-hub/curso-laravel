# 8.3. Middleware para React-Admin

**Referencia teórica:** [Middleware para adaptar la respuesta](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/072_middlewareReactAdmin.html) · [Laravel: Middleware](https://laravel.com/docs/13.x/middleware#middleware-groups) · [ra-data-json-server](https://github.com/marmelab/react-admin/tree/master/packages/ra-data-json-server)

El _dataProvider_ `json-server` de React-Admin y nuestra API no hablan igual. En lugar de cambiar los controladores, un **middleware** traduce la petición antes de llegar al controlador y la respuesta después de salir de él.

## Conceptos clave

| | La API del Bloque 7 | Lo que usa `json-server` |
|---|---|---|
| Página | `page`, `per_page` | `_start`, `_end` (posiciones, no páginas) |
| Orden | `sort`, `order` (`asc`) | `_sort`, `_order` (`ASC`) |
| Búsqueda | `search` | `q` |
| Listado | `{ data: [...], links, meta: { total } }` | `[...]` y el total en la cabecera `X-Total-Count` |
| Un registro | `{ data: { ... } }` | `{ ... }` |
| Errores 422 | `errors.nombre: ["…"]` | `errors.nombre: "…"` |

- Un middleware recibe la petición y el siguiente paso (`$next`). Lo que hace **antes** de `$next($request)` afecta a la petición; lo que hace **después**, a la respuesta.
- La API la usan también _Swagger_, los tests y cualquier otra aplicación, que esperan `data`, `links` y `meta`. Por eso el middleware **solo actúa si la petición lleva la cabecera `X-Cliente: react-admin`**, que añadirá el _dataProvider_ en [8.4](./RA8_4_proveedores.md). En la referencia teórica actúa siempre; aquí, la batería de evaluación debe seguir pasando en esta rama.
- El total sale de `meta.total`, que ya calcula el paginador; así sirve también para los recursos anidados.

## Ejercicios

1. **El middleware.**

    ```bash
    php artisan make:middleware ReactAdminResponse
    ```

    ```php
    <?php

    namespace App\Http\Middleware;

    use Closure;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Http\Request;
    use Symfony\Component\HttpFoundation\Response;

    /**
     * Adapta las peticiones y las respuestas de la API al dataProvider json-server de React-Admin.
     * Solo actúa si la petición lleva la cabecera X-Cliente: react-admin; el resto de clientes
     * (Swagger, los tests, una app móvil) reciben la API tal cual: data, links y meta.
     */
    class ReactAdminResponse
    {
        public function handle(Request $request, Closure $next): Response
        {
            if ($request->header('X-Cliente') !== 'react-admin') {
                return $next($request);
            }

            // Antes: los parámetros de React-Admin pasan a ser los de la API.
            if ($request->filled('_start')) {
                $porPagina = max(1, (int) $request->input('_end', 10) - (int) $request->input('_start'));
                $request->merge(['per_page' => $porPagina, 'page' => intdiv((int) $request->input('_start'), $porPagina) + 1]);
            }
            if ($request->filled('_sort')) {
                $request->merge(['sort' => $request->input('_sort'), 'order' => strtolower($request->input('_order', 'asc'))]);
            }
            if ($request->filled('q')) {
                $request->merge(['search' => $request->input('q')]);
            }

            $response = $next($request);

            // Después: sin data/links/meta, con el total en X-Total-Count y un mensaje por campo.
            if ($response instanceof JsonResponse) {
                $datos = $response->getData(true);
                if (is_array($datos) && array_key_exists('data', $datos)) {
                    if (isset($datos['meta']['total'])) {
                        $response->headers->set('X-Total-Count', (string) $datos['meta']['total']);
                    }
                    $response->setData($datos['data']);
                } elseif ($response->getStatusCode() === 422 && isset($datos['errors'])) {
                    $response->setData(['message' => $datos['message'] ?? '', 'errors' => array_map(fn ($m) => is_array($m) ? $m[0] : $m, $datos['errors'])]);
                }
            }

            return $response;
        }
    }
    ```

2. **Registrarlo en el grupo `api`.** En `bootstrap/app.php`:

    ```php
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(prepend: [
            \App\Http\Middleware\ReactAdminResponse::class,
        ]);
    })
    ```

3. **Pruébalo con `curl`**, con y sin la cabecera, y compara:

    ```bash
    TOKEN=$(curl -s -X POST http://gestion-obras.test/api/v1/tokens -H 'Accept: application/json' \
      -d email=jefe@gestion-obras.test -d password=password | jq -r .access_token)
    curl -s -i 'http://gestion-obras.test/api/v1/clientes?_start=0&_end=2&_sort=nombre&_order=ASC' \
      -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' -H 'X-Cliente: react-admin'
    ```

4. **Investiga.** Responde en tu _pull request_:
    - ¿Por qué es mejor un middleware que cambiar el `index()` de cada controlador, como propone la primera opción de la referencia teórica?
    - La referencia calcula el total con `$modelo::count()`. ¿Qué devolvería para `/obras/3/ingresos`? ¿Por qué aquí se usa `meta.total`?
    - Quita la condición de la cabecera y ejecuta `php artisan test`. ¿Qué tests fallan y por qué?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque8/RA8_3_MiddlewareTest.php tests/Feature/
php artisan test --filter=RA8_3
php artisan test
```

Resultado esperado: `Tests: 7 passed`, y la batería de [evaluación](./evaluacion.md) sigue en verde.

## Git

Rama `react-admin`, commit `feat: middleware ReactAdminResponse`.

---

**Siguiente:** [8.4. dataProvider y authProvider](./RA8_4_proveedores.md)
