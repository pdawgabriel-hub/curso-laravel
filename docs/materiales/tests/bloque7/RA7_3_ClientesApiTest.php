<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Presupuesto;
use Tests\Feature\FeatureTestCase;

class RA7_3_ClientesApiTest extends FeatureTestCase
{
    private const CAMPOS = ['id', 'codigo', 'nombre', 'apellidos', 'nombre_completo', 'nif', 'direccion', 'cod_postal', 'telefono', 'created_at', 'updated_at'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('jefe');
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

    public function test_puede_crear_un_cliente()
    {
        $response = $this->postJson('/api/v1/clientes', $this->datos());

        $response->assertCreated()
            ->assertJsonStructure(['data' => self::CAMPOS])
            ->assertJsonPath('data.codigo', 'CLI0001')
            ->assertJsonPath('data.nombre_completo', 'Juan Pérez López');
        $this->assertDatabaseHas('clientes', ['nif' => '12345678Z', 'codigo' => 'CLI0001']);
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
}
