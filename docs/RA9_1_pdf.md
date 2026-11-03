# 9.1. Documentos PDF

**Referencia teórica:** [laravel-dompdf](https://github.com/barryvdh/laravel-dompdf) · [Laravel: Responses](https://laravel.com/docs/13.x/responses#file-responses)

Los tres informes del módulo de Odoo: el presupuesto para entregar al cliente, el parte de trabajo y el seguimiento de gastos de la obra.

## Conceptos clave

- `composer require barryvdh/laravel-dompdf` convierte una vista Blade en PDF: `Pdf::loadView('pdf.presupuesto', $datos)->stream('PREP0001.pdf')`.
- dompdf entiende HTML y CSS sencillos (tablas, `position: fixed` para cabecera y pie) y no ejecuta JavaScript ni Tailwind compilado: los estilos van en un `<style>` dentro de la vista.
- `stream()` lo abre en el navegador; `download()` fuerza la descarga.

## Ejercicios

1. `PdfController` con tres acciones y sus rutas web protegidas con `auth`:

   | Ruta | Nombre | Vista |
   |:--|:--|:--|
   | `/presupuestos/{presupuesto}/pdf` | `presupuestos.pdf` | `pdf.presupuesto` |
   | `/partes-trabajo/{parteTrabajo}/pdf` | `partes-trabajo.pdf` | `pdf.parte-trabajo` |
   | `/gastos/{gasto}/pdf` | `gastos.pdf` | `pdf.gasto` |

2. **Presupuesto**: cabecera con los datos de la empresa (`config('gestionobras.empresa')`), datos del cliente copiados en el presupuesto, tabla de líneas, base imponible, IVA y total con formato `1.210,00 €`.
3. **Parte de trabajo**: obra, fecha, descripción y una fila por trabajador con horas, tarifa y subtotal.
4. **Seguimiento de gastos**: datos de la obra y de su ficha (fechas, licencia, ICIO), costes por tipo, ingresos, beneficio real y pendiente.
5. Botón *Imprimir* en el detalle de cada uno de los tres.

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
