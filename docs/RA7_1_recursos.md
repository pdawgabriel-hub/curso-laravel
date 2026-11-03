# 7.1. API REST y controladores de recursos

**Referencia teórica:** [API y REST](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/061_api_y_rest.html) · [Esqueleto de la API](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/062_esqueleto_API.html) · [Controladores de recursos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/07_resourceController.html) · [Laravel: API Resources](https://laravel.com/docs/13.x/eloquent-resources)

Implementar todos los recursos del [contrato](./contrato-api.md) en `/api/v1`, con controladores en `App\Http\Controllers\API` y respuestas con API Resources.

## Conceptos clave

- `php artisan install:api` instala Sanctum y crea `routes/api.php` (prefijo `/api`).
- `Route::apiResource('clientes', API\ClienteController::class)` genera `index`, `store`, `show`, `update` y `destroy`.
- **Recursos anidados**: `apiResource('obras.ingresos', …)` → `/obras/{obra}/ingresos/{ingreso}`. Con `->scoped()`, Laravel comprueba que el hijo pertenece al padre si encuentra la relación por el nombre del parámetro (`ingreso` → `$obra->ingresos()`); si el nombre no coincide (`parteTrabajo` → `partesTrabajo`), la comprobación se hace en el controlador con `abort_if($parte->obra_id !== $obra->id, 404)`.
- `->parameters(['trabajadores' => 'trabajador'])`: Laravel singulariza en inglés (`trabajadore`), así que conviene fijar los nombres de los parámetros.
- **API Resource** (`php artisan make:resource ClienteResource`): `array_merge(parent::toArray($request), ['nombre_completo' => …])` añade los campos calculados. Un listado se devuelve con `ClienteResource::collection($query->paginate())`, lo que produce `data`, `links` y `meta`.
- Al crear se devuelve `new ClienteResource($cliente->refresh())`: sin `refresh()` faltarían las columnas que no venían en la petición. Un `Resource` devuelto desde `store` responde con **201** automáticamente.

## Ejercicios

1. **Esqueleto.**

    ```bash
    php artisan install:api
    ```

    Añadir `HasApiTokens` al modelo `User`. En `routes/api.php`:

    ```php
    Route::prefix('v1')->name('api.')->group(function () {
        Route::middleware('auth:sanctum')->group(function () {
            Route::apiResource('clientes', API\ClienteController::class)->parameters(['clientes' => 'cliente']);
            // …
        });
    });
    ```

    > **Importante.** Sin `->name('api.')`, los nombres de las rutas de la API (`clientes.index`) sustituyen a los de la web y las vistas generan URLs de la API.

2. **Clientes (ejemplo guiado).** `php artisan make:controller API/ClienteController --api` y `ClienteResource`. Validación, respuestas y regla de borrado según el contrato. Para la validación, el mismo `ClienteRequest` de la web sirve si sus reglas usan `sometimes|required` en la actualización, o se crea uno específico de la API.

    > **Aviso.** La autorización (`Gate::authorize()`) se añade en [7.4](./RA7_4_autorizacion.md): mientras no existan las Policies, Laravel deniega cualquier comprobación y todas las rutas responderían 403.

3. **Resto de recursos.** Aplicar el patrón a todos los recursos del contrato, en este orden: trabajadores y faltas, proveedores, especialistas, presupuestos y líneas, obras (con `ObraService`), gastos (`only(['index', 'show', 'update'])`), ingresos, partes de trabajo y sus líneas, partes de proveedor y de especialista.

4. **Endpoints adicionales de obras.** `presupuestos-disponibles`, `vincular-presupuesto` (el asistente de Odoo) y los participantes `trabajadores`, `proveedores` y `especialistas`.

5. **Estadísticas y calendario.** `GET /estadisticas` y `GET /calendario` con el formato del contrato. La lógica va en servicios (`App\Services\Estadisticas` y `App\Services\Calendario`), que reutilizará la web en el Bloque 9.

## Comprueba tu solución automáticamente

Copiar las clases base de los tests de la API (no son tests: aportan `actuarComo()`, `assertImporte()` y `assertListado()`) y el test del apartado:

```bash
E=/var/www/curso-laravel/evaluacion/tests
cp $E/TestCase.php tests/
cp $E/Feature/FeatureTestCase.php tests/Feature/
cp /var/www/curso-laravel/docs/materiales/tests/bloque7/RA7_1_ClientesApiTest.php tests/Feature/
php artisan test --filter=RA7_1
```

Resultado esperado: `Tests: 10 passed`. El resto de recursos se comprueba con peticiones manuales (por ejemplo, con la extensión REST Client de VS Code o con `curl`) y, al final del bloque, con la batería de evaluación.

## Git

Rama `RA7_1` con un commit por recurso, o una rama por grupo de recursos (`RA7_1_obras`…).

---

**Siguiente:** [7.2. Búsqueda, orden y paginación](./RA7_2_busqueda.md)
