<?php

namespace Tests\Unit\Models;

use App\Models\Ingreso;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IngresoTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiene_los_atributos_asignables_esperados()
    {
        $this->assertEquals(['obra_id', 'cliente_id', 'nombre_obra', 'tipo', 'fecha', 'documento', 'num_documento', 'importe'], (new Ingreso())->getFillable());
    }

    public function test_usa_la_tabla_correcta()
    {
        $this->assertEquals('ingresos', (new Ingreso())->getTable());
    }

    public static function relaciones(): array
    {
        return [
            ['obra', BelongsTo::class],
            ['cliente', BelongsTo::class],
            ['observaciones', MorphMany::class],
            ['archivos', MorphMany::class],
        ];
    }

    #[DataProvider('relaciones')]
    public function test_define_la_relacion(string $metodo, string $tipo)
    {
        $this->assertTrue(method_exists(Ingreso::class, $metodo), "Falta la relación {$metodo}()");
        $this->assertInstanceOf($tipo, (new Ingreso())->{$metodo}());
    }

    public function test_se_puede_crear_con_su_factory()
    {
        $modelo = Ingreso::factory()->create();

        $this->assertInstanceOf(Ingreso::class, $modelo);
        $this->assertDatabaseHas('ingresos', ['id' => $modelo->id]);
    }
}
