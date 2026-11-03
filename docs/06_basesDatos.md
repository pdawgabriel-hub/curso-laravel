---
title: Bloque 6. Acceso a datos
nav_order: 4
has_children: true
permalink: /06_basesDatos.html
---

# Bloque 6. Acceso a almacenes de datos (RA6)

Los arrays de prueba se sustituyen por MariaDB y Eloquent. Se crea el esquema completo de la aplicación, los modelos con sus relaciones, los datos de demostración, los cálculos financieros y las operaciones que deben ser atómicas.

| Apartado | Resultado | Test |
|:--|:--|:--|
| [6.1. Migraciones](RA6_1_migraciones.html) | Las 20 tablas del esquema | `RA6_1_MigracionesTest` |
| [6.2. Modelos Eloquent](RA6_2_modelos.html) | 17 modelos con `$fillable`, casts y códigos automáticos | `Unit/Models/*` |
| [6.3. Factories y seeders](RA6_3_seeders.html) | Datos de demostración coherentes | `RA6_3_SeedersTest` |
| [6.4. Query Builder](RA6_4_queryBuilder.html) | Consultas de negocio | Comprobación manual |
| [6.5. CRUD web con base de datos](RA6_5_crud.html) | Las pantallas guardan y borran de verdad | `RA6_5_CrudWebTest` |
| [6.6. Relaciones](RA6_6_relaciones.html) | 1:1, 1:N, N:M, a través de y deducidas | `RA6_6_RelacionesTest` |
| [6.7. Cálculos financieros](RA6_7_calculos.html) | Servicio `ResumenFinanciero` | `RA6_7_CalculosTest` |
| [6.8. Transacciones y bloqueos](RA6_8_transacciones.html) | `ObraService` y secuencias sin duplicados | `RA6_8_TransaccionesTest` |
| [6.9. Bitácoras y adjuntos](RA6_9_bitacoras.html) | Relaciones polimórficas y ficheros | `RA6_9_BitacorasTest` |

Al terminar el bloque: tag `bloque-6`.
