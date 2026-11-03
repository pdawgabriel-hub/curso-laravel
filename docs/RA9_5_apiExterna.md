# 9.5. Consumir una API externa

**Referencia teórica:** [Consumiendo API externas](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/12_apiExterna.html) · [Laravel: HTTP Client](https://laravel.com/docs/13.x/http-client) · [Nominatim](https://nominatim.org/release-docs/latest/api/Search/)

Obtener las coordenadas de la dirección de una obra con el servicio público de geocodificación de OpenStreetMap (Nominatim) y mostrarla en un mapa.

## Conceptos clave

- `Http::withHeaders([...])->timeout(10)->get($url, $query)->throw()->json()`.
- La [política de uso de Nominatim](https://operations.osmfoundation.org/policies/nominatim/) exige una cabecera `User-Agent` que identifique la aplicación y no más de una petición por segundo: por eso las respuestas se guardan en caché.
- `Cache::remember($clave, now()->addDay(), fn () => …)`.
- En los tests, `Http::fake()` devuelve respuestas preparadas y `Http::assertSent()` comprueba la petición.

## Ejercicios

1. Servicio `App\Services\Geocodificador` con `buscar(string $direccion): ?array` que consulta `https://nominatim.openstreetmap.org/search` con `q`, `format=json` y `limit=1`, envía un `User-Agent` con el nombre de la aplicación y el email de la empresa, devuelve `['lat' => float, 'lon' => float, 'direccion' => display_name]` o `null` si no hay resultados, y guarda el resultado un día en caché.
2. Endpoint `GET /api/v1/obras/{obra}/ubicacion`:
   - Dirección consultada: `direccion_obra` de su ficha de Gastos o, si está vacía, la del cliente.
   - **200** `{ data: { lat, lon, direccion, consulta } }`.
   - **422** si la obra no tiene ninguna dirección (sin llamar al servicio).
   - **404** si el servicio no encuentra la dirección.
   - **503** si el servicio falla o no responde.
3. Mapa en el detalle de la obra con [Leaflet](https://leafletjs.com/) (desde un CDN) y un marcador en las coordenadas.

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque9/RA9_5_ApiExternaTest.php tests/Feature/
php artisan test --filter=RA9_5
```

Resultado esperado: `Tests: 6 passed`. Ninguno de los tests llama de verdad a Nominatim.

## Git

Rama `RA9_5`. Al fusionar: `git tag -a bloque-9 -m "Bloque 9: aplicaciones híbridas" && git push --tags`.

---

**Siguiente:** [Anexo. Escenario de prueba completo](./anexo_escenario.md)
