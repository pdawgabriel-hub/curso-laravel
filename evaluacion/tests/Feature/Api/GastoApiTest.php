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
use Tests\Feature\FeatureTestCase;

class GastoApiTest extends FeatureTestCase
{
    private const CAMPOS = ['id', 'codigo', 'obra_id', 'presupuesto_id', 'direccion_obra', 'inicio_obra', 'fin_obra', 'solicitud_obra',
        'mandante', 'gestion_licencia', 'pago_icio', 'horas_trabajadas', 'coste_moo', 'coste_hora', 'coste_proveedores',
        'coste_especialistas', 'gastos', 'ingresos', 'beneficio_real', 'debe', 'estado', 'created_at', 'updated_at'];

    private Obra $obra;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('administrativa');
        $this->obra = Obra::factory()->create();
    }

    private function aprobar(float $precio): Presupuesto
    {
        $p = Presupuesto::factory()->create(['cliente_id' => $this->obra->cliente_id, 'obra_id' => $this->obra->id, 'estado' => 'aprobado', 'iva' => 21]);
        LineaPresupuesto::factory()->create(['presupuesto_id' => $p->id, 'uds' => 1, 'precio' => $precio]);

        return $p;
    }

    private function gasto(): array
    {
        return $this->getJson("/api/v1/gastos/{$this->obra->gasto->id}")->assertOk()->json('data');
    }

    public function test_puede_listar_y_ver_fichas_de_gastos()
    {
        Obra::factory()->create();

        $this->assertListado($this->getJson('/api/v1/gastos'), self::CAMPOS, 2);
        $this->getJson("/api/v1/gastos/{$this->obra->gasto->id}")
            ->assertOk()
            ->assertJsonStructure(['data' => self::CAMPOS])
            ->assertJsonPath('data.obra_id', $this->obra->id);
    }

    public function test_no_se_crean_ni_borran_fichas_a_mano()
    {
        $this->postJson('/api/v1/gastos', ['obra_id' => $this->obra->id])->assertMethodNotAllowed();
        $this->deleteJson("/api/v1/gastos/{$this->obra->gasto->id}")->assertMethodNotAllowed();
        $this->assertDatabaseCount('gastos', 1);
    }

    public function test_suma_los_tres_tipos_de_coste()
    {
        $parte = ParteTrabajo::factory()->create(['obra_id' => $this->obra->id]);
        LineaParteTrabajo::factory()->count(2)->create(['parte_trabajo_id' => $parte->id, 'horas' => 8, 'coste_hora' => 20]);
        ParteProveedor::factory()->create(['obra_id' => $this->obra->id, 'importe' => 200]);
        ParteEspecialista::factory()->create(['obra_id' => $this->obra->id, 'importe' => 150]);

        $g = $this->gasto();

        $this->assertImporte(16, $g['horas_trabajadas']);
        $this->assertImporte(320, $g['coste_moo']);
        $this->assertImporte(20, $g['coste_hora']);
        $this->assertImporte(200, $g['coste_proveedores']);
        $this->assertImporte(150, $g['coste_especialistas']);
        $this->assertImporte(670, $g['gastos']);
    }

    public function test_coste_hora_es_cero_sin_horas()
    {
        $this->assertImporte(0, $this->gasto()['coste_hora']);
    }

    public function test_el_beneficio_real_es_caja_y_no_margen_proyectado()
    {
        $this->aprobar(1000);
        ParteProveedor::factory()->create(['obra_id' => $this->obra->id, 'importe' => 300]);
        Ingreso::factory()->create(['obra_id' => $this->obra->id, 'importe' => 500]);

        $g = $this->gasto();

        $this->assertImporte(500, $g['ingresos']);
        $this->assertImporte(200, $g['beneficio_real'], 'beneficio_real = ingresos - gastos, nunca total - gastos');
    }

    public function test_debe_es_total_de_la_obra_menos_ingresos()
    {
        $this->aprobar(1000);
        Ingreso::factory()->create(['obra_id' => $this->obra->id, 'importe' => 210]);
        ParteProveedor::factory()->create(['obra_id' => $this->obra->id, 'importe' => 900]);

        $this->assertImporte(1000, $this->gasto()['debe']);
    }

    public function test_estado_pasa_a_finalizado_al_cobrarlo_todo()
    {
        $this->aprobar(1000);
        $this->assertSame('proceso', $this->gasto()['estado']);

        Ingreso::factory()->create(['obra_id' => $this->obra->id, 'importe' => 1210]);

        $this->assertSame('finalizado', $this->gasto()['estado']);
    }

    public function test_obra_sin_total_ni_ingresos_sigue_en_proceso()
    {
        $this->assertSame('proceso', $this->gasto()['estado']);
    }

    public function test_actualiza_los_datos_de_seguimiento()
    {
        $response = $this->putJson("/api/v1/gastos/{$this->obra->gasto->id}", [
            'direccion_obra' => 'Calle Sol 3', 'inicio_obra' => '2026-03-01', 'fin_obra' => '2026-04-15',
            'solicitud_obra' => '2026-02-10', 'mandante' => true, 'gestion_licencia' => false, 'pago_icio' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.direccion_obra', 'Calle Sol 3')
            ->assertJsonPath('data.inicio_obra', '2026-03-01')
            ->assertJsonPath('data.mandante', true)
            ->assertJsonPath('data.gestion_licencia', false);
    }

    public function test_el_fin_de_obra_no_puede_ser_anterior_al_inicio()
    {
        $this->putJson("/api/v1/gastos/{$this->obra->gasto->id}", ['inicio_obra' => '2026-04-01', 'fin_obra' => '2026-03-01'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fin_obra');
    }

    public function test_solo_admite_presupuestos_de_la_misma_obra()
    {
        $propio = $this->aprobar(100);
        $ajeno = Presupuesto::factory()->create();

        $this->putJson("/api/v1/gastos/{$this->obra->gasto->id}", ['presupuesto_id' => $ajeno->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('presupuesto_id');
        $this->putJson("/api/v1/gastos/{$this->obra->gasto->id}", ['presupuesto_id' => $propio->id])
            ->assertOk()
            ->assertJsonPath('data.presupuesto_id', $propio->id);
    }

    public function test_no_se_puede_cambiar_la_obra_de_la_ficha()
    {
        $otra = Obra::factory()->create();

        $this->putJson("/api/v1/gastos/{$this->obra->gasto->id}", ['obra_id' => $otra->id, 'direccion_obra' => 'X'])->assertOk();

        $this->assertDatabaseHas('gastos', ['id' => $this->obra->gasto->id, 'obra_id' => $this->obra->id]);
    }
}
