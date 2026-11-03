<?php

namespace Tests\Unit\Models;

use App\Models\Archivo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ArchivoTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiene_los_atributos_asignables_esperados()
    {
        $this->assertEquals(['nombre_archivo', 'ruta', 'tipo'], (new Archivo())->getFillable());
    }

    public function test_usa_la_tabla_correcta()
    {
        $this->assertEquals('archivos', (new Archivo())->getTable());
    }

    public static function relaciones(): array
    {
        return [
            ['archivable', MorphTo::class],
        ];
    }

    #[DataProvider('relaciones')]
    public function test_define_la_relacion(string $metodo, string $tipo)
    {
        $this->assertTrue(method_exists(Archivo::class, $metodo), "Falta la relación {$metodo}()");
        $this->assertInstanceOf($tipo, (new Archivo())->{$metodo}());
    }
}
