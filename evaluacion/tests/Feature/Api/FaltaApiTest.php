<?php

namespace Tests\Feature\Api;

use App\Models\Falta;
use App\Models\Trabajador;
use Tests\Feature\FeatureTestCase;

class FaltaApiTest extends FeatureTestCase
{
    private Trabajador $trabajador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('jefe');
        $this->trabajador = Trabajador::factory()->create();
    }

    public function test_puede_listar_las_faltas_de_un_trabajador()
    {
        Falta::factory()->count(2)->create(['trabajador_id' => $this->trabajador->id]);
        Falta::factory()->create();

        $this->assertListado(
            $this->getJson("/api/v1/trabajadores/{$this->trabajador->id}/faltas"),
            ['id', 'trabajador_id', 'fecha_inicio', 'fecha_fin', 'tipo', 'motivo', 'dias', 'user_id'],
            2
        );
    }

    public function test_crea_una_falta_y_calcula_los_dias_inclusive()
    {
        $response = $this->postJson("/api/v1/trabajadores/{$this->trabajador->id}/faltas", [
            'fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-08-07', 'tipo' => 'vacaciones',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.dias', 5)
            ->assertJsonPath('data.trabajador_id', $this->trabajador->id)
            ->assertJsonPath('data.fecha_inicio', '2026-08-03');
    }

    public function test_una_falta_de_un_solo_dia_cuenta_uno()
    {
        $this->postJson("/api/v1/trabajadores/{$this->trabajador->id}/faltas", [
            'fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-08-03', 'tipo' => 'personal',
        ])->assertCreated()->assertJsonPath('data.dias', 1);
    }

    public function test_el_fin_no_puede_ser_anterior_al_inicio()
    {
        $this->postJson("/api/v1/trabajadores/{$this->trabajador->id}/faltas", [
            'fecha_inicio' => '2026-08-07', 'fecha_fin' => '2026-08-03',
        ])->assertUnprocessable()->assertJsonValidationErrors('fecha_fin');
    }

    public function test_el_tipo_debe_ser_valido()
    {
        $this->postJson("/api/v1/trabajadores/{$this->trabajador->id}/faltas", [
            'fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-08-03', 'tipo' => 'fiesta',
        ])->assertUnprocessable()->assertJsonValidationErrors('tipo');
    }

    public function test_actualiza_y_borra_una_falta()
    {
        $falta = Falta::factory()->create(['trabajador_id' => $this->trabajador->id]);
        $url = "/api/v1/trabajadores/{$this->trabajador->id}/faltas/{$falta->id}";

        $this->putJson($url, ['fecha_inicio' => '2026-01-10', 'fecha_fin' => '2026-01-11', 'tipo' => 'baja_medica'])
            ->assertOk()->assertJsonPath('data.tipo', 'baja_medica')->assertJsonPath('data.dias', 2);
        $this->deleteJson($url)->assertNoContent();
        $this->assertDatabaseMissing('faltas', ['id' => $falta->id]);
    }

    public function test_la_falta_de_otro_trabajador_devuelve_404()
    {
        $ajena = Falta::factory()->create();

        $this->getJson("/api/v1/trabajadores/{$this->trabajador->id}/faltas/{$ajena->id}")->assertNotFound();
    }

    public function test_usuario_sin_rol_no_puede_listar_faltas()
    {
        $x = Falta::factory()->create(['trabajador_id' => $this->trabajador->id, 'tipo' => 'personal']);
        $this->actuarComo(null);

        $this->getJson("/api/v1/trabajadores/{$this->trabajador->id}/faltas")->assertForbidden();
    }

    public function test_usuario_sin_rol_no_puede_crear_faltas()
    {
        $x = Falta::factory()->create(['trabajador_id' => $this->trabajador->id, 'tipo' => 'personal']);
        $antes = \DB::table('faltas')->count();
        $this->actuarComo(null);

        $this->postJson("/api/v1/trabajadores/{$this->trabajador->id}/faltas", ['fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-01-02'])->assertForbidden();

        $this->assertDatabaseCount('faltas', $antes);
    }

    public function test_usuario_sin_rol_no_puede_modificar_faltas()
    {
        $x = Falta::factory()->create(['trabajador_id' => $this->trabajador->id, 'tipo' => 'personal']);
        $this->actuarComo(null);

        $this->putJson("/api/v1/trabajadores/{$this->trabajador->id}/faltas/{$x->id}", ['fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-01-02', 'tipo' => 'otro'])->assertForbidden();

        $this->assertDatabaseHas('faltas', ['id' => $x->id, 'tipo' => 'personal']);
    }

    public function test_usuario_sin_rol_no_puede_borrar_faltas()
    {
        $x = Falta::factory()->create(['trabajador_id' => $this->trabajador->id, 'tipo' => 'personal']);
        $this->actuarComo(null);

        $this->deleteJson("/api/v1/trabajadores/{$this->trabajador->id}/faltas/{$x->id}")->assertForbidden();

        $this->assertDatabaseHas('faltas', ['id' => $x->id]);
    }

    public function test_pagina_faltas()
    {
        Falta::factory()->count(25)->create(['trabajador_id' => $this->trabajador->id]);

        $this->assertPaginado($this->getJson("/api/v1/trabajadores/{$this->trabajador->id}/faltas?per_page=10"), 10, 25);
    }

    public function test_acepta_todos_los_tipos_validos()
    {
        foreach (['vacaciones', 'baja_medica', 'personal', 'injustificada', 'otro'] as $tipo) {
            $this->postJson("/api/v1/trabajadores/{$this->trabajador->id}/faltas", [
                'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-01-01', 'tipo' => $tipo,
            ])->assertCreated()->assertJsonPath('data.tipo', $tipo);
        }
    }

    public function test_filtra_por_tipo()
    {
        Falta::factory()->count(2)->create(['trabajador_id' => $this->trabajador->id, 'tipo' => 'vacaciones']);
        $baja = Falta::factory()->create(['trabajador_id' => $this->trabajador->id, 'tipo' => 'baja_medica']);

        $response = $this->getJson("/api/v1/trabajadores/{$this->trabajador->id}/faltas?tipo=baja_medica");

        $this->assertSame([$baja->id], array_column($response->json('data'), 'id'));
    }

    public function test_busca_por_motivo()
    {
        Falta::factory()->create(['trabajador_id' => $this->trabajador->id, 'motivo' => 'Operación de rodilla']);
        Falta::factory()->create(['trabajador_id' => $this->trabajador->id, 'motivo' => 'Asuntos propios']);

        $this->assertCount(1, $this->getJson("/api/v1/trabajadores/{$this->trabajador->id}/faltas?search=rodilla")->json('data'));
    }

    public function test_muestra_la_falta_de_su_trabajador()
    {
        $falta = Falta::factory()->create(['trabajador_id' => $this->trabajador->id]);

        $this->getJson("/api/v1/trabajadores/{$this->trabajador->id}/faltas/{$falta->id}")
            ->assertOk()
            ->assertJsonPath('data.trabajador_id', $this->trabajador->id);
    }

    public function test_el_propietario_es_el_usuario_autenticado_aunque_se_intente_falsear()
    {
        $yo = $this->actuarComo('administrativa');
        $otro = \App\Models\User::factory()->create();

        $this->postJson("/api/v1/trabajadores/{$this->trabajador->id}/faltas", ['fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-01-01', 'user_id' => $otro->id])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $yo->id);
    }
}
