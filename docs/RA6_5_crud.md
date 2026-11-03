{% raw %}
# 6.5. CRUD web con base de datos

**Referencia teórica:** [Ejercicios de bases de datos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/047_ejerciciosBD.html) · [Crear y modificar](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/0475_crearModificarFamiliaProfesional.html) · [Laravel: Route Model Binding](https://laravel.com/docs/13.x/routing#route-model-binding)

Las pantallas del Bloque 5 pasan a leer y escribir en la base de datos, y se completan las de los demás módulos de la aplicación.

## Conceptos clave

- **Route Model Binding**: con la ruta `show/{cliente}` y el parámetro `Cliente $cliente`, Laravel busca el registro y responde 404 si no existe.
- Los modelos Eloquent implementan `ArrayAccess`, así que `$cliente['nombre']` sigue funcionando en las vistas; aun así conviene pasar a `$cliente->nombre`.
- `paginate(15)` en los listados y `{{ $clientes->links() }}` en la vista.
- `$request->validated()` devuelve solo los campos validados del Form Request.
- La regla `Rule::unique('clientes')->ignore($this->route('cliente'))` permite guardar un cliente sin cambiar su NIF.
- Un borrado prohibido por una regla de negocio vuelve a la página anterior con `session('error')` (en [6.8](./RA6_8_transacciones.md) se generaliza con una excepción propia).

## Ejercicios

1. **Clientes.**

    1. Rutas con binding: `show/{cliente}`, `edit/{cliente}`, `update/{cliente}` y la nueva `DELETE destroy/{cliente}` (`clientes.destroy`, protegida con `auth`).
    2. `ClientesController` usa el modelo `Cliente` (o un `EloquentClienteRepository` que sustituya al de arrays en el contenedor).
    3. `store` crea, `update` guarda, `destroy` borra; un cliente con presupuestos no se borra y se vuelve atrás con `error`.
    4. El listado enlaza cada cliente con `route('clientes.show', $cliente->id)` y el detalle muestra su `codigo`.
    5. Mostrar `session('error')` en el layout, como `session('mensaje')`.

2. **Obras y trabajadores.** Mismo cambio en `ObrasController` (el alta crea también la ficha de Gastos) y `TrabajadoresController`. Desaparecen los repositorios de arrays y `RA5_8_ProyectoTest`.

3. **Resto de módulos.** Pantallas de listado, detalle, alta, edición y borrado para:

    | Módulo | Detalles |
    |:--|:--|
    | Proveedores, Especialistas | Formularios con sus validaciones del [contrato](./contrato-api.md) |
    | Presupuestos | Selector de cliente y obra; líneas editables en el detalle (añadir y quitar); base, IVA y total al pie |
    | Partes de trabajo | Desde la obra: fecha, descripción y una línea por trabajador |
    | Partes de proveedor y especialista | Desde la obra |
    | Ingresos | Desde la obra |
    | Gastos | Solo listado, detalle y edición de los datos de seguimiento (no se crea ni se borra) |
    | Faltas | Desde el trabajador |

    El menú del layout muestra cada entrada en cuanto existe su ruta `*.index`.

## Comprueba tu solución automáticamente

```bash
rm tests/Feature/RA5_*.php
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_5_CrudWebTest.php tests/Feature/
php artisan test --filter=RA6_5
```

Resultado esperado: `Tests: 10 passed`. El resto de módulos se comprueba en el navegador con los datos de demostración.

## Git

Rama `RA6_5`. Un commit por módulo.
{% endraw %}

---

**Siguiente:** [6.6. Relaciones entre modelos](./RA6_6_relaciones.md)
