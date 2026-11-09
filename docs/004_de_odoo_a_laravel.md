# 0.4. De Odoo a Laravel

La especificación funcional de la aplicación es el informe técnico del módulo `gestion_bd` de Odoo 18. Esta página resume qué se conserva, qué cambia de nombre y cómo se traduce cada mecanismo de Odoo a Laravel.

## Modelos y tablas

| Odoo (`_name`) | Modelo Laravel | Tabla | Código |
|:--|:--|:--|:--|
| `gestion.clientes` | `Cliente` | `clientes` | `CLI0001` |
| `gestion.trabajadores` | `Trabajador` | `trabajadores` | `TRB0001` |
| `gestion.proveedores` | `Proveedor` | `proveedores` | `PROV0001` |
| `gestion.especialistas` | `Especialista` | `especialistas` | `ESP0001` |
| `gestion.obras` | `Obra` | `obras` | `OBR0001` |
| `gestion.presupuestos` | `Presupuesto` | `presupuestos` | `PREP0001` |
| `gestion.presupuesto.linea` | `LineaPresupuesto` | `lineas_presupuesto` | — |
| `gestion.gastos` | `Gasto` | `gastos` | `GAS0001` |
| `gestion.ingresos` | `Ingreso` | `ingresos` | `ING0001` |
| `gestion.partes.trabajo` | `ParteTrabajo` | `partes_trabajo` | — |
| `gestion.partes.trabajo.linea` | `LineaParteTrabajo` | `lineas_parte_trabajo` | — |
| `gestion.partes.proveedor` | `ParteProveedor` | `partes_proveedor` | — |
| `gestion.partes.especialista` | `ParteEspecialista` | `partes_especialista` | — |
| `gestion.trabajadores.falta` | `Falta` | `faltas` | — |
| `*.observaciones` (7 tablas) | `Observacion` (polimórfico) | `observaciones` | — |
| `*.incidencias` (5 tablas) | `Incidencia` (polimórfico) | `incidencias` | — |
| `*.archivo` (9 tablas) | `Archivo` (polimórfico) | `archivos` | — |
| `ir.sequence` | `Secuencia` | `secuencias` | — |
| `gestion.calendario.evento` (VIEW) | servicio `Calendario` | — | — |
| `gestion.dashboard` | servicio `Estadisticas` | — | — |
| `res.users` + grupos | `User` con columna `rol` | `users` | — |

> **Nota.** Los nombres de tabla en plural español no coinciden con los que deduce Laravel (`Trabajador` → `trabajadors`), así que todos los modelos declaran `protected $table`.

## Cambios de nombre

