<?php

namespace Tests\Feature\Api;

use App\Models\Especialista;
use App\Models\Obra;
use App\Models\ParteEspecialista;
use Tests\Feature\FeatureTestCase;

class EspecialistaApiTest extends FeatureTestCase
{
    private const CAMPOS = ['id', 'codigo', 'tipo', 'referencia', 'comunicacion_parte', 'nombre', 'notas', 'telefono', 'correo', 'importe', 'created_at', 'updated_at'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('jefe');
    }

    public function test_puede_listar_especialistas()
    {
        Especialista::factory()->count(2)->create();

        $this->assertListado($this->getJson('/api/v1/especialistas'), self::CAMPOS, 2);
    }

    public function test_crea_un_especialista()
    {
        $this->postJson('/api/v1/especialistas', ['nombre' => 'Marta Gil', 'tipo' => 'Arquitecto', 'importe' => 300])
            ->assertCreated()
            ->assertJsonStructure(['data' => self::CAMPOS])
            ->assertJsonPath('data.codigo', 'ESP0001');
    }

    public function test_valida_nombre_e_importe_no_negativo()
    {
        $this->postJson('/api/v1/especialistas', ['importe' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre', 'importe']);
    }

    public function test_busca_por_tipo()
    {
        Especialista::factory()->create(['tipo' => 'Fontanero']);
        Especialista::factory()->create(['tipo' => 'Arquitecto']);

        $this->assertCount(1, $this->getJson('/api/v1/especialistas?search=fontanero')->json('data'));
    }

    public function test_no_se_borra_un_especialista_con_partes()
    {
        $parte = ParteEspecialista::factory()->create();

        $this->deleteJson("/api/v1/especialistas/{$parte->especialista_id}")->assertStatus(409);
    }

    public function test_obras_en_las_que_ha_participado()
    {
        $e = Especialista::factory()->create();
        $obra = Obra::factory()->create();
        ParteEspecialista::factory()->create(['obra_id' => $obra->id, 'especialista_id' => $e->id]);

        $this->assertSame([$obra->id], array_column($this->getJson("/api/v1/especialistas/{$e->id}/obras")->json('data'), 'id'));
    }

    public function test_usuario_sin_rol_no_puede_listar_especialistas()
    {
        $x = Especialista::factory()->create(['nombre' => 'Original']);
        $this->actuarComo(null);

        $this->getJson("/api/v1/especialistas")->assertForbidden();
    }

    public function test_usuario_sin_rol_no_puede_crear_especialistas()
    {
        $x = Especialista::factory()->create(['nombre' => 'Original']);
        $antes = \DB::table('especialistas')->count();
        $this->actuarComo(null);

        $this->postJson("/api/v1/especialistas", ['nombre' => 'A'])->assertForbidden();

        $this->assertDatabaseCount('especialistas', $antes);
    }

    public function test_usuario_sin_rol_no_puede_modificar_especialistas()
    {
        $x = Especialista::factory()->create(['nombre' => 'Original']);
        $this->actuarComo(null);

        $this->putJson("/api/v1/especialistas/{$x->id}", ['nombre' => 'Cambiado'])->assertForbidden();

        $this->assertDatabaseHas('especialistas', ['id' => $x->id, 'nombre' => 'Original']);
    }

    public function test_usuario_sin_rol_no_puede_borrar_especialistas()
    {
        $x = Especialista::factory()->create(['nombre' => 'Original']);
        $this->actuarComo(null);

        $this->deleteJson("/api/v1/especialistas/{$x->id}")->assertForbidden();

        $this->assertDatabaseHas('especialistas', ['id' => $x->id]);
    }

    public function test_pagina_especialistas()
    {
        Especialista::factory()->count(25)->create();

        $this->assertPaginado($this->getJson("/api/v1/especialistas?per_page=10"), 10, 25);
    }
}
