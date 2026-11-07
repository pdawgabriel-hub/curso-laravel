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

    public function test_usuario_sin_rol_no_puede_listar_lineas()
    {
        $x = LineaParteTrabajo::factory()->create(['parte_trabajo_id' => $this->parte->id, 'horas' => 2]);
        $this->actuarComo(null);

        $this->getJson("/api/v1/partes-trabajo/{$this->parte->id}/lineas")->assertForbidden();
    }

    public function test_usuario_sin_rol_no_puede_crear_lineas()
    {
        $x = LineaParteTrabajo::factory()->create(['parte_trabajo_id' => $this->parte->id, 'horas' => 2]);
        $antes = \DB::table('lineas_parte_trabajo')->count();
        $this->actuarComo(null);

        $this->postJson("/api/v1/partes-trabajo/{$this->parte->id}/lineas", ['trabajador_id' => Trabajador::factory()->create()->id, 'horas' => 1])->assertForbidden();

        $this->assertDatabaseCount('lineas_parte_trabajo', $antes);
    }

    public function test_usuario_sin_rol_no_puede_modificar_lineas()
    {
        $x = LineaParteTrabajo::factory()->create(['parte_trabajo_id' => $this->parte->id, 'horas' => 2]);
        $this->actuarComo(null);

        $this->putJson("/api/v1/partes-trabajo/{$this->parte->id}/lineas/{$x->id}", ['horas' => 8])->assertForbidden();

        $this->assertDatabaseHas('lineas_parte_trabajo', ['id' => $x->id, 'horas' => 2]);
    }

    public function test_usuario_sin_rol_no_puede_borrar_lineas()
    {
        $x = LineaParteTrabajo::factory()->create(['parte_trabajo_id' => $this->parte->id, 'horas' => 2]);
        $this->actuarComo(null);

        $this->deleteJson("/api/v1/partes-trabajo/{$this->parte->id}/lineas/{$x->id}")->assertForbidden();

        $this->assertDatabaseHas('lineas_parte_trabajo', ['id' => $x->id]);
    }

    public function test_pagina_lineas()
    {
        LineaParteTrabajo::factory()->count(25)->create(['parte_trabajo_id' => $this->parte->id]);

        $this->assertPaginado($this->getJson("/api/v1/partes-trabajo/{$this->parte->id}/lineas?per_page=10"), 10, 25);
    }

    public function test_muestra_la_linea_de_su_parte()
    {
        $linea = LineaParteTrabajo::factory()->create(['parte_trabajo_id' => $this->parte->id]);

        $this->getJson($this->url("/{$linea->id}"))->assertOk()->assertJsonPath('data.parte_trabajo_id', $this->parte->id);
    }

    public function test_lote_crea_una_linea_por_trabajador_con_su_tarifa()
    {
        $a = Trabajador::factory()->create(['coste_hora_estandar' => 20]);
        $b = Trabajador::factory()->create(['coste_hora_estandar' => 25]);

        $response = $this->postJson($this->url('/lote'), ['trabajador_ids' => [$a->id, $b->id], 'horas' => 8]);

        $response->assertCreated()->assertJsonCount(2, 'data');
        $this->assertEqualsCanonicalizing([$a->id, $b->id], array_column($response->json('data'), 'trabajador_id'));
        $this->assertDatabaseHas('lineas_parte_trabajo', ['parte_trabajo_id' => $this->parte->id, 'trabajador_id' => $a->id, 'horas' => 8, 'coste_hora' => 20]);
        $this->assertDatabaseHas('lineas_parte_trabajo', ['parte_trabajo_id' => $this->parte->id, 'trabajador_id' => $b->id, 'horas' => 8, 'coste_hora' => 25]);
    }

    public function test_lote_valida_trabajadores_y_horas()
    {
        $a = Trabajador::factory()->create();

        $this->postJson($this->url('/lote'), [])->assertUnprocessable()->assertJsonValidationErrors(['trabajador_ids', 'horas']);
        $this->postJson($this->url('/lote'), ['trabajador_ids' => [], 'horas' => 8])->assertUnprocessable()->assertJsonValidationErrors('trabajador_ids');
        $this->postJson($this->url('/lote'), ['trabajador_ids' => [$a->id], 'horas' => 25])->assertUnprocessable()->assertJsonValidationErrors('horas');
    }

    public function test_lote_no_crea_nada_si_algun_trabajador_no_existe()
    {
        $a = Trabajador::factory()->create();

        $this->postJson($this->url('/lote'), ['trabajador_ids' => [$a->id, 999], 'horas' => 8])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('trabajador_ids.1');

        $this->assertDatabaseCount('lineas_parte_trabajo', 0);
    }

    public function test_lote_rechaza_trabajadores_repetidos()
    {
        $a = Trabajador::factory()->create();

        $this->postJson($this->url('/lote'), ['trabajador_ids' => [$a->id, $a->id], 'horas' => 8])->assertUnprocessable();

        $this->assertDatabaseCount('lineas_parte_trabajo', 0);
    }

    public function test_lote_respeta_el_maximo_configurado()
    {
        config(['gestionobras.max_lineas_lote' => 2]);
        $ids = Trabajador::factory()->count(3)->create()->pluck('id')->all();

        $this->postJson($this->url('/lote'), ['trabajador_ids' => $ids, 'horas' => 8])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('trabajador_ids');
        $this->assertDatabaseCount('lineas_parte_trabajo', 0);

        $this->postJson($this->url('/lote'), ['trabajador_ids' => array_slice($ids, 0, 2), 'horas' => 8])->assertCreated();
        $this->assertDatabaseCount('lineas_parte_trabajo', 2);
    }

    public function test_usuario_sin_rol_no_puede_crear_un_lote()
    {
        $a = Trabajador::factory()->create();
        $this->actuarComo(null);

        $this->postJson($this->url('/lote'), ['trabajador_ids' => [$a->id], 'horas' => 8])->assertForbidden();

        $this->assertDatabaseCount('lineas_parte_trabajo', 0);
    }
}
