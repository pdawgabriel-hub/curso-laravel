---
title: 6.4. Query Builder
parent: Bloque 6. Acceso a datos
nav_order: 4
---

# 6.4. Query Builder
{: .no_toc }

{: .nota }
> **Referencia teórica:** [Constructor de consultas (Query Builder)](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/046_queryBuilder.html) · [Laravel: Queries](https://laravel.com/docs/13.x/queries)

1. TOC
{:toc}

## Objetivo

Resolver con el Query Builder (`DB::table()`) y con Eloquent las consultas de negocio que más adelante usarán el panel de análisis y la API.

## Conceptos clave

- `DB::table('ingresos')->where('obra_id', 3)->sum('importe')`: agregados sin cargar modelos.
- `join`, `groupBy`, `selectRaw`, `orderByDesc`, `having`.
- `whereBetween('fecha', [$desde, $hasta])`, `whereYear('fecha', 2026)`.
- `DB::enableQueryLog()` / `DB::getQueryLog()` o `->toRawSql()` para ver el SQL generado.

## Ejercicios

Resolver en `php artisan tinker`, sobre los datos de demostración, primero con `DB::table()` y después con Eloquent. Guardar las consultas finales en `docs/consultas.md` del repositorio de la aplicación, con el SQL que generan.

1. Obras con el nombre completo de su cliente, ordenadas por código.
2. Total cobrado por obra (`codigo`, `cobrado`), de mayor a menor.
3. Obras sin ningún ingreso.
4. Horas y coste de mano de obra por trabajador (`horas × coste_hora` de sus líneas), los 5 con más horas.
5. Gasto en proveedores por proveedor y año.
6. Presupuestos aprobados por año, con su número y la suma de sus bases imponibles.
7. Obras cuyo gasto externo (proveedores + especialistas) supera 1 000 €, con `having`.
8. Ingresos del mes en curso.
9. Trabajadores con alguna falta de tipo `baja_medica` en el último año.
10. Para una obra dada, su `total` (presupuestos aprobados) y lo pendiente de cobro, en una sola consulta con subconsultas.

## Comprobación

Cada consulta debe devolver el mismo resultado con `DB::table()` y con Eloquent. La consulta 10 se puede contrastar con el resultado de [6.7](RA6_7_calculos.html).

## Git

Rama `RA6_4`, commit `docs: consultas de negocio con Query Builder`.
