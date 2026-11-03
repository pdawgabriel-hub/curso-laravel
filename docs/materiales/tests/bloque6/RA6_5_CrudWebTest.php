<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Obra;
use App\Models\Presupuesto;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RA6_5_CrudWebTest extends TestCase
{
    use RefreshDatabase;

    private array $datos = ['nombre' => 'Ana', 'apellidos' => 'Ruiz', 'nif' => '11111111H', 'direccion' => 'Calle Luna 3', 'telefono' => '600123123'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['rol' => 'administrativa']));
    }

    public function test_el_listado_sale_de_la_base_de_datos()
    {
        $clientes = Cliente::factory()->count(3)->create();

        $response = $this->get('/clientes')->assertOk();

        foreach ($clientes as $c) {
            $response->assertSee($c->nombre)->assertSee(route('clientes.show', $c->id), false);
        }
    }

    public function test_detalle_con_su_codigo()
    {
        $c = Cliente::factory()->create();

        $this->get("/clientes/show/{$c->id}")->assertOk()->assertSee($c->codigo);
        $this->get('/clientes/show/999')->assertNotFound();
    }

    public function test_alta_guarda_en_la_base_de_datos()
    {
        $this->post('/clientes/store', $this->datos)->assertSessionHasNoErrors()->assertRedirect(route('clientes.index'));

        $this->assertDatabaseHas('clientes', ['nif' => '11111111H', 'codigo' => 'CLI0001']);
    }

    public function test_el_nif_es_unico_tambien_desde_el_formulario()
    {
        Cliente::factory()->create(['nif' => '11111111H']);

        $this->post('/clientes/store', $this->datos)->assertSessionHasErrors('nif');
    }

    public function test_modificacion_guarda_los_cambios()
    {
        $c = Cliente::factory()->create(['nif' => '11111111H']);

        $this->put("/clientes/update/{$c->id}", ['telefono' => '699999999'] + $this->datos)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('clientes.show', $c->id));
        $this->assertDatabaseHas('clientes', ['id' => $c->id, 'telefono' => '699999999']);
    }

    public function test_borrado_de_un_cliente()
    {
        $c = Cliente::factory()->create();

        $this->delete("/clientes/destroy/{$c->id}")->assertRedirect(route('clientes.index'));
        $this->assertDatabaseMissing('clientes', ['id' => $c->id]);
    }

    public function test_no_se_borra_un_cliente_con_presupuestos()
    {
        $c = Cliente::factory()->create();
        Presupuesto::factory()->create(['cliente_id' => $c->id]);

        $this->from("/clientes/show/{$c->id}")->delete("/clientes/destroy/{$c->id}")
            ->assertRedirect("/clientes/show/{$c->id}")
            ->assertSessionHas('error');
        $this->assertDatabaseHas('clientes', ['id' => $c->id]);
    }

    public function test_alta_de_obra_crea_su_ficha_de_gastos()
    {
        $c = Cliente::factory()->create();

        $this->post('/obras/store', ['descripcion' => 'Reforma de baño', 'cliente_id' => $c->id])->assertSessionHasNoErrors();

        $obra = Obra::firstOrFail();
        $this->assertSame('OBR0001', $obra->codigo);
        $this->assertNotNull($obra->gasto);
    }

    public function test_alta_y_borrado_de_trabajador()
    {
        $this->post('/trabajadores/store', ['nombre' => 'Eva', 'apellido' => 'Sanz', 'coste_hora_estandar' => 22])->assertSessionHasNoErrors();
        $t = Trabajador::firstOrFail();
        $this->assertSame('TRB0001', $t->codigo);

        $this->delete("/trabajadores/destroy/{$t->id}")->assertRedirect(route('trabajadores.index'));
        $this->assertDatabaseCount('trabajadores', 0);
    }

    public function test_borrar_exige_iniciar_sesion()
    {
        $c = Cliente::factory()->create();
        auth()->logout();

        $this->delete("/clientes/destroy/{$c->id}")->assertRedirect('/login');
    }
}
