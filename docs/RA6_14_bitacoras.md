# 6.14. Bitácoras y adjuntos

**Referencia teórica:** [Utilizar ficheros](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/0476_utilizarFicheros.html) · [Laravel: Polymorphic Relationships](https://laravel.com/docs/13.x/eloquent-relationships#polymorphic-relationships) · [Laravel: File Storage](https://laravel.com/docs/13.x/filesystem)

Observaciones, incidencias y archivos adjuntos para cualquier entidad, con tres tablas en lugar de las 21 que tenía el módulo de Odoo.

## Conceptos clave

- **Relación polimórfica**: `observaciones.observable_type` + `observable_id` apuntan a cualquier modelo. `morphMany(Observacion::class, 'observable')` en el padre, `morphTo()` en el hijo.
- **Mapa polimórfico** con `Relation::enforceMorphMap([...])` en `AppServiceProvider::boot()`: en `*_type` se guarda `obras` en vez de `App\Models\Obra`. Las claves coinciden con los segmentos de URL de la API (`partes-trabajo`), lo que permite resolver el padre desde la ruta en el Bloque 7. Como `enforceMorphMap` obliga a registrar **todos** los modelos que participan en relaciones polimórficas, también va `'users' => User::class` (Sanctum guarda los tokens de forma polimórfica).
- `Storage::put()`, `$file->store('archivos/obras')`, `Storage::delete($ruta)`, `Storage::download($ruta, $nombre)`; en los tests, `Storage::fake()`.

> **Nota.** Los adjuntos de este proyecto se guardan en el disco privado y se descargan a través de un controlador, porque son contratos y facturas. Para ficheros públicos se usa el disco `public` y el enlace `public/storage`. En Docker, `php artisan storage:link` genera una ruta absoluta que no sirve dentro del contenedor, así que el enlace se crea a mano: `cd public/ && ln -s ../storage/app/public storage`.

## Ejercicios

1. **Las tablas.** `morphs('observable')` crea las columnas `observable_type` y `observable_id`, con su índice.

    ```bash
    php artisan make:migration create_observaciones_table --create=observaciones
    ```

    Campo | Tipo | nullable
    -----|----|---
    observable | `morphs` | no
    texto | text | no
    user_id | clave ajena a `users`, `nullOnDelete` | sí

    ```bash
    php artisan make:migration create_incidencias_table --create=incidencias
    ```

    Campo | Tipo | nullable
    -----|----|---
    incidentable | `morphs` | no
    texto | text | no
    user_id | clave ajena a `users`, `nullOnDelete` | sí

    ```bash
    php artisan make:migration create_archivos_table --create=archivos
    ```

    Campo | Tipo | nullable
    -----|----|---
    archivable | `morphs` | no
    nombre_archivo | string | no
    ruta | string | no
    tipo | enum: `imagen`, `pdf`, `otro` | no

    Estas tablas no llevan _factory_ ni semilla: los registros se crean desde su padre (`$obra->observaciones()->create([...])`).

2. **Trait TieneBitacoras.** Trait con `observaciones()`, `incidencias()` (ambas ordenadas de la más nueva a la más antigua) y `archivos()`, y un evento `deleting` que borra las tres. Los archivos se borran **uno a uno** para que se dispare su propio `deleting`.

3. **Modelos Observacion, Incidencia y Archivo.** Con `$table`:

    - `Observacion`: `$fillable` `texto, user_id`; relaciones `observable()` (`morphTo`) y `usuario()`.
    - `Incidencia`: `$fillable` `texto, user_id`; relaciones `incidentable()` (`morphTo`) y `usuario()`.
    - `Archivo`: `$fillable` `nombre_archivo, ruta, tipo`; relación `archivable()` (`morphTo`), y en `deleting` borra su fichero del disco.

4. **Mapa polimórfico.** Registrar en `AppServiceProvider` las claves `users`, `clientes`, `trabajadores`, `proveedores`, `especialistas`, `obras`, `presupuestos`, `gastos`, `ingresos`, `partes-trabajo`, `partes-proveedor` y `partes-especialista`.

5. **En las vistas.** En los detalles de las entidades que los admiten (lista en el [contrato](./contrato-api.md#bitácoras-y-adjuntos)):

    - Sección de observaciones e incidencias con un formulario de una sola línea; el autor es el usuario conectado y se muestra con la fecha.
    - Sección de adjuntos con subida de fichero (`enctype="multipart/form-data"`), descarga y borrado.

    Rutas web de los formularios, protegidas con `auth`, que al guardar vuelven a la página anterior:

    | Método | URL | Nombre |
    |:--|:--|:--|
    | POST | `/{tipo}/{id}/observaciones` | `bitacoras.observaciones` |
    | POST | `/{tipo}/{id}/incidencias` | `bitacoras.incidencias` |
    | POST | `/{tipo}/{id}/archivos` | `bitacoras.archivos` |

    `{tipo}` es la clave del mapa polimórfico, limitada con `whereIn` a las entidades de cada lista.

6. **Investiga.** Responde en tu _pull request_: quita `enforceMorphMap` y añade una observación a una obra. ¿Qué se guarda en `observable_type`? ¿Qué problema habría si mañana cambias de carpeta el modelo?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_14_BitacorasTest.php tests/Feature/
php artisan test
```

Resultado esperado: los 5 tests de `RA6_14_BitacorasTest` pasan, y todo el bloque en verde.

## Git

Rama `RA6_14`, commit `feat: bitácoras y adjuntos polimórficos`.

---

**Siguiente:** [6.15. Proyecto: flujo completo de una obra por la web](./RA6_15_proyecto.md)
