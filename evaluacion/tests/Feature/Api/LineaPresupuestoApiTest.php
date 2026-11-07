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
        $this->actuarComo('jefe');
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

    public function test_usuario_sin_rol_no_puede_listar_lineas()
    {
        $x = LineaPresupuesto::factory()->create(['presupuesto_id' => $this->presupuesto->id, 'uds' => 2]);
        $this->actuarComo(null);

        $this->getJson("/api/v1/presupuestos/{$this->presupuesto->id}/lineas")->assertForbidden();
    }

    public function test_usuario_sin_rol_no_puede_crear_lineas()
    {
        $x = LineaPresupuesto::factory()->create(['presupuesto_id' => $this->presupuesto->id, 'uds' => 2]);
        $antes = \DB::table('lineas_presupuesto')->count();
        $this->actuarComo(null);

        $this->postJson("/api/v1/presupuestos/{$this->presupuesto->id}/lineas", ['uds' => 1, 'precio' => 1])->assertForbidden();

        $this->assertDatabaseCount('lineas_presupuesto', $antes);
    }

    public function test_usuario_sin_rol_no_puede_modificar_lineas()
    {
        $x = LineaPresupuesto::factory()->create(['presupuesto_id' => $this->presupuesto->id, 'uds' => 2]);
        $this->actuarComo(null);

        $this->putJson("/api/v1/presupuestos/{$this->presupuesto->id}/lineas/{$x->id}", ['uds' => 9, 'precio' => 1])->assertForbidden();

        $this->assertDatabaseHas('lineas_presupuesto', ['id' => $x->id, 'uds' => 2]);
    }

    public function test_usuario_sin_rol_no_puede_borrar_lineas()
    {
        $x = LineaPresupuesto::factory()->create(['presupuesto_id' => $this->presupuesto->id, 'uds' => 2]);
        $this->actuarComo(null);

        $this->deleteJson("/api/v1/presupuestos/{$this->presupuesto->id}/lineas/{$x->id}")->assertForbidden();

        $this->assertDatabaseHas('lineas_presupuesto', ['id' => $x->id]);
    }

    public function test_pagina_lineas()
    {
        LineaPresupuesto::factory()->count(25)->create(['presupuesto_id' => $this->presupuesto->id]);

        $this->assertPaginado($this->getJson("/api/v1/presupuestos/{$this->presupuesto->id}/lineas?per_page=10"), 10, 25);
    }

    public function test_muestra_la_linea_de_su_presupuesto()
    {
        $linea = LineaPresupuesto::factory()->create(['presupuesto_id' => $this->presupuesto->id]);

        $this->getJson($this->url("/{$linea->id}"))->assertOk()->assertJsonPath('data.presupuesto_id', $this->presupuesto->id);
    }
}
