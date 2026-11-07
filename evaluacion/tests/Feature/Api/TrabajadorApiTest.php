<?php

namespace Tests\Feature\Api;

use App\Models\LineaParteTrabajo;
use App\Models\Obra;
use App\Models\ParteTrabajo;
use App\Models\Trabajador;
use Tests\Feature\FeatureTestCase;

class TrabajadorApiTest extends FeatureTestCase
{
    private const CAMPOS = ['id', 'codigo', 'tipo', 'nombre', 'apellido', 'nombre_completo', 'telefono', 'coste_hora_estandar', 'horas_totales', 'total_euros', 'created_at', 'updated_at'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('jefe');
    }

    public function test_puede_listar_trabajadores()
    {
        Trabajador::factory()->count(3)->create();

        $this->assertListado($this->getJson('/api/v1/trabajadores'), self::CAMPOS, 3);
    }

    public function test_puede_crear_un_trabajador_con_tarifa_por_defecto()
    {
        $response = $this->postJson('/api/v1/trabajadores', ['nombre' => 'Luis', 'apellido' => 'Martín', 'tipo' => 'Peón']);

        $response->assertCreated()
            ->assertJsonStructure(['data' => self::CAMPOS])
            ->assertJsonPath('data.codigo', 'TRB0001')
            ->assertJsonPath('data.nombre_completo', 'Luis Martín');
        $this->assertImporte(20, $response->json('data.coste_hora_estandar'));
        $this->assertImporte(0, $response->json('data.horas_totales'));
    }

    public function test_valida_obligatorios_y_coste_no_negativo()
    {
        $this->postJson('/api/v1/trabajadores', ['coste_hora_estandar' => -5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre', 'apellido', 'coste_hora_estandar']);
    }

    public function test_puede_ver_actualizar_y_borrar_un_trabajador()
    {
        $t = Trabajador::factory()->create();

        $this->getJson("/api/v1/trabajadores/{$t->id}")->assertOk()->assertJsonPath('data.id', $t->id);
        $this->putJson("/api/v1/trabajadores/{$t->id}", ['nombre' => 'Pedro', 'apellido' => 'Ruiz', 'coste_hora_estandar' => 25])
            ->assertOk()->assertJsonPath('data.nombre', 'Pedro');
        $this->assertDatabaseHas('trabajadores', ['id' => $t->id, 'coste_hora_estandar' => 25]);
        $this->deleteJson("/api/v1/trabajadores/{$t->id}")->assertNoContent();
        $this->assertDatabaseMissing('trabajadores', ['id' => $t->id]);
    }

    public function test_no_se_borra_un_trabajador_con_partes()
    {
        $linea = LineaParteTrabajo::factory()->create();

        $this->deleteJson("/api/v1/trabajadores/{$linea->trabajador_id}")->assertStatus(409);
        $this->assertDatabaseHas('trabajadores', ['id' => $linea->trabajador_id]);
    }

    public function test_acumula_horas_y_coste_de_sus_lineas()
    {
        $t = Trabajador::factory()->create(['coste_hora_estandar' => 20]);
        LineaParteTrabajo::factory()->create(['trabajador_id' => $t->id, 'horas' => 8]);
        LineaParteTrabajo::factory()->create(['trabajador_id' => $t->id, 'horas' => 4]);

        $response = $this->getJson("/api/v1/trabajadores/{$t->id}");

        $this->assertImporte(12, $response->json('data.horas_totales'));
        $this->assertImporte(240, $response->json('data.total_euros'));
    }

    public function test_busca_por_nombre_y_apellido()
    {
        Trabajador::factory()->create(['nombre' => 'Luis', 'apellido' => 'Martín']);
        Trabajador::factory()->create(['nombre' => 'Luis', 'apellido' => 'Gómez']);

        $this->assertCount(1, $this->getJson('/api/v1/trabajadores?search=' . urlencode('Luis Martín'))->json('data'));
    }

    public function test_obras_en_las_que_ha_participado()
    {
        $t = Trabajador::factory()->create();
        $obra = Obra::factory()->create();
        $parte = ParteTrabajo::factory()->create(['obra_id' => $obra->id]);
        LineaParteTrabajo::factory()->create(['parte_trabajo_id' => $parte->id, 'trabajador_id' => $t->id]);
        Obra::factory()->create();

        $response = $this->getJson("/api/v1/trabajadores/{$t->id}/obras");

        $response->assertOk();
        $this->assertSame([$obra->id], array_column($response->json('data'), 'id'));
    }

    public function test_lineas_de_trabajo_del_trabajador_solo_son_las_suyas()
    {
        $t = Trabajador::factory()->create();
        $parte = ParteTrabajo::factory()->create();
        LineaParteTrabajo::factory()->create(['parte_trabajo_id' => $parte->id, 'trabajador_id' => $t->id, 'horas' => 6]);
        LineaParteTrabajo::factory()->create(['parte_trabajo_id' => $parte->id, 'horas' => 3]);

        $response = $this->getJson("/api/v1/trabajadores/{$t->id}/lineas-trabajo");

        $this->assertListado($response, ['id', 'parte_trabajo_id', 'trabajador_id', 'horas', 'coste_hora', 'coste_subtotal', 'fecha', 'obra_id'], 1);
        $this->assertSame($parte->obra_id, $response->json('data.0.obra_id'));
        $this->assertSame($parte->fecha->format('Y-m-d'), $response->json('data.0.fecha'));
    }

    public function test_usuario_sin_rol_no_puede_listar_trabajadores()
    {
        $x = Trabajador::factory()->create(['nombre' => 'Original']);
        $this->actuarComo(null);

        $this->getJson("/api/v1/trabajadores")->assertForbidden();
    }

    public function test_usuario_sin_rol_no_puede_crear_trabajadores()
    {
        $x = Trabajador::factory()->create(['nombre' => 'Original']);
        $antes = \DB::table('trabajadores')->count();
        $this->actuarComo(null);

        $this->postJson("/api/v1/trabajadores", ['nombre' => 'A', 'apellido' => 'B'])->assertForbidden();

        $this->assertDatabaseCount('trabajadores', $antes);
    }

    public function test_usuario_sin_rol_no_puede_modificar_trabajadores()
    {
        $x = Trabajador::factory()->create(['nombre' => 'Original']);
        $this->actuarComo(null);

        $this->putJson("/api/v1/trabajadores/{$x->id}", ['nombre' => 'Cambiado'])->assertForbidden();

        $this->assertDatabaseHas('trabajadores', ['id' => $x->id, 'nombre' => 'Original']);
    }

    public function test_usuario_sin_rol_no_puede_borrar_trabajadores()
    {
        $x = Trabajador::factory()->create(['nombre' => 'Original']);
        $this->actuarComo(null);

        $this->deleteJson("/api/v1/trabajadores/{$x->id}")->assertForbidden();

        $this->assertDatabaseHas('trabajadores', ['id' => $x->id]);
    }

    public function test_pagina_trabajadores()
    {
        Trabajador::factory()->count(25)->create();

        $this->assertPaginado($this->getJson("/api/v1/trabajadores?per_page=10"), 10, 25);
    }
}
