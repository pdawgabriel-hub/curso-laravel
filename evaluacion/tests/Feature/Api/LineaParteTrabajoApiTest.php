<?php

namespace Tests\Feature\Api;

use App\Models\LineaParteTrabajo;
use App\Models\ParteTrabajo;
use App\Models\Trabajador;
use Tests\Feature\FeatureTestCase;

class LineaParteTrabajoApiTest extends FeatureTestCase
{
    private ParteTrabajo $parte;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('administrativa');
        $this->parte = ParteTrabajo::factory()->create();
    }

    private function url(string $resto = ''): string
    {
        return "/api/v1/partes-trabajo/{$this->parte->id}/lineas{$resto}";
    }

    public function test_lista_las_lineas_del_parte()
    {
        LineaParteTrabajo::factory()->count(2)->create(['parte_trabajo_id' => $this->parte->id]);
        LineaParteTrabajo::factory()->create();

        $this->assertListado($this->getJson($this->url()), ['id', 'parte_trabajo_id', 'trabajador_id', 'horas', 'coste_hora', 'coste_subtotal', 'fecha', 'obra_id'], 2);
    }

    public function test_anade_una_linea_copiando_la_tarifa_del_trabajador()
    {
        $t = Trabajador::factory()->create(['coste_hora_estandar' => 22.5]);

        $response = $this->postJson($this->url(), ['trabajador_id' => $t->id, 'horas' => 4]);

        $response->assertCreated()->assertJsonPath('data.obra_id', $this->parte->obra_id);
        $this->assertImporte(22.5, $response->json('data.coste_hora'));
        $this->assertImporte(90, $response->json('data.coste_subtotal'));
    }

    public function test_valida_trabajador_y_rango_de_horas()
    {
        $this->postJson($this->url(), ['horas' => 30])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['trabajador_id', 'horas']);
    }

    public function test_actualizar_horas_recalcula_el_subtotal()
    {
        $linea = LineaParteTrabajo::factory()->create(['parte_trabajo_id' => $this->parte->id, 'horas' => 2, 'coste_hora' => 20]);

        $response = $this->putJson($this->url("/{$linea->id}"), ['horas' => 6]);

        $this->assertImporte(120, $response->json('data.coste_subtotal'));
    }

    public function test_borra_una_linea()
    {
        $linea = LineaParteTrabajo::factory()->create(['parte_trabajo_id' => $this->parte->id]);

        $this->deleteJson($this->url("/{$linea->id}"))->assertNoContent();
        $this->assertDatabaseMissing('lineas_parte_trabajo', ['id' => $linea->id]);
    }

    public function test_la_linea_de_otro_parte_devuelve_404()
    {
        $ajena = LineaParteTrabajo::factory()->create();

        $this->putJson($this->url("/{$ajena->id}"), ['horas' => 1])->assertNotFound();
    }
}
