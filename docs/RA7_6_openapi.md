---
title: 7.6. Documentación OpenAPI
parent: Bloque 7. Servicios web
nav_order: 6
---

# 7.6. Documentación OpenAPI
{: .no_toc }

{: .nota }
> **Referencia teórica:** [OpenAPI Specification 3.0](https://spec.openapis.org/oas/v3.0.3) · [Swagger UI](https://swagger.io/tools/swagger-ui/)

1. TOC
{:toc}

## Objetivo

Publicar la especificación de la API en formato OpenAPI 3 y una página con Swagger UI para probarla desde el navegador.

## Conceptos clave

- La especificación describe rutas (`paths`), parámetros, cuerpos (`requestBody`), respuestas por código y esquemas reutilizables (`components/schemas`).
- `components/securitySchemes/bearerAuth` (`type: http`, `scheme: bearer`) permite autenticarse en Swagger UI con un token de 7.3.
- Swagger UI se carga desde un CDN en una vista Blade y lee `public/openapi.json`.

## Ejercicios

1. `public/openapi.json` con `openapi: 3.0.3`, `info`, `bearerAuth` y, como mínimo, tokens, clientes (con todos sus métodos y los códigos 200, 201, 204, 401, 403, 404, 409 y 422), obras, presupuestos, ingresos de una obra y la ficha de Gastos.
2. Esquemas `Cliente`, `Obra`, `Presupuesto` y `Error` en `components/schemas`, reutilizados con `$ref`.
3. Vista `documentacion.blade.php` con Swagger UI y la ruta web `GET /api/documentacion`.
4. Completar la especificación con el resto de recursos.

## Comprobación

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque7/RA7_6_DocumentacionApiTest.php tests/Feature/
php artisan test --filter=RA7_6
```

Resultado esperado: `Tests: 4 passed`. Además, en <http://gestion-obras.test/api/documentacion>, *Authorize* con un token y *Try it out* en `GET /api/v1/clientes` deben devolver el listado.

## Git

Rama `RA7_6`. Al fusionar: `git tag -a bloque-7 -m "Bloque 7: servicios web" && git push --tags`, y empezar la [evaluación](evaluacion.html).
