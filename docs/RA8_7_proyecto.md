# 8.7. Proyecto: la obra completa en React-Admin

**Referencia teórica:** [Ejercicios de controladores de recursos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/074_ejerciciosControladoresRecursos.html) · [React-Admin: Authorization](https://marmelab.com/react-admin/Permissions.html)

Cierre del bloque: el mismo recorrido que en [6.15](./RA6_15_proyecto.md), esta vez en el panel React-Admin y contra la API.

## El flujo

| Paso | Pantalla | Resultado |
|:--:|:--|:--|
| 1 | `/login` y después `/dashboard` | Se inicia sesión en Laravel y, dentro del panel, en la API con el mismo usuario |
| 2 | Clientes | Alta de un cliente; con un NIF repetido, el error aparece bajo el campo |
| 3 | Presupuestos | Presupuesto del cliente; en su ficha, una línea de 10 uds × 100 € → total `1210` |
| 4 | Obras | Obra del cliente; en la edición del presupuesto se elige la obra y se aprueba → total de la obra `1210` |
| 5 | Ficha de la obra → *Partes de trabajo* | Un parte de la obra |
| 6 | Ficha de la obra → *Proveedores* y *Especialistas* | 200 € y 150 € |
| 7 | Ficha de la obra → *Ingresos* | 500 € → beneficio negativo y salud `rojo` en *Resumen* |
| 8 | Obras → borrar | Rechazado: la notificación muestra el motivo (409) |

## Ejercicios

1. **El recorrido.** Hazlo con `php artisan migrate:fresh --seed` y `npm run dev`. Anota en el _pull request_ lo que hayas tenido que corregir.

2. **Permisos en la interfaz.** Con `usePermissions()`, que devuelve lo que da `getPermissions` (los `roles`), oculta a quien no es jefe el recurso de Gastos y el botón de borrar de los maestros. La API ya lo impide ([7.6](./RA7_6_autorizacion.md)); esto solo evita mostrar botones que van a fallar.

3. **Una salida.** En el menú de usuario de React-Admin, *Cerrar sesión* revoca el token. Añade un enlace para volver al perfil de Breeze (`/profile`).

4. **Investiga.** Responde en tu _pull request_:
    - Compara esta rama con `main`: ¿qué hace el servidor en cada una para mostrar el listado de obras? ¿Dónde se pinta el HTML?
    - La batería de [evaluación](./evaluacion.md) pasa igual en las dos ramas. ¿Qué dice eso de la API?

## Comprueba tu solución automáticamente

```bash
php artisan test
npm test
npm run type-check
npm run build
```

Todo en verde: la API, con la batería de evaluación incluida, y los tests de Vitest.

## Git

Rama `react-admin`. Al terminar: `git tag -a bloque-8 -m "Bloque 8: React-Admin" && git push origin react-admin --tags`. La rama **no** se fusiona con `main`, que continúa con el Bloque 9.

---

**Siguiente:** [9.1. Documentos PDF](./RA9_1_pdf.md)
