<?php

namespace Tests\Feature;

use App\Models\Ingreso;
use App\Models\User;
use App\Services\Estadisticas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RA9_3_DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_el_servicio_de_estadisticas_es_el_mismo_que_usa_la_api()
    {
        Ingreso::factory()->create(['importe' => 300]);

        $datos = app(Estadisticas::class)->calcular('todo');

        $this->assertEqualsWithDelta(300, $datos['resumen']['ingresos'], 0.001);
        $this->assertSame(['este_mes', 'tres_meses', 'este_anio', 'todo'], Estadisticas::PERIODOS);
    }

    public function test_el_dashboard_exige_sesion()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_el_dashboard_recibe_las_estadisticas_del_periodo()
    {
        $this->actingAs(User::factory()->create(['rol' => 'jefe']));

        $this->get('/dashboard?periodo=este_mes')
            ->assertOk()
            ->assertViewHas('periodo', 'este_mes')
            ->assertViewHas('estadisticas', fn ($e) => isset($e['resumen'], $e['evolucion_mensual']) && $e['periodo'] === 'este_mes');
    }

    public function test_un_periodo_desconocido_muestra_todo()
    {
        $this->actingAs(User::factory()->create(['rol' => 'jefe']));

        $this->get('/dashboard?periodo=siempre')->assertOk()->assertViewHas('periodo', 'todo');
    }

    public function test_el_dashboard_dibuja_graficos_con_chartjs()
    {
        $this->actingAs(User::factory()->create(['rol' => 'jefe']));

        $this->get('/dashboard')->assertSee('id="grafico-evolucion"', false)->assertSee('chart.js', false);
    }
}
