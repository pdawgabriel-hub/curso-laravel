<?php

namespace Tests\Unit\Models;

use App\Models\Incidencia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IncidenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiene_los_atributos_asignables_esperados()
    {
        $this->assertEquals(['texto', 'user_id'], (new Incidencia())->getFillable());
    }

    public function test_usa_la_tabla_correcta()
    {
        $this->assertEquals('incidencias', (new Incidencia())->getTable());
    }

    public static function relaciones(): array
    {
        return [
            ['incidentable', MorphTo::class],
            ['usuario', BelongsTo::class],
        ];
    }

    #[DataProvider('relaciones')]
    public function test_define_la_relacion(string $metodo, string $tipo)
    {
        $this->assertTrue(method_exists(Incidencia::class, $metodo), "Falta la relación {$metodo}()");
        $this->assertInstanceOf($tipo, (new Incidencia())->{$metodo}());
    }
}
