<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Falta;
use App\Models\Obra;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** 6.9: pantallas de obras, de la ficha de Gastos y de faltas. */
class RA6_9_ObrasTest extends TestCase
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

    private function assertColumnas(TestResponse $r, array $columnas): void
    {
        foreach ($columnas as $c) {
            $this->assertMatchesRegularExpression('#<th[^>]*>\s*' . preg_quote($c, '#') . '\s*</th>#u', $r->getContent(), "Falta la columna «{$c}»");
        }
    }

    private function assertMenuActivo(TestResponse $r, string $ruta): void
    {
        $this->assertMatchesRegularExpression('#href="' . preg_quote(route($ruta), '#') . '"[^>]*aria-current="page"#', $r->getContent(), "El menú no marca {$ruta}");
    }

    public function test_listado_de_obras()
    {
        $vacio = $this->get(route('obras.index'))->assertOk()->assertSee('No hay');
        $this->assertColumnas($vacio, ['Código', 'Descripción', 'Cliente']);
        $this->assertMenuActivo($vacio, 'obras.index');

        $c = Cliente::factory()->create(['nombre' => 'Juan', 'apellidos' => 'Pérez']);
        $obras = Obra::factory()->count(16)->create(['cliente_id' => $c->id]);
        $this->get(route('obras.index'))->assertOk()->assertSee('Juan Pérez')->assertSee(route('obras.show', $obras->first()), false)->assertSee('page=2', false);
    }

    public function test_alta_de_una_obra_con_su_ficha()
    {
        $c = Cliente::factory()->create(['nombre' => 'Juan', 'apellidos' => 'Pérez']);

        $this->get(route('obras.create'))->assertOk()->assertSee('<select', false)->assertSee('name="cliente_id"', false)->assertSee('Juan Pérez');
        $this->followingRedirects()->post(route('obras.store'), ['descripcion' => 'Reforma de baño', 'cliente_id' => $c->id])->assertOk()->assertSee('role="status"', false);

        $obra = Obra::firstOrFail();
        $this->assertSame('OBR0001', $obra->codigo);
        $this->assertNotNull($obra->gasto, 'El alta de la obra crea su ficha de Gastos');
    }

    public function test_detalle_y_modificacion_de_la_obra()
    {
        $c = Cliente::factory()->create(['nombre' => 'Juan', 'apellidos' => 'Pérez']);
        $obra = Obra::factory()->create(['descripcion' => 'Reforma de baño', 'cliente_id' => $c->id]);

        $r = $this->get(route('obras.show', $obra))->assertOk()->assertSee($obra->codigo)->assertSee('Juan Pérez')->assertSee('Reforma de baño');
        $r->assertSee(route('gastos.show', $obra->gasto), false)->assertSee(route('obras.edit', $obra), false);
        $r->assertSee('action="' . route('obras.destroy', $obra) . '"', false)->assertSee('return confirm(', false);
        $this->get(route('obras.show', 999))->assertNotFound();

        $this->get(route('obras.edit', $obra))->assertOk()->assertSee('Reforma de baño');
        $this->put(route('obras.update', $obra), ['descripcion' => 'Reforma de cocina', 'cliente_id' => $c->id])->assertRedirect(route('obras.show', $obra));
        $this->assertDatabaseHas('obras', ['id' => $obra->id, 'descripcion' => 'Reforma de cocina']);
    }

    public function test_la_obra_valida_su_cliente()
    {
        $this->from(route('obras.create'))->followingRedirects()->post(route('obras.store'), ['descripcion' => 'Baño', 'cliente_id' => 999])
            ->assertSee('aria-invalid="true"', false)->assertSee('Baño');
        $this->assertDatabaseCount('obras', 0);
    }

    public function test_pantallas_de_la_ficha_de_gastos()
    {
        $obra = Obra::factory()->create(['descripcion' => 'Reforma de baño']);
        $g = $obra->gasto;

        $lista = $this->get(route('gastos.index'))->assertOk()->assertSee($g->codigo)->assertSee($obra->codigo);
        $this->assertColumnas($lista, ['Código', 'Obra', 'Cliente']);
        $this->assertMenuActivo($lista, 'gastos.index');

        $this->get(route('gastos.show', $g))->assertOk()->assertSee($g->codigo)->assertSee(route('obras.show', $obra), false);
        $this->get(route('gastos.edit', $g))->assertOk()
            ->assertSee('name="direccion_obra"', false)
            ->assertSee('type="checkbox" value="1"', false)
            ->assertSee('name="mandante"', false);

        $this->put(route('gastos.update', $g), ['direccion_obra' => 'Calle Sol 3', 'inicio_obra' => '2026-03-01', 'fin_obra' => '2026-04-15', 'mandante' => '1'])->assertRedirect(route('gastos.show', $g));
        $this->assertTrue($g->fresh()->mandante);
        $this->get(route('gastos.show', $g))->assertSee('Calle Sol 3');

        $this->put(route('gastos.update', $g), ['inicio_obra' => '2026-05-10', 'fin_obra' => '2026-05-01'])->assertSessionHasErrors('fin_obra');
    }

    public function test_la_ficha_no_se_crea_ni_se_borra()
    {
        foreach (['gastos.create', 'gastos.store', 'gastos.destroy'] as $ruta) {
            $this->assertFalse(Route::has($ruta), "No debe existir la ruta {$ruta}");
        }
    }

    public function test_faltas_desde_el_trabajador()
    {
        $t = Trabajador::factory()->create();

        $this->get(route('trabajadores.faltas.create', $t))->assertOk()->assertSee('name="fecha_inicio"', false)->assertSee('name="tipo"', false);
        $this->post(route('trabajadores.faltas.store', $t), ['fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-08-07', 'tipo' => 'vacaciones', 'user_id' => 999])
            ->assertRedirect(route('trabajadores.show', $t));
        $falta = Falta::firstOrFail();
        $this->assertSame($this->user->id, $falta->user_id, 'El propietario es el usuario conectado');

        $this->get(route('trabajadores.show', $t))->assertSee('Vacaciones')->assertSee('5')
            ->assertSee('action="' . route('trabajadores.faltas.destroy', [$t, $falta]) . '"', false);

        $this->from(route('trabajadores.faltas.create', $t))->followingRedirects()
            ->post(route('trabajadores.faltas.store', $t), ['fecha_inicio' => '2026-08-07', 'fecha_fin' => '2026-08-03'])
            ->assertSee('aria-invalid="true"', false);

        $this->delete(route('trabajadores.faltas.destroy', [$t, $falta]))->assertRedirect(route('trabajadores.show', $t));
        $this->assertDatabaseCount('faltas', 0);
    }

    public function test_listado_de_faltas_y_otro_trabajador()
    {
        $t = Trabajador::factory()->create(['nombre' => 'Rocío']);
        $falta = Falta::factory()->create(['trabajador_id' => $t->id, 'tipo' => 'baja_medica']);
        $otro = Trabajador::factory()->create();

        $lista = $this->get(route('faltas.index'))->assertOk()->assertSee('Rocío')->assertSee('Baja médica');
        $this->assertColumnas($lista, ['Trabajador', 'Tipo', 'Desde', 'Hasta', 'Días']);
        $this->assertMenuActivo($lista, 'faltas.index');

        $this->delete(route('trabajadores.faltas.destroy', [$otro, $falta]))->assertNotFound();
    }

    public function test_cada_formulario_tiene_su_form_request()
    {
        $esperados = [
            \App\Http\Requests\ObraRequest::class => ['descripcion', 'cliente_id'],
            \App\Http\Requests\GastoRequest::class => ['direccion_obra', 'inicio_obra', 'fin_obra', 'solicitud_obra', 'mandante', 'gestion_licencia', 'pago_icio', 'presupuesto_id'],
            \App\Http\Requests\FaltaRequest::class => ['fecha_inicio', 'fecha_fin', 'tipo', 'motivo'],
        ];

        foreach ($esperados as $clase => $campos) {
            $this->assertTrue(class_exists($clase), "Falta la clase $clase");
            $this->assertTrue(is_subclass_of($clase, \Illuminate\Foundation\Http\FormRequest::class), "$clase debe extender FormRequest");
            $reglas = (new $clase)->rules();
            foreach ($campos as $campo) {
                $this->assertArrayHasKey($campo, $reglas, "$clase no tiene reglas para $campo");
            }
        }
    }
}
