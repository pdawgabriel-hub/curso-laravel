# 7.3. Búsqueda, orden y paginación

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

## Ejercicios

1. Crea `app/Helpers/FilterHelper.php`, con el método estático `applyFilter($query, Request $request, array $buscables, array $filtros = [])`, que devuelve el paginador. Empieza haciéndolo en el `index()` de `ClienteController` y refactoriza después al _helper_, como en la referencia teórica. Este proyecto usa el parámetro `search`, no `q`.
2. Cada modelo declara sus campos buscables (por ejemplo, `public const BUSCABLES = ['codigo', 'nombre', 'apellidos', 'nif', 'telefono'];`), según el contrato.
3. Todos los `index` de la API usan el helper, con los filtros de cada recurso (`estado`, `obra_id`, `cliente_id`, `tipo`…).
4. El mismo helper sirve para los listados web: añadir un buscador a los listados de clientes, obras y presupuestos.

5. **Investiga.** Responde en tu _pull request_: pide `/api/v1/clientes?sort=password`. ¿Qué pasaría si `sort` llegara directamente a `orderBy` sin comprobarlo? Mira también `links` en la respuesta con `?per_page=2&search=a`: ¿qué conserva `withQueryString()`?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque7/RA7_3_BusquedaApiTest.php tests/Feature/
php artisan test --filter=RA7_3
```

Resultado esperado: `Tests: 5 passed`.

## Git

Rama `RA7_3`, commit `feat: búsqueda multipalabra, orden y paginación comunes`.

---

**Siguiente:** [7.4. Autenticación con _Sanctum_](./RA7_4_sanctum.md)
