<?php

namespace Tests\Unit\Models;

use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProveedorTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiene_los_atributos_asignables_esperados()
    {
        $this->assertEquals(['nombre', 'direccion', 'telefono', 'correo', 'tipo', 'referencia', 'documento', 'num_documento'], (new Proveedor())->getFillable());
    }

    public function test_usa_la_tabla_correcta()
    {
        $this->assertEquals('proveedores', (new Proveedor())->getTable());
    }

    public static function relaciones(): array
    {
        return [
            ['partes', HasMany::class],
            ['presupuestos', BelongsToMany::class],
            ['observaciones', MorphMany::class],
            ['archivos', MorphMany::class],
        ];
    }

    #[DataProvider('relaciones')]
    public function test_define_la_relacion(string $metodo, string $tipo)
    {
        $this->assertTrue(method_exists(Proveedor::class, $metodo), "Falta la relación {$metodo}()");
        $this->assertInstanceOf($tipo, (new Proveedor())->{$metodo}());
    }

    public function test_se_puede_crear_con_su_factory()
    {
        $modelo = Proveedor::factory()->create();

        $this->assertInstanceOf(Proveedor::class, $modelo);
        $this->assertDatabaseHas('proveedores', ['id' => $modelo->id]);
    }
}
