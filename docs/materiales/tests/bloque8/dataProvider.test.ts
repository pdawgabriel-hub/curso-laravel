import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { CLAVE_TOKEN, dataProvider, urlColeccion } from '../dataProvider';

const API = 'http://gestion-obras.test/api/v1';

/** Simula fetch: guarda cada petición y responde con el JSON y las cabeceras indicadas. */
const fetchFalso = (cuerpo: unknown = {}, cabeceras: Record<string, string> = {}, estado = 200) => {
    const fn = vi.fn(async (_url: string, _opciones?: RequestInit) =>
        new Response(estado === 204 ? null : JSON.stringify(cuerpo), { status: estado, headers: { 'Content-Type': 'application/json', ...cabeceras } }),
    );
    vi.stubGlobal('fetch', fn);
    return fn;
};
const peticion = (fn: ReturnType<typeof fetchFalso>, n = 0) => ({ url: String(fn.mock.calls[n][0]), opciones: fn.mock.calls[n][1] as RequestInit });

beforeEach(() => localStorage.clear());
afterEach(() => vi.unstubAllGlobals());

describe('8.4 · dataProvider', () => {
    it('lista un recurso con los parámetros de React-Admin y lee el total de X-Total-Count', async () => {
        const fn = fetchFalso([{ id: 1, codigo: 'CLI0001' }], { 'X-Total-Count': '21' });

        const res = await dataProvider.getList('clientes', { pagination: { page: 2, perPage: 10 }, sort: { field: 'nombre', order: 'ASC' }, filter: { q: 'Juan' } });

        const { url } = peticion(fn);
        expect(url.startsWith(`${API}/clientes?`)).toBe(true);
        expect(url).toContain('_start=10');
        expect(url).toContain('_end=20');
        expect(url).toContain('_sort=nombre');
        expect(url).toContain('q=Juan');
        expect(res.total).toBe(21);
        expect(res.data[0].codigo).toBe('CLI0001');
    });

    it('envía siempre la cabecera X-Cliente y, si hay sesión, el token', async () => {
        const fn = fetchFalso({ id: 1 });
        await dataProvider.getOne('clientes', { id: 1 });
        let cabeceras = new Headers(peticion(fn).opciones.headers);
        expect(cabeceras.get('X-Cliente')).toBe('react-admin');
        expect(cabeceras.get('Authorization')).toBeNull();

        localStorage.setItem(CLAVE_TOKEN, 'abc');
        await dataProvider.getOne('clientes', { id: 1 });
        cabeceras = new Headers(peticion(fn, 1).opciones.headers);
        expect(cabeceras.get('Authorization')).toBe('Bearer abc');
    });

    it('construye la URL de los recursos anidados con su padre', () => {
        expect(urlColeccion('clientes')).toBe(`${API}/clientes`);
        expect(urlColeccion('ingresos', 3)).toBe(`${API}/obras/3/ingresos`);
        expect(urlColeccion('faltas', 5)).toBe(`${API}/trabajadores/5/faltas`);
        expect(urlColeccion('lineas-presupuesto', 2)).toBe(`${API}/presupuestos/2/lineas`);
        expect(urlColeccion('lineas-parte', 4)).toBe(`${API}/partes-trabajo/4/lineas`);
        expect(() => urlColeccion('ingresos')).toThrow();
    });

    it('lista los hijos de un registro en la ruta de su padre', async () => {
        const fn = fetchFalso([{ id: 7, obra_id: 3 }], { 'X-Total-Count': '1' });

        const res = await dataProvider.getManyReference('ingresos', { target: 'obra_id', id: 3, pagination: { page: 1, perPage: 25 }, sort: { field: 'id', order: 'ASC' }, filter: {} });

        expect(peticion(fn).url.startsWith(`${API}/obras/3/ingresos?`)).toBe(true);
        expect(res.total).toBe(1);
    });

    it('lista los registros de otro recurso con un filtro normal', async () => {
        const fn = fetchFalso([], { 'X-Total-Count': '0' });

        await dataProvider.getManyReference('presupuestos', { target: 'cliente_id', id: 9, pagination: { page: 1, perPage: 25 }, sort: { field: 'id', order: 'ASC' }, filter: {} });

        const { url } = peticion(fn);
        expect(url.startsWith(`${API}/presupuestos?`)).toBe(true);
        expect(url).toContain('cliente_id=9');
    });

    it('crea, modifica y borra un hijo en la ruta de su padre', async () => {
        let fn = fetchFalso({ id: 7, obra_id: 3, importe: 100 }, {}, 201);
        await dataProvider.create('ingresos', { data: { obra_id: 3, importe: 100 } });
        expect(peticion(fn).url).toBe(`${API}/obras/3/ingresos`);
        expect(peticion(fn).opciones.method).toBe('POST');

        fn = fetchFalso({ id: 7, obra_id: 3, importe: 150 });
        await dataProvider.update('ingresos', { id: 7, data: { importe: 150 }, previousData: { id: 7, obra_id: 3 } });
        expect(peticion(fn).url).toBe(`${API}/obras/3/ingresos/7`);
        expect(peticion(fn).opciones.method).toBe('PUT');

        fn = fetchFalso(null, {}, 204);
        const res = await dataProvider.delete('ingresos', { id: 7, previousData: { id: 7, obra_id: 3 } });
        expect(peticion(fn).url).toBe(`${API}/obras/3/ingresos/7`);
        expect(peticion(fn).opciones.method).toBe('DELETE');
        expect(res.data.id).toBe(7);
    });

    it('un hijo no se pide suelto y getMany pide cada registro', async () => {
        await expect(dataProvider.getOne('ingresos', { id: 7 })).rejects.toThrow();

        const fn = fetchFalso({ id: 1 });
        const res = await dataProvider.getMany('clientes', { ids: [1, 2] });
        expect(fn).toHaveBeenCalledTimes(2);
        expect(peticion(fn, 1).url).toBe(`${API}/clientes/2`);
        expect(res.data).toHaveLength(2);
    });
});
