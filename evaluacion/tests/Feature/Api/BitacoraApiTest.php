<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\Obra;
use App\Models\ParteTrabajo;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class BitacoraApiTest extends FeatureTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->actuarComo('administrativa');
    }

    public function test_anade_una_observacion_con_autor_automatico()
    {
        $cliente = Cliente::factory()->create();

        $response = $this->postJson("/api/v1/clientes/{$cliente->id}/observaciones", ['texto' => 'Prefiere que le llamen por la tarde']);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'texto', 'user_id', 'usuario', 'created_at']])
            ->assertJsonPath('data.user_id', $this->user->id)
            ->assertJsonPath('data.usuario', $this->user->name);
        $this->assertDatabaseHas('observaciones', ['observable_type' => 'clientes', 'observable_id' => $cliente->id]);
    }

    public function test_el_autor_no_se_puede_falsear()
    {
        $cliente = Cliente::factory()->create();
        $otro = User::factory()->create();

        $this->postJson("/api/v1/clientes/{$cliente->id}/observaciones", ['texto' => 'Nota', 'user_id' => $otro->id])
            ->assertJsonPath('data.user_id', $this->user->id);
    }

    public function test_lista_las_observaciones_de_la_mas_nueva_a_la_mas_antigua()
    {
        $obra = Obra::factory()->create();
        $this->postJson("/api/v1/obras/{$obra->id}/observaciones", ['texto' => 'Primera']);
        $this->postJson("/api/v1/obras/{$obra->id}/observaciones", ['texto' => 'Segunda']);
        $this->postJson('/api/v1/obras/' . Obra::factory()->create()->id . '/observaciones', ['texto' => 'De otra obra']);

        $response = $this->getJson("/api/v1/obras/{$obra->id}/observaciones");

        $this->assertListado($response, ['id', 'texto', 'user_id', 'usuario', 'created_at'], 2);
        $this->assertSame('Segunda', $response->json('data.0.texto'));
    }

    public function test_incidencias_separadas_de_las_observaciones()
    {
        $obra = Obra::factory()->create();
        $this->postJson("/api/v1/obras/{$obra->id}/observaciones", ['texto' => 'Seguimiento']);
        $this->postJson("/api/v1/obras/{$obra->id}/incidencias", ['texto' => 'Fuga de agua'])->assertCreated();

        $this->assertSame(['Fuga de agua'], array_column($this->getJson("/api/v1/obras/{$obra->id}/incidencias")->json('data'), 'texto'));
        $this->assertDatabaseHas('incidencias', ['incidentable_type' => 'obras', 'incidentable_id' => $obra->id]);
    }

    public function test_incidencias_de_un_parte_de_trabajo()
    {
        $parte = ParteTrabajo::factory()->create();

        $this->postJson("/api/v1/partes-trabajo/{$parte->id}/incidencias", ['texto' => 'Falta material'])->assertCreated();
        $this->assertDatabaseHas('incidencias', ['incidentable_type' => 'partes-trabajo', 'incidentable_id' => $parte->id]);
    }

    public function test_el_texto_es_obligatorio()
    {
        $cliente = Cliente::factory()->create();

        $this->postJson("/api/v1/clientes/{$cliente->id}/observaciones", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('texto');
    }

    public function test_entidad_sin_ese_tipo_de_bitacora_devuelve_404()
    {
        $cliente = Cliente::factory()->create();

        $this->postJson("/api/v1/clientes/{$cliente->id}/incidencias", ['texto' => 'X'])->assertNotFound();
        $this->getJson('/api/v1/clientes/999/observaciones')->assertNotFound();
    }

    public function test_borra_una_observacion_y_una_incidencia()
    {
        $obra = Obra::factory()->create();
        $o = $this->postJson("/api/v1/obras/{$obra->id}/observaciones", ['texto' => 'A'])->json('data.id');
        $i = $this->postJson("/api/v1/obras/{$obra->id}/incidencias", ['texto' => 'B'])->json('data.id');

        $this->deleteJson("/api/v1/observaciones/{$o}")->assertNoContent();
        $this->deleteJson("/api/v1/incidencias/{$i}")->assertNoContent();
        $this->assertDatabaseCount('observaciones', 0);
        $this->assertDatabaseCount('incidencias', 0);
    }

    public function test_borrar_la_entidad_borra_su_bitacora()
    {
        $cliente = Cliente::factory()->create();
        $this->postJson("/api/v1/clientes/{$cliente->id}/observaciones", ['texto' => 'A']);

        $this->deleteJson("/api/v1/clientes/{$cliente->id}")->assertNoContent();
        $this->assertDatabaseCount('observaciones', 0);
    }
}
