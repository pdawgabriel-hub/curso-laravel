# Evaluación

## Cómo se evalúa

El proyecto se evalúa con tests automáticos, en dos niveles:

| Nivel | Cuándo | Qué comprueba |
|:--|:--|:--|
| Tests de apartado | Al terminar cada apartado | El ejercicio del apartado (`RA5_3_RutasTest`, `RA7_3_ControladoresTest`…) |
| Batería de evaluación | Al terminar el Bloque 7 | La API completa y los modelos, según el [contrato](./contrato-api.md), más el [escenario completo](./anexo_escenario.md) |

La batería se entrega como una carpeta `tests/` completa que sustituye a la del proyecto. **El proyecto está aprobado cuando toda la batería está en verde**, igual que se haría con una batería entregada al final de un módulo: no se conocen los tests de antemano, pero sí el contrato que comprueban.

> **Importante.** Para que la evaluación tenga sentido, la batería se abre **después** de terminar el Bloque 7. La excepción son los tests de modelo (`Unit/Models`), que solo comprueban lo que el contrato fija (`$fillable`, tabla, relaciones y factory) y se usan ya en [6.8](./RA6_8_relaciones.md) y [6.15](./RA6_15_proyecto.md).

## Contenido de la batería

| Fichero | Tests |
|:--|--:|
| `Unit/Models/*Test.php` (17 modelos) | 112 |
| `Unit/UserTest.php` | 7 |
| `Feature/Api/AutenticacionApiTest.php` | 9 |
| `Feature/Api/AutorizacionApiTest.php`, la tabla de permisos | 25 |
| `Feature/Api/ClienteApiTest.php` | 22 |
| `Feature/Api/TrabajadorApiTest.php` · `FaltaApiTest.php` | 14 · 17 |
| `Feature/Api/ProveedorApiTest.php` · `EspecialistaApiTest.php` | 12 · 11 |
| `Feature/Api/PresupuestoApiTest.php` · `LineaPresupuestoApiTest.php` | 21 · 11 |
| `Feature/Api/ObraApiTest.php` · `GastoApiTest.php` · `IngresoApiTest.php` | 24 · 18 · 16 |
| `Feature/Api/ParteTrabajoApiTest.php` · `LineaParteTrabajoApiTest.php` | 16 · 18 |
| `Feature/Api/ParteProveedorApiTest.php` · `ParteEspecialistaApiTest.php` | 16 · 16 |
| `Feature/Api/BitacoraApiTest.php` · `ArchivoApiTest.php` | 14 · 9 |
| `Feature/Api/EstadisticaApiTest.php` · `CalendarioApiTest.php` | 10 · 4 |
| `Feature/Escenario/EscenarioCompletoTest.php` | 3 |
| **Total** | **425** |

Cada recurso comprueba su CRUD, la validación de cada campo, los valores admitidos, la búsqueda, los filtros, la paginación con `links` y `meta`, el 404 con otro padre y el **403 a un usuario sin rol** en cada operación, además de que la base de datos no cambie.

## Procedimiento

1. Terminar el Bloque 7 y fusionar todo en `main`, con la CI en verde.
2. Crear una rama para la evaluación y copiar la batería:

   ```bash
   git switch main && git pull
   git switch -c evaluacion
   mkdir -p tests.backup && cp -r tests/. tests.backup/   # conserva los tests propios
   cp -r /var/www/curso-laravel/evaluacion/tests/. tests/
   ```

3. Configurar el administrador en `.env` y `.env.example` (los tests leen `config('app.admin.email')`):

   ```ini
   ADMIN_EMAIL=admin@gestion-obras.test
   ```

4. Ejecutar la batería por partes, de lo más básico a lo más completo:

   ```bash
   php artisan test --testsuite=Unit
   php artisan test tests/Feature/Api/AutenticacionApiTest.php
   php artisan test tests/Feature/Api
   php artisan test tests/Feature/Escenario
   ```

5. Corregir el código (no los tests) hasta que todo pase, con un commit por cada corrección significativa:

   ```bash
   git commit -am "fix: devuelve 409 al borrar un cliente con presupuestos"
   ```

6. Cuando `php artisan test` esté en verde, abrir la pull request `evaluacion → main` con el resumen de la ejecución y, tras fusionarla, crear el tag `evaluacion-api`.

> **Consejo.** Si un test falla por un detalle del contrato (el nombre de un campo, el orden del `$fillable`, un código de estado), el mensaje de PHPUnit indica exactamente qué esperaba. Es la misma situación que con una batería entregada al final del curso: conviene leer el contrato con atención antes de empezar el Bloque 7.

## Errores frecuentes

| Síntoma | Causa habitual |
|:--|:--|
| `Failed asserting that an array has the key 'telefono'` al crear | Se devuelve el modelo recién creado sin `refresh()`: faltan las columnas que no se enviaron |
| Rutas web que devuelven JSON o al revés | `apiResource('clientes')` sin el prefijo de nombre `api.` pisa `clientes.index` de la web |
| 500 en vez de 409 al borrar | La excepción de integridad de la base de datos llega sin capturar: hay que comprobar la regla antes de borrar |
| `Call to undefined method …::factory()` | Falta `use HasFactory` en el modelo |
| Los tests de modelo fallan solo por el orden | `$fillable` debe estar en el orden del contrato |
| 403 en todas las rutas con el administrador | `config('app.admin.email')` no está definido o `Gate::before` no devuelve `true` |
| El propietario sale `null` o el de la petición | `user_id` debe asignarse en el controlador con el usuario autenticado, después de validar |
| 403 esperado y llega 200 o 201 | Falta `Gate::authorize()` en esa acción: hay que revisar todas, también los recursos anidados y las rutas adicionales |
| 422 esperado y llega 403, o al revés | El orden importa: primero se autoriza y después se valida |
| `Class "Database\Factories\GastoFactory" not found` | La ficha de Gastos no debe crearse con factory: la crea la obra |
