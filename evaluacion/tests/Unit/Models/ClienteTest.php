<?php

namespace Tests\Unit\Models;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiene_los_atributos_asignables_esperados()
    {
        $this->assertEquals(['nombre', 'apellidos', 'nif', 'direccion', 'cod_postal', 'telefono'], (new Cliente())->getFillable());
    }

    public function test_usa_la_tabla_correcta()
    {
        $this->assertEquals('clientes', (new Cliente())->getTable());
    }

    public static function relaciones(): array
    {
        return [
            ['presupuestos', HasMany::class],
            ['obras', HasMany::class],
            ['ingresos', HasMany::class],
            ['observaciones', MorphMany::class],
        ];
    }

    #[DataProvider('relaciones')]
    public function test_define_la_relacion(string $metodo, string $tipo)
    {
        $this->assertTrue(method_exists(Cliente::class, $metodo), "Falta la relación {$metodo}()");
        $this->assertInstanceOf($tipo, (new Cliente())->{$metodo}());
    }

    public function test_se_puede_crear_con_su_factory()
    {
        $modelo = Cliente::factory()->create();

        $this->assertInstanceOf(Cliente::class, $modelo);
        $this->assertDatabaseHas('clientes', ['id' => $modelo->id]);
    }
}
