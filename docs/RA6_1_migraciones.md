---
title: 6.1. Migraciones
parent: Bloque 6. Acceso a datos
nav_order: 1
---

# 6.1. Migraciones y Schema Builder
{: .no_toc }

{: .nota }
> **Referencia teórica:** [Configuración inicial](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/041_configuracionInicial.html) · [Migraciones](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/042_migraciones.html) · [Schema Builder](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/043_schemaBuilder.html)

1. TOC
{:toc}

## Objetivo

Crear con migraciones el esquema completo de la aplicación sobre MariaDB, con sus claves foráneas, índices únicos y valores por defecto.

## Conceptos clave

- Una migración por tabla (o por grupo de tablas muy ligadas, como un parte y sus líneas), en orden de dependencias: primero las tablas a las que otras apuntan.
- `foreignId('obra_id')->constrained('obras')` + la acción de borrado: `cascadeOnDelete()`, `restrictOnDelete()` o `nullOnDelete()`.
- `decimal('importe', 10, 2)` para dinero, nunca `float`.
- `morphs('observable')` crea `observable_type` + `observable_id` con su índice (6.9).
- Los tests usan SQLite en memoria (`phpunit.xml`); la aplicación, MariaDB. Las migraciones deben funcionar en ambos.

## Ejercicios

### Ejercicio 1. Conexión con MariaDB

En `.env`:

```ini
DB_CONNECTION=mariadb
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=gestion_obras
DB_USERNAME=gestion
DB_PASSWORD=gestion
```

`php artisan migrate` crea las tablas del esqueleto en `gestion_obras` (comprobarlo en phpMyAdmin).

### Ejercicio 2. Esquema completo

Crear las migraciones de la tabla siguiente. Las columnas exactas, los tipos y las restricciones están en el [contrato](contrato-api.html) y en el diagrama relacional de [0.4](004_de_odoo_a_laravel.html).

| Migración | Tablas | Claves y restricciones |
|:--|:--|:--|
| `add_rol_to_users_table` | `users.rol` | `enum('administrativa','jefe')`, nullable |
| `create_secuencias_table` | `secuencias` | `prefijo` único, `siguiente` (entero, 1) |
| `create_clientes_table` | `clientes` | `codigo` y `nif` únicos (`nif` nullable) |
| `create_trabajadores_table` | `trabajadores` | `coste_hora_estandar` decimal(8,2) **default 20** |
| `create_proveedores_table` | `proveedores` | `base` y `iva` decimales |
| `create_especialistas_table` | `especialistas` | `notas` (texto) |
| `create_obras_table` | `obras` | `cliente_id` nullable, `nullOnDelete` |
| `create_presupuestos_table` | `presupuestos`, `lineas_presupuesto`, `presupuesto_proveedor`, `especialista_presupuesto` | `cliente_id` `restrictOnDelete`; `obra_id` `nullOnDelete`; `iva` default 21; `estado` default `no_aprobado`; líneas `cascadeOnDelete`; pivotes con clave primaria compuesta |
| `create_gastos_table` | `gastos` | `obra_id` **único** y `cascadeOnDelete`; `presupuesto_id` `nullOnDelete`; tres booleanos default `false` |
| `create_ingresos_table` | `ingresos` | `obra_id` **obligatorio** y `restrictOnDelete` |
| `create_partes_tables` | `partes_trabajo`, `lineas_parte_trabajo`, `partes_proveedor`, `partes_especialista` | `obra_id` `cascadeOnDelete`; `trabajador_id`, `proveedor_id`, `especialista_id` `restrictOnDelete`; `estado_pago` default `pendiente` |
| `create_faltas_table` | `faltas` | `trabajador_id` `cascadeOnDelete`; `tipo` default `vacaciones` |
| `create_bitacoras_y_archivos_tables` | `observaciones`, `incidencias`, `archivos` | `morphs()`; `user_id` nullable `nullOnDelete` |

{: .consejo }
El nombre de las tablas pivote sigue la convención de Laravel (singulares en orden alfabético: `especialista_presupuesto`, `presupuesto_proveedor`); así `belongsToMany` las encuentra sin indicarlas.

### Ejercicio 3. Ida y vuelta

Cada migración debe tener un `down()` que deshaga su `up()`. Comprobarlo con:

```bash
php artisan migrate:fresh
php artisan migrate:rollback --step=13
php artisan migrate
```

## Comprobación

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_1_MigracionesTest.php tests/Feature/
php artisan test --filter=RA6_1
```

Resultado esperado: `Tests: 23 passed`.

## Git

Rama `RA6_1`. Un commit por migración o por grupo de tablas relacionadas.
