# Anexo. Escenario de prueba completo

El ciclo de negocio de una obra de principio a fin, con el resultado esperado en cada paso. Sirve para comprobar a mano la aplicación (en la web o con la API) y es también el test `EscenarioCompletoTest` de la [batería de evaluación](./evaluacion.md).

## Datos de partida

- Cliente **Juan Pérez López**.
- Trabajadores **Ana** y **Luis**, ambos a 20 €/h.

## Pasos

| Paso | Acción | Resultado esperado |
|:--:|:--|:--|
| 1 | Crear una obra para el cliente | Tiene una y solo una ficha de Gastos. `total = 0`, `estado_pago = pendiente` |
| 2 | Presupuesto de la obra con una línea de 10 uds × 100 €, IVA 21 %, **no aprobado** | Presupuesto: base 1 000, cuota 210, total 1 210. La obra sigue con `total = 0` |
| 3 | Aprobar el presupuesto | `obra.total = 1 210` |
| 4 | Parte de trabajo con Ana y Luis, 8 h cada uno | Cada línea 160 €; `coste_moo = 320`; `horas_totales = 16`; cada trabajador acumula 8 h y 160 € |
| 5 | Subir la tarifa de Ana a 25 €/h | Las líneas del paso 4 **no** cambian (`coste_moo` sigue en 320) |
| 6 | Parte de proveedor de 200 € y de especialista de 150 € | `gastos = 670` |
| 7 | Ingreso de 500 € | `ingresos = 500` · `beneficio_real = −170` · `debe = 710` · `margen_proyectado = 540` · `porcentaje_cobrado = 41,3` · `estado_pago = parcial` · `salud = rojo` · ficha en `proceso` |
| 8 | Ingreso de 710 € | `ingresos = 1 210` · `beneficio_real = 540` · `debe = 0` · `porcentaje_cobrado = 100` · `estado_pago = pagado` · `salud = verde` · ficha `finalizado` |
| 9 | Línea de parte de 25 h o −1 h; parte externo de 0 €; IVA 150 %; falta con fin anterior al inicio | Rechazados por validación (422) |
| 10 | Buscar "Juan Pérez" en clientes | Lo encuentra, aunque nombre y apellidos están en columnas distintas |
| 11 | Borrar la obra | Rechazado: tiene ingresos (409) |
| 11b | Repetir el escenario sin los pasos 7 y 8 y borrar la obra | Desaparecen su ficha de Gastos y sus partes; el presupuesto sigue existiendo sin obra; Ana y Luis vuelven a 0 h y 0 € |
| 12 | Borrar la ficha de Gastos directamente | Rechazado (405 en la API; no hay botón en la web) |
| 13 | Presupuesto o ingreso de otro cliente en la obra | Rechazado por validación (422 en `cliente_id`) |

## Comprobación con la API

Con un token de [7.3](./RA7_3_sanctum.md), los pasos se pueden reproducir con `curl` o con Swagger UI ([7.6](./RA7_6_openapi.md)). Las rutas de cada paso:

| Paso | Petición |
|:--:|:--|
| 1 | `POST /api/v1/obras` |
| 2 | `POST /api/v1/presupuestos` y `POST /api/v1/presupuestos/{id}/lineas` |
| 3 | `PUT /api/v1/presupuestos/{id}` con `{ "estado": "aprobado" }` |
| 4 | `POST /api/v1/obras/{id}/partes-trabajo` con `lineas` |
| 5 | `PUT /api/v1/trabajadores/{id}` |
| 6 | `POST /api/v1/obras/{id}/partes-proveedor` y `/partes-especialista` |
| 7, 8 | `POST /api/v1/obras/{id}/ingresos` |
| — | Resultados: `GET /api/v1/obras/{id}` y `GET /api/v1/gastos/{gasto_id}` |
| 11 | `DELETE /api/v1/obras/{id}` |

## Comprobación automática

```bash
mkdir -p tests/Feature/Escenario
cp /var/www/curso-laravel/evaluacion/tests/Feature/Escenario/EscenarioCompletoTest.php tests/Feature/Escenario/
php artisan test tests/Feature/Escenario
```

Resultado esperado: `Tests: 3 passed`.

El mismo ciclo por las pantallas, con formularios y sesión iniciada, es el test `RA6_10_FlujoWebTest` del apartado [6.10](./RA6_10_proyecto.md).
