# 8.4. dataProvider y authProvider
<!-- {% raw %} -->

**Referencia teórica:** [React-Admin](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/063_React_Admin.html) · [Autenticación por tokens](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/091_autenticacionTokens.html) · [React-Admin: Data Providers](https://marmelab.com/react-admin/DataProviders.html) · [Auth Providers](https://marmelab.com/react-admin/AuthProviderWriting.html) · [Vitest](https://vitest.dev/guide/)

React-Admin no hace peticiones por su cuenta: se las pide a dos objetos. El **_dataProvider_** traduce `getList`, `getOne`, `create`, `update` y `delete` a peticiones HTTP, y el **_authProvider_** inicia y cierra la sesión. Los dos se escriben en _TypeScript_ y se prueban con **Vitest**, sin navegador.

## Conceptos clave

- `jsonServerProvider(apiUrl, httpClient)` ya sabe hablar con una API como la que deja el middleware de [8.3](./RA8_3_middleware.md). Se reutiliza y se sustituyen solo los métodos que no encajan, con la sintaxis de propagación `{ ...base, getOne: … }`.
- `httpClient` es la función por la que pasan **todas** las peticiones: es el sitio para añadir la cabecera `X-Cliente` y el token.
- **Recursos anidados:** en la API, un ingreso vive en `/obras/{obra}/ingresos`, pero React-Admin solo conoce el recurso `ingresos`. El _dataProvider_ guarda en una tabla qué recursos cuelgan de qué padre y por qué campo (`obra_id`), y construye la URL a partir del registro.
- **El tipo `DataProvider`** obliga a que cada método reciba y devuelva lo que React-Admin espera: si `getList` no devuelve `total`, `type-check` lo detecta.
- **El token** de [7.5](./RA7_5_sanctum.md) se guarda en `localStorage` al iniciar sesión y se envía en cada petición como `Authorization: Bearer …`.
- **Vitest** ejecuta funciones de _TypeScript_ en Node. Para no depender del servidor, los tests sustituyen `fetch` por una función falsa (`vi.stubGlobal`) y comprueban qué URL se habría pedido.

## Ejercicios

1. **Vitest.** Añade a `devDependencies` `vitest` `^5.1.0`, `jsdom`, `@testing-library/react`, `@testing-library/dom` y `@types/node` `^22.0.0`, y el script `"test": "vitest run"`. Copia la configuración:

    ```bash
    cp /var/www/curso-laravel/docs/materiales/plantillas/RA8_4/vitest.config.ts .
    npm install
    ```

    ```ts
    import { defineConfig } from 'vitest/config';
    import react from '@vitejs/plugin-react';
    import { fileURLToPath } from 'node:url';

    export default defineConfig({
        plugins: [react()],
        resolve: {
            alias: { '@': fileURLToPath(new URL('./resources/js', import.meta.url)) },
        },
        test: {
            environment: 'jsdom',
            include: ['resources/js/**/*.test.{ts,tsx}'],
            env: { VITE_API_URL: 'http://gestion-obras.test/api/v1' },
        },
    });
    ```

2. **El _dataProvider_.** Sustituye `resources/js/react-admin/dataProvider.ts`:

    ```ts
    import jsonServerProvider from 'ra-data-json-server';
    import { DataProvider, fetchUtils } from 'react-admin';

    export const apiUrl = import.meta.env.VITE_API_URL;
    export const CLAVE_TOKEN = 'gestion-obras.token';

    /** Todas las peticiones llevan la cabecera que activa el middleware ReactAdminResponse y, si lo hay, el token. */
    export const httpClient = (url: string, options: fetchUtils.Options = {}) => {
        const headers = new Headers(options.headers);
        headers.set('Accept', 'application/json');
        headers.set('X-Cliente', 'react-admin');
        const token = localStorage.getItem(CLAVE_TOKEN);
        if (token) {
            headers.set('Authorization', `Bearer ${token}`);
        }
        return fetchUtils.fetchJson(url, { ...options, headers });
    };

    /** Recursos que en la API cuelgan de un padre: /obras/{obra_id}/ingresos… */
    export interface Anidado {
        padre: string;
        clave: string;
        ruta: string;
    }

    export const anidados: Record<string, Anidado> = {
        ingresos: { padre: 'obras', clave: 'obra_id', ruta: 'ingresos' },
        'partes-trabajo': { padre: 'obras', clave: 'obra_id', ruta: 'partes-trabajo' },
        'partes-proveedor': { padre: 'obras', clave: 'obra_id', ruta: 'partes-proveedor' },
        'partes-especialista': { padre: 'obras', clave: 'obra_id', ruta: 'partes-especialista' },
        faltas: { padre: 'trabajadores', clave: 'trabajador_id', ruta: 'faltas' },
        'lineas-presupuesto': { padre: 'presupuestos', clave: 'presupuesto_id', ruta: 'lineas' },
        'lineas-parte': { padre: 'partes-trabajo', clave: 'parte_trabajo_id', ruta: 'lineas' },
    };

    /** URL de la colección de un recurso; si es anidado, necesita el id de su padre. */
    export const urlColeccion = (resource: string, padreId?: unknown): string => {
        const anidado = anidados[resource];
        if (!anidado) {
            return `${apiUrl}/${resource}`;
        }
        if (padreId === undefined || padreId === null || padreId === '') {
            throw new Error(`«${resource}» necesita ${anidado.clave}`);
        }
        return `${apiUrl}/${anidado.padre}/${padreId}/${anidado.ruta}`;
    };

    const base = jsonServerProvider(apiUrl, httpClient);

    export const dataProvider: DataProvider = {
        ...base,

        getOne: (resource, params) => {
            if (anidados[resource]) {
                return Promise.reject(new Error(`«${resource}» solo se consulta desde su ${anidados[resource].padre}`));
            }
            return base.getOne(resource, params);
        },

        // La API no filtra por una lista de ids: se pide cada registro.
        getMany: (resource, params) =>
            Promise.all(params.ids.map((id) => httpClient(`${urlColeccion(resource)}/${id}`).then(({ json }) => json)))
                .then((data) => ({ data })),

        getManyReference: (resource, params) => {
            const anidado = anidados[resource];
            if (!anidado || params.target !== anidado.clave) {
                return base.getManyReference(resource, params);
            }
            const { page, perPage } = params.pagination;
            const { field, order } = params.sort;
            const query = new URLSearchParams({
                ...params.filter,
                _start: String((page - 1) * perPage),
                _end: String(page * perPage),
                _sort: field,
                _order: order,
            });
            return httpClient(`${urlColeccion(resource, params.id)}?${query}`).then(({ headers, json }) => ({
                data: json,
                total: parseInt(headers.get('X-Total-Count') ?? '0', 10),
            }));
        },

        // create y update: ejercicio 3.

        // La API responde 204 sin cuerpo: se devuelve el registro que se ha borrado.
        delete: (resource, params) => {
            const anidado = anidados[resource];
            const url = anidado ? urlColeccion(resource, params.previousData?.[anidado.clave]) : urlColeccion(resource);
            return httpClient(`${url}/${params.id}`, { method: 'DELETE' }).then(() => ({ data: params.previousData as any }));
        },

        deleteMany: (resource, params) =>
            Promise.all(params.ids.map((id) => httpClient(`${urlColeccion(resource)}/${id}`, { method: 'DELETE' }))).then(() => ({ data: params.ids })),
    };
    ```

3. **Alta y modificación de los anidados.** Completa `create` y `update` siguiendo el modelo de `getManyReference` y `delete`:
    - Si el recurso no es anidado, se usa el de `base`.
    - Si lo es, `create` envía un `POST` a `urlColeccion(resource, params.data[clave])` con `JSON.stringify(params.data)`, y `update`, un `PUT` a `urlColeccion(resource, params.previousData[clave])/id`.
    - Los dos devuelven `{ data: json }`.

4. **El _authProvider_.** Crea `resources/js/react-admin/authProvider.ts`:

    ```ts
    import { AuthProvider } from 'react-admin';
    import { apiUrl, CLAVE_TOKEN, httpClient } from './dataProvider';

    /** Inicio de sesión con los tokens de Sanctum del apartado 7.5. */
    export const authProvider: AuthProvider = {
        login: async ({ username, password }) => {
            const { json } = await httpClient(`${apiUrl}/tokens`, {
                method: 'POST',
                body: JSON.stringify({ email: username, password }),
            });
            localStorage.setItem(CLAVE_TOKEN, json.access_token);
        },

        checkAuth: async () => {
            if (!localStorage.getItem(CLAVE_TOKEN)) {
                throw new Error('Inicia sesión');
            }
        },

        // logout, checkError, getIdentity y getPermissions: ejercicio 5.
    };
    ```

5. **Completa el _authProvider_.**
    - `logout`: si hay token, `DELETE /tokens` (aunque falle, se sigue), y se borra el token de `localStorage`.
    - `checkError`: con un error `401`, borra el token y rechaza la promesa, para que React-Admin vuelva al inicio de sesión. Con cualquier otro error, como un `403`, no hace nada.
    - `getIdentity`: `GET /user` y devuelve `{ id, fullName: name }`.
    - `getPermissions`: `GET /user` y devuelve sus `roles`.

6. **Conéctalos.** En `App.tsx`: `<Admin layout={Layout} dataProvider={dataProvider} authProvider={authProvider} requireAuth>`. Entra en `/dashboard`: React-Admin pide usuario y contraseña (los de [6.7](./RA6_7_usuarios.md)) y el listado de clientes ya muestra datos.

7. **Investiga.** Responde en tu _pull request_:
    - ¿Por qué `getMany` pide los registros de uno en uno? ¿Qué cambiarías en la API para hacerlo con una sola petición?
    - ¿Por qué no se puede pedir un ingreso suelto (`getOne('ingresos')`) y sí una lista de ingresos de una obra?
    - Guardar el token en `localStorage` es cómodo, pero cualquier _script_ de la página puede leerlo. ¿Qué ofrece a cambio la sesión de la referencia teórica ([7.8](./RA7_8_opcional.md))?

## Comprueba tu solución automáticamente

```bash
mkdir -p resources/js/react-admin/__tests__
cp /var/www/curso-laravel/docs/materiales/tests/bloque8/{dataProvider,authProvider}.test.ts resources/js/react-admin/__tests__/
npm test
npm run type-check
```

Resultado esperado: `Tests 12 passed`, y `type-check` sin errores.

## Git

Rama `react-admin`. Commits: `feat: dataProvider para la API`, `feat: inicio de sesión con token`, `test: Vitest`.

---

**Siguiente:** [8.5. Los recursos](./RA8_5_recursos.md)
<!-- {% endraw %} -->
