# 6.1. Configuración, migraciones y _Schema Builder_

**Referencia teórica:** [Configuración inicial](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/041_configuracionInicial.html) · [Migraciones](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/042_migraciones.html) · [Schema Builder](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/043_schemaBuilder.html)

Los arrays de prueba se van a sustituir por MariaDB. En este apartado se crean **todas** las tablas de la aplicación; los modelos llegan en [6.2](./RA6_2_modelos.md) y los datos en [6.3](./RA6_3_seeders.md).

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
| `php artisan make:migration add_rol_to_users_table --table=users` | Crea una migración que modifica una tabla (en [6.7](./RA6_7_usuarios.md)) |
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
| `morphs('observable')` | Dos columnas, `observable_type` y `observable_id`, para apuntar a una fila de cualquier tabla ([6.14](./RA6_14_bitacoras.md)) |
| `$table->primary(['a_id', 'b_id'])` | Clave primaria compuesta (tablas pivote) |
| `->cascadeOnDelete()`, `->restrictOnDelete()`, `->nullOnDelete()` | Qué ocurre al borrar el registro al que apunta |

Los tests usan SQLite en memoria (`phpunit.xml`) y la aplicación MariaDB: las migraciones deben funcionar en las dos.

## Ejercicios

1. **La base de datos.** Crea el usuario y la base de datos `gestion`, cambia el `.env` y ejecuta:

    ```bash
    php artisan migrate:install
    php artisan migrate
    ```

    Comprueba en phpMyAdmin que aparecen la tabla `migrations` y las del esqueleto.

2. **El orden.** Las migraciones se ejecutan por la fecha de su nombre, así que se crean **en el orden de los ejercicios**: cada clave ajena apunta a una tabla que ya existe. Todas las tablas llevan, además de sus campos, `id()` y `timestamps()`, salvo las dos tablas pivote del ejercicio 5. Al terminar cada ejercicio, ejecuta `php artisan migrate` y revisa la tabla en phpMyAdmin.

3. **Los maestros y los códigos.** Los códigos (`CLI0001`) los asignará la aplicación en [6.2](./RA6_2_modelos.md), con la tabla `secuencias`.

    ```bash
    php artisan make:migration create_clientes_table --create=clientes
    ```

    Campo | Tipo | nullable
    -----|----|---
    codigo | string(20), único | no
    nombre | string | no
    apellidos | string | sí
    nif | string(20), único | sí
    direccion | string | no
    cod_postal | string(10) | sí
    telefono | string(20) | no

    ```bash
    php artisan make:migration create_secuencias_table --create=secuencias
    ```

    Campo | Tipo | nullable
    -----|----|---
    prefijo | string(10), único | no
    siguiente | entero sin signo, **1** por defecto | no

    ```bash
    php artisan make:migration create_trabajadores_table --create=trabajadores
    ```

    Campo | Tipo | nullable
    -----|----|---
    codigo | string(20), único | no
    tipo | string | sí
    nombre | string | no
    apellido | string | no
    telefono | string(20) | sí
    coste_hora_estandar | decimal(8,2), **20** por defecto | no

    ```bash
    php artisan make:migration create_proveedores_table --create=proveedores
    ```

    Campo | Tipo | nullable
    -----|----|---
    codigo | string(20), único | no
    nombre | string | no
    direccion | string | sí
    telefono | string(20) | sí
    correo | string | sí
    tipo | string | sí
    referencia | string | sí
    documento | string | sí
    num_documento | string | sí
    base | decimal(10,2) | no
    iva | decimal(5,2) | no

    ```bash
    php artisan make:migration create_especialistas_table --create=especialistas
    ```

    Campo | Tipo | nullable
    -----|----|---
    codigo | string(20), único | no
    tipo | string | sí
    referencia | string | sí
    comunicacion_parte | text | sí
    nombre | string | no
    notas | text | sí
    telefono | string(20) | sí
    correo | string | sí
    importe | decimal(10,2) | sí

