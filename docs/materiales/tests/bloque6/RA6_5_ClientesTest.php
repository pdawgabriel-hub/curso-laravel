<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Secuencia;
use App\Models\User;
use Database\Seeders\ClientesTableSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RA6_5_ClientesTest extends TestCase
{
    use RefreshDatabase;

    private array $datos = ['nombre' => 'Ana', 'apellidos' => 'Ruiz', 'nif' => '11111111H', 'direccion' => 'Calle Luna 3', 'telefono' => '600123123'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function entrar(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'jefe']));
    }

    public function test_la_tabla_de_clientes()
    {
        $this->assertTrue(Schema::hasColumns('clientes', ['id', 'codigo', 'nombre', 'apellidos', 'nif', 'direccion', 'cod_postal', 'telefono', 'created_at', 'updated_at']));
        $this->assertTrue(Schema::hasColumns('secuencias', ['id', 'prefijo', 'siguiente']));
    }

    public function test_el_modelo()
    {
        $c = new Cliente();
        $this->assertSame('clientes', $c->getTable());
        $this->assertEquals(['nombre', 'apellidos', 'nif', 'direccion', 'cod_postal', 'telefono'], $c->getFillable());
        $this->assertSame('Ana Ruiz', (new Cliente(['nombre' => 'Ana', 'apellidos' => 'Ruiz']))->nombre_completo);
        $this->assertSame('Hostelería Sol', (new Cliente(['nombre' => 'Hostelería Sol']))->nombre_completo);
    }

    public function test_el_codigo_es_correlativo_y_el_nif_unico_salvo_vacio()
    {
        $this->assertSame('CLI0001', Cliente::factory()->create()->codigo);
        $this->assertSame('CLI0002', Cliente::factory()->create()->codigo);
        $this->assertSame('OBR0001', Secuencia::siguiente('OBR'));
        Cliente::factory()->count(2)->create(['nif' => null]);

        Cliente::factory()->create(['nif' => '11111111H']);
        $this->expectException(QueryException::class);
        Cliente::factory()->create(['nif' => '11111111H']);
    }

    public function test_la_semilla_trae_los_clientes_del_array_y_otros_diez()
    {
        $this->seed(ClientesTableSeeder::class);

        $this->assertSame(20, Cliente::count());
        $this->assertDatabaseHas('clientes', ['nombre' => 'Juan', 'nif' => '12345678Z', 'codigo' => 'CLI0001']);
    }

    public function test_los_listados_y_detalles_exigen_sesion()
    {
        $c = Cliente::factory()->create();

        $this->get('/clientes')->assertRedirect('/login');
        $this->get("/clientes/show/{$c->id}")->assertRedirect('/login');
        $this->delete("/clientes/destroy/{$c->id}")->assertRedirect('/login');
    }

    public function test_el_listado_sale_de_la_base_de_datos()
    {
        $this->entrar();
        $clientes = Cliente::factory()->count(3)->create();

        $response = $this->get('/clientes')->assertOk();

        foreach ($clientes as $c) {
            $response->assertSee($c->nombre)->assertSee(route('clientes.show', $c->id), false);
        }
    }

    public function test_detalle_con_su_codigo()
    {
        $this->entrar();
        $c = Cliente::factory()->create();

        $this->get("/clientes/show/{$c->id}")->assertOk()->assertSee($c->codigo)->assertSee($c->nombre_completo);
        $this->get('/clientes/show/999')->assertNotFound();
    }

    public function test_alta_guarda_en_la_base_de_datos()
    {
        $this->entrar();

        $this->post('/clientes/store', $this->datos)->assertSessionHasNoErrors()->assertRedirect(route('clientes.index'))->assertSessionHas('mensaje');

        $this->assertDatabaseHas('clientes', ['nif' => '11111111H', 'codigo' => 'CLI0001']);
    }

    public function test_alta_valida_los_datos()
    {
        $this->entrar();
        Cliente::factory()->create(['nif' => '11111111H']);

        $this->post('/clientes/store', $this->datos)->assertSessionHasErrors('nif');
        $this->post('/clientes/store', [])->assertSessionHasErrors(['nombre', 'direccion', 'telefono']);
        $this->assertDatabaseCount('clientes', 1);
    }

    public function test_modificacion_guarda_los_cambios_y_admite_su_propio_nif()
    {
        $this->entrar();
        $c = Cliente::factory()->create(['nif' => '11111111H']);

        $this->get("/clientes/edit/{$c->id}")->assertOk()->assertSee('value="' . $c->nombre . '"', false);
        $this->put("/clientes/update/{$c->id}", ['telefono' => '699999999'] + $this->datos)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('clientes.show', $c->id));
        $this->assertDatabaseHas('clientes', ['id' => $c->id, 'telefono' => '699999999']);
    }

    public function test_borrado()
    {
        $this->entrar();
        $c = Cliente::factory()->create();

        $this->delete("/clientes/destroy/{$c->id}")->assertRedirect(route('clientes.index'));
        $this->assertDatabaseMissing('clientes', ['id' => $c->id]);
    }
}
