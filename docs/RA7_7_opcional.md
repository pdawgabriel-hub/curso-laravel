# 7.7. Opcional: _PHP-CRUD-API_ y sesiones en la API

**Referencia teórica:** [Crear una CRUD API](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/065_PHP_CRUD_API.html) · [Autenticación con sesiones en API](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/092_autenticacionSesionAPI.html)

Dos temas de los apuntes del módulo que este proyecto no necesita. Se pueden practicar en una rama aparte.

> **Aviso.** Hazlos en una rama que **no** vayas a fusionar. Los dos cambian piezas de las que dependen los tests de este bloque.

## _PHP-CRUD-API_

Es un script que genera una API REST sobre todas las tablas, sin programar controladores. Es útil para probar un _frontend_ rápidamente, pero se salta las validaciones, las _Policies_ y las reglas de negocio: un usuario podría crear un ingreso de otro cliente o borrar una ficha de Gastos.

```bash
git switch -c prueba-crud-api
composer require symfony/psr-http-message-bridge laminas/laminas-diactoros mevdschee/php-crud-api
```

Sigue la referencia teórica para añadir la ruta `Route::any('/{any}', …)` al final de `routes/api.php`.

**Investiga.** Pide `GET /api/records/gastos` y crea un registro con `POST`. ¿Qué regla de la aplicación te has saltado? ¿Por qué no se usaría esto en producción?

## Sesiones en la API

Hasta ahora, un cliente externo se identifica con un _token_ ([7.4](./RA7_4_sanctum.md)). Si la API la usa una página de la misma aplicación, puede aprovechar la sesión de quien ya ha iniciado sesión: así funciona, por ejemplo, un _frontend_ como el del Bloque 8.

**Investiga.** Sigue la referencia teórica en la rama de prueba. ¿Qué cambia en la petición? ¿Por qué hace falta proteger también la petición contra CSRF?

---

**Siguiente:** [8.1. _React-Admin_ sobre la API](./RA8_1_reactAdmin.md)
