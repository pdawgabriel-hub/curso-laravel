# 6.3. Inicialización de la base de datos (_seeders_ y _factories_)

**Referencia teórica:** [Inicialización de la base de datos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/045_databaseSeeding.html) · [Laravel: Factories](https://laravel.com/docs/13.x/eloquent-factories)

En este apartado se crean **todas** las _factories_ y las semillas, incluidos los datos de demostración: veinte obras completas con las que trabajar en el resto del bloque.

## Ficheros semilla

Una clase por tabla, que `DatabaseSeeder` llama en orden. Es el equivalente a los datos de demostración del módulo de _Odoo_.

```bash
php artisan make:seeder ClientesTableSeeder
```

```php
public function run(): void
{
    Model::unguard();
    Schema::disableForeignKeyConstraints();

    $this->call(ClientesTableSeeder::class);

    Model::reguard();
    Schema::enableForeignKeyConstraints();
}
```

| Comando | Para qué |
|---|---|
| `php artisan db:seed` | Ejecuta `DatabaseSeeder` |
| `php artisan db:seed --class=ClientesTableSeeder` | Ejecuta una sola semilla |
| `php artisan migrate:fresh --seed` | Rehace las tablas y las rellena |

## _Factories_

Una _factory_ define datos válidos de ejemplo. La usan las semillas para generar volumen y los tests para crear lo que necesitan:

```bash
php artisan make:factory ClienteFactory --model=Cliente
```

```php
public function definition(): array
{
    return [
        'nombre' => fake()->firstName(),
        'nif' => fake()->unique()->numerify('########') . fake()->randomLetter(),
        // ...
    ];
}
```

```php
Cliente::factory()->count(10)->create();                  // diez clientes
Ingreso::factory()->create(['importe' => 500]);           // lo demás, de la factory
```

El modelo necesita `use HasFactory;`. Las claves ajenas se definen con otra _factory_ (`'obra_id' => Obra::factory()`), que solo se ejecuta si no se pasa ese valor. Con `APP_FAKER_LOCALE=es_ES` (de [5.2](./RA5_2_estructura.md)), los datos salen en español.

## Semillas y _factories_ en el proyecto

No son alternativas: cada una tiene su papel.

- Las **semillas** rellenan la base de datos de desarrollo con `migrate:fresh --seed`. Todas se apoyan en las _factories_ (`Cliente::factory(20)->create()`): los arrays del Bloque 5 desaparecen en este bloque.
- Los **tests** crean sus datos con _factories_ y no dependen de las semillas, salvo los que comprueban la propia semilla. Una _factory_ que falle hace fallar todos los tests que la usen.

Por eso cada ejercicio indica los campos de su _factory_. No llevan _factory_ `Secuencia`, `Gasto` (nace con su obra), `Observacion`, `Incidencia` ni `Archivo` (se crean desde su registro).

## Ejercicios

1. **Las _factories_ de los maestros.** Con `php artisan make:factory ClienteFactory --model=Cliente`, y así con cada una. `codigo` no va en ninguna: lo asigna `TieneCodigo`.

    Campo | Valor en `ClienteFactory`
    -----|----
    nombre | `fake()->firstName()`
    apellidos | `fake()->lastName()`
    nif | `fake()->unique()->numerify('########') . fake()->randomLetter()`
    direccion | `fake()->streetAddress()`
    cod_postal | `fake()->numerify('#####')`
    telefono | `fake()->numerify('6########')`

    Campo | Valor en `TrabajadorFactory`
    -----|----
    tipo | `'Peón'`
    nombre | `fake()->firstName()`
    apellido | `fake()->lastName()`
    telefono | `fake()->numerify('6########')`
    coste_hora_estandar | `20`

    Campo | Valor en `ProveedorFactory`
    -----|----
    nombre | `fake()->company()`
    direccion | `fake()->streetAddress()`
    telefono | `fake()->numerify('9########')`
    correo | `fake()->companyEmail()`
    tipo | `'Materiales'`
    referencia | `fake()->bothify('REF-####')`
    base | `fake()->randomFloat(2, 100, 5000)`
    iva | `21`

    Campo | Valor en `EspecialistaFactory`
    -----|----
    tipo | `fake()->randomElement(['Arquitecto', 'Electricista'])`
    referencia | `fake()->bothify('ESP-####')`
    nombre | `fake()->name()`
    telefono | `fake()->numerify('6########')`
    correo | `fake()->safeEmail()`
    importe | `fake()->randomFloat(2, 100, 2000)`

2. **Obras, presupuestos e ingresos.** Una clave ajena se rellena con la _factory_ del otro modelo (`Cliente::factory()`), que solo se ejecuta si no se pasa ese valor.

    Campo | Valor en `ObraFactory`
    -----|----
    descripcion | `'Reforma de ' . fake()->word()`
    cliente_id | `Cliente::factory()`

    Toda obra creada con la _factory_ tiene su ficha de Gastos, como ocurrirá al darla de alta en la aplicación:

    ```php
    public function configure(): static
    {
        return $this->afterCreating(fn (Obra $obra) => Gasto::create(['obra_id' => $obra->id]));
    }
    ```

    Campo | Valor en `PresupuestoFactory`
    -----|----
    fecha | `fake()->dateTimeBetween('-1 year')->format('Y-m-d')`
    cliente_id | `Cliente::factory()`
    obra_id | `null`
    iva | `21`
    estado | `'no_aprobado'`

    No rellena `nombre_cliente`, `nif`, `direccion` ni `telefono`: se copian del cliente con el evento de [6.2](./RA6_2_modelos.md).

    Campo | Valor en `LineaPresupuestoFactory`
    -----|----
    presupuesto_id | `Presupuesto::factory()`
    descripcion | `fake()->sentence(4)`
    uds | `fake()->numberBetween(1, 10)`
    precio | `fake()->randomFloat(2, 10, 500)`

    Campo | Valor en `IngresoFactory`
    -----|----
    obra_id | `Obra::factory()`
    tipo | `'Transferencia'`
    fecha | `fake()->dateTimeBetween('-1 year')->format('Y-m-d')`
    importe | `fake()->randomFloat(2, 100, 2000)`

    `cliente_id` y `nombre_obra` no van en la _factory_: se toman de la obra.

3. **Partes y faltas.** `user_id` no va en ninguna: queda vacío salvo que un test lo indique.

    Campo | Valor en `ParteTrabajoFactory`
    -----|----
    obra_id | `Obra::factory()`
    fecha | `fake()->dateTimeBetween('-1 year')->format('Y-m-d')`
    descripcion | `fake()->sentence()`

    Campo | Valor en `LineaParteTrabajoFactory`
    -----|----
    parte_trabajo_id | `ParteTrabajo::factory()`
    trabajador_id | `Trabajador::factory()`
    horas | `fake()->numberBetween(1, 8)`

    No rellena `coste_hora`: se copia del trabajador.

    Campo | Valor en `ParteProveedorFactory`
    -----|----
    obra_id | `Obra::factory()`
    proveedor_id | `Proveedor::factory()`
    fecha | `fake()->dateTimeBetween('-1 year')->format('Y-m-d')`
    descripcion | `fake()->sentence()`
    documento | `fake()->bothify('FAC-####')`
    importe | `fake()->randomFloat(2, 50, 1000)`
    estado_pago | `'pendiente'`

    Campo | Valor en `ParteEspecialistaFactory`
    -----|----
    obra_id | `Obra::factory()`
    especialista_id | `Especialista::factory()`
    fecha | `fake()->dateTimeBetween('-1 year')->format('Y-m-d')`
    descripcion | `fake()->sentence()`
    documento | `fake()->bothify('FAC-####')`
    importe | `fake()->randomFloat(2, 50, 1000)`
    estado_pago | `'pendiente'`

    Campo | Valor en `FaltaFactory`
    -----|----
    trabajador_id | `Trabajador::factory()`
    fecha_inicio | `$inicio = fake()->dateTimeBetween('-1 year')->format('Y-m-d')`
    fecha_fin | `date('Y-m-d', strtotime($inicio . ' +' . fake()->numberBetween(0, 10) . ' days'))`
    tipo | `'vacaciones'`
    motivo | `fake()->sentence()`

4. **Las semillas de los maestros.** Una por tabla, con `php artisan make:seeder ClientesTableSeeder`:

    Semilla | Qué hace
    -----|----
    `ClientesTableSeeder` | `Cliente::factory(20)->create()`
    `TrabajadoresTableSeeder` | `Trabajador::factory(20)->create()`
    `ProveedoresTableSeeder` | `Proveedor::factory(20)->create()`
    `EspecialistasTableSeeder` | `Especialista::factory(20)->create()`
    `FaltasTableSeeder` | De 0 a 4 faltas por cada trabajador: `Falta::factory(rand(0, 4))->create(['trabajador_id' => $t->id])`

5. **Los datos de demostración.** `ObrasTableSeeder` crea 20 obras de clientes ya existentes. Por cada obra:
    - un presupuesto **aprobado** de su cliente, vinculado a la obra, con 3 a 6 líneas;
    - 2 a 5 partes de trabajo con 2 a 4 trabajadores ya existentes cada uno;
    - 1 a 3 partes de proveedor y 1 o 2 de especialista, con importes de entre el 5 % y el 15 % del total del presupuesto, para que el beneficio no salga siempre negativo;
    - 0 a 3 ingresos cuya suma **nunca supere el total**: cada ingreso es un porcentaje de lo que queda pendiente.

    El total del presupuesto se calcula a partir de sus líneas: `Σ (uds × precio) × (1 + iva / 100)`, redondeado a 2 decimales.

6. **`DatabaseSeeder`.** Llama, en este orden, a las semillas de clientes, trabajadores, faltas, proveedores, especialistas y obras, entre `Model::unguard()` y `Model::reguard()`, como en la teoría. Comprueba el resultado:

    ```bash
    php artisan migrate:fresh --seed
    ```

7. **Investiga.** Responde en tu _pull request_:
    - Ejecuta dos veces `migrate:fresh --seed`. ¿Salen los mismos clientes? ¿Por qué los tests no dependen de estos datos?
    - ¿Qué ocurre con `Presupuesto::factory()->create(['cliente_id' => $c->id])`? ¿Y con `Presupuesto::factory()->create()`? ¿Cuántos clientes hay después de cada una?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_3_SemillasTest.php tests/Feature/
php artisan test --filter=RA6_3
```

Resultado esperado: `Tests: 18 passed`.

## Git

Rama `RA6_3`. Commits: `feat: factories`, `feat: semillas de los maestros`, `feat: datos de demostración`.

---

**Siguiente:** [6.4. Constructor de consultas](./RA6_4_queryBuilder.md)
