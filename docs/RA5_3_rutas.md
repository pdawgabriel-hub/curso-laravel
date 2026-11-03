---
title: 5.3. Rutas
parent: Bloque 5. Introducción a Laravel
nav_order: 3
---

# 5.3. Rutas
{: .no_toc }

{: .nota }
> **Referencia teórica:** [Rutas](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/RA5_3_rutas.html) · [Laravel: Routing](https://laravel.com/docs/13.x/routing)

1. TOC
{:toc}

## Objetivo

Definir el mapa de URLs de clientes y obras con closures que devuelven texto, antes de tener vistas o controladores.

## Conceptos clave

- `Route::get('url', fn () => …)->name('nombre')`: ruta con nombre, para generar la URL con `route('nombre', $parametros)`.
- `whereNumber('id')`: si el parámetro no es un número natural, la ruta no coincide y Laravel responde 404.
- `Route::prefix('clientes')->name('clientes.')->group(…)`: agrupa rutas con el mismo prefijo de URL y de nombre.
- Devolver un array desde una ruta genera una respuesta JSON.

## Ejercicios

### Ejercicio 1. Pantalla principal

`GET /` devuelve el texto `Pantalla principal` (sustituye a la ruta de bienvenida).

### Ejercicio 2. Rutas de clientes y de obras

Dos grupos de rutas, cada uno con su `prefix` y su `name`:

| URL | Nombre | Devuelve |
|:--|:--|:--|
| `/clientes` | `clientes.index` | `Listado de clientes` |
| `/clientes/show/{id}` | `clientes.show` | `Detalle del cliente {id}` |
| `/clientes/create` | `clientes.create` | `Nuevo cliente` |
| `/clientes/edit/{id}` | `clientes.edit` | `Modificar cliente {id}` |
| `/obras` | `obras.index` | `Listado de obras` |
| `/obras/show/{id}` | `obras.show` | `Detalle de la obra {id}` |
| `/obras/create` | `obras.create` | `Nueva obra` |
| `/obras/edit/{id}` | `obras.edit` | `Modificar obra {id}` |

Todos los `{id}` deben ser números naturales.

### Ejercicio 3. Datos de la empresa en JSON

`GET /empresa`, con nombre `empresa`, devuelve `config('gestionobras.empresa')`.

### Ejercicio 4. Listado de rutas

```bash
php artisan route:list --except-vendor
php artisan route:list --path=obras
```

## Comprobación

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque5/RA5_3_RutasTest.php tests/Feature/
php artisan test --filter=RA5_3
```

Resultado esperado: `Tests: 6 passed (32 assertions)`.

{: .aviso }
En 5.4 las rutas pasan a devolver vistas y este test deja de aplicarse: se borra en el primer commit de la rama `RA5_4`.

## Git

Rama `RA5_3`, commit `feat: rutas de clientes y obras`.
