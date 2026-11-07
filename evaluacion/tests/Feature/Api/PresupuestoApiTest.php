<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\Especialista;
use App\Models\LineaPresupuesto;
use App\Models\Obra;
use App\Models\Presupuesto;
use App\Models\Proveedor;
use Tests\Feature\FeatureTestCase;

class PresupuestoApiTest extends FeatureTestCase
{
    private const CAMPOS = ['id', 'codigo', 'fecha', 'anio', 'cliente_id', 'obra_id', 'nombre_cliente', 'nif', 'direccion', 'telefono',
        'iva', 'estado', 'base_imponible', 'cuota_iva', 'total', 'proveedor_ids', 'especialista_ids', 'created_at', 'updated_at'];

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('administrativa');
        $this->cliente = Cliente::factory()->create(['nombre' => 'Juan', 'apellidos' => 'Pérez', 'nif' => '12345678Z']);
    }

    public function test_puede_listar_presupuestos()
    {
        Presupuesto::factory()->count(3)->create();

        $this->assertListado($this->getJson('/api/v1/presupuestos'), self::CAMPOS, 3);
    }

    public function test_crea_un_presupuesto_copiando_los_datos_del_cliente()
    {
        $response = $this->postJson('/api/v1/presupuestos', ['cliente_id' => $this->cliente->id, 'fecha' => '2026-05-10']);

        $response->assertCreated()
            ->assertJsonStructure(['data' => self::CAMPOS])
            ->assertJsonPath('data.codigo', 'PREP0001')
            ->assertJsonPath('data.nombre_cliente', 'Juan Pérez')
            ->assertJsonPath('data.nif', '12345678Z')
            ->assertJsonPath('data.direccion', $this->cliente->direccion)
            ->assertJsonPath('data.estado', 'no_aprobado')
            ->assertJsonPath('data.fecha', '2026-05-10')
            ->assertJsonPath('data.anio', 2026);
        $this->assertImporte(21, $response->json('data.iva'));
    }

    public function test_los_datos_copiados_no_cambian_si_cambia_el_cliente()
    {
        $id = $this->postJson('/api/v1/presupuestos', ['cliente_id' => $this->cliente->id])->json('data.id');

        $this->cliente->update(['nombre' => 'Otro', 'nif' => '00000000T']);

        $this->getJson("/api/v1/presupuestos/{$id}")
            ->assertJsonPath('data.nombre_cliente', 'Juan Pérez')
            ->assertJsonPath('data.nif', '12345678Z');
    }

    public function test_sin_fecha_toma_la_de_hoy()
    {
        $this->postJson('/api/v1/presupuestos', ['cliente_id' => $this->cliente->id])
            ->assertJsonPath('data.fecha', now()->toDateString());
    }

    public function test_calcula_base_cuota_y_total()
    {
        $p = Presupuesto::factory()->create(['cliente_id' => $this->cliente->id, 'iva' => 21]);
        LineaPresupuesto::factory()->create(['presupuesto_id' => $p->id, 'uds' => 10, 'precio' => 100]);

        $response = $this->getJson("/api/v1/presupuestos/{$p->id}");

        $this->assertImporte(1000, $response->json('data.base_imponible'));
        $this->assertImporte(210, $response->json('data.cuota_iva'));
        $this->assertImporte(1210, $response->json('data.total'));
    }

    public function test_redondea_la_cuota_a_dos_decimales()
    {
        $p = Presupuesto::factory()->create(['cliente_id' => $this->cliente->id, 'iva' => 21]);
        LineaPresupuesto::factory()->create(['presupuesto_id' => $p->id, 'uds' => 3, 'precio' => 33.33]);

        $response = $this->getJson("/api/v1/presupuestos/{$p->id}");

        $this->assertImporte(99.99, $response->json('data.base_imponible'));
        $this->assertImporte(21.00, $response->json('data.cuota_iva'));
        $this->assertImporte(120.99, $response->json('data.total'));
    }

    public function test_valida_cliente_iva_y_estado()
    {
        $this->postJson('/api/v1/presupuestos', ['iva' => 150, 'estado' => 'quiza'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cliente_id', 'iva', 'estado']);
        $this->postJson('/api/v1/presupuestos', ['cliente_id' => 999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cliente_id');
    }

    public function test_el_cliente_debe_ser_el_de_su_obra()
    {
        $obra = Obra::factory()->create();

        $this->postJson('/api/v1/presupuestos', ['cliente_id' => $this->cliente->id, 'obra_id' => $obra->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cliente_id');
        $this->postJson('/api/v1/presupuestos', ['cliente_id' => $obra->cliente_id, 'obra_id' => $obra->id])
            ->assertCreated();
    }

    public function test_vincula_proveedores_y_especialistas()
    {
        $prov = Proveedor::factory()->count(2)->create();
        $esp = Especialista::factory()->create();

        $response = $this->postJson('/api/v1/presupuestos', [
            'cliente_id' => $this->cliente->id,
            'proveedor_ids' => $prov->pluck('id')->all(),
            'especialista_ids' => [$esp->id],
        ]);

        $response->assertCreated();
        $this->assertEqualsCanonicalizing($prov->pluck('id')->all(), $response->json('data.proveedor_ids'));
        $this->assertSame([$esp->id], $response->json('data.especialista_ids'));
        $this->assertDatabaseHas('presupuesto_proveedor', ['proveedor_id' => $prov[0]->id, 'presupuesto_id' => $response->json('data.id')]);
        $this->assertDatabaseHas('especialista_presupuesto', ['especialista_id' => $esp->id]);
    }

    public function test_actualiza_estado_y_proveedores()
    {
        $p = Presupuesto::factory()->create(['cliente_id' => $this->cliente->id]);
        $prov = Proveedor::factory()->create();
        $p->proveedores()->attach(Proveedor::factory()->create());

        $this->putJson("/api/v1/presupuestos/{$p->id}", ['estado' => 'aprobado', 'proveedor_ids' => [$prov->id]])
            ->assertOk()
            ->assertJsonPath('data.estado', 'aprobado')
            ->assertJsonPath('data.proveedor_ids', [$prov->id]);
    }

    public function test_filtra_por_estado_y_por_obra()
    {
        $obra = Obra::factory()->create();
        Presupuesto::factory()->create(['estado' => 'aprobado', 'obra_id' => $obra->id, 'cliente_id' => $obra->cliente_id]);
        Presupuesto::factory()->count(2)->create(['estado' => 'no_aprobado']);

        $this->assertCount(1, $this->getJson('/api/v1/presupuestos?estado=aprobado')->json('data'));
        $this->assertCount(1, $this->getJson("/api/v1/presupuestos?obra_id={$obra->id}")->json('data'));
    }

    public function test_busca_por_nombre_de_cliente()
    {
        $this->postJson('/api/v1/presupuestos', ['cliente_id' => $this->cliente->id]);
        Presupuesto::factory()->create(['nombre_cliente' => 'Zoe Ramos']);

        $this->assertCount(1, $this->getJson('/api/v1/presupuestos?search=Juan')->json('data'));
    }

    public function test_borrar_un_presupuesto_borra_sus_lineas()
    {
        $p = Presupuesto::factory()->create();
        $linea = LineaPresupuesto::factory()->create(['presupuesto_id' => $p->id]);

        $this->deleteJson("/api/v1/presupuestos/{$p->id}")->assertNoContent();
        $this->assertDatabaseMissing('presupuestos', ['id' => $p->id]);
        $this->assertDatabaseMissing('lineas_presupuesto', ['id' => $linea->id]);
    }

    public function test_usuario_sin_rol_no_puede_ver_presupuestos()
    {
        $p = Presupuesto::factory()->create();
        $this->actuarComo(null);

        $this->getJson("/api/v1/presupuestos/{$p->id}")->assertForbidden();
    }

    public function test_usuario_sin_rol_no_puede_listar_presupuestos()
    {
        $x = Presupuesto::factory()->create(['estado' => 'no_aprobado']);
        $this->actuarComo(null);

        $this->getJson("/api/v1/presupuestos")->assertForbidden();
    }

    public function test_usuario_sin_rol_no_puede_crear_presupuestos()
    {
        $x = Presupuesto::factory()->create(['estado' => 'no_aprobado']);
        $antes = \DB::table('presupuestos')->count();
        $this->actuarComo(null);

        $this->postJson("/api/v1/presupuestos", ['cliente_id' => $this->cliente->id])->assertForbidden();

        $this->assertDatabaseCount('presupuestos', $antes);
    }

    public function test_usuario_sin_rol_no_puede_modificar_presupuestos()
    {
        $x = Presupuesto::factory()->create(['estado' => 'no_aprobado']);
        $this->actuarComo(null);

        $this->putJson("/api/v1/presupuestos/{$x->id}", ['estado' => 'aprobado'])->assertForbidden();

        $this->assertDatabaseHas('presupuestos', ['id' => $x->id, 'estado' => 'no_aprobado']);
    }

    public function test_usuario_sin_rol_no_puede_borrar_presupuestos()
    {
        $x = Presupuesto::factory()->create(['estado' => 'no_aprobado']);
        $this->actuarComo(null);

        $this->deleteJson("/api/v1/presupuestos/{$x->id}")->assertForbidden();

        $this->assertDatabaseHas('presupuestos', ['id' => $x->id]);
    }

    public function test_pagina_presupuestos()
    {
        Presupuesto::factory()->count(25)->create();

        $this->assertPaginado($this->getJson("/api/v1/presupuestos?per_page=10"), 10, 25);
    }

    public function test_acepta_los_estados_validos()
    {
        foreach (['aprobado', 'no_aprobado'] as $estado) {
            $this->postJson('/api/v1/presupuestos', ['cliente_id' => $this->cliente->id, 'estado' => $estado])
                ->assertCreated()
                ->assertJsonPath('data.estado', $estado);
        }
    }

    public function test_filtra_por_cliente()
    {
        $propio = Presupuesto::factory()->create(['cliente_id' => $this->cliente->id]);
        Presupuesto::factory()->create();

        $response = $this->getJson("/api/v1/presupuestos?cliente_id={$this->cliente->id}");

        $this->assertSame([$propio->id], array_column($response->json('data'), 'id'));
    }
}
