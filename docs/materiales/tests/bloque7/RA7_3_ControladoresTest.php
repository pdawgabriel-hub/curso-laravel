<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Especialista;
use App\Models\Falta;
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
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

/** 7.3: los controladores de recursos de todas las entidades. */
class RA7_3_ControladoresTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('jefe');
    }

    public static function recursos(): array
    {
        return array_map(fn ($r) => [$r], array_combine($r = [
            'clientes', 'trabajadores', 'proveedores', 'especialistas', 'presupuestos', 'obras',
            'faltas', 'lineas-presupuesto', 'ingresos', 'partes-trabajo', 'partes-proveedor', 'partes-especialista', 'lineas-parte',
        ], $r));
    }

    /**
     * Para cada recurso: [URL de la colección, registro existente, datos válidos, campos obligatorios, registro de otro padre o null].
     */
    private function recurso(string $nombre): array
    {
        $obra = fn () => Obra::factory()->create();
        return match ($nombre) {
            'clientes' => ['/api/v1/clientes', Cliente::factory()->create(), ['nombre' => 'Ana', 'direccion' => 'C/ Sol 1', 'telefono' => '600000000'], ['nombre', 'direccion', 'telefono'], null],
            'trabajadores' => ['/api/v1/trabajadores', Trabajador::factory()->create(), ['nombre' => 'Luis', 'apellido' => 'Gil'], ['nombre', 'apellido'], null],
            'proveedores' => ['/api/v1/proveedores', Proveedor::factory()->create(), ['nombre' => 'Sur', 'base' => 100, 'iva' => 21], ['nombre', 'base', 'iva'], null],
            'especialistas' => ['/api/v1/especialistas', Especialista::factory()->create(), ['nombre' => 'Marta'], ['nombre'], null],
            'presupuestos' => ['/api/v1/presupuestos', Presupuesto::factory()->create(), ['cliente_id' => Cliente::factory()->create()->id], ['cliente_id'], null],
            'obras' => ['/api/v1/obras', Obra::factory()->create(), ['descripcion' => 'Reforma de baño'], [], null],
            'faltas' => (function () {
                $t = Trabajador::factory()->create();
                return ["/api/v1/trabajadores/{$t->id}/faltas", Falta::factory()->create(['trabajador_id' => $t->id]), ['fecha_inicio' => '2026-05-04', 'fecha_fin' => '2026-05-08'], ['fecha_inicio', 'fecha_fin'], Falta::factory()->create()];
            })(),
            'lineas-presupuesto' => (function () {
                $p = Presupuesto::factory()->create();
                return ["/api/v1/presupuestos/{$p->id}/lineas", LineaPresupuesto::factory()->create(['presupuesto_id' => $p->id]), ['uds' => 2, 'precio' => 10], ['uds', 'precio'], LineaPresupuesto::factory()->create()];
            })(),
            'ingresos' => (function () use ($obra) {
                $o = $obra();
                return ["/api/v1/obras/{$o->id}/ingresos", Ingreso::factory()->create(['obra_id' => $o->id]), ['importe' => 100], ['importe'], Ingreso::factory()->create()];
            })(),
            'partes-trabajo' => (function () use ($obra) {
                $o = $obra();
                return ["/api/v1/obras/{$o->id}/partes-trabajo", ParteTrabajo::factory()->create(['obra_id' => $o->id]), ['descripcion' => 'Demolición'], ['descripcion'], ParteTrabajo::factory()->create()];
            })(),
            'partes-proveedor' => (function () use ($obra) {
                $o = $obra();
                return ["/api/v1/obras/{$o->id}/partes-proveedor", ParteProveedor::factory()->create(['obra_id' => $o->id]), ['proveedor_id' => Proveedor::factory()->create()->id, 'importe' => 200], ['proveedor_id', 'importe'], ParteProveedor::factory()->create()];
            })(),
            'partes-especialista' => (function () use ($obra) {
                $o = $obra();
                return ["/api/v1/obras/{$o->id}/partes-especialista", ParteEspecialista::factory()->create(['obra_id' => $o->id]), ['especialista_id' => Especialista::factory()->create()->id, 'importe' => 150], ['especialista_id', 'importe'], ParteEspecialista::factory()->create()];
            })(),
            'lineas-parte' => (function () {
                $p = ParteTrabajo::factory()->create();
                return ["/api/v1/partes-trabajo/{$p->id}/lineas", LineaParteTrabajo::factory()->create(['parte_trabajo_id' => $p->id]), ['trabajador_id' => Trabajador::factory()->create()->id, 'horas' => 2], ['trabajador_id', 'horas'], LineaParteTrabajo::factory()->create()];
            })(),
        };
    }

    #[DataProvider('recursos')]
    public function test_listar_y_ver(string $nombre)
    {
        [$url, $registro] = $this->recurso($nombre);

        $this->getJson($url)->assertOk()->assertJsonStructure(['data' => [['id']], 'links', 'meta' => ['total']]);
        $this->getJson("{$url}/{$registro->id}")->assertOk()->assertJsonPath('data.id', $registro->id);
        $this->getJson("{$url}/999999")->assertNotFound();
    }

    #[DataProvider('recursos')]
    public function test_crear_responde_201_con_el_registro_completo(string $nombre)
    {
        [$url, , $datos] = $this->recurso($nombre);

        $r = $this->postJson($url, $datos)->assertCreated()->assertJsonStructure(['data' => ['id', 'created_at']]);
        foreach ($datos as $campo => $valor) {
            $this->assertEquals($valor, $r->json("data.{$campo}"), "data.{$campo}");
        }
    }

    #[DataProvider('recursos')]
    public function test_crear_valida_los_obligatorios(string $nombre)
    {
        [$url, , , $obligatorios] = $this->recurso($nombre);

        $r = $this->postJson($url, $nombre === 'obras' ? ['cliente_id' => 999999] : []);
        $r->assertUnprocessable()->assertJsonValidationErrors($nombre === 'obras' ? ['cliente_id'] : $obligatorios);
    }

    #[DataProvider('recursos')]
    public function test_modificar_y_borrar(string $nombre)
    {
        [$url, $registro, $datos] = $this->recurso($nombre);

        $this->putJson("{$url}/{$registro->id}", $datos)->assertOk()->assertJsonPath('data.id', $registro->id);
        $this->deleteJson("{$url}/{$registro->id}")->assertNoContent();
        $this->assertModelMissing($registro);
    }

    #[DataProvider('recursos')]
    public function test_un_hijo_de_otro_padre_da_404(string $nombre)
    {
        [$url, , , , $ajeno] = $this->recurso($nombre);
        if ($ajeno === null) {
            $this->assertTrue(true, 'Recurso sin padre');

            return;
        }

        $this->getJson("{$url}/{$ajeno->id}")->assertNotFound();
        $this->deleteJson("{$url}/{$ajeno->id}")->assertNotFound();
        $this->assertModelExists($ajeno);
    }

    public function test_la_ficha_de_gastos_solo_se_lista_ve_y_modifica()
    {
        $g = Obra::factory()->create()->gasto;

        $this->getJson('/api/v1/gastos')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson("/api/v1/gastos/{$g->id}")->assertOk();
        $this->putJson("/api/v1/gastos/{$g->id}", ['direccion_obra' => 'C/ Luna 2'])->assertOk()->assertJsonPath('data.direccion_obra', 'C/ Luna 2');
        $this->postJson('/api/v1/gastos', [])->assertMethodNotAllowed();
        $this->deleteJson("/api/v1/gastos/{$g->id}")->assertMethodNotAllowed();
    }

    public function test_las_reglas_de_negocio_responden_409()
    {
        $c = Cliente::factory()->create();
        Presupuesto::factory()->create(['cliente_id' => $c->id]);
        $obra = Obra::factory()->create();
        Ingreso::factory()->create(['obra_id' => $obra->id]);

        $this->deleteJson("/api/v1/clientes/{$c->id}")->assertConflict();
        $this->deleteJson("/api/v1/obras/{$obra->id}")->assertConflict();
    }

    public function test_las_rutas_extra_de_obras_y_recursos()
    {
        $c = Cliente::factory()->create();
        $obra = Obra::factory()->create(['cliente_id' => $c->id]);
        $libre = Presupuesto::factory()->create(['cliente_id' => $c->id]);
        $linea = LineaParteTrabajo::factory()->create(['parte_trabajo_id' => ParteTrabajo::factory()->create(['obra_id' => $obra->id])->id]);
        $pp = ParteProveedor::factory()->create(['obra_id' => $obra->id]);

        $this->getJson("/api/v1/obras/{$obra->id}/presupuestos-disponibles")->assertOk()->assertJsonPath('data.0.id', $libre->id);
        $this->postJson("/api/v1/obras/{$obra->id}/vincular-presupuesto", ['presupuesto_id' => $libre->id])->assertOk();
        $this->assertSame($obra->id, $libre->fresh()->obra_id);
        $this->getJson("/api/v1/obras/{$obra->id}/trabajadores")->assertOk()->assertJsonPath('data.0.id', $linea->trabajador_id);
        $this->getJson("/api/v1/obras/{$obra->id}/proveedores")->assertOk()->assertJsonPath('data.0.id', $pp->proveedor_id);
        $this->getJson("/api/v1/obras/{$obra->id}/especialistas")->assertOk();
        $this->getJson("/api/v1/trabajadores/{$linea->trabajador_id}/obras")->assertOk()->assertJsonPath('data.0.id', $obra->id);
        $this->getJson("/api/v1/trabajadores/{$linea->trabajador_id}/lineas-trabajo")->assertOk()->assertJsonPath('data.0.id', $linea->id);
        $this->getJson("/api/v1/proveedores/{$pp->proveedor_id}/obras")->assertOk()->assertJsonPath('data.0.id', $obra->id);
    }

    public function test_alta_de_lineas_por_lote()
    {
        $parte = ParteTrabajo::factory()->create();
        $ids = Trabajador::factory()->count(3)->create()->pluck('id')->all();

        $this->postJson("/api/v1/partes-trabajo/{$parte->id}/lineas/lote", ['trabajador_ids' => $ids, 'horas' => 8])->assertCreated()->assertJsonCount(3, 'data');
        $this->postJson("/api/v1/partes-trabajo/{$parte->id}/lineas/lote", ['trabajador_ids' => [$ids[0], $ids[0]], 'horas' => 8])->assertUnprocessable();
        $this->assertSame(3, $parte->lineas()->count());
    }

    public function test_estadisticas_y_calendario()
    {
        Obra::factory()->create();

        $this->getJson('/api/v1/estadisticas')->assertOk()->assertJsonStructure(['data' => ['periodo', 'resumen', 'top_obras', 'cobros_pendientes', 'top_trabajadores', 'evolucion_mensual']]);
        $this->getJson('/api/v1/estadisticas?periodo=nunca')->assertUnprocessable();
        $this->getJson('/api/v1/calendario?desde=2026-01-01&hasta=2026-12-31')->assertOk()->assertJsonStructure(['data']);
        $this->getJson('/api/v1/calendario')->assertUnprocessable();
    }

    public function test_la_api_valida_con_los_form_request_de_la_web()
    {
        $r = 'App\\Http\\Requests\\';
        $c = 'App\\Http\\Controllers\\API\\';
        $usos = [
            'ClienteController' => 'ClienteRequest', 'TrabajadorController' => 'TrabajadorRequest',
            'ProveedorController' => 'ProveedorRequest', 'EspecialistaController' => 'EspecialistaRequest',
            'PresupuestoController' => 'PresupuestoRequest', 'ObraController' => 'ObraRequest', 'GastoController' => 'GastoRequest',
            'FaltaController' => 'FaltaRequest', 'LineaPresupuestoController' => 'LineaPresupuestoRequest', 'IngresoController' => 'IngresoRequest',
            'ParteTrabajoController' => 'ParteTrabajoRequest', 'ParteProveedorController' => 'ParteProveedorRequest',
            'ParteEspecialistaController' => 'ParteEspecialistaRequest', 'LineaParteTrabajoController' => 'LineaParteTrabajoRequest',
        ];

        foreach ($usos as $controlador => $request) {
            $this->assertTrue(is_subclass_of($r . $request, $r . 'GestionRequest'), "$request debe heredar de GestionRequest");
            foreach (['store', 'update'] as $metodo) {
                if (! method_exists($c . $controlador, $metodo)) {
                    continue;   // Gastos no tiene store
                }
                $tipos = array_map(fn ($p) => (string) $p->getType(), (new ReflectionMethod($c . $controlador, $metodo))->getParameters());
                $this->assertContains($r . $request, $tipos, "$controlador::$metodo debe recibir $request");
            }
        }
        foreach (['VincularPresupuestoRequest', 'LoteLineasRequest'] as $request) {
            $this->assertTrue(is_subclass_of($r . $request, $r . 'GestionRequest'), "$request debe heredar de GestionRequest");
        }
    }

    public function test_en_la_api_se_modifica_solo_lo_que_se_envia_y_en_la_web_no()
    {
        $cliente = Cliente::factory()->create();

        $this->postJson('/api/v1/clientes', ['telefono' => '600000000'])->assertUnprocessable()->assertJsonValidationErrors(['nombre', 'direccion']);
        $this->putJson("/api/v1/clientes/{$cliente->id}", ['telefono' => '611111111'])->assertOk()->assertJsonPath('data.telefono', '611111111');
        $this->assertSame($cliente->nombre, $cliente->fresh()->nombre);

        $this->actingAs(User::factory()->create(['rol' => 'jefe']));
        $this->put(route('clientes.update', $cliente->id), ['telefono' => '622222222'])->assertSessionHasErrors(['nombre', 'direccion']);
        $this->assertSame('611111111', $cliente->fresh()->telefono);
    }
}
