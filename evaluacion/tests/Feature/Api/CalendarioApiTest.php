<?php

namespace Tests\Feature\Api;

use App\Models\Ingreso;
use App\Models\ParteEspecialista;
use App\Models\ParteProveedor;
use App\Models\ParteTrabajo;
use App\Models\Presupuesto;
use Tests\Feature\FeatureTestCase;

class CalendarioApiTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('administrativa');
    }

    public function test_unifica_los_eventos_de_cinco_origenes()
    {
        $p = Presupuesto::factory()->create(['fecha' => '2026-05-04']);
        Ingreso::factory()->create(['fecha' => '2026-05-02']);
        ParteTrabajo::factory()->create(['fecha' => '2026-05-03']);
        ParteProveedor::factory()->create(['fecha' => '2026-05-05']);
        ParteEspecialista::factory()->create(['fecha' => '2026-05-01']);
        Ingreso::factory()->create(['fecha' => '2026-07-01']);

        $response = $this->getJson('/api/v1/calendario?desde=2026-05-01&hasta=2026-05-31');

        $response->assertOk()->assertJsonStructure(['data' => ['*' => ['tipo', 'fecha', 'titulo', 'modelo', 'id']]]);
        $this->assertSame(
            ['parte_especialista', 'ingreso', 'parte_trabajo', 'presupuesto', 'parte_proveedor'],
            array_column($response->json('data'), 'tipo')
        );
        $this->assertSame(['modelo' => 'presupuestos', 'id' => $p->id], array_intersect_key($response->json('data.3'), ['modelo' => 1, 'id' => 1]));
        $this->assertSame('2026-05-04', $response->json('data.3.fecha'));
    }

    public function test_requiere_un_rango_valido()
    {
        $this->getJson('/api/v1/calendario')->assertUnprocessable()->assertJsonValidationErrors(['desde', 'hasta']);
        $this->getJson('/api/v1/calendario?desde=2026-05-31&hasta=2026-05-01')->assertUnprocessable()->assertJsonValidationErrors('hasta');
    }
}
