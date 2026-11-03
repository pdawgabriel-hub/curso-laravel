---
title: 6.7. Cálculos financieros
parent: Bloque 6. Acceso a datos
nav_order: 7
---

# 6.7. Cálculos financieros
{: .no_toc }

{: .nota }
> **Referencia teórica:** reglas de negocio de [0.4](004_de_odoo_a_laravel.html#reglas-de-negocio) · [Laravel: Accessors](https://laravel.com/docs/13.x/eloquent-mutators#accessors-and-mutators) · [Agregados de relaciones](https://laravel.com/docs/13.x/eloquent-relationships#aggregating-related-models)

1. TOC
{:toc}

## Objetivo

Calcular en un único sitio todos los indicadores financieros de una obra: total, mano de obra, gastos, beneficio de caja, pendiente de cobro, margen, porcentaje cobrado, estado de pago, salud y estado de la ficha de Gastos.

## Conceptos clave

En Odoo estos valores eran campos `compute` guardados y recalculados por el ORM. Hay dos formas de llevarlos a Laravel:

| Opción | Ventaja | Inconveniente |
|:--|:--|:--|
| **Calcular al leer** (accessors o servicio) | Nunca se desincroniza; código simple | Más consultas al listar muchas obras |
| Guardar y recalcular al escribir (observers) | Se puede ordenar y filtrar por esos campos en SQL | Hay que recalcular toda la cadena en cada cambio (presupuesto, línea, parte, ingreso…) |

Con el volumen de una empresa de reformas (decenas o cientos de obras), calcular al leer es suficiente. Las cantidades se redondean a 2 decimales (`round($x, 2)`) en cada paso, y el porcentaje a 1.

## Ejercicios

### Ejercicio 1. Totales de presupuesto

Accessors `base_imponible`, `cuota_iva` y `total` en `Presupuesto` (fórmulas de 0.4, redondeando la cuota antes de sumar).

### Ejercicio 2. Servicio ResumenFinanciero

`App\Services\ResumenFinanciero` con el método `obra(Obra $obra): array`, que devuelve exactamente estas claves:

```
total, horas_totales, coste_moo, coste_proveedores, coste_especialistas, gastos, ingresos,
beneficio, debe, margen_proyectado, porcentaje_cobrado, estado_pago, salud, coste_hora, estado
```

`estado_pago` reutiliza `EstadoPago::calcular()` de 5.8.

### Ejercicio 3. Acumulados del trabajador

`horas_totales` y `total_euros` del trabajador: suma de sus líneas en cualquier obra.

### Ejercicio 4. En las vistas

- Detalle de la obra: cinco tarjetas con *Total presupuesto*, *Gastos*, *Beneficio de caja*, *Pendiente de cobro* y *Margen proyectado*, una barra de progreso con el porcentaje cobrado y un indicador de color con la salud.
- Listado de obras: columnas de total, estado de pago y salud. Usar `with()` para no lanzar una consulta por obra al pedir el cliente.

## Comprobación

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_7_CalculosTest.php tests/Feature/
php artisan test --filter=RA6_7
```

Resultado esperado: `Tests: 6 passed`. Los valores del test son los del [escenario completo](anexo_escenario.html): total 1 210 €, gastos 670 €, cobro de 500 € → beneficio −170 €, debe 710 €, 41,3 % cobrado, salud roja.

## Git

Rama `RA6_7`, commit `feat: ResumenFinanciero con los indicadores de la obra`.
