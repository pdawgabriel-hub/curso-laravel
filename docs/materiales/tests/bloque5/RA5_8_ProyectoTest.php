<?php

namespace Tests\Feature;

use App\Http\Controllers\ClientesController;
use App\Http\Controllers\ObrasController;
use App\Repositories\ArrayClienteRepository;
use App\Repositories\ArrayObraRepository;
use App\Repositories\ClienteRepository;
use App\Repositories\ObraRepository;
use App\Support\EstadoPago;
use ReflectionClass;
use Tests\TestCase;

class RA5_8_ProyectoTest extends TestCase
{
    public function test_los_repositorios_son_interfaces_con_implementacion_en_arrays()
    {
        $this->assertTrue(interface_exists(ClienteRepository::class));
        $this->assertTrue(interface_exists(ObraRepository::class));
        $this->assertInstanceOf(ArrayClienteRepository::class, app(ClienteRepository::class));
        $this->assertInstanceOf(ArrayObraRepository::class, app(ObraRepository::class));
    }

    public function test_el_contenedor_los_registra_como_singleton()
    {
        $this->assertSame(app(ClienteRepository::class), app(ClienteRepository::class));
        $this->assertSame(app(ObraRepository::class), app(ObraRepository::class));
    }

    public function test_el_repositorio_de_clientes()
    {
        $repo = app(ClienteRepository::class);

        $this->assertCount(10, $repo->all());
        $this->assertSame('Juan', $repo->find(1)['nombre']);
        $this->assertNull($repo->find(999));
    }

    public function test_el_repositorio_de_obras_filtra_por_cliente()
    {
        $repo = app(ObraRepository::class);

        $this->assertCount(10, $repo->all());
        $this->assertSame([1], array_keys($repo->deCliente(1)));
    }

    public function test_los_controladores_reciben_el_repositorio_por_inyeccion()
    {
        $tipos = fn (string $clase) => array_map(
            fn ($p) => $p->getType()?->getName(),
            (new ReflectionClass($clase))->getConstructor()?->getParameters() ?? []
        );

        $this->assertContains(ClienteRepository::class, $tipos(ClientesController::class));
        $this->assertContains(ObraRepository::class, $tipos(ObrasController::class));
    }

    public function test_estado_de_pago_segun_lo_cobrado()
    {
        $this->assertSame('pendiente', EstadoPago::calcular(1000, 0));
        $this->assertSame('parcial', EstadoPago::calcular(1000, 400));
        $this->assertSame('pagado', EstadoPago::calcular(1000, 1000));
        $this->assertSame('pagado', EstadoPago::calcular(1000, 1200));
        $this->assertSame('pagado', EstadoPago::calcular(0, 50));
        $this->assertSame('pendiente', EstadoPago::calcular(0, 0));
    }
}
