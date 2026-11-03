<?php

namespace Tests\Feature;

use App\Http\Controllers\ClientesController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ObrasController;
use App\Http\Controllers\TrabajadoresController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RA5_5_ControladoresTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_cada_ruta_apunta_a_su_controlador()
    {
        $this->assertSame(HomeController::class, Route::getRoutes()->match(request()->create('/'))->getActionName());
        foreach (['clientes' => ClientesController::class, 'obras' => ObrasController::class, 'trabajadores' => TrabajadoresController::class] as $grupo => $controlador) {
            foreach (['index', 'show', 'create', 'edit'] as $accion) {
                $this->assertSame("$controlador@$accion", Route::getRoutes()->getByName("$grupo.$accion")?->getActionName(), "$grupo.$accion");
            }
        }
    }

    public function test_las_rutas_de_cada_grupo_estan_en_su_fichero()
    {
        $web = file_get_contents(base_path('routes/web.php'));
        foreach (['clientes', 'obras', 'trabajadores'] as $grupo) {
            $fichero = base_path("routes/$grupo.php");
            $this->assertFileExists($fichero);
            $this->assertStringContainsString("'$grupo.'", file_get_contents($fichero), "routes/$grupo.php debe definir el grupo $grupo.");
            $this->assertStringContainsString("/$grupo.php", $web, "routes/web.php debe incluir routes/$grupo.php");
        }
    }

    public function test_la_portada_muestra_la_empresa()
    {
        $this->get('/')->assertOk()->assertViewIs('home')->assertSee(config('gestionobras.empresa.nombre'));
    }

    public function test_listado_completo_de_clientes()
    {
        $response = $this->get('/clientes')->assertOk();

        $this->assertCount(10, $response->viewData('clientes'));
        $response->assertSee('Juan')->assertSee('Pérez López')->assertSee('Elena');
    }

    public function test_detalle_y_edicion_de_cliente()
    {
        $this->get('/clientes/show/2')->assertOk()->assertSee('García Ruiz')->assertSee('23456789D');
        // A partir de 5.7 la edición exige sesión iniciada
        $this->actingAs(User::factory()->create())->get('/clientes/edit/2')->assertOk()->assertSee('María');
    }

    public function test_cliente_inexistente_devuelve_404()
    {
        $this->get('/clientes/show/99')->assertNotFound();
        $this->actingAs(User::factory()->create())->get('/clientes/edit/99')->assertNotFound();
    }

    public function test_detalle_de_obra_con_su_cliente_y_estado_de_pago()
    {
        $this->get('/obras/show/1')->assertOk()->assertSee('Reforma integral de cocina')->assertSee('Juan Pérez López')->assertSee('Pagado');
        $this->get('/obras/show/2')->assertOk()->assertSee('Parcial');
        $this->get('/obras/show/3')->assertOk()->assertSee('Pendiente');
        $this->get('/obras/show/99')->assertNotFound();
    }

    public function test_listado_de_obras_y_de_trabajadores()
    {
        $this->assertCount(10, $this->get('/obras')->assertOk()->viewData('obras'));
        $this->get('/trabajadores')->assertOk()->assertSee('Sánchez')->assertSee('Campos');
        $this->get('/trabajadores/show/1')->assertOk()->assertSee('Encargado')->assertSee('28');
        $this->get('/trabajadores/show/99')->assertNotFound();
    }
}