4. **Obras y presupuestos.**

    ```bash
    php artisan make:migration create_obras_table --create=obras
    ```

    Campo | Tipo | nullable
    -----|----|---
    codigo | string(20), único | no
    descripcion | text | sí
    cliente_id | clave ajena a `clientes`, `nullOnDelete` | sí

    ```bash
    php artisan make:migration create_presupuestos_table --create=presupuestos
    ```

    Campo | Tipo | nullable
    -----|----|---
    codigo | string(20), único | no
    fecha | date | no
    cliente_id | clave ajena a `clientes`, `restrictOnDelete` | no
    obra_id | clave ajena a `obras`, `nullOnDelete` | sí
    nombre_cliente | string | no
    nif | string(20) | sí
    direccion | string | sí
    telefono | string(20) | sí
    iva | decimal(5,2), **21** por defecto | no
    estado | enum: `aprobado`, `no_aprobado` (por defecto) | no

    ```bash
    php artisan make:migration create_lineas_presupuesto_table --create=lineas_presupuesto
    ```

    Campo | Tipo | nullable
    -----|----|---
    presupuesto_id | clave ajena a `presupuestos`, `cascadeOnDelete` | no
    descripcion | text | sí
    uds | entero sin signo, **1** por defecto | no
    precio | decimal(10,2), **0** por defecto | no

5. **Las tablas pivote.** Un presupuesto incluye varios proveedores y especialistas, y cada uno aparece en varios presupuestos. Estas tablas **no llevan `id()` ni `timestamps()`**: su clave primaria es la pareja, `$table->primary(['presupuesto_id', 'proveedor_id'])`. Su nombre es el de los dos modelos en singular y por orden alfabético, así Laravel las encuentra sin indicarlo ([6.8](./RA6_8_relaciones.md)).

    ```bash
    php artisan make:migration create_presupuesto_proveedor_table --create=presupuesto_proveedor
    ```

    Campo | Tipo | nullable
    -----|----|---
    presupuesto_id | clave ajena a `presupuestos`, `cascadeOnDelete` | no
    proveedor_id | clave ajena a `proveedores`, `cascadeOnDelete` | no

    ```bash
    php artisan make:migration create_especialista_presupuesto_table --create=especialista_presupuesto
    ```

    Campo | Tipo | nullable
    -----|----|---
    especialista_id | clave ajena a `especialistas`, `cascadeOnDelete` | no
    presupuesto_id | clave ajena a `presupuestos`, `cascadeOnDelete` | no

6. **La ficha de Gastos y los ingresos.** Cada obra tiene **una** ficha de Gastos: por eso `obra_id` es único. Un cobro no desaparece con su obra: `restrictOnDelete`.

    ```bash
    php artisan make:migration create_gastos_table --create=gastos
    ```

    Campo | Tipo | nullable
    -----|----|---
    codigo | string(20), único | no
    obra_id | clave ajena a `obras`, **única**, `cascadeOnDelete` | no
    presupuesto_id | clave ajena a `presupuestos`, `nullOnDelete` | sí
    direccion_obra | string | sí
    inicio_obra | date | sí
    fin_obra | date | sí
    solicitud_obra | date | sí
    mandante | boolean, `false` por defecto | no
    gestion_licencia | boolean, `false` por defecto | no
    pago_icio | boolean, `false` por defecto | no

    ```bash
    php artisan make:migration create_ingresos_table --create=ingresos
    ```

    Campo | Tipo | nullable
    -----|----|---
    codigo | string(20), único | no
    obra_id | clave ajena a `obras`, `restrictOnDelete` | no
    cliente_id | clave ajena a `clientes`, `nullOnDelete` | sí
    nombre_obra | string | sí
    tipo | string | sí
    fecha | date | no
    documento | string | sí
    num_documento | string | sí
    importe | decimal(10,2) | no

