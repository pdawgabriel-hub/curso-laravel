---
title: Inicio
nav_order: 1
permalink: /
---

# Gestión de Obras con Laravel
{: .fs-9 }

Construcción paso a paso, con Laravel 13, de un ERP para una empresa de construcción y reformas: clientes, presupuestos, obras, mano de obra, proveedores, especialistas, cobros y seguimiento financiero de cada obra.
{: .fs-6 .fw-300 }

[Empezar por el entorno](00_entorno.html){: .btn .btn-primary .fs-5 .mb-4 .mb-md-0 .mr-2 }
[Contrato de la API](contrato-api.html){: .btn .fs-5 .mb-4 .mb-md-0 }

---

## Qué se construye

La aplicación reproduce en Laravel un módulo real de Odoo (`gestion_bd`). Su informe técnico es la especificación funcional: las fórmulas, validaciones y decisiones de diseño de [De Odoo a Laravel](004_de_odoo_a_laravel.html) salen de él.

```
Cliente → Presupuesto (líneas + IVA) → Obra ─┬─ Partes de Trabajo (horas × tarifa)
                                              ├─ Partes de Proveedor
                                              ├─ Partes de Especialista
                                              └─ Ingresos (cobros)
                                                        ↓
                                  Gastos: ficha 1:1 de cada obra con su balance real
```

El resultado final es una aplicación con:

- Interfaz web con Blade para el uso diario (Bloques 5 y 6).
- Una API REST versionada (`/api/v1`) con Sanctum, Policies y roles (Bloque 7).
- PDF, correo, panel de análisis con gráficos, calendario y consumo de una API externa (Bloque 9).

## Organización

Los bloques siguen la numeración de los resultados de aprendizaje (RA) del módulo **Desarrollo Web en Entorno Servidor**, y cada apartado enlaza con su referencia teórica.

| Bloque | Contenido | Horas orientativas |
|:--|:--|--:|
| [0. Entorno y forma de trabajo](00_entorno.html) | Laradock, Git, tests y CI, equivalencias Odoo → Laravel | 6 |
| [5. Introducción a Laravel](05_laravel.html) | Rutas, Blade, controladores, formularios, Breeze, patrones | 26 |
| [6. Acceso a datos](06_basesDatos.html) | Migraciones, Eloquent, seeders, relaciones, cálculos, transacciones | 32 |
| [7. Servicios web](07_api.html) | API REST, búsqueda, Sanctum, autorización, ficheros, OpenAPI | 28 |
| [8. Frontend dinámico](08_frontend.html) *(opcional)* | React-Admin sobre la API | 6 |
| [9. Aplicaciones híbridas](09_servicios.html) | PDF, correo, dashboard, calendario, API externa | 12 |
| [Anexo. Escenario completo](anexo_escenario.html) | El ciclo de una obra de principio a fin | — |

## Cómo se trabaja cada apartado

Todas las páginas de ejercicios tienen la misma estructura:

1. **Objetivo**: qué parte de la aplicación se construye.
2. **Referencia teórica**: enlace a los apuntes del módulo y a la documentación de Laravel.
3. **Conceptos clave**: resumen breve, aplicado a este proyecto.
4. **Ejercicios**: numerados, sobre la aplicación.
5. **Comprobación**: los tests del apartado que hay que poner en verde.
6. **Git**: rama, commits y cierre del apartado.

La [evaluación final](evaluacion.html) consiste en pasar una batería completa de tests sobre la API, con el mismo formato que los tests de los apartados.

## Dos repositorios

| Repositorio | Contenido |
|:--|:--|
| `curso-laravel` (este) | Apuntes, plantillas, tests de cada apartado y batería de evaluación |
| `gestion-obras-laravel` | La aplicación, creada en el apartado [5.1](RA5_1_instalacion.html) |
