<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\Ingreso;
use App\Models\LineaPresupuesto;
use App\Models\Obra;
use App\Models\Presupuesto;
use Tests\Feature\FeatureTestCase;

class IngresoApiTest extends FeatureTestCase
{
    private const CAMPOS = ['id', 'codigo', 'obra_id', 'cliente_id', 'nombre_obra', 'tipo', 'fecha', 'anio', 'documento', 'num_documento',
        'importe', 'total_ingresos', 'debe', 'created_at', 'updated_at'];

    private Obra $obra;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('jefe');
        $this->obra = Obra::factory()->create(['descripcion' => 'Reforma de baño']);
        $p = Presupuesto::factory()->create(['cliente_id' => $this->obra->cliente_id, 'obra_id' => $this->obra->id, 'estado' => 'aprobado', 'iva' => 21]);
        LineaPresupuesto::factory()->create(['presupuesto_id' => $p->id, 'uds' => 1, 'precio' => 1000]);
    }

    private function url(string $resto = ''): string
    {
        return "/api/v1/obras/{$this->obra->id}/ingresos{$resto}";
    }

    public function test_lista_los_ingresos_de_la_obra()
    {
        Ingreso::factory()->count(2)->create(['obra_id' => $this->obra->id]);
        Ingreso::factory()->create();

        $this->assertListado($this->getJson($this->url()), self::CAMPOS, 2);
    }

    public function test_crea_un_ingreso_con_el_cliente_y_la_descripcion_de_la_obra()
    {
        $response = $this->postJson($this->url(), ['importe' => 500, 'tipo' => 'Transferencia', 'fecha' => '2026-06-01']);

        $response->assertCreated()
            ->assertJsonStructure(['data' => self::CAMPOS])
            ->assertJsonPath('data.codigo', 'ING0001')
            ->assertJsonPath('data.obra_id', $this->obra->id)
            ->assertJsonPath('data.cliente_id', $this->obra->cliente_id)
            ->assertJsonPath('data.nombre_obra', 'Reforma de baño')
            ->assertJsonPath('data.anio', 2026);
        $this->assertImporte(500, $response->json('data.total_ingresos'));
        $this->assertImporte(710, $response->json('data.debe'));
    }

    public function test_total_ingresos_suma_todos_los_cobros_de_la_obra()
    {
        Ingreso::factory()->create(['obra_id' => $this->obra->id, 'importe' => 300]);
        $id = $this->postJson($this->url(), ['importe' => 200])->json('data.id');

        $response = $this->getJson($this->url("/{$id}"));

        $this->assertImporte(500, $response->json('data.total_ingresos'));
        $this->assertImporte(710, $response->json('data.debe'));
    }

    public function test_se_puede_cobrar_mas_de_lo_presupuestado()
    {
        $response = $this->postJson($this->url(), ['importe' => 1500]);

        $response->assertCreated();
        $this->assertImporte(-290, $response->json('data.debe'));
    }

    public function test_el_importe_es_obligatorio_y_positivo()
    {
        $this->postJson($this->url(), [])->assertUnprocessable()->assertJsonValidationErrors('importe');
        $this->postJson($this->url(), ['importe' => 0])->assertUnprocessable()->assertJsonValidationErrors('importe');
    }

    public function test_el_cliente_debe_ser_el_de_la_obra()
    {
        $otro = Cliente::factory()->create();

        $this->postJson($this->url(), ['importe' => 100, 'cliente_id' => $otro->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cliente_id');
    }

    public function test_el_estado_de_pago_de_la_obra_evoluciona()
    {
        $this->assertSame('pendiente', $this->getJson("/api/v1/obras/{$this->obra->id}")->json('data.estado_pago'));
        $this->postJson($this->url(), ['importe' => 500]);
        $this->assertSame('parcial', $this->getJson("/api/v1/obras/{$this->obra->id}")->json('data.estado_pago'));
        $this->postJson($this->url(), ['importe' => 710]);
        $this->assertSame('pagado', $this->getJson("/api/v1/obras/{$this->obra->id}")->json('data.estado_pago'));
    }

    public function test_actualiza_y_borra_un_ingreso()
    {
        $ingreso = Ingreso::factory()->create(['obra_id' => $this->obra->id, 'importe' => 100]);

        $this->putJson($this->url("/{$ingreso->id}"), ['importe' => 250])->assertOk();
        $this->assertDatabaseHas('ingresos', ['id' => $ingreso->id, 'importe' => 250]);
        $this->deleteJson($this->url("/{$ingreso->id}"))->assertNoContent();
        $this->assertDatabaseMissing('ingresos', ['id' => $ingreso->id]);
    }

    public function test_el_ingreso_de_otra_obra_devuelve_404()
    {
        $ajeno = Ingreso::factory()->create();

        $this->getJson($this->url("/{$ajeno->id}"))->assertNotFound();
        $this->deleteJson($this->url("/{$ajeno->id}"))->assertNotFound();
        $this->assertDatabaseHas('ingresos', ['id' => $ajeno->id]);
    }

    public function test_usuario_sin_rol_no_puede_listar_ingresos()
    {
        $x = Ingreso::factory()->create(['obra_id' => $this->obra->id, 'importe' => 100]);
        $this->actuarComo(null);

        $this->getJson("/api/v1/obras/{$this->obra->id}/ingresos")->assertForbidden();
    }

    public function test_usuario_sin_rol_no_puede_crear_ingresos()
    {
        $x = Ingreso::factory()->create(['obra_id' => $this->obra->id, 'importe' => 100]);
        $antes = \DB::table('ingresos')->count();
        $this->actuarComo(null);

        $this->postJson("/api/v1/obras/{$this->obra->id}/ingresos", ['importe' => 10])->assertForbidden();

        $this->assertDatabaseCount('ingresos', $antes);
    }

    public function test_usuario_sin_rol_no_puede_modificar_ingresos()
    {
        $x = Ingreso::factory()->create(['obra_id' => $this->obra->id, 'importe' => 100]);
        $this->actuarComo(null);

        $this->putJson("/api/v1/obras/{$this->obra->id}/ingresos/{$x->id}", ['importe' => 999])->assertForbidden();

        $this->assertDatabaseHas('ingresos', ['id' => $x->id, 'importe' => 100]);
    }

    public function test_usuario_sin_rol_no_puede_borrar_ingresos()
    {
        $x = Ingreso::factory()->create(['obra_id' => $this->obra->id, 'importe' => 100]);
        $this->actuarComo(null);

        $this->deleteJson("/api/v1/obras/{$this->obra->id}/ingresos/{$x->id}")->assertForbidden();

        $this->assertDatabaseHas('ingresos', ['id' => $x->id]);
    }

    public function test_pagina_ingresos()
    {
        Ingreso::factory()->count(25)->create(['obra_id' => $this->obra->id]);

        $this->assertPaginado($this->getJson("/api/v1/obras/{$this->obra->id}/ingresos?per_page=10"), 10, 25);
    }

    public function test_busca_por_tipo()
    {
        Ingreso::factory()->create(['obra_id' => $this->obra->id, 'tipo' => 'Efectivo']);
        Ingreso::factory()->create(['obra_id' => $this->obra->id, 'tipo' => 'Transferencia']);

        $this->assertCount(1, $this->getJson($this->url() . '?search=efectivo')->json('data'));
    }

    public function test_muestra_el_ingreso_de_su_obra()
    {
        $ingreso = Ingreso::factory()->create(['obra_id' => $this->obra->id]);

        $this->getJson($this->url("/{$ingreso->id}"))->assertOk()->assertJsonPath('data.obra_id', $this->obra->id);
    }
}
