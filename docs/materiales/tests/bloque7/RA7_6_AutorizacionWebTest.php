<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Ingreso;
use App\Models\Obra;
use App\Models\ParteTrabajo;
use App\Models\Presupuesto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Las pantallas aplican las mismas Policies que la API. */
class RA7_6_AutorizacionWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_un_usuario_sin_rol_no_entra_en_ningun_modulo()
    {
        $this->actingAs(User::factory()->create());

        foreach (['clientes', 'obras', 'presupuestos', 'trabajadores', 'proveedores', 'especialistas', 'gastos', 'faltas'] as $modulo) {
            $this->get(route("$modulo.index"))->assertForbidden();
        }
    }

    public function test_la_administrativa_no_borra_clientes_y_no_ve_el_boton()
    {
        $c = Cliente::factory()->create();
        $this->actingAs(User::factory()->create(['rol' => 'administrativa']));

        $this->get(route('clientes.show', $c->id))->assertOk()->assertDontSee(route('clientes.destroy', $c->id), false);
        $this->delete(route('clientes.destroy', $c->id))->assertForbidden();
        $this->assertDatabaseHas('clientes', ['id' => $c->id]);
    }

    public function test_la_administrativa_no_crea_obras_y_no_aprueba_presupuestos()
    {
        $p = Presupuesto::factory()->create();
        $this->actingAs(User::factory()->create(['rol' => 'administrativa']));

        $this->get(route('obras.create'))->assertForbidden();
        $this->post(route('obras.store'), ['descripcion' => 'X'])->assertForbidden();
        $this->put(route('presupuestos.update', $p->id), ['cliente_id' => $p->cliente_id, 'estado' => 'aprobado'])->assertForbidden();
        $this->assertDatabaseCount('obras', 0);
        $this->assertDatabaseHas('presupuestos', ['id' => $p->id, 'estado' => 'no_aprobado']);
    }

    public function test_cada_uno_borra_sus_partes_y_el_jefe_todos()
    {
        $yo = User::factory()->create(['rol' => 'administrativa']);
        $obra = Obra::factory()->create();
        $mio = ParteTrabajo::factory()->create(['obra_id' => $obra->id, 'user_id' => $yo->id]);
        $ajeno = ParteTrabajo::factory()->create(['obra_id' => $obra->id, 'user_id' => User::factory()->create()->id]);

        $this->actingAs($yo);
        $pagina = $this->get(route('obras.show', $obra->id))->assertOk();
        $pagina->assertSee(route('obras.partes-trabajo.destroy', [$obra, $mio]), false)->assertDontSee(route('obras.partes-trabajo.destroy', [$obra, $ajeno]), false);
        $this->delete(route('obras.partes-trabajo.destroy', [$obra, $mio]))->assertRedirect();
        $this->delete(route('obras.partes-trabajo.destroy', [$obra, $ajeno]))->assertForbidden();

        $this->actingAs(User::factory()->create(['rol' => 'jefe']));
        $this->delete(route('obras.partes-trabajo.destroy', [$obra, $ajeno]))->assertRedirect();
        $this->assertDatabaseCount('partes_trabajo', 0);
    }

    public function test_los_ingresos_y_los_gastos_son_del_jefe()
    {
        $ingreso = Ingreso::factory()->create();
        $obra = $ingreso->obra;
        $this->actingAs(User::factory()->create(['rol' => 'administrativa']));

        $this->get(route('gastos.index'))->assertForbidden();
        $this->get(route('gastos.show', $obra->gasto))->assertForbidden();
        $this->post(route('obras.ingresos.store', $obra), ['importe' => 10])->assertForbidden();
        $this->delete(route('obras.ingresos.destroy', [$obra, $ingreso]))->assertForbidden();
        $this->get(route('obras.show', $obra))->assertOk()->assertDontSee($ingreso->codigo);
        $this->assertDatabaseCount('ingresos', 1);
    }
}
