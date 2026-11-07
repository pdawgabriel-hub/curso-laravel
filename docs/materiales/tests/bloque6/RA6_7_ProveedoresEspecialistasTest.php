<?php

namespace Tests\Feature;

use App\Models\Especialista;
use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\EspecialistasTableSeeder;
use Database\Seeders\ProveedoresTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RA6_7_ProveedoresEspecialistasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['rol' => 'jefe']));
    }

    public function test_tablas_y_modelos()
    {
        $this->assertTrue(Schema::hasColumns('proveedores', ['id', 'codigo', 'nombre', 'direccion', 'telefono', 'correo', 'tipo', 'referencia', 'documento', 'num_documento', 'base', 'iva']));
        $this->assertTrue(Schema::hasColumns('especialistas', ['id', 'codigo', 'tipo', 'referencia', 'comunicacion_parte', 'nombre', 'notas', 'telefono', 'correo', 'importe']));
        $this->assertSame('proveedores', (new Proveedor())->getTable());
        $this->assertEquals(['nombre', 'direccion', 'telefono', 'correo', 'tipo', 'referencia', 'documento', 'num_documento', 'base', 'iva'], (new Proveedor())->getFillable());
        $this->assertEquals(['tipo', 'referencia', 'comunicacion_parte', 'nombre', 'notas', 'telefono', 'correo', 'importe'], (new Especialista())->getFillable());
    }

    public function test_codigos_y_total_con_iva()
    {
        $p = Proveedor::create(['nombre' => 'Materiales Sur', 'base' => 100, 'iva' => 21]);

        $this->assertSame('PROV0001', $p->codigo);
        $this->assertEqualsWithDelta(121, $p->total, 0.001);
        $this->assertSame('ESP0001', Especialista::create(['nombre' => 'Marta'])->codigo);
    }

    public function test_las_semillas_usan_sus_factories()
    {
        $this->seed([ProveedoresTableSeeder::class, EspecialistasTableSeeder::class]);

        $this->assertSame(20, Proveedor::count());
        $this->assertSame(20, Especialista::count());
    }

    public function test_pantallas_de_proveedores()
    {
        $this->post('/proveedores/store', ['nombre' => 'Materiales Sur', 'base' => 100, 'iva' => 21])->assertSessionHasNoErrors()->assertRedirect(route('proveedores.index'));
        $p = Proveedor::firstOrFail();

        $this->get('/proveedores')->assertOk()->assertSee('Materiales Sur')->assertSee(route('proveedores.show', $p->id), false);
        $this->get("/proveedores/show/{$p->id}")->assertOk()->assertSee($p->codigo)->assertSee('121');
        $this->get("/proveedores/edit/{$p->id}")->assertOk();
        $this->put("/proveedores/update/{$p->id}", ['nombre' => 'Materiales Norte', 'base' => 100, 'iva' => 21])->assertRedirect(route('proveedores.show', $p->id));
        $this->assertDatabaseHas('proveedores', ['id' => $p->id, 'nombre' => 'Materiales Norte']);
        $this->delete("/proveedores/destroy/{$p->id}")->assertRedirect(route('proveedores.index'));
        $this->assertDatabaseCount('proveedores', 0);
    }

    public function test_pantallas_de_especialistas()
    {
        $this->post('/especialistas/store', ['nombre' => 'Marta Gil', 'tipo' => 'Arquitecta'])->assertSessionHasNoErrors()->assertRedirect(route('especialistas.index'));
        $e = Especialista::firstOrFail();

        $this->get('/especialistas')->assertOk()->assertSee('Marta Gil');
        $this->get("/especialistas/show/{$e->id}")->assertOk()->assertSee('Arquitecta');
        $this->put("/especialistas/update/{$e->id}", ['nombre' => 'Marta Vidal'])->assertRedirect(route('especialistas.show', $e->id));
        $this->delete("/especialistas/destroy/{$e->id}")->assertRedirect(route('especialistas.index'));
        $this->assertDatabaseCount('especialistas', 0);
    }

    public function test_validaciones()
    {
        $this->post('/proveedores/store', ['nombre' => 'X', 'base' => -1, 'iva' => 150, 'correo' => 'mal'])->assertSessionHasErrors(['base', 'iva', 'correo']);
        $this->post('/especialistas/store', ['importe' => -1])->assertSessionHasErrors(['nombre', 'importe']);
    }
}
