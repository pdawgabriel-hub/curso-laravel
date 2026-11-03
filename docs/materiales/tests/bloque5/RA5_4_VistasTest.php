<?php

namespace Tests\Feature;

use Tests\TestCase;

class RA5_4_VistasTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_existe_el_componente_de_layout()
    {
        $this->assertFileExists(resource_path('views/components/layout.blade.php'));
    }

    public function test_listado_de_clientes_usa_su_vista_y_el_layout()
    {
        $this->get('/clientes')
            ->assertOk()
            ->assertViewIs('clientes.index')
            ->assertViewHas('clientes')
            ->assertSee(config('gestionobras.empresa.nombre'))
            ->assertSee(route('clientes.index'), false);
    }

    public function test_detalle_creacion_y_edicion_de_cliente()
    {
        $this->get('/clientes/show/1')->assertOk()->assertViewIs('clientes.show')->assertViewHas('cliente')->assertViewHas('id', 1);
        $this->get('/clientes/create')->assertOk()->assertViewIs('clientes.create');
        $this->get('/clientes/edit/1')->assertOk()->assertViewIs('clientes.edit')->assertViewHas('cliente');
    }

    public function test_vistas_de_obras()
    {
        $this->get('/obras')->assertOk()->assertViewIs('obras.index')->assertViewHas('obras');
        $this->get('/obras/show/1')->assertOk()->assertViewIs('obras.show')->assertViewHas('obra');
        $this->get('/obras/create')->assertOk()->assertViewIs('obras.create');
        $this->get('/obras/edit/1')->assertOk()->assertViewIs('obras.edit')->assertViewHas('obra');
    }

    public function test_el_menu_enlaza_obras()
    {
        $this->get('/clientes')->assertSee(route('obras.index'), false);
    }
}
