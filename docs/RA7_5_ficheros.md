# 7.5. Ficheros y bitácoras en la API

**Referencia teórica:** [Manejo de ficheros en la API](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/11_manejarFicheros.html) · [Laravel: File Uploads](https://laravel.com/docs/13.x/requests#files)

Rutas genéricas `/{tipo}/{id}/observaciones|incidencias|archivos` para todas las entidades que las admiten, resolviendo el padre con el mapa polimórfico de 6.9.

## Conceptos clave

- `Route::get('{tipo}/{id}/archivos', …)->whereIn('tipo', ArchivoController::TIPOS)->whereNumber('id')`: un tipo fuera de la lista no coincide con la ruta y da 404.
- `Relation::getMorphedModel($tipo)::findOrFail($id)` obtiene el modelo padre a partir del segmento de la URL.
- El autor de una observación se toma de `$request->user()`; nunca de la petición.
- `$request->file('archivo')->store("archivos/{$tipo}")` guarda en el disco privado (`storage/app/private`); la descarga pasa por un controlador que comprueba permisos.

## Ejercicios

1. `API\BitacoraController` (`index`, `store` y borrado de observaciones e incidencias) y `API\ArchivoController` (`index`, `store`, `descarga` y `destroy`), con las listas de entidades del contrato.
2. Clasificación del adjunto por MIME: `image/*` → `imagen`, `application/pdf` → `pdf`, el resto → `otro`.
3. Comprobar en los tests de la batería, más adelante, que borrar una entidad borra sus ficheros del disco.

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque7/RA7_5_ArchivosApiTest.php tests/Feature/
php artisan test --filter=RA7_5
```

Resultado esperado: `Tests: 5 passed`.

## Git

Rama `RA7_5`, commit `feat: bitácoras y adjuntos en la API`.

---

**Siguiente:** [7.6. Documentación OpenAPI](./RA7_6_openapi.md)
