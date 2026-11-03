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
        $this->actuarComo('administrativa');
        $this->trabajador = Trabajador::factory()->create();
    }

    public function test_puede_listar_las_faltas_de_un_trabajador()
    {
        Falta::factory()->count(2)->create(['trabajador_id' => $this->trabajador->id]);
        Falta::factory()->create();

        $this->assertListado(
            $this->getJson("/api/v1/trabajadores/{$this->trabajador->id}/faltas"),
            ['id', 'trabajador_id', 'fecha_inicio', 'fecha_fin', 'tipo', 'motivo', 'dias'],
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
}
