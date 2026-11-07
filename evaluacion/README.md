# Batería de evaluación

Tests de la API y de los modelos según el [contrato de la API](../docs/contrato-api.md). Instrucciones completas en la página [Evaluación](https://pdawgabriel-hub.github.io/curso-laravel/evaluacion.html).

```bash
# Desde la raíz de la aplicación, en una rama nueva
git switch -c evaluacion
cp -r /var/www/curso-laravel/evaluacion/tests/. tests/
php artisan test --testsuite=Unit
php artisan test tests/Feature/Api tests/Feature/Escenario
```

| Carpeta | Contenido |
|---|---|
| `TestCase.php`, `Feature/FeatureTestCase.php` | Clases base con `actuarComo()`, `actuarComoAdministrador()`, `assertImporte()`, `assertListado()` y `assertPaginado()` |
| `Unit/Models/`, `Unit/UserTest.php` | `$fillable`, tabla, relaciones y factory de cada modelo; métodos de `User` |
| `Feature/Api/` | Un fichero por recurso de la API |
| `Feature/Escenario/` | El ciclo completo de una obra |
