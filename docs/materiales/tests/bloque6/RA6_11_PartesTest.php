<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Especialista;
use App\Models\Ingreso;
use App\Models\LineaParteTrabajo;
use App\Models\Obra;
use App\Models\ParteEspecialista;
use App\Models\ParteProveedor;
use App\Models\ParteTrabajo;
use App\Models\Proveedor;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 6.11: partes e ingresos desde las pestañas del detalle de la obra. */
class RA6_11_PartesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Obra $obra;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->user = User::factory()->create(['rol' => 'jefe']);
        $this->actingAs($this->user);
        $this->obra = Obra::factory()->create(['descripcion' => 'Reforma de baño']);
    }

    /** URL del detalle de la obra con una pestaña abierta. */
    private function pestana(string $clave, ?Obra $obra = null): string
    {
        return route('obras.show', [($obra ?? $this->obra)->id, 'pestana' => $clave]);
    }

    public function test_el_detalle_de_la_obra_tiene_pestanas()
    {
        $html = $this->get(route('obras.show', $this->obra->id))->assertOk()->getContent();

        $this->assertStringContainsString('<nav aria-label="Pestañas"', $html);
        foreach (['resumen', 'partes-trabajo', 'partes-proveedor', 'partes-especialista', 'ingresos'] as $clave) {
            $this->assertStringContainsString('pestana=' . $clave . '"', $html, "Falta la pestaña $clave");
        }
        $this->assertMatchesRegularExpression('#pestana=resumen"[^>]*aria-current="page"#', $html, 'Sin ?pestana, la activa es Resumen');

        $otra = $this->get(route('obras.show', [$this->obra->id, 'pestana' => 'otra']))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#pestana=resumen"[^>]*aria-current="page"#', $otra, 'Una pestaña desconocida muestra el resumen');
    }

    public function test_cada_pestana_muestra_su_tabla_y_su_boton_de_alta()
    {
        $pestanas = [
            'partes-trabajo' => ['obras.partes-trabajo.create', ['Fecha', 'Descripción', 'Trabajadores', 'Horas', 'Coste']],
            'partes-proveedor' => ['obras.partes-proveedor.create', ['Fecha', 'Proveedor', 'Documento', 'Importe', 'Estado de pago']],
            'partes-especialista' => ['obras.partes-especialista.create', ['Fecha', 'Especialista', 'Documento', 'Importe', 'Estado de pago']],
            'ingresos' => ['obras.ingresos.create', ['Código', 'Fecha', 'Tipo', 'Importe']],
        ];

        foreach ($pestanas as $clave => [$alta, $columnas]) {
            $r = $this->get($this->pestana($clave))->assertOk()->assertSee(route($alta, $this->obra->id), false)->assertSee('No hay');
            $this->assertMatchesRegularExpression('#pestana=' . $clave . '"[^>]*aria-current="page"#', $r->getContent(), "La pestaña $clave queda marcada");
            $r->assertSeeInOrder(array_merge(...array_map(fn ($c) => ['<th scope="col"', $c], $columnas)), false);
        }
    }

    public function test_solo_se_ve_la_pestana_activa()
    {
        ParteTrabajo::factory()->create(['obra_id' => $this->obra->id, 'descripcion' => 'Demolición del tabique']);
        $i = Ingreso::factory()->create(['obra_id' => $this->obra->id]);

        $this->get(route('obras.show', $this->obra->id))->assertSee('Reforma de baño')
            ->assertDontSee('Demolición del tabique')->assertDontSee($i->codigo)
            ->assertDontSee(route('obras.partes-trabajo.create', $this->obra->id), false);
        $this->get($this->pestana('partes-trabajo'))->assertSee('Demolición del tabique')->assertDontSee($i->codigo);
        $this->get($this->pestana('ingresos'))->assertSee($i->codigo)->assertDontSee('Demolición del tabique');
    }

    public function test_parte_de_trabajo_desde_la_obra()
    {
        $t = Trabajador::factory()->create(['nombre' => 'Ana', 'apellido' => 'Sanz', 'coste_hora_estandar' => 20]);

        $this->get(route('obras.partes-trabajo.create', $this->obra))->assertOk()
            ->assertSee('Ana Sanz')->assertSee('name="lineas[0][trabajador_id]"', false)->assertSee('name="lineas[0][horas]"', false)
            ->assertSee('name="descripcion"', false);
        $this->post(route('obras.partes-trabajo.store', $this->obra), ['descripcion' => 'Demolición', 'lineas' => [['trabajador_id' => $t->id, 'horas' => 8]], 'user_id' => 999])
            ->assertSessionHasNoErrors()
            ->assertRedirect($this->pestana('partes-trabajo'));

        $parte = ParteTrabajo::firstOrFail();
        $this->assertSame($this->user->id, $parte->user_id, 'El propietario es el usuario conectado');
        $this->assertDatabaseHas('lineas_parte_trabajo', ['parte_trabajo_id' => $parte->id, 'coste_hora' => 20]);
        $this->get($this->pestana('partes-trabajo'))->assertSee('Demolición')->assertSee('Ana Sanz')->assertSee('8,00')->assertSee('160,00')
            ->assertSee('action="' . route('obras.partes-trabajo.destroy', [$this->obra, $parte]) . '"', false);

        $this->delete(route('obras.partes-trabajo.destroy', [$this->obra, $parte]))->assertRedirect($this->pestana('partes-trabajo'));
        $this->assertDatabaseCount('lineas_parte_trabajo', 0);
    }

    public function test_validaciones_del_parte_y_otro_padre()
    {
        $t = Trabajador::factory()->create();
        $ajeno = ParteTrabajo::factory()->create();

        $this->post(route('obras.partes-trabajo.store', $this->obra), ['descripcion' => 'X', 'lineas' => [['trabajador_id' => $t->id, 'horas' => 25]]])->assertSessionHasErrors('lineas.0.horas');
        $this->from(route('obras.partes-trabajo.create', $this->obra))->followingRedirects()
            ->post(route('obras.partes-trabajo.store', $this->obra), ['descripcion' => 'X', 'lineas' => [['trabajador_id' => $t->id, 'horas' => 25]]])
            ->assertSee('aria-invalid="true"', false);
        $this->post(route('obras.partes-trabajo.store', $this->obra), [])->assertSessionHasErrors('descripcion');
        $this->delete(route('obras.partes-trabajo.destroy', [$this->obra, $ajeno]))->assertNotFound();
        $this->assertDatabaseCount('partes_trabajo', 1);
    }

    public function test_partes_externos_desde_la_obra()
    {
        $prov = Proveedor::factory()->create(['nombre' => 'Cerámicas Levante']);
        $esp = Especialista::factory()->create(['nombre' => 'Marta Vidal']);

        $this->get(route('obras.partes-proveedor.create', $this->obra))->assertOk()->assertSee('name="proveedor_id"', false)->assertSee('Cerámicas Levante')->assertSee('name="estado_pago"', false);
        $this->get(route('obras.partes-especialista.create', $this->obra))->assertOk()->assertSee('name="especialista_id"', false);
        $this->post(route('obras.partes-proveedor.store', $this->obra), ['proveedor_id' => $prov->id, 'importe' => 200])->assertRedirect($this->pestana('partes-proveedor'));
        $this->post(route('obras.partes-especialista.store', $this->obra), ['especialista_id' => $esp->id, 'importe' => 150])->assertRedirect($this->pestana('partes-especialista'));

        $pp = ParteProveedor::firstOrFail();
        $this->assertSame($this->user->id, $pp->user_id);
        $this->assertSame('pendiente', $pp->fresh()->estado_pago);
        $this->get($this->pestana('partes-proveedor'))->assertSee('Cerámicas Levante')->assertSee('200,00')
            ->assertSee('action="' . route('obras.partes-proveedor.destroy', [$this->obra, $pp]) . '"', false);
        $this->get($this->pestana('partes-especialista'))->assertSee('Marta Vidal')->assertSee('150,00');
        $this->delete(route('obras.partes-proveedor.destroy', [$this->obra, $pp]))->assertRedirect($this->pestana('partes-proveedor'));

        $this->post(route('obras.partes-proveedor.store', $this->obra), ['proveedor_id' => $prov->id, 'importe' => 0])->assertSessionHasErrors('importe');
        $this->delete(route('obras.partes-especialista.destroy', [Obra::factory()->create(), ParteEspecialista::firstOrFail()]))->assertNotFound();
    }

    public function test_no_se_borra_lo_que_tiene_partes()
    {
        $linea = LineaParteTrabajo::factory()->create();
        $pp = ParteProveedor::factory()->create();
        $pe = ParteEspecialista::factory()->create();

        foreach ([route('trabajadores.destroy', $linea->trabajador_id), route('proveedores.destroy', $pp->proveedor_id), route('especialistas.destroy', $pe->especialista_id)] as $ruta) {
            $this->from('/')->delete($ruta)->assertRedirect('/')->assertSessionHas('error');
        }
        $this->assertDatabaseHas('trabajadores', ['id' => $linea->trabajador_id]);
        $this->assertDatabaseHas('proveedores', ['id' => $pp->proveedor_id]);
        $this->assertDatabaseHas('especialistas', ['id' => $pe->especialista_id]);
    }

    public function test_ingresos_desde_la_obra()
    {
        $this->get(route('obras.ingresos.create', $this->obra))->assertOk()->assertSee('name="importe"', false)->assertSee('name="tipo"', false);
        $this->post(route('obras.ingresos.store', $this->obra), ['importe' => 500, 'tipo' => 'Transferencia'])->assertSessionHasNoErrors()->assertRedirect($this->pestana('ingresos'));
        $i = Ingreso::firstOrFail();
        $this->get($this->pestana('ingresos'))->assertSee($i->codigo)->assertSee('500,00')
            ->assertSee('action="' . route('obras.ingresos.destroy', [$this->obra, $i]) . '"', false);

        $this->delete(route('obras.ingresos.destroy', [$this->obra, $i]))->assertRedirect($this->pestana('ingresos'));
        $this->assertDatabaseCount('ingresos', 0);
    }

    public function test_validaciones_del_ingreso()
    {
        $otro = Cliente::factory()->create();
        $ajeno = Ingreso::factory()->create();

        $this->from(route('obras.ingresos.create', $this->obra))->followingRedirects()
            ->post(route('obras.ingresos.store', $this->obra), ['importe' => 0, 'tipo' => 'Cheque'])
            ->assertSee('aria-invalid="true"', false)->assertSee('value="Cheque"', false);
        $this->post(route('obras.ingresos.store', $this->obra), ['importe' => 10, 'cliente_id' => $otro->id])->assertSessionHasErrors('cliente_id');
        $this->delete(route('obras.ingresos.destroy', [$this->obra, $ajeno]))->assertNotFound();
        $this->assertDatabaseHas('ingresos', ['id' => $ajeno->id]);
    }

    public function test_cada_formulario_tiene_su_form_request()
    {
        $esperados = [
            \App\Http\Requests\ParteTrabajoRequest::class => ['fecha', 'descripcion', 'lineas', 'lineas.*.trabajador_id', 'lineas.*.horas'],
            \App\Http\Requests\ParteProveedorRequest::class => ['proveedor_id', 'importe', 'estado_pago', 'fecha', 'descripcion', 'documento'],
            \App\Http\Requests\ParteEspecialistaRequest::class => ['especialista_id', 'importe', 'estado_pago', 'fecha', 'descripcion', 'documento'],
            \App\Http\Requests\IngresoRequest::class => ['importe', 'tipo', 'fecha', 'documento', 'num_documento', 'cliente_id'],
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
