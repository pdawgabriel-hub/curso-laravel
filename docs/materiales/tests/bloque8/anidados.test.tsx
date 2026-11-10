import { cleanup, render, screen } from '@testing-library/react';
import { AdminContext, ResourceContextProvider, testDataProvider } from 'react-admin';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { volverAlPadre } from '../recursos/comunes';
import { ObraShow } from '../recursos/obras';
import { PresupuestoShow } from '../recursos/presupuestos';
import { TrabajadorShow } from '../recursos/trabajadores';

afterEach(cleanup);

/** Pinta la ficha de un registro y devuelve el dataProvider de prueba para ver qué se le pidió. */
const pintarFicha = async (recurso: string, Ficha: React.ComponentType, hijos: object[]) => {
    const dataProvider = testDataProvider({
        getOne: vi.fn(async () => ({ data: { id: 5, codigo: 'REG0005', nombre_completo: 'Luis Gil', nombre_cliente: 'Ana Ruiz' } })) as any,
        getManyReference: vi.fn(async () => ({ data: hijos, total: hijos.length })) as any,
    });
    render(
        <MemoryRouter initialEntries={[`/${recurso}/5/show`]}>
            <AdminContext dataProvider={dataProvider}>
                <ResourceContextProvider value={recurso}>
                    <Routes>
                        <Route path={`/${recurso}/:id/show`} element={<Ficha />} />
                    </Routes>
                </ResourceContextProvider>
            </AdminContext>
        </MemoryRouter>,
    );
    await screen.findByText('REG0005');
    return dataProvider;
};

describe('8.6 · Hijos en la ficha del padre', () => {
    it('la ficha del trabajador lista sus faltas y permite añadir una', async () => {
        const dp = await pintarFicha('trabajadores', TrabajadorShow, [{ id: 1, trabajador_id: 5, tipo: 'vacaciones', dias: 3 }]);

        expect(dp.getManyReference).toHaveBeenCalledWith('faltas', expect.objectContaining({ target: 'trabajador_id', id: 5 }));
        expect(await screen.findByText('vacaciones')).toBeTruthy();
        expect(screen.getByText('Nueva falta')).toBeTruthy();
    });

    it('la ficha del presupuesto lista sus líneas y permite añadir una', async () => {
        const dp = await pintarFicha('presupuestos', PresupuestoShow, [{ id: 1, presupuesto_id: 5, descripcion: 'Alicatado', uds: 10, precio: 100, total_linea: 1000 }]);

        expect(dp.getManyReference).toHaveBeenCalledWith('lineas-presupuesto', expect.objectContaining({ target: 'presupuesto_id', id: 5 }));
        expect(await screen.findByText('Alicatado')).toBeTruthy();
        expect(screen.getByText('Nueva línea')).toBeTruthy();
    });

    it('la ficha de la obra abre la pestaña de su URL, con su lista y su alta', async () => {
        const dataProvider = testDataProvider({
            getOne: vi.fn(async () => ({ data: { id: 5, codigo: 'OBR0005', descripcion: 'Reforma de baño', cliente_id: 1 } })) as any,
            getMany: vi.fn(async () => ({ data: [] })) as any,
            getManyReference: vi.fn(async () => ({ data: [{ id: 1, obra_id: 5, codigo: 'ING0001', importe: 500 }], total: 1 })) as any,
        });
        render(
            <MemoryRouter initialEntries={['/obras/5/show/ingresos']}>
                <AdminContext dataProvider={dataProvider}>
                    <ResourceContextProvider value="obras">
                        <Routes>
                            <Route path="/obras/:id/show/*" element={<ObraShow />} />
                        </Routes>
                    </ResourceContextProvider>
                </AdminContext>
            </MemoryRouter>,
        );

        expect(await screen.findByText('ING0001')).toBeTruthy();
        expect(screen.getByText('Nuevo ingreso')).toBeTruthy();
        expect(dataProvider.getManyReference).toHaveBeenCalledWith('ingresos', expect.objectContaining({ target: 'obra_id', id: 5 }));
        expect(screen.queryByText('Nuevo parte')).toBeNull();
    });

    it('tras dar de alta un hijo se vuelve a su pestaña', () => {
        expect(volverAlPadre('obras', 'obra_id', 'ingresos')(undefined, undefined, { obra_id: 5 })).toBe('obras/5/show/ingresos');
        expect(volverAlPadre('trabajadores', 'trabajador_id')(undefined, undefined, { trabajador_id: 3 })).toBe('trabajadores/3/show');
    });
});
