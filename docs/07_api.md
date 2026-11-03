---
title: Bloque 7. Servicios web
nav_order: 5
has_children: true
permalink: /07_api.html
---

# Bloque 7. Servicios web reutilizables (RA7)

La aplicación expone una API REST versionada, autenticada con Sanctum y autorizada con Policies, que cumple el [contrato de la API](contrato-api.html). Los ejercicios se guían con el recurso **Clientes** y después se aplica el mismo patrón al resto de recursos.

| Apartado | Resultado | Test |
|:--|:--|:--|
| [7.1. API REST y controladores de recursos](RA7_1_recursos.html) | Todos los recursos del contrato con API Resources | `RA7_1_ClientesApiTest` |
| [7.2. Búsqueda, orden y paginación](RA7_2_busqueda.html) | Helper común de listados | `RA7_2_BusquedaApiTest` |
| [7.3. Autenticación con Sanctum](RA7_3_sanctum.html) | Tokens Bearer | `RA7_3_TokensApiTest` |
| [7.4. Autorización](RA7_4_autorizacion.html) | Gates, Policies y roles | `RA7_4_AutorizacionApiTest` |
| [7.5. Ficheros en la API](RA7_5_ficheros.html) | Bitácoras y adjuntos por la API | `RA7_5_ArchivosApiTest` |
| [7.6. Documentación OpenAPI](RA7_6_openapi.html) | `openapi.json` y Swagger UI | `RA7_6_DocumentacionApiTest` |

Al terminar el bloque: tag `bloque-7` y [evaluación](evaluacion.html).
