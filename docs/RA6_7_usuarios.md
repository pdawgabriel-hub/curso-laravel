# 6.7. Usuarios: rol, administrador y semilla

**Referencia teórica:** [Usuarios: migración, registro y seeder](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/052_ejerciciosUsers.html) · [Laravel: Configuration](https://laravel.com/docs/13.x/configuration#environment-configuration)

La tabla `users` la creó Breeze en [5.7](./RA5_7_breeze.md). Ahora cada usuario tiene un **rol**, `administrativa` o `jefe`, y hay un **administrador** identificado por su correo. Los permisos de cada rol se aplican en el Bloque 7; aquí se preparan los datos.

## Conceptos clave

- **Modificar una tabla existente:** una migración con `--table=users` y `Schema::table()` en lugar de `Schema::create()`. Su `down()` deshace el cambio con `dropColumn()`.
- **Configuración desde el entorno:** un valor que cambia entre instalaciones (el correo del administrador) va en `.env`, se lee **solo** desde `config/` con `env()`, y el resto de la aplicación usa `config('app.admin.email')`. `.env` no se sube al repositorio; `.env.example` sí, con la clave y un valor de ejemplo.
- Un usuario sin rol puede iniciar sesión, pero en el Bloque 7 no podrá hacer nada salvo ver sus propios datos.

## Ejercicios

1. **El rol.**

    ```bash
    php artisan make:migration add_rol_to_users_table --table=users
    ```

    Campo | Tipo | nullable
    -----|----|---
    rol | enum: `administrativa`, `jefe`, después de `email` | sí

    El `down()` elimina la columna. Añade `rol` al `$fillable` del modelo `User`. `UserFactory` no lo rellena: un usuario de la _factory_ no tiene rol salvo que se indique (`User::factory()->create(['rol' => 'jefe'])`).

2. **El correo del administrador.** En `config/app.php`:

    ```php
    'admin' => ['email' => env('ADMIN_EMAIL')],
    ```

    Y `ADMIN_EMAIL=admin@gestion-obras.test` en `.env` y en `.env.example`.

3. **La semilla de usuarios.** `UsersTableSeeder` crea tres usuarios con la _factory_:

    Nombre | Correo | Rol
    -----|-----|----
    `Administrador` | `config('app.admin.email')` | ninguno
    `Jefe` | `jefe@gestion-obras.test` | `jefe`
    `Administrativa` | `administrativa@gestion-obras.test` | `administrativa`

    `DatabaseSeeder` la llama **la primera**. La contraseña de los tres es la de `UserFactory` (`password`).

4. **El rol en el menú.** El layout de [6.5](./RA6_5_interfaz.md) ya muestra, bajo el nombre del usuario, su rol con la primera letra en mayúscula. Comprueba con `php artisan migrate:fresh --seed` que, al entrar como `jefe@gestion-obras.test`, aparece *Jefe*.

5. **Investiga.** Responde en tu _pull request_:
    - Ejecuta `php artisan config:cache` y cambia después `ADMIN_EMAIL` en `.env`. ¿Qué devuelve `config('app.admin.email')` en `tinker`? ¿Y tras `php artisan config:clear`? ¿Por qué `env()` solo debe usarse dentro de `config/`?
    - ¿Por qué el administrador se identifica por su correo y no con un tercer valor del `enum`?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_7_UsuariosTest.php tests/Feature/
php artisan test --filter=RA6_7
```

Resultado esperado: `Tests: 5 passed`.

## Git

Rama `RA6_7`. Commits: `feat: rol de los usuarios`, `feat: semilla de usuarios`.

---

**Siguiente:** [6.8. Relaciones entre modelos](./RA6_8_relaciones.md)
