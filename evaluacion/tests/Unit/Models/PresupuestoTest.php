<?php

namespace Tests\Unit\Models;

use App\Models\Presupuesto;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PresupuestoTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiene_los_atributos_asignables_esperados()
    {
        $this->assertEquals(['fecha', 'cliente_id', 'obra_id', 'nombre_cliente', 'nif', 'direccion', 'telefono', 'iva', 'estado'], (new Presupuesto())->getFillable());
    }

    public function test_usa_la_tabla_correcta()
    {
        $this->assertEquals('presupuestos', (new Presupuesto())->getTable());
    }

    public static function relaciones(): array
    {
        return [
            ['cliente', BelongsTo::class],
            ['obra', BelongsTo::class],
            ['lineas', HasMany::class],
            ['proveedores', BelongsToMany::class],
            ['especialistas', BelongsToMany::class],
            ['observaciones', MorphMany::class],
            ['incidencias', MorphMany::class],
            ['archivos', MorphMany::class],
        ];
    }

    #[DataProvider('relaciones')]
    public function test_define_la_relacion(string $metodo, string $tipo)
    {
        $this->assertTrue(method_exists(Presupuesto::class, $metodo), "Falta la relación {$metodo}()");
        $this->assertInstanceOf($tipo, (new Presupuesto())->{$metodo}());
    }

    public function test_se_puede_crear_con_su_factory()
    {
        $modelo = Presupuesto::factory()->create();

        $this->assertInstanceOf(Presupuesto::class, $modelo);
        $this->assertDatabaseHas('presupuestos', ['id' => $modelo->id]);
    }
}
