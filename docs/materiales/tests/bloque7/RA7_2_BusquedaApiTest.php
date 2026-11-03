<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Presupuesto;
use Tests\Feature\FeatureTestCase;

class RA7_2_BusquedaApiTest extends FeatureTestCase
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

    public function test_el_listado_se_pagina_con_per_page()
    {
        Cliente::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/clientes?per_page=2');

        $this->assertCount(2, $response->json('data'));
        $this->assertSame(5, $response->json('meta.total'));
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
}
