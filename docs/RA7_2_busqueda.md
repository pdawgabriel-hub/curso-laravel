---
title: 7.2. Búsqueda, orden y paginación
parent: Bloque 7. Servicios web
nav_order: 2
---

# 7.2. Búsqueda, orden y paginación
{: .no_toc }

{: .nota }
> **Referencia teórica:** [Helper para la búsqueda](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/073_helperBusqueda.html) · [Laravel: Pagination](https://laravel.com/docs/13.x/pagination)

1. TOC
{:toc}

## Objetivo

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

1. Clase `App\Support\Listado` con un método estático `aplicar($query, Request $request, array $buscables, array $filtros = [])` que devuelve el paginador.
2. Cada modelo declara sus campos buscables (por ejemplo, `public const BUSCABLES = ['codigo', 'nombre', 'apellidos', 'nif', 'telefono'];`), según el contrato.
3. Todos los `index` de la API usan el helper, con los filtros de cada recurso (`estado`, `obra_id`, `cliente_id`, `tipo`…).
4. El mismo helper sirve para los listados web: añadir un buscador a los listados de clientes, obras y presupuestos.

## Comprobación

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque7/RA7_2_BusquedaApiTest.php tests/Feature/
php artisan test --filter=RA7_2
```

Resultado esperado: `Tests: 4 passed`.

## Git

Rama `RA7_2`, commit `feat: búsqueda multipalabra, orden y paginación comunes`.
