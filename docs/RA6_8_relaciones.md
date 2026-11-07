# 6.8. Relaciones: obras y faltas
<!-- {% raw %} -->

**Referencia teórica:** [Relaciones](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/08_relaciones.html) · [Uno a uno](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/081_relaciones_unoAuno.html) · [Uno a muchos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/082_relaciones_unoAmuchos.html) · [Insertar y actualizar con relaciones](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/084_DML_Relaciones.html)

Hasta ahora, cada tabla iba por su cuenta. Las siguientes dependen unas de otras.

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

## Propietario

Las faltas guardan quién las registró. El `user_id` **nunca** sale del formulario, igual que el autor en [5.8](./RA5_8_proyecto.md):

```php
$trabajador->faltas()->create($request->validated() + ['user_id' => $request->user()->id]);
```

## Ejercicios

1. **Obras.** Migración `create_obras_table`: `codigo` (único), `descripcion` (texto, opcional) y `cliente_id` (clave ajena opcional a `clientes`, con `nullOnDelete`). Modelo `Obra` con `PREFIJO = 'OBR'` y las relaciones `cliente()` y, en `Cliente`, `obras()`.

2. **La ficha de Gastos.** Migración `create_gastos_table`:

    Campo | Tipo | nullable
    -----|----|---
    codigo | string(20), único | no
    obra_id | clave ajena, **única**, `cascadeOnDelete` | no
    presupuesto_id | unsignedBigInteger (su clave ajena se añade en 6.9) | sí
    direccion_obra | string | sí
    inicio_obra, fin_obra, solicitud_obra | date | sí
    mandante, gestion_licencia, pago_icio | boolean, `false` por defecto | no

    Modelo `Gasto` con `PREFIJO = 'GAS'`, _casts_ y las relaciones `obra()` y, en `Obra`, `gasto()`. **No lleva _factory_**: la ficha nace con su obra.

3. **La obra crea su ficha.** `ObraFactory`: toda obra creada con la _factory_ tiene su ficha:

    ```php
    public function configure(): static
    {
        return $this->afterCreating(fn (Obra $obra) => $obra->gasto()->firstOrCreate());
    }
    ```

    `ObrasController` usa el modelo con `findOrFail`, y `store` crea la obra y su ficha. En [6.13](./RA6_13_transacciones.md) esto se hará en una transacción. Elimina `$arrayObras` y sustituye en las vistas `$obra['campo']` por `$obra->campo`. El alta ofrece un selector de clientes y el detalle muestra `$obra->cliente?->nombre_completo`. De momento, deja de mostrar el total y el estado de pago: volverán, calculados, en [6.12](./RA6_12_calculos.md). Añade `destroy` y su ruta.

4. **Faltas.** Migración `create_faltas_table`: `trabajador_id` (clave ajena, `cascadeOnDelete`), `fecha_inicio`, `fecha_fin`, `tipo` (`enum`: `vacaciones` por defecto, `baja_medica`, `personal`, `injustificada`, `otro`), `motivo` (texto, opcional) y `user_id` (clave ajena opcional a `users`, con `nullOnDelete`). Modelo `Falta`, con las relaciones `trabajador()` y `usuario()`, y el _accessor_ `dias` (inclusive: un solo día cuenta 1). En `Trabajador`, `faltas()`. `FaltaFactory` y `FaltasTableSeeder`, con 0 a 4 faltas por trabajador.

5. **Pantallas de faltas.** Se crean desde el trabajador:

    | Método | URL | Nombre |
    |---|---|---|
    | GET | `/trabajadores/{trabajador}/faltas/create` | `trabajadores.faltas.create` |
    | POST | `/trabajadores/{trabajador}/faltas` | `trabajadores.faltas.store` |
    | DELETE | `/trabajadores/{trabajador}/faltas/{falta}` | `trabajadores.faltas.destroy` |
    | GET | `/faltas` | `faltas.index` (todas, con el nombre del trabajador) |

    Al guardar o borrar se vuelve al detalle del trabajador, que lista sus faltas con el texto del tipo (`Vacaciones`, `Baja médica`, `Personal`, `Injustificada`, `Otro`) y los días. El propietario es el usuario conectado. Una falta de otro trabajador da 404.

6. **Investiga.** Responde en tu _pull request_:
    - Con `DB::enableQueryLog()` en `tinker`, recorre las obras mostrando el nombre de su cliente, con y sin `with('cliente')`. ¿Cuántas consultas lanza cada una?
    - Añade `<input name="user_id" value="1">` al formulario de faltas con las herramientas de desarrollo. ¿Qué `user_id` se guarda? ¿Por qué?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_8_ObrasFaltasTest.php tests/Feature/
php artisan test --filter=RA6_8
```

Resultado esperado: `Tests: 9 passed`.

## Git

Rama `RA6_8`. Commits: `feat: obras y ficha de gastos`, `feat: faltas de los trabajadores`.

---

**Siguiente:** [6.9. Ejercicios: presupuestos](./RA6_9_presupuestos.md)
<!-- {% endraw %} -->
