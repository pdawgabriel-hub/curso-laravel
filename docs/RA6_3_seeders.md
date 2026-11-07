# 6.3. Inicialización de la base de datos (_seeders_ y _factories_)

**Referencia teórica:** [Inicialización de la base de datos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/045_databaseSeeding.html) · [Laravel: Factories](https://laravel.com/docs/13.x/eloquent-factories)

Este apartado es teoría: cada entidad crea su semilla en sus ejercicios, a partir de [6.5](./RA6_5_clientes.md).

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

- Las **semillas** rellenan la base de datos de desarrollo con `migrate:fresh --seed`. Casi todas se limitan a llamar a una _factory_ (`Proveedor::factory(20)->create()`).
- Los **tests** crean sus datos con _factories_ y no dependen de las semillas, salvo los que comprueban la propia semilla. Una _factory_ que falle hace fallar todos los tests que la usen.

Por eso cada ejercicio indica los campos de su _factory_. No llevan _factory_ `Secuencia`, `Gasto` (nace con su obra), `Observacion`, `Incidencia` ni `Archivo` (se crean desde su registro).

---

**Siguiente:** [6.4. Constructor de consultas](./RA6_4_queryBuilder.md)
