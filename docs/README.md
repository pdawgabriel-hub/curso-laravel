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

1. [Configuración, migraciones y _Schema Builder_](./RA6_1_migraciones.md): todas las tablas
2. [Modelos de datos mediante ORM](./RA6_2_modelos.md): todos los modelos
3. [Inicialización de la base de datos: _seeders_ y _factories_](./RA6_3_seeders.md): todas las _factories_ y los datos de demostración
4. [Constructor de consultas (_Query Builder_)](./RA6_4_queryBuilder.md)
5. [Interfaz con Blade: layout y componentes](./RA6_5_interfaz.md)
6. Ejercicios: [los maestros en la base de datos](./RA6_6_maestros.md)
7. [Usuarios: rol, administrador y semilla](./RA6_7_usuarios.md)
8. [Relaciones entre modelos](./RA6_8_relaciones.md)
9. Ejercicios de relaciones: [obras, ficha de Gastos y faltas](./RA6_9_obras.md) · [presupuestos](./RA6_10_presupuestos.md) · [partes e ingresos](./RA6_11_partes.md)
10. [Transacciones y bloqueos](./RA6_12_transacciones.md)
11. [Cálculos financieros](./RA6_13_calculos.md)
12. [Bitácoras y adjuntos: relaciones polimórficas y ficheros](./RA6_14_bitacoras.md)
13. _Proyecto:_ [flujo completo de una obra por la web](./RA6_15_proyecto.md)

## Bloque 7. Servicios web reutilizables (RA7)

API REST según el [contrato de la API](./contrato-api.md).

1. [Introducción a API y REST. _Swagger_](./RA7_1_swagger.md)
2. [Esqueleto de la API y _API Resources_](./RA7_2_recursos.md)
3. [Controladores de recursos](./RA7_3_controladores.md)
4. [Búsqueda, orden y paginación](./RA7_4_busqueda.md)
5. [Autenticación con _Sanctum_](./RA7_5_sanctum.md)
6. [Autorización: _Gates_, _Policies_ y roles](./RA7_6_autorizacion.md)
7. [Ficheros y bitácoras en la API](./RA7_7_ficheros.md)
8. _Opcional:_ [_PHP-CRUD-API_ y sesiones en la API](./RA7_8_opcional.md)

Al terminar el bloque: [evaluación](./evaluacion.md).

## Bloque 8. Aplicaciones web dinámicas (RA8)

En una rama aparte, `react-admin`: el servidor solo envía datos y la interfaz es _React-Admin_ en _TypeScript_. La rama `main` conserva la interfaz _Blade_.

1. [Una rama sin vistas](./RA8_1_breezeApi.md)
2. [_React-Admin_ con _Inertia_ y _TypeScript_](./RA8_2_reactAdmin.md)
3. [_Middleware_ para _React-Admin_](./RA8_3_middleware.md)
4. [_dataProvider_ y _authProvider_](./RA8_4_proveedores.md)
5. [Los recursos](./RA8_5_recursos.md)
6. [Los registros que cuelgan de otro](./RA8_6_anidados.md)
7. _Proyecto:_ [la obra completa en _React-Admin_](./RA8_7_proyecto.md)

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
