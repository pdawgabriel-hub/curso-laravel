{% raw %}
# 5.5. Controladores

**Referencia teórica:** [Controladores](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/RA5_5_controladores.html) · [Laravel: Controllers](https://laravel.com/docs/13.x/controllers)

Sacar la lógica de las rutas a controladores, con un fichero de rutas por módulo y datos de prueba de diez registros por entidad.

## Conceptos clave

- Métodos con los nombres de recurso de Laravel: `index`, `show`, `create`, `edit` (y en 5.6, `store` y `update`).
- `abort_unless(isset($this->arrayClientes[$id]), 404)`: un id inexistente devuelve 404.
- **Controlador invocable** (`__invoke`) para acciones únicas como la portada: `Route::get('/', HomeController::class)`.
- `Route::controller(ClientesController::class)->prefix('clientes')->name('clientes.')->group(…)` evita repetir el controlador en cada ruta.
- `routes/web.php` incluye cada módulo con `require __DIR__ . '/clientes.php';`.

## Ejercicios

1. **HomeController.** `php artisan make:controller HomeController --invokable`. Devuelve la vista `home` con `empresa` = `config('gestionobras.empresa')`.

2. **ClientesController.**

    ```bash
    P=/var/www/curso-laravel/docs/materiales/plantillas/RA5_5
    cp $P/app/Http/Controllers/ClientesController.php app/Http/Controllers/
    cp $P/routes/clientes.php routes/
    ```

    Completar `$arrayClientes` con el contenido de [`array_clientes.php`](https://github.com/pdawgabriel-hub/curso-laravel/blob/main/docs/materiales/plantillas/RA5_5/datos/array_clientes.php) (10 clientes) e incluir `routes/clientes.php` desde `routes/web.php`, eliminando las closures de 5.4.

3. **ObrasController.** Mismo patrón, con `$arrayObras` de [`array_obras.php`](https://github.com/pdawgabriel-hub/curso-laravel/blob/main/docs/materiales/plantillas/RA5_5/datos/array_obras.php) y sus rutas en `routes/obras.php`. Además:

    - `show` muestra el **nombre completo del cliente** de la obra (buscándolo en el array de clientes por `cliente_id`).
    - `show` muestra el **estado de pago** según la regla de negocio: `Pendiente` si `cobrado ≤ 0`, `Parcial` si `cobrado < total`, `Pagado` en otro caso.

4. **TrabajadoresController.** Módulo completo nuevo: controlador, `routes/trabajadores.php` y vistas `trabajadores/{index,show,create,edit}`, con [`array_trabajadores.php`](https://github.com/pdawgabriel-hub/curso-laravel/blob/main/docs/materiales/plantillas/RA5_5/datos/array_trabajadores.php). El detalle muestra el tipo y la tarifa en €/h.

## Comprueba tu solución automáticamente

```bash
rm tests/Feature/RA5_4_VistasTest.php
cp /var/www/curso-laravel/docs/materiales/tests/bloque5/RA5_5_ControladoresTest.php tests/Feature/
php artisan test --filter=RA5_5
```

Resultado esperado: `Tests: 8 passed`.

## Git

Rama `RA5_5`. Un commit por controlador.
{% endraw %}

---

**Siguiente:** [5.6. Formularios y validación](./RA5_6_formularios.md)
