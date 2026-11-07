<?php

namespace Tests\Unit\Models;

use App\Models\Falta;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FaltaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiene_los_atributos_asignables_esperados()
    {
        $this->assertEquals(['trabajador_id', 'fecha_inicio', 'fecha_fin', 'tipo', 'motivo', 'user_id'], (new Falta())->getFillable());
    }

    public function test_usa_la_tabla_correcta()
    {
        $this->assertEquals('faltas', (new Falta())->getTable());
    }

    public static function relaciones(): array
    {
        return [
            ['trabajador', BelongsTo::class],
            ['usuario', BelongsTo::class],
        ];
    }

    #[DataProvider('relaciones')]
    public function test_define_la_relacion(string $metodo, string $tipo)
    {
        $this->assertTrue(method_exists(Falta::class, $metodo), "Falta la relación {$metodo}()");
        $this->assertInstanceOf($tipo, (new Falta())->{$metodo}());
    }

    public function test_se_puede_crear_con_su_factory()
    {
        $modelo = Falta::factory()->create();

        $this->assertInstanceOf(Falta::class, $modelo);
        $this->assertDatabaseHas('faltas', ['id' => $modelo->id]);
    }
}
