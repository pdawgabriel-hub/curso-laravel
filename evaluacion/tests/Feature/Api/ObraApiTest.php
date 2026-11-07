<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\Gasto;
use App\Models\Ingreso;
use App\Models\LineaParteTrabajo;
use App\Models\LineaPresupuesto;
use App\Models\Obra;
use App\Models\ParteEspecialista;
use App\Models\ParteProveedor;
use App\Models\ParteTrabajo;
use App\Models\Presupuesto;
use App\Models\Trabajador;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\FeatureTestCase;

class ObraApiTest extends FeatureTestCase
{
    private const CAMPOS = ['id', 'codigo', 'descripcion', 'cliente_id', 'total', 'horas_totales', 'coste_moo', 'estado_pago',
        'gastos_totales', 'beneficio', 'pendiente_cobro', 'margen_proyectado', 'porcentaje_cobrado', 'salud', 'gasto_id', 'created_at', 'updated_at'];

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('jefe');
        $this->cliente = Cliente::factory()->create();
    }

    private function presupuesto(Obra $obra, float $precio, string $estado = 'aprobado'): Presupuesto
    {
        $p = Presupuesto::factory()->create(['cliente_id' => $obra->cliente_id, 'obra_id' => $obra->id, 'estado' => $estado, 'iva' => 21]);
        LineaPresupuesto::factory()->create(['presupuesto_id' => $p->id, 'uds' => 1, 'precio' => $precio]);

        return $p;
    }

    public function test_puede_listar_obras()
    {
        Obra::factory()->count(3)->create();

        $this->assertListado($this->getJson('/api/v1/obras'), self::CAMPOS, 3);
    }

    public function test_crear_una_obra_crea_su_ficha_de_gastos()
    {
        $response = $this->postJson('/api/v1/obras', ['descripcion' => 'Reforma de cocina', 'cliente_id' => $this->cliente->id]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => self::CAMPOS])
            ->assertJsonPath('data.codigo', 'OBR0001')
            ->assertJsonPath('data.estado_pago', 'pendiente');
        $this->assertImporte(0, $response->json('data.total'));
        $obraId = $response->json('data.id');
        $this->assertSame(1, Gasto::where('obra_id', $obraId)->count());
        $this->assertSame(Gasto::where('obra_id', $obraId)->value('id'), $response->json('data.gasto_id'));
        $this->assertDatabaseHas('gastos', ['obra_id' => $obraId, 'codigo' => 'GAS0001']);
    }

    public function test_valida_el_cliente()
    {
        $this->postJson('/api/v1/obras', ['cliente_id' => 999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cliente_id');
    }

    public function test_el_total_solo_suma_presupuestos_aprobados()
    {
        $obra = Obra::factory()->create(['cliente_id' => $this->cliente->id]);
        $this->presupuesto($obra, 1000, 'aprobado');
        $this->presupuesto($obra, 500, 'no_aprobado');

        $this->assertImporte(1210, $this->getJson("/api/v1/obras/{$obra->id}")->json('data.total'));
    }

    public function test_el_total_se_recalcula_al_aprobar_un_presupuesto()
    {
        $obra = Obra::factory()->create(['cliente_id' => $this->cliente->id]);
        $p = $this->presupuesto($obra, 1000, 'no_aprobado');
        $this->assertImporte(0, $this->getJson("/api/v1/obras/{$obra->id}")->json('data.total'));

        $this->putJson("/api/v1/presupuestos/{$p->id}", ['estado' => 'aprobado'])->assertOk();

        $this->assertImporte(1210, $this->getJson("/api/v1/obras/{$obra->id}")->json('data.total'));
    }

    public function test_horas_y_coste_de_mano_de_obra()
    {
        $obra = Obra::factory()->create();
        $parte = ParteTrabajo::factory()->create(['obra_id' => $obra->id]);
        LineaParteTrabajo::factory()->count(2)->create(['parte_trabajo_id' => $parte->id, 'horas' => 8, 'coste_hora' => 20]);

        $response = $this->getJson("/api/v1/obras/{$obra->id}");

        $this->assertImporte(16, $response->json('data.horas_totales'));
        $this->assertImporte(320, $response->json('data.coste_moo'));
    }

    public function test_indicadores_financieros_y_salud()
    {
        $obra = Obra::factory()->create(['cliente_id' => $this->cliente->id]);
        $this->presupuesto($obra, 1000);
        ParteProveedor::factory()->create(['obra_id' => $obra->id, 'importe' => 600]);
        Ingreso::factory()->create(['obra_id' => $obra->id, 'importe' => 400]);

        $d = $this->getJson("/api/v1/obras/{$obra->id}")->json('data');

        $this->assertImporte(600, $d['gastos_totales']);
        $this->assertImporte(-200, $d['beneficio']);
        $this->assertImporte(810, $d['pendiente_cobro']);
        $this->assertImporte(610, $d['margen_proyectado']);
        $this->assertImporte(33.1, $d['porcentaje_cobrado']);
        $this->assertSame('parcial', $d['estado_pago']);
        $this->assertSame('rojo', $d['salud']);
    }

    public function test_obra_pagada_sin_perdidas_esta_en_verde()
    {
        $obra = Obra::factory()->create(['cliente_id' => $this->cliente->id]);
        $this->presupuesto($obra, 1000);
        Ingreso::factory()->create(['obra_id' => $obra->id, 'importe' => 1210]);

        $d = $this->getJson("/api/v1/obras/{$obra->id}")->json('data');

        $this->assertSame('pagado', $d['estado_pago']);
        $this->assertSame('verde', $d['salud']);
        $this->assertImporte(100, $d['porcentaje_cobrado']);
    }

    public function test_obra_en_curso_sin_perdidas_esta_en_ambar()
    {
        $obra = Obra::factory()->create(['cliente_id' => $this->cliente->id]);
        $this->presupuesto($obra, 1000);

        $this->assertSame('ambar', $this->getJson("/api/v1/obras/{$obra->id}")->json('data.salud'));
    }

    public function test_no_se_cambia_el_cliente_si_tiene_presupuestos_de_otro()
    {
        $obra = Obra::factory()->create(['cliente_id' => $this->cliente->id]);
        $this->presupuesto($obra, 100);
        $otro = Cliente::factory()->create();

        $this->putJson("/api/v1/obras/{$obra->id}", ['cliente_id' => $otro->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cliente_id');
        $this->putJson("/api/v1/obras/{$obra->id}", ['descripcion' => 'Nueva descripción', 'cliente_id' => $this->cliente->id])
            ->assertOk()
            ->assertJsonPath('data.descripcion', 'Nueva descripción');
    }

    public function test_borrar_obra_borra_lo_suyo_y_desvincula_presupuestos()
    {
        Storage::fake();
        $obra = Obra::factory()->create(['cliente_id' => $this->cliente->id]);
        $p = $this->presupuesto($obra, 100);
        $t = Trabajador::factory()->create();
        $parte = ParteTrabajo::factory()->create(['obra_id' => $obra->id]);
        LineaParteTrabajo::factory()->create(['parte_trabajo_id' => $parte->id, 'trabajador_id' => $t->id, 'horas' => 5]);
        $pp = ParteProveedor::factory()->create(['obra_id' => $obra->id]);
        ParteEspecialista::factory()->create(['obra_id' => $obra->id]);
        $ruta = $this->postJson("/api/v1/partes-proveedor/{$pp->id}/archivos", ['archivo' => UploadedFile::fake()->create('factura.pdf', 10, 'application/pdf')])
            ->assertCreated()->json('data.id');
        $fichero = \App\Models\Archivo::find($ruta)->ruta;
        Storage::assertExists($fichero);

        $this->deleteJson("/api/v1/obras/{$obra->id}")->assertNoContent();

        $this->assertDatabaseMissing('obras', ['id' => $obra->id]);
        $this->assertDatabaseMissing('gastos', ['obra_id' => $obra->id]);
        $this->assertDatabaseCount('partes_trabajo', 0);
        $this->assertDatabaseCount('lineas_parte_trabajo', 0);
        $this->assertDatabaseCount('partes_proveedor', 0);
        $this->assertDatabaseCount('partes_especialista', 0);
        $this->assertDatabaseCount('archivos', 0);
        Storage::assertMissing($fichero);
        $this->assertDatabaseHas('presupuestos', ['id' => $p->id, 'obra_id' => null]);
        $this->assertImporte(0, $this->getJson("/api/v1/trabajadores/{$t->id}")->json('data.horas_totales'));
    }

    public function test_no_se_borra_una_obra_con_ingresos()
    {
        $obra = Obra::factory()->create();
        $parte = ParteTrabajo::factory()->create(['obra_id' => $obra->id]);
        Ingreso::factory()->create(['obra_id' => $obra->id]);

        $this->deleteJson("/api/v1/obras/{$obra->id}")->assertStatus(409);

        $this->assertDatabaseHas('obras', ['id' => $obra->id]);
        $this->assertDatabaseHas('partes_trabajo', ['id' => $parte->id]);
        $this->assertDatabaseHas('gastos', ['obra_id' => $obra->id]);
    }

    public function test_presupuestos_disponibles_para_vincular()
    {
        $obra = Obra::factory()->create(['cliente_id' => $this->cliente->id]);
        $libre = Presupuesto::factory()->create(['cliente_id' => $this->cliente->id]);
        Presupuesto::factory()->create();                                    // de otro cliente
        $this->presupuesto(Obra::factory()->create(['cliente_id' => $this->cliente->id]), 100); // ya vinculado

        $response = $this->getJson("/api/v1/obras/{$obra->id}/presupuestos-disponibles");

        $response->assertOk();
        $this->assertSame([$libre->id], array_column($response->json('data'), 'id'));
    }

    public function test_vincula_un_presupuesto_existente()
    {
        $obra = Obra::factory()->create(['cliente_id' => $this->cliente->id]);
        $libre = Presupuesto::factory()->create(['cliente_id' => $this->cliente->id]);

        $this->postJson("/api/v1/obras/{$obra->id}/vincular-presupuesto", ['presupuesto_id' => $libre->id])
            ->assertOk()
            ->assertJsonPath('data.obra_id', $obra->id);
        $this->assertDatabaseHas('presupuestos', ['id' => $libre->id, 'obra_id' => $obra->id]);
    }

    public function test_no_vincula_presupuestos_de_otro_cliente_ni_ya_vinculados()
    {
        $obra = Obra::factory()->create(['cliente_id' => $this->cliente->id]);
        $ajeno = Presupuesto::factory()->create();
        $vinculado = $this->presupuesto(Obra::factory()->create(['cliente_id' => $this->cliente->id]), 100);

        foreach ([$ajeno, $vinculado] as $p) {
            $this->postJson("/api/v1/obras/{$obra->id}/vincular-presupuesto", ['presupuesto_id' => $p->id])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('presupuesto_id');
        }
    }

    public function test_participantes_de_la_obra_se_deducen_de_sus_partes()
    {
        $obra = Obra::factory()->create();
        $parte = ParteTrabajo::factory()->create(['obra_id' => $obra->id]);
        $t = Trabajador::factory()->create();
        LineaParteTrabajo::factory()->count(2)->create(['parte_trabajo_id' => $parte->id, 'trabajador_id' => $t->id]);
        $pp = ParteProveedor::factory()->create(['obra_id' => $obra->id]);
        $pe = ParteEspecialista::factory()->create(['obra_id' => $obra->id]);
        Trabajador::factory()->create();

        $this->assertSame([$t->id], array_column($this->getJson("/api/v1/obras/{$obra->id}/trabajadores")->json('data'), 'id'));
        $this->assertSame([$pp->proveedor_id], array_column($this->getJson("/api/v1/obras/{$obra->id}/proveedores")->json('data'), 'id'));
        $this->assertSame([$pe->especialista_id], array_column($this->getJson("/api/v1/obras/{$obra->id}/especialistas")->json('data'), 'id'));
    }

    public function test_busca_por_descripcion()
    {
        Obra::factory()->create(['descripcion' => 'Reforma integral de baño']);
        Obra::factory()->create(['descripcion' => 'Pintura de fachada']);

        $this->assertCount(1, $this->getJson('/api/v1/obras?search=baño')->json('data'));
    }

    public function test_usuario_sin_rol_no_puede_listar_obras()
    {
        $x = Obra::factory()->create(['descripcion' => 'Original']);
        $this->actuarComo(null);

        $this->getJson("/api/v1/obras")->assertForbidden();
    }

    public function test_usuario_sin_rol_no_puede_crear_obras()
    {
        $x = Obra::factory()->create(['descripcion' => 'Original']);
        $antes = \DB::table('obras')->count();
        $this->actuarComo(null);

        $this->postJson("/api/v1/obras", ['descripcion' => 'A'])->assertForbidden();

        $this->assertDatabaseCount('obras', $antes);
    }

    public function test_usuario_sin_rol_no_puede_modificar_obras()
    {
        $x = Obra::factory()->create(['descripcion' => 'Original']);
        $this->actuarComo(null);

        $this->putJson("/api/v1/obras/{$x->id}", ['descripcion' => 'Cambiada'])->assertForbidden();

        $this->assertDatabaseHas('obras', ['id' => $x->id, 'descripcion' => 'Original']);
    }

    public function test_usuario_sin_rol_no_puede_borrar_obras()
    {
        $x = Obra::factory()->create(['descripcion' => 'Original']);
        $this->actuarComo(null);

        $this->deleteJson("/api/v1/obras/{$x->id}")->assertForbidden();

        $this->assertDatabaseHas('obras', ['id' => $x->id]);
    }

    public function test_pagina_obras()
    {
        Obra::factory()->count(25)->create();

        $this->assertPaginado($this->getJson("/api/v1/obras?per_page=10"), 10, 25);
    }

    public function test_filtra_por_cliente()
    {
        $propia = Obra::factory()->create(['cliente_id' => $this->cliente->id]);
        Obra::factory()->create();

        $response = $this->getJson("/api/v1/obras?cliente_id={$this->cliente->id}");

        $this->assertSame([$propia->id], array_column($response->json('data'), 'id'));
    }

    public function test_usuario_sin_rol_no_puede_vincular_un_presupuesto()
    {
        $obra = Obra::factory()->create(['cliente_id' => $this->cliente->id]);
        $libre = Presupuesto::factory()->create(['cliente_id' => $this->cliente->id]);
        $this->actuarComo(null);

        $this->postJson("/api/v1/obras/{$obra->id}/vincular-presupuesto", ['presupuesto_id' => $libre->id])->assertForbidden();

        $this->assertDatabaseHas('presupuestos', ['id' => $libre->id, 'obra_id' => null]);
    }
}
