# 6.10. Proyecto: flujo completo de una obra por la web

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
| 7 | Detalle de la obra → parte de trabajo | Dos trabajadores a 20 €/h, 8 h cada uno → `16,00` horas y `320,00` de mano de obra |
| 8 | Edición del trabajador | Subir su tarifa no cambia lo ya registrado |
| 9 | Detalle de la obra → partes externos | Proveedor de 200 € y especialista de 150 € → gastos `670,00` |
| 10 | Detalle de la obra → ingreso de 500 € | Beneficio `-170,00`, pendiente `710,00`, margen `540,00`, `41,3` % cobrado, `Parcial`, `Rojo`; ficha `En proceso` |
| 11 | Detalle de la obra → ingreso de 710 € | Beneficio `540,00`, `100,0` % cobrado, `Pagado`, `Verde`; ficha `Finalizada` |
| 12 | Borrar la obra | Rechazado: se vuelve atrás y se muestra el motivo |

## Ejercicios

1. **Rutas y formularios.** Completar todas las rutas de la tabla de [6.5](./RA6_5_crud.md) y las de [6.9](./RA6_9_bitacoras.md), con sus formularios.

2. **Acciones en los detalles.** El detalle de cada registro enlaza lo que depende de él:
    - **Obra:** botones para añadir partes e ingresos, y para borrar cada uno; selector de presupuestos libres; observaciones, incidencias y adjuntos; botón de borrar la obra.
    - **Presupuesto:** formulario de líneas y botón de quitar cada una.
    - **Trabajador:** sus faltas, con el texto del tipo (`Vacaciones`, `Baja médica`…) y botón de borrar.

3. **Mensajes.** Toda regla de negocio rechazada vuelve atrás con `session('error')`, y el layout la muestra.

4. **Prueba manual.** Repetir el flujo en el navegador con `php artisan migrate:fresh --seed`. Anotar en el pull request lo que se haya tenido que corregir y que los tests no detectaron.

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_10_FlujoWebTest.php tests/Feature/
php artisan test --filter=RA6_10
```

Resultado esperado: `Tests: 8 passed`.

El test comprueba:

- el flujo de la tabla, iniciando sesión de verdad, con los formularios y los importes que se muestran en pantalla;
- el borrado de una obra sin cobros, que conserva el presupuesto;
- que los formularios rechazan los datos no válidos y no guardan nada;
- que todas las pantallas responden y que el menú enlaza todos los módulos;
- el alta y la baja de partes, ingresos, faltas y líneas, y el 404 con otro padre;
- observaciones, incidencias y adjuntos en la ficha de la obra;
- que escribir exige iniciar sesión y que la ficha de Gastos no se crea ni se borra.

> **Nota.** El test mira el contenido de las páginas, no su aspecto: comprueba que aparece `1.210,00` y que el formulario apunta a la ruta correcta, sin imponer ni el diseño ni los nombres de las vistas. Por eso la prueba manual sigue siendo necesaria, sobre todo en los selectores y en cómo se envían los formularios desde el navegador.

## Git

Rama `RA6_10`. Al fusionar: `git tag -a bloque-6 -m "Bloque 6: acceso a datos" && git push --tags`.

---

**Siguiente:** [7.1. API REST y controladores de recursos](./RA7_1_recursos.md)
