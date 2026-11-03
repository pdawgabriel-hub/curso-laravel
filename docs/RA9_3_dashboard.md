{% raw %}
# 9.3. Panel de análisis financiero

**Referencia teórica:** [Chart.js](https://www.chartjs.org/docs/latest/) · [Blade: @json / Js::from](https://laravel.com/docs/13.x/blade#rendering-json)

El "Análisis Financiero" del módulo de Odoo: indicadores globales, rentabilidad por obra, distribución de costes, cobros pendientes, trabajadores con más horas y evolución mensual, con un selector de periodo.

## Conceptos clave

- El servicio `App\Services\Estadisticas` de 7.1 calcula los datos; la API los devuelve en JSON y la web los pinta. Un único cálculo para las dos interfaces.
- `Js::from($estadisticas['evolucion_mensual'])` (o `@json`) pasa datos de PHP a JavaScript de forma segura.
- Chart.js desde un CDN: `<canvas id="grafico-evolucion">` + `new Chart(...)`.

## Ejercicios

1. `Estadisticas::PERIODOS = ['este_mes', 'tres_meses', 'este_anio', 'todo']` y `calcular(string $periodo): array`, usado por la API y por la web.
2. `DashboardController` invocable en la ruta `dashboard` (la de Breeze), protegida con `auth`. Recibe `?periodo=`; un valor desconocido se trata como `todo`. Pasa a la vista `periodo` y `estadisticas`.
3. Vista con:
   - selector de periodo (cuatro enlaces o un `<select>` que recarga la página);
   - tarjetas de resumen (ingresos, gastos, beneficio, horas, pendiente de cobro);
   - gráfico de líneas `grafico-evolucion` (ingresos por mes) y de barras (obras iniciadas y finalizadas);
   - gráfico circular con la distribución de costes (proveedores, especialistas, mano de obra);
   - tablas de las 10 obras más rentables, de los cobros pendientes y de los 5 trabajadores con más horas.
4. Formato de moneda en JavaScript: `new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' })`.

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque9/RA9_3_DashboardTest.php tests/Feature/
php artisan test --filter=RA9_3
```

Resultado esperado: `Tests: 5 passed`.

## Git

Rama `RA9_3`, commit `feat: panel de análisis financiero con Chart.js`.
{% endraw %}

---

**Siguiente:** [9.4. Calendario unificado](./RA9_4_calendario.md)
