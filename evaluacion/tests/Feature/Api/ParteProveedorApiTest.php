<?php

namespace Tests\Feature\Api;

use App\Models\Obra;
use App\Models\ParteProveedor;
use App\Models\Proveedor;
use Tests\Feature\FeatureTestCase;

class ParteProveedorApiTest extends FeatureTestCase
{
    private const CAMPOS = ['id', 'obra_id', 'proveedor_id', 'fecha', 'descripcion', 'documento', 'importe', 'estado_pago', 'created_at', 'updated_at'];

    private Obra $obra;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('administrativa');
        $this->obra = Obra::factory()->create();
    }

    private function url(string $resto = ''): string
    {
        return "/api/v1/obras/{$this->obra->id}/partes-proveedor{$resto}";
    }

    public function test_lista_los_partes_de_la_obra()
    {
        ParteProveedor::factory()->count(2)->create(['obra_id' => $this->obra->id]);
        ParteProveedor::factory()->create();

        $this->assertListado($this->getJson($this->url()), self::CAMPOS, 2);
    }

    public function test_crea_un_parte_pendiente_por_defecto_y_sin_documento()
    {
        $x = Proveedor::factory()->create();

        $response = $this->postJson($this->url(), ['proveedor_id' => $x->id, 'importe' => 250.5, 'fecha' => '2026-07-01']);

        $response->assertCreated()
            ->assertJsonStructure(['data' => self::CAMPOS])
            ->assertJsonPath('data.estado_pago', 'pendiente')
            ->assertJsonPath('data.documento', null)
            ->assertJsonPath('data.fecha', '2026-07-01');
        $this->assertImporte(250.5, $response->json('data.importe'));
    }

    public function test_valida_proveedor_importe_y_estado_de_pago()
    {
        $this->postJson($this->url(), ['importe' => 0, 'estado_pago' => 'a medias'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['proveedor_id', 'importe', 'estado_pago']);
    }

    public function test_su_importe_suma_en_la_ficha_de_gastos()
    {
        ParteProveedor::factory()->create(['obra_id' => $this->obra->id, 'importe' => 120]);
        ParteProveedor::factory()->create(['obra_id' => $this->obra->id, 'importe' => 80]);

        $g = $this->getJson("/api/v1/gastos/{$this->obra->gasto->id}")->json('data');

        $this->assertImporte(200, $g['coste_proveedores']);
        $this->assertImporte(200, $g['gastos']);
    }

    public function test_actualiza_y_borra_un_parte()
    {
        $parte = ParteProveedor::factory()->create(['obra_id' => $this->obra->id, 'estado_pago' => 'pendiente']);

        $this->putJson($this->url("/{$parte->id}"), ['estado_pago' => 'pagado'])->assertOk()->assertJsonPath('data.estado_pago', 'pagado');
        $this->deleteJson($this->url("/{$parte->id}"))->assertNoContent();
        $this->assertDatabaseMissing('partes_proveedor', ['id' => $parte->id]);
    }

    public function test_el_parte_de_otra_obra_devuelve_404()
    {
        $ajeno = ParteProveedor::factory()->create();

        $this->getJson($this->url("/{$ajeno->id}"))->assertNotFound();
    }
}
