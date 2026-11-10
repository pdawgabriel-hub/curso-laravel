# 8.6. Los registros que cuelgan de otro
<!-- {% raw %} -->

**Referencia teórica:** [Personalizar las tablas de React-Admin](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/066_componentesRA.html) · [React-Admin: ReferenceManyField](https://marmelab.com/react-admin/ReferenceManyField.html) · [Prefilling a Form](https://marmelab.com/react-admin/Create.html#prefilling-the-form)

Las faltas, las líneas, los partes y los ingresos no tienen pantalla propia: igual que en la web del Bloque 6, se ven, se crean y se borran **desde la ficha de su padre**. El _dataProvider_ de [8.4](./RA8_4_proveedores.md) ya sabe construir sus URL anidadas.

## Conceptos clave

- `<ReferenceManyField reference="faltas" target="trabajador_id">` pide al _dataProvider_ `getManyReference('faltas', { target: 'trabajador_id', id })`, que el nuestro convierte en `GET /trabajadores/{id}/faltas`.
- **Alta con el padre ya puesto:** un `<CreateButton>` puede pasar al formulario un registro inicial con `state={{ record: { trabajador_id: 5 } }}`. Así el `create` del _dataProvider_ sabe a qué URL enviarlo.
- **Volver al padre:** el `redirect` de `<Create>` admite una función que recibe los datos guardados y devuelve la ruta, por ejemplo `trabajadores/5/show`.
- **Pestañas con ruta:** en `<TabbedShowLayout>`, `<TabbedShowLayout.Tab label="Ingresos" path="ingresos">` abre esa pestaña en `#/obras/5/show/ingresos`. Se usan las mismas claves que `?pestana=` en la web ([6.11](./RA6_11_partes.md)), y tras dar de alta un hijo se vuelve a su pestaña: `obras/5/show/ingresos`.
- **Borrar sin salir de la ficha:** `<DeleteButton redirect={false}>`. El _dataProvider_ recibe el registro borrado (`previousData`) y saca de él el id del padre.
- Un recurso registrado solo con `create` (`<Resource name="faltas" create={FaltaCreate} />`) no aparece en el menú, pero tiene su formulario de alta.

## El ejemplo: las faltas del trabajador

Tres piezas que sirven para todos los hijos, en `recursos/comunes.tsx`:

```tsx
import { CreateButton, DeleteButton, useRecordContext } from 'react-admin';

/** Botón para dar de alta un hijo (ingreso, parte, falta…) con el id del registro actual ya puesto. */
export const AltaHijo = ({ recurso, clave, texto }: { recurso: string; clave: string; texto: string }) => {
    const registro = useRecordContext();
    if (!registro) return null;
    return <CreateButton resource={recurso} label={texto} state={{ record: { [clave]: registro.id } }} />;
};

/** Borrar un hijo sin salir de la ficha del padre. */
export const BorrarHijo = () => <DeleteButton redirect={false} mutationMode="pessimistic" />;

/** Tras dar de alta un hijo, se vuelve a la ficha de su padre y, si se indica, a una de sus pestañas. */
export const volverAlPadre = (padre: string, clave: string, pestana?: string) =>
    (_recurso?: string, _id?: unknown, datos?: Record<string, unknown>) =>
        `${padre}/${datos?.[clave]}/show${pestana ? `/${pestana}` : ''}`;
```

En `recursos/trabajadores.tsx`, la ficha del trabajador con sus faltas y el formulario de alta:

```tsx
export const tiposFalta = [
    { id: 'vacaciones', name: 'Vacaciones' }, { id: 'baja_medica', name: 'Baja médica' }, { id: 'personal', name: 'Personal' },
    { id: 'injustificada', name: 'Injustificada' }, { id: 'otro', name: 'Otro' },
];

export const TrabajadorShow = () => (
    <Show>
        <SimpleShowLayout>
            <TextField source="codigo" label="Código" />
            <TextField source="nombre_completo" label="Nombre" />
            <NumberField source="coste_hora_estandar" label="Tarifa" />
            <NumberField source="horas_totales" label="Horas" />
            <AltaHijo recurso="faltas" clave="trabajador_id" texto="Nueva falta" />
            <ReferenceManyField label="Faltas" reference="faltas" target="trabajador_id">
                <Datagrid bulkActionButtons={false}>
                    <TextField source="tipo" />
                    <DateField source="fecha_inicio" label="Desde" />
                    <DateField source="fecha_fin" label="Hasta" />
                    <NumberField source="dias" label="Días" />
                    <BorrarHijo />
                </Datagrid>
            </ReferenceManyField>
        </SimpleShowLayout>
    </Show>
);

export const FaltaCreate = () => (
    <Create redirect={volverAlPadre('trabajadores', 'trabajador_id')}>
        <SimpleForm>
            <DateInput source="fecha_inicio" label="Desde" validate={required()} />
            <DateInput source="fecha_fin" label="Hasta" validate={required()} />
            <SelectInput source="tipo" choices={tiposFalta} defaultValue="vacaciones" />
            <TextInput source="motivo" multiline />
        </SimpleForm>
    </Create>
);

export const faltas: ResourceProps = { name: 'faltas', create: FaltaCreate };
```

Y en `App.tsx`, `<Resource {...faltas} />`.

## Ejercicios

1. **Las faltas.** Añade el ejemplo y comprueba en el navegador que la falta se crea, aparece en la ficha y se borra sin salir de ella.

2. **Las líneas del presupuesto.** En la ficha del presupuesto (`PresupuestoShow`), la lista de líneas con un botón *Nueva línea*, y el recurso `lineasPresupuesto` (`name: 'lineas-presupuesto'`) con su alta: descripción, unidades y precio, y vuelta al presupuesto.

3. **Partes e ingresos de la obra.** En `ObraShow`, una pestaña por cada tipo de hijo, con su lista, su botón de alta y su botón de borrar, igual que en la web:

    Pestaña (`path`) | Recurso | Columnas | Botón de alta
    -----|----|----|----
    *Partes de trabajo* (`partes-trabajo`) | `partes-trabajo` | fecha, descripción, horas | *Nuevo parte*
    *Proveedores* (`partes-proveedor`) | `partes-proveedor` | fecha, proveedor, documento, importe, estado de pago | *Nuevo parte de proveedor*
    *Especialistas* (`partes-especialista`) | `partes-especialista` | fecha, especialista, documento, importe, estado de pago | *Nuevo parte de especialista*
    *Ingresos* (`ingresos`) | `ingresos` | código, fecha, tipo, importe | *Nuevo ingreso*

    Cada formulario de alta vuelve a su pestaña: `redirect={volverAlPadre('obras', 'obra_id', 'ingresos')}`. Exporta los recursos de alta en un array `hijosDeObra` y regístralos en `App.tsx` con `hijosDeObra.map(...)`. En los partes externos, el proveedor o el especialista se eligen con `<ReferenceInput>`; el importe debe ser mayor que 0.

4. **Opcional: las líneas de un parte.** El recurso `lineas-parte` cuelga de `partes-trabajo`. Si quieres verlas, hace falta una ficha del parte, con su propio `ReferenceManyField`.

5. **Investiga.** Responde en tu _pull request_:
    - Intenta abrir `/dashboard#/ingresos/1/show` escribiéndolo en la barra de direcciones. ¿Qué pasa? ¿Por qué tiene sentido que no funcione?
    - Entra como administrativa y borra el parte de otro usuario. ¿Quién lo impide y qué ve el usuario?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque8/anidados.test.tsx resources/js/react-admin/__tests__/
npm test
npm run type-check
npm run build
```

Resultado esperado: `Tests 29 passed` entre los cuatro ficheros de Vitest, `type-check` sin errores y la compilación terminada.

## Git

Rama `react-admin`. Commits: `feat: faltas desde el trabajador`, `feat: líneas del presupuesto`, `feat: partes e ingresos desde la obra`.

---

**Siguiente:** [8.7. Proyecto: la obra completa en React-Admin](./RA8_7_proyecto.md)
<!-- {% endraw %} -->
