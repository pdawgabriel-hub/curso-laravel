---
title: 9.4. Calendario
parent: Bloque 9. Aplicaciones híbridas
nav_order: 4
---

# 9.4. Calendario unificado
{: .no_toc }

{: .nota }
> **Referencia teórica:** [FullCalendar: events as JSON feed](https://fullcalendar.io/docs/events-json-feed)

1. TOC
{:toc}

## Objetivo

Un calendario con presupuestos, ingresos y los tres tipos de parte, cada tipo con su color, que en Odoo era un modelo de solo lectura sobre una vista SQL.

## Conceptos clave

- El servicio `App\Services\Calendario` de 7.1 une las cinco consultas y devuelve eventos con `tipo`, `fecha`, `titulo`, `modelo` e `id`.
- FullCalendar pide los eventos del rango visible a una URL con `?start=…&end=…` (fechas ISO con hora y zona) y espera objetos con `title`, `start` y, opcionalmente, `color` y `extendedProps`.

## Ejercicios

1. `CalendarioController` web con dos acciones protegidas con `auth`:
   - `index` → vista `calendario` con un `<div id="calendario">` cuyo atributo `data-eventos` es `route('calendario.eventos')`, y FullCalendar desde un CDN.
   - `eventos` (ruta `calendario.eventos`) → valida `start` y `end`, toma solo la fecha y devuelve los eventos del servicio en formato FullCalendar: `title`, `start`, `color` (uno distinto por tipo) y `extendedProps` con `tipo`, `modelo` e `id`.
2. Vista mensual por defecto, en español (`locale: 'es'`, la semana empieza en lunes), con una leyenda de colores.
3. Al pulsar un evento se abre el detalle del registro de origen (equivalente al botón "Abrir registro original" de Odoo).

## Comprobación

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque9/RA9_4_CalendarioTest.php tests/Feature/
php artisan test --filter=RA9_4
```

Resultado esperado: `Tests: 3 passed`.

## Git

Rama `RA9_4`, commit `feat: calendario unificado con FullCalendar`.
