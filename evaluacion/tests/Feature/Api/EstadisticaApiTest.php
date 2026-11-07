<?php

namespace Tests\Feature\Api;

use App\Models\Ingreso;
use App\Models\LineaParteTrabajo;
use App\Models\LineaPresupuesto;
use App\Models\Obra;
use App\Models\ParteEspecialista;
use App\Models\ParteProveedor;
use App\Models\ParteTrabajo;
use App\Models\Presupuesto;
use App\Models\Trabajador;
use Tests\Feature\FeatureTestCase;

class EstadisticaApiTest extends FeatureTestCase
{
    private Obra $obra;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('jefe');
        $this->obra = Obra::factory()->create();
        $p = Presupuesto::factory()->create(['cliente_id' => $this->obra->cliente_id, 'obra_id' => $this->obra->id, 'estado' => 'aprobado', 'iva' => 21]);
        LineaPresupuesto::factory()->create(['presupuesto_id' => $p->id, 'uds' => 1, 'precio' => 1000]);
    }

    public function test_devuelve_la_estructura_completa()
    {
        $this->getJson('/api/v1/estadisticas')
            ->assertOk()
            ->assertJsonPath('data.periodo', 'todo')
            ->assertJsonPath('data.desde', null)
            ->assertJsonStructure(['data' => [
                'periodo', 'desde',
                'resumen' => ['num_obras', 'total_presupuestado', 'ingresos', 'coste_proveedores', 'coste_especialistas',
                    'coste_moo', 'gastos', 'beneficio', 'horas', 'pendiente_cobro'],
                'top_obras', 'cobros_pendientes', 'top_trabajadores', 'evolucion_mensual',
            ]]);
    }

    public function test_resumen_de_todo_el_historico()
    {
        $parte = ParteTrabajo::factory()->create(['obra_id' => $this->obra->id, 'fecha' => now()->subYears(2)]);
        LineaParteTrabajo::factory()->create(['parte_trabajo_id' => $parte->id, 'horas' => 10, 'coste_hora' => 20]);
        ParteProveedor::factory()->create(['obra_id' => $this->obra->id, 'importe' => 100]);
        ParteEspecialista::factory()->create(['obra_id' => $this->obra->id, 'importe' => 50]);
        Ingreso::factory()->create(['obra_id' => $this->obra->id, 'importe' => 600]);

        $r = $this->getJson('/api/v1/estadisticas?periodo=todo')->json('data.resumen');

        $this->assertSame(1, $r['num_obras']);
        $this->assertImporte(1210, $r['total_presupuestado']);
        $this->assertImporte(600, $r['ingresos']);
        $this->assertImporte(100, $r['coste_proveedores']);
        $this->assertImporte(50, $r['coste_especialistas']);
        $this->assertImporte(200, $r['coste_moo']);
        $this->assertImporte(350, $r['gastos']);
        $this->assertImporte(250, $r['beneficio']);
        $this->assertImporte(10, $r['horas']);
        $this->assertImporte(610, $r['pendiente_cobro']);
    }

    public function test_el_periodo_filtra_por_la_fecha_de_cada_movimiento()
    {
        Ingreso::factory()->create(['obra_id' => $this->obra->id, 'importe' => 300, 'fecha' => now()->toDateString()]);
        Ingreso::factory()->create(['obra_id' => $this->obra->id, 'importe' => 200, 'fecha' => now()->subYears(2)->toDateString()]);
        ParteProveedor::factory()->create(['obra_id' => $this->obra->id, 'importe' => 100, 'fecha' => now()->subYears(2)->toDateString()]);

        $d = $this->getJson('/api/v1/estadisticas?periodo=este_mes')->json('data');

        $this->assertSame(now()->startOfMonth()->toDateString(), $d['desde']);
        $this->assertImporte(300, $d['resumen']['ingresos']);
        $this->assertImporte(0, $d['resumen']['coste_proveedores']);
        $this->assertImporte(710, $d['resumen']['pendiente_cobro'], 'El pendiente de cobro se calcula siempre sobre todo el histórico');
    }

    public function test_desde_de_cada_periodo()
    {
        $this->assertSame(now()->startOfYear()->toDateString(), $this->getJson('/api/v1/estadisticas?periodo=este_anio')->json('data.desde'));
        $this->assertSame(now()->subMonths(3)->toDateString(), $this->getJson('/api/v1/estadisticas?periodo=tres_meses')->json('data.desde'));
    }

    public function test_periodo_no_valido()
    {
        $this->getJson('/api/v1/estadisticas?periodo=siempre')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('periodo');
    }

    public function test_top_obras_por_beneficio()
    {
        $otra = Obra::factory()->create();
        Ingreso::factory()->create(['obra_id' => $this->obra->id, 'importe' => 500]);
        Ingreso::factory()->create(['obra_id' => $otra->id, 'importe' => 900]);
        ParteProveedor::factory()->create(['obra_id' => $otra->id, 'importe' => 100]);

        $top = $this->getJson('/api/v1/estadisticas')->json('data.top_obras');

        $this->assertSame([$otra->id, $this->obra->id], array_column($top, 'obra_id'));
        $this->assertImporte(800, $top[0]['beneficio']);
        $this->assertSame(['obra_id', 'codigo', 'descripcion', 'ingresos', 'gastos', 'beneficio'], array_keys($top[0]));
    }

    public function test_cobros_pendientes_de_mayor_a_menor()
    {
        $pagada = Obra::factory()->create();
        Ingreso::factory()->create(['obra_id' => $this->obra->id, 'importe' => 10]);

        $cobros = $this->getJson('/api/v1/estadisticas')->json('data.cobros_pendientes');

        $this->assertCount(1, $cobros);
        $this->assertSame($this->obra->id, $cobros[0]['obra_id']);
        $this->assertImporte(1200, $cobros[0]['debe']);
        $this->assertSame(['obra_id', 'codigo', 'descripcion', 'total', 'cobrado', 'debe'], array_keys($cobros[0]));
    }

    public function test_top_trabajadores_por_horas_del_periodo()
    {
        $a = Trabajador::factory()->create();
        $b = Trabajador::factory()->create();
        $parte = ParteTrabajo::factory()->create(['obra_id' => $this->obra->id, 'fecha' => now()->toDateString()]);
        LineaParteTrabajo::factory()->create(['parte_trabajo_id' => $parte->id, 'trabajador_id' => $a->id, 'horas' => 3]);
        LineaParteTrabajo::factory()->create(['parte_trabajo_id' => $parte->id, 'trabajador_id' => $b->id, 'horas' => 7]);

        $top = $this->getJson('/api/v1/estadisticas?periodo=este_mes')->json('data.top_trabajadores');

        $this->assertSame([$b->id, $a->id], array_column($top, 'trabajador_id'));
        $this->assertSame(['trabajador_id', 'nombre_completo', 'horas', 'coste'], array_keys($top[0]));
    }

    public function test_la_evolucion_mensual_ignora_el_periodo()
    {
        Ingreso::factory()->create(['obra_id' => $this->obra->id, 'importe' => 100, 'fecha' => '2024-03-15']);
        Ingreso::factory()->create(['obra_id' => $this->obra->id, 'importe' => 50, 'fecha' => '2024-03-20']);
        Ingreso::factory()->create(['obra_id' => $this->obra->id, 'importe' => 70, 'fecha' => '2023-12-01']);
        $this->obra->gasto->update(['inicio_obra' => '2023-12-05', 'fin_obra' => '2024-03-30']);

        $evolucion = $this->getJson('/api/v1/estadisticas?periodo=este_mes')->json('data.evolucion_mensual');

        $this->assertSame([[2023, 12], [2024, 3]], array_map(fn ($m) => [$m['anio'], $m['mes']], $evolucion));
        $this->assertImporte(70, $evolucion[0]['ingresos']);
        $this->assertSame(1, $evolucion[0]['obras_iniciadas']);
        $this->assertImporte(150, $evolucion[1]['ingresos']);
        $this->assertSame(1, $evolucion[1]['obras_finalizadas']);
    }

    public function test_usuario_sin_rol_no_ve_las_estadisticas()
    {
        $this->actuarComo(null);

        $this->getJson('/api/v1/estadisticas')->assertForbidden();
    }
}
