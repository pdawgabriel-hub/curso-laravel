---
title: 0.3. Tests e integración continua
parent: Bloque 0. Entorno y forma de trabajo
nav_order: 3
---

# 0.3. Tests e integración continua
{: .no_toc }

{: .nota }
> **Referencia teórica:** [Testing en Laravel](https://laravel.com/docs/13.x/testing) · [HTTP Tests](https://laravel.com/docs/13.x/http-tests) · [Database Testing](https://laravel.com/docs/13.x/database-testing)

1. TOC
{:toc}

## Objetivo

Comprobar cada apartado con tests automáticos, en local y en cada push a GitHub.

## Cómo son los tests de los apartados

Los apuntes se clonan junto a la aplicación (`~/Documentos/laravel/curso-laravel`, apartado [5.1](RA5_1_instalacion.html)), de modo que desde el `workspace` están en `/var/www/curso-laravel`.

Cada apartado tiene un fichero `RAx_y_NombreTest.php` en [`docs/materiales/tests/`](https://github.com/pdawgabriel-hub/curso-laravel/tree/main/docs/materiales/tests). Se copia a `tests/Feature/` de la aplicación y se ejecuta filtrando por su nombre:

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque5/RA5_3_RutasTest.php tests/Feature/
php artisan test --filter=RA5_3
```

```
   PASS  Tests\Feature\RA5_3_RutasTest
  ✓ pantalla principal
  ✓ rutas de clientes
  …
  Tests:    6 passed (32 assertions)
```

{: .aviso }
Los tests reflejan el estado de su apartado. Al avanzar, algunos dejan de aplicarse (por ejemplo, los de 5.3 esperan textos que en 5.4 pasan a ser vistas). Cuando eso ocurra se borran de `tests/Feature/` en el commit que los deja obsoletos.

## Piezas que conviene conocer

| Pieza | Para qué |
|:--|:--|
| `RefreshDatabase` | Cada test empieza con la base de datos recién migrada (SQLite en memoria, según `phpunit.xml`) |
| Factories | Crean datos de prueba: `Cliente::factory()->count(3)->create()` |
| `$this->get()`, `postJson()`… | Hacen peticiones a la aplicación sin servidor |
| `assertOk()`, `assertCreated()`, `assertJsonPath()`… | Comprueban la respuesta |
| `assertDatabaseHas()` | Comprueba el contenido de una tabla |
| `withoutVite()` | Evita necesitar `npm run build` al renderizar vistas en los tests |
| `Mail::fake()`, `Http::fake()`, `Storage::fake()` | Sustituyen servicios externos por dobles comprobables |

## Integración continua

Con GitHub Actions, cada push y cada pull request ejecuta toda la batería. La plantilla está en [`docs/materiales/ci/tests.yml`](https://github.com/pdawgabriel-hub/curso-laravel/blob/main/docs/materiales/ci/tests.yml):

```bash
mkdir -p .github/workflows
cp /var/www/curso-laravel/docs/materiales/ci/tests.yml .github/workflows/
git add .github && git commit -m "chore: ejecuta los tests en GitHub Actions"
```

El workflow compila los assets (`npm run build`), porque los tests de Breeze renderizan vistas con `@vite`.

## Ejercicios

1. Añadir el workflow al repositorio de la aplicación en cuanto exista (apartado 5.1).
2. Hacer un push con un test que falle a propósito, ver la ejecución en rojo en la pestaña *Actions* y corregirlo.
3. Añadir la insignia del workflow al `README.md` de la aplicación:
   `![tests](https://github.com/<usuario>/gestion-obras-laravel/actions/workflows/tests.yml/badge.svg)`
