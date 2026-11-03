<?php

namespace Tests\Feature\Api;

use App\Models\Obra;
use App\Models\ParteProveedor;
use App\Models\Proveedor;
use Tests\Feature\FeatureTestCase;

class ProveedorApiTest extends FeatureTestCase
{
    private const CAMPOS = ['id', 'codigo', 'nombre', 'direccion', 'telefono', 'correo', 'tipo', 'referencia', 'documento', 'num_documento', 'base', 'iva', 'total', 'created_at', 'updated_at'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('administrativa');
    }

    public function test_puede_listar_proveedores()
    {
        Proveedor::factory()->count(2)->create();

        $this->assertListado($this->getJson('/api/v1/proveedores'), self::CAMPOS, 2);
    }

    public function test_crea_un_proveedor_y_calcula_el_total_con_iva()
    {
        $response = $this->postJson('/api/v1/proveedores', ['nombre' => 'Materiales Sur', 'base' => 100, 'iva' => 21]);

        $response->assertCreated()->assertJsonPath('data.codigo', 'PROV0001');
        $this->assertImporte(121, $response->json('data.total'));
    }

    public function test_valida_base_iva_y_correo()
    {
        $this->postJson('/api/v1/proveedores', ['nombre' => 'X', 'base' => -1, 'iva' => 150, 'correo' => 'no-es-correo'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['base', 'iva', 'correo']);
        $this->postJson('/api/v1/proveedores', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre', 'base', 'iva']);
    }

    public function test_puede_actualizar_y_borrar_un_proveedor()
    {
        $p = Proveedor::factory()->create();

        $this->putJson("/api/v1/proveedores/{$p->id}", ['nombre' => 'Nuevo', 'base' => 200, 'iva' => 10])
            ->assertOk()->assertJsonPath('data.nombre', 'Nuevo');
        $this->deleteJson("/api/v1/proveedores/{$p->id}")->assertNoContent();
        $this->assertDatabaseMissing('proveedores', ['id' => $p->id]);
    }

    public function test_no_se_borra_un_proveedor_con_partes()
    {
        $parte = ParteProveedor::factory()->create();

        $this->deleteJson("/api/v1/proveedores/{$parte->proveedor_id}")->assertStatus(409);
    }

    public function test_obras_en_las_que_ha_participado()
    {
        $p = Proveedor::factory()->create();
        $obra = Obra::factory()->create();
        ParteProveedor::factory()->count(2)->create(['obra_id' => $obra->id, 'proveedor_id' => $p->id]);
        Obra::factory()->create();

        $response = $this->getJson("/api/v1/proveedores/{$p->id}/obras");

        $this->assertSame([$obra->id], array_column($response->json('data'), 'id'));
    }
}
