<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Especialista;
use App\Models\Falta;
use App\Models\Gasto;
use App\Models\Ingreso;
use App\Models\LineaParteTrabajo;
use App\Models\LineaPresupuesto;
use App\Models\Obra;
use App\Models\ParteEspecialista;
use App\Models\ParteProveedor;
use App\Models\ParteTrabajo;
use App\Models\Presupuesto;
use App\Models\Proveedor;
use App\Models\Trabajador;
use App\Models\User;
use App\Services\ResumenFinanciero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * El ciclo de una obra de principio a fin a través de las pantallas:
 * se inicia sesión, se usan los formularios y se comprueba lo que se muestra.
 */
class RA6_15_FlujoWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function iniciarSesion(): void
    {
        $user = User::factory()->create(['email' => 'jefe@obras.test', 'password' => 'secreto123', 'rol' => 'jefe']);

        $this->post('/login', ['email' => 'jefe@obras.test', 'password' => 'secreto123'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    /** La página tiene un formulario que envía a $accion con estos campos. */
    private function assertFormulario(string $pagina, string $accion, array $campos): void
    {
        $response = $this->get($pagina)->assertOk()->assertSee('action="' . $accion . '"', false);

        foreach ($campos as $campo) {
            $response->assertSee('name="' . $campo . '"', false);
        }
    }

    /** Envía un formulario y comprueba que no hay errores y adónde redirige. */
    private function enviar(string $metodo, string $url, array $datos = [], ?string $destino = null): void
    {
        $response = $this->{$metodo}($url, $datos)->assertSessionHasNoErrors();

        $destino ? $response->assertRedirect($destino) : $response->assertRedirect();
    }

    /** URL del detalle de la obra con una pestaña abierta (6.11). */
    private function pestana(Obra $obra, string $clave): string
    {
        return route('obras.show', [$obra->id, 'pestana' => $clave]);
    }

    /** Datos de partida: un cliente, dos trabajadores, un proveedor y un especialista, creados por formulario. */
    private function maestros(): array
    {
        $this->assertFormulario(route('clientes.create'), route('clientes.store'), ['nombre', 'apellidos', 'nif', 'direccion', 'cod_postal', 'telefono']);
        $this->enviar('post', route('clientes.store'), ['nombre' => 'Juan', 'apellidos' => 'Pérez López', 'nif' => '12345678Z', 'direccion' => 'Calle Mayor 1', 'telefono' => '600111222'], route('clientes.index'));

        $this->assertFormulario(route('trabajadores.create'), route('trabajadores.store'), ['tipo', 'nombre', 'apellido', 'telefono', 'coste_hora_estandar']);
        $this->enviar('post', route('trabajadores.store'), ['nombre' => 'Ana', 'apellido' => 'Sanz', 'coste_hora_estandar' => 20], route('trabajadores.index'));
        $this->enviar('post', route('trabajadores.store'), ['nombre' => 'Luis', 'apellido' => 'Gil', 'coste_hora_estandar' => 20], route('trabajadores.index'));

        $this->assertFormulario(route('proveedores.create'), route('proveedores.store'), ['nombre', 'correo', 'tipo']);
        $this->enviar('post', route('proveedores.store'), ['nombre' => 'Cerámicas Levante', 'tipo' => 'Materiales'], route('proveedores.index'));

        $this->assertFormulario(route('especialistas.create'), route('especialistas.store'), ['nombre', 'tipo', 'telefono']);
        $this->enviar('post', route('especialistas.store'), ['nombre' => 'Marta Vidal', 'tipo' => 'Arquitecta'], route('especialistas.index'));

        return [Cliente::firstOrFail(), Trabajador::where('nombre', 'Ana')->firstOrFail(), Trabajador::where('nombre', 'Luis')->firstOrFail(), Proveedor::firstOrFail(), Especialista::firstOrFail()];
    }

    /** Presupuesto con una línea de 10 uds × 100 €, IVA 21 %, creado por formulario. */
    private function presupuesto(Cliente $cliente): Presupuesto
    {
        $this->assertFormulario(route('presupuestos.create'), route('presupuestos.store'), ['cliente_id', 'fecha', 'iva', 'estado']);
        $this->post(route('presupuestos.store'), ['cliente_id' => $cliente->id, 'iva' => 21, 'estado' => 'no_aprobado'])->assertSessionHasNoErrors();
        $presupuesto = Presupuesto::firstOrFail();

        $this->assertFormulario(route('presupuestos.show', $presupuesto), route('presupuestos.lineas.store', $presupuesto), ['descripcion', 'uds', 'precio']);
        $this->enviar('post', route('presupuestos.lineas.store', $presupuesto), ['descripcion' => 'Alicatado de baño', 'uds' => 10, 'precio' => 100], route('presupuestos.show', $presupuesto));

        $this->get(route('presupuestos.show', $presupuesto))
            ->assertOk()
            ->assertSee($presupuesto->codigo)
            ->assertSee('Juan Pérez López')
            ->assertSee('Alicatado de baño')
            ->assertSee('1.000,00')
            ->assertSee('210,00')
            ->assertSee('1.210,00');

        return $presupuesto;
    }

    /** Obra del cliente, con el presupuesto vinculado y aprobado, un parte de trabajo y dos costes externos. */
    private function obraConCostes(Cliente $cliente, Presupuesto $presupuesto, Trabajador $ana, Trabajador $luis, Proveedor $proveedor, Especialista $especialista): Obra
    {
        // Obra nueva: nace con su ficha de Gastos y sin precio
        $this->assertFormulario(route('obras.create'), route('obras.store'), ['descripcion', 'cliente_id']);
        $this->post(route('obras.store'), ['descripcion' => 'Reforma integral', 'cliente_id' => $cliente->id])->assertSessionHasNoErrors();
        $obra = Obra::firstOrFail();
        $this->assertSame(1, Gasto::where('obra_id', $obra->id)->count());
        $this->get(route('obras.show', $obra))->assertOk()->assertSee($obra->codigo)->assertSee('Reforma integral')->assertSee('Juan Pérez López')->assertSee('Pendiente');

        // El presupuesto libre del cliente se ofrece en la obra y se vincula
        $this->assertFormulario(route('obras.show', $obra), route('obras.vincular-presupuesto', $obra), ['presupuesto_id']);
        $this->get(route('obras.show', $obra))->assertSee($presupuesto->codigo);
        $this->enviar('post', route('obras.vincular-presupuesto', $obra), ['presupuesto_id' => $presupuesto->id], route('obras.show', $obra));
        $this->assertSame($obra->id, $presupuesto->fresh()->obra_id);

        // No aprobado todavía: la obra sigue sin precio. Al aprobarlo, el total es 1.210,00
        $this->assertEquals(0, app(ResumenFinanciero::class)->obra($obra->fresh())['total']);
        $this->assertFormulario(route('presupuestos.edit', $presupuesto), route('presupuestos.update', $presupuesto), ['cliente_id', 'iva', 'estado', '_method']);
        $this->enviar('put', route('presupuestos.update', $presupuesto), ['cliente_id' => $cliente->id, 'obra_id' => $obra->id, 'iva' => 21, 'estado' => 'aprobado'], route('presupuestos.show', $presupuesto));
        $this->assertEquals(1210, app(ResumenFinanciero::class)->obra($obra->fresh())['total']);
        $this->get(route('obras.show', $obra))->assertSee('1.210,00');

        // Parte de trabajo: dos trabajadores, 8 h cada uno, con la tarifa copiada
        $this->assertFormulario(route('obras.partes-trabajo.create', $obra), route('obras.partes-trabajo.store', $obra), ['fecha', 'descripcion', 'lineas[0][trabajador_id]', 'lineas[0][horas]']);
        $this->get(route('obras.partes-trabajo.create', $obra))->assertSee('Ana Sanz')->assertSee('Luis Gil');
        $this->enviar('post', route('obras.partes-trabajo.store', $obra), [
            'descripcion' => 'Demolición de tabiques',
            'lineas' => [['trabajador_id' => $ana->id, 'horas' => 8], ['trabajador_id' => $luis->id, 'horas' => 8]],
        ], $this->pestana($obra, 'partes-trabajo'));
        $this->get($this->pestana($obra, 'partes-trabajo'))->assertSee('Demolición de tabiques')->assertSee('16,00')->assertSee('320,00')->assertSee('Ana Sanz')->assertSee('Luis Gil');
        $this->get(route('trabajadores.show', $ana))->assertSee('8,00')->assertSee('160,00');

        // Subir la tarifa no cambia lo ya registrado
        $this->enviar('put', route('trabajadores.update', $ana), ['nombre' => 'Ana', 'apellido' => 'Sanz', 'coste_hora_estandar' => 25], route('trabajadores.show', $ana));
        $this->get(route('obras.show', $obra))->assertSee('320,00');

        // Costes externos
        $this->assertFormulario(route('obras.partes-proveedor.create', $obra), route('obras.partes-proveedor.store', $obra), ['proveedor_id', 'fecha', 'descripcion', 'documento', 'importe', 'estado_pago']);
        $this->enviar('post', route('obras.partes-proveedor.store', $obra), ['proveedor_id' => $proveedor->id, 'importe' => 200], $this->pestana($obra, 'partes-proveedor'));
        $this->assertFormulario(route('obras.partes-especialista.create', $obra), route('obras.partes-especialista.store', $obra), ['especialista_id', 'fecha', 'descripcion', 'documento', 'importe', 'estado_pago']);
        $this->enviar('post', route('obras.partes-especialista.store', $obra), ['especialista_id' => $especialista->id, 'importe' => 150], $this->pestana($obra, 'partes-especialista'));
        $this->get(route('obras.show', $obra))->assertSee('670,00');
        $this->get($this->pestana($obra, 'partes-proveedor'))->assertSee('Cerámicas Levante');
        $this->get($this->pestana($obra, 'partes-especialista'))->assertSee('Marta Vidal');

        return $obra;
    }

    public function test_ciclo_completo_de_una_obra_por_las_pantallas()
    {
        $this->iniciarSesion();
        [$cliente, $ana, $luis, $proveedor, $especialista] = $this->maestros();
        $presupuesto = $this->presupuesto($cliente);
        $obra = $this->obraConCostes($cliente, $presupuesto, $ana, $luis, $proveedor, $especialista);
        $gasto = $obra->gasto;

        // Primer cobro: 500 € → parcial, en rojo, 41,3 % cobrado
        $this->assertFormulario(route('obras.ingresos.create', $obra), route('obras.ingresos.store', $obra), ['importe', 'tipo', 'fecha', 'documento', 'num_documento']);
        $this->enviar('post', route('obras.ingresos.store', $obra), ['importe' => 500, 'tipo' => 'Transferencia'], $this->pestana($obra, 'ingresos'));
        $this->get($this->pestana($obra, 'ingresos'))->assertSee(Ingreso::firstOrFail()->codigo);
        $this->get(route('obras.show', $obra))
            ->assertSee('-170,00')      // beneficio de caja
            ->assertSee('710,00')       // pendiente de cobro
            ->assertSee('540,00')       // margen proyectado
            ->assertSee('41,3')
            ->assertSee('Parcial')
            ->assertSee('Rojo');
        $this->get(route('gastos.show', $gasto))
            ->assertOk()
            ->assertSee($gasto->codigo)
            ->assertSee('670,00')
            ->assertSee('500,00')
            ->assertSee('-170,00')
            ->assertSee('710,00')
            ->assertSee('En proceso');

        // Segundo cobro: 710 € → pagado, en verde, 100 % cobrado
        $this->enviar('post', route('obras.ingresos.store', $obra), ['importe' => 710], $this->pestana($obra, 'ingresos'));
        $this->get(route('obras.show', $obra))->assertDontSee('-170,00')->assertSee('540,00')->assertSee('100,0')->assertSee('Pagado')->assertSee('Verde');
        $this->get(route('gastos.show', $gasto))->assertSee('1.210,00')->assertSee('540,00')->assertSee('Finalizada');

        // Datos de seguimiento de la ficha
        $this->assertFormulario(route('gastos.edit', $gasto), route('gastos.update', $gasto), ['direccion_obra', 'inicio_obra', 'fin_obra', 'solicitud_obra', 'mandante', 'gestion_licencia', 'pago_icio', '_method']);
        $this->enviar('put', route('gastos.update', $gasto), ['direccion_obra' => 'Calle Sol 3', 'inicio_obra' => '2026-03-01', 'fin_obra' => '2026-04-15'], route('gastos.show', $gasto));
        $this->get(route('gastos.show', $gasto))->assertSee('Calle Sol 3');

        // Una obra con cobros no se borra: se vuelve atrás y se muestra el motivo
        $response = $this->from(route('obras.show', $obra))->delete(route('obras.destroy', $obra));
        $response->assertRedirect(route('obras.show', $obra))->assertSessionHas('error');
        $this->assertDatabaseHas('obras', ['id' => $obra->id]);
        $motivo = session('error');
        $this->get(route('obras.show', $obra))->assertSee($motivo);
    }

    public function test_borrar_una_obra_sin_cobros_desde_su_pantalla()
    {
        $this->iniciarSesion();
        [$cliente, $ana, $luis, $proveedor, $especialista] = $this->maestros();
        $presupuesto = $this->presupuesto($cliente);
        $obra = $this->obraConCostes($cliente, $presupuesto, $ana, $luis, $proveedor, $especialista);

        $this->get(route('obras.show', $obra))->assertSee(route('obras.destroy', $obra), false);
        $this->enviar('delete', route('obras.destroy', $obra), [], route('obras.index'));

        $this->assertDatabaseMissing('obras', ['id' => $obra->id]);
        $this->assertDatabaseCount('gastos', 0);
        $this->assertDatabaseCount('partes_trabajo', 0);
        $this->assertDatabaseCount('partes_proveedor', 0);
        $this->assertDatabaseCount('partes_especialista', 0);
        $this->assertDatabaseHas('presupuestos', ['id' => $presupuesto->id, 'obra_id' => null]);
    }

    public function test_los_formularios_rechazan_los_datos_no_validos()
    {
        $this->iniciarSesion();
        $obra = Obra::factory()->create();
        $presupuesto = Presupuesto::factory()->create(['cliente_id' => $obra->cliente_id]);
        $otro = Cliente::factory()->create();
        $trabajador = Trabajador::factory()->create();
        $proveedor = Proveedor::factory()->create();

        $this->post(route('obras.partes-trabajo.store', $obra), ['descripcion' => 'X', 'lineas' => [['trabajador_id' => $trabajador->id, 'horas' => 25]]])->assertSessionHasErrors('lineas.0.horas');
        $this->post(route('obras.partes-trabajo.store', $obra), ['lineas' => []])->assertSessionHasErrors('descripcion');
        $this->post(route('obras.partes-proveedor.store', $obra), ['proveedor_id' => $proveedor->id, 'importe' => 0])->assertSessionHasErrors('importe');
        $this->post(route('obras.ingresos.store', $obra), ['importe' => 0])->assertSessionHasErrors('importe');
        $this->post(route('obras.ingresos.store', $obra), ['importe' => 10, 'cliente_id' => $otro->id])->assertSessionHasErrors('cliente_id');
        $this->post(route('presupuestos.store'), ['cliente_id' => $obra->cliente_id, 'iva' => 150])->assertSessionHasErrors('iva');
        $this->post(route('presupuestos.store'), ['cliente_id' => $otro->id, 'obra_id' => $obra->id])->assertSessionHasErrors('cliente_id');
        $this->post(route('presupuestos.lineas.store', $presupuesto), ['uds' => -1, 'precio' => 10])->assertSessionHasErrors('uds');
        $this->post(route('trabajadores.faltas.store', $trabajador), ['fecha_inicio' => '2026-05-10', 'fecha_fin' => '2026-05-01'])->assertSessionHasErrors('fecha_fin');
        $this->put(route('gastos.update', $obra->gasto), ['inicio_obra' => '2026-05-10', 'fin_obra' => '2026-05-01'])->assertSessionHasErrors('fin_obra');

        $this->assertDatabaseCount('partes_trabajo', 0);
        $this->assertDatabaseCount('partes_proveedor', 0);
        $this->assertDatabaseCount('ingresos', 0);
        $this->assertDatabaseCount('lineas_presupuesto', 0);
        $this->assertDatabaseCount('faltas', 0);
    }

    public function test_todas_las_pantallas_responden()
    {
        $this->iniciarSesion();
        $obra = Obra::factory()->create();
        $presupuesto = Presupuesto::factory()->create(['cliente_id' => $obra->cliente_id, 'obra_id' => $obra->id]);
        $falta = Falta::factory()->create();
        $proveedor = ParteProveedor::factory()->create(['obra_id' => $obra->id])->proveedor;
        $especialista = ParteEspecialista::factory()->create(['obra_id' => $obra->id])->especialista;
        LineaParteTrabajo::factory()->create(['parte_trabajo_id' => ParteTrabajo::factory()->create(['obra_id' => $obra->id])->id]);
        LineaPresupuesto::factory()->create(['presupuesto_id' => $presupuesto->id]);
        Ingreso::factory()->create(['obra_id' => $obra->id]);

        $ids = ['clientes' => $obra->cliente, 'trabajadores' => $falta->trabajador, 'proveedores' => $proveedor, 'especialistas' => $especialista, 'presupuestos' => $presupuesto, 'obras' => $obra];
        foreach ($ids as $modulo => $registro) {
            $this->get(route("$modulo.index"))->assertOk();
            $this->get(route("$modulo.show", $registro))->assertOk();
            $this->get(route("$modulo.create"))->assertOk();
            $this->get(route("$modulo.edit", $registro))->assertOk();
        }
        $this->get(route('gastos.index'))->assertOk()->assertSee($obra->gasto->codigo);
        $this->get(route('gastos.show', $obra->gasto))->assertOk();
        $this->get(route('gastos.edit', $obra->gasto))->assertOk();
        $this->get(route('faltas.index'))->assertOk()->assertSee($falta->trabajador->nombre);
        $this->get(route('trabajadores.faltas.create', $falta->trabajador))->assertOk();

        // El menú enlaza todos los módulos
        $menu = $this->get(route('clientes.index'));
        foreach (['clientes', 'trabajadores', 'proveedores', 'especialistas', 'presupuestos', 'obras', 'gastos', 'faltas'] as $modulo) {
            $menu->assertSee(route("$modulo.index"), false);
        }
    }

    public function test_altas_y_bajas_de_lo_que_cuelga_de_cada_registro()
    {
        $this->iniciarSesion();
        $obra = Obra::factory()->create();
        $trabajador = Trabajador::factory()->create();

        // Falta del trabajador, con sus días
        $this->assertFormulario(route('trabajadores.faltas.create', $trabajador), route('trabajadores.faltas.store', $trabajador), ['fecha_inicio', 'fecha_fin', 'tipo', 'motivo']);
        $this->enviar('post', route('trabajadores.faltas.store', $trabajador), ['fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-08-07', 'tipo' => 'vacaciones'], route('trabajadores.show', $trabajador));
        $falta = Falta::firstOrFail();
        $this->get(route('trabajadores.show', $trabajador))->assertSee('Vacaciones')->assertSee(route('trabajadores.faltas.destroy', [$trabajador, $falta]), false);
        $this->enviar('delete', route('trabajadores.faltas.destroy', [$trabajador, $falta]), [], route('trabajadores.show', $trabajador));
        $this->assertDatabaseCount('faltas', 0);

        // Cada parte e ingreso se borra desde la obra
        $parte = ParteTrabajo::factory()->create(['obra_id' => $obra->id]);
        $pp = ParteProveedor::factory()->create(['obra_id' => $obra->id]);
        $pe = ParteEspecialista::factory()->create(['obra_id' => $obra->id]);
        $ingreso = Ingreso::factory()->create(['obra_id' => $obra->id]);
        $rutas = [
            'partes-trabajo' => route('obras.partes-trabajo.destroy', [$obra, $parte]),
            'partes-proveedor' => route('obras.partes-proveedor.destroy', [$obra, $pp]),
            'partes-especialista' => route('obras.partes-especialista.destroy', [$obra, $pe]),
            'ingresos' => route('obras.ingresos.destroy', [$obra, $ingreso]),
        ];
        foreach ($rutas as $clave => $ruta) {
            $this->get($this->pestana($obra, $clave))->assertSee($ruta, false);
            $this->enviar('delete', $ruta, [], $this->pestana($obra, $clave));
        }
        $this->assertDatabaseCount('partes_trabajo', 0);
        $this->assertDatabaseCount('partes_proveedor', 0);
        $this->assertDatabaseCount('partes_especialista', 0);
        $this->assertDatabaseCount('ingresos', 0);

        // Línea de presupuesto
        $presupuesto = Presupuesto::factory()->create();
        $linea = LineaPresupuesto::factory()->create(['presupuesto_id' => $presupuesto->id]);
        $this->get(route('presupuestos.show', $presupuesto))->assertSee(route('presupuestos.lineas.destroy', [$presupuesto, $linea]), false);
        $this->enviar('delete', route('presupuestos.lineas.destroy', [$presupuesto, $linea]), [], route('presupuestos.show', $presupuesto));
        $this->assertDatabaseCount('lineas_presupuesto', 0);

        // Un hijo de otro padre no se toca
        $ajeno = Ingreso::factory()->create();
        $this->delete(route('obras.ingresos.destroy', [$obra, $ajeno]))->assertNotFound();
        $this->assertDatabaseHas('ingresos', ['id' => $ajeno->id]);
    }

    public function test_observaciones_incidencias_y_adjuntos_en_la_ficha_de_la_obra()
    {
        Storage::fake();
        $this->iniciarSesion();
        $obra = Obra::factory()->create();
        $volver = route('obras.show', $obra);

        $this->assertFormulario($volver, route('bitacoras.observaciones', ['obras', $obra->id]), ['texto']);
        $this->assertFormulario($volver, route('bitacoras.incidencias', ['obras', $obra->id]), ['texto']);
        $this->assertFormulario($volver, route('bitacoras.archivos', ['obras', $obra->id]), ['archivo']);

        $this->from($volver)->post(route('bitacoras.observaciones', ['obras', $obra->id]), ['texto' => 'Llamar al cliente el lunes'])->assertRedirect($volver);
        $this->from($volver)->post(route('bitacoras.incidencias', ['obras', $obra->id]), ['texto' => 'Fuga en la bajante'])->assertRedirect($volver);
        $this->from($volver)->post(route('bitacoras.archivos', ['obras', $obra->id]), ['archivo' => UploadedFile::fake()->create('licencia.pdf', 10, 'application/pdf')])->assertRedirect($volver);

        $this->get($volver)
            ->assertSee('Llamar al cliente el lunes')
            ->assertSee('Fuga en la bajante')
            ->assertSee('licencia.pdf')
            ->assertSee(auth()->user()->name);
        $this->assertDatabaseHas('observaciones', ['observable_type' => 'obras', 'observable_id' => $obra->id, 'user_id' => auth()->id()]);
        Storage::assertExists(\App\Models\Archivo::firstOrFail()->ruta);
    }

    public function test_la_ficha_de_gastos_no_se_crea_ni_se_borra_por_la_web()
    {
        foreach (['gastos.create', 'gastos.store', 'gastos.destroy'] as $ruta) {
            $this->assertFalse(Route::has($ruta), "No debe existir la ruta $ruta");
        }
    }

    public function test_escribir_exige_iniciar_sesion()
    {
        $obra = Obra::factory()->create();
        $presupuesto = Presupuesto::factory()->create();
        $trabajador = Trabajador::factory()->create();

        $rutas = [
            route('obras.partes-trabajo.store', $obra), route('obras.partes-proveedor.store', $obra), route('obras.partes-especialista.store', $obra),
            route('obras.ingresos.store', $obra), route('obras.vincular-presupuesto', $obra), route('presupuestos.store'),
            route('presupuestos.lineas.store', $presupuesto), route('proveedores.store'), route('especialistas.store'),
            route('trabajadores.faltas.store', $trabajador), route('bitacoras.observaciones', ['obras', $obra->id]),
        ];
        foreach ($rutas as $ruta) {
            $this->post($ruta, [])->assertRedirect('/login');
        }
        $this->put(route('gastos.update', $obra->gasto), [])->assertRedirect('/login');
        $this->assertDatabaseCount('partes_trabajo', 0);
    }
}
