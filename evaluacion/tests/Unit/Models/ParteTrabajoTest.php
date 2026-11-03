<?php

namespace Tests\Unit\Models;

use App\Models\ParteTrabajo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ParteTrabajoTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiene_los_atributos_asignables_esperados()
    {
        $this->assertEquals(['obra_id', 'fecha', 'descripcion'], (new ParteTrabajo())->getFillable());
    }

    public function test_usa_la_tabla_correcta()
    {
        $this->assertEquals('partes_trabajo', (new ParteTrabajo())->getTable());
    }

    public static function relaciones(): array
    {
        return [
            ['obra', BelongsTo::class],
            ['lineas', HasMany::class],
            ['incidencias', MorphMany::class],
        ];
    }

    #[DataProvider('relaciones')]
    public function test_define_la_relacion(string $metodo, string $tipo)
    {
        $this->assertTrue(method_exists(ParteTrabajo::class, $metodo), "Falta la relación {$metodo}()");
        $this->assertInstanceOf($tipo, (new ParteTrabajo())->{$metodo}());
    }

    public function test_se_puede_crear_con_su_factory()
    {
        $modelo = ParteTrabajo::factory()->create();

        $this->assertInstanceOf(ParteTrabajo::class, $modelo);
        $this->assertDatabaseHas('partes_trabajo', ['id' => $modelo->id]);
    }
}
