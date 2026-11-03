---
title: 6.6. Relaciones
parent: Bloque 6. Acceso a datos
nav_order: 6
---

# 6.6. Relaciones entre modelos
{: .no_toc }

{: .nota }
> **Referencia teórica:** [Relaciones](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/08_relaciones.html) · [Uno a uno](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/081_relaciones_unoAuno.html) · [Uno a muchos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/082_relaciones_unoAmuchos.html) · [Muchos a muchos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/083_relaciones_muchosAmuchos.html) · [Insertar y actualizar con relaciones](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/084_DML_Relaciones.html)

1. TOC
{:toc}

## Objetivo

Definir todas las relaciones del [contrato](contrato-api.html#modelos) y las reglas que dependen de ellas: copia de datos del cliente, tarifa congelada y participantes deducidos.

## Conceptos clave

| Tipo | Ejemplo en el proyecto |
|:--|:--|
| 1:1 | `Obra::gasto()` (`hasOne`) ↔ `Gasto::obra()` (`belongsTo`) |
| 1:N | `Cliente::obras()`, `Obra::partesTrabajo()`, `Presupuesto::lineas()` |
| N:M | `Presupuesto::proveedores()` ↔ `Proveedor::presupuestos()` (`belongsToMany`, pivote `presupuesto_proveedor`) |
| A través de | `Obra::lineasTrabajo()` (`hasManyThrough` vía `ParteTrabajo`) |
| Deducida | `Obra::trabajadoresParticipantes()`: `Trabajador::whereHas('lineasTrabajo.parte', …)` |

- `with('cliente')` (*eager loading*) evita el problema N+1 en los listados.
- `attach`, `detach` y `sync` gestionan la tabla pivote.
- Cuando el nombre de la clave foránea no sigue la convención, se indica: `belongsTo(ParteTrabajo::class, 'parte_trabajo_id')`.

## Ejercicios

### Ejercicio 1. Relaciones del contrato

Añadir a cada modelo los métodos de relación de la tabla del contrato con su tipo de retorno (`: HasMany`, `: BelongsTo`…).

### Ejercicio 2. Relaciones deducidas

- `Obra::trabajadoresParticipantes()`, `proveedoresParticipantes()` y `especialistasParticipantes()` devuelven un `Builder` con los registros distintos que aparecen en sus partes.
- `Trabajador::obrasParticipadas()`, `Proveedor::obrasParticipadas()` y `Especialista::obrasParticipadas()` hacen lo inverso.

En el detalle de una obra, pestañas o secciones con sus trabajadores, proveedores y especialistas; en el detalle de un trabajador, sus obras.

### Ejercicio 3. Reglas al crear

- **Presupuesto**: en `creating`, si no se han indicado, copiar del cliente `nombre_cliente` (nombre completo), `nif`, `direccion` y `telefono`; la fecha por defecto es hoy.
- **LineaParteTrabajo**: en `creating`, si no se indica `coste_hora`, copiar `coste_hora_estandar` del trabajador. Cambiar después la tarifa no modifica las líneas existentes.
- **Ingreso**: si no se indican, `cliente_id` y `nombre_obra` se toman de la obra.

### Ejercicio 4. N:M en los formularios

En el formulario de presupuesto, casillas para elegir proveedores y especialistas (`sync`). En la ficha del proveedor, la lista de presupuestos en los que aparece.

## Comprobación

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_6_RelacionesTest.php tests/Feature/
php artisan test --filter=RA6_6
php artisan test tests/Unit/Models
```

Resultado esperado: `RA6_6`: `Tests: 7 passed`; `tests/Unit/Models`: `Tests: 108 passed`.

## Git

Rama `RA6_6`. Commits: `feat: relaciones de los modelos`, `feat: participantes deducidos de los partes`, `feat: copia de datos del cliente y tarifa congelada`.
