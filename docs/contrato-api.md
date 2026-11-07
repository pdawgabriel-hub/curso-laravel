# Contrato de la API

Especificación de la API REST y de los modelos que comprueban los tests del Bloque 7 y la [batería de evaluación](./evaluacion.md). Cualquier implementación que cumpla esta página pasa los tests.

## Convenciones generales

| Aspecto | Convención |
|:--|:--|
| Prefijo | `/api/v1`. Los nombres de ruta llevan el prefijo `api.` (`Route::prefix('v1')->name('api.')`) para no chocar con los de la web (`clientes.index`) |
| Autenticación | Sanctum (`auth:sanctum`) en todas las rutas salvo `POST /tokens`. Sin token → **401** |
| Roles | Columna `users.rol`: `administrativa` o `jefe` (mismos permisos), o `null`. El administrador es el usuario cuyo email coincide con `config('app.admin.email')` (`ADMIN_EMAIL` en `.env`) y pasa por `Gate::before` |
| Permisos | Con rol (o administrador) se puede todo, salvo borrar observaciones e incidencias de otro autor ([ver](#bitácoras-y-adjuntos)); un usuario sin rol recibe **403** en cualquier ruta salvo `GET /user`. Primero se autoriza y después se valida |
| Listados | `Resource::collection($query->paginate())`: `{ data: [...], links: {...}, meta: {...} }` |
| Un registro | `{ data: {...} }` |
| Crear | **201** con el registro completo (todas las columnas, también las que no se enviaron: `$modelo->refresh()`) |
| Actualizar | **200** con el registro. Admite actualización parcial: los campos obligatorios se validan con `sometimes|required` |
| Borrar | **204** sin cuerpo |
| Validación | **422** con `errors.campo` (formato estándar de Laravel) |
| Regla de negocio | **409** con `{ "message": "…" }` (borrar una obra con ingresos, un cliente con presupuestos…) |
| No encontrado | **404**, también si un recurso anidado pertenece a otro padre (`/obras/1/ingresos/7` cuando el ingreso 7 es de la obra 2) |
| Importes | Números con 2 decimales como máximo |
| Fechas | `Y-m-d` (`2026-05-10`). Si se omite `fecha` al crear, se usa la de hoy |
| Códigos | `codigo` lo asigna la aplicación (`CLI0001`…), no se acepta en la petición, es correlativo por prefijo y no se reutiliza |

### Parámetros de los listados

Todos los `index` aceptan:

| Parámetro | Efecto |
|:--|:--|
| `search` | Divide el texto en palabras; cada palabra debe aparecer en alguno de los campos buscables (`LIKE`) |
| `sort`, `order` | Orden por un campo (`sort=nombre&order=desc`). Por defecto `id asc` |
| `per_page` | Tamaño de página (15 por defecto). `links` incluye `first`, `last`, `prev` y `next`; `meta`, al menos `current_page`, `total` y `per_page` |
| Filtros | Igualdad exacta sobre los campos indicados en cada recurso (`?estado=aprobado`) |

## Autenticación y usuario

| Método | Ruta | Respuesta |
|:--|:--|:--|
| POST | `/tokens` | `{ email, password }` → **200** `{ token_type: "Bearer", access_token }`; credenciales incorrectas → **422** en `email` |
| DELETE | `/tokens` | Revoca el token actual → **204** |
| GET | `/user` | El usuario autenticado más `roles`: `["administrador"]`, `["jefe"]`, `["administrativa"]` o `[]` |

## Maestros

### Clientes

`apiResource('clientes')` · buscables: `codigo`, `nombre`, `apellidos`, `nif`, `telefono`

| Campo | Tipo | Validación |
|:--|:--|:--|
| `nombre` | string | obligatorio, máx. 255 |
| `apellidos` | string | opcional |
| `nif` | string | opcional, máx. 20, **único** (varios clientes pueden no tener NIF) |
| `direccion` | string | obligatorio |
| `cod_postal` | string | opcional, máx. 10 |
| `telefono` | string | obligatorio, máx. 20 |

Respuesta: `id, codigo, nombre, apellidos, nombre_completo, nif, direccion, cod_postal, telefono, created_at, updated_at`.
Borrar un cliente con presupuestos → **409**.

### Trabajadores

`apiResource('trabajadores')` · buscables: `codigo`, `nombre`, `apellido`

| Campo | Validación |
|:--|:--|
| `tipo` | opcional |
| `nombre`, `apellido` | obligatorios |
| `telefono` | opcional |
| `coste_hora_estandar` | numérico ≥ 0; **20** por defecto |

Respuesta: `id, codigo, tipo, nombre, apellido, nombre_completo, telefono, coste_hora_estandar, horas_totales, total_euros, created_at, updated_at`.
Borrar un trabajador con líneas de parte → **409**.

| Extra | Respuesta |
|:--|:--|
| GET `/trabajadores/{trabajador}/obras` | Listado de obras en las que tiene alguna línea de parte |
| GET `/trabajadores/{trabajador}/lineas-trabajo` | Listado de **sus** líneas (formato de [líneas de parte](#líneas-de-parte-de-trabajo)) |

### Faltas

`apiResource('trabajadores.faltas')` (con `scoped()`) · filtro: `tipo`

| Campo | Validación |
|:--|:--|
| `fecha_inicio`, `fecha_fin` | obligatorias; `fecha_fin ≥ fecha_inicio` |
| `tipo` | `vacaciones` (por defecto), `baja_medica`, `personal`, `injustificada`, `otro` |
| `motivo` | opcional |

Respuesta: campos + `id, trabajador_id, dias` (inclusive: un día = 1).

### Proveedores

`apiResource('proveedores')` · buscables: `codigo`, `nombre`

| Campo | Validación |
|:--|:--|
| `nombre` | obligatorio |
| `direccion`, `telefono`, `tipo`, `referencia`, `documento`, `num_documento` | opcionales |
| `correo` | opcional, email |
| `base` | obligatorio, ≥ 0 |
| `iva` | obligatorio, entre 0 y 100 |

Respuesta: campos + `id, codigo, total` (`base × (1 + iva/100)`). Borrar con partes → **409**. GET `/proveedores/{proveedor}/obras`.

### Especialistas

`apiResource('especialistas')` · buscables: `codigo`, `nombre`, `tipo`

Campos: `tipo, referencia, comunicacion_parte, nombre` (obligatorio)`, notas, telefono, correo` (email)`, importe` (opcional, ≥ 0).
Borrar con partes → **409**. GET `/especialistas/{especialista}/obras`.

## Presupuestos

`apiResource('presupuestos')` · buscables: `codigo`, `nombre_cliente` · filtros: `obra_id`, `cliente_id`, `estado`

| Campo | Validación |
|:--|:--|
| `fecha` | opcional (hoy por defecto) |
| `cliente_id` | obligatorio, existe |
| `obra_id` | opcional, existe. Si la obra tiene cliente, debe ser el mismo → si no **422** en `cliente_id` |
| `nombre_cliente`, `nif`, `direccion`, `telefono` | opcionales: si no se envían se **copian del cliente** al crear y ya no cambian |
| `iva` | 0–100; **21** por defecto |
| `estado` | `aprobado` o `no_aprobado` (por defecto) |
| `proveedor_ids`, `especialista_ids` | arrays de ids; se sincronizan con las tablas pivote |

Respuesta: campos + `id, codigo, anio, base_imponible, cuota_iva, total, proveedor_ids, especialista_ids, created_at, updated_at`. Borrar un presupuesto borra sus líneas y adjuntos.

### Líneas de presupuesto

`apiResource('presupuestos.lineas')` (con `scoped()`): `descripcion` (opcional), `uds` (entero ≥ 0, obligatorio), `precio` (≥ 0, obligatorio). Respuesta: `id, presupuesto_id, descripcion, uds, precio, total_linea`.

## Obras

`apiResource('obras')` · buscables: `codigo`, `descripcion` · filtro: `cliente_id`

| Campo | Validación |
|:--|:--|
| `descripcion` | opcional |
| `cliente_id` | opcional, existe. No se puede cambiar si la obra tiene presupuestos o ingresos de otro cliente → **422** |

Al crear una obra se crea **en la misma transacción** su ficha de Gastos (`GAS0001`).

Respuesta: `id, codigo, descripcion, cliente_id, total, horas_totales, coste_moo, estado_pago, gastos_totales, beneficio, pendiente_cobro, margen_proyectado, porcentaje_cobrado, salud, gasto_id, created_at, updated_at` (fórmulas en [0.4](./004_de_odoo_a_laravel.md#reglas-de-negocio)).

Borrar: con ingresos → **409** y no se toca nada; sin ingresos → se borran sus partes (con sus líneas, bitácoras y adjuntos), su ficha de Gastos y sus bitácoras y adjuntos, y sus presupuestos quedan con `obra_id = null`.

| Extra | Respuesta |
|:--|:--|
| GET `/obras/{obra}/presupuestos-disponibles` | Presupuestos sin obra (y del cliente de la obra, si tiene) |
| POST `/obras/{obra}/vincular-presupuesto` | `{ presupuesto_id }` → **200** con el presupuesto. Solo presupuestos sin obra y del mismo cliente; si no **422** en `presupuesto_id` |
| GET `/obras/{obra}/trabajadores` · `/proveedores` · `/especialistas` | Participantes deducidos de sus partes, sin repetir |
| GET `/obras/{obra}/ubicacion` | Ver [9.5](./RA9_5_apiExterna.md) |

## Ficha de Gastos

`apiResource('gastos')->only(['index', 'show', 'update'])` · filtro: `obra_id`. `POST /gastos` y `DELETE /gastos/{id}` → **405**.

| Campo | Validación |
|:--|:--|
| `presupuesto_id` | opcional; debe ser de la misma obra → si no **422** |
| `direccion_obra` | opcional |
| `inicio_obra`, `fin_obra`, `solicitud_obra` | fechas opcionales; `fin_obra ≥ inicio_obra` → si no **422** en `fin_obra` |
| `mandante`, `gestion_licencia`, `pago_icio` | booleanos |

`obra_id` no se puede cambiar (se ignora). Respuesta: campos + `id, codigo, obra_id, horas_trabajadas, coste_moo, coste_hora, coste_proveedores, coste_especialistas, gastos, ingresos, beneficio_real, debe, estado`.

## Ingresos

`apiResource('obras.ingresos')` · buscables: `codigo`, `nombre_obra`, `tipo`

| Campo | Validación |
|:--|:--|
| `importe` | obligatorio, **> 0** |
| `cliente_id` | opcional; debe ser el de la obra → si no **422**. Si se omite, se toma el de la obra |
| `nombre_obra` | opcional; si se omite, la descripción de la obra |
| `tipo`, `fecha`, `documento`, `num_documento` | opcionales |

Respuesta: campos + `id, codigo, obra_id, anio, total_ingresos, debe`. Se puede cobrar más de lo presupuestado (`debe` negativo).

## Partes

### Partes de trabajo

`apiResource('obras.partes-trabajo')` · buscables: `descripcion`

| Campo | Validación |
|:--|:--|
| `fecha` | opcional |
| `descripcion` | obligatoria |
| `lineas` | opcional, array: `lineas.*.trabajador_id` (obligatorio), `lineas.*.horas` (0–24), `lineas.*.coste_hora` (opcional, ≥ 0) |

La cabecera y sus líneas se crean en una transacción. Respuesta: `id, obra_id, fecha, descripcion, horas_totales, coste_total, lineas, created_at, updated_at`. Borrar un parte borra sus líneas.

### Líneas de parte de trabajo

`apiResource('partes-trabajo.lineas')` (con `scoped()`): `trabajador_id` (obligatorio), `horas` (0–24, obligatorio), `coste_hora` (opcional; si se omite se **copia** el `coste_hora_estandar` del trabajador y ya no cambia).
Respuesta: `id, parte_trabajo_id, trabajador_id, horas, coste_hora, coste_subtotal, fecha, obra_id` (`fecha` y `obra_id` son los del parte).

**Alta por lote.** POST `/partes-trabajo/{parteTrabajo}/lineas/lote` crea una línea por trabajador con las mismas horas, para una cuadrilla que hace la misma jornada:

| Campo | Validación |
|:--|:--|
| `trabajador_ids` | obligatorio, array con al menos 1 id y como máximo `config('gestionobras.max_lineas_lote')` (20 por defecto) |
| `trabajador_ids.*` | existe y no se repite (`distinct`) |
| `horas` | obligatorio, 0–24 |

Cada línea copia la tarifa de su trabajador. **Todo o nada**: se valida antes de crear y se crea en una transacción. Respuesta: **201** con `{ data: [ líneas ] }`, sin paginar.

### Partes de proveedor y de especialista

`apiResource('obras.partes-proveedor')` y `apiResource('obras.partes-especialista')` · filtros: `proveedor_id`/`especialista_id`, `estado_pago`

| Campo | Validación |
|:--|:--|
| `proveedor_id` / `especialista_id` | obligatorio, existe |
| `importe` | obligatorio, **> 0** |
| `estado_pago` | `pendiente` (por defecto) o `pagado` |
| `fecha`, `descripcion`, `documento` | opcionales (no siempre hay factura) |

## Bitácoras y adjuntos

Rutas genéricas por tipo de entidad. El tipo es el segmento de la URL y coincide con el **mapa polimórfico** (`Relation::enforceMorphMap`), que también se guarda en `*_type`.

| Bitácora | Entidades |
|:--|:--|
| Observaciones | `clientes`, `trabajadores`, `proveedores`, `especialistas`, `obras`, `presupuestos`, `ingresos` |
| Incidencias | `obras`, `presupuestos`, `partes-trabajo`, `partes-proveedor`, `partes-especialista` |
| Archivos | `trabajadores`, `proveedores`, `especialistas`, `obras`, `presupuestos`, `gastos`, `ingresos`, `partes-proveedor`, `partes-especialista` |

| Método | Ruta | Detalle |
|:--|:--|:--|
| GET / POST | `/{tipo}/{id}/observaciones` | `{ texto }` obligatorio. El autor (`user_id`) es siempre el usuario autenticado. Orden: más nueva primero |
| GET | `/user/observaciones` | Observaciones escritas por el **usuario autenticado**, más nueva primero (paginado) |
| DELETE | `/observaciones/{observacion}` | **204**. Solo su autor, el rol `jefe` o el administrador; si no, **403** |
| GET / POST | `/{tipo}/{id}/incidencias` | Igual que observaciones |
| DELETE | `/incidencias/{incidencia}` | **204**. Mismos permisos que las observaciones |
| GET / POST | `/{tipo}/{id}/archivos` | Multipart `archivo` (obligatorio, máx. 10 MB). `tipo`: `imagen`, `pdf` u `otro` según el MIME |
| GET | `/archivos/{archivo}/descarga` | Descarga con el nombre original |
| DELETE | `/archivos/{archivo}` | Borra la fila **y el fichero** |

Una entidad fuera de su lista → **404**. Respuesta de bitácora: `id, texto, user_id, usuario, created_at`. Respuesta de archivo: `id, nombre_archivo, tipo, created_at`.
Borrar cualquier entidad borra sus observaciones, incidencias y archivos (también los ficheros del disco).

## Estadísticas y calendario

GET `/estadisticas?periodo=este_mes|tres_meses|este_anio|todo` (por defecto `todo`; otro valor → **422**):

```json
{ "data": {
  "periodo": "este_mes", "desde": "2026-11-01",
  "resumen": { "num_obras", "total_presupuestado", "ingresos", "coste_proveedores", "coste_especialistas",
               "coste_moo", "gastos", "beneficio", "horas", "pendiente_cobro" },
  "top_obras":          [{ "obra_id", "codigo", "descripcion", "ingresos", "gastos", "beneficio" }],
  "cobros_pendientes":  [{ "obra_id", "codigo", "descripcion", "total", "cobrado", "debe" }],
  "top_trabajadores":   [{ "trabajador_id", "nombre_completo", "horas", "coste" }],
  "evolucion_mensual":  [{ "anio", "mes", "ingresos", "obras_iniciadas", "obras_finalizadas" }]
}}
```

- `desde`: `este_mes` → día 1 del mes; `tres_meses` → hoy menos 3 meses; `este_anio` → 1 de enero; `todo` → `null`.
- Ingresos, costes, horas, `top_obras` (10, por beneficio desc.) y `top_trabajadores` (5, por horas desc.) se filtran por la **fecha de cada movimiento** (ingreso o parte).
- `num_obras`, `total_presupuestado`, `pendiente_cobro` (Σ de los `debe` positivos) y `cobros_pendientes` (obras con `debe > 0`, de mayor a menor) son **históricos**.
- `evolucion_mensual` ignora el periodo: ingresos por mes de su fecha, obras iniciadas y finalizadas por `inicio_obra` y `fin_obra` de su ficha de Gastos, en orden cronológico.

GET `/calendario?desde=&hasta=` (ambas obligatorias, `hasta ≥ desde`) → `{ data: [{ tipo, fecha, titulo, modelo, id }] }`, ordenado por fecha y tipo. Tipos: `presupuesto`, `ingreso`, `parte_trabajo`, `parte_proveedor`, `parte_especialista`; `modelo` es la clave del mapa polimórfico.

## Modelos

Los tests de modelo comprueban `$fillable` **en este orden exacto**, la tabla y las relaciones.

| Modelo | `$fillable` | Relaciones |
|:--|:--|:--|
| `Cliente` | `nombre, apellidos, nif, direccion, cod_postal, telefono` | `presupuestos`, `obras`, `ingresos` (HasMany), `observaciones` (MorphMany) |
| `Trabajador` | `tipo, nombre, apellido, telefono, coste_hora_estandar` | `lineasTrabajo`, `faltas` (HasMany), `observaciones`, `archivos` (MorphMany) |
| `Proveedor` | `nombre, direccion, telefono, correo, tipo, referencia, documento, num_documento, base, iva` | `partes` (HasMany), `presupuestos` (BelongsToMany), `observaciones`, `archivos` |
| `Especialista` | `tipo, referencia, comunicacion_parte, nombre, notas, telefono, correo, importe` | `partes`, `presupuestos`, `observaciones`, `archivos` |
| `Obra` | `descripcion, cliente_id` | `cliente` (BelongsTo), `gasto` (HasOne), `presupuestos`, `ingresos`, `partesTrabajo`, `partesProveedor`, `partesEspecialista` (HasMany), `observaciones`, `incidencias`, `archivos` |
| `Presupuesto` | `fecha, cliente_id, obra_id, nombre_cliente, nif, direccion, telefono, iva, estado` | `cliente`, `obra`, `lineas`, `proveedores`, `especialistas`, `observaciones`, `incidencias`, `archivos` |
| `LineaPresupuesto` | `presupuesto_id, descripcion, uds, precio` | `presupuesto` |
| `Gasto` | `obra_id, presupuesto_id, direccion_obra, inicio_obra, fin_obra, solicitud_obra, mandante, gestion_licencia, pago_icio` | `obra`, `presupuesto`, `archivos` |
| `Ingreso` | `obra_id, cliente_id, nombre_obra, tipo, fecha, documento, num_documento, importe` | `obra`, `cliente`, `observaciones`, `archivos` |
| `ParteTrabajo` | `obra_id, fecha, descripcion` | `obra`, `lineas`, `incidencias` |
| `LineaParteTrabajo` | `parte_trabajo_id, trabajador_id, horas, coste_hora` | `parte` (BelongsTo `ParteTrabajo`), `trabajador` |
| `ParteProveedor` | `obra_id, proveedor_id, fecha, descripcion, documento, importe, estado_pago` | `obra`, `proveedor`, `archivos`, `incidencias` |
| `ParteEspecialista` | `obra_id, especialista_id, fecha, descripcion, documento, importe, estado_pago` | `obra`, `especialista`, `archivos`, `incidencias` |
| `Falta` | `trabajador_id, fecha_inicio, fecha_fin, tipo, motivo` | `trabajador` |
| `Observacion` | `texto, user_id` | `observable` (MorphTo), `usuario` (BelongsTo `User`) |
| `Incidencia` | `texto, user_id` | `incidentable` (MorphTo), `usuario` |
| `Archivo` | `nombre_archivo, ruta, tipo` | `archivable` (MorphTo) |

Además, `Obra` tiene `lineasTrabajo()` (HasManyThrough) y los métodos `trabajadoresParticipantes()`, `proveedoresParticipantes()` y `especialistasParticipantes()`, y `Trabajador`, `Proveedor` y `Especialista` tienen `obrasParticipadas()`; todos devuelven un `Builder`.

`User` tiene `rol` en su `$fillable`, oculta `password` y `remember_token`, y define `esAdministrador()`, `tieneRol()` y `roles()`.

### Factories

Todos los modelos de la tabla tienen factory salvo `Gasto`, `Observacion`, `Incidencia` y `Archivo`. Requisitos:

- Toda obra creada con `Obra::factory()` tiene su ficha de Gastos, igual que con `ObraService::crear()` (con `afterCreating` en la factory o con un evento del modelo).
- `PresupuestoFactory` no rellena `nombre_cliente`: se copia del cliente al crear.
- `LineaParteTrabajoFactory` no rellena `coste_hora`: se copia del trabajador.
- `UserFactory` acepta `rol` (por defecto `null`).
