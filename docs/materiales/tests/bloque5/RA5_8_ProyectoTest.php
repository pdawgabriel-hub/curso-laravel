<?php

namespace Tests\Feature;

use App\Dominio\Cliente;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\ObrasController;
use App\Models\User;
use App\Repositorios\ArrayClienteRepository;
use App\Repositorios\ClienteRepository;
use App\Repositorios\JsonClienteRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class RA5_8_ProyectoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** Sustituye el repositorio de la aplicación por uno en memoria: el test no toca clientes.json. */
    private function repositorioEnMemoria(): ArrayClienteRepository
    {
        $repositorio = new ArrayClienteRepository();
        $this->app->instance(ClienteRepository::class, $repositorio);

        return $repositorio;
    }

    private function ficheroTemporal(): string
    {
        $fichero = tempnam(sys_get_temp_dir(), 'clientes');
        unlink($fichero);
        $this->beforeApplicationDestroyed(fn () => @unlink($fichero));

        return $fichero;
    }

    public function test_clases_del_dominio_y_del_repositorio()
    {
        $this->assertTrue(interface_exists(ClienteRepository::class), 'Falta la interfaz app/Repositorios/ClienteRepository.php');
        $this->assertContains(ClienteRepository::class, class_implements(ArrayClienteRepository::class));
        $this->assertContains(ClienteRepository::class, class_implements(JsonClienteRepository::class));

        $cliente = new Cliente(1, 'Ana', 'Ruiz Gil', null, 'Calle Sol 1', null, '600000000');
        $this->assertSame('Ana Ruiz Gil', $cliente->nombreCompleto());
        $this->assertSame('Hostelería Sol', (new Cliente(2, 'Hostelería Sol', null, null, 'C', null, '6'))->nombreCompleto());
    }

    public function test_el_contenedor_entrega_siempre_el_mismo_repositorio_json()
    {
        $repositorio = $this->app->make(ClienteRepository::class);

        $this->assertInstanceOf(JsonClienteRepository::class, $repositorio);
        $this->assertSame($repositorio, $this->app->make(ClienteRepository::class), 'Regístralo con singleton()');
    }

    public function test_el_repositorio_en_array()
    {
        $repositorio = new ArrayClienteRepository();

        $this->assertCount(10, $repositorio->todos());
        $this->assertInstanceOf(Cliente::class, $repositorio->todos()[0]);
        $this->assertSame('Juan', $repositorio->porId(1)->nombre);
        $this->assertSame('28013', $repositorio->porId(1)->codPostal);
        $this->assertNull($repositorio->porId(999));

        $nuevo = $repositorio->guardar(new Cliente(null, 'Ana', null, null, 'Calle Sol 1', null, '600000000'));
        $this->assertSame(11, $nuevo->id);
        $this->assertSame('Ana', $repositorio->porId(11)->nombre);

        $repositorio->guardar(new Cliente(11, 'Ana María', null, null, 'Calle Sol 1', null, '600000000'));
        $this->assertSame('Ana María', $repositorio->porId(11)->nombre);
        $this->assertCount(11, $repositorio->todos());
    }

    public function test_el_repositorio_json_recuerda_entre_peticiones()
    {
        $fichero = $this->ficheroTemporal();
        $primero = new JsonClienteRepository($fichero, new ArrayClienteRepository());

        $this->assertCount(10, $primero->todos(), 'Sin fichero, parte de los datos de ejemplo');
        $this->assertFileDoesNotExist($fichero);

        $nuevo = $primero->guardar(new Cliente(null, 'Ana', 'Ruiz', null, 'Calle Sol 1', null, '600000000', 'Carla'));
        $this->assertSame(11, $nuevo->id);
        $this->assertFileExists($fichero);

        // Otra petición: un objeto nuevo sobre el mismo fichero
        $segundo = new JsonClienteRepository($fichero, new ArrayClienteRepository());
        $this->assertCount(11, $segundo->todos());
        $recuperado = $segundo->porId(11);
        $this->assertInstanceOf(Cliente::class, $recuperado);
        $this->assertSame('Ruiz', $recuperado->apellidos);
        $this->assertSame('Carla', $recuperado->creadoPor);
        $this->assertSame('Juan', $segundo->porId(1)->nombre);
        $this->assertNull($segundo->porId(999));
    }

    public function test_los_controladores_reciben_el_repositorio_por_inyeccion()
    {
        $tipos = fn (string $clase) => array_map(
            fn ($p) => $p->getType()?->getName(),
            (new ReflectionClass($clase))->getConstructor()?->getParameters() ?? []
        );

        $this->assertContains(ClienteRepository::class, $tipos(ClientesController::class));
        $this->assertContains(ClienteRepository::class, $tipos(ObrasController::class));
        $this->assertFalse(property_exists(ClientesController::class, 'arrayClientes'), 'El array ya no vive en el controlador');
    }

    public function test_listado_y_detalle_con_objetos_del_dominio()
    {
        $this->repositorioEnMemoria();

        $response = $this->get('/clientes')->assertOk()->assertSee('Juan Pérez López')->assertSee(route('clientes.show', 10), false);
        $this->assertCount(10, $response->viewData('clientes'));

        $this->get('/clientes/show/2')->assertOk()->assertSee('María García Ruiz')->assertSee('23456789D');
        $this->get('/clientes/show/999')->assertNotFound();
    }

    public function test_el_alta_guarda_con_el_autor_de_la_sesion_aunque_se_intente_falsear()
    {
        $repositorio = $this->repositorioEnMemoria();
        $this->actingAs(User::factory()->create(['name' => 'Carla Usuaria']));

        $this->post('/clientes/store', ['nombre' => 'Ana', 'apellidos' => 'Ruiz', 'direccion' => 'Calle Luna 3', 'cod_postal' => '28001', 'telefono' => '600123123', 'creado_por' => 'Otra Persona', 'creadoPor' => 'Otra Persona'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHas('mensaje');

        $cliente = $repositorio->porId(11);
        $this->assertNotNull($cliente, 'El alta debe guardar el cliente en el repositorio');
        $this->assertSame('Ana Ruiz', $cliente->nombreCompleto());
        $this->assertSame('28001', $cliente->codPostal);
        $this->assertSame('Carla Usuaria', $cliente->creadoPor);
        $this->get('/clientes/show/11')->assertOk()->assertSee('Ana Ruiz')->assertSee('Carla Usuaria');
    }

    public function test_la_modificacion_guarda_y_conserva_el_autor()
    {
        $repositorio = $this->repositorioEnMemoria();
        $repositorio->guardar(new Cliente(null, 'Ana', null, null, 'Calle Sol 1', null, '600000000', 'Carla Usuaria'));
        $this->actingAs(User::factory()->create(['name' => 'Otro Usuario']));

        $this->get('/clientes/edit/11')->assertOk()->assertSee('value="Ana"', false)->assertSee(route('clientes.update', 11), false);
        $this->put('/clientes/update/11', ['nombre' => 'Ana María', 'direccion' => 'Calle Sol 1', 'telefono' => '600000000'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('clientes.show', 11));

        $this->assertSame('Ana María', $repositorio->porId(11)->nombre);
        $this->assertSame('Carla Usuaria', $repositorio->porId(11)->creadoPor);
        $this->assertCount(11, $repositorio->todos());
        $this->get('/clientes/edit/999')->assertNotFound();
    }

    public function test_el_detalle_de_una_obra_muestra_su_cliente_desde_el_repositorio()
    {
        $repositorio = $this->repositorioEnMemoria();
        $repositorio->guardar(new Cliente(1, 'Cliente', 'Renombrado', null, 'Calle Sol 1', null, '600000000'));

        $this->get('/obras/show/1')->assertOk()->assertSee('Cliente Renombrado');
    }
}
