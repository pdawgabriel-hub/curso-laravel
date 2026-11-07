# 7.5. Autorización: Gates, Policies y roles

**Referencia teórica:** [Autorización](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/10_autorizacion.html) · [Gates](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/101_gates.html) · [Policies](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/102_policies.html) · [Roles y permisos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/103_roles.html) · [Laravel: Authorization](https://laravel.com/docs/13.x/authorization)

Cada acción se permite o no según quién la pide, y a veces según de quién es el registro. Se combinan _Policies_ con los roles del proyecto:

- **usuario sin rol:** no accede a nada;
- **administrador:** el usuario cuyo email coincide con `ADMIN_EMAIL`, que puede con todo;
- **jefe** y **administrativa:** la columna `rol` de 6.1;
- **propietario de un recurso:** el usuario de su `user_id`.

## _Gates_ y _Policies_

- Un **_Gate_** es una regla suelta en `AppServiceProvider`. Sirve para algo puntual, pero la mayoría de las aplicaciones usan _Policies_.
- Una **_Policy_** agrupa las reglas de un modelo: `php artisan make:policy ClientePolicy --model=Cliente`. Laravel la encuentra por su nombre.
- `Gate::authorize('update', $cliente)` lanza 403 si la _Policy_ dice que no. En una vista, `@can('delete', $cliente)`.
- **Se autoriza antes de validar:** un usuario sin permiso recibe 403, no los errores de validación.
- `Gate::before` da paso al administrador; devolver `null` deja que decida la _Policy_:

    ```php
    Gate::before(fn (User $user) => $user->esAdmin() ? true : null);
    ```

- Cuando varias _Policies_ son iguales, una clase base evita repetir código.

## Tabla de permisos

| Recurso | Ver | Crear | Modificar | Borrar |
|---|---|---|---|---|
| Clientes, proveedores, especialistas, trabajadores | con rol | con rol | con rol | jefe |
| Presupuestos | con rol | con rol | con rol; aprobar o retirar la aprobación, solo jefe | jefe |
| Líneas de presupuesto | con rol | con rol | con rol | con rol |
| Obras | con rol | jefe | jefe | jefe |
| Partes, faltas | con rol | con rol | propietario o jefe | propietario o jefe |
| Líneas de parte | con rol | como su parte | como su parte | como su parte |
| Ingresos, ficha de Gastos, estadísticas | jefe | jefe (Gastos no se crean) | jefe | jefe (Gastos no se borran) |
| Observaciones, incidencias | si puede ver su registro | con rol, si puede ver su registro | — | propietario o jefe |
| Adjuntos | si puede ver su registro | si puede modificar su registro | — | si puede modificar su registro |

El calendario se ve con rol, pero a quien no es jefe no le aparecen los ingresos.

## Ejercicios

1. **Los roles.** En `User`, como en la referencia teórica:

    ```php
    public function esAdmin(): bool { return $this->email === config('app.admin.email'); }
    public function esJefe(): bool { return $this->rol === 'jefe'; }
    public function esAdministrativa(): bool { return $this->rol === 'administrativa'; }
    public function esPropietario($recurso, string $propiedad = 'user_id'): bool { return $recurso->{$propiedad} === $this->id; }
    public function tieneRol(): bool { return $this->esAdmin() || $this->esJefe() || $this->esAdministrativa(); }
    public function roles(): array { return $this->esAdmin() ? ['administrador'] : array_values(array_filter([$this->rol])); }
    ```

    `GET /api/v1/user` devuelve también `roles`. Añade `Gate::before` a `AppServiceProvider::boot()`.

2. **La _Policy_ base.** `App\Policies\PolicyBase`: `viewAny`, `view`, `create` y `update` devuelven `tieneRol()`, y `delete`, `esJefe()`. `ClientePolicy`, `TrabajadorPolicy`, `ProveedorPolicy` y `EspecialistaPolicy` heredan de ella sin más. Añade `Gate::authorize()` a todas las acciones de `ClienteController` y comprueba la primera fila de la tabla con Swagger.

3. **El resto de la tabla.** Crea una _Policy_ por modelo:
    - `ObraPolicy`: `create` y `update`, solo jefe. `vincular-presupuesto` es modificar la obra.
    - `ParteTrabajoPolicy`, `ParteProveedorPolicy`, `ParteEspecialistaPolicy` y `FaltaPolicy`: `update` y `delete`, jefe o propietario.
    - `LineaParteTrabajoPolicy`: lo que diga la _Policy_ de su parte (`$user->can('update', $linea->parte)`). Crear una línea, o un lote, es modificar el parte.
    - `IngresoPolicy` y `GastoPolicy`: todo, solo jefe. Las estadísticas usan `viewAny` de `Gasto`.
    - `ObservacionPolicy` e `IncidenciaPolicy`: `delete`, jefe o propietario.
    - `ArchivoPolicy`: ver y borrar, según su registro (`$user->can('view', $archivo->archivable)`).
    - Añade `GET /user/observaciones`, con las observaciones del usuario autenticado.

4. **Aprobar.** Método `aprobar` en `PresupuestoPolicy`, solo jefe. En `store` y `update`, si el estado cambia a `aprobado` o deja de serlo, `Gate::authorize('aprobar', Presupuesto::class)`.

5. **El propietario.** En la API, igual que en la web de 6.8 y 6.10: `user_id` es siempre el usuario autenticado, y lo que venga en la petición se ignora. Inclúyelo en la respuesta de partes y faltas.

6. **Las pantallas.** Una regla que solo está en la API no protege nada: quien entra por la web pasaría igual. Añade `Gate::authorize()` a los controladores web, con las mismas _Policies_, y usa `@can` para mostrar solo los botones permitidos. A quien no es jefe no se le muestran los ingresos en el detalle de la obra, ni Gastos en el menú.

7. **El calendario.** `GET /calendario` omite los ingresos a quien no puede verlos.

8. **Investiga.** Responde en tu _pull request_:
    - ¿Dónde está mejor comprobar el permiso de aprobar: en la _Policy_, en el _Form Request_ o en la vista? ¿Por qué?
    - Entra como administrativa y pide por URL el detalle de una ficha de Gastos. ¿Qué pasa? ¿Y si quitas `Gate::authorize` y dejas solo el `@can`?
    - ¿Qué devuelve `$user->can('delete', $parte)` para el administrador? ¿Quién lo decide?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque7/RA7_5_*.php tests/Feature/
php artisan test --filter=RA7_5
```

Resultado esperado: `Tests: 25 passed`. Comprueban los perfiles, la primera fila de la tabla, los presupuestos, los partes y las pantallas. El resto de la tabla (obras, líneas, faltas, ingresos, gastos y adjuntos) se comprueba en la [evaluación](./evaluacion.md).

## Git

Rama `RA7_5`. Commits: `feat: roles de usuario y Gate::before`, `feat: policies de todos los modelos`.

---

**Siguiente:** [7.6. Ficheros y bitácoras en la API](./RA7_6_ficheros.md)
