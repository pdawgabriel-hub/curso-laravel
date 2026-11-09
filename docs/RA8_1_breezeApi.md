# 8.1. Una rama sin vistas

**Referencia teórica:** [Breeze API](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/062_esqueleto_API.html) · [De Blade a React](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/064_inertia_reactadmin.html)

En este bloque, la interfaz pasa del servidor al navegador: el servidor **solo envía datos**, por la API del Bloque 7, y una aplicación _React-Admin_ escrita en _TypeScript_ los muestra. Como es otra forma de construir la misma aplicación, se hace en una **rama aparte** que no se fusiona con `main`: `main` conserva la interfaz _Blade_ y sigue con el Bloque 9.

## Conceptos clave

- **Servidor de datos y cliente:** hasta ahora, Laravel construía cada página HTML (_Blade_). A partir de aquí, el navegador descarga una aplicación _JavaScript_ una sola vez (una _SPA_) y pide a la API solo los datos que necesita.
- **Una rama de larga duración:** `react-admin` sale de `main` cuando el Bloque 7 está terminado. Si más adelante se corrige algo de la API en `main`, se trae a la rama con `git merge main`.
- En los apuntes de referencia, el servidor sin vistas se consigue con `php artisan breeze:install api`. Aquí no hace falta: en [8.2](./RA8_2_reactAdmin.md) se instala Breeze con React, que sustituye a la vez las vistas de inicio de sesión y `routes/web.php`. En este apartado solo se retiran las pantallas propias de la aplicación.

## Ejercicios

1. **La rama.** Con el Bloque 7 fusionado en `main`:

    ```bash
    git switch main && git pull
    git switch -c react-admin
    ```

2. **Fuera las pantallas Blade de la aplicación.** Borra lo que construiste en los apartados [6.5](./RA6_5_interfaz.md) a [6.11](./RA6_11_partes.md) y [6.14](./RA6_14_bitacoras.md) para la web:
    - Los controladores web (`ClientesController`, `ObrasController`…) y sus _Requests_ de formulario que solo use la web.
    - Las vistas `resources/views/{clientes,trabajadores,proveedores,especialistas,presupuestos,obras,gastos,faltas}` y los componentes de [6.5](./RA6_5_interfaz.md) (`resources/views/components/{layout,tabla,campo,…}.blade.php`).
    - Los ficheros de rutas web de los módulos (`routes/clientes.php`…) y sus `require` en `routes/web.php`.

    Los modelos, los servicios, las _Policies_ y la API **no se tocan**.

3. **Fuera sus tests.** Ya no hay pantallas que comprobar:

    ```bash
    git rm tests/Feature/RA6_{5,6,7,9,10,11,15}_*.php tests/Feature/RA7_6_AutorizacionWebTest.php
    ```

4. **Comprueba que la API sigue entera.**

    ```bash
    php artisan route:list --path=api
    php artisan test
    ```

5. **Investiga.** Responde en tu _pull request_: compara el `.gitignore`, `composer.json` y `package.json` de `main` y de la rama con `git diff main -- composer.json package.json`. ¿Qué cambiará en el siguiente apartado y qué se mantiene igual?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque8/RA8_1_SinVistasTest.php tests/Feature/
php artisan test --filter=RA8_1
```

Resultado esperado: `Tests: 2 passed`, y el resto de la batería, incluida la [evaluación](./evaluacion.md), en verde.

## Git

Rama `react-admin`, commit `refactor: el servidor solo envía datos`. Esta rama no se fusiona con `main`: súbela con `git push -u origin react-admin`.

---

**Siguiente:** [8.2. React-Admin con Inertia y TypeScript](./RA8_2_reactAdmin.md)