7. **Los partes.** `user_id` guarda quién registró el parte. Un trabajador, proveedor o especialista con partes no se puede borrar: `restrictOnDelete`.

    ```bash
    php artisan make:migration create_partes_trabajo_table --create=partes_trabajo
    ```

    Campo | Tipo | nullable
    -----|----|---
    obra_id | clave ajena a `obras`, `cascadeOnDelete` | no
    fecha | date | no
    descripcion | text | no
    user_id | clave ajena a `users`, `nullOnDelete` | sí

    ```bash
    php artisan make:migration create_lineas_parte_trabajo_table --create=lineas_parte_trabajo
    ```

    Campo | Tipo | nullable
    -----|----|---
    parte_trabajo_id | clave ajena a `partes_trabajo`, `cascadeOnDelete` | no
    trabajador_id | clave ajena a `trabajadores`, `restrictOnDelete` | no
    horas | decimal(5,2), **0** por defecto | no
    coste_hora | decimal(8,2), **0** por defecto | no

    ```bash
    php artisan make:migration create_partes_proveedor_table --create=partes_proveedor
    ```

    Campo | Tipo | nullable
    -----|----|---
    obra_id | clave ajena a `obras`, `cascadeOnDelete` | no
    proveedor_id | clave ajena a `proveedores`, `restrictOnDelete` | no
    fecha | date | no
    descripcion | text | sí
    documento | string | sí
    importe | decimal(10,2) | no
    estado_pago | enum: `pendiente` (por defecto), `pagado` | no
    user_id | clave ajena a `users`, `nullOnDelete` | sí

    ```bash
    php artisan make:migration create_partes_especialista_table --create=partes_especialista
    ```

    Campo | Tipo | nullable
    -----|----|---
    obra_id | clave ajena a `obras`, `cascadeOnDelete` | no
    especialista_id | clave ajena a `especialistas`, `restrictOnDelete` | no
    fecha | date | no
    descripcion | text | sí
    documento | string | sí
    importe | decimal(10,2) | no
    estado_pago | enum: `pendiente` (por defecto), `pagado` | no
    user_id | clave ajena a `users`, `nullOnDelete` | sí

    ```bash
    php artisan make:migration create_faltas_table --create=faltas
    ```

    Campo | Tipo | nullable
    -----|----|---
    trabajador_id | clave ajena a `trabajadores`, `cascadeOnDelete` | no
    fecha_inicio | date | no
    fecha_fin | date | no
    tipo | enum: `vacaciones` (por defecto), `baja_medica`, `personal`, `injustificada`, `otro` | no
    motivo | text | sí
    user_id | clave ajena a `users`, `nullOnDelete` | sí

8. **Bitácoras y adjuntos.** Una sola tabla de observaciones sirve para clientes, obras, presupuestos…: `morphs('observable')` crea `observable_type` (qué tabla) y `observable_id` (qué fila), con su índice. Se usan en [6.14](./RA6_14_bitacoras.md).

    ```bash
    php artisan make:migration create_observaciones_table --create=observaciones
    ```

    Campo | Tipo | nullable
    -----|----|---
    observable | `morphs` | no
    texto | text | no
    user_id | clave ajena a `users`, `nullOnDelete` | sí

    ```bash
    php artisan make:migration create_incidencias_table --create=incidencias
    ```

    Campo | Tipo | nullable
    -----|----|---
    incidentable | `morphs` | no
    texto | text | no
    user_id | clave ajena a `users`, `nullOnDelete` | sí

    ```bash
    php artisan make:migration create_archivos_table --create=archivos
    ```

    Campo | Tipo | nullable
    -----|----|---
    archivable | `morphs` | no
    nombre_archivo | string | no
    ruta | string | no
    tipo | enum: `imagen`, `pdf`, `otro` | no

9. **Investiga.** Responde en tu _pull request_:
    - Ejecuta `php artisan migrate:status`. ¿Dónde guarda Laravel lo que ya se ha aplicado?
    - Ejecuta `php artisan migrate:rollback` y mira qué tablas desaparecen. Vuelve a aplicarlas. ¿Por qué no debes editar una migración ya aplicada en un proyecto compartido?
    - Cambia temporalmente el nombre de la migración de `presupuestos` para que se ejecute antes que la de `obras` y lanza `php artisan migrate:fresh`. ¿Qué error aparece? Deshaz el cambio.

## Comprueba tu solución automáticamente

```bash
rm tests/Feature/RA5_*.php     # comprobaban la etapa de arrays: dejan de servir a lo largo del bloque
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_1_MigracionesTest.php tests/Feature/
php artisan test --filter=RA6_1
```

Resultado esperado: `Tests: 25 passed`.

> **Nota.** Los tests de [5.x](./RA5_8_proyecto.md) se retiran aquí porque, a medida que avance el bloque, los arrays y el repositorio desaparecen.

## Git

Rama `RA6_1`. Un commit por ejercicio: `feat: tablas de los maestros`, `feat: obras y presupuestos`, …

---

**Siguiente:** [6.2. Modelos de datos mediante ORM](./RA6_2_modelos.md)
