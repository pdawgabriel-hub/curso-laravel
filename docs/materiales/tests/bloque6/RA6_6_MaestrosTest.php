<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Especialista;
use App\Models\Proveedor;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** 6.6: clientes, trabajadores, proveedores y especialistas en la base de datos, con sus vistas. */
class RA6_6_MaestrosTest extends TestCase
{
    use RefreshDatabase;

    /** Usuario con rol de jefe. La columna `rol` llega en 6.7: hasta entonces, el usuario no lleva rol. */
    private function usuario(array $datos = []): User
    {
        return User::factory()->create($datos + (Schema::hasColumn('users', 'rol') ? ['rol' => 'jefe'] : []));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function entrar(): void
    {
        $this->actingAs($this->usuario());
    }

    /** Las columnas del listado son encabezados <th> con estos textos. */
    private function assertColumnas(TestResponse $r, array $columnas): void
    {
        foreach ($columnas as $c) {
            $this->assertMatchesRegularExpression('#<th[^>]*>\s*' . preg_quote($c, '#') . '\s*</th>#u', $r->getContent(), "Falta la columna «{$c}» en el listado");
        }
    }

    private function assertMenuActivo(TestResponse $r, string $ruta): void
    {
        $this->assertMatchesRegularExpression('#href="' . preg_quote(route($ruta), '#') . '"[^>]*aria-current="page"#', $r->getContent(), "El menú no marca {$ruta} como página actual");
    }

    public static function maestros(): array
    {
        return [
            'clientes' => ['clientes', Cliente::class, ['Código', 'Nombre', 'NIF', 'Teléfono']],
            'trabajadores' => ['trabajadores', Trabajador::class, ['Código', 'Nombre', 'Tipo', 'Tarifa']],
            'proveedores' => ['proveedores', Proveedor::class, ['Código', 'Nombre', 'Tipo', 'Total']],
            'especialistas' => ['especialistas', Especialista::class, ['Código', 'Nombre', 'Tipo', 'Teléfono']],
        ];
    }

    #[DataProvider('maestros')]
    public function test_nada_se_ve_sin_iniciar_sesion(string $recurso, string $clase, array $columnas)
    {
        $m = $clase::factory()->create();

        $this->get(route("{$recurso}.index"))->assertRedirect('/login');
        $this->get(route("{$recurso}.show", $m->id))->assertRedirect('/login');
        $this->get(route("{$recurso}.create"))->assertRedirect('/login');
        $this->delete(route("{$recurso}.destroy", $m->id))->assertRedirect('/login');
    }

    #[DataProvider('maestros')]
    public function test_el_listado(string $recurso, string $clase, array $columnas)
    {
        $this->entrar();

        $vacio = $this->get(route("{$recurso}.index"))->assertOk()->assertSee('No hay');
        $this->assertMenuActivo($vacio, "{$recurso}.index");
        $this->assertColumnas($vacio, $columnas);

        $registros = $clase::factory()->count(16)->create();
        $lleno = $this->get(route("{$recurso}.index"))->assertOk()->assertDontSee('No hay')->assertSee('<h1', false);
        $lleno->assertSee($registros->first()->codigo)->assertSee(route("{$recurso}.show", $registros->first()->id), false);
        $lleno->assertSee(route("{$recurso}.create"), false);
        $lleno->assertSee('page=2', false);
        $html = $lleno->getContent();
        $enPagina = $registros->filter(fn ($m) => str_contains($html, route("{$recurso}.show", $m->id) . '"'))->count();
        $this->assertSame(15, $enPagina, 'El listado pagina de 15 en 15');
    }

    #[DataProvider('maestros')]
    public function test_el_detalle(string $recurso, string $clase, array $columnas)
    {
        $this->entrar();
        $m = $clase::factory()->create();

        $r = $this->get(route("{$recurso}.show", $m->id))->assertOk()->assertSee($m->codigo)->assertSee('<h1', false);
        $r->assertSee(route("{$recurso}.edit", $m->id), false);
        $r->assertSee('action="' . route("{$recurso}.destroy", $m->id) . '"', false)->assertSee('return confirm(', false);
        $this->assertMenuActivo($r, "{$recurso}.index");
        $this->get(route("{$recurso}.show", 999))->assertNotFound();
    }

    public function test_clientes_alta_modificacion_y_borrado()
    {
        $this->entrar();
        $datos = ['nombre' => 'Ana', 'apellidos' => 'Ruiz', 'nif' => '11111111H', 'direccion' => 'Calle Luna 3', 'telefono' => '600123123'];

        $this->get(route('clientes.create'))->assertOk()->assertSee('name="nombre"', false)->assertSee('name="direccion"', false);
        $this->followingRedirects()->post(route('clientes.store'), $datos)->assertOk()->assertSee('role="status"', false);
        $c = Cliente::firstOrFail();
        $this->assertSame('CLI0001', $c->codigo);
        $this->get(route('clientes.show', $c->id))->assertSee('Ana Ruiz')->assertSee('Calle Luna 3');

        $this->get(route('clientes.edit', $c->id))->assertOk()->assertSee('value="Ana"', false);
        $this->put(route('clientes.update', $c->id), ['telefono' => '699999999'] + $datos)->assertSessionHasNoErrors()->assertRedirect(route('clientes.show', $c->id));
        $this->assertDatabaseHas('clientes', ['id' => $c->id, 'telefono' => '699999999']);

        $this->delete(route('clientes.destroy', $c->id))->assertRedirect(route('clientes.index'))->assertSessionHas('mensaje');
        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_clientes_validacion_con_errores_en_la_vista()
    {
        $this->entrar();
        Cliente::factory()->create(['nif' => '11111111H']);

        $this->post(route('clientes.store'), ['nombre' => 'Ana', 'direccion' => 'C/ Sol', 'telefono' => '6', 'nif' => '11111111H'])->assertSessionHasErrors('nif');
        $this->post(route('clientes.store'), [])->assertSessionHasErrors(['nombre', 'direccion', 'telefono']);

        $this->from(route('clientes.create'))->followingRedirects()->post(route('clientes.store'), ['nombre' => 'Ana'])
            ->assertOk()
            ->assertSee('value="Ana"', false)
            ->assertSee('aria-invalid="true"', false);
        $this->assertDatabaseCount('clientes', 1);
    }

    public function test_clientes_admite_su_propio_nif_al_modificar()
    {
        $this->entrar();
        $c = Cliente::factory()->create(['nif' => '11111111H']);

        $this->put(route('clientes.update', $c->id), ['nombre' => 'Ana', 'direccion' => 'C/ Sol', 'telefono' => '6', 'nif' => '11111111H'])->assertSessionHasNoErrors();
    }

    public function test_trabajadores()
    {
        $this->entrar();

        $this->post(route('trabajadores.store'), ['nombre' => 'Eva', 'apellido' => 'Sanz', 'tipo' => 'Encargado', 'coste_hora_estandar' => 22])->assertSessionHasNoErrors()->assertRedirect(route('trabajadores.index'));
        $t = Trabajador::firstOrFail();
        $this->assertSame('TRB0001', $t->codigo);
        $this->get(route('trabajadores.show', $t->id))->assertSee('Eva Sanz')->assertSee('Encargado')->assertSee('22,00');

        $this->put(route('trabajadores.update', $t->id), ['nombre' => 'Eva', 'apellido' => 'Sanz', 'coste_hora_estandar' => 25])->assertRedirect(route('trabajadores.show', $t->id));
        $this->assertEquals(25, $t->fresh()->coste_hora_estandar);

        $this->from(route('trabajadores.create'))->followingRedirects()->post(route('trabajadores.store'), ['nombre' => 'Eva', 'apellido' => 'Sanz', 'coste_hora_estandar' => -1])
            ->assertSee('aria-invalid="true"', false);
        $this->assertDatabaseCount('trabajadores', 1);

        $this->delete(route('trabajadores.destroy', $t->id))->assertRedirect(route('trabajadores.index'));
        $this->assertDatabaseCount('trabajadores', 0);
    }

    public function test_proveedores()
    {
        $this->entrar();

        $this->post(route('proveedores.store'), ['nombre' => 'Materiales Sur', 'tipo' => 'Materiales'])->assertSessionHasNoErrors()->assertRedirect(route('proveedores.index'));
        $p = Proveedor::firstOrFail();
        $this->get(route('proveedores.index'))->assertSee('Materiales Sur');
        $this->get(route('proveedores.show', $p->id))->assertSee($p->codigo)->assertSee('Materiales');
        $this->put(route('proveedores.update', $p->id), ['nombre' => 'Materiales Norte'])->assertRedirect(route('proveedores.show', $p->id));
        $this->assertDatabaseHas('proveedores', ['id' => $p->id, 'nombre' => 'Materiales Norte']);

        $this->post(route('proveedores.store'), ['correo' => 'mal'])->assertSessionHasErrors(['nombre', 'correo']);

        $this->delete(route('proveedores.destroy', $p->id))->assertRedirect(route('proveedores.index'));
        $this->assertDatabaseCount('proveedores', 0);
    }

    public function test_especialistas()
    {
        $this->entrar();

        $this->post(route('especialistas.store'), ['nombre' => 'Marta Gil', 'tipo' => 'Arquitecta'])->assertSessionHasNoErrors()->assertRedirect(route('especialistas.index'));
        $e = Especialista::firstOrFail();
        $this->get(route('especialistas.index'))->assertSee('Marta Gil');
        $this->get(route('especialistas.show', $e->id))->assertSee('Arquitecta');
        $this->put(route('especialistas.update', $e->id), ['nombre' => 'Marta Vidal'])->assertRedirect(route('especialistas.show', $e->id));

        $this->post(route('especialistas.store'), ['correo' => 'mal'])->assertSessionHasErrors(['nombre', 'correo']);

        $this->delete(route('especialistas.destroy', $e->id))->assertRedirect(route('especialistas.index'));
        $this->assertDatabaseCount('especialistas', 0);
    }

    public function test_cada_formulario_tiene_su_form_request()
    {
        $esperados = [
            \App\Http\Requests\ClienteRequest::class => ['nombre', 'apellidos', 'nif', 'direccion', 'cod_postal', 'telefono'],
            \App\Http\Requests\TrabajadorRequest::class => ['nombre', 'apellido', 'tipo', 'telefono', 'coste_hora_estandar'],
            \App\Http\Requests\ProveedorRequest::class => ['nombre', 'direccion', 'telefono', 'correo', 'tipo', 'referencia', 'documento', 'num_documento'],
            \App\Http\Requests\EspecialistaRequest::class => ['nombre', 'tipo', 'referencia', 'comunicacion_parte', 'telefono', 'correo', 'notas'],
        ];

        foreach ($esperados as $clase => $campos) {
            $this->assertTrue(class_exists($clase), "Falta la clase $clase");
            $this->assertTrue(is_subclass_of($clase, \Illuminate\Foundation\Http\FormRequest::class), "$clase debe extender FormRequest");
            $reglas = (new $clase)->rules();
            foreach ($campos as $campo) {
                $this->assertArrayHasKey($campo, $reglas, "$clase no tiene reglas para $campo");
            }
        }
    }
}
