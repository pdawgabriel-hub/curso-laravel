<?php

namespace Tests\Unit\Models;

use App\Models\LineaParteTrabajo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LineaParteTrabajoTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiene_los_atributos_asignables_esperados()
    {
        $this->assertEquals(['parte_trabajo_id', 'trabajador_id', 'horas', 'coste_hora'], (new LineaParteTrabajo())->getFillable());
    }

    public function test_usa_la_tabla_correcta()
    {
        $this->assertEquals('lineas_parte_trabajo', (new LineaParteTrabajo())->getTable());
    }

    public static function relaciones(): array
    {
        return [
            ['parte', BelongsTo::class],
            ['trabajador', BelongsTo::class],
        ];
    }

    #[DataProvider('relaciones')]
    public function test_define_la_relacion(string $metodo, string $tipo)
    {
        $this->assertTrue(method_exists(LineaParteTrabajo::class, $metodo), "Falta la relación {$metodo}()");
        $this->assertInstanceOf($tipo, (new LineaParteTrabajo())->{$metodo}());
    }

    public function test_se_puede_crear_con_su_factory()
    {
        $modelo = LineaParteTrabajo::factory()->create();

        $this->assertInstanceOf(LineaParteTrabajo::class, $modelo);
        $this->assertDatabaseHas('lineas_parte_trabajo', ['id' => $modelo->id]);
    }
}
