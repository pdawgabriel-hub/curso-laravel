<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Obra;

/** 8.3: el middleware ReactAdminResponse adapta la API al dataProvider json-server, solo cuando lo pide React-Admin. */
class RA8_3_MiddlewareTest extends FeatureTestCase
{
    private array $ra = ['X-Cliente' => 'react-admin'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('jefe');
    }

    public function test_sin_la_cabecera_la_api_no_cambia()
    {
        Cliente::factory()->count(3)->create();

        $r = $this->getJson('/api/v1/clientes')->assertOk()->assertJsonStructure(['data', 'links', 'meta']);
        $r->assertHeaderMissing('X-Total-Count');
    }

    public function test_un_listado_es_un_array_con_el_total_en_la_cabecera()
    {
        Cliente::factory()->count(3)->create();

        $r = $this->getJson('/api/v1/clientes', $this->ra)->assertOk()->assertHeader('X-Total-Count', '3');

        $this->assertTrue(array_is_list($r->json()), 'La respuesta debe ser directamente el array de registros');
        $this->assertCount(3, $r->json());
        $this->assertArrayHasKey('codigo', $r->json('0'));
    }

    public function test_traduce_start_end_sort_order_y_q()
    {
        foreach (['Beatriz', 'Andrés', 'Carmen', 'Diego', 'Elena'] as $nombre) {
            Cliente::factory()->create(['nombre' => $nombre]);
        }

        $pagina = $this->getJson('/api/v1/clientes?_start=2&_end=4&_sort=nombre&_order=ASC', $this->ra);
        $this->assertSame(['Carmen', 'Diego'], array_column($pagina->json(), 'nombre'));
        $pagina->assertHeader('X-Total-Count', '5');

        $this->assertSame('Elena', $this->getJson('/api/v1/clientes?_start=0&_end=1&_sort=nombre&_order=DESC', $this->ra)->json('0.nombre'));
        $this->assertSame(['Diego'], array_column($this->getJson('/api/v1/clientes?q=Dieg', $this->ra)->json(), 'nombre'));
    }

    public function test_un_registro_sale_sin_envolver()
    {
        $c = Cliente::factory()->create();

        $this->getJson("/api/v1/clientes/{$c->id}", $this->ra)->assertOk()->assertJsonPath('id', $c->id)->assertJsonPath('codigo', $c->codigo);
        $this->postJson('/api/v1/clientes', ['nombre' => 'Ana', 'direccion' => 'C/ Sol', 'telefono' => '6'], $this->ra)->assertCreated()->assertJsonPath('codigo', 'CLI0002');
    }

    public function test_los_recursos_anidados_tambien()
    {
        $obra = Obra::factory()->create();
        $this->postJson("/api/v1/obras/{$obra->id}/ingresos", ['importe' => 100], $this->ra)->assertCreated()->assertJsonPath('obra_id', $obra->id);

        $this->getJson("/api/v1/obras/{$obra->id}/ingresos?_start=0&_end=10", $this->ra)->assertOk()->assertHeader('X-Total-Count', '1');
    }

    public function test_los_errores_de_validacion_llevan_un_mensaje_por_campo()
    {
        $r = $this->postJson('/api/v1/clientes', [], $this->ra)->assertUnprocessable();

        $this->assertIsString($r->json('errors.nombre'), 'React-Admin espera un texto por campo, no un array');
        $this->assertIsString($r->json('message'));
    }

    public function test_esta_registrado_en_el_grupo_api()
    {
        $this->assertContains(\App\Http\Middleware\ReactAdminResponse::class, app(\Illuminate\Foundation\Http\Kernel::class)->getMiddlewareGroups()['api'] ?? []);
    }
}
