<?php

namespace Tests\Feature\Api;

use App\Models\Especialista;
use App\Models\Obra;
use App\Models\ParteEspecialista;
use Tests\Feature\FeatureTestCase;

class ParteEspecialistaApiTest extends FeatureTestCase
{
    private const CAMPOS = ['id', 'obra_id', 'especialista_id', 'fecha', 'descripcion', 'documento', 'importe', 'estado_pago', 'user_id', 'created_at', 'updated_at'];

    private Obra $obra;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('jefe');
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

    public function test_usuario_sin_rol_no_puede_listar_partes()
    {
        $x = ParteEspecialista::factory()->create(['obra_id' => $this->obra->id, 'estado_pago' => 'pendiente']);
        $this->actuarComo(null);

        $this->getJson("/api/v1/obras/{$this->obra->id}/partes-especialista")->assertForbidden();
    }

    public function test_usuario_sin_rol_no_puede_crear_partes()
    {
        $x = ParteEspecialista::factory()->create(['obra_id' => $this->obra->id, 'estado_pago' => 'pendiente']);
        $antes = \DB::table('partes_especialista')->count();
        $this->actuarComo(null);

        $this->postJson("/api/v1/obras/{$this->obra->id}/partes-especialista", ['especialista_id' => Especialista::factory()->create()->id, 'importe' => 10])->assertForbidden();

        $this->assertDatabaseCount('partes_especialista', $antes);
    }

    public function test_usuario_sin_rol_no_puede_modificar_partes()
    {
        $x = ParteEspecialista::factory()->create(['obra_id' => $this->obra->id, 'estado_pago' => 'pendiente']);
        $this->actuarComo(null);

        $this->putJson("/api/v1/obras/{$this->obra->id}/partes-especialista/{$x->id}", ['estado_pago' => 'pagado'])->assertForbidden();

        $this->assertDatabaseHas('partes_especialista', ['id' => $x->id, 'estado_pago' => 'pendiente']);
    }

    public function test_usuario_sin_rol_no_puede_borrar_partes()
    {
        $x = ParteEspecialista::factory()->create(['obra_id' => $this->obra->id, 'estado_pago' => 'pendiente']);
        $this->actuarComo(null);

        $this->deleteJson("/api/v1/obras/{$this->obra->id}/partes-especialista/{$x->id}")->assertForbidden();

        $this->assertDatabaseHas('partes_especialista', ['id' => $x->id]);
    }

    public function test_pagina_partes()
    {
        ParteEspecialista::factory()->count(25)->create(['obra_id' => $this->obra->id]);

        $this->assertPaginado($this->getJson("/api/v1/obras/{$this->obra->id}/partes-especialista?per_page=10"), 10, 25);
    }

    public function test_acepta_los_estados_de_pago_validos()
    {
        foreach (['pendiente', 'pagado'] as $estado) {
            $this->postJson($this->url(), ['especialista_id' => Especialista::factory()->create()->id, 'importe' => 10, 'estado_pago' => $estado])
                ->assertCreated()
                ->assertJsonPath('data.estado_pago', $estado);
        }
    }

    public function test_filtra_por_estado_de_pago_y_por_especialista()
    {
        $pagado = ParteEspecialista::factory()->create(['obra_id' => $this->obra->id, 'estado_pago' => 'pagado']);
        ParteEspecialista::factory()->create(['obra_id' => $this->obra->id, 'estado_pago' => 'pendiente']);

        $this->assertSame([$pagado->id], array_column($this->getJson($this->url() . '?estado_pago=pagado')->json('data'), 'id'));
        $this->assertSame([$pagado->id], array_column($this->getJson($this->url() . "?especialista_id={$pagado->especialista_id}")->json('data'), 'id'));
    }

    public function test_busca_por_descripcion_y_documento()
    {
        ParteEspecialista::factory()->create(['obra_id' => $this->obra->id, 'descripcion' => 'Azulejos del baño', 'documento' => 'FAC-1']);
        ParteEspecialista::factory()->create(['obra_id' => $this->obra->id, 'descripcion' => 'Pintura', 'documento' => 'FAC-2']);

        $this->assertCount(1, $this->getJson($this->url() . '?search=azulejos')->json('data'));
        $this->assertCount(1, $this->getJson($this->url() . '?search=FAC-2')->json('data'));
    }

    public function test_muestra_el_parte_de_su_obra()
    {
        $parte = ParteEspecialista::factory()->create(['obra_id' => $this->obra->id]);

        $this->getJson($this->url("/{$parte->id}"))->assertOk()->assertJsonPath('data.obra_id', $this->obra->id);
    }

    public function test_el_propietario_es_el_usuario_autenticado_aunque_se_intente_falsear()
    {
        $yo = $this->actuarComo('administrativa');
        $otro = \App\Models\User::factory()->create();

        $this->postJson($this->url(), ['especialista_id' => Especialista::factory()->create()->id, 'importe' => 10, 'user_id' => $otro->id])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $yo->id);
    }
}
