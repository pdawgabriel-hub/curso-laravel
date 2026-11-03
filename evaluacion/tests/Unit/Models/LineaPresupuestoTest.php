<?php

namespace Tests\Unit\Models;

use App\Models\LineaPresupuesto;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LineaPresupuestoTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiene_los_atributos_asignables_esperados()
    {
        $this->assertEquals(['presupuesto_id', 'descripcion', 'uds', 'precio'], (new LineaPresupuesto())->getFillable());
    }

    public function test_usa_la_tabla_correcta()
    {
        $this->assertEquals('lineas_presupuesto', (new LineaPresupuesto())->getTable());
    }

    public static function relaciones(): array
    {
        return [
            ['presupuesto', BelongsTo::class],
        ];
    }

    #[DataProvider('relaciones')]
    public function test_define_la_relacion(string $metodo, string $tipo)
    {
        $this->assertTrue(method_exists(LineaPresupuesto::class, $metodo), "Falta la relación {$metodo}()");
        $this->assertInstanceOf($tipo, (new LineaPresupuesto())->{$metodo}());
    }

    public function test_se_puede_crear_con_su_factory()
    {
        $modelo = LineaPresupuesto::factory()->create();

        $this->assertInstanceOf(LineaPresupuesto::class, $modelo);
        $this->assertDatabaseHas('lineas_presupuesto', ['id' => $modelo->id]);
    }
}
