<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RA5_7_AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        // A partir de 5.8: repositorio en memoria, para no tocar storage/app/clientes.json
        if (interface_exists(\App\Repositorios\ClienteRepository::class)) {
            $this->app->instance(\App\Repositorios\ClienteRepository::class, new \App\Repositorios\ArrayClienteRepository());
        }
    }

    public function test_los_listados_y_detalles_son_publicos()
    {
        foreach (['/clientes', '/clientes/show/1', '/obras', '/obras/show/1', '/trabajadores'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_crear_y_modificar_exige_iniciar_sesion()
    {
        foreach (['clientes', 'obras', 'trabajadores'] as $grupo) {
            $this->get("/$grupo/create")->assertRedirect('/login');
            $this->get("/$grupo/edit/1")->assertRedirect('/login');
            $this->post("/$grupo/store", [])->assertRedirect('/login');
            $this->put("/$grupo/update/1", [])->assertRedirect('/login');
        }
    }

    public function test_con_sesion_iniciada_se_accede_a_los_formularios()
    {
        $this->actingAs(User::factory()->create());

        foreach (['clientes', 'obras', 'trabajadores'] as $grupo) {
            $this->get("/$grupo/create")->assertOk();
            $this->get("/$grupo/edit/1")->assertOk();
        }
    }

    public function test_el_layout_muestra_iniciar_sesion_o_el_usuario()
    {
        $this->get('/clientes')->assertSee(route('login'), false);

        $this->actingAs(User::factory()->create(['name' => 'Carla Usuaria']));
        $this->get('/clientes')->assertSee('Carla Usuaria')->assertSee(route('logout'), false);
    }
}
