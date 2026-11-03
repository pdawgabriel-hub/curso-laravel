# Gestión de Obras con Laravel

Apuntes para construir paso a paso, con **Laravel 13**, un ERP de obras y reformas: clientes, presupuestos, obras, partes de trabajo, proveedores, especialistas, cobros y seguimiento financiero.

**Apuntes publicados:** https://pdawgabriel-hub.github.io/curso-laravel/

## Contenido del repositorio

| Ruta | Qué es |
|---|---|
| `docs/` | Apuntes (GitHub Pages con el tema por defecto) |
| `docs/materiales/plantillas/` | Código de partida del Bloque 5, organizado por apartado |
| `docs/materiales/tests/` | Tests de cada apartado (`RA5_3_RutasTest.php`…) |
| `docs/materiales/ci/` | Workflow de GitHub Actions para el repositorio de la aplicación |
| `evaluacion/tests/` | Batería de evaluación final de la API |

La aplicación se desarrolla en un repositorio aparte, `gestion-obras-laravel`, que se crea en el apartado 5.1.

## Ver los apuntes en local

```bash
cd docs
bundle init && bundle add github-pages webrick
bundle exec jekyll serve
```

## Publicación

GitHub → *Settings* → *Pages* → *Build and deployment*: **Deploy from a branch**, rama `main`, carpeta `/docs`.
