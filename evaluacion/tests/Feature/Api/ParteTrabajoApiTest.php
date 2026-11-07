<?php

namespace Tests\Feature\Api;

use App\Models\LineaParteTrabajo;
use App\Models\Obra;
use App\Models\ParteTrabajo;
use App\Models\Trabajador;
use Tests\Feature\FeatureTestCase;

class ParteTrabajoApiTest extends FeatureTestCase
{
    private const CAMPOS = ['id', 'obra_id', 'fecha', 'descripcion', 'horas_totales', 'coste_total', 'lineas', 'user_id', 'created_at', 'updated_at'];

    private Obra $obra;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('jefe');
        $this->obra = Obra::factory()->create();
    }

    private function url(string $resto = ''): string
    {
        return "/api/v1/obras/{$this->obra->id}/partes-trabajo{$resto}";
    }

    public function test_lista_los_partes_de_la_obra()
    {
        ParteTrabajo::factory()->count(2)->create(['obra_id' => $this->obra->id]);
        ParteTrabajo::factory()->create();

        $this->assertListado($this->getJson($this->url()), self::CAMPOS, 2);
    }

    public function test_crea_un_parte_con_sus_lineas_y_congela_la_tarifa()
    {
        $a = Trabajador::factory()->create(['coste_hora_estandar' => 20]);
        $b = Trabajador::factory()->create(['coste_hora_estandar' => 25]);

        $response = $this->postJson($this->url(), [
            'fecha' => '2026-09-15',
            'descripcion' => 'Demolición de tabiques',
            'lineas' => [
                ['trabajador_id' => $a->id, 'horas' => 8],
                ['trabajador_id' => $b->id, 'horas' => 4],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => self::CAMPOS])
            ->assertJsonPath('data.obra_id', $this->obra->id)
            ->assertJsonCount(2, 'data.lineas');
        $this->assertImporte(12, $response->json('data.horas_totales'));
        $this->assertImporte(260, $response->json('data.coste_total'));
        $this->assertDatabaseHas('lineas_parte_trabajo', ['trabajador_id' => $a->id, 'coste_hora' => 20]);
        $this->assertDatabaseHas('lineas_parte_trabajo', ['trabajador_id' => $b->id, 'coste_hora' => 25]);
    }

    public function test_respeta_un_coste_hora_explicito_en_la_linea()
    {
        $t = Trabajador::factory()->create(['coste_hora_estandar' => 20]);

        $response = $this->postJson($this->url(), [
            'descripcion' => 'Urgencia',
            'lineas' => [['trabajador_id' => $t->id, 'horas' => 2, 'coste_hora' => 30]],
        ]);

        $this->assertImporte(60, $response->json('data.coste_total'));
    }

    public function test_cambiar_la_tarifa_no_afecta_a_partes_anteriores()
    {
        $t = Trabajador::factory()->create(['coste_hora_estandar' => 20]);
        $id = $this->postJson($this->url(), ['descripcion' => 'Tarea', 'lineas' => [['trabajador_id' => $t->id, 'horas' => 8]]])->json('data.id');

        $this->putJson("/api/v1/trabajadores/{$t->id}", ['coste_hora_estandar' => 25])->assertOk();

        $this->assertImporte(160, $this->getJson($this->url("/{$id}"))->json('data.coste_total'));
    }

    public function test_valida_descripcion_y_horas_de_las_lineas()
    {
        $t = Trabajador::factory()->create();

        $this->postJson($this->url(), [])->assertUnprocessable()->assertJsonValidationErrors('descripcion');
        $this->postJson($this->url(), ['descripcion' => 'X', 'lineas' => [['trabajador_id' => $t->id, 'horas' => 25]]])
            ->assertUnprocessable()->assertJsonValidationErrors('lineas.0.horas');
        $this->postJson($this->url(), ['descripcion' => 'X', 'lineas' => [['trabajador_id' => $t->id, 'horas' => -1]]])
            ->assertUnprocessable()->assertJsonValidationErrors('lineas.0.horas');
        $this->postJson($this->url(), ['descripcion' => 'X', 'lineas' => [['horas' => 2]]])
            ->assertUnprocessable()->assertJsonValidationErrors('lineas.0.trabajador_id');
        $this->assertDatabaseCount('partes_trabajo', 0);
    }

    public function test_actualiza_la_cabecera_del_parte()
    {
        $parte = ParteTrabajo::factory()->create(['obra_id' => $this->obra->id]);

        $this->putJson($this->url("/{$parte->id}"), ['descripcion' => 'Alicatado', 'fecha' => '2026-10-01'])
            ->assertOk()
            ->assertJsonPath('data.descripcion', 'Alicatado')
            ->assertJsonPath('data.fecha', '2026-10-01');
    }

    public function test_borrar_un_parte_recalcula_al_trabajador()
    {
        $t = Trabajador::factory()->create();
        $parte = ParteTrabajo::factory()->create(['obra_id' => $this->obra->id]);
        LineaParteTrabajo::factory()->create(['parte_trabajo_id' => $parte->id, 'trabajador_id' => $t->id, 'horas' => 8]);

        $this->deleteJson($this->url("/{$parte->id}"))->assertNoContent();

        $this->assertDatabaseCount('lineas_parte_trabajo', 0);
        $d = $this->getJson("/api/v1/trabajadores/{$t->id}")->json('data');
        $this->assertImporte(0, $d['horas_totales']);
        $this->assertImporte(0, $d['total_euros']);
    }

    public function test_el_parte_de_otra_obra_devuelve_404()
    {
        $ajeno = ParteTrabajo::factory()->create();

        $this->getJson($this->url("/{$ajeno->id}"))->assertNotFound();
        $this->deleteJson($this->url("/{$ajeno->id}"))->assertNotFound();
    }

    public function test_usuario_sin_rol_no_puede_listar_partes()
    {
        $x = ParteTrabajo::factory()->create(['obra_id' => $this->obra->id, 'descripcion' => 'Original']);
        $this->actuarComo(null);

        $this->getJson("/api/v1/obras/{$this->obra->id}/partes-trabajo")->assertForbidden();
    }

    public function test_usuario_sin_rol_no_puede_crear_partes()
    {
        $x = ParteTrabajo::factory()->create(['obra_id' => $this->obra->id, 'descripcion' => 'Original']);
        $antes = \DB::table('partes_trabajo')->count();
        $this->actuarComo(null);

        $this->postJson("/api/v1/obras/{$this->obra->id}/partes-trabajo", ['descripcion' => 'A'])->assertForbidden();

        $this->assertDatabaseCount('partes_trabajo', $antes);
    }

    public function test_usuario_sin_rol_no_puede_modificar_partes()
    {
        $x = ParteTrabajo::factory()->create(['obra_id' => $this->obra->id, 'descripcion' => 'Original']);
        $this->actuarComo(null);

        $this->putJson("/api/v1/obras/{$this->obra->id}/partes-trabajo/{$x->id}", ['descripcion' => 'Cambiada'])->assertForbidden();

        $this->assertDatabaseHas('partes_trabajo', ['id' => $x->id, 'descripcion' => 'Original']);
    }

    public function test_usuario_sin_rol_no_puede_borrar_partes()
    {
        $x = ParteTrabajo::factory()->create(['obra_id' => $this->obra->id, 'descripcion' => 'Original']);
        $this->actuarComo(null);

        $this->deleteJson("/api/v1/obras/{$this->obra->id}/partes-trabajo/{$x->id}")->assertForbidden();

        $this->assertDatabaseHas('partes_trabajo', ['id' => $x->id]);
    }

    public function test_pagina_partes()
    {
        ParteTrabajo::factory()->count(25)->create(['obra_id' => $this->obra->id]);

        $this->assertPaginado($this->getJson("/api/v1/obras/{$this->obra->id}/partes-trabajo?per_page=10"), 10, 25);
    }

    public function test_busca_por_descripcion()
    {
        ParteTrabajo::factory()->create(['obra_id' => $this->obra->id, 'descripcion' => 'Demolición de tabiques']);
        ParteTrabajo::factory()->create(['obra_id' => $this->obra->id, 'descripcion' => 'Pintura']);

        $this->assertCount(1, $this->getJson($this->url() . '?search=tabiques')->json('data'));
    }

    public function test_muestra_el_parte_de_su_obra()
    {
        $parte = ParteTrabajo::factory()->create(['obra_id' => $this->obra->id]);

        $this->getJson($this->url("/{$parte->id}"))->assertOk()->assertJsonPath('data.obra_id', $this->obra->id);
    }

    public function test_el_propietario_es_el_usuario_autenticado_aunque_se_intente_falsear()
    {
        $yo = $this->actuarComo('administrativa');
        $otro = \App\Models\User::factory()->create();

        $this->postJson($this->url(), ['descripcion' => 'Tarea', 'user_id' => $otro->id])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $yo->id);
    }
}
