<?php

namespace Tests\Feature;

use App\Http\Controllers\ClientesController;
use App\Http\Controllers\TrabajadoresController;
use App\Http\Requests\ClienteRequest;
use App\Http\Requests\TrabajadorRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class RA5_6_FormulariosTest extends TestCase
{
    use RefreshDatabase;

    private array $cliente = ['nombre' => 'Ana', 'apellidos' => 'Ruiz', 'direccion' => 'Calle Luna 3', 'telefono' => '600123123'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        // A partir de 5.8: repositorio en memoria, para no tocar storage/app/clientes.json
        if (interface_exists(\App\Repositorios\ClienteRepository::class)) {
            $this->app->instance(\App\Repositorios\ClienteRepository::class, new \App\Repositorios\ArrayClienteRepository());
        }
        // A partir de 5.7 estas rutas exigen sesión iniciada
        $this->actingAs(User::factory()->create());
    }

    public function test_el_formulario_de_alta_tiene_sus_campos()
    {
        $response = $this->get('/clientes/create')->assertOk();

        foreach (['_token', 'nombre', 'apellidos', 'nif', 'direccion', 'cod_postal', 'telefono'] as $campo) {
            $response->assertSee('name="' . $campo . '"', false);
        }
        $response->assertSee(route('clientes.store'), false);
    }

    public function test_el_formulario_de_edicion_viene_relleno_y_usa_put()
    {
        $this->get('/clientes/edit/1')
            ->assertOk()
            ->assertSee('value="Juan"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee(route('clientes.update', 1), false);
    }

    public function test_alta_con_errores_vuelve_con_los_mensajes()
    {
        $this->from('/clientes/create')
            ->post('/clientes/store', ['nombre' => 'Ana'])
            ->assertRedirect('/clientes/create')
            ->assertSessionHasErrors(['direccion', 'telefono'])
            ->assertSessionHasInput('nombre', 'Ana');
    }

    public function test_alta_correcta_redirige_al_listado_con_mensaje()
    {
        $this->post('/clientes/store', $this->cliente)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHas('mensaje');
    }

    public function test_modificacion_correcta_redirige_al_detalle()
    {
        $this->put('/clientes/update/1', $this->cliente)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('clientes.show', 1));
        $this->put('/clientes/update/1', [])->assertSessionHasErrors(['nombre', 'direccion', 'telefono']);
    }

    public function test_los_controladores_validan_con_form_requests()
    {
        $this->assertTrue(class_exists(ClienteRequest::class));
        $this->assertSame(ClienteRequest::class, (new ReflectionMethod(ClientesController::class, 'store'))->getParameters()[0]->getType()->getName());
        $this->assertSame(TrabajadorRequest::class, (new ReflectionMethod(TrabajadoresController::class, 'store'))->getParameters()[0]->getType()->getName());
    }

    public function test_el_coste_hora_del_trabajador_no_puede_ser_negativo()
    {
        $this->post('/trabajadores/store', ['nombre' => 'Eva', 'apellido' => 'Sanz', 'coste_hora_estandar' => -3])
            ->assertSessionHasErrors('coste_hora_estandar');
        $this->post('/trabajadores/store', ['nombre' => 'Eva', 'apellido' => 'Sanz', 'coste_hora_estandar' => 22])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('trabajadores.index'));
    }
}
