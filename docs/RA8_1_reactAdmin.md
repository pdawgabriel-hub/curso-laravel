# 8.1. React-Admin sobre la API

**Referencia teórica:** [React-Admin](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/063_React_Admin.html) · [React-Admin sobre Inertia](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/064_inertia_reactadmin.html) · [Personalizar las tablas](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/066_componentesRA.html) · [React-Admin: Data Providers](https://marmelab.com/react-admin/DataProviders.html)

Un panel React-Admin en `/admin` que lista, crea, edita y borra clientes, obras y presupuestos a través de `/api/v1`.

## Conceptos clave

- React-Admin habla con el backend mediante un **data provider**: traduce `getList`, `getOne`, `create`, `update` y `delete` a peticiones HTTP.
- La API de este proyecto pagina con `page`/`per_page` y devuelve `data` + `meta.total`; el data provider debe convertir los parámetros de React-Admin (`pagination`, `sort`, `filter`) a `page`, `per_page`, `sort`, `order` y `search`.
- La autenticación puede reutilizar la sesión de la web (Sanctum *stateful*) o pedir un token con `POST /tokens` desde un *auth provider*.

## Ejercicios

1. Montar React-Admin con Vite en la propia aplicación (o con Inertia, como en la referencia teórica) y servirlo en `/admin`.
2. Escribir el data provider para el formato de la API: listados paginados, recursos anidados (`obras/{id}/ingresos`) y errores 422 mostrados en los campos.
3. Recursos `clientes`, `obras` (con los indicadores financieros en el listado) y `presupuestos` (con sus líneas en el formulario de edición).
4. *Auth provider* con `POST /tokens`, `DELETE /tokens` y `GET /user` para mostrar el nombre y ocultar acciones a los usuarios sin rol.

## Comprobación

Manual en el navegador: el listado de obras pagina y ordena, un alta con errores marca los campos en rojo y un usuario sin rol no ve los datos.

## Git

Rama `RA8_1`, tag `bloque-8`.

---

**Siguiente:** [9.1. Documentos PDF](./RA9_1_pdf.md)
