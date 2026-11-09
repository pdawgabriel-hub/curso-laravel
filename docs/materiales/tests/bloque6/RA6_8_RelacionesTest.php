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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 6.8: todas las relaciones de los modelos. */
class RA6_8_RelacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_uno_a_uno_obra_y_ficha_de_gastos()
    {
        $obra = Obra::factory()->create();

        $this->assertInstanceOf(HasOne::class, $obra->gasto());
        $this->assertInstanceOf(BelongsTo::class, $obra->gasto->obra());
        $this->assertTrue($obra->gasto->obra->is($obra));

        $this->expectException(QueryException::class);
        Gasto::create(['obra_id' => $obra->id]);
    }

    public function test_uno_a_muchos_desde_el_cliente()
    {
        $c = Cliente::factory()->create();
        $obra = Obra::factory()->create(['cliente_id' => $c->id]);
        Presupuesto::factory()->count(2)->create(['cliente_id' => $c->id]);
        Ingreso::factory()->create(['obra_id' => $obra->id]);

        $this->assertInstanceOf(HasMany::class, $c->obras());
        $this->assertCount(1, $c->obras);
        $this->assertCount(2, $c->presupuestos);
        $this->assertCount(1, $c->ingresos);
        $this->assertTrue($obra->cliente->is($c));
        $this->assertTrue($c->presupuestos->first()->cliente->is($c));
    }

    public function test_trabajador_y_sus_faltas()
    {
        $t = Trabajador::factory()->create();
        Falta::factory()->count(3)->create(['trabajador_id' => $t->id]);

        $this->assertInstanceOf(HasMany::class, $t->faltas());
        $this->assertCount(3, $t->faltas);
        $this->assertTrue($t->faltas->first()->trabajador->is($t));
    }

    public function test_presupuesto_obra_y_lineas()
    {
        $obra = Obra::factory()->create();
        $p = Presupuesto::factory()->create(['cliente_id' => $obra->cliente_id, 'obra_id' => $obra->id]);
        LineaPresupuesto::factory()->count(2)->create(['presupuesto_id' => $p->id]);

        $this->assertCount(2, $p->lineas);
        $this->assertTrue($p->lineas->first()->presupuesto->is($p));
        $this->assertTrue($p->obra->is($obra));
        $this->assertCount(1, $obra->presupuestos);
        $this->assertInstanceOf(BelongsTo::class, $obra->gasto->presupuesto());
    }

    public function test_muchos_a_muchos_proveedores_y_especialistas()
    {
        $p = Presupuesto::factory()->create();
        $prov = Proveedor::factory()->create();
        $esp = Especialista::factory()->create();

        $this->assertInstanceOf(BelongsToMany::class, $p->proveedores());
        $p->proveedores()->attach($prov);
        $esp->presupuestos()->attach($p);

        $this->assertTrue($prov->presupuestos->contains($p), 'La relación se ve desde el proveedor');
        $this->assertTrue($p->especialistas->contains($esp), 'La relación se ve desde el presupuesto');

        $p->proveedores()->sync([]);
        $this->assertCount(0, $p->fresh()->proveedores);
    }

    public function test_partes_con_su_obra_su_recurso_y_su_autor()
    {
        $user = User::factory()->create();
        $parte = ParteTrabajo::factory()->create(['user_id' => $user->id]);
        $linea = LineaParteTrabajo::factory()->create(['parte_trabajo_id' => $parte->id]);
        $pp = ParteProveedor::factory()->create(['obra_id' => $parte->obra_id, 'user_id' => $user->id]);
        $pe = ParteEspecialista::factory()->create(['obra_id' => $parte->obra_id]);
        $f = Falta::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($parte->usuario->is($user));
        $this->assertTrue($pp->usuario->is($user));
        $this->assertTrue($f->usuario->is($user));
        $this->assertTrue($linea->parte->is($parte));
        $this->assertCount(1, $parte->lineas);
        $this->assertCount(1, $linea->trabajador->lineasTrabajo);
        $this->assertCount(1, $pp->proveedor->partes);
        $this->assertCount(1, $pe->especialista->partes);
        $obra = $parte->obra;
        $this->assertCount(1, $obra->partesTrabajo);
        $this->assertCount(1, $obra->partesProveedor);
        $this->assertCount(1, $obra->partesEspecialista);
    }

    public function test_lineas_de_la_obra_a_traves_de_sus_partes()
    {
        $obra = Obra::factory()->create();
        $parte = ParteTrabajo::factory()->create(['obra_id' => $obra->id]);
        LineaParteTrabajo::factory()->count(3)->create(['parte_trabajo_id' => $parte->id, 'horas' => 2]);
        LineaParteTrabajo::factory()->create(['horas' => 8]);

        $this->assertInstanceOf(HasManyThrough::class, $obra->lineasTrabajo());
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

        $this->assertEquals([$t->id], $obra->trabajadoresParticipantes()->pluck('id')->all(), 'Sin repetir, aunque tenga dos líneas');
        $this->assertEquals([$pp->proveedor_id], $obra->proveedoresParticipantes()->pluck('id')->all());
        $this->assertEquals([$pe->especialista_id], $obra->especialistasParticipantes()->pluck('id')->all());
        $this->assertEquals([$obra->id], $t->obrasParticipadas()->pluck('id')->all());
        $this->assertEquals([$obra->id], $pp->proveedor->obrasParticipadas()->pluck('id')->all());
        $this->assertEquals([$obra->id], $pe->especialista->obrasParticipadas()->pluck('id')->all());
    }

    public function test_ingreso_con_su_obra_y_su_cliente()
    {
        $obra = Obra::factory()->create();
        $i = Ingreso::factory()->create(['obra_id' => $obra->id]);

        $this->assertTrue($i->obra->is($obra));
        $this->assertTrue($i->cliente->is($obra->cliente));
        $this->assertCount(1, $obra->ingresos);
    }
}
