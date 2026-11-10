# 8.5. Los recursos
<!-- {% raw %} -->

**Referencia teórica:** [Personalizar las tablas de React-Admin](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/066_componentesRA.html) · [React-Admin: List](https://marmelab.com/react-admin/List.html) · [Show](https://marmelab.com/react-admin/Show.html) · [Edit y Create](https://marmelab.com/react-admin/EditTutorial.html) · [Reference fields](https://marmelab.com/react-admin/ReferenceField.html)

Cada entidad tiene su fichero en `resources/js/react-admin/recursos/`, con su listado, su ficha, su alta y su edición, como `proyectos.jsx` en la referencia teórica. En este apartado se hacen **todas**, a partir del ejemplo de clientes.

## Conceptos clave

- **Listado:** `<List>` pide los datos y `<Datagrid>` los pinta; cada `<TextField source="…">` es una columna. `filters` añade el buscador (`<SearchInput source="q">`, que el middleware convierte en `search`).
- **Ficha y formularios:** `<Show>` con `<SimpleShowLayout>`; `<Create>` y `<Edit>` con `<SimpleForm>`. Los mismos campos sirven para el alta y la edición si se sacan a un componente.
- **Validación en el cliente:** `validate={[required(), maxLength(255)]}`. Es una ayuda para el usuario: la que manda es la del servidor, cuyos errores (422) React-Admin pinta bajo cada campo gracias al middleware.
- `mutationMode="pessimistic"` espera la respuesta del servidor antes de dar por guardado un cambio: así se ven sus errores de validación.
- **Claves ajenas:** `<ReferenceField source="cliente_id" reference="clientes" />` muestra el cliente de un registro, y `<ReferenceInput>` con `<SelectInput>` lo elige. Muestran lo que diga `recordRepresentation` del recurso al que apuntan.
- **Muchos a muchos:** `<ReferenceArrayInput source="proveedor_ids" reference="proveedores">` con `<SelectArrayInput>`, que envía el array de ids que espera la API.
- **`ResourceProps`:** cada fichero exporta un objeto con todo lo que necesita su `<Resource>`, y `App.tsx` lo usa con `<Resource {...clientes} />`.

## El ejemplo: clientes

`resources/js/react-admin/recursos/clientes.tsx`:

```tsx
import {
    Create,
    Datagrid,
    DateField,
    Edit,
    EditButton,
    List,
    maxLength,
    ReferenceManyField,
    required,
    ResourceProps,
    SearchInput,
    Show,
    SimpleForm,
    SimpleShowLayout,
    TextField,
    TextInput,
    useRecordContext,
} from 'react-admin';

const filtros = [<SearchInput key="q" source="q" alwaysOn />];

export const ClienteList = () => (
    <List filters={filtros} sort={{ field: 'codigo', order: 'ASC' }}>
        <Datagrid rowClick="show" bulkActionButtons={false}>
            <TextField source="codigo" label="Código" />
            <TextField source="nombre_completo" label="Nombre" sortBy="nombre" />
            <TextField source="nif" label="NIF" />
            <TextField source="telefono" label="Teléfono" />
            <EditButton />
        </Datagrid>
    </List>
);

const ClienteTitulo = () => {
    const cliente = useRecordContext();
    return <span>Cliente {cliente ? cliente.nombre_completo : ''}</span>;
};

/** Los mismos campos en el alta y en la modificación. */
const ClienteCampos = () => (
    <>
        <TextInput source="nombre" validate={[required(), maxLength(255)]} />
        <TextInput source="apellidos" />
        <TextInput source="nif" label="NIF" validate={maxLength(20)} />
        <TextInput source="direccion" label="Dirección" validate={required()} />
        <TextInput source="cod_postal" label="Código postal" validate={maxLength(10)} />
        <TextInput source="telefono" label="Teléfono" validate={[required(), maxLength(20)]} />
    </>
);

export const ClienteCreate = () => (
    <Create redirect="show">
        <SimpleForm>
            <ClienteCampos />
        </SimpleForm>
    </Create>
);

export const ClienteEdit = () => (
    <Edit title={<ClienteTitulo />} mutationMode="pessimistic">
        <SimpleForm>
            <ClienteCampos />
        </SimpleForm>
    </Edit>
);

export const ClienteShow = () => (
    <Show title={<ClienteTitulo />}>
        <SimpleShowLayout>
            <TextField source="codigo" label="Código" />
            <TextField source="nombre_completo" label="Nombre" />
            <TextField source="nif" label="NIF" />
            <TextField source="direccion" label="Dirección" />
            <TextField source="cod_postal" label="Código postal" />
            <TextField source="telefono" label="Teléfono" />
            <ReferenceManyField label="Presupuestos" reference="presupuestos" target="cliente_id">
                <Datagrid rowClick="show" bulkActionButtons={false}>
                    <TextField source="codigo" label="Código" />
                    <DateField source="fecha" />
                    <TextField source="estado" />
                </Datagrid>
            </ReferenceManyField>
        </SimpleShowLayout>
    </Show>
);

/** Todo lo que necesita <Resource> en App.tsx: <Resource {...clientes} />. */
export const clientes: ResourceProps = {
    name: 'clientes',
    options: { label: 'Clientes' },
    recordRepresentation: 'nombre_completo',
    list: ClienteList,
    show: ClienteShow,
    create: ClienteCreate,
    edit: ClienteEdit,
};
```

`App.tsx`:

```tsx
import { Admin, Resource } from 'react-admin';
import { authProvider } from './authProvider';
import { dataProvider } from './dataProvider';
import { Layout } from './Layout';
import { clientes } from './recursos/clientes';

export const App = () => (
    <Admin layout={Layout} dataProvider={dataProvider} authProvider={authProvider} requireAuth>
        <Resource {...clientes} />
    </Admin>
);
```

## Ejercicios

1. **Clientes.** Escribe `clientes.tsx` y añádelo a `App.tsx`. Prueba el buscador, el orden por columnas, el alta con un NIF repetido (el error del servidor aparece en el campo) y la ficha con sus presupuestos.

2. **El resto de recursos.** Un fichero por entidad, que exporta su objeto con el nombre del recurso. Todos con listado, ficha, alta y edición, salvo la ficha de Gastos, que no se crea:

    Fichero | Exporta | Listado | Formulario
    -----|----|----|----
    `trabajadores.tsx` | `trabajadores` | `Código`, nombre, tipo, tarifa | nombre y apellido obligatorios, tipo, teléfono, tarifa ≥ 0
    `proveedores.tsx` | `proveedores` | `Código`, nombre, tipo, total | nombre, base e IVA obligatorios; correo con `email()`; IVA entre 0 y 100
    `especialistas.tsx` | `especialistas` | `Código`, nombre, tipo, teléfono | nombre obligatorio; correo; importe ≥ 0; notas
    `presupuestos.tsx` | `presupuestos` | `Código`, fecha, cliente (`ReferenceField`), estado, total; filtro por estado | cliente y obra con `ReferenceInput`; fecha, IVA, estado; proveedores y especialistas con `ReferenceArrayInput`
    `obras.tsx` | `obras` y `gastos` | `Código`, descripción, cliente, total, salud | descripción obligatoria; cliente con `ReferenceInput`
    `obras.tsx` (Gastos) | `gastos`, sin `create` | `Código`, obra, gastos, debe | dirección y fechas de la obra

    Cada recurso tiene su `recordRepresentation`, que es lo que mostrarán los `ReferenceField` que apunten a él: el `codigo` en presupuestos y obras, el `nombre` en proveedores y especialistas, el `nombre_completo` en trabajadores.

3. **La ficha de la obra.** Muestra los indicadores de [6.13](./RA6_13_calculos.md) que devuelve la API: total, gastos, beneficio, pendiente y porcentaje cobrado. Usa `<TabbedShowLayout>`: la primera pestaña, *Resumen*, sin `path`; las demás llegan en [8.6](./RA8_6_anidados.md).

4. **El menú.** Registra todos los recursos en `App.tsx`. El menú lateral de React-Admin muestra los que tienen `list`, cada uno con un icono. Para que sea solo texto, como el de la web, `Layout.tsx` le pasa a React-Admin un menú propio:

    ```tsx
    import type { ReactNode } from 'react';
    import { Layout as RALayout, Menu, useResourceDefinitions } from 'react-admin';

    /** Menú lateral solo con texto: un enlace por cada recurso que tiene listado. */
    export const MenuDeTexto = () => {
        const recursos = useResourceDefinitions();
        return (
            <Menu>
                {Object.values(recursos)
                    .filter((r) => r.hasList)
                    .map((r) => (
                        <Menu.Item key={r.name} to={`/${r.name}`} primaryText={r.options?.label ?? r.name} />
                    ))}
            </Menu>
        );
    };

    export const Layout = ({ children }: { children: ReactNode }) => <RALayout menu={MenuDeTexto}>{children}</RALayout>;
    ```

    Sin iconos, el menú plegado no muestra nada; se abre y se cierra con el botón de la barra superior.

5. **Investiga.** Responde en tu _pull request_:
    - Entra como administrativa e intenta borrar un cliente. ¿Qué responde la API y qué muestra React-Admin? ¿Quién protege de verdad los datos?
    - En el listado de obras, ¿cuántas peticiones hace el `ReferenceField` del cliente para 25 obras? Mira la pestaña *Red*.

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque8/recursos.test.tsx resources/js/react-admin/__tests__/
npm test
npm run type-check
```

Resultado esperado: los 13 tests de `recursos.test.tsx` pasan. Pintan cada listado con un _dataProvider_ de prueba (`testDataProvider`), sin servidor, y comprueban sus columnas.

## Git

Rama `react-admin`. Un commit por recurso: `feat: clientes en React-Admin`, …

---

**Siguiente:** [8.6. Los registros que cuelgan de otro](./RA8_6_anidados.md)
<!-- {% endraw %} -->
