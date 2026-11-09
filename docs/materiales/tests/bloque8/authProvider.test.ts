import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { authProvider } from '../authProvider';
import { CLAVE_TOKEN } from '../dataProvider';

const API = 'http://gestion-obras.test/api/v1';
const responder = (cuerpo: unknown, estado = 200) =>
    vi.stubGlobal('fetch', vi.fn(async () => new Response(estado === 204 ? null : JSON.stringify(cuerpo), { status: estado, headers: { 'Content-Type': 'application/json' } })));

beforeEach(() => localStorage.clear());
afterEach(() => vi.unstubAllGlobals());

describe('8.4 · authProvider', () => {
    it('inicia sesión pidiendo un token y lo guarda', async () => {
        responder({ token_type: 'Bearer', access_token: '1|abc' });

        await authProvider.login({ username: 'jefe@gestion-obras.test', password: 'password' });

        const [url, opciones] = (fetch as ReturnType<typeof vi.fn>).mock.calls[0];
        expect(url).toBe(`${API}/tokens`);
        expect(opciones.method).toBe('POST');
        expect(JSON.parse(opciones.body)).toEqual({ email: 'jefe@gestion-obras.test', password: 'password' });
        expect(localStorage.getItem(CLAVE_TOKEN)).toBe('1|abc');
    });

    it('con credenciales incorrectas no guarda nada', async () => {
        responder({ message: 'Credenciales incorrectas', errors: { email: 'Credenciales incorrectas' } }, 422);

        await expect(authProvider.login({ username: 'x@x.es', password: 'mal' })).rejects.toBeTruthy();
        expect(localStorage.getItem(CLAVE_TOKEN)).toBeNull();
    });

    it('sin token pide iniciar sesión, y con un 401 la cierra', async () => {
        await expect(authProvider.checkAuth({})).rejects.toBeTruthy();

        localStorage.setItem(CLAVE_TOKEN, 'abc');
        await expect(authProvider.checkAuth({})).resolves.toBeUndefined();
        await expect(authProvider.checkError({ status: 403 })).resolves.toBeUndefined();
        await expect(authProvider.checkError({ status: 401 })).rejects.toBeTruthy();
        expect(localStorage.getItem(CLAVE_TOKEN)).toBeNull();
    });

    it('cierra la sesión revocando el token', async () => {
        localStorage.setItem(CLAVE_TOKEN, 'abc');
        responder(null, 204);

        await authProvider.logout({});

        const [url, opciones] = (fetch as ReturnType<typeof vi.fn>).mock.calls[0];
        expect(url).toBe(`${API}/tokens`);
        expect(opciones.method).toBe('DELETE');
        expect(localStorage.getItem(CLAVE_TOKEN)).toBeNull();
    });

    it('la identidad y los permisos salen de GET /user', async () => {
        localStorage.setItem(CLAVE_TOKEN, 'abc');
        responder({ id: 3, name: 'Jefe', roles: ['jefe'] });

        expect(await authProvider.getIdentity!()).toEqual({ id: 3, fullName: 'Jefe' });
        expect(await authProvider.getPermissions!({})).toEqual(['jefe']);
    });
});
