<?php

namespace Tests\Feature\Escenario;

use App\Models\Cliente;
use App\Models\Gasto;
use App\Models\Trabajador;
use Tests\Feature\FeatureTestCase;

/**
 * Escenario de prueba completo: el ciclo de negocio de una obra de principio a fin
 * a través de la API, con los resultados esperados de cada paso.
 */
class EscenarioCompletoTest extends FeatureTestCase
{
    private Cliente $cliente;
    private Trabajador $ana;
    private Trabajador $luis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('jefe');
        $this->cliente = Cliente::factory()->create(['nombre' => 'Juan', 'apellidos' => 'Pérez López']);
        $this->ana = Trabajador::factory()->create(['coste_hora_estandar' => 20]);
        $this->luis = Trabajador::factory()->create(['coste_hora_estandar' => 20]);
    }

    private function obra(int $id): array
    {
        return $this->getJson("/api/v1/obras/{$id}")->assertOk()->json('data');
    }

    private function gasto(int $id): array
    {
        return $this->getJson("/api/v1/gastos/{$id}")->assertOk()->json('data');
    }

    /** Pasos 1 a 6: obra, presupuesto, aprobación, partes y costes externos. Devuelve [obra, presupuesto, parte]. */
    private function prepararObra(): array
    {
        // 1. Obra nueva: una y solo una ficha de Gastos, total 0, pendiente
        $obra = $this->postJson('/api/v1/obras', ['descripcion' => 'Reforma integral', 'cliente_id' => $this->cliente->id])->assertCreated()->json('data');
        $this->assertSame(1, Gasto::where('obra_id', $obra['id'])->count());
        $this->assertImporte(0, $obra['total']);
        $this->assertSame('pendiente', $obra['estado_pago']);

        // 2. Presupuesto no aprobado de 10 uds × 100 € con IVA 21 %
        $p = $this->postJson('/api/v1/presupuestos', ['cliente_id' => $this->cliente->id, 'obra_id' => $obra['id'], 'iva' => 21])->assertCreated()->json('data');
        $this->postJson("/api/v1/presupuestos/{$p['id']}/lineas", ['descripcion' => 'Partida', 'uds' => 10, 'precio' => 100])->assertCreated();
        $p = $this->getJson("/api/v1/presupuestos/{$p['id']}")->json('data');
        $this->assertImporte(1000, $p['base_imponible']);
        $this->assertImporte(210, $p['cuota_iva']);
        $this->assertImporte(1210, $p['total']);
        $this->assertImporte(0, $this->obra($obra['id'])['total']);

        // 3. Aprobar el presupuesto
        $this->putJson("/api/v1/presupuestos/{$p['id']}", ['estado' => 'aprobado'])->assertOk();
        $this->assertImporte(1210, $this->obra($obra['id'])['total']);

        // 4. Parte de trabajo con dos trabajadores de 20 €/h, 8 h cada uno
        $parte = $this->postJson("/api/v1/obras/{$obra['id']}/partes-trabajo", [
            'descripcion' => 'Demolición',
            'lineas' => [['trabajador_id' => $this->ana->id, 'horas' => 8], ['trabajador_id' => $this->luis->id, 'horas' => 8]],
        ])->assertCreated()->json('data');
        foreach ($parte['lineas'] as $linea) {
            $this->assertImporte(160, $linea['coste_subtotal']);
        }
        $o = $this->obra($obra['id']);
        $this->assertImporte(320, $o['coste_moo']);
        $this->assertImporte(16, $o['horas_totales']);
        foreach ([$this->ana, $this->luis] as $t) {
            $d = $this->getJson("/api/v1/trabajadores/{$t->id}")->json('data');
            $this->assertImporte(8, $d['horas_totales']);
            $this->assertImporte(160, $d['total_euros']);
        }

        // 5. Subir la tarifa de un trabajador no cambia las líneas ya registradas
        $this->putJson("/api/v1/trabajadores/{$this->ana->id}", ['coste_hora_estandar' => 25])->assertOk();
        $this->assertImporte(320, $this->obra($obra['id'])['coste_moo']);

        // 6. Parte de proveedor de 200 € y de especialista de 150 €
        $this->postJson("/api/v1/obras/{$obra['id']}/partes-proveedor", ['proveedor_id' => \App\Models\Proveedor::factory()->create()->id, 'importe' => 200])->assertCreated();
        $this->postJson("/api/v1/obras/{$obra['id']}/partes-especialista", ['especialista_id' => \App\Models\Especialista::factory()->create()->id, 'importe' => 150])->assertCreated();
        $this->assertImporte(670, $this->gasto($o['gasto_id'])['gastos']);

        return [$this->obra($obra['id']), $p, $parte];
    }

    public function test_ciclo_completo_de_una_obra()
    {
        [$obra] = $this->prepararObra();

        // 7. Ingreso de 500 €
        $this->postJson("/api/v1/obras/{$obra['id']}/ingresos", ['importe' => 500])->assertCreated();
        $g = $this->gasto($obra['gasto_id']);
        $o = $this->obra($obra['id']);
        $this->assertImporte(500, $g['ingresos']);
        $this->assertImporte(-170, $g['beneficio_real']);
        $this->assertImporte(710, $g['debe']);
        $this->assertImporte(540, $o['margen_proyectado']);
        $this->assertImporte(41.3, $o['porcentaje_cobrado']);
        $this->assertSame('parcial', $o['estado_pago']);
        $this->assertSame('rojo', $o['salud']);
        $this->assertSame('proceso', $g['estado']);

        // 8. Ingreso de 710 €
        $this->postJson("/api/v1/obras/{$obra['id']}/ingresos", ['importe' => 710])->assertCreated();
        $g = $this->gasto($obra['gasto_id']);
        $o = $this->obra($obra['id']);
        $this->assertImporte(1210, $g['ingresos']);
        $this->assertImporte(540, $g['beneficio_real']);
        $this->assertImporte(0, $g['debe']);
        $this->assertImporte(100, $o['porcentaje_cobrado']);
        $this->assertSame('pagado', $o['estado_pago']);
        $this->assertSame('verde', $o['salud']);
        $this->assertSame('finalizado', $g['estado']);

        // 11. Una obra con ingresos no se puede borrar
        $this->deleteJson("/api/v1/obras/{$obra['id']}")->assertStatus(409);
    }

    public function test_validaciones_del_escenario()
    {
        [$obra, $p, $parte] = $this->prepararObra();

        // 9. Horas fuera de rango, importe 0, IVA 150 y falta con fin anterior al inicio
        $this->postJson("/api/v1/partes-trabajo/{$parte['id']}/lineas", ['trabajador_id' => $this->ana->id, 'horas' => 25])->assertUnprocessable();
        $this->postJson("/api/v1/partes-trabajo/{$parte['id']}/lineas", ['trabajador_id' => $this->ana->id, 'horas' => -1])->assertUnprocessable();
        $this->postJson("/api/v1/obras/{$obra['id']}/partes-proveedor", ['proveedor_id' => \App\Models\Proveedor::factory()->create()->id, 'importe' => 0])->assertUnprocessable();
        $this->putJson("/api/v1/presupuestos/{$p['id']}", ['iva' => 150])->assertUnprocessable();
        $this->postJson("/api/v1/trabajadores/{$this->ana->id}/faltas", ['fecha_inicio' => '2026-05-10', 'fecha_fin' => '2026-05-01'])->assertUnprocessable();

        // 10. "Juan Pérez" encuentra al cliente aunque nombre y apellidos estén en columnas distintas
        $this->assertSame([$this->cliente->id], array_column($this->getJson('/api/v1/clientes?search=' . urlencode('Juan Pérez'))->json('data'), 'id'));

        // 12. La ficha de Gastos no se borra directamente
        $this->deleteJson("/api/v1/gastos/{$obra['gasto_id']}")->assertMethodNotAllowed();

        // 13. Presupuesto o ingreso de otro cliente en la obra
        $otro = Cliente::factory()->create();
        $this->postJson('/api/v1/presupuestos', ['cliente_id' => $otro->id, 'obra_id' => $obra['id']])->assertUnprocessable()->assertJsonValidationErrors('cliente_id');
        $this->postJson("/api/v1/obras/{$obra['id']}/ingresos", ['importe' => 10, 'cliente_id' => $otro->id])->assertUnprocessable()->assertJsonValidationErrors('cliente_id');
    }

    public function test_borrar_una_obra_sin_cobros()
    {
        [$obra, $p] = $this->prepararObra();

        // 11b. Sin ingresos se borra: desaparecen ficha y partes, el presupuesto queda sin obra y los trabajadores vuelven a 0
        $this->deleteJson("/api/v1/obras/{$obra['id']}")->assertNoContent();

        $this->assertDatabaseCount('gastos', 0);
        $this->assertDatabaseCount('partes_trabajo', 0);
        $this->assertDatabaseCount('partes_proveedor', 0);
        $this->assertDatabaseCount('partes_especialista', 0);
        $this->assertNull($this->getJson("/api/v1/presupuestos/{$p['id']}")->json('data.obra_id'));
        foreach ([$this->ana, $this->luis] as $t) {
            $d = $this->getJson("/api/v1/trabajadores/{$t->id}")->json('data');
            $this->assertImporte(0, $d['horas_totales']);
            $this->assertImporte(0, $d['total_euros']);
        }
    }
}
