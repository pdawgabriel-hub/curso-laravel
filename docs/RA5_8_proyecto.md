---
title: "5.8. Proyecto: patrones"
parent: Bloque 5. Introducción a Laravel
nav_order: 8
---
{% raw %}
# 5.8. Proyecto: MVC y patrones de diseño
{: .no_toc }

{: .nota }
> **Referencia teórica:** [Proyecto: POO y patrones de diseño](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/RA5_8_proyecto.html) · [Laravel: Service Container](https://laravel.com/docs/13.x/container)

1. TOC
{:toc}

## Objetivo

Preparar el cambio a base de datos del Bloque 6: los controladores dejan de conocer **de dónde** salen los datos (repositorios) y la lógica de negocio sale de las vistas y los controladores a clases propias.

## Conceptos clave

| Patrón | En este proyecto |
|:--|:--|
| MVC | Rutas → controlador → vista; los datos aún en arrays |
| Repositorio | Interfaz `ClienteRepository` con una implementación `ArrayClienteRepository`. En el Bloque 6 se sustituye por Eloquent sin tocar los controladores |
| Inyección de dependencias | El controlador declara `ClienteRepository` en su constructor y el contenedor de Laravel le pasa la implementación registrada |
| Singleton | `$this->app->singleton(ClienteRepository::class, ArrayClienteRepository::class)`: una sola instancia por petición |
| Clase de dominio | `EstadoPago::calcular($total, $cobrado)`: la regla de negocio en un único sitio, comprobable con un test unitario |

## Ejercicios

### Ejercicio 1. Repositorio de clientes

1. `app/Repositories/ClienteRepository.php`: interfaz con `all(): array` y `find(int $id): ?array`.
2. `app/Repositories/ArrayClienteRepository.php`: implementación con el array de 10 clientes (sale del controlador).
3. Registro como singleton en `AppServiceProvider::register()`.
4. `ClientesController` recibe el repositorio en el constructor (`public function __construct(private ClienteRepository $clientes) {}`).

### Ejercicio 2. Repositorio de obras

`ObraRepository` (`all`, `find` y `deCliente(int $clienteId): array`, que devuelve las obras de un cliente conservando sus claves) y `ArrayObraRepository`. `ObrasController` recibe `ObraRepository` y `ClienteRepository`.

En el detalle de un cliente, mostrar la lista de sus obras usando `deCliente()`.

### Ejercicio 3. Regla de negocio del estado de pago

`app/Support/EstadoPago.php` con el método estático `calcular(float $total, float $cobrado): string`, que devuelve `pendiente`, `parcial` o `pagado` (regla de [0.4](004_de_odoo_a_laravel.html#reglas-de-negocio)). `ObrasController::show` lo usa en lugar de calcularlo en la vista.

### Ejercicio 4. Documentar la arquitectura

Añadir al README de la aplicación un diagrama sencillo de capas (rutas → controladores → repositorios / servicios → vistas) y la lista de patrones aplicados.

## Comprobación

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque5/RA5_8_ProyectoTest.php tests/Feature/
php artisan test
```

Resultado esperado: todos los tests del bloque en verde (`RA5_8`: `Tests: 6 passed`).

## Git

Rama `RA5_8`. Al fusionar: `git tag -a bloque-5 -m "Bloque 5: introducción a Laravel" && git push --tags`.
{% endraw %}
