<?php

namespace Tests\Unit\Models;

use App\Models\ParteProveedor;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ParteProveedorTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiene_los_atributos_asignables_esperados()
    {
        $this->assertEquals(['obra_id', 'proveedor_id', 'fecha', 'descripcion', 'documento', 'importe', 'estado_pago', 'user_id'], (new ParteProveedor())->getFillable());
    }

    public function test_usa_la_tabla_correcta()
    {
        $this->assertEquals('partes_proveedor', (new ParteProveedor())->getTable());
    }

    public static function relaciones(): array
    {
        return [
            ['obra', BelongsTo::class],
            ['usuario', BelongsTo::class],
            ['proveedor', BelongsTo::class],
            ['archivos', MorphMany::class],
            ['incidencias', MorphMany::class],
        ];
    }

    #[DataProvider('relaciones')]
    public function test_define_la_relacion(string $metodo, string $tipo)
    {
        $this->assertTrue(method_exists(ParteProveedor::class, $metodo), "Falta la relación {$metodo}()");
        $this->assertInstanceOf($tipo, (new ParteProveedor())->{$metodo}());
    }

    public function test_se_puede_crear_con_su_factory()
    {
        $modelo = ParteProveedor::factory()->create();

        $this->assertInstanceOf(ParteProveedor::class, $modelo);
        $this->assertDatabaseHas('partes_proveedor', ['id' => $modelo->id]);
    }
}
