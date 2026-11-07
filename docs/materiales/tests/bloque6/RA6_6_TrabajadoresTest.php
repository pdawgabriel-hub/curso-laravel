<?php

namespace Tests\Feature;

use App\Models\Trabajador;
use App\Models\User;
use Database\Seeders\TrabajadoresTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RA6_6_TrabajadoresTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['rol' => 'jefe']));
    }

    public function test_la_tabla_y_el_modelo()
    {
        $this->assertTrue(Schema::hasColumns('trabajadores', ['id', 'codigo', 'tipo', 'nombre', 'apellido', 'telefono', 'coste_hora_estandar']));
        $t = new Trabajador();
        $this->assertSame('trabajadores', $t->getTable());
        $this->assertEquals(['tipo', 'nombre', 'apellido', 'telefono', 'coste_hora_estandar'], $t->getFillable());
        $this->assertEquals(20, $t->coste_hora_estandar);
        $this->assertSame('Luis Gil', (new Trabajador(['nombre' => 'Luis', 'apellido' => 'Gil']))->nombre_completo);
    }

    public function test_el_codigo_y_la_tarifa_por_defecto()
    {
        $t = Trabajador::create(['nombre' => 'Luis', 'apellido' => 'Gil']);

        $this->assertSame('TRB0001', $t->codigo);
        $this->assertEquals(20, $t->fresh()->coste_hora_estandar);
    }

    public function test_la_semilla()
    {
        $this->seed(TrabajadoresTableSeeder::class);

        $this->assertSame(20, Trabajador::count());
        $this->assertDatabaseHas('trabajadores', ['codigo' => 'TRB0001']);
        $this->assertDatabaseHas('trabajadores', ['codigo' => 'TRB0020']);
    }

    public function test_listado_y_detalle()
    {
        $t = Trabajador::factory()->create(['nombre' => 'Rocío', 'tipo' => 'Encargado']);

        $this->get('/trabajadores')->assertOk()->assertSee('Rocío')->assertSee(route('trabajadores.show', $t->id), false);
        $this->get("/trabajadores/show/{$t->id}")->assertOk()->assertSee($t->codigo)->assertSee('Encargado');
        $this->get('/trabajadores/show/999')->assertNotFound();
    }

    public function test_alta_modificacion_y_borrado()
    {
        $this->post('/trabajadores/store', ['nombre' => 'Eva', 'apellido' => 'Sanz', 'coste_hora_estandar' => 22])->assertSessionHasNoErrors()->assertRedirect(route('trabajadores.index'));
        $t = Trabajador::firstOrFail();
        $this->assertSame('TRB0001', $t->codigo);

        $this->put("/trabajadores/update/{$t->id}", ['nombre' => 'Eva', 'apellido' => 'Sanz', 'coste_hora_estandar' => 25])->assertRedirect(route('trabajadores.show', $t->id));
        $this->assertEquals(25, $t->fresh()->coste_hora_estandar);

        $this->delete("/trabajadores/destroy/{$t->id}")->assertRedirect(route('trabajadores.index'));
        $this->assertDatabaseCount('trabajadores', 0);
    }

    public function test_valida_el_coste()
    {
        $this->post('/trabajadores/store', ['nombre' => 'Eva', 'apellido' => 'Sanz', 'coste_hora_estandar' => -1])->assertSessionHasErrors('coste_hora_estandar');
        $this->assertDatabaseCount('trabajadores', 0);
    }
}
