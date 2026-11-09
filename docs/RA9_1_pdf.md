# 9.1. Documentos PDF
<!-- {% raw %} -->

**Referencia teórica:** [laravel-dompdf](https://github.com/barryvdh/laravel-dompdf) · [Laravel: Responses](https://laravel.com/docs/13.x/responses#file-responses)

Los tres informes del módulo de Odoo: el presupuesto para entregar al cliente, el parte de trabajo y el seguimiento de gastos de la obra.

## Conceptos clave

- `composer require barryvdh/laravel-dompdf` convierte una vista Blade en PDF: `Pdf::loadView('pdf.presupuesto', $datos)->stream('PREP0001.pdf')`.
- dompdf entiende HTML y CSS sencillos (tablas, `position: fixed` para cabecera y pie) y no ejecuta JavaScript ni Tailwind compilado: los estilos van en un `<style>` dentro de la vista.
- `stream()` lo abre en el navegador; `download()` fuerza la descarga.

## El ejemplo: el presupuesto

```php
<?php

namespace App\Http\Controllers;

use App\Models\Presupuesto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class PdfController extends Controller
{
    public function presupuesto(Presupuesto $presupuesto)
    {
        Gate::authorize('view', $presupuesto);

        return Pdf::loadView('pdf.presupuesto', [
            'presupuesto' => $presupuesto->load('lineas'),
            'empresa' => config('gestionobras.empresa'),
        ])->stream("{$presupuesto->codigo}.pdf");
    }
}
```

`resources/views/pdf/presupuesto.blade.php`:

```blade
@php($euros = fn ($x) => number_format($x, 2, ',', '.') . ' €')
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Presupuesto {{ $presupuesto->codigo }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        header { border-bottom: 2px solid #f59e0b; padding-bottom: 8px; margin-bottom: 16px; }
        h1 { font-size: 18px; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #f1f5f9; text-align: left; padding: 6px; }
        td { padding: 6px; border-bottom: 1px solid #e2e8f0; }
        .num { text-align: right; }
        .totales td { border: none; }
        .total { font-weight: bold; font-size: 13px; }
    </style>
</head>
<body>
    <header>
        <h1>{{ $empresa['nombre'] }}</h1>
        <div>{{ $empresa['cif'] ?? '' }} · {{ $empresa['direccion'] ?? '' }} · {{ $empresa['telefono'] ?? '' }}</div>
    </header>

    <h2>Presupuesto {{ $presupuesto->codigo }}</h2>
    <p>
        Fecha: {{ $presupuesto->fecha->format('d/m/Y') }}<br>
        Cliente: {{ $presupuesto->nombre_cliente }} @if ($presupuesto->nif) ({{ $presupuesto->nif }}) @endif<br>
        {{ $presupuesto->direccion }} · {{ $presupuesto->telefono }}
    </p>

    <table>
        <thead>
            <tr><th>Descripción</th><th class="num">Uds.</th><th class="num">Precio</th><th class="num">Total</th></tr>
        </thead>
        <tbody>
            @foreach ($presupuesto->lineas as $linea)
                <tr>
                    <td>{{ $linea->descripcion }}</td>
                    <td class="num">{{ $linea->uds }}</td>
                    <td class="num">{{ $euros($linea->precio) }}</td>
                    <td class="num">{{ $euros($linea->total_linea) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totales">
        <tr><td class="num">Base imponible</td><td class="num">{{ $euros($presupuesto->base_imponible) }}</td></tr>
        <tr><td class="num">IVA ({{ $presupuesto->iva }} %)</td><td class="num">{{ $euros($presupuesto->cuota_iva) }}</td></tr>
        <tr class="total"><td class="num">Total</td><td class="num">{{ $euros($presupuesto->total) }}</td></tr>
    </table>
</body>
</html>
```

```php
// routes/web.php
Route::get('presupuestos/{presupuesto}/pdf', [PdfController::class, 'presupuesto'])
    ->middleware('auth')->name('presupuestos.pdf');
```

## Ejercicios

1. **El presupuesto.** Instala dompdf, escribe el ejemplo y ábrelo desde el navegador.

2. **Los otros dos documentos**, con la misma forma: una acción en `PdfController`, su vista en `resources/views/pdf/` y su ruta web con `auth`.

    | Ruta | Nombre | Vista | Contenido |
    |:--|:--|:--|:--|
    | `/partes-trabajo/{parteTrabajo}/pdf` | `partes-trabajo.pdf` | `pdf.parte-trabajo` | Obra, fecha, descripción y una fila por trabajador con horas, tarifa y subtotal |
    | `/gastos/{gasto}/pdf` | `gastos.pdf` | `pdf.gasto` | Datos de la obra y de su ficha (fechas, licencia, ICIO), costes por tipo, ingresos, beneficio real y pendiente |

3. **El botón *Imprimir*.** En el detalle del presupuesto, del parte y de la ficha de Gastos, un `<x-boton :href="route('presupuestos.pdf', $presupuesto)" variante="secundario" target="_blank">Imprimir</x-boton>`.

4. **Investiga.** Responde en tu _pull request_: cambia `stream()` por `download()`. ¿Qué diferencia notas en el navegador? ¿Por qué la vista del PDF no usa `<x-layout>` ni las clases de Tailwind?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque9/RA9_1_PdfTest.php tests/Feature/
php artisan test --filter=RA9_1
```

Resultado esperado: `Tests: 5 passed`.

## Git

Rama `RA9_1`, commit `feat: informes PDF de presupuesto, parte y seguimiento`.

---

**Siguiente:** [9.2. Correo electrónico](./RA9_2_correo.md)
<!-- {% endraw %} -->
