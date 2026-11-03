<?php

namespace Tests\Feature;

use App\Models\Ingreso;
use App\Models\LineaParteTrabajo;
use App\Models\LineaPresupuesto;
use App\Models\Obra;
use App\Models\ParteEspecialista;
use App\Models\ParteProveedor;
use App\Models\ParteTrabajo;
use App\Models\Presupuesto;
use App\Services\ResumenFinanciero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RA6_7_CalculosTest extends TestCase
{
    use RefreshDatabase;

    private const CLAVES = ['total', 'horas_totales', 'coste_moo', 'coste_proveedores', 'coste_especialistas', 'gastos', 'ingresos',
        'beneficio', 'debe', 'margen_proyectado', 'porcentaje_cobrado', 'estado_pago', 'salud', 'coste_hora', 'estado'];

    private function resumen(Obra $obra): array
    {
        return app(ResumenFinanciero::class)->obra($obra->fresh());
    }

    private function obraConCostes(): Obra
    {
        $obra = Obra::factory()->create();
        $p = Presupuesto::factory()->create(['cliente_id' => $obra->cliente_id, 'obra_id' => $obra->id, 'estado' => 'aprobado', 'iva' => 21]);
        LineaPresupuesto::factory()->create(['presupuesto_id' => $p->id, 'uds' => 10, 'precio' => 100]);
        Presupuesto::factory()->create(['cliente_id' => $obra->cliente_id, 'obra_id' => $obra->id, 'estado' => 'no_aprobado']);
        $parte = ParteTrabajo::factory()->create(['obra_id' => $obra->id]);
        LineaParteTrabajo::factory()->count(2)->create(['parte_trabajo_id' => $parte->id, 'horas' => 8, 'coste_hora' => 20]);
        ParteProveedor::factory()->create(['obra_id' => $obra->id, 'importe' => 200]);
        ParteEspecialista::factory()->create(['obra_id' => $obra->id, 'importe' => 150]);

        return $obra;
    }

    public function test_devuelve_todos_los_indicadores()
    {
        $this->assertEqualsCanonicalizing(self::CLAVES, array_keys($this->resumen(Obra::factory()->create())));
    }

    public function test_obra_recien_creada()
    {
        $r = $this->resumen(Obra::factory()->create());

        $this->assertEquals(0, $r['total']);
        $this->assertSame('pendiente', $r['estado_pago']);
        $this->assertSame('ambar', $r['salud']);
        $this->assertSame('proceso', $r['estado']);
        $this->assertEquals(0, $r['porcentaje_cobrado']);
        $this->assertEquals(0, $r['coste_hora']);
    }

    public function test_costes_con_cobro_parcial()
    {
        $obra = $this->obraConCostes();
        Ingreso::factory()->create(['obra_id' => $obra->id, 'importe' => 500]);

        $r = $this->resumen($obra);

        $this->assertEqualsWithDelta(1210, $r['total'], 0.001);
        $this->assertEqualsWithDelta(16, $r['horas_totales'], 0.001);
        $this->assertEqualsWithDelta(320, $r['coste_moo'], 0.001);
        $this->assertEqualsWithDelta(670, $r['gastos'], 0.001);
        $this->assertEqualsWithDelta(-170, $r['beneficio'], 0.001);
        $this->assertEqualsWithDelta(710, $r['debe'], 0.001);
        $this->assertEqualsWithDelta(540, $r['margen_proyectado'], 0.001);
        $this->assertEqualsWithDelta(41.3, $r['porcentaje_cobrado'], 0.001);
        $this->assertEqualsWithDelta(20, $r['coste_hora'], 0.001);
        $this->assertSame('parcial', $r['estado_pago']);
        $this->assertSame('rojo', $r['salud']);
        $this->assertSame('proceso', $r['estado']);
    }

    public function test_obra_cobrada_del_todo()
    {
        $obra = $this->obraConCostes();
        Ingreso::factory()->create(['obra_id' => $obra->id, 'importe' => 1210]);

        $r = $this->resumen($obra);

        $this->assertEqualsWithDelta(540, $r['beneficio'], 0.001);
        $this->assertEqualsWithDelta(0, $r['debe'], 0.001);
        $this->assertEqualsWithDelta(100, $r['porcentaje_cobrado'], 0.001);
        $this->assertSame('pagado', $r['estado_pago']);
        $this->assertSame('verde', $r['salud']);
        $this->assertSame('finalizado', $r['estado']);
    }

    public function test_cobrar_de_mas_deja_debe_negativo_y_porcentaje_acotado()
    {
        $obra = $this->obraConCostes();
        Ingreso::factory()->create(['obra_id' => $obra->id, 'importe' => 1500]);

        $r = $this->resumen($obra);

        $this->assertEqualsWithDelta(-290, $r['debe'], 0.001);
        $this->assertEqualsWithDelta(100, $r['porcentaje_cobrado'], 0.001);
    }

    public function test_obra_sin_total_con_ingresos_figura_como_pagada()
    {
        $obra = Obra::factory()->create();
        Ingreso::factory()->create(['obra_id' => $obra->id, 'importe' => 50]);

        $this->assertSame('pagado', $this->resumen($obra)['estado_pago']);
    }
}
