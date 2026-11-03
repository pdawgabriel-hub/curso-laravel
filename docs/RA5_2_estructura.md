---
title: 5.2. Estructura y configuración
parent: Bloque 5. Introducción a Laravel
nav_order: 2
---

# 5.2. Estructura y configuración
{: .no_toc }

{: .nota }
> **Referencia teórica:** [Estructura de un proyecto, configuración y Artisan](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/RA5_2_estructura.html) · [Laravel: Configuration](https://laravel.com/docs/13.x/configuration)

1. TOC
{:toc}

## Objetivo

Adaptar la configuración a una empresa española y crear un fichero de configuración propio con los datos de la empresa, que usarán el layout, los PDF y los correos.

## Conceptos clave

| Carpeta | Qué contiene en este proyecto |
|:--|:--|
| `app/Models` | Modelos Eloquent (Bloque 6) |
| `app/Http/Controllers` | Controladores web; `API/` para los de la API (Bloque 7) |
| `app/Http/Requests` | Form Requests (5.6) |
| `app/Services`, `app/Repositories` | Lógica de negocio y acceso a datos (5.8 y Bloque 6) |
| `config/` | Configuración; `config/gestionobras.php` es propio |
| `database/` | Migraciones, factories y seeders |
| `resources/views` | Vistas Blade |
| `routes/` | `web.php`, `api.php` y un fichero por grupo de rutas (5.5) |
| `tests/` | Tests `Feature` y `Unit` |

- `config('fichero.clave')` lee la configuración; `env()` solo debe usarse **dentro** de los ficheros de `config/`, porque con `php artisan config:cache` el `.env` deja de leerse.

## Ejercicios

### Ejercicio 1. Idioma, zona horaria y datos falsos en español

En `.env` **y** en `.env.example` (la CI usa este último):

```ini
APP_NAME="Gestión de Obras"
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_ES
```

En `config/app.php`, `'timezone' => 'Europe/Madrid'`.

### Ejercicio 2. Configuración propia

Copiar la plantilla [`config/gestionobras.php`](https://github.com/pdawgabriel-hub/curso-laravel/blob/main/docs/materiales/plantillas/RA5_2/config/gestionobras.php):

```bash
cp /var/www/curso-laravel/docs/materiales/plantillas/RA5_2/config/gestionobras.php config/
```

Añadir a `.env` y `.env.example` las claves `EMPRESA_NOMBRE`, `EMPRESA_CIF`, `EMPRESA_DIRECCION`, `EMPRESA_TELEFONO` y `EMPRESA_EMAIL` con los datos de la empresa.

### Ejercicio 3. Artisan

Explorar los comandos que se usarán a lo largo del proyecto y anotar en el README de la aplicación los más útiles:

```bash
php artisan list
php artisan about
php artisan config:show gestionobras
php artisan tinker      # >>> config('gestionobras.empresa.nombre')
```

## Comprobación

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque5/RA5_2_ConfiguracionTest.php tests/Feature/
php artisan test --filter=RA5_2
```

Resultado esperado: `Tests: 4 passed`.

## Git

Rama `RA5_2`, commit `feat: configuración de la empresa e idioma español`.
