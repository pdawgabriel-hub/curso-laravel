# 6.2. Modelos Eloquent

**Referencia teórica:** [Modelos de datos mediante ORM](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/044_modelosORM.html) · [Laravel: Eloquent](https://laravel.com/docs/13.x/eloquent) · [Mutators y casts](https://laravel.com/docs/13.x/eloquent-mutators)

Un modelo por tabla, con su `$table`, su `$fillable` en el orden del contrato, sus `casts`, sus accessors y la asignación automática del código correlativo.

## Conceptos clave

- `$fillable` protege de la asignación masiva: `codigo` **no** está en ninguno, porque lo asigna la aplicación.
- `casts`: `'fecha' => 'date:Y-m-d'` (la fecha se serializa como `2026-05-10`), `'importe' => 'float'`, `'mandante' => 'boolean'`.
- **Accessors** para valores derivados que no se guardan: `getNombreCompletoAttribute()` → `$cliente->nombre_completo`.
- **Eventos del modelo** (`static::creating(...)` en `booted()`) para valores que se rellenan solos: el código, la fecha de hoy, los datos del cliente en el presupuesto, la tarifa en la línea de parte.
- Un **trait** reutilizable agrupa un comportamiento común a varios modelos.

## Ejercicios

1. **Modelos.** Crear los 17 modelos del [contrato](./contrato-api.md#modelos) (`php artisan make:model Cliente`, etc.), cada uno con `protected $table` y `$fillable` exactamente en el orden indicado.

2. **Códigos correlativos.**

    1. Modelo `Secuencia` (tabla `secuencias`) con un método estático `siguiente(string $prefijo): string` que devuelve `OBR0001`, `OBR0002`… (la versión segura frente a concurrencia se completa en [6.8](./RA6_8_transacciones.md)).
    2. Trait `App\Models\Concerns\TieneCodigo` que, en el evento `creating`, asigna `$modelo->codigo ??= Secuencia::siguiente(static::PREFIJO)`.
    3. Cada modelo con código declara `public const PREFIJO = 'CLI';` y usa el trait.

3. **Casts, valores por defecto y accessors.**

    - Fechas `date:Y-m-d`; importes y tarifas `float`; los tres campos sí/no de `Gasto`, `boolean`.
    - `protected $attributes` para los valores por defecto que debe tener el objeto antes de guardarse (`coste_hora_estandar = 20`, `iva = 21`, `estado = 'no_aprobado'`…).
    - Accessors: `Cliente::nombre_completo`, `Trabajador::nombre_completo`, `Proveedor::total`, `LineaPresupuesto::total_linea`, `LineaParteTrabajo::coste_subtotal`, `Falta::dias`, `Presupuesto::anio`, `Ingreso::anio`.

4. **Tinker.**

    ```php
    >>> $c = App\Models\Cliente::create(['nombre' => 'Ana', 'direccion' => 'C/ Sol 1', 'telefono' => '600000000']);
    >>> $c->codigo            // "CLI0001"
    >>> $c->nombre_completo   // "Ana"
    ```

## Comprueba tu solución automáticamente

Los tests de modelo forman parte de la batería de evaluación y comprueban justo lo que fija el contrato (`$fillable`, tabla y relaciones), así que se usan ya en este bloque:

```bash
mkdir -p tests/Unit/Models
cp /var/www/curso-laravel/evaluacion/tests/Unit/Models/*.php tests/Unit/Models/
php artisan test tests/Unit/Models --filter='atributos_asignables|tabla_correcta'
```

Resultado esperado: `Tests: 34 passed`. Los tests de relaciones pasan en [6.6](./RA6_6_relaciones.md) y los de factory en [6.3](./RA6_3_seeders.md).

## Git

Rama `RA6_2`. Commits: `feat: modelos de maestros`, `feat: códigos correlativos con Secuencia y TieneCodigo`, `feat: modelos de obras, presupuestos y partes`.

---

**Siguiente:** [6.3. Factories y seeders](./RA6_3_seeders.md)
