<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Ingreso;
use App\Models\Obra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RA6_11_IngresosGastosTest extends TestCase
{
    use RefreshDatabase;

    private Obra $obra;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['rol' => 'jefe']));
        $this->obra = Obra::factory()->create(['descripcion' => 'Reforma de baño']);
    }

    public function test_la_tabla_de_ingresos()
    {
        $this->assertTrue(Schema::hasColumns('ingresos', ['id', 'codigo', 'obra_id', 'cliente_id', 'nombre_obra', 'tipo', 'fecha', 'documento', 'num_documento', 'importe']));
    }

    public function test_el_ingreso_toma_cliente_y_descripcion_de_la_obra()
    {
        $i = Ingreso::create(['obra_id' => $this->obra->id, 'importe' => 100]);

        $this->assertSame('ING0001', $i->codigo);
        $this->assertSame($this->obra->cliente_id, $i->cliente_id);
        $this->assertSame('Reforma de baño', $i->nombre_obra);
        $this->assertSame(now()->toDateString(), $i->fecha->toDateString());
        $this->assertCount(1, $this->obra->ingresos);
    }

    public function test_ingresos_desde_la_obra()
    {
        $this->get(route('obras.ingresos.create', $this->obra))->assertOk()->assertSee('name="importe"', false);
        $this->post(route('obras.ingresos.store', $this->obra), ['importe' => 500, 'tipo' => 'Transferencia'])->assertSessionHasNoErrors()->assertRedirect(route('obras.show', $this->obra));
        $i = Ingreso::firstOrFail();
        $this->get(route('obras.show', $this->obra))->assertSee($i->codigo);

        $this->delete(route('obras.ingresos.destroy', [$this->obra, $i]))->assertRedirect(route('obras.show', $this->obra));
        $this->assertDatabaseCount('ingresos', 0);
    }

    public function test_validaciones_del_ingreso()
    {
        $otro = Cliente::factory()->create();
        $ajeno = Ingreso::factory()->create();

        $this->post(route('obras.ingresos.store', $this->obra), ['importe' => 0])->assertSessionHasErrors('importe');
        $this->post(route('obras.ingresos.store', $this->obra), ['importe' => 10, 'cliente_id' => $otro->id])->assertSessionHasErrors('cliente_id');
        $this->delete(route('obras.ingresos.destroy', [$this->obra, $ajeno]))->assertNotFound();
        $this->assertDatabaseHas('ingresos', ['id' => $ajeno->id]);
    }

    public function test_pantallas_de_la_ficha_de_gastos()
    {
        $g = $this->obra->gasto;

        $this->get(route('gastos.index'))->assertOk()->assertSee($g->codigo);
        $this->get(route('gastos.show', $g))->assertOk()->assertSee($g->codigo);
        $this->get(route('gastos.edit', $g))->assertOk()->assertSee('name="direccion_obra"', false);
        $this->put(route('gastos.update', $g), ['direccion_obra' => 'Calle Sol 3', 'inicio_obra' => '2026-03-01', 'fin_obra' => '2026-04-15'])->assertRedirect(route('gastos.show', $g));
        $this->get(route('gastos.show', $g))->assertSee('Calle Sol 3');
        $this->put(route('gastos.update', $g), ['inicio_obra' => '2026-05-10', 'fin_obra' => '2026-05-01'])->assertSessionHasErrors('fin_obra');
    }

    public function test_la_ficha_no_se_crea_ni_se_borra()
    {
        foreach (['gastos.create', 'gastos.store', 'gastos.destroy'] as $ruta) {
            $this->assertFalse(Route::has($ruta), "No debe existir la ruta $ruta");
        }
    }
}
