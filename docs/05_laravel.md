---
title: Bloque 5. Introducción a Laravel
nav_order: 3
has_children: true
permalink: /05_laravel.html
---

# Bloque 5. Introducción a Laravel (RA5)

Se crea la aplicación y se construye su primera versión **sin base de datos**: rutas, vistas Blade con un layout común, controladores que sirven datos desde arrays, formularios validados con Form Requests, autenticación con Breeze y una primera separación en capas con repositorios.

| Apartado | Resultado | Test |
|:--|:--|:--|
| [5.1. Instalación](RA5_1_instalacion.html) | Proyecto Laravel 13 en GitHub y en `gestion-obras.test` | — |
| [5.2. Estructura y configuración](RA5_2_estructura.html) | `config/gestionobras.php`, idioma y zona horaria | `RA5_2_ConfiguracionTest` |
| [5.3. Rutas](RA5_3_rutas.html) | Rutas de clientes y obras con closures | `RA5_3_RutasTest` |
| [5.4. Vistas y Blade](RA5_4_vistas.html) | Componente `<x-layout>` y vistas de clientes y obras | `RA5_4_VistasTest` |
| [5.5. Controladores](RA5_5_controladores.html) | `ClientesController`, `ObrasController`, `TrabajadoresController` | `RA5_5_ControladoresTest` |
| [5.6. Formularios y validación](RA5_6_formularios.html) | Alta y edición con Form Requests | `RA5_6_FormulariosTest` |
| [5.7. Breeze y middleware](RA5_7_breeze.html) | Login; crear y modificar exige sesión | `RA5_7_AutenticacionTest` |
| [5.8. Proyecto: patrones](RA5_8_proyecto.html) | Repositorios con inyección de dependencias y lógica de negocio fuera de las vistas | `RA5_8_ProyectoTest` |

Al terminar el bloque: tag `bloque-5`.
