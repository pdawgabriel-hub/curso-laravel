# 6.8. Relaciones entre modelos
<!-- {% raw %} -->

**Referencia teórica:** [Relaciones](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/08_relaciones.html) · [Uno a uno](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/081_relaciones_unoAuno.html) · [Uno a muchos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/082_relaciones_unoAmuchos.html) · [Muchos a muchos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/083_relaciones_muchosAmuchos.html) · [Insertar y actualizar con relaciones](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/084_DML_Relaciones.html) · [Laravel: Has Many Through](https://laravel.com/docs/13.x/eloquent-relationships#has-many-through)

Las claves ajenas existen desde [6.1](./RA6_1_migraciones.md); ahora los modelos las conocen. En vez de `Cliente::find($obra->cliente_id)` se escribe `$obra->cliente`. En este apartado se añaden **todas** las relaciones de la aplicación; las pantallas que las usan llegan en [6.9](./RA6_9_obras.md), [6.10](./RA6_10_presupuestos.md) y [6.11](./RA6_11_partes.md).

## Uno a uno

Una obra tiene **una** ficha de Gastos:

```php
// Obra
public function gasto(): HasOne { return $this->hasOne(Gasto::class); }

// Gasto
public function obra(): BelongsTo { return $this->belongsTo(Obra::class); }
```

La clave ajena `obra_id` está en `gastos`, y es `unique`. Sin esa restricción, sería uno a muchos.

## Uno a muchos

Un cliente tiene **muchas** obras:

```php
// Cliente
public function obras(): HasMany { return $this->hasMany(Obra::class); }

// Obra
public function cliente(): BelongsTo { return $this->belongsTo(Cliente::class); }
```

`$cliente->obras` devuelve una colección, y `$cliente->obras()` la consulta, a la que se puede seguir añadiendo condiciones. `Obra::with('cliente')->get()` carga los clientes de una vez y evita lanzar una consulta por cada obra (el problema _N+1_).

## Insertar con relaciones

```php
$obra->gasto()->create();                      // pone obra_id solo
$trabajador->faltas()->create($datos);          // pone trabajador_id solo
```

## Muchos a muchos

Un presupuesto incluye varios proveedores, y un proveedor aparece en varios presupuestos. Hace falta una **tabla pivote**, nombrada con los dos modelos en singular y por orden alfabético, con lo que Laravel la encuentra sola:

```php
// Presupuesto
public function proveedores(): BelongsToMany { return $this->belongsToMany(Proveedor::class); }

// Proveedor
public function presupuestos(): BelongsToMany { return $this->belongsToMany(Presupuesto::class); }
```

```php
$presupuesto->proveedores()->attach($id);     // añade
$presupuesto->proveedores()->detach($id);     // quita
$presupuesto->proveedores()->sync($ids);      // deja exactamente estos
```

## Relaciones a través de otra

Una obra tiene líneas de trabajo **a través de** sus partes, y `whereHas` busca según lo que hay en una relación:

```php
// Obra
public function lineasTrabajo(): HasManyThrough
{
    return $this->hasManyThrough(LineaParteTrabajo::class, ParteTrabajo::class);
}

public function trabajadoresParticipantes(): Builder
{
    return Trabajador::whereHas('lineasTrabajo.parte', fn ($q) => $q->where('obra_id', $this->id));
}
```

## Propietario

Las faltas guardan quién las registró. El `user_id` **nunca** sale del formulario, igual que el autor en [5.8](./RA5_8_proyecto.md):

```php
$trabajador->faltas()->create($request->validated() + ['user_id' => $request->user()->id]);
```

## Ejercicios

1. **Uno a uno y uno a muchos.** Añade estas relaciones. El método del lado "uno" devuelve `HasOne` o `HasMany`; el del lado "muchos", `BelongsTo`.

    Modelo | Relaciones
    -----|----
    `Cliente` | `presupuestos()`, `obras()`, `ingresos()` (HasMany)
    `Trabajador` | `lineasTrabajo()`, `faltas()` (HasMany)
    `Proveedor`, `Especialista` | `partes()` (HasMany, a `ParteProveedor` o `ParteEspecialista`)
    `Obra` | `cliente()` (BelongsTo); `gasto()` (HasOne); `presupuestos()`, `ingresos()`, `partesTrabajo()`, `partesProveedor()`, `partesEspecialista()` (HasMany)
    `Presupuesto` | `cliente()`, `obra()` (BelongsTo); `lineas()` (HasMany a `LineaPresupuesto`)
    `LineaPresupuesto` | `presupuesto()`
    `Gasto` | `obra()`, `presupuesto()`
    `Ingreso` | `obra()`, `cliente()`
    `ParteTrabajo` | `obra()`, `usuario()` (BelongsTo a `User` por `user_id`); `lineas()` (HasMany a `LineaParteTrabajo`, clave `parte_trabajo_id`)
    `LineaParteTrabajo` | `parte()` (`belongsTo(ParteTrabajo::class, 'parte_trabajo_id')`), `trabajador()`
    `ParteProveedor`, `ParteEspecialista` | `obra()`, `usuario()`, y `proveedor()` o `especialista()`
    `Falta` | `trabajador()`, `usuario()`
    `Observacion`, `Incidencia` | `usuario()`

    Cuando el nombre del método no coincide con la clave ajena (`usuario()` y `user_id`, `parte()` y `parte_trabajo_id`), hay que indicar la clave como segundo parámetro.

2. **Muchos a muchos.** `proveedores()` y `especialistas()` en `Presupuesto`, y `presupuestos()` en `Proveedor` y `Especialista`, sobre las tablas pivote de [6.1](./RA6_1_migraciones.md).

3. **A través de otra y participantes.** `lineasTrabajo()` en `Obra` (HasManyThrough). Y, sin ninguna lista que mantener a mano, quién ha participado en cada obra, deducido de sus partes; todos devuelven un `Builder`:
    - En `Obra`: `trabajadoresParticipantes()`, `proveedoresParticipantes()` y `especialistasParticipantes()`.
    - En `Trabajador`, `Proveedor` y `Especialista`: `obrasParticipadas()`.

4. **Usa las relaciones.** Ahora que existen, simplifica lo que hiciste sin ellas:
    - Los eventos de [6.2](./RA6_2_modelos.md): `$p->cliente` en lugar de `Cliente::find($p->cliente_id)`, y lo mismo con el trabajador y la obra.
    - `ObraFactory` ([6.3](./RA6_3_seeders.md)): `$obra->gasto()->create()` en lugar de `Gasto::create(['obra_id' => $obra->id])`.

5. **Pruébalo en `tinker`.**

    ```php
    $obra = App\Models\Obra::first();
    $obra->cliente->nombre_completo;
    $obra->gasto->codigo;
    $obra->partesTrabajo()->count();
    $obra->trabajadoresParticipantes()->pluck('nombre');
    $p = App\Models\Presupuesto::first();
    $p->proveedores()->sync([1, 2]);
    App\Models\Proveedor::find(1)->presupuestos->pluck('codigo');
    ```

6. **Investiga.** Responde en tu _pull request_:
    - Con `DB::enableQueryLog()` en `tinker`, recorre las obras mostrando el nombre de su cliente, con y sin `with('cliente')`. ¿Cuántas consultas lanza cada una?
    - Reescribe las consultas 1 y 3 de [6.4](./RA6_4_queryBuilder.md) con relaciones (`with`, `doesntHave`). ¿Cuál se lee mejor?
    - Borra un presupuesto con líneas. ¿Quién ha borrado las líneas, tu código o la base de datos?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_8_RelacionesTest.php tests/Feature/
php artisan test --filter=RA6_8
```

Resultado esperado: `Tests: 9 passed`.

Y los tests de modelo de la evaluación, que comprueban `$fillable`, tabla, relaciones y _factory_ de los 17 modelos:

```bash
mkdir -p tests/Unit/Models
cp /var/www/curso-laravel/evaluacion/tests/Unit/Models/*.php tests/Unit/Models/
php artisan test tests/Unit/Models
```

Resultado esperado: todos pasan salvo los que comprueban las relaciones de las bitácoras (`observaciones()`, `incidencias()`, `archivos()` y sus `morphTo`), que llegan en [6.14](./RA6_14_bitacoras.md).

## Git

Rama `RA6_8`. Commits: `feat: relaciones uno a muchos`, `feat: relaciones muchos a muchos`, `feat: participantes de las obras`.

---

**Siguiente:** [6.9. Ejercicios: obras, ficha de Gastos y faltas](./RA6_9_obras.md)
<!-- {% endraw %} -->
