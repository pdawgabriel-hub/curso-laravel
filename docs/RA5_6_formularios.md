---
title: 5.6. Formularios y validación
parent: Bloque 5. Introducción a Laravel
nav_order: 6
---
{% raw %}
# 5.6. Formularios y validación
{: .no_toc }

{: .nota }
> **Referencia teórica:** [Formularios, datos de entrada, validación y redirecciones](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/RA5_6_formularios.html) · [Laravel: Validation](https://laravel.com/docs/13.x/validation)

1. TOC
{:toc}

## Objetivo

Formularios de alta y edición de clientes, obras y trabajadores, validados con **Form Requests**, que redirigen con mensaje o vuelven con los errores y lo que se había escrito.

## Conceptos clave

- **Form Request** (`php artisan make:request ClienteRequest`): las reglas viven en su clase y el controlador las recibe ya validadas al tipar el parámetro: `public function store(ClienteRequest $request)`.
- Si la validación falla, Laravel redirige a la página anterior con `$errors` y `old()`.
- `@csrf` en todo formulario `POST`; `@method('PUT')` para simular PUT desde HTML.
- `redirect()->route('clientes.index')->with('mensaje', 'Cliente creado.')`: el layout ya muestra `session('mensaje')`.
- Una vista parcial (`@include('clientes._formulario')`) evita duplicar los campos entre `create` y `edit`.

## Ejercicios

### Ejercicio 1. Formulario de clientes

```bash
P=/var/www/curso-laravel/docs/materiales/plantillas/RA5_6
cp $P/app/Http/Requests/ClienteRequest.php app/Http/Requests/
cp $P/resources/views/clientes/*.blade.php resources/views/clientes/
```

Añadir a `routes/clientes.php`:

| Método | URL | Nombre | Acción |
|:--|:--|:--|:--|
| POST | `/clientes/store` | `clientes.store` | `store(ClienteRequest $request)` → redirige a `clientes.index` con `mensaje` |
| PUT | `/clientes/update/{id}` | `clientes.update` | `update(ClienteRequest $request, int $id)` → redirige a `clientes.show` |

Hasta el Bloque 6 no hay dónde guardar: los métodos solo validan y redirigen.

### Ejercicio 2. Formulario de trabajadores

Crear `TrabajadorRequest` (nombre y apellido obligatorios; `coste_hora_estandar` obligatorio, numérico y ≥ 0, con valor inicial `config('gestionobras.coste_hora_por_defecto')` en el formulario de alta) y sus formularios, rutas `trabajadores.store` y `trabajadores.update`.

### Ejercicio 3. Formulario de obras

Formulario con `descripcion` (obligatoria) y un `<select>` de clientes construido con el array de clientes. Rutas `obras.store` y `obras.update`.

### Ejercicio 4. Mensajes en español

```bash
composer require laravel-lang/common --dev
php artisan lang:add es
```

Comprobar que los errores aparecen en español y que los nombres de campo salen de `attributes()` del Form Request.

## Comprobación

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque5/RA5_6_FormulariosTest.php tests/Feature/
php artisan test --filter=RA5_6
```

Resultado esperado: `Tests: 7 passed`. El test inicia sesión con un usuario porque en 5.7 estas rutas pasarán a exigirla.

## Git

Rama `RA5_6`. Commits: `feat: alta y edición de clientes con ClienteRequest`, `feat: formularios de trabajadores`, `feat: formularios de obras`.
{% endraw %}
