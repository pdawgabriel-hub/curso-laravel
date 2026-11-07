<?php

namespace Tests\Unit\Models;

use App\Models\ParteEspecialista;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ParteEspecialistaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiene_los_atributos_asignables_esperados()
    {
        $this->assertEquals(['obra_id', 'especialista_id', 'fecha', 'descripcion', 'documento', 'importe', 'estado_pago', 'user_id'], (new ParteEspecialista())->getFillable());
    }

    public function test_usa_la_tabla_correcta()
    {
        $this->assertEquals('partes_especialista', (new ParteEspecialista())->getTable());
    }

    public static function relaciones(): array
    {
        return [
            ['obra', BelongsTo::class],
            ['usuario', BelongsTo::class],
            ['especialista', BelongsTo::class],
            ['archivos', MorphMany::class],
            ['incidencias', MorphMany::class],
        ];
    }

    #[DataProvider('relaciones')]
    public function test_define_la_relacion(string $metodo, string $tipo)
    {
        $this->assertTrue(method_exists(ParteEspecialista::class, $metodo), "Falta la relación {$metodo}()");
        $this->assertInstanceOf($tipo, (new ParteEspecialista())->{$metodo}());
    }

    public function test_se_puede_crear_con_su_factory()
    {
        $modelo = ParteEspecialista::factory()->create();

        $this->assertInstanceOf(ParteEspecialista::class, $modelo);
        $this->assertDatabaseHas('partes_especialista', ['id' => $modelo->id]);
    }
}
