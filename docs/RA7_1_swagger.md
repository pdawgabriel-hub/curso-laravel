# 7.1. Introducción a API y REST. Swagger

**Referencia teórica:** [Introducción a API y REST](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/06_crearAPI.html) · [Presentaciones](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/061_api_y_rest.html) · [L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger)

Hasta ahora, la aplicación solo respondía con páginas para un navegador. En este bloque va a ofrecer una **API**: datos en JSON que puede usar cualquier otro programa. Antes de programar nada, se **define** qué va a responder, y esa definición es el punto de partida del trabajo.

## REST

| Método | URL | Acción | Respuesta |
|---|---|---|---|
| GET | `/clientes` | Listar | 200 |
| POST | `/clientes` | Crear | 201 |
| GET | `/clientes/5` | Ver uno | 200, o 404 |
| PUT | `/clientes/5` | Modificar | 200, o 422 si los datos no son válidos |
| DELETE | `/clientes/5` | Borrar | 204 |

La API se versiona con el prefijo `/api/v1`, para poder cambiarla sin romper a quien ya la use.

## Swagger

_Swagger UI_ muestra un fichero **OpenAPI** como una página en la que se puede leer la documentación y probar la API. El fichero de este proyecto ya está escrito: [`gestion_obras_api.yaml`](https://github.com/pdawgabriel-hub/curso-laravel/blob/main/docs/materiales/swagger/gestion_obras_api.yaml). Junto con el [contrato de la API](./contrato-api.md), es la referencia de todo el bloque.

```yaml
paths:
  /clientes/{cliente}:
    delete:
      tags: [clientes]
      summary: Borra un registro de clientes
      responses:
        '204':
          description: Sin contenido
        '409':
          description: Regla de negocio
```

## Ejercicios

1. **Instala Swagger en el proyecto.**

    ```bash
    composer require darkaonline/l5-swagger
    php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
    mkdir -p storage/api-docs
    cp /var/www/curso-laravel/docs/materiales/swagger/gestion_obras_api.yaml storage/api-docs/
    ```

    En `config/l5-swagger.php`:

    ```php
    'docs_json' => 'gestion_obras_api.json',
    'docs_yaml' => 'gestion_obras_api.yaml',
    'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'yaml'),
    ```

2. **Explora la API.** Abre <http://gestion-obras.test/api/documentation>. Busca `DELETE /clientes/{cliente}`. ¿Qué códigos de respuesta tiene? Pulsa *Try it out* y ejecútalo: como aún no hemos creado ninguna ruta, responderá 404.

3. **Completa la especificación.** Faltan dos rutas del [contrato](./contrato-api.md). Añádelas a `storage/api-docs/gestion_obras_api.yaml`, y compruébalo en la página:
    - `POST /partes-trabajo/{parteTrabajo}/lineas/lote`, con las respuestas 201, 403 y 422;
    - `GET /user/observaciones`, con las respuestas 200 y 401.

4. **Investiga.** Responde en tu _pull request_:
    - ¿Qué diferencia hay entre 401 y 403? ¿Y entre 404 y 405?
    - ¿Por qué `DELETE /obras/{obra}` documenta 409 y `DELETE /presupuestos/{presupuesto}` no?

> **Nota.** Otra opción para ver el fichero, sin instalar nada en el proyecto, es el contenedor de _Laradock_: `docker compose up -d swagger-ui`, en <http://localhost:5555>.

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque7/RA7_1_SwaggerTest.php tests/Feature/
php artisan test --filter=RA7_1
```

Resultado esperado: `Tests: 5 passed`.

## Git

Rama `RA7_1`. Commits: `chore: instala l5-swagger`, `docs: especificación de la API`.

---

**Siguiente:** [7.2. Esqueleto de la API y API Resources](./RA7_2_recursos.md)
