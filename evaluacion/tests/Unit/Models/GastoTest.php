<?php

namespace Tests\Unit\Models;

use App\Models\Gasto;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GastoTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiene_los_atributos_asignables_esperados()
    {
        $this->assertEquals(['obra_id', 'presupuesto_id', 'direccion_obra', 'inicio_obra', 'fin_obra', 'solicitud_obra', 'mandante', 'gestion_licencia', 'pago_icio'], (new Gasto())->getFillable());
    }

    public function test_usa_la_tabla_correcta()
    {
        $this->assertEquals('gastos', (new Gasto())->getTable());
    }

    public static function relaciones(): array
    {
        return [
            ['obra', BelongsTo::class],
            ['presupuesto', BelongsTo::class],
            ['archivos', MorphMany::class],
        ];
    }

    #[DataProvider('relaciones')]
    public function test_define_la_relacion(string $metodo, string $tipo)
    {
        $this->assertTrue(method_exists(Gasto::class, $metodo), "Falta la relación {$metodo}()");
        $this->assertInstanceOf($tipo, (new Gasto())->{$metodo}());
    }
}
