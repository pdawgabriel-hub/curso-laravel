# 6.11. Ejercicios: ingresos y ficha de gastos

**Referencia teórica:** [Ejercicios de bases de datos de otras entidades](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/0471_BDFamiliaProfesional.html)

Con los cobros y las pantallas de la ficha de Gastos, ya están todas las tablas. Las siguientes secciones calculan los resultados.

## Ejercicios

1. **Ingresos.** Migración `create_ingresos_table`: `codigo` (único), `obra_id` **obligatorio** con `restrictOnDelete`, `cliente_id` opcional con `nullOnDelete`, `nombre_obra`, `tipo`, `documento` y `num_documento` opcionales, `fecha`, e `importe` en `decimal(10,2)`. Modelo `Ingreso` (`PREFIJO = 'ING'`), `IngresoFactory` y las relaciones `obra()`, `cliente()` y, en `Obra` y `Cliente`, `ingresos()`. Si no se indican, la fecha es hoy, y `cliente_id` y `nombre_obra` se toman de la obra.

2. **Pantallas de ingresos.** Desde la obra, con los nombres `obras.ingresos.create`, `obras.ingresos.store` y `obras.ingresos.destroy`, y las mismas URL que los partes. Campos: `importe`, `tipo`, `fecha`, `documento` y `num_documento`. Validación: `importe` > 0; si se envía `cliente_id`, debe ser el de la obra. El detalle de la obra los lista con su código.

3. **Pantallas de la ficha de Gastos.** No hay alta ni borrado, porque la ficha nace y muere con su obra:

    | Método | URL | Nombre |
    |---|---|---|
    | GET | `/gastos` | `gastos.index` |
    | GET | `/gastos/show/{gasto}` | `gastos.show` |
    | GET | `/gastos/edit/{gasto}` | `gastos.edit` |
    | PUT | `/gastos/update/{gasto}` | `gastos.update` |

    El formulario edita `direccion_obra`, las tres fechas y los tres booleanos. `fin_obra` no puede ser anterior a `inicio_obra`.

4. **El menú.** Comprueba que el menú del _layout_ muestra ya todos los módulos.

5. **Investiga.** Responde en tu _pull request_: `ingresos.obra_id` usa `restrictOnDelete`, y `partes_trabajo.obra_id`, `cascadeOnDelete`. Intenta borrar en `tinker` una obra con ingresos. ¿Qué ocurre? ¿Por qué un parte puede desaparecer con su obra y un cobro no?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_11_IngresosGastosTest.php tests/Feature/
php artisan test --filter=RA6_11
```

Resultado esperado: `Tests: 6 passed`.

## Git

Rama `RA6_11`. Commits: `feat: ingresos`, `feat: pantallas de la ficha de gastos`.

---

**Siguiente:** [6.12. Cálculos financieros](./RA6_12_calculos.md)
