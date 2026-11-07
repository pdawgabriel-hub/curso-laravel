# 7.4. Autorización: Gates, Policies y roles

**Referencia teórica:** [Autorización](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/10_autorizacion.html) · [Gates](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/101_gates.html) · [Policies](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/102_policies.html) · [Roles y permisos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/103_roles.html) · [Laravel: Authorization](https://laravel.com/docs/13.x/authorization)

Reproducir los grupos de Odoo: *Administrativa* y *Jefe / Administrador* con los mismos permisos, un administrador técnico con acceso total y ningún acceso para los usuarios sin rol.

## Conceptos clave

- `Gate::before(fn (User $user) => $user->esAdministrador() ? true : null)`: devolver `null` deja que decida la Policy.
- Una **Policy** por modelo (`php artisan make:policy ClientePolicy --model=Cliente`), descubierta automáticamente por su nombre.
- `Gate::authorize('update', $cliente)` lanza 403 si la Policy devuelve `false`.
- Cuando varias Policies son iguales, una clase base común evita repetir código.
- Una Policy también puede decidir según **quién** es el usuario y **de quién** es el registro: `$observacion->user_id === $user->id`.
- Primero se autoriza y después se valida: un usuario sin permiso recibe 403, no los errores de validación.

## Ejercicios

1. En `User`: `esAdministrador()` (email igual a `config('app.admin.email')`), `tieneRol()` y `roles()` (`['administrador']`, `[$rol]` o `[]`), y `rol` en el `$fillable`.
2. `Gate::before` en `AppServiceProvider::boot()`.
3. `App\Policies\PolicyBase` con `viewAny`, `view`, `create`, `update` y `delete` que devuelven `$user->tieneRol()`, y una Policy por modelo que hereda de ella.
4. Todos los métodos de los controladores de la API llaman a `Gate::authorize()` con la acción y el modelo que corresponde.
5. `ObservacionPolicy` e `IncidenciaPolicy` sobrescriben `delete`: solo su autor o el rol `jefe` (el administrador ya pasa por `Gate::before`). Añadir también `GET /user/observaciones`, con las observaciones del usuario autenticado.
6. La web también usa las Policies: los botones de crear, editar y borrar solo se muestran con `@can`.

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque7/RA7_4_AutorizacionApiTest.php tests/Feature/
php artisan test --filter=RA7_4
```

Resultado esperado: `Tests: 7 passed`.

## Git

Rama `RA7_4`. Commits: `feat: roles de usuario y Gate::before`, `feat: policies de todos los modelos`.

---

**Siguiente:** [7.5. Ficheros y bitácoras en la API](./RA7_5_ficheros.md)
