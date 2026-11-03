<?php

namespace Tests\Feature;

use App\Models\LineaParteTrabajo;
use App\Models\LineaPresupuesto;
use App\Models\Obra;
use App\Models\Presupuesto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RA9_1_PdfTest extends TestCase
{
    use RefreshDatabase;

    private function assertEsPdf($response): void
    {
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_pdf_del_presupuesto()
    {
        $this->actingAs(User::factory()->create(['rol' => 'administrativa']));
        $p = Presupuesto::factory()->create();
        LineaPresupuesto::factory()->count(3)->create(['presupuesto_id' => $p->id]);

        $this->assertEsPdf($this->get(route('presupuestos.pdf', $p)));
    }

    public function test_pdf_del_parte_de_trabajo()
    {
        $this->actingAs(User::factory()->create(['rol' => 'administrativa']));
        $linea = LineaParteTrabajo::factory()->create();

        $this->assertEsPdf($this->get(route('partes-trabajo.pdf', $linea->parte_trabajo_id)));
    }

    public function test_pdf_del_seguimiento_de_gastos()
    {
        $this->actingAs(User::factory()->create(['rol' => 'administrativa']));
        $obra = Obra::factory()->create();

        $this->assertEsPdf($this->get(route('gastos.pdf', $obra->gasto)));
    }

    public function test_los_pdf_exigen_sesion()
    {
        $p = Presupuesto::factory()->create();

        $this->get(route('presupuestos.pdf', $p))->assertRedirect('/login');
    }

    public function test_la_plantilla_del_presupuesto_incluye_empresa_cliente_y_totales()
    {
        $p = Presupuesto::factory()->create(['nombre_cliente' => 'Juan Pérez', 'iva' => 21]);
        LineaPresupuesto::factory()->create(['presupuesto_id' => $p->id, 'descripcion' => 'Alicatado de baño', 'uds' => 10, 'precio' => 100]);

        $html = view('pdf.presupuesto', ['presupuesto' => $p, 'empresa' => config('gestionobras.empresa')])->render();

        foreach ([config('gestionobras.empresa.nombre'), $p->codigo, 'Juan Pérez', 'Alicatado de baño'] as $texto) {
            $this->assertStringContainsString($texto, $html);
        }
        $this->assertMatchesRegularExpression('/1\.?210/', $html, 'Debe mostrar el total (1.210,00 €)');
    }
}
