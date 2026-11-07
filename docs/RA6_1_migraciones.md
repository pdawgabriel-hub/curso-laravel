# 6.1. Configuración, migraciones y _Schema Builder_

**Referencia teórica:** [Configuración inicial](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/041_configuracionInicial.html) · [Migraciones](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/042_migraciones.html) · [Schema Builder](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/043_schemaBuilder.html)

Los arrays de prueba se van a sustituir por MariaDB. Este apartado prepara la conexión y explica cómo se definen las tablas; se crean de una en una, a partir de [6.5](./RA6_5_clientes.md).

## Configuración

En phpMyAdmin (<http://localhost:8081>), *Cuentas de usuarios* → *Agregar cuenta de usuario*, con la opción **Crear base de datos con el mismo nombre y otorgar todos los privilegios**. En el `.env`:

```ini
DB_CONNECTION=mariadb
DB_HOST=mariadb
DB_DATABASE=gestion
DB_USERNAME=gestion
DB_PASSWORD=gestion
```

## Migraciones

Una migración es un fichero que describe un cambio en la base de datos: `up()` lo aplica y `down()` lo deshace.

| Comando | Para qué |
|---|---|
| `php artisan make:migration create_clientes_table --create=clientes` | Crea una migración para una tabla nueva |
| `php artisan make:migration add_rol_to_users_table --table=users` | Crea una migración que modifica una tabla |
| `php artisan migrate` | Aplica las migraciones pendientes |
| `php artisan migrate:rollback` | Deshace el último lote |
| `php artisan migrate:fresh` | Borra todas las tablas y vuelve a aplicarlas |

Las migraciones se ejecutan por orden de nombre. Si una tabla tiene una clave ajena, la tabla a la que apunta debe crearse antes.

## _Schema Builder_

| Método | Ejemplo en el proyecto |
|---|---|
| `string('nombre')` | Texto corto |
| `text('descripcion')` | Texto largo |
| `decimal('importe', 10, 2)` | Dinero; nunca `float` |
| `date('fecha')` | Fecha |
| `boolean('mandante')` | Sí o no |
| `enum('estado', ['aprobado', 'no_aprobado'])` | Lista cerrada de valores |
| `->nullable()`, `->default(21)`, `->unique()` | Modificadores |
| `foreignId('obra_id')->constrained('obras')` | Clave ajena |
| `->cascadeOnDelete()`, `->restrictOnDelete()`, `->nullOnDelete()` | Qué ocurre al borrar el registro al que apunta |

Los tests usan SQLite en memoria (`phpunit.xml`) y la aplicación MariaDB: las migraciones deben funcionar en las dos.

## Ejercicios

1. **La base de datos.** Crea el usuario y la base de datos `gestion`, cambia el `.env` y ejecuta:

    ```bash
    php artisan migrate:install
    php artisan migrate
    ```

    Comprueba en phpMyAdmin que aparecen la tabla `migrations` y las del esqueleto.

2. **El rol de los usuarios.** Crea la migración `add_rol_to_users_table` que añade a `users` la columna `rol`, un `enum('administrativa', 'jefe')` que admite nulos, después de `email`. El `down()` elimina la columna. Añade `rol` a los campos asignables del modelo `User`.

3. **Investiga.** Responde en tu _pull request_:
    - Ejecuta `php artisan migrate:status`. ¿Dónde guarda Laravel lo que ya se ha aplicado?
    - Ejecuta `php artisan migrate:rollback` y mira la tabla `users`. Vuelve a aplicarla. ¿Por qué no debes editar una migración ya aplicada en un proyecto compartido?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_1_ConfiguracionTest.php tests/Feature/
php artisan test --filter=RA6_1
```

Resultado esperado: `Tests: 1 passed`.

## Git

Rama `RA6_1`, commit `feat: rol de los usuarios`.

---

**Siguiente:** [6.2. Modelos de datos mediante ORM](./RA6_2_modelos.md)
