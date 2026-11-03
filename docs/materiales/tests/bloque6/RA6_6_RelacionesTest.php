<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Especialista;
use App\Models\LineaParteTrabajo;
use App\Models\Obra;
use App\Models\ParteEspecialista;
use App\Models\ParteProveedor;
use App\Models\ParteTrabajo;
use App\Models\Presupuesto;
use App\Models\Proveedor;
use App\Models\Trabajador;
use App\Services\ObraService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RA6_6_RelacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_uno_a_uno_obra_y_ficha_de_gastos()
    {
        $obra = app(ObraService::class)->crear(['descripcion' => 'Cocina']);

        $this->assertNotNull($obra->gasto);
        $this->assertTrue($obra->gasto->obra->is($obra));
    }

    public function test_uno_a_muchos_desde_el_cliente()
    {
        $c = Cliente::factory()->create();
        Obra::factory()->count(2)->create(['cliente_id' => $c->id]);
        Presupuesto::factory()->count(3)->create(['cliente_id' => $c->id]);

        $this->assertCount(2, $c->obras);
        $this->assertCount(3, $c->presupuestos);
        $this->assertTrue($c->obras->first()->cliente->is($c));
    }

    public function test_muchos_a_muchos_presupuesto_proveedores_y_especialistas()
    {
        $p = Presupuesto::factory()->create();
        $prov = Proveedor::factory()->create();
        $esp = Especialista::factory()->create();

        $p->proveedores()->attach($prov);
        $esp->presupuestos()->attach($p);

        $this->assertTrue($prov->presupuestos->contains($p), 'La relación se ve desde el proveedor');
        $this->assertTrue($p->especialistas->contains($esp), 'La relación se ve desde el presupuesto');
    }

    public function test_lineas_de_trabajo_de_la_obra_a_traves_de_sus_partes()
    {
        $obra = Obra::factory()->create();
        $parte = ParteTrabajo::factory()->create(['obra_id' => $obra->id]);
        LineaParteTrabajo::factory()->count(3)->create(['parte_trabajo_id' => $parte->id, 'horas' => 2]);
        LineaParteTrabajo::factory()->create(['horas' => 8]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasManyThrough::class, $obra->lineasTrabajo());
        $this->assertEquals(6, $obra->lineasTrabajo()->sum('horas'));
    }

    public function test_participantes_deducidos_de_los_partes()
    {
        $obra = Obra::factory()->create();
        $t = Trabajador::factory()->create();
        $parte = ParteTrabajo::factory()->create(['obra_id' => $obra->id]);
        LineaParteTrabajo::factory()->count(2)->create(['parte_trabajo_id' => $parte->id, 'trabajador_id' => $t->id]);
        $pp = ParteProveedor::factory()->create(['obra_id' => $obra->id]);
        $pe = ParteEspecialista::factory()->create(['obra_id' => $obra->id]);
        Trabajador::factory()->create();

        $this->assertEquals([$t->id], $obra->trabajadoresParticipantes()->pluck('id')->all());
        $this->assertEquals([$pp->proveedor_id], $obra->proveedoresParticipantes()->pluck('id')->all());
        $this->assertEquals([$pe->especialista_id], $obra->especialistasParticipantes()->pluck('id')->all());
        $this->assertEquals([$obra->id], $t->obrasParticipadas()->pluck('id')->all());
    }

    public function test_la_tarifa_se_congela_al_crear_la_linea()
    {
        $t = Trabajador::factory()->create(['coste_hora_estandar' => 20]);
        $linea = LineaParteTrabajo::factory()->create(['trabajador_id' => $t->id, 'horas' => 8]);

        $t->update(['coste_hora_estandar' => 30]);

        $this->assertEquals(20, $linea->fresh()->coste_hora);
    }

    public function test_los_datos_del_cliente_se_copian_en_el_presupuesto()
    {
        $c = Cliente::factory()->create(['nombre' => 'Juan', 'apellidos' => 'Pérez']);
        $p = Presupuesto::create(['cliente_id' => $c->id, 'fecha' => '2026-01-10']);

        $this->assertSame('Juan Pérez', $p->nombre_cliente);
        $this->assertSame($c->nif, $p->nif);
    }
}
