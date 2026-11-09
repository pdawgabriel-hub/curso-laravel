<?php

namespace Tests\Feature;

use App\Models\Cliente;
use Illuminate\Support\Facades\Route;

/** 8.1: en la rama react-admin, el servidor solo envía datos; las pantallas Blade de la aplicación desaparecen. */
class RA8_1_SinVistasTest extends FeatureTestCase
{
    public function test_ya_no_hay_pantallas_blade_de_la_aplicacion()
    {
        foreach (['clientes', 'trabajadores', 'proveedores', 'especialistas', 'presupuestos', 'obras', 'gastos', 'faltas'] as $modulo) {
            $this->assertFalse(Route::has("{$modulo}.index"), "Sigue existiendo la ruta web {$modulo}.index");
            $this->assertDirectoryDoesNotExist(resource_path("views/{$modulo}"), "Sigue existiendo resources/views/{$modulo}");
        }
        $this->assertFileDoesNotExist(resource_path('views/components/layout.blade.php'));
    }

    public function test_la_api_sigue_igual()
    {
        $this->actuarComo('jefe');
        Cliente::factory()->count(2)->create();

        $this->getJson('/api/v1/clientes')->assertOk()->assertJsonStructure(['data', 'links', 'meta'])->assertJsonPath('meta.total', 2);
        $this->assertTrue(Route::has('api.clientes.index'));
    }
}
