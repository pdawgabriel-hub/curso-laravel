---
title: Evaluación
nav_order: 11
---

# Evaluación
{: .no_toc }

1. TOC
{:toc}

## Cómo se evalúa

El proyecto se evalúa con tests automáticos, en dos niveles:

| Nivel | Cuándo | Qué comprueba |
|:--|:--|:--|
| Tests de apartado | Al terminar cada apartado | El ejercicio del apartado (`RA5_3_RutasTest`, `RA7_1_ClientesApiTest`…) |
| Batería de evaluación | Al terminar el Bloque 7 | La API completa y los modelos, según el [contrato](contrato-api.html), más el [escenario completo](anexo_escenario.html) |

La batería se entrega como una carpeta `tests/` completa que sustituye a la del proyecto. **El proyecto está aprobado cuando toda la batería está en verde**, igual que se haría con una batería entregada al final de un módulo: no se conocen los tests de antemano, pero sí el contrato que comprueban.

{: .importante }
Para que la evaluación tenga sentido, la batería se abre **después** de terminar el Bloque 7. Hasta entonces la referencia es el [contrato de la API](contrato-api.html): rutas, campos, `$fillable`, validaciones y códigos de respuesta.

## Contenido de la batería

| Fichero | Tests |
|:--|--:|
| `Unit/Models/*Test.php` (17 modelos) | 108 |
| `Feature/Api/AutenticacionApiTest.php` | 9 |
| `Feature/Api/ClienteApiTest.php` | 18 |
| `Feature/Api/TrabajadorApiTest.php` · `FaltaApiTest.php` | 9 · 7 |
| `Feature/Api/ProveedorApiTest.php` · `EspecialistaApiTest.php` | 6 · 6 |
| `Feature/Api/PresupuestoApiTest.php` · `LineaPresupuestoApiTest.php` | 14 · 5 |
| `Feature/Api/ObraApiTest.php` · `GastoApiTest.php` · `IngresoApiTest.php` | 17 · 12 · 9 |
| `Feature/Api/ParteTrabajoApiTest.php` · `LineaParteTrabajoApiTest.php` | 8 · 6 |
| `Feature/Api/ParteProveedorApiTest.php` · `ParteEspecialistaApiTest.php` | 6 · 6 |
| `Feature/Api/BitacoraApiTest.php` · `ArchivoApiTest.php` | 9 · 8 |
| `Feature/Api/EstadisticaApiTest.php` · `CalendarioApiTest.php` | 9 · 2 |
| `Feature/Escenario/EscenarioCompletoTest.php` | 3 |
| **Total** | **277** |

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

{: .consejo }
Si un test falla por un detalle del contrato (el nombre de un campo, el orden del `$fillable`, un código de estado), el mensaje de PHPUnit indica exactamente qué esperaba. Es la misma situación que con una batería entregada al final del curso: conviene leer el contrato con atención antes de empezar el Bloque 7.

## Errores frecuentes

| Síntoma | Causa habitual |
|:--|:--|
| `Failed asserting that an array has the key 'telefono'` al crear | Se devuelve el modelo recién creado sin `refresh()`: faltan las columnas que no se enviaron |
| Rutas web que devuelven JSON o al revés | `apiResource('clientes')` sin el prefijo de nombre `api.` pisa `clientes.index` de la web |
| 500 en vez de 409 al borrar | La excepción de integridad de la base de datos llega sin capturar: hay que comprobar la regla antes de borrar |
| `Call to undefined method …::factory()` | Falta `use HasFactory` en el modelo |
| Los tests de modelo fallan solo por el orden | `$fillable` debe estar en el orden del contrato |
| 403 en todas las rutas con el administrador | `config('app.admin.email')` no está definido o `Gate::before` no devuelve `true` |
| `Class "Database\Factories\GastoFactory" not found` | La ficha de Gastos no debe crearse con factory: la crea la obra |
