<?php

namespace Tests\Feature\Api;

use App\Models\Obra;
use App\Models\ParteEspecialista;
use App\Models\Especialista;
use Tests\Feature\FeatureTestCase;

class ParteEspecialistaApiTest extends FeatureTestCase
{
    private const CAMPOS = ['id', 'obra_id', 'especialista_id', 'fecha', 'descripcion', 'documento', 'importe', 'estado_pago', 'created_at', 'updated_at'];

    private Obra $obra;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('administrativa');
        $this->obra = Obra::factory()->create();
    }

    private function url(string $resto = ''): string
    {
        return "/api/v1/obras/{$this->obra->id}/partes-especialista{$resto}";
    }

    public function test_lista_los_partes_de_la_obra()
    {
        ParteEspecialista::factory()->count(2)->create(['obra_id' => $this->obra->id]);
        ParteEspecialista::factory()->create();

        $this->assertListado($this->getJson($this->url()), self::CAMPOS, 2);
    }

    public function test_crea_un_parte_pendiente_por_defecto_y_sin_documento()
    {
        $x = Especialista::factory()->create();

        $response = $this->postJson($this->url(), ['especialista_id' => $x->id, 'importe' => 250.5, 'fecha' => '2026-07-01']);

        $response->assertCreated()
            ->assertJsonStructure(['data' => self::CAMPOS])
            ->assertJsonPath('data.estado_pago', 'pendiente')
            ->assertJsonPath('data.documento', null)
            ->assertJsonPath('data.fecha', '2026-07-01');
        $this->assertImporte(250.5, $response->json('data.importe'));
    }

    public function test_valida_especialista_importe_y_estado_de_pago()
    {
        $this->postJson($this->url(), ['importe' => 0, 'estado_pago' => 'a medias'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['especialista_id', 'importe', 'estado_pago']);
    }

    public function test_su_importe_suma_en_la_ficha_de_gastos()
    {
        ParteEspecialista::factory()->create(['obra_id' => $this->obra->id, 'importe' => 120]);
        ParteEspecialista::factory()->create(['obra_id' => $this->obra->id, 'importe' => 80]);

        $g = $this->getJson("/api/v1/gastos/{$this->obra->gasto->id}")->json('data');

        $this->assertImporte(200, $g['coste_especialistas']);
        $this->assertImporte(200, $g['gastos']);
    }

    public function test_actualiza_y_borra_un_parte()
    {
        $parte = ParteEspecialista::factory()->create(['obra_id' => $this->obra->id, 'estado_pago' => 'pendiente']);

        $this->putJson($this->url("/{$parte->id}"), ['estado_pago' => 'pagado'])->assertOk()->assertJsonPath('data.estado_pago', 'pagado');
        $this->deleteJson($this->url("/{$parte->id}"))->assertNoContent();
        $this->assertDatabaseMissing('partes_especialista', ['id' => $parte->id]);
    }

    public function test_el_parte_de_otra_obra_devuelve_404()
    {
        $ajeno = ParteEspecialista::factory()->create();

        $this->getJson($this->url("/{$ajeno->id}"))->assertNotFound();
    }
}
