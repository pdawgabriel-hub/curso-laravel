<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RA5_3_RutasTest extends TestCase
{
    public function test_pantalla_principal()
    {
        $this->get('/')->assertOk()->assertSee('Pantalla principal');
    }

    public function test_rutas_de_clientes()
    {
        $this->get('/clientes')->assertOk()->assertSee('Listado de clientes');
        $this->get('/clientes/show/7')->assertOk()->assertSee('Detalle del cliente 7');
        $this->get('/clientes/create')->assertOk()->assertSee('Nuevo cliente');
        $this->get('/clientes/edit/7')->assertOk()->assertSee('Modificar cliente 7');
    }

    public function test_rutas_de_obras()
    {
        $this->get('/obras')->assertOk()->assertSee('Listado de obras');
        $this->get('/obras/show/3')->assertOk()->assertSee('Detalle de la obra 3');
        $this->get('/obras/create')->assertOk()->assertSee('Nueva obra');
        $this->get('/obras/edit/3')->assertOk()->assertSee('Modificar obra 3');
    }

    public function test_los_identificadores_deben_ser_numericos()
    {
        $this->get('/clientes/show/abc')->assertNotFound();
        $this->get('/obras/edit/-1')->assertNotFound();
    }

    public function test_las_rutas_tienen_nombre()
    {
        foreach (['clientes', 'obras'] as $grupo) {
            foreach (['index', 'show', 'create', 'edit'] as $accion) {
                $this->assertTrue(Route::has("$grupo.$accion"), "Falta la ruta con nombre $grupo.$accion");
            }
        }
        $this->assertSame(url('/clientes/show/5'), route('clientes.show', 5));
    }

    public function test_datos_de_la_empresa_en_json()
    {
        $this->assertTrue(Route::has('empresa'));
        $this->getJson('/empresa')->assertOk()->assertExactJson(config('gestionobras.empresa'));
    }
}
