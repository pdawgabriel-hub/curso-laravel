# 6.12. Transacciones y bloqueos

**Referencia teórica:** [Query Builder: transacciones](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/046_queryBuilder.html) · [Laravel: Database Transactions](https://laravel.com/docs/13.x/database#database-transactions) · [Pessimistic Locking](https://laravel.com/docs/13.x/queries#pessimistic-locking) · [Laravel: Errors](https://laravel.com/docs/13.x/errors#renderable-exceptions)

Que las operaciones de varios pasos sean atómicas (todo o nada), que dos altas simultáneas no obtengan el mismo código y que las reglas de negocio de borrado tengan una respuesta uniforme.

## Conceptos clave

- `DB::transaction(fn () => …)`: si algo lanza una excepción dentro, se deshace todo.
- `lockForUpdate()`: bloquea la fila leída hasta el final de la transacción. Sin él, dos peticiones a la vez leerían el mismo `siguiente` de la secuencia y generarían dos `OBR0007`.
- **Servicio de dominio** (`ObraService`): la lógica que no pertenece a un único modelo ni a un controlador concreto, reutilizable desde la web, la API y los seeders.
- **Excepción renderizable**: una clase con método `render()` decide la respuesta: JSON 409 en la API, o volver atrás con `error` en la web.

## Ejercicios

1. **Secuencias seguras.** `Secuencia::siguiente()` dentro de `DB::transaction`: lee la fila del prefijo con `lockForUpdate()`, la crea si no existe, toma `siguiente`, lo incrementa y devuelve el código con relleno a 4 dígitos.

2. **ReglaNegocioException.** `App\Exceptions\ReglaNegocioException` con `render(Request $request)`:

    - Si `$request->expectsJson()` → `response()->json(['message' => …], 409)`.
    - Si no → `back()->with('error', …)`.

    Sustituye por ella las comprobaciones de borrado de [6.10](./RA6_10_presupuestos.md) y [6.11](./RA6_11_partes.md) (cliente con presupuestos, trabajador, proveedor o especialista con partes).

3. **ObraService.** `App\Services\ObraService` con:

    - `crear(array $datos): Obra`: crea la obra y su ficha de Gastos en una transacción.
    - `borrar(Obra $obra): void`: si la obra tiene ingresos lanza `ReglaNegocioException`; si no, en una transacción borra sus partes **uno a uno con Eloquent** (para que sus líneas, bitácoras y adjuntos se borren también), desvincula sus presupuestos (`obra_id = null`), borra su ficha de Gastos y la obra.

    `ObrasController` y `ObrasTableSeeder` usan el servicio: la semilla deja de crear la ficha a mano.

    > **Importante.** El borrado en cascada de la base de datos no ejecuta los eventos de Eloquent, así que no borraría los ficheros adjuntos ni las bitácoras polimórficas. Por eso los partes se borran con `->get()->each->delete()`.

4. **Investiga.** Responde en tu _pull request_: quita temporalmente `DB::transaction` de `crear()` y haz que falle la creación de la ficha lanzando una excepción en el evento `creating` de `Gasto`. ¿Qué queda en la base de datos? Deshaz el cambio.

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_12_TransaccionesTest.php tests/Feature/
php artisan test --filter=RA6_12
```

Resultado esperado: `Tests: 5 passed`. El test de atomicidad simula un fallo al crear la ficha de Gastos y comprueba que tampoco queda la obra.

## Git

Rama `RA6_12`. Commits: `feat: secuencias con bloqueo pesimista`, `feat: ReglaNegocioException`, `refactor: extrae ObraService`.

---

**Siguiente:** [6.13. Cálculos financieros](./RA6_13_calculos.md)
