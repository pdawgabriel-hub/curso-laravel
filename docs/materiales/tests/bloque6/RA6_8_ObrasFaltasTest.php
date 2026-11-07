<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Falta;
use App\Models\Gasto;
use App\Models\Obra;
use App\Models\Trabajador;
use App\Models\User;
use Database\Seeders\FaltasTableSeeder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RA6_8_ObrasFaltasTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->user = User::factory()->create(['rol' => 'jefe']);
        $this->actingAs($this->user);
    }

    public function test_tablas()
    {
        $this->assertTrue(Schema::hasColumns('obras', ['id', 'codigo', 'descripcion', 'cliente_id']));
        $this->assertTrue(Schema::hasColumns('gastos', ['id', 'codigo', 'obra_id', 'presupuesto_id', 'direccion_obra', 'inicio_obra', 'fin_obra', 'solicitud_obra', 'mandante', 'gestion_licencia', 'pago_icio']));
        $this->assertTrue(Schema::hasColumns('faltas', ['id', 'trabajador_id', 'fecha_inicio', 'fecha_fin', 'tipo', 'motivo', 'user_id']));
    }

    public function test_uno_a_uno_obra_y_ficha_de_gastos()
    {
        $obra = Obra::factory()->create();

        $this->assertInstanceOf(HasOne::class, $obra->gasto());
        $this->assertInstanceOf(BelongsTo::class, $obra->gasto->obra());
        $this->assertTrue($obra->gasto->obra->is($obra));
        $this->assertStringStartsWith('GAS', $obra->gasto->codigo);

        $this->expectException(QueryException::class);
        Gasto::create(['obra_id' => $obra->id]);
    }

    public function test_uno_a_muchos_cliente_y_obras()
    {
        $c = Cliente::factory()->create();
        Obra::factory()->count(2)->create(['cliente_id' => $c->id]);

        $this->assertInstanceOf(HasMany::class, $c->obras());
        $this->assertCount(2, $c->obras);
        $this->assertTrue($c->obras->first()->cliente->is($c));
    }

    public function test_uno_a_muchos_trabajador_y_faltas()
    {
        $t = Trabajador::factory()->create();
        Falta::factory()->count(3)->create(['trabajador_id' => $t->id]);

        $this->assertCount(3, $t->faltas);
        $this->assertTrue($t->faltas->first()->trabajador->is($t));
    }

    public function test_los_dias_de_una_falta()
    {
        $f = Falta::factory()->create(['fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-08-07']);

        $this->assertSame(5, $f->fresh()->dias);
    }

    public function test_pantallas_de_obras()
    {
        $c = Cliente::factory()->create(['nombre' => 'Juan', 'apellidos' => 'Pérez']);

        $this->post('/obras/store', ['descripcion' => 'Reforma de baño', 'cliente_id' => $c->id])->assertSessionHasNoErrors();
        $obra = Obra::firstOrFail();
        $this->assertSame('OBR0001', $obra->codigo);
        $this->assertNotNull($obra->gasto, 'El alta de la obra crea su ficha de Gastos');

        $this->get('/obras')->assertOk()->assertSee('Reforma de baño')->assertSee(route('obras.show', $obra->id), false);
        $this->get("/obras/show/{$obra->id}")->assertOk()->assertSee('OBR0001')->assertSee('Juan Pérez');
        $this->get('/obras/show/999')->assertNotFound();
        $this->put("/obras/update/{$obra->id}", ['descripcion' => 'Reforma de cocina', 'cliente_id' => $c->id])->assertRedirect(route('obras.show', $obra->id));
        $this->assertDatabaseHas('obras', ['id' => $obra->id, 'descripcion' => 'Reforma de cocina']);
    }

    public function test_la_obra_valida_su_cliente()
    {
        $this->post('/obras/store', ['descripcion' => 'X', 'cliente_id' => 999])->assertSessionHasErrors('cliente_id');
        $this->assertDatabaseCount('obras', 0);
    }

    public function test_pantalla_de_faltas()
    {
        $t = Trabajador::factory()->create();

        $this->get(route('trabajadores.faltas.create', $t))->assertOk();
        $this->post(route('trabajadores.faltas.store', $t), ['fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-08-07', 'tipo' => 'vacaciones'])->assertRedirect(route('trabajadores.show', $t));
        $falta = Falta::firstOrFail();
        $this->assertSame($this->user->id, $falta->user_id, 'El propietario es el usuario conectado');

        $this->get(route('trabajadores.show', $t))->assertSee('Vacaciones');
        $this->get(route('faltas.index'))->assertOk()->assertSee($t->nombre);
        $this->post(route('trabajadores.faltas.store', $t), ['fecha_inicio' => '2026-08-07', 'fecha_fin' => '2026-08-03'])->assertSessionHasErrors('fecha_fin');
        $this->delete(route('trabajadores.faltas.destroy', [$t, $falta]))->assertRedirect(route('trabajadores.show', $t));
        $this->assertDatabaseCount('faltas', 0);
    }

    public function test_la_semilla_de_faltas()
    {
        Trabajador::factory()->count(3)->create();

        $this->seed(FaltasTableSeeder::class);

        $this->assertLessThanOrEqual(12, Falta::count());
    }
}
