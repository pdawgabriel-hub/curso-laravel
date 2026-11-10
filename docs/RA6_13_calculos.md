# 6.13. Cálculos financieros

**Referencia teórica:** reglas de negocio de [0.4](./004_de_odoo_a_laravel.md#reglas-de-negocio) · [Laravel: Accessors](https://laravel.com/docs/13.x/eloquent-mutators#accessors-and-mutators) · [Agregados de relaciones](https://laravel.com/docs/13.x/eloquent-relationships#aggregating-related-models)

Calcular en un único sitio todos los indicadores financieros de una obra: total, mano de obra, gastos, beneficio de caja, pendiente de cobro, margen, porcentaje cobrado, estado de pago, salud y estado de la ficha de Gastos.

## Conceptos clave

En Odoo estos valores eran campos `compute` guardados y recalculados por el ORM. Hay dos formas de llevarlos a Laravel:

| Opción | Ventaja | Inconveniente |
|:--|:--|:--|
| **Calcular al leer** (accessors o servicio) | Nunca se desincroniza; código simple | Más consultas al listar muchas obras |
| Guardar y recalcular al escribir (observers) | Se puede ordenar y filtrar por esos campos en SQL | Hay que recalcular toda la cadena en cada cambio (presupuesto, línea, parte, ingreso…) |

Con el volumen de una empresa de reformas (decenas o cientos de obras), calcular al leer es suficiente. Las cantidades se redondean a 2 decimales (`round($x, 2)`) en cada paso, y el porcentaje a 1.

## Ejercicios

1. **Totales de presupuesto.** Accessors `base_imponible`, `cuota_iva` y `total` en `Presupuesto` (fórmulas de 0.4, redondeando la cuota antes de sumar).

2. **Servicio ResumenFinanciero.** `App\Services\ResumenFinanciero` con el método `obra(Obra $obra): array`, que devuelve exactamente estas claves:

    ```
    total, horas_totales, coste_moo, coste_proveedores, coste_especialistas, gastos, ingresos,
    beneficio, debe, margen_proyectado, porcentaje_cobrado, estado_pago, salud, coste_hora, estado
    ```

    `estado_pago` sigue la regla de [0.4](./004_de_odoo_a_laravel.md#reglas-de-negocio), la misma que ya calculaba `ObrasController` en 5.5: ahora vive en un único sitio.

3. **Acumulados del trabajador.** `horas_totales` y `total_euros` del trabajador: suma de sus líneas en cualquier obra.

4. **En las vistas.**

    - Detalle de la obra, en la pestaña *Resumen*: cinco `<x-kpi>` con *Total presupuesto*, *Gastos*, *Beneficio de caja* (tono `negativo` si es menor que 0), *Pendiente de cobro* y *Margen proyectado*; un `<x-progreso>` con el porcentaje cobrado, y la salud y el estado de pago con `<x-insignia>` (verde, ámbar o rojo).
    - Detalle de la ficha de Gastos (`gastos.show`): gastos, ingresos, beneficio real, pendiente y estado.
    - Detalle del trabajador: horas y coste acumulados.
    - **Formato:** importes y horas con el helper `Formato` de [6.6](./RA6_6_maestros.md) (`1.210,00`, `-170,00`, `16,00`); el porcentaje con un decimal (`41,3`).
    - **Textos:** estado de pago `Pendiente`, `Parcial` o `Pagado`; salud `Verde`, `Ámbar` o `Rojo`; estado de la ficha `En proceso` o `Finalizada`.
    - Listado de obras: columnas de total, estado de pago y salud. Listado de presupuestos: la columna `Total`.

5. **Investiga.** Responde en tu _pull request_:
    - ¿Por qué `beneficio` es `ingresos − gastos` y no `total − gastos`? ¿Qué mostraría cada fórmula en una obra recién presupuestada, sin ningún cobro?
    - ¿Cuántas consultas lanza el listado de obras con 15 obras? ¿Cómo lo reducirías?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_13_CalculosTest.php tests/Feature/
php artisan test --filter=RA6_13
```

Resultado esperado: `Tests: 6 passed`. Los valores del test son los del [escenario completo](./anexo_escenario.md): total 1 210 €, gastos 670 €, cobro de 500 € → beneficio −170 €, debe 710 €, 41,3 % cobrado, salud roja.

## Git

Rama `RA6_13`, commit `feat: ResumenFinanciero con los indicadores de la obra`.

---

**Siguiente:** [6.14. Bitácoras y adjuntos](./RA6_14_bitacoras.md)
