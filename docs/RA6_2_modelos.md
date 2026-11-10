# 6.2. Modelos de datos mediante ORM

**Referencia teórica:** [Modelos de datos mediante ORM](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/044_modelosORM.html) · [Laravel: Eloquent](https://laravel.com/docs/13.x/eloquent)

En este apartado se crean **todos** los modelos, con sus campos asignables, sus conversiones, sus códigos y los datos que se rellenan solos al crear. Las relaciones entre modelos llegan en [6.8](./RA6_8_relaciones.md): hasta entonces, para leer otro registro se usa `Modelo::find($id)`.

## Definición de un modelo

```bash
php artisan make:model Cliente
```

_Eloquent_ es el ORM de Laravel y sigue el patrón _Active Record_ que vimos en [5.8](./RA5_8_proyecto.md): cada objeto se guarda a sí mismo. Con poco más que una clase vacía ya se puede trabajar:

```php
$clientes = Cliente::all();
$cliente = Cliente::findOrFail($id);     // 404 si no existe
$cliente->telefono = '600000000';
$cliente->save();
$cliente->delete();
```

## Convenios

- **Tabla:** Laravel la deduce del nombre del modelo en plural **inglés**: `Trabajador` buscaría `trabajadors`. En este proyecto todos los modelos la indican: `protected $table = 'trabajadores';`.
- **Clave primaria:** `id`.
- **Fechas:** `created_at` y `updated_at`, que se rellenan solas.

## Asignación masiva

`Cliente::create($datos)` y `$cliente->update($datos)` solo rellenan los campos de `$fillable`. Así nadie puede colar un campo que no esperamos.

```php
protected $fillable = ['nombre', 'apellidos', 'nif', 'direccion', 'cod_postal', 'telefono'];
```

El orden de `$fillable` debe ser el de cada ejercicio, porque los tests lo comprueban.

## Conversiones y valores calculados

```php
protected $casts = ['fecha' => 'date:Y-m-d', 'importe' => 'float', 'mandante' => 'boolean'];
protected $attributes = ['iva' => 21];     // valor por defecto antes de guardar

public function getNombreCompletoAttribute(): string   // $cliente->nombre_completo
{
    return trim($this->nombre . ' ' . $this->apellidos);
}
```

## Eventos del modelo

Para rellenar algo automáticamente al crear un registro:

```php
protected static function booted(): void
{
    static::creating(function (Presupuesto $p) {
        $p->fecha ??= now()->toDateString();
    });
}
```

## Ejercicios

1. **Los códigos correlativos.** `codigo` no está en ningún `$fillable`: lo asigna la aplicación.

    - Modelo `Secuencia`: `$table = 'secuencias'`, `$fillable` `prefijo, siguiente`, y el método estático `siguiente(string $prefijo): string`, que devuelve `CLI0001`, `CLI0002`… y crea la fila del prefijo si no existe. En [6.12](./RA6_12_transacciones.md) se hará seguro frente a accesos simultáneos.
    - _Trait_ `App\Models\Concerns\TieneCodigo`:

        ```php
        trait TieneCodigo
        {
            protected static function bootTieneCodigo(): void
            {
                static::creating(function ($modelo) {
                    $modelo->codigo ??= Secuencia::siguiente(static::PREFIJO);
                });
            }
        }
        ```

    Cada modelo con código lleva `use TieneCodigo;` y su constante, por ejemplo `public const PREFIJO = 'CLI';`.

2. **Los modelos.** Todos llevan `use HasFactory` (la _factory_ se crea en [6.3](./RA6_3_seeders.md)) y su `$table`, porque Laravel buscaría los nombres en inglés.

    Modelo | `$table` | Código | `$fillable`, en este orden
    -----|----|----|----
    `Cliente` | `clientes` | `CLI` | `nombre, apellidos, nif, direccion, cod_postal, telefono`
    `Trabajador` | `trabajadores` | `TRB` | `tipo, nombre, apellido, telefono, coste_hora_estandar`
    `Proveedor` | `proveedores` | `PROV` | `nombre, direccion, telefono, correo, tipo, referencia, documento, num_documento`
    `Especialista` | `especialistas` | `ESP` | `tipo, referencia, comunicacion_parte, nombre, notas, telefono, correo`
    `Obra` | `obras` | `OBR` | `descripcion, cliente_id`
    `Presupuesto` | `presupuestos` | `PREP` | `fecha, cliente_id, obra_id, nombre_cliente, nif, direccion, telefono, iva, estado`
    `LineaPresupuesto` | `lineas_presupuesto` | — | `presupuesto_id, descripcion, uds, precio`
    `Gasto` | `gastos` | `GAS` | `obra_id, presupuesto_id, direccion_obra, inicio_obra, fin_obra, solicitud_obra, mandante, gestion_licencia, pago_icio`
    `Ingreso` | `ingresos` | `ING` | `obra_id, cliente_id, nombre_obra, tipo, fecha, documento, num_documento, importe`
    `ParteTrabajo` | `partes_trabajo` | — | `obra_id, fecha, descripcion, user_id`
    `LineaParteTrabajo` | `lineas_parte_trabajo` | — | `parte_trabajo_id, trabajador_id, horas, coste_hora`
    `ParteProveedor` | `partes_proveedor` | — | `obra_id, proveedor_id, fecha, descripcion, documento, importe, estado_pago, user_id`
    `ParteEspecialista` | `partes_especialista` | — | `obra_id, especialista_id, fecha, descripcion, documento, importe, estado_pago, user_id`
    `Falta` | `faltas` | — | `trabajador_id, fecha_inicio, fecha_fin, tipo, motivo, user_id`
    `Observacion` | `observaciones` | — | `texto, user_id`
    `Incidencia` | `incidencias` | — | `texto, user_id`
    `Archivo` | `archivos` | — | `nombre_archivo, ruta, tipo`

    `Gasto`, `Observacion`, `Incidencia` y `Archivo` no tendrán _factory_: no necesitan `use HasFactory`.

3. **Conversiones, valores por defecto y _accessors_.**

    - _Casts_: fechas con `'date:Y-m-d'` (`fecha`, `fecha_inicio`, `fecha_fin`, `inicio_obra`, `fin_obra`, `solicitud_obra`); importes, horas, tarifas e `iva` con `'float'`; `uds` con `'integer'`; `mandante`, `gestion_licencia` y `pago_icio` con `'boolean'`.
    - `$attributes`: `Trabajador` con `coste_hora_estandar` 20; `Presupuesto` con `iva` 21 y `estado` `no_aprobado`; `ParteProveedor` y `ParteEspecialista` con `estado_pago` `pendiente`; `Falta` con `tipo` `vacaciones`.
    - _Accessors_: `nombre_completo` en `Cliente` (nombre y apellidos) y en `Trabajador` (nombre y apellido); `total_linea` en `LineaPresupuesto` (`uds × precio`); `coste_subtotal` en `LineaParteTrabajo` (`horas × coste_hora`); `dias` en `Falta` (inclusive: un solo día cuenta 1).

4. **Lo que se rellena solo al crear.** Con el evento `creating` de cada modelo ([Eventos del modelo](#eventos-del-modelo)). Solo se rellena lo que no venga ya indicado:

    - `Presupuesto`: la `fecha` es hoy, y `nombre_cliente` (nombre completo), `nif`, `direccion` y `telefono` se **copian** del cliente con `Cliente::find($p->cliente_id)`. Un presupuesto entregado no debe cambiar si el cliente cambia su ficha.
    - `LineaParteTrabajo`: `coste_hora` se **copia** del `coste_hora_estandar` del trabajador. Si mañana sube la tarifa, los partes antiguos no cambian.
    - `Ingreso`: la `fecha` es hoy, y `cliente_id` y `nombre_obra` (la descripción) se toman de la obra.
    - `ParteTrabajo`, `ParteProveedor` y `ParteEspecialista`: la `fecha` es hoy.

5. **Pruébalo en `tinker`.**

    ```php
    $c = App\Models\Cliente::create(['nombre' => 'Ana', 'apellidos' => 'Ruiz', 'direccion' => 'C/ Sol 1', 'telefono' => '600000000']);
    $c->codigo;                                   // CLI0001
    $p = App\Models\Presupuesto::create(['cliente_id' => $c->id]);
    $p->nombre_cliente;                           // Ana Ruiz
    ```

6. **Investiga.** Responde en tu _pull request_:
    - Ejecuta en `tinker` `App\Models\Cliente::create(['nombre' => 'A', 'direccion' => 'B', 'telefono' => 'C', 'codigo' => 'X'])->codigo`. ¿Qué código tiene? ¿Por qué?
    - Da de alta un cliente, bórralo y crea otro. ¿Se reutiliza su código? ¿Dónde está guardado el siguiente?
    - ¿Por qué `coste_hora` se guarda en la línea, mientras que `nombre_completo` del cliente no se guarda en ningún sitio?
    - Compara `App\Models\Cliente::first()` con `App\Models\Cliente::toBase()->first()`. ¿Qué patrón de [5.8](./RA5_8_proyecto.md) reconoces en cada una?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_2_ModelosTest.php tests/Feature/
php artisan test --filter=RA6_2
```

Resultado esperado: `Tests: 24 passed`.

## Git

Rama `RA6_2`. Commits: `feat: códigos correlativos`, `feat: modelos de la aplicación`, `feat: datos que se copian al crear`.

---

**Siguiente:** [6.3. Inicialización de la base de datos](./RA6_3_seeders.md)
