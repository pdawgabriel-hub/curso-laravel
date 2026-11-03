<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\Presupuesto;
use Tests\Feature\FeatureTestCase;

class ClienteApiTest extends FeatureTestCase
{
    private const CAMPOS = ['id', 'codigo', 'nombre', 'apellidos', 'nombre_completo', 'nif', 'direccion', 'cod_postal', 'telefono', 'created_at', 'updated_at'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('administrativa');
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'Juan',
            'apellidos' => 'Pérez López',
            'nif' => '12345678Z',
            'direccion' => 'Calle Mayor 1',
            'cod_postal' => '28001',
            'telefono' => '600111222',
        ], $cambios);
    }

    public function test_puede_listar_clientes()
    {
        Cliente::factory()->count(3)->create();

        $this->assertListado($this->getJson('/api/v1/clientes'), self::CAMPOS, 3);
    }

    public function test_el_listado_se_pagina_con_per_page()
    {
        Cliente::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/clientes?per_page=2');

        $this->assertCount(2, $response->json('data'));
        $this->assertSame(5, $response->json('meta.total'));
    }

    public function test_puede_crear_un_cliente()
    {
        $response = $this->postJson('/api/v1/clientes', $this->datos());

        $response->assertCreated()
            ->assertJsonStructure(['data' => self::CAMPOS])
            ->assertJsonPath('data.codigo', 'CLI0001')
            ->assertJsonPath('data.nombre_completo', 'Juan Pérez López');
        $this->assertDatabaseHas('clientes', ['nif' => '12345678Z', 'codigo' => 'CLI0001']);
    }

    public function test_los_codigos_son_secuenciales_y_no_se_reutilizan()
    {
        $this->postJson('/api/v1/clientes', $this->datos(['nif' => null]))->assertJsonPath('data.codigo', 'CLI0001');
        $id = $this->postJson('/api/v1/clientes', $this->datos(['nif' => null]))->assertJsonPath('data.codigo', 'CLI0002')->json('data.id');

        $this->deleteJson("/api/v1/clientes/{$id}")->assertNoContent();

        $this->postJson('/api/v1/clientes', $this->datos(['nif' => null]))->assertJsonPath('data.codigo', 'CLI0003');
    }

    public function test_el_codigo_no_se_puede_fijar_desde_la_peticion()
    {
        $this->postJson('/api/v1/clientes', $this->datos(['codigo' => 'HACK999']))
            ->assertCreated()
            ->assertJsonPath('data.codigo', 'CLI0001');
    }

    public function test_valida_los_campos_obligatorios()
    {
        $this->postJson('/api/v1/clientes', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre', 'direccion', 'telefono']);
    }

    public function test_el_nif_es_unico()
    {
        Cliente::factory()->create(['nif' => '12345678Z']);

        $this->postJson('/api/v1/clientes', $this->datos())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nif');
    }

    public function test_el_nif_puede_quedar_vacio_en_varios_clientes()
    {
        $this->postJson('/api/v1/clientes', $this->datos(['nif' => null]))->assertCreated();
        $this->postJson('/api/v1/clientes', $this->datos(['nif' => null]))->assertCreated();
    }

    public function test_puede_ver_un_cliente()
    {
        $cliente = Cliente::factory()->create();

        $this->getJson("/api/v1/clientes/{$cliente->id}")
            ->assertOk()
            ->assertJsonStructure(['data' => self::CAMPOS])
            ->assertJsonPath('data.id', $cliente->id);
    }

    public function test_cliente_inexistente_devuelve_404()
    {
        $this->getJson('/api/v1/clientes/999')->assertNotFound();
    }

    public function test_puede_actualizar_un_cliente()
    {
        $cliente = Cliente::factory()->create(['nif' => '11111111H']);

        $this->putJson("/api/v1/clientes/{$cliente->id}", $this->datos(['nif' => '11111111H', 'telefono' => '699000000']))
            ->assertOk()
            ->assertJsonPath('data.telefono', '699000000');
        $this->assertDatabaseHas('clientes', ['id' => $cliente->id, 'telefono' => '699000000']);
    }

    public function test_puede_borrar_un_cliente_sin_presupuestos()
    {
        $cliente = Cliente::factory()->create();

        $this->deleteJson("/api/v1/clientes/{$cliente->id}")->assertNoContent();
        $this->assertDatabaseMissing('clientes', ['id' => $cliente->id]);
    }

    public function test_no_se_borra_un_cliente_con_presupuestos()
    {
        $cliente = Cliente::factory()->create();
        Presupuesto::factory()->create(['cliente_id' => $cliente->id]);

        $this->deleteJson("/api/v1/clientes/{$cliente->id}")->assertStatus(409);
        $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
    }

    public function test_busqueda_multipalabra_en_columnas_distintas()
    {
        Cliente::factory()->create(['nombre' => 'Juan', 'apellidos' => 'Pérez López']);
        Cliente::factory()->create(['nombre' => 'Juan', 'apellidos' => 'García']);
        Cliente::factory()->create(['nombre' => 'Ana', 'apellidos' => 'Pérez']);

        $response = $this->getJson('/api/v1/clientes?search=' . urlencode('Juan Pérez'));

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Pérez López', $response->json('data.0.apellidos'));
    }

    public function test_busqueda_por_nif_y_telefono()
    {
        Cliente::factory()->create(['nif' => '99999999R', 'telefono' => '611223344']);
        Cliente::factory()->create();

        $this->assertCount(1, $this->getJson('/api/v1/clientes?search=99999999R')->json('data'));
        $this->assertCount(1, $this->getJson('/api/v1/clientes?search=611223344')->json('data'));
    }

    public function test_ordena_por_campo_y_sentido()
    {
        Cliente::factory()->create(['nombre' => 'Beatriz']);
        Cliente::factory()->create(['nombre' => 'Andrés']);

        $response = $this->getJson('/api/v1/clientes?sort=nombre&order=asc');

        $this->assertSame('Andrés', $response->json('data.0.nombre'));
    }

    public function test_usuario_sin_rol_no_puede_crear_clientes()
    {
        $this->actuarComo(null);

        $this->postJson('/api/v1/clientes', $this->datos())->assertForbidden();
        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_el_administrador_puede_gestionar_clientes()
    {
        $this->actuarComoAdministrador();

        $this->postJson('/api/v1/clientes', $this->datos())->assertCreated();
    }
}
