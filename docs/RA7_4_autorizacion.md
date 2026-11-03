---
title: 7.4. Autorización
parent: Bloque 7. Servicios web
nav_order: 4
---

# 7.4. Autorización: Gates, Policies y roles
{: .no_toc }

{: .nota }
> **Referencia teórica:** [Autorización](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/10_autorizacion.html) · [Gates](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/101_gates.html) · [Policies](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/102_policies.html) · [Roles y permisos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/103_roles.html) · [Laravel: Authorization](https://laravel.com/docs/13.x/authorization)

1. TOC
{:toc}

## Objetivo

Reproducir los grupos de Odoo: *Administrativa* y *Jefe / Administrador* con los mismos permisos, un administrador técnico con acceso total y ningún acceso para los usuarios sin rol.

## Conceptos clave

- `Gate::before(fn (User $user) => $user->esAdministrador() ? true : null)`: devolver `null` deja que decida la Policy.
- Una **Policy** por modelo (`php artisan make:policy ClientePolicy --model=Cliente`), descubierta automáticamente por su nombre.
- `Gate::authorize('update', $cliente)` lanza 403 si la Policy devuelve `false`.
- Cuando varias Policies son iguales, una clase base común evita repetir código.

## Ejercicios

1. En `User`: `esAdministrador()` (email igual a `config('app.admin.email')`), `tieneRol()` y `roles()` (`['administrador']`, `[$rol]` o `[]`), y `rol` en el `$fillable`.
2. `Gate::before` en `AppServiceProvider::boot()`.
3. `App\Policies\PolicyBase` con `viewAny`, `view`, `create`, `update` y `delete` que devuelven `$user->tieneRol()`, y una Policy por modelo que hereda de ella.
4. Todos los métodos de los controladores de la API llaman a `Gate::authorize()` con la acción y el modelo que corresponde.
5. La web también usa las Policies: los botones de crear, editar y borrar solo se muestran con `@can`.

## Comprobación

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque7/RA7_4_AutorizacionApiTest.php tests/Feature/
php artisan test --filter=RA7_4
```

Resultado esperado: `Tests: 7 passed`.

## Git

Rama `RA7_4`. Commits: `feat: roles de usuario y Gate::before`, `feat: policies de todos los modelos`.
