<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Especialista;
use App\Models\Ingreso;
use App\Models\LineaParteTrabajo;
use App\Models\Obra;
use App\Models\ParteEspecialista;
use App\Models\ParteProveedor;
use App\Models\ParteTrabajo;
use App\Models\Proveedor;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 6.11: partes e ingresos desde el detalle de la obra. */
class RA6_11_PartesTest extends TestCase
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
        $this->obra = Obra::factory()->create(['descripcion' => 'Reforma de baño']);
    }

    public function test_el_detalle_de_la_obra_enlaza_las_altas()
    {
        $r = $this->get(route('obras.show', $this->obra))->assertOk();

        foreach (['obras.partes-trabajo.create', 'obras.partes-proveedor.create', 'obras.partes-especialista.create', 'obras.ingresos.create'] as $ruta) {
            $r->assertSee(route($ruta, $this->obra), false);
        }
    }

    public function test_parte_de_trabajo_desde_la_obra()
    {
        $t = Trabajador::factory()->create(['nombre' => 'Ana', 'apellido' => 'Sanz', 'coste_hora_estandar' => 20]);

        $this->get(route('obras.partes-trabajo.create', $this->obra))->assertOk()
            ->assertSee('Ana Sanz')->assertSee('name="lineas[0][trabajador_id]"', false)->assertSee('name="lineas[0][horas]"', false)
            ->assertSee('name="descripcion"', false);
        $this->post(route('obras.partes-trabajo.store', $this->obra), ['descripcion' => 'Demolición', 'lineas' => [['trabajador_id' => $t->id, 'horas' => 8]], 'user_id' => 999])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('obras.show', $this->obra));

        $parte = ParteTrabajo::firstOrFail();
        $this->assertSame($this->user->id, $parte->user_id, 'El propietario es el usuario conectado');
        $this->assertDatabaseHas('lineas_parte_trabajo', ['parte_trabajo_id' => $parte->id, 'coste_hora' => 20]);
        $this->get(route('obras.show', $this->obra))->assertSee('Demolición')->assertSee('Ana Sanz')
            ->assertSee('action="' . route('obras.partes-trabajo.destroy', [$this->obra, $parte]) . '"', false);

        $this->delete(route('obras.partes-trabajo.destroy', [$this->obra, $parte]))->assertRedirect(route('obras.show', $this->obra));
        $this->assertDatabaseCount('lineas_parte_trabajo', 0);
    }

    public function test_validaciones_del_parte_y_otro_padre()
    {
        $t = Trabajador::factory()->create();
        $ajeno = ParteTrabajo::factory()->create();

        $this->post(route('obras.partes-trabajo.store', $this->obra), ['descripcion' => 'X', 'lineas' => [['trabajador_id' => $t->id, 'horas' => 25]]])->assertSessionHasErrors('lineas.0.horas');
        $this->from(route('obras.partes-trabajo.create', $this->obra))->followingRedirects()
            ->post(route('obras.partes-trabajo.store', $this->obra), ['descripcion' => 'X', 'lineas' => [['trabajador_id' => $t->id, 'horas' => 25]]])
            ->assertSee('aria-invalid="true"', false);
        $this->post(route('obras.partes-trabajo.store', $this->obra), [])->assertSessionHasErrors('descripcion');
        $this->delete(route('obras.partes-trabajo.destroy', [$this->obra, $ajeno]))->assertNotFound();
        $this->assertDatabaseCount('partes_trabajo', 1);
    }

    public function test_partes_externos_desde_la_obra()
    {
        $prov = Proveedor::factory()->create(['nombre' => 'Cerámicas Levante']);
        $esp = Especialista::factory()->create(['nombre' => 'Marta Vidal']);

        $this->get(route('obras.partes-proveedor.create', $this->obra))->assertOk()->assertSee('name="proveedor_id"', false)->assertSee('Cerámicas Levante')->assertSee('name="estado_pago"', false);
        $this->get(route('obras.partes-especialista.create', $this->obra))->assertOk()->assertSee('name="especialista_id"', false);
        $this->post(route('obras.partes-proveedor.store', $this->obra), ['proveedor_id' => $prov->id, 'importe' => 200])->assertRedirect(route('obras.show', $this->obra));
        $this->post(route('obras.partes-especialista.store', $this->obra), ['especialista_id' => $esp->id, 'importe' => 150])->assertRedirect(route('obras.show', $this->obra));

        $pp = ParteProveedor::firstOrFail();
        $this->assertSame($this->user->id, $pp->user_id);
        $this->assertSame('pendiente', $pp->fresh()->estado_pago);
        $this->get(route('obras.show', $this->obra))->assertSee('Cerámicas Levante')->assertSee('Marta Vidal')->assertSee('200,00')
            ->assertSee('action="' . route('obras.partes-proveedor.destroy', [$this->obra, $pp]) . '"', false);

        $this->post(route('obras.partes-proveedor.store', $this->obra), ['proveedor_id' => $prov->id, 'importe' => 0])->assertSessionHasErrors('importe');
        $this->delete(route('obras.partes-especialista.destroy', [Obra::factory()->create(), ParteEspecialista::firstOrFail()]))->assertNotFound();
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

    public function test_ingresos_desde_la_obra()
    {
        $this->get(route('obras.ingresos.create', $this->obra))->assertOk()->assertSee('name="importe"', false)->assertSee('name="tipo"', false);
        $this->post(route('obras.ingresos.store', $this->obra), ['importe' => 500, 'tipo' => 'Transferencia'])->assertSessionHasNoErrors()->assertRedirect(route('obras.show', $this->obra));
        $i = Ingreso::firstOrFail();
        $this->get(route('obras.show', $this->obra))->assertSee($i->codigo)->assertSee('500,00')
            ->assertSee('action="' . route('obras.ingresos.destroy', [$this->obra, $i]) . '"', false);

        $this->delete(route('obras.ingresos.destroy', [$this->obra, $i]))->assertRedirect(route('obras.show', $this->obra));
        $this->assertDatabaseCount('ingresos', 0);
    }

    public function test_validaciones_del_ingreso()
    {
        $otro = Cliente::factory()->create();
        $ajeno = Ingreso::factory()->create();

        $this->from(route('obras.ingresos.create', $this->obra))->followingRedirects()
            ->post(route('obras.ingresos.store', $this->obra), ['importe' => 0, 'tipo' => 'Cheque'])
            ->assertSee('aria-invalid="true"', false)->assertSee('value="Cheque"', false);
        $this->post(route('obras.ingresos.store', $this->obra), ['importe' => 10, 'cliente_id' => $otro->id])->assertSessionHasErrors('cliente_id');
        $this->delete(route('obras.ingresos.destroy', [$this->obra, $ajeno]))->assertNotFound();
        $this->assertDatabaseHas('ingresos', ['id' => $ajeno->id]);
    }
}
