# Gestión de Obras con Laravel

Construcción paso a paso, con _Laravel_ 13, de un ERP para una empresa de construcción y reformas: clientes, presupuestos, obras, mano de obra propia, proveedores, especialistas, cobros y seguimiento financiero de cada obra. La aplicación reproduce un módulo real de _Odoo_ (`gestion_bd`), cuyo informe técnico es la especificación funcional.

> **Estructura por Resultados de Aprendizaje.** Los bloques siguen la numeración de los RA del módulo **Desarrollo Web en Entorno Servidor**, de modo que el Bloque _n_ corresponde al RA _n_. Los bloques 1 a 4 (arquitecturas y PHP plano) no se incluyen: la aplicación empieza directamente en _Laravel_. Cada apartado enlaza con su **referencia teórica** en los apuntes del módulo.
>
> **Cada apartado** tiene sus ejercicios sobre la aplicación, un test para comprobarlos (`RA5_3_RutasTest.php`…) y su rama de Git. La [evaluación](./evaluacion.md) final es una batería de tests sobre la API.

**Antes de empezar:** prepara el entorno (Docker + Laradock) siguiendo el [Bloque 0](#bloque-0-entorno-y-forma-de-trabajo).

| Bloque | Contenido | Horas |
|-------:|-----------|------:|
| 0 | Entorno y forma de trabajo | 6 |
| 5 | Generación dinámica de páginas web: introducción a _Laravel_ (RA5) | 26 |
| 6 | Acceso a almacenes de datos (RA6) | 32 |
| 7 | Servicios web reutilizables (RA7) | 28 |
| 8 | Aplicaciones web dinámicas (RA8) — _opcional_ | 6 |
| 9 | Aplicaciones web híbridas (RA9) | 12 |

```
Cliente → Presupuesto (líneas + IVA) → Obra ─┬─ Partes de Trabajo (horas × tarifa)
                                              ├─ Partes de Proveedor
                                              ├─ Partes de Especialista
                                              └─ Ingresos (cobros)
                                                        ↓
                                  Gastos: ficha 1:1 de cada obra con su balance real
```

---

## Bloque 0. Entorno y forma de trabajo

Todo lo que se usa durante el proyecto: el entorno, el flujo de Git, los tests y la correspondencia entre el módulo de _Odoo_ y _Laravel_.

1. [Entorno con Laradock](./001_laradock.md)
2. [Git y GitHub: ramas por apartado, _commits_, _pull requests_ y _tags_](./002_git.md)
3. [Tests e integración continua](./003_tests.md)
4. [De _Odoo_ a _Laravel_: modelos, reglas de negocio y decisiones de diseño](./004_de_odoo_a_laravel.md)

## Bloque 5. Generación dinámica de páginas web: introducción a Laravel (RA5)

Primera versión de la aplicación, **sin base de datos**: los datos salen de arrays.

1. [Instalación](./RA5_1_instalacion.md)
2. [Estructura del proyecto y configuración propia](./RA5_2_estructura.md)
3. [Rutas](./RA5_3_rutas.md)
4. [Vistas, _Blade_ y _layouts_](./RA5_4_vistas.md)
5. [Controladores](./RA5_5_controladores.md)
6. [Formularios, validación con _Form Requests_ y redirecciones](./RA5_6_formularios.md)
7. [_Middleware_ y control de usuarios con _Breeze_](./RA5_7_breeze.md)
8. _Proyecto:_ [POO y patrones de diseño (MVC, repositorio, _Singleton_, DAO)](./RA5_8_proyecto.md)

## Bloque 6. Acceso a almacenes de datos (RA6)

Los arrays se sustituyen por MariaDB y _Eloquent_.

1. [Migraciones y _Schema Builder_](./RA6_1_migraciones.md)
2. [Modelos _Eloquent_ y códigos correlativos](./RA6_2_modelos.md)
3. [_Factories_ y _seeders_: datos de demostración](./RA6_3_seeders.md)
4. [Constructor de consultas (_Query Builder_)](./RA6_4_queryBuilder.md)
5. [CRUD web con base de datos](./RA6_5_crud.md)
6. [Relaciones entre modelos](./RA6_6_relaciones.md)
7. [Cálculos financieros](./RA6_7_calculos.md)
8. [Transacciones y bloqueos](./RA6_8_transacciones.md)
9. [Bitácoras y adjuntos: relaciones polimórficas y ficheros](./RA6_9_bitacoras.md)
10. _Proyecto:_ [flujo completo de una obra por la web](./RA6_10_proyecto.md)

## Bloque 7. Servicios web reutilizables (RA7)

API REST según el [contrato de la API](./contrato-api.md).

1. [API REST y controladores de recursos](./RA7_1_recursos.md)
2. [Búsqueda, orden y paginación](./RA7_2_busqueda.md)
3. [Autenticación con _Sanctum_](./RA7_3_sanctum.md)
4. [Autorización: _Gates_, _Policies_ y roles](./RA7_4_autorizacion.md)
5. [Ficheros y bitácoras en la API](./RA7_5_ficheros.md)
6. [Documentación OpenAPI](./RA7_6_openapi.md)

Al terminar el bloque: [evaluación](./evaluacion.md).

## Bloque 8. Aplicaciones web dinámicas (RA8) — opcional

La interfaz _Blade_ cubre toda la funcionalidad; este bloque añade una segunda interfaz que consume la API.

1. [_React-Admin_ sobre la API](./RA8_1_reactAdmin.md)

## Bloque 9. Aplicaciones web híbridas (RA9)

1. [Documentos PDF](./RA9_1_pdf.md)
2. [Correo electrónico](./RA9_2_correo.md)
3. [Panel de análisis financiero con _Chart.js_](./RA9_3_dashboard.md)
4. [Calendario unificado](./RA9_4_calendario.md)
5. [Consumir una API externa](./RA9_5_apiExterna.md)

## Anexos

- [Contrato de la API](./contrato-api.md)
- [Evaluación](./evaluacion.md)
- [Escenario de prueba completo](./anexo_escenario.md)

| Repositorio | Contenido |
|:--|:--|
| `curso-laravel` (este) | Apuntes, plantillas, tests de cada apartado y batería de evaluación |
| `gestion-obras-laravel` | La aplicación, creada en el apartado [5.1](./RA5_1_instalacion.md) |
