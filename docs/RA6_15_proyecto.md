# 6.15. Proyecto: flujo completo de una obra por la web

**Referencia teórica:** [Ejercicios de bases de datos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/047_ejerciciosBD.html) · [Laravel: HTTP Tests](https://laravel.com/docs/13.x/http-tests)

Cierre del bloque: la aplicación debe poder usarse de principio a fin en el navegador. No hay código nuevo que aprender; se trata de terminar y conectar todas las pantallas de los apartados anteriores.

## El flujo

| Paso | Pantalla | Resultado |
|:--:|:--|:--|
| 1 | Iniciar sesión | Con un usuario de los datos de demostración |
| 2 | Clientes, trabajadores, proveedores y especialistas | Se dan de alta con sus formularios |
| 3 | Presupuestos | Presupuesto del cliente; en su detalle, una línea de 10 uds × 100 € → base `1.000,00`, IVA `210,00`, total `1.210,00` |
| 4 | Obras | Obra del cliente, que nace con su ficha de Gastos y estado `Pendiente` |
| 5 | Detalle de la obra | Se vincula el presupuesto libre. Sin aprobar, la obra sigue sin precio |
| 6 | Edición del presupuesto | Se aprueba → total de la obra `1.210,00` |
| 7 | Obra, pestaña *Partes de trabajo* | Dos trabajadores a 20 €/h, 8 h cada uno → `16,00` horas y `320,00` de mano de obra |
| 8 | Edición del trabajador | Subir su tarifa no cambia lo ya registrado |
| 9 | Obra, pestañas *Proveedores* y *Especialistas* | Proveedor de 200 € y especialista de 150 € → gastos `670,00` |
| 10 | Obra, pestaña *Ingresos* → ingreso de 500 € | Beneficio `-170,00`, pendiente `710,00`, margen `540,00`, `41,3` % cobrado, `Parcial`, `Rojo`; ficha `En proceso` |
| 11 | Obra, pestaña *Ingresos* → ingreso de 710 € | Beneficio `540,00`, `100,0` % cobrado, `Pagado`, `Verde`; ficha `Finalizada` |
| 12 | Borrar la obra | Rechazado: se vuelve atrás y se muestra el motivo |

## Ejercicios

1. **Rutas y formularios.** Comprueba que tienes todas las rutas de los apartados [6.6](./RA6_6_maestros.md) a [6.11](./RA6_11_partes.md) y las de [6.14](./RA6_14_bitacoras.md), con sus formularios, y que todas las pantallas usan los componentes de [6.5](./RA6_5_interfaz.md).

2. **Acciones en los detalles.** El detalle de cada registro enlaza lo que depende de él:
    - **Obra:** botones para añadir partes e ingresos, y para borrar cada uno; selector de presupuestos libres; observaciones, incidencias y adjuntos; botón de borrar la obra.
    - **Presupuesto:** formulario de líneas y botón de quitar cada una.
    - **Trabajador:** sus faltas, con el texto del tipo (`Vacaciones`, `Baja médica`…) y botón de borrar.

3. **Mensajes.** Toda regla de negocio rechazada vuelve atrás con `session('error')`, y el layout la muestra.

4. **Datos de demostración.** Comprueba que `php artisan migrate:fresh --seed` crea los usuarios de [6.7](./RA6_7_usuarios.md) y las veinte obras de [6.3](./RA6_3_seeders.md), y que, ahora que existen los cálculos, ninguna obra de la demostración muestra un pendiente negativo.

5. **Prueba manual.** Repetir el flujo en el navegador con `php artisan migrate:fresh --seed`. Anotar en el pull request lo que se haya tenido que corregir y que los tests no detectaron.

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_15_FlujoWebTest.php tests/Feature/
php artisan test --filter=RA6_15
```

Resultado esperado: `Tests: 8 passed`.

Y los tests de modelo de [6.8](./RA6_8_relaciones.md), que ahora pasan todos, también los de las bitácoras:

```bash
mkdir -p tests/Unit/Models
cp /var/www/curso-laravel/evaluacion/tests/Unit/Models/*.php tests/Unit/Models/
php artisan test tests/Unit/Models
```

Resultado esperado: `Tests: 112 passed`.

El test comprueba:

- el flujo de la tabla, iniciando sesión de verdad, con los formularios y los importes que se muestran en pantalla;
- el borrado de una obra sin cobros, que conserva el presupuesto;
- que los formularios rechazan los datos no válidos y no guardan nada;
- que todas las pantallas responden y que el menú enlaza todos los módulos;
- el alta y la baja de partes, ingresos, faltas y líneas, y el 404 con otro padre;
- observaciones, incidencias y adjuntos en la ficha de la obra;
- que escribir exige iniciar sesión y que la ficha de Gastos no se crea ni se borra;

> **Nota.** Los tests miran el contenido y la estructura de las páginas, no su aspecto: comprueban que aparece `1.210,00`, que el formulario apunta a la ruta correcta y las marcas de [6.5](./RA6_5_interfaz.md), sin imponer colores ni nombres de vistas. Por eso la prueba manual sigue siendo necesaria, sobre todo en los selectores, en cómo se envían los formularios desde el navegador y en cómo se ve en una pantalla estrecha.

## Git

Rama `RA6_15`. Al fusionar: `git tag -a bloque-6 -m "Bloque 6: acceso a datos" && git push --tags`.

---

**Siguiente:** [7.1. Introducción a API y REST. Swagger](./RA7_1_swagger.md)
