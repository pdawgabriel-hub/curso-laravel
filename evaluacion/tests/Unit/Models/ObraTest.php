<?php

namespace Tests\Unit\Models;

use App\Models\Obra;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ObraTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiene_los_atributos_asignables_esperados()
    {
        $this->assertEquals(['descripcion', 'cliente_id'], (new Obra())->getFillable());
    }

    public function test_usa_la_tabla_correcta()
    {
        $this->assertEquals('obras', (new Obra())->getTable());
    }

    public static function relaciones(): array
    {
        return [
            ['cliente', BelongsTo::class],
            ['gasto', HasOne::class],
            ['presupuestos', HasMany::class],
            ['ingresos', HasMany::class],
            ['partesTrabajo', HasMany::class],
            ['partesProveedor', HasMany::class],
            ['partesEspecialista', HasMany::class],
            ['observaciones', MorphMany::class],
            ['incidencias', MorphMany::class],
            ['archivos', MorphMany::class],
        ];
    }

    #[DataProvider('relaciones')]
    public function test_define_la_relacion(string $metodo, string $tipo)
    {
        $this->assertTrue(method_exists(Obra::class, $metodo), "Falta la relación {$metodo}()");
        $this->assertInstanceOf($tipo, (new Obra())->{$metodo}());
    }

    public function test_se_puede_crear_con_su_factory()
    {
        $modelo = Obra::factory()->create();

        $this->assertInstanceOf(Obra::class, $modelo);
        $this->assertDatabaseHas('obras', ['id' => $modelo->id]);
    }
}
