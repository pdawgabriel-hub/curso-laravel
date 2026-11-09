<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Especialista;
use App\Models\LineaPresupuesto;
use App\Models\Obra;
use App\Models\Presupuesto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** 6.10: pantallas de presupuestos, sus líneas, proveedores y especialistas, y la vinculación a la obra. */
class RA6_10_PresupuestosTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['rol' => 'jefe']));
        $this->cliente = Cliente::factory()->create(['nombre' => 'Juan', 'apellidos' => 'Pérez']);
    }

    private function assertColumnas(TestResponse $r, array $columnas): void
    {
        foreach ($columnas as $c) {
            $this->assertMatchesRegularExpression('#<th[^>]*>\s*' . preg_quote($c, '#') . '\s*</th>#u', $r->getContent(), "Falta la columna «{$c}»");
        }
    }

    public function test_listado_de_presupuestos()
    {
        $vacio = $this->get(route('presupuestos.index'))->assertOk()->assertSee('No hay');
        $this->assertColumnas($vacio, ['Código', 'Fecha', 'Cliente', 'Estado']);
        $this->assertMatchesRegularExpression('#href="' . preg_quote(route('presupuestos.index'), '#') . '"[^>]*aria-current="page"#', $vacio->getContent());

        $p = Presupuesto::factory()->create(['cliente_id' => $this->cliente->id, 'estado' => 'aprobado']);
        $this->get(route('presupuestos.index'))->assertSee($p->codigo)->assertSee('Juan Pérez')->assertSee('Aprobado')->assertSee(route('presupuestos.show', $p), false);
    }

    public function test_alta_con_selectores_y_casillas()
    {
        $prov = Proveedor::factory()->create(['nombre' => 'Cerámicas Levante']);
        $esp = Especialista::factory()->create(['nombre' => 'Marta Vidal']);

        $this->get(route('presupuestos.create'))->assertOk()
            ->assertSee('name="cliente_id"', false)->assertSee('Juan Pérez')
            ->assertSee('name="proveedor_ids[]"', false)->assertSee('Cerámicas Levante')
            ->assertSee('name="especialista_ids[]"', false);

        $this->post(route('presupuestos.store'), ['cliente_id' => $this->cliente->id, 'iva' => 21, 'proveedor_ids' => [$prov->id], 'especialista_ids' => [$esp->id]])->assertSessionHasNoErrors();
        $p = Presupuesto::firstOrFail();
        $this->assertTrue($p->proveedores->contains($prov));
        $this->assertTrue($prov->presupuestos->contains($p), 'El presupuesto aparece en la ficha del proveedor');
        $this->get(route('proveedores.show', $prov))->assertSee($p->codigo);

        $this->put(route('presupuestos.update', $p), ['cliente_id' => $this->cliente->id, 'proveedor_ids' => []])->assertSessionHasNoErrors();
        $this->assertCount(0, $p->fresh()->proveedores);
    }

    public function test_detalle_con_lineas()
    {
        $p = Presupuesto::factory()->create(['cliente_id' => $this->cliente->id]);

        $r = $this->get(route('presupuestos.show', $p))->assertOk()->assertSee('Juan Pérez')->assertSee('No hay');
        $r->assertSee('action="' . route('presupuestos.lineas.store', $p) . '"', false)->assertSee('name="uds"', false);
        $r->assertSee('action="' . route('presupuestos.destroy', $p) . '"', false)->assertSee('return confirm(', false);

        $this->post(route('presupuestos.lineas.store', $p), ['descripcion' => 'Alicatado', 'uds' => 10, 'precio' => 100])->assertRedirect(route('presupuestos.show', $p));
        $linea = LineaPresupuesto::firstOrFail();
        $this->get(route('presupuestos.show', $p))->assertSee('Alicatado')->assertSee('1.000,00')
            ->assertSee('action="' . route('presupuestos.lineas.destroy', [$p, $linea]) . '"', false);

        $this->delete(route('presupuestos.lineas.destroy', [$p, $linea]))->assertRedirect(route('presupuestos.show', $p));
        $this->assertDatabaseCount('lineas_presupuesto', 0);
        $this->post(route('presupuestos.lineas.store', $p), ['uds' => -1, 'precio' => 1])->assertSessionHasErrors('uds');
        $this->delete(route('presupuestos.lineas.destroy', [Presupuesto::factory()->create(), LineaPresupuesto::factory()->create(['presupuesto_id' => $p->id])]))->assertNotFound();
    }

    public function test_el_cliente_del_presupuesto_es_el_de_su_obra()
    {
        $obra = Obra::factory()->create();

        $this->from(route('presupuestos.create'))->followingRedirects()
            ->post(route('presupuestos.store'), ['cliente_id' => $this->cliente->id, 'obra_id' => $obra->id])
            ->assertSee('aria-invalid="true"', false);
        $this->post(route('presupuestos.store'), ['cliente_id' => $this->cliente->id, 'iva' => 150])->assertSessionHasErrors('iva');
        $this->assertDatabaseCount('presupuestos', 0);
    }

    public function test_vincular_un_presupuesto_libre_a_la_obra()
    {
        $obra = Obra::factory()->create(['cliente_id' => $this->cliente->id]);
        $libre = Presupuesto::factory()->create(['cliente_id' => $this->cliente->id]);
        $ajeno = Presupuesto::factory()->create();

        $this->get(route('obras.show', $obra))->assertSee('name="presupuesto_id"', false)->assertSee($libre->codigo)->assertDontSee($ajeno->codigo);
        $this->post(route('obras.vincular-presupuesto', $obra), ['presupuesto_id' => $libre->id])->assertRedirect(route('obras.show', $obra));
        $this->assertSame($obra->id, $libre->fresh()->obra_id);
        $this->post(route('obras.vincular-presupuesto', $obra), ['presupuesto_id' => $ajeno->id])->assertSessionHasErrors('presupuesto_id');
    }

    public function test_no_se_borra_un_cliente_con_presupuestos()
    {
        Presupuesto::factory()->create(['cliente_id' => $this->cliente->id]);

        $this->from(route('clientes.show', $this->cliente->id))->delete(route('clientes.destroy', $this->cliente->id))
            ->assertRedirect(route('clientes.show', $this->cliente->id))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('clientes', ['id' => $this->cliente->id]);

        $this->from(route('clientes.show', $this->cliente->id))->followingRedirects()->delete(route('clientes.destroy', $this->cliente->id))
            ->assertSee('role="alert"', false);
    }
}
