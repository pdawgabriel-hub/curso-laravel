# 7.5. Autenticación con Sanctum

**Referencia teórica:** [Autenticación](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/09_autenticacion.html) · [Por tokens](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/091_autenticacionTokens.html) · [Por sesiones](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/092_autenticacionSesionAPI.html) · [Laravel: Sanctum](https://laravel.com/docs/13.x/sanctum)

Que un cliente externo (una aplicación móvil, otro sistema o un frontend separado) obtenga un token con su email y contraseña y lo use en la cabecera `Authorization: Bearer …`.

## Conceptos clave

- `$user->createToken('nombre')->plainTextToken` crea el token; en la base de datos se guarda solo su hash (`personal_access_tokens`).
- `auth:sanctum` acepta tanto el token Bearer como la sesión de la web (para peticiones del propio frontend con *stateful domains*).
- En los tests, `Sanctum::actingAs($user)` autentica sin token.

## Ejercicios

1. `API\TokenController` con `store` (valida `email` y `password`; si no coinciden, `ValidationException` en `email`; si coinciden, `{ token_type: 'Bearer', access_token }`) y `destroy` (borra `currentAccessToken()` y responde 204).
2. Rutas `POST /api/v1/tokens` (pública) y `DELETE /api/v1/tokens` (autenticada).
3. `GET /api/v1/user` devuelve el usuario y sus `roles` (en [7.6](./RA7_6_autorizacion.md) se completa el cálculo de roles).
4. Probar el flujo completo con `curl`:

   ```bash
   TOKEN=$(curl -s -X POST http://gestion-obras.test/api/v1/tokens \
     -H 'Accept: application/json' -d email=jefe@gestion-obras.test -d password=password | jq -r .access_token)
   curl -s http://gestion-obras.test/api/v1/obras -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' | jq '.meta.total'
   ```

5. **Investiga.** Responde en tu _pull request_: mira la tabla `personal_access_tokens` en phpMyAdmin. ¿Coincide el valor guardado con el que recibiste? ¿Qué pasa si se filtra la base de datos? Autentícate en _Swagger UI_ con *Authorize* y prueba una ruta.

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque7/RA7_5_TokensApiTest.php tests/Feature/
php artisan test --filter=RA7_5
```

Resultado esperado: `Tests: 5 passed`.

## Git

Rama `RA7_5`, commit `feat: emisión y revocación de tokens con Sanctum`.

---

**Siguiente:** [7.6. Autorización](./RA7_6_autorizacion.md)