| Odoo | Laravel | Motivo |
|:--|:--|:--|
| `cliente_id`, `obra_id`… (código `CLI0001`) | `codigo` | En Laravel `*_id` es siempre una clave foránea |
| `telf` | `telefono` | Legibilidad |
| `ref` | `referencia` | Legibilidad |
| `especialistas.observaciones` (texto) | `notas` | Evita el choque con la relación `observaciones()` |
| `mandante`, `gestion_licencia`, `pago_icio` (sí/no) | `boolean` | Tipo nativo |
| `gastos.coste_moo_total` y `coste_moo` | `coste_moo` | Eran el mismo valor |
| `proveedores.nombre` (opcional) | obligatorio | Un proveedor sin nombre no se puede identificar en los selectores |
| Administrativa y Jefe, con los mismos permisos | [Tabla de permisos](./contrato-api.md#permisos): obras, ingresos, gastos, aprobar y borrar son del jefe; cada uno gestiona sus partes y faltas | Permite practicar _Policies_ con roles y propietario |
| Líneas de parte, de una en una | Además, alta por lote | Una cuadrilla suele hacer la misma jornada |

## Mecanismos de Odoo y su equivalente

| Odoo | Laravel | Apartado |
|:--|:--|:--|
| `ir.sequence` | Tabla `secuencias` + `lockForUpdate()` en una transacción | [6.12](./RA6_12_transacciones.md) |
| `create()` que asigna el código | Evento `creating` del modelo (trait `TieneCodigo`) | [6.2](./RA6_2_modelos.md) |
| `Obras.create()` que crea su Gastos | `ObraService::crear()` en una transacción | [6.12](./RA6_12_transacciones.md) |
| `Obras.unlink()` con sus reglas | `ObraService::borrar()` + `ReglaNegocioException` (409) | [6.12](./RA6_12_transacciones.md) |
| `compute=... store=True` | Accessors o servicio `ResumenFinanciero` calculados al leer | [6.13](./RA6_13_calculos.md) |
| `related='cliente_id.nombre'` | Relación + accessor, o `with()` | [6.8](./RA6_8_relaciones.md) |
| `@api.onchange` (copiar datos del cliente) | Evento `creating` del modelo | [6.2](./RA6_2_modelos.md) |
| `@api.constrains`, `_sql_constraints` | Reglas de validación + `unique`/`foreign` en migraciones | [5.6](./RA5_6_formularios.md), [6.1](./RA6_1_migraciones.md) |
| `_search_display_name` | Parámetro `search` multipalabra en los listados | [7.4](./RA7_4_busqueda.md) |
| `ir.model.access.csv` + grupos | Policies + `Gate::before` para el administrador | [7.6](./RA7_6_autorizacion.md) |
| `post_init_hook` (datos de demo) | `DatabaseSeeder`, una semilla por tabla y factories | [6.3](./RA6_3_seeders.md) |
| `TransientModel` (asistente) | Endpoint `POST /obras/{obra}/vincular-presupuesto` | [7.3](./RA7_3_controladores.md) |
| Vistas list/form/kanban | Vistas Blade | [5.4](./RA5_4_vistas.md) |
| Reportes QWeb | Vistas Blade → PDF con dompdf | [9.1](./RA9_1_pdf.md) |
| Componente OWL del dashboard | Vista Blade + Chart.js sobre el servicio `Estadisticas` | [9.3](./RA9_3_dashboard.md) |
| Modelo `_auto = False` (VIEW SQL) | Servicio que une cinco consultas | [9.4](./RA9_4_calendario.md) |

## Reglas de negocio

Todas las cantidades se redondean a 2 decimales, salvo `porcentaje_cobrado`, que se redondea a 1.

**Presupuesto**

```
linea.total_linea     = uds × precio
base_imponible        = Σ total_linea
cuota_iva             = base_imponible × iva / 100        (iva por defecto 21)
total                 = base_imponible + cuota_iva
```

**Obra**

```
total               = Σ total de sus presupuestos con estado 'aprobado'      (0 si no hay)
horas_totales       = Σ horas de las líneas de todos sus partes de trabajo
coste_moo           = Σ horas × coste_hora de esas líneas
gastos_totales      = coste_proveedores + coste_especialistas + coste_moo
ingresos            = Σ importe de sus ingresos
beneficio           = ingresos − gastos_totales          ← caja real, nunca total − gastos
pendiente_cobro     = total − ingresos                   (puede ser negativo)
margen_proyectado   = total − gastos_totales
porcentaje_cobrado  = acotar((total − pendiente_cobro) × 100 / total, 0, 100)   (0 si total = 0)
estado_pago         = 'pendiente' si ingresos ≤ 0 · 'parcial' si ingresos < total · 'pagado' en otro caso
salud               = 'rojo' si beneficio < 0 · 'verde' si estado_pago = 'pagado' · 'ambar' en otro caso
```

**Ficha de Gastos** (1:1 con la obra): los mismos importes, más

```
coste_hora  = coste_moo / horas      (0 si no hay horas)
estado      = 'finalizado' si (total − ingresos ≤ 0 y ingresos > 0) · 'proceso' en otro caso
```

**Otros**

```
proveedor.total    = base × (1 + iva / 100)
falta.dias         = (fecha_fin − fecha_inicio) + 1
ingreso.total_ingresos = Σ importe de todos los ingresos de su obra;  ingreso.debe = obra.total − total_ingresos
```

## Decisiones de diseño que se conservan

1. **El precio de la obra no se escribe a mano**: sale de sus presupuestos aprobados.
2. **Beneficio = caja**: `ingresos − gastos`. La previsión es un indicador aparte (`margen_proyectado`).
3. **La tarifa se congela en cada línea de parte**: al crear la línea se copia el `coste_hora_estandar` del trabajador.
4. **Los datos del cliente se copian en el presupuesto** al crearlo, para que un presupuesto entregado no cambie.
5. **La ficha de Gastos nace y muere con la obra**: no se crea ni se borra por separado.
6. **Los participantes de una obra se deducen** de sus partes (trabajadores, proveedores, especialistas).
7. **`debe` y los indicadores siempre en vivo**: se calculan al leer.
8. **Códigos legibles y correlativos** (`OBR0001`) que no se reutilizan.
9. **Bitácoras con autor y fecha automáticos**, de la más nueva a la más antigua.
10. **Búsqueda por lo que la gente recuerda**: "Juan Pérez" encuentra al cliente aunque nombre y apellidos estén en columnas distintas.
11. **Filtros de fecha por la fecha real de cada movimiento**; el pendiente de cobro y la evolución mensual ignoran el periodo.
12. **Borrar una obra borra lo que solo existe por ella** y desvincula sus presupuestos; **una obra con cobros no se borra**.
13. **Cada presupuesto e ingreso pertenece al cliente de su obra**.
14. **Borrar un registro no deja restos**: sus bitácoras y sus ficheros adjuntos desaparecen con él.

## Validaciones

| Regla | Dónde |
|:--|:--|
| Códigos únicos; NIF único (puede quedar vacío) | Clientes y todas las entidades con código |
| Una ficha de Gastos por obra (`UNIQUE(obra_id)`) | Gastos |
| `0 ≤ iva ≤ 100` | Presupuestos, Proveedores |
| `coste_hora_estandar ≥ 0`, `base ≥ 0`, `importe ≥ 0` | Trabajadores, Proveedores, Especialistas |
| `importe > 0` | Partes de proveedor y especialista, Ingresos |
| `0 ≤ horas ≤ 24` | Líneas de parte de trabajo |
| `fecha_fin ≥ fecha_inicio` | Faltas; `fin_obra ≥ inicio_obra` en Gastos |
| No se borra un cliente con presupuestos, un trabajador con partes ni un proveedor o especialista con partes | Restricción de borrado → 409 |

---

**Siguiente:** [5.1. Instalación](./RA5_1_instalacion.md)
