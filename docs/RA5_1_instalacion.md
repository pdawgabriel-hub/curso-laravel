---
title: 5.1. Instalación
parent: Bloque 5. Introducción a Laravel
nav_order: 1
---

# 5.1. Instalación
{: .no_toc }

{: .nota }
> **Referencia teórica:** [Instalación de Laravel y desarrollo colaborativo](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/RA5_1_instalacion.html) · [Laravel: Installation](https://laravel.com/docs/13.x/installation)

1. TOC
{:toc}

## Objetivo

Crear el proyecto `gestion-obras` con Laravel 13, publicarlo en GitHub como `gestion-obras-laravel` y servirlo en `http://gestion-obras.test`.

## Conceptos clave

- `composer create-project laravel/laravel:^13.0` descarga el esqueleto y sus dependencias, genera `.env` con una `APP_KEY` y crea la base de datos SQLite (`database/database.sqlite`).
- Laravel 13 necesita PHP 8.3 o superior; el entorno usa PHP 8.4.
- El `.gitignore` del esqueleto excluye `vendor/`, `node_modules/`, `.env` y `database/database.sqlite`: tras clonar hay que regenerarlos (`composer install`, `cp .env.example .env`, `php artisan key:generate`, `php artisan migrate`).

## Ejercicios

### Ejercicio 1. Crear el proyecto

Desde el equipo (no hace falta PHP instalado, se usa un contenedor de un solo uso):

```bash
cd ~/Documentos/laravel
docker run -it --rm -v "$PWD:/usr/src/app" thecodingmachine/php:8.4-v4-slim-cli \
    composer create-project laravel/laravel:^13.0 gestion-obras
```

### Ejercicio 2. Primer commit y repositorio en GitHub

Crear en GitHub el repositorio vacío `gestion-obras-laravel` (sin README ni `.gitignore`) y subir el esqueleto:

```bash
cd gestion-obras
git init -b main
git add .
git commit -m "chore: esqueleto de Laravel 13"
git remote add origin git@github.com:<usuario>/gestion-obras-laravel.git
git push -u origin main
```

### Ejercicio 3. Servir la aplicación

Con el sitio de nginx del [apartado 0.1](001_laradock.html), dentro del `workspace`:

```bash
cd /var/www/gestion-obras
php artisan migrate
npm install && npm run build
```

<http://gestion-obras.test> debe mostrar la página de bienvenida de Laravel.

### Ejercicio 4. Integración continua

Añadir el workflow del [apartado 0.3](003_tests.html) y comprobar en la pestaña *Actions* que los tests de ejemplo del esqueleto pasan.

```bash
git switch -c RA5_1
mkdir -p .github/workflows && cp /var/www/curso-laravel/docs/materiales/ci/tests.yml .github/workflows/
git add .github && git commit -m "chore: ejecuta los tests en GitHub Actions"
git push -u origin RA5_1
```

### Ejercicio 5. README de la aplicación

Sustituir el `README.md` del esqueleto por uno propio: qué es la aplicación, cómo se instala en local (los comandos del ejercicio 3 tras clonar) y la insignia de la CI.

## Comprobación

- `php artisan --version` → `Laravel Framework 13.x`.
- `php artisan test` → los dos tests de ejemplo pasan.
- La CI del pull request `RA5_1` está en verde.

## Git

Pull request `RA5_1 → main` con el workflow y el README.
