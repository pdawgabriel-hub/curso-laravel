---
title: 5.4. Vistas y Blade
parent: Bloque 5. Introducción a Laravel
nav_order: 4
---
{% raw %}
# 5.4. Vistas y Blade
{: .no_toc }

{: .nota }
> **Referencia teórica:** [Vistas, Blade y layouts](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/RA5_4_vistas.html) · [Laravel: Blade](https://laravel.com/docs/13.x/blade)

1. TOC
{:toc}

## Objetivo

Sustituir los textos de 5.3 por vistas Blade que comparten un layout con la cabecera de la empresa y el menú de la aplicación.

## Conceptos clave

- **Componente anónimo** `resources/views/components/layout.blade.php`: se usa como `<x-layout titulo="Clientes">…</x-layout>`; el contenido llega en `$slot` y los atributos se declaran con `@props`.
- `{{ $variable }}` escapa el HTML; `@foreach`, `@forelse`, `@if`, `@auth`… son directivas.
- `view('clientes.index', ['clientes' => $clientes])` busca `resources/views/clientes/index.blade.php`.
- El menú del layout solo muestra las entradas cuya ruta existe (`Route::has()`), de modo que crece a medida que se añaden módulos.

## Ejercicios

### Ejercicio 1. Layout

Copiar el componente de la plantilla:

```bash
P=/var/www/curso-laravel/docs/materiales/plantillas
mkdir -p resources/views/components resources/views/clientes
cp $P/RA5_4/resources/views/components/layout.blade.php resources/views/components/
```

El layout usa `@vite`, así que hay que tener los assets compilados (`npm run build`, o `npm run dev` mientras se desarrolla).

### Ejercicio 2. Vistas de clientes

Copiar las cuatro vistas de ejemplo:

```bash
cp $P/RA5_4/resources/views/clientes/*.blade.php resources/views/clientes/
```

Las rutas de clientes devuelven ahora sus vistas con un array de prueba **definido en `routes/web.php`** (en 5.5 pasará al controlador):

```php
$clientes = [
    1 => ['nombre' => 'Juan', 'apellidos' => 'Pérez López', 'nif' => '12345678Z', 'direccion' => 'Calle Mayor 12', 'cod_postal' => '28013', 'telefono' => '600111222'],
    2 => ['nombre' => 'María', 'apellidos' => 'García Ruiz', 'nif' => '23456789D', 'direccion' => 'Avenida de América 45', 'cod_postal' => '28028', 'telefono' => '600222333'],
    3 => ['nombre' => 'Antonio', 'apellidos' => 'Martínez Sanz', 'nif' => '34567890V', 'direccion' => 'Calle Toledo 8', 'cod_postal' => '28005', 'telefono' => '600333444'],
];

Route::prefix('clientes')->name('clientes.')->group(function () use ($clientes) {
    Route::get('/', fn () => view('clientes.index', ['clientes' => $clientes]))->name('index');
    Route::get('show/{id}', fn (int $id) => view('clientes.show', ['cliente' => $clientes[$id], 'id' => $id]))
        ->whereNumber('id')->name('show');
    // create y edit, igual
});
```

### Ejercicio 3. Vistas de obras

Crear `resources/views/obras/{index,show,create,edit}.blade.php` siguiendo el modelo de clientes, con un array de tres obras en la ruta (campos `codigo`, `descripcion`, `cliente_id`, `total`, `cobrado`; ver [`array_obras.php`](https://github.com/pdawgabriel-hub/curso-laravel/blob/main/docs/materiales/plantillas/RA5_5/datos/array_obras.php)).

- `obras.index`: tabla con código, descripción y total con formato de moneda (`number_format($obra['total'], 2, ',', '.') . ' €'`).
- `obras.show`: recibe `obra` e `id`.

### Ejercicio 4. Portada

`GET /` devuelve la vista `home` con el nombre, la dirección y el teléfono de la empresa leídos de la configuración.

## Comprobación

```bash
rm tests/Feature/RA5_3_RutasTest.php
cp /var/www/curso-laravel/docs/materiales/tests/bloque5/RA5_4_VistasTest.php tests/Feature/
php artisan test --filter=RA5_4
```

Resultado esperado: `Tests: 5 passed`.

## Git

Rama `RA5_4`. Commits sugeridos: `feat: layout común con el menú de la aplicación`, `feat: vistas de clientes`, `feat: vistas de obras`.
{% endraw %}
