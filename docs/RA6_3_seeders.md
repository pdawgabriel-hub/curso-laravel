---
title: 6.3. Factories y seeders
parent: Bloque 6. Acceso a datos
nav_order: 3
---

# 6.3. Factories y seeders
{: .no_toc }

{: .nota }
> **Referencia teórica:** [Inicialización de la base de datos (Seeding)](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/045_databaseSeeding.html) · [Laravel: Factories](https://laravel.com/docs/13.x/eloquent-factories) · [Laravel: Seeding](https://laravel.com/docs/13.x/seeding)

1. TOC
{:toc}

## Objetivo

Factories para todos los modelos que las necesitan y un `DatabaseSeeder` que genere una empresa de demostración coherente: el equivalente al `post_init_hook` de Odoo.

## Conceptos clave

- Una factory define datos válidos por defecto; los tests sobrescriben solo lo que les importa: `Ingreso::factory()->create(['importe' => 500])`.
- Las claves foráneas se definen con otra factory (`'obra_id' => Obra::factory()`), que solo se ejecuta si no se pasa el valor.
- `afterCreating()` completa lo que debe existir siempre junto al modelo (la ficha de Gastos de una obra).
- Con `APP_FAKER_LOCALE=es_ES`, `fake()->firstName()` genera nombres en español.

## Ejercicios

### Ejercicio 1. Factories

Una factory por modelo, salvo `Gasto`, `Observacion`, `Incidencia` y `Archivo` (requisitos en el [contrato](contrato-api.html#factories)).

- `ObraFactory`: toda obra creada con la factory tiene su ficha de Gastos.
- `PresupuestoFactory`: sin `nombre_cliente` (se copia del cliente al crear, en 6.6).
- `LineaParteTrabajoFactory`: sin `coste_hora` (se copia del trabajador, en 6.6).
- `FaltaFactory`: `fecha_fin` igual o posterior a `fecha_inicio`.

### Ejercicio 2. DatabaseSeeder

1. Tres usuarios: el administrador (`config('app.admin.email')`), uno con rol `jefe` y otro con rol `administrativa`. Añadir `ADMIN_EMAIL` a `.env` y `.env.example`, y la clave `'admin' => ['email' => env('ADMIN_EMAIL')]` a `config/app.php`.
2. 20 clientes, 20 trabajadores (con 0 a 4 faltas cada uno), 20 proveedores y 20 especialistas.
3. Por cada una de las 20 obras:
   - un presupuesto **aprobado** de su cliente con 3 a 6 líneas;
   - 2 a 5 partes de trabajo con 2 a 4 trabajadores distintos cada uno;
   - 1 a 3 partes de proveedor y 1 a 2 de especialista, con importes proporcionales al total para que el beneficio no salga siempre negativo;
   - fechas de inicio y fin en su ficha de Gastos;
   - 0 a 3 ingresos cuya suma **nunca supere el total** de la obra.

```bash
php artisan migrate:fresh --seed
```

{: .consejo }
Para que los ingresos no superen el total, se calcula el total de la obra después de crear el presupuesto y cada ingreso se toma como un porcentaje de lo que queda pendiente.

## Comprobación

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_3_SeedersTest.php tests/Feature/
php artisan test --filter=RA6_3
php artisan test tests/Unit/Models --filter=factory
```

Resultado esperado: `RA6_3`: `Tests: 5 passed`.

## Git

Rama `RA6_3`. Commits: `feat: factories`, `feat: datos de demostración en DatabaseSeeder`.
