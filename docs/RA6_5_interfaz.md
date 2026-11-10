# 6.5. Interfaz con Blade: layout y componentes
<!-- {% raw %} -->

**Referencia teórica:** [Vistas, Blade y layouts](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/RA5_4_vistas.html) · [Laravel: Blade components](https://laravel.com/docs/13.x/blade#components) · [Tailwind CSS](https://tailwindcss.com/docs)

Antes de construir las pantallas de cada entidad, la aplicación cambia de aspecto: menú lateral, cabeceras, tablas, formularios y tarjetas comunes. Cada pantalla de los apartados siguientes se escribe una sola vez, ya con estos componentes.

## Conceptos clave

- **Componente anónimo:** un fichero en `resources/views/components/` que se usa como etiqueta. `components/boton.blade.php` es `<x-boton>`. No necesita clase PHP.
- **`@props`:** declara los atributos del componente y sus valores por defecto. Los atributos que no se declaran llegan en `$attributes` y se añaden al elemento con `{{ $attributes->merge([...]) }}`.
- **Slots:** `{{ $slot }}` es el contenido entre las etiquetas. Un slot con nombre se rellena con `<x-slot:pie>...</x-slot:pie>`.
- **Pasar PHP a un componente:** con dos puntos delante del atributo, `:opciones="$clientes->pluck('nombre_completo', 'id')"`. Sin dos puntos, el valor es un texto.
- **Tailwind CSS** ya viene con Breeze ([5.7](./RA5_7_breeze.md)). Vite compila solo las clases que aparecen en las vistas: tras cambiar una vista, `npm run build`, o `npm run dev` mientras se trabaja.

```blade
<x-cabecera titulo="Clientes" subtitulo="Personas y empresas que encargan obras">
    <x-boton :href="route('clientes.create')">Nuevo cliente</x-boton>
</x-cabecera>

<x-tabla :columnas="['Código', 'Nombre', 'Teléfono', '']">
    @foreach ($clientes as $cliente)
        <tr>
            <td>{{ $cliente->codigo }}</td>
            <td><a href="{{ route('clientes.show', $cliente->id) }}">{{ $cliente->nombre_completo }}</a></td>
            <td>{{ $cliente->telefono }}</td>
            <td class="text-right"><a href="{{ route('clientes.edit', $cliente->id) }}">Editar</a></td>
        </tr>
    @endforeach
    <x-slot:pie>{{ $clientes->links() }}</x-slot:pie>
</x-tabla>
```

## Los componentes

| Componente | Para qué | Atributos |
|:--|:--|:--|
| `<x-layout titulo="…">` | Página completa: menú lateral, usuario y mensajes de la sesión | `titulo` |
| `<x-cabecera>` | Título `<h1>` de la página y sus botones | `titulo`, `subtitulo` |
| `<x-boton>` | Enlace (con `href`) o botón | `href`, `variante` (`primario`, `secundario`, `peligro`), `type` |
| `<x-tabla>` | Tabla con cabecera; sin filas, muestra un mensaje | `columnas`, `vacio`; slot `pie` |
| `<x-tarjeta>` | Bloque blanco con título | `titulo`; slot `acciones` |
| `<x-pestanas>` | Fila de pestañas: cada una es un enlace a la misma página con `?pestana=clave` | `pestanas` (`clave => texto`), `activa` |
| `<x-dato>` | Par etiqueta/valor dentro de un `<dl>` | `etiqueta` |
| `<x-insignia>` | Estado en una pastilla de color | `color` (`gris`, `verde`, `ambar`, `rojo`, `azul`) |
| `<x-kpi>` | Indicador numérico grande | `etiqueta`, `tono` (`neutro`, `positivo`, `negativo`) |
| `<x-progreso>` | Barra de porcentaje | `porcentaje` |
| `<x-formulario>` | Formulario con `@csrf`, método y botones | `action`, `metodo`, `boton`, `cancelar`, `ficheros` |
| `<x-campo>` | `<input>` con etiqueta, valor anterior y error | `name`, `etiqueta`, `type`, `valor`, `ancho` |
| `<x-area>` | `<textarea>` | `name`, `etiqueta`, `valor`, `filas` |
| `<x-selector>` | `<select>` | `name`, `etiqueta`, `opciones`, `valor`, `vacio` |
| `<x-casilla>` | Casilla de sí o no (envía `0` si no se marca) | `name`, `etiqueta`, `marcado` |
| `<x-borrar>` | Botón de borrar que pide confirmación | `action`, `pregunta`, `texto`, `discreto` |
| `<x-alerta>` | Mensaje de éxito o de error | `tipo` (`exito`, `error`) |

## Qué comprueban los tests en las vistas

Los tests de este bloque no miran colores ni clases, sino la estructura. Los componentes ya la cumplen; si escribes HTML a mano, debe cumplirla igual:

| Elemento | Marca que se comprueba |
|:--|:--|
| Menú | `<nav aria-label="Principal">`, y la entrada de la sección actual con `aria-current="page"` |
| Título | Un `<h1>` con el título de la página |
| Mensajes | Éxito con `role="status"`; error con `role="alert"` |
| Pestañas | `<nav aria-label="Pestañas">`, un enlace por pestaña con `pestana=clave` en la URL, y la activa con `aria-current="page"` |
| Tablas | Encabezados `<th scope="col">` con los nombres de columna que pide cada ejercicio, y un texto que empiece por `No hay` cuando no hay filas |
| Formularios | Tras un error de validación, el valor enviado sigue en el campo, el campo lleva `aria-invalid="true"` y el mensaje aparece en la página |
| Borrar | Formulario con `_method` `DELETE` y `onsubmit="return confirm(…)"` |

## Ejercicios

1. **Los componentes.** Desde el `workspace`, copia el layout y los componentes:

    ```bash
    cp /var/www/curso-laravel/docs/materiales/plantillas/RA6_5/resources/views/components/*.blade.php resources/views/components/
    ```

    El nuevo `layout.blade.php` sustituye al de [5.4](./RA5_4_vistas.md). Como todas las vistas usan ya `<x-layout>`, la aplicación entera cambia de aspecto. Compila los estilos y compruébalo en el navegador:

    ```bash
    npm run build
    ```

2. **Lee los componentes.** Abre `tabla.blade.php`, `campo.blade.php` y `borrar.blade.php` y localiza en cada uno: los `@props`, dónde se usa `$slot` y dónde se usa `$attributes`.

3. **Las vistas del Bloque 5.** Cambia las vistas de clientes, obras y trabajadores para que usen `<x-cabecera>`, `<x-tabla>`, `<x-tarjeta>` con `<x-dato>`, y `<x-formulario>` con `<x-campo>`. Los datos siguen saliendo de los arrays y del repositorio: en [6.6](./RA6_6_maestros.md) pasarán a la base de datos.

4. **El nombre de la empresa.** El layout muestra `config('gestionobras.empresa.nombre')` ([5.2](./RA5_2_estructura.md)). Comprueba que aparece en el menú y en el título de la pestaña.

5. **Investiga.** Responde en tu _pull request_:
    - En `campo.blade.php`, ¿por qué se convierte `lineas[0][horas]` en `lineas.0.horas` antes de llamar a `old()` y a `$errors->has()`?
    - Sin volver a ejecutar `npm run build`, añade a una vista una clase de Tailwind que no se use en ningún otro sitio (`bg-fuchsia-300`) y recarga. ¿Se aplica? ¿Por qué?
    - ¿Qué ventaja tiene que el menú use `aria-current="page"` en lugar de solo una clase de color?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_5_InterfazTest.php tests/Feature/
php artisan test --filter=RA6_5
```

Resultado esperado: `Tests: 10 passed`.

## Git

Rama `RA6_5`. Commits: `feat: layout con menú lateral y componentes Blade`, `refactor: vistas del bloque 5 con los componentes`.

---

**Siguiente:** [6.6. Ejercicios: los maestros en la base de datos](./RA6_6_maestros.md)
<!-- {% endraw %} -->
