# 8.2. React-Admin con Inertia y TypeScript
<!-- {% raw %} -->

**Referencia teórica:** [React-Admin](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/063_React_Admin.html) · [De Blade a React](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/064_inertia_reactadmin.html) · [React-Admin: Tutorial](https://marmelab.com/react-admin/Tutorial.html) · [TypeScript para programadores de JavaScript](https://www.typescriptlang.org/docs/handbook/typescript-in-5-minutes.html)

Breeze con React e Inertia sirve las páginas de inicio de sesión y, en `/dashboard`, monta una aplicación _React-Admin_ que habla con la API. Todo el código del cliente se escribe en _TypeScript_.

## Conceptos clave

- **Inertia** une Laravel y React sin escribir una API para las páginas: una ruta devuelve `Inertia::render('Dashboard')` y el navegador pinta el componente `resources/js/Pages/Dashboard.tsx`.
- **React-Admin** es un _framework_ para paneles de gestión. Un `<Admin>` contiene un `<Resource>` por entidad, con su listado, su ficha, su alta y su edición. Usa su propio enrutador: las pantallas quedan en `/dashboard#/clientes`, `/dashboard#/clientes/3/show`…
- **TypeScript** es JavaScript con tipos. `tsc --noEmit` comprueba los tipos sin generar nada; Vite compila los `.tsx` igual que los `.jsx`. Los errores de tipos aparecen antes de abrir el navegador.
- **Variables de entorno en el cliente:** solo llegan a Vite las que empiezan por `VITE_`, y se leen con `import.meta.env.VITE_API_URL`.

## Ejercicios

1. **Breeze con React, Inertia y TypeScript.**

    ```bash
    php artisan breeze:install react --typescript
    ```

    Como en la referencia teórica, Breeze sobrescribe ficheros que ya tenías. Revisa `git diff` antes de seguir:
    - `routes/web.php`: queda el de Breeze, con `/`, `/dashboard` y el perfil. Es lo que se busca.
    - **`app/Providers/AppServiceProvider.php`: recupera el tuyo**, porque Breeze lo sustituye y se pierden `Gate::before` ([7.6](./RA7_6_autorizacion.md)) y el mapa polimórfico ([6.14](./RA6_14_bitacoras.md)). Después, añade a su `boot()` la línea de Breeze `Vite::prefetch(concurrency: 3);`:

        ```bash
        git restore app/Providers/AppServiceProvider.php
        ```

    - En `resources/js/app.tsx`, quita la línea `import './bootstrap';`: el esqueleto de Laravel 13 ya no trae ese fichero.
    - Borra las vistas _Blade_ que deja la instalación anterior: `resources/views/{auth,layouts,profile,components}` y `resources/views/dashboard.blade.php`. **No** borres `resources/views/vendor`, donde está _Swagger_.

2. **La aplicación React-Admin.** Como en la referencia teórica, se genera en una carpeta aparte y se trae al proyecto. Desde el `workspace`, fuera del proyecto:

    ```bash
    cd /var/www
    npm create react-admin@latest gestion-ra -- --data-provider json-server --auth-provider none --resource clientes --install skip
    ```

3. **Trasladar el código.**
    - Copia `gestion-ra/src` a `resources/js/react-admin` (sin `index.tsx` ni `vite-env.d.ts`).
    - En `resources/js/react-admin/Layout.tsx`, quita `<CheckForApplicationUpdate />`, que comprueba si hay una versión nueva de una aplicación independiente.
    - **Mezcla `package.json`:** añade a `dependencies` las de `gestion-ra/package.json` (`react-admin`, `ra-data-json-server`, `@mui/material`, `@mui/icons-material`, `@emotion/react`, `@emotion/styled`, `react-router` y `react-router-dom`). En `devDependencies`, usa sus versiones de React: `react` y `react-dom` `^19.0.0`, `@types/react` y `@types/react-dom` `^19.0.0`, y `@vitejs/plugin-react` `^6.0.5` (la de Breeze no admite Vite 8). Añade el script `"type-check": "tsc --noEmit"`.
    - En `resources/js/types/vite-env.d.ts`, declara la variable de la API:

        ```ts
        /// <reference types="vite/client" />

        interface ImportMetaEnv {
            readonly VITE_APP_NAME: string;
            readonly VITE_API_URL: string;
        }

        interface ImportMeta {
            readonly env: ImportMetaEnv;
        }
        ```

    - En `.env` y `.env.example`: `VITE_API_URL="${APP_URL}/api/v1"`.

4. **Montarla en `/dashboard`.** `resources/js/Pages/Dashboard.tsx`:

    ```tsx
    import { Head } from '@inertiajs/react';
    import { StrictMode } from 'react';
    import { App } from '../react-admin/App';

    export default function Dashboard() {
        return (
            <StrictMode>
                <Head title="Gestión de obras" />
                <App />
            </StrictMode>
        );
    }
    ```

    En `resources/js/react-admin/dataProvider.ts`, cambia `VITE_JSON_SERVER_URL` por `VITE_API_URL`.

5. **Compilar.**

    ```bash
    npm install
    npm run type-check
    npm run dev
    ```

    Entra en <http://gestion-obras.test/login>. Tras iniciar sesión, `/dashboard` muestra React-Admin con el recurso `clientes`, aunque todavía no recibe datos: la API responde 401 porque nadie ha iniciado sesión en ella, y su formato no es el que espera React-Admin. Lo resuelven [8.3](./RA8_3_middleware.md) y [8.4](./RA8_4_proveedores.md).

6. **Investiga.** Responde en tu _pull request_:
    - Escribe en `App.tsx` `const n: number = 'hola';` y ejecuta `npm run type-check`. ¿Qué ocurre? ¿Y con `npm run dev`? ¿Por qué conviene ejecutar los dos?
    - Abre las herramientas de desarrollo del navegador en `/dashboard#/clientes`. ¿Qué peticiones hace React-Admin y qué espera recibir?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque8/RA8_2_ReactAdminTest.php tests/Feature/
php artisan test --filter=RA8_2
npm run type-check
```

Resultado esperado: `Tests: 3 passed`, y `type-check` sin errores. Breeze también ha dejado sus propios tests en `tests/Feature/Auth`: deben pasar.

## Git

Rama `react-admin`. Commits: `feat: Breeze con React, Inertia y TypeScript`, `feat: React-Admin en el panel`.

---

**Siguiente:** [8.3. Middleware para React-Admin](./RA8_3_middleware.md)
<!-- {% endraw %} -->
