# 6.5. CRUD web con base de datos
{% raw %}

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
    2. `ClientesController` usa el modelo `App\Models\Cliente` directamente: `Cliente::all()`, `findOrFail()`, `save()`. Es _Active Record_, así que ya no hace falta el repositorio: borra `app/Dominio`, `app/Repositorios`, `resources/datos`, su registro en `AppServiceProvider` y `storage/app/clientes.json`, y usa `$cliente->nombre_completo` en lugar de `nombreCompleto()`.
    3. `store` crea, `update` guarda, `destroy` borra; un cliente con presupuestos no se borra y se vuelve atrás con `error`.
    4. El listado enlaza cada cliente con `route('clientes.show', $cliente->id)` y el detalle muestra su `codigo`.
    5. Mostrar `session('error')` en el layout, como `session('mensaje')`.

2. **Obras y trabajadores.** Mismo cambio en `ObrasController` (el alta crea también la ficha de Gastos) y `TrabajadoresController`. Desaparecen los arrays. `RA5_8_ProyectoTest` deja de aplicarse.

3. **Resto de módulos.** Pantallas para todos los módulos, con estas rutas. Lo que exigen las rutas de escritura es iniciar sesión (`auth`), y todas tienen nombre.

    **Módulos de primer nivel:** `clientes`, `trabajadores`, `proveedores`, `especialistas`, `presupuestos` y `obras`. Cada uno con el mismo patrón (`{modulo}` es el módulo y `{id}` el registro):

    | Método | URL | Nombre | Tras guardar |
    |:--|:--|:--|:--|
    | GET | `/{modulo}` | `{modulo}.index` | |
    | GET | `/{modulo}/show/{id}` | `{modulo}.show` | |
    | GET | `/{modulo}/create` | `{modulo}.create` | |
    | GET | `/{modulo}/edit/{id}` | `{modulo}.edit` | |
    | POST | `/{modulo}/store` | `{modulo}.store` | Al listado; en `presupuestos` y `obras`, a su detalle |
    | PUT | `/{modulo}/update/{id}` | `{modulo}.update` | Al detalle |
    | DELETE | `/{modulo}/destroy/{id}` | `{modulo}.destroy` | Al listado |

    **Lo que cuelga de un registro.** Los formularios se abren desde el detalle del padre, y al guardar o borrar se vuelve a él:

    | Método | URL | Nombre |
    |:--|:--|:--|
    | POST | `/presupuestos/{presupuesto}/lineas` | `presupuestos.lineas.store` |
    | DELETE | `/presupuestos/{presupuesto}/lineas/{linea}` | `presupuestos.lineas.destroy` |
    | POST | `/obras/{obra}/vincular-presupuesto` | `obras.vincular-presupuesto` |
    | GET | `/obras/{obra}/partes-trabajo/create` | `obras.partes-trabajo.create` |
    | POST | `/obras/{obra}/partes-trabajo` | `obras.partes-trabajo.store` |
    | DELETE | `/obras/{obra}/partes-trabajo/{parte}` | `obras.partes-trabajo.destroy` |
    | GET · POST · DELETE | Igual para `partes-proveedor`, `partes-especialista` e `ingresos` | `obras.partes-proveedor.*`, `obras.partes-especialista.*`, `obras.ingresos.*` |
    | GET | `/trabajadores/{trabajador}/faltas/create` | `trabajadores.faltas.create` |
    | POST | `/trabajadores/{trabajador}/faltas` | `trabajadores.faltas.store` |
    | DELETE | `/trabajadores/{trabajador}/faltas/{falta}` | `trabajadores.faltas.destroy` |

    **Módulos especiales:**

    | Método | URL | Nombre | Nota |
    |:--|:--|:--|:--|
    | GET | `/gastos` | `gastos.index` | Las fichas se listan, se ven y se editan; **no** hay `create`, `store` ni `destroy` |
    | GET | `/gastos/show/{gasto}` | `gastos.show` | |
    | GET | `/gastos/edit/{gasto}` | `gastos.edit` | |
    | PUT | `/gastos/update/{gasto}` | `gastos.update` | Al detalle |
    | GET | `/faltas` | `faltas.index` | Todas las faltas, con el nombre del trabajador |

    Los **campos** de cada formulario se llaman como en el [contrato](./contrato-api.md), y las validaciones son las mismas. En el parte de trabajo, las líneas se envían como `lineas[0][trabajador_id]` y `lineas[0][horas]`, con un selector de trabajadores.

    En el presupuesto, además de los datos de cabecera, las líneas se añaden y se quitan desde el detalle (con los campos `descripcion`, `uds` y `precio`), y al pie se muestran la base, el IVA y el total. Las casillas de proveedores y especialistas se añaden en [6.6](./RA6_6_relaciones.md).

    En el detalle de la obra se ofrecen los presupuestos libres de su cliente en un selector `presupuesto_id`, que es el equivalente al asistente de vincular de _Odoo_.

    El menú del layout muestra cada entrada en cuanto existe su ruta `*.index`.

## Comprueba tu solución automáticamente

```bash
rm tests/Feature/RA5_*.php
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_5_CrudWebTest.php tests/Feature/
php artisan test --filter=RA6_5
```

Resultado esperado: `Tests: 10 passed`. El resto de módulos se comprueba al final del bloque con el [flujo completo](./RA6_10_proyecto.md).

## Git

Rama `RA6_5`. Un commit por módulo.
{% endraw %}

---

**Siguiente:** [6.6. Relaciones entre modelos](./RA6_6_relaciones.md)
