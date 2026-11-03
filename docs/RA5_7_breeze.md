---
title: 5.7. Breeze y middleware
parent: Bloque 5. Introducción a Laravel
nav_order: 7
---
{% raw %}
# 5.7. Breeze y middleware
{: .no_toc }

{: .nota }
> **Referencia teórica:** [Middleware y control de usuarios con Breeze](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/RA5_7_breeze.html) · [Laravel: Authentication](https://laravel.com/docs/13.x/authentication) · [Laravel: Middleware](https://laravel.com/docs/13.x/middleware)

1. TOC
{:toc}

## Objetivo

Añadir login con Breeze y proteger las rutas que crean o modifican datos; los listados y detalles siguen siendo públicos.

## Conceptos clave

- Breeze genera las rutas de `routes/auth.php`, sus controladores, vistas y tests (`tests/Feature/Auth`).
- El middleware `auth` redirige a `/login` a quien no ha iniciado sesión y, tras identificarse, `redirect()->intended()` lo devuelve a la página que pedía.
- Un grupo `Route::middleware('auth')->group(…)` dentro del grupo de cada módulo protege solo las acciones de escritura.

## Ejercicios

### Ejercicio 1. Instalar Breeze

```bash
composer require laravel/breeze:^2.4 --dev
php artisan breeze:install      # Blade with Alpine · sin modo oscuro · PHPUnit
npm install && npm run build
php artisan migrate
```

{: .aviso }
Breeze sustituye `routes/web.php`. Antes de instalarlo hay que hacer commit, para después recuperar la ruta `/`, la ruta `empresa` y los `require` de los módulos comparando con `git diff`.

### Ejercicio 2. Proteger la escritura

En `routes/clientes.php`, `routes/obras.php` y `routes/trabajadores.php`, `create`, `edit`, `store` y `update` van dentro de `Route::middleware('auth')`.

### Ejercicio 3. Sesión en el layout

El componente `<x-layout>` de la plantilla ya muestra el enlace *Iniciar sesión* o el nombre del usuario y el botón *Cerrar sesión* (`@auth` / `@else`). Comprobarlo en el navegador.

### Ejercicio 4. Panel tras el login

Breeze redirige a `/dashboard` tras el login. Hasta el Bloque 9 puede quedarse su vista por defecto, o redirigir a `clientes.index` cambiando la ruta de `dashboard`.

## Comprobación

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque5/RA5_7_AutenticacionTest.php tests/Feature/
php artisan test --filter=RA5_7
php artisan test                    # incluidos los tests de Breeze
```

Resultado esperado: `RA5_7`: `Tests: 4 passed`.

## Git

Rama `RA5_7`. Commits: `chore: instala laravel/breeze`, `feat: crear y modificar exige iniciar sesión`.
{% endraw %}
