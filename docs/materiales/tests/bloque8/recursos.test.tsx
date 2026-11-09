import { cleanup, render, screen } from '@testing-library/react';
import { AdminContext, DataProvider, ResourceContextProvider, ResourceProps, testDataProvider } from 'react-admin';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { clientes } from '../recursos/clientes';
import { especialistas } from '../recursos/especialistas';
import { gastos, obras } from '../recursos/obras';
import { presupuestos } from '../recursos/presupuestos';
import { proveedores } from '../recursos/proveedores';
import { trabajadores } from '../recursos/trabajadores';

afterEach(cleanup);

/** Pinta el listado de un recurso con un dataProvider de prueba que devuelve dos registros. */
const pintarListado = (recurso: ResourceProps, extra: Partial<DataProvider> = {}) => {
    const dataProvider = testDataProvider({
        getList: vi.fn(async () => ({ data: [{ id: 1, codigo: 'X0001', obra_id: 1, cliente_id: 1 }, { id: 2, codigo: 'X0002', obra_id: 1, cliente_id: 1 }], total: 2 })) as any,
        getMany: vi.fn(async () => ({ data: [{ id: 1, codigo: 'OBR0001', nombre_completo: 'Ana Ruiz' }] })) as any,
        ...extra,
    });
    const Lista = recurso.list as React.ComponentType;
    render(
        <AdminContext dataProvider={dataProvider}>
            <ResourceContextProvider value={recurso.name}>
                <Lista />
            </ResourceContextProvider>
        </AdminContext>,
    );
    return dataProvider;
};

describe('8.5 · Recursos', () => {
    it.each([
        ['clientes', clientes],
        ['trabajadores', trabajadores],
        ['proveedores', proveedores],
        ['especialistas', especialistas],
        ['presupuestos', presupuestos],
        ['obras', obras],
    ])('%s tiene listado, ficha, alta y modificación', (nombre, recurso) => {
        expect(recurso.name).toBe(nombre);
        expect(recurso.list).toBeTruthy();
        expect(recurso.show).toBeTruthy();
        expect(recurso.create).toBeTruthy();
        expect(recurso.edit).toBeTruthy();
    });

    it('la ficha de Gastos no se crea', () => {
        expect(gastos.name).toBe('gastos');
        expect(gastos.list).toBeTruthy();
        expect(gastos.edit).toBeTruthy();
        expect(gastos.create).toBeUndefined();
    });

    it('el listado de clientes muestra sus columnas y sus registros', async () => {
        const dataProvider = pintarListado(clientes);

        expect(await screen.findByText('X0001')).toBeTruthy();
        expect(screen.getByText('X0002')).toBeTruthy();
        for (const columna of ['Código', 'Nombre', 'NIF', 'Teléfono']) {
            expect(screen.getAllByText(columna).length).toBeGreaterThan(0);
        }
        expect(dataProvider.getList).toHaveBeenCalledWith('clientes', expect.objectContaining({ pagination: expect.any(Object) }));
    });

    it.each([
        ['trabajadores', trabajadores],
        ['proveedores', proveedores],
        ['especialistas', especialistas],
        ['presupuestos', presupuestos],
        ['obras', obras],
    ])('el listado de %s muestra el código de cada registro', async (_n, recurso) => {
        pintarListado(recurso);

        expect(await screen.findByText('X0001')).toBeTruthy();
        expect(screen.getAllByText('Código').length).toBeGreaterThan(0);
    });
});
