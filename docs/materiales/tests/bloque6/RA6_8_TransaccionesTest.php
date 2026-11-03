<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Gasto;
use App\Models\Ingreso;
use App\Models\Obra;
use App\Models\ParteTrabajo;
use App\Models\Presupuesto;
use App\Models\Secuencia;
use App\Services\ObraService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class RA6_8_TransaccionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_secuencias_son_correlativas_por_prefijo()
    {
        $this->assertSame('OBR0001', Secuencia::siguiente('OBR'));
        $this->assertSame('OBR0002', Secuencia::siguiente('OBR'));
        $this->assertSame('CLI0001', Secuencia::siguiente('CLI'));
        $this->assertSame('OBR0003', Secuencia::siguiente('OBR'));
    }

    public function test_crear_una_obra_es_atomico()
    {
        // Si falla la ficha de Gastos, tampoco debe quedar la obra
        Gasto::creating(fn () => throw new RuntimeException('Fallo simulado'));

        try {
            app(ObraService::class)->crear(['descripcion' => 'Cocina']);
            $this->fail('Se esperaba una excepción');
        } catch (RuntimeException) {
        }

        $this->assertDatabaseCount('obras', 0);
        $this->assertDatabaseCount('gastos', 0);
    }

    public function test_borrar_una_obra_con_ingresos_lanza_regla_de_negocio()
    {
        $obra = Obra::factory()->create();
        ParteTrabajo::factory()->create(['obra_id' => $obra->id]);
        Ingreso::factory()->create(['obra_id' => $obra->id]);

        $this->expectException(ReglaNegocioException::class);
        try {
            app(ObraService::class)->borrar($obra);
        } finally {
            $this->assertDatabaseHas('obras', ['id' => $obra->id]);
            $this->assertDatabaseCount('partes_trabajo', 1);
        }
    }

    public function test_borrar_una_obra_sin_ingresos()
    {
        $obra = Obra::factory()->create();
        $p = Presupuesto::factory()->create(['cliente_id' => $obra->cliente_id, 'obra_id' => $obra->id]);
        ParteTrabajo::factory()->create(['obra_id' => $obra->id]);

        app(ObraService::class)->borrar($obra);

        $this->assertDatabaseMissing('obras', ['id' => $obra->id]);
        $this->assertDatabaseCount('gastos', 0);
        $this->assertDatabaseCount('partes_trabajo', 0);
        $this->assertDatabaseHas('presupuestos', ['id' => $p->id, 'obra_id' => null]);
    }

    public function test_la_regla_de_negocio_se_traduce_en_409_en_la_api()
    {
        $this->assertSame(409, (new ReglaNegocioException('x'))->render(request()->create('/api/x', 'DELETE', server: ['HTTP_ACCEPT' => 'application/json']))->getStatusCode());
    }
}
