<?php

namespace Tests\Feature;

use App\Models\Especialista;
use App\Models\LineaParteTrabajo;
use App\Models\Obra;
use App\Models\ParteEspecialista;
use App\Models\ParteProveedor;
use App\Models\ParteTrabajo;
use App\Models\Proveedor;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RA6_10_PartesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Obra $obra;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->user = User::factory()->create(['rol' => 'jefe']);
        $this->actingAs($this->user);
        $this->obra = Obra::factory()->create();
    }

    public function test_tablas()
    {
        $this->assertTrue(Schema::hasColumns('partes_trabajo', ['id', 'obra_id', 'fecha', 'descripcion', 'user_id']));
        $this->assertTrue(Schema::hasColumns('lineas_parte_trabajo', ['id', 'parte_trabajo_id', 'trabajador_id', 'horas', 'coste_hora']));
        $this->assertTrue(Schema::hasColumns('partes_proveedor', ['id', 'obra_id', 'proveedor_id', 'fecha', 'descripcion', 'documento', 'importe', 'estado_pago', 'user_id']));
        $this->assertTrue(Schema::hasColumns('partes_especialista', ['id', 'obra_id', 'especialista_id', 'fecha', 'descripcion', 'documento', 'importe', 'estado_pago', 'user_id']));
    }

    public function test_la_tarifa_se_copia_al_crear_la_linea_y_ya_no_cambia()
    {
        $t = Trabajador::factory()->create(['coste_hora_estandar' => 20]);
        $linea = LineaParteTrabajo::factory()->create(['trabajador_id' => $t->id, 'horas' => 8]);

        $t->update(['coste_hora_estandar' => 30]);

        $this->assertEquals(20, $linea->fresh()->coste_hora);
        $this->assertEqualsWithDelta(160, $linea->fresh()->coste_subtotal, 0.001);
    }

    public function test_lineas_de_la_obra_a_traves_de_sus_partes()
    {
        $parte = ParteTrabajo::factory()->create(['obra_id' => $this->obra->id]);
        LineaParteTrabajo::factory()->count(3)->create(['parte_trabajo_id' => $parte->id, 'horas' => 2]);
        LineaParteTrabajo::factory()->create(['horas' => 8]);

        $this->assertInstanceOf(HasManyThrough::class, $this->obra->lineasTrabajo());
        $this->assertEquals(6, $this->obra->lineasTrabajo()->sum('horas'));
    }

    public function test_participantes_deducidos_de_los_partes()
    {
        $t = Trabajador::factory()->create();
        $parte = ParteTrabajo::factory()->create(['obra_id' => $this->obra->id]);
        LineaParteTrabajo::factory()->count(2)->create(['parte_trabajo_id' => $parte->id, 'trabajador_id' => $t->id]);
        $pp = ParteProveedor::factory()->create(['obra_id' => $this->obra->id]);
        $pe = ParteEspecialista::factory()->create(['obra_id' => $this->obra->id]);
        Trabajador::factory()->create();

        $this->assertEquals([$t->id], $this->obra->trabajadoresParticipantes()->pluck('id')->all());
        $this->assertEquals([$pp->proveedor_id], $this->obra->proveedoresParticipantes()->pluck('id')->all());
        $this->assertEquals([$pe->especialista_id], $this->obra->especialistasParticipantes()->pluck('id')->all());
        $this->assertEquals([$this->obra->id], $t->obrasParticipadas()->pluck('id')->all());
    }

    public function test_parte_de_trabajo_desde_la_obra()
    {
        $t = Trabajador::factory()->create(['nombre' => 'Ana', 'apellido' => 'Sanz', 'coste_hora_estandar' => 20]);

        $this->get(route('obras.partes-trabajo.create', $this->obra))->assertOk()->assertSee('Ana Sanz')->assertSee('name="lineas[0][horas]"', false);
        $this->post(route('obras.partes-trabajo.store', $this->obra), ['descripcion' => 'Demolición', 'lineas' => [['trabajador_id' => $t->id, 'horas' => 8]], 'user_id' => 999])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('obras.show', $this->obra));

        $parte = ParteTrabajo::firstOrFail();
        $this->assertSame($this->user->id, $parte->user_id, 'El propietario es el usuario conectado');
        $this->assertDatabaseHas('lineas_parte_trabajo', ['parte_trabajo_id' => $parte->id, 'coste_hora' => 20]);
        $this->get(route('obras.show', $this->obra))->assertSee('Demolición')->assertSee('Ana Sanz');

        $this->delete(route('obras.partes-trabajo.destroy', [$this->obra, $parte]))->assertRedirect(route('obras.show', $this->obra));
        $this->assertDatabaseCount('lineas_parte_trabajo', 0);
    }

    public function test_partes_externos_desde_la_obra()
    {
        $prov = Proveedor::factory()->create(['nombre' => 'Cerámicas Levante']);
        $esp = Especialista::factory()->create(['nombre' => 'Marta Vidal']);

        $this->get(route('obras.partes-proveedor.create', $this->obra))->assertOk();
        $this->post(route('obras.partes-proveedor.store', $this->obra), ['proveedor_id' => $prov->id, 'importe' => 200])->assertRedirect(route('obras.show', $this->obra));
        $this->post(route('obras.partes-especialista.store', $this->obra), ['especialista_id' => $esp->id, 'importe' => 150])->assertRedirect(route('obras.show', $this->obra));

        $this->assertSame($this->user->id, ParteProveedor::firstOrFail()->user_id);
        $this->assertSame('pendiente', ParteProveedor::firstOrFail()->fresh()->estado_pago);
        $this->get(route('obras.show', $this->obra))->assertSee('Cerámicas Levante')->assertSee('Marta Vidal');

        $this->post(route('obras.partes-proveedor.store', $this->obra), ['proveedor_id' => $prov->id, 'importe' => 0])->assertSessionHasErrors('importe');
    }

    public function test_validaciones_y_otro_padre()
    {
        $t = Trabajador::factory()->create();
        $ajeno = ParteTrabajo::factory()->create();

        $this->post(route('obras.partes-trabajo.store', $this->obra), ['descripcion' => 'X', 'lineas' => [['trabajador_id' => $t->id, 'horas' => 25]]])->assertSessionHasErrors('lineas.0.horas');
        $this->post(route('obras.partes-trabajo.store', $this->obra), [])->assertSessionHasErrors('descripcion');
        $this->delete(route('obras.partes-trabajo.destroy', [$this->obra, $ajeno]))->assertNotFound();
    }

    public function test_no_se_borra_lo_que_tiene_partes()
    {
        $linea = LineaParteTrabajo::factory()->create();
        $pp = ParteProveedor::factory()->create();
        $pe = ParteEspecialista::factory()->create();

        foreach ([route('trabajadores.destroy', $linea->trabajador_id), route('proveedores.destroy', $pp->proveedor_id), route('especialistas.destroy', $pe->especialista_id)] as $ruta) {
            $this->from('/')->delete($ruta)->assertRedirect('/')->assertSessionHas('error');
        }
        $this->assertDatabaseHas('trabajadores', ['id' => $linea->trabajador_id]);
        $this->assertDatabaseHas('proveedores', ['id' => $pp->proveedor_id]);
        $this->assertDatabaseHas('especialistas', ['id' => $pe->especialista_id]);
    }
}
