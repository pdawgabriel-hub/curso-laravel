<?php

namespace Tests\Feature\Api;

use App\Models\LineaPresupuesto;
use App\Models\Presupuesto;
use Tests\Feature\FeatureTestCase;

class LineaPresupuestoApiTest extends FeatureTestCase
{
    private Presupuesto $presupuesto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('administrativa');
        $this->presupuesto = Presupuesto::factory()->create();
    }

    private function url(string $resto = ''): string
    {
        return "/api/v1/presupuestos/{$this->presupuesto->id}/lineas{$resto}";
    }

    public function test_lista_solo_las_lineas_del_presupuesto()
    {
        LineaPresupuesto::factory()->count(2)->create(['presupuesto_id' => $this->presupuesto->id]);
        LineaPresupuesto::factory()->create();

        $this->assertListado($this->getJson($this->url()), ['id', 'presupuesto_id', 'descripcion', 'uds', 'precio', 'total_linea'], 2);
    }

    public function test_crea_una_linea_y_calcula_su_total()
    {
        $response = $this->postJson($this->url(), ['descripcion' => 'Alicatado', 'uds' => 4, 'precio' => 25.5]);

        $response->assertCreated()->assertJsonPath('data.presupuesto_id', $this->presupuesto->id);
        $this->assertImporte(102, $response->json('data.total_linea'));
        $this->assertImporte(102, $this->getJson("/api/v1/presupuestos/{$this->presupuesto->id}")->json('data.base_imponible'));
    }

    public function test_valida_uds_y_precio()
    {
        $this->postJson($this->url(), ['uds' => -1, 'precio' => 'mucho'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['uds', 'precio']);
        $this->postJson($this->url(), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['uds', 'precio']);
    }

    public function test_actualiza_y_borra_una_linea()
    {
        $linea = LineaPresupuesto::factory()->create(['presupuesto_id' => $this->presupuesto->id, 'uds' => 1, 'precio' => 10]);

        $response = $this->putJson($this->url("/{$linea->id}"), ['uds' => 3, 'precio' => 10]);
        $this->assertImporte(30, $response->json('data.total_linea'));

        $this->deleteJson($this->url("/{$linea->id}"))->assertNoContent();
        $this->assertDatabaseMissing('lineas_presupuesto', ['id' => $linea->id]);
    }

    public function test_la_linea_de_otro_presupuesto_devuelve_404()
    {
        $ajena = LineaPresupuesto::factory()->create();

        $this->getJson($this->url("/{$ajena->id}"))->assertNotFound();
    }
}
