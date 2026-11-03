<?php

namespace Tests\Unit\Models;

use App\Models\Trabajador;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TrabajadorTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiene_los_atributos_asignables_esperados()
    {
        $this->assertEquals(['tipo', 'nombre', 'apellido', 'telefono', 'coste_hora_estandar'], (new Trabajador())->getFillable());
    }

    public function test_usa_la_tabla_correcta()
    {
        $this->assertEquals('trabajadores', (new Trabajador())->getTable());
    }

    public static function relaciones(): array
    {
        return [
            ['lineasTrabajo', HasMany::class],
            ['faltas', HasMany::class],
            ['observaciones', MorphMany::class],
            ['archivos', MorphMany::class],
        ];
    }

    #[DataProvider('relaciones')]
    public function test_define_la_relacion(string $metodo, string $tipo)
    {
        $this->assertTrue(method_exists(Trabajador::class, $metodo), "Falta la relación {$metodo}()");
        $this->assertInstanceOf($tipo, (new Trabajador())->{$metodo}());
    }

    public function test_se_puede_crear_con_su_factory()
    {
        $modelo = Trabajador::factory()->create();

        $this->assertInstanceOf(Trabajador::class, $modelo);
        $this->assertDatabaseHas('trabajadores', ['id' => $modelo->id]);
    }
}
