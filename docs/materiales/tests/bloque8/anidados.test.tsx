import { cleanup, render, screen } from '@testing-library/react';
import { AdminContext, ResourceContextProvider, testDataProvider } from 'react-admin';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, describe, expect, it, vi } from 'vitest';
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
});
