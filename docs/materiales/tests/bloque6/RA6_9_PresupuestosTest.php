<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Especialista;
use App\Models\LineaPresupuesto;
use App\Models\Obra;
use App\Models\Presupuesto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RA6_9_PresupuestosTest extends TestCase
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

    public function test_tablas()
    {
        $this->assertTrue(Schema::hasColumns('presupuestos', ['id', 'codigo', 'fecha', 'cliente_id', 'obra_id', 'nombre_cliente', 'nif', 'direccion', 'telefono', 'iva', 'estado']));
        $this->assertTrue(Schema::hasColumns('lineas_presupuesto', ['id', 'presupuesto_id', 'descripcion', 'uds', 'precio']));
        $this->assertTrue(Schema::hasColumns('presupuesto_proveedor', ['presupuesto_id', 'proveedor_id']));
        $this->assertTrue(Schema::hasColumns('especialista_presupuesto', ['especialista_id', 'presupuesto_id']));
    }

    public function test_copia_los_datos_del_cliente_y_ya_no_cambian()
    {
        $p = Presupuesto::create(['cliente_id' => $this->cliente->id]);

        $this->assertSame('Juan Pérez', $p->nombre_cliente);
        $this->assertSame($this->cliente->nif, $p->nif);
        $this->assertSame(now()->toDateString(), $p->fecha->toDateString());
        $this->assertSame('PREP0001', $p->codigo);

        $this->cliente->update(['nombre' => 'Otro']);
        $this->assertSame('Juan Pérez', $p->fresh()->nombre_cliente);
    }

    public function test_valores_por_defecto()
    {
        $p = Presupuesto::create(['cliente_id' => $this->cliente->id])->fresh();

        $this->assertEquals(21, $p->iva);
        $this->assertSame('no_aprobado', $p->estado);
    }

    public function test_uno_a_muchos_lineas_con_borrado_en_cascada()
    {
        $p = Presupuesto::factory()->create();
        $linea = LineaPresupuesto::factory()->create(['presupuesto_id' => $p->id, 'uds' => 4, 'precio' => 25.5]);

        $this->assertCount(1, $p->lineas);
        $this->assertEqualsWithDelta(102, $linea->total_linea, 0.001);

        $p->delete();
        $this->assertDatabaseCount('lineas_presupuesto', 0);
    }

    public function test_muchos_a_muchos_proveedores_y_especialistas()
    {
        $p = Presupuesto::factory()->create();
        $prov = Proveedor::factory()->create();
        $esp = Especialista::factory()->create();

        $p->proveedores()->attach($prov);
        $esp->presupuestos()->attach($p);

        $this->assertTrue($prov->presupuestos->contains($p), 'La relación se ve desde el proveedor');
        $this->assertTrue($p->especialistas->contains($esp), 'La relación se ve desde el presupuesto');
    }

    public function test_no_se_borra_un_cliente_con_presupuestos()
    {
        Presupuesto::factory()->create(['cliente_id' => $this->cliente->id]);

        $this->from(route('clientes.show', $this->cliente->id))->delete(route('clientes.destroy', $this->cliente->id))
            ->assertRedirect(route('clientes.show', $this->cliente->id))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('clientes', ['id' => $this->cliente->id]);

        $this->expectException(QueryException::class);
        $this->cliente->delete();
    }

    public function test_alta_y_lineas_desde_las_pantallas()
    {
        $this->get(route('presupuestos.create'))->assertOk()->assertSee('name="cliente_id"', false);
        $this->post(route('presupuestos.store'), ['cliente_id' => $this->cliente->id, 'iva' => 21])->assertSessionHasNoErrors();
        $p = Presupuesto::firstOrFail();

        $this->post(route('presupuestos.lineas.store', $p), ['descripcion' => 'Alicatado', 'uds' => 10, 'precio' => 100])->assertRedirect(route('presupuestos.show', $p));
        $this->get(route('presupuestos.show', $p))->assertOk()->assertSee('Juan Pérez')->assertSee('Alicatado');
        $linea = LineaPresupuesto::firstOrFail();
        $this->delete(route('presupuestos.lineas.destroy', [$p, $linea]))->assertRedirect(route('presupuestos.show', $p));
        $this->assertDatabaseCount('lineas_presupuesto', 0);
        $this->post(route('presupuestos.lineas.store', $p), ['uds' => -1, 'precio' => 1])->assertSessionHasErrors('uds');
    }

    public function test_el_cliente_del_presupuesto_es_el_de_su_obra()
    {
        $obra = Obra::factory()->create();

        $this->post(route('presupuestos.store'), ['cliente_id' => $this->cliente->id, 'obra_id' => $obra->id])->assertSessionHasErrors('cliente_id');
        $this->post(route('presupuestos.store'), ['cliente_id' => $this->cliente->id, 'iva' => 150])->assertSessionHasErrors('iva');
        $this->assertDatabaseCount('presupuestos', 0);
    }

    public function test_vincular_un_presupuesto_libre_a_la_obra()
    {
        $obra = Obra::factory()->create(['cliente_id' => $this->cliente->id]);
        $libre = Presupuesto::factory()->create(['cliente_id' => $this->cliente->id]);
        $ajeno = Presupuesto::factory()->create();

        $this->get(route('obras.show', $obra))->assertSee($libre->codigo)->assertDontSee($ajeno->codigo);
        $this->post(route('obras.vincular-presupuesto', $obra), ['presupuesto_id' => $libre->id])->assertRedirect(route('obras.show', $obra));
        $this->assertSame($obra->id, $libre->fresh()->obra_id);
        $this->post(route('obras.vincular-presupuesto', $obra), ['presupuesto_id' => $ajeno->id])->assertSessionHasErrors('presupuesto_id');
    }
}
