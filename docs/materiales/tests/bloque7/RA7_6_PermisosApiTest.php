<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Especialista;
use App\Models\Obra;
use App\Models\ParteEspecialista;
use App\Models\ParteProveedor;
use App\Models\ParteTrabajo;
use App\Models\Presupuesto;
use App\Models\Proveedor;
use App\Models\Trabajador;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class RA7_6_PermisosApiTest extends FeatureTestCase
{
    public static function maestros(): array
    {
        return [
            'clientes' => [Cliente::class, 'clientes', ['nombre' => 'A', 'direccion' => 'B', 'telefono' => 'C']],
            'trabajadores' => [Trabajador::class, 'trabajadores', ['nombre' => 'A', 'apellido' => 'B']],
            'proveedores' => [Proveedor::class, 'proveedores', ['nombre' => 'A', 'base' => 1, 'iva' => 21]],
            'especialistas' => [Especialista::class, 'especialistas', ['nombre' => 'A']],
        ];
    }

    #[DataProvider('maestros')]
    public function test_la_administrativa_gestiona_los_maestros_pero_no_los_borra(string $modelo, string $ruta, array $datos)
    {
        $x = $modelo::factory()->create();
        $this->actuarComo('administrativa');

        $this->getJson("/api/v1/$ruta")->assertOk();
        $this->getJson("/api/v1/$ruta/{$x->id}")->assertOk();
        $this->postJson("/api/v1/$ruta", $datos)->assertCreated();
        $this->putJson("/api/v1/$ruta/{$x->id}", $datos)->assertOk();
        $this->deleteJson("/api/v1/$ruta/{$x->id}")->assertForbidden();
        $this->assertDatabaseHas($ruta, ['id' => $x->id]);
    }

    #[DataProvider('maestros')]
    public function test_el_jefe_borra_los_maestros(string $modelo, string $ruta, array $datos)
    {
        $x = $modelo::factory()->create();
        $this->actuarComo('jefe');

        $this->deleteJson("/api/v1/$ruta/{$x->id}")->assertNoContent();
    }

    public function test_la_administrativa_trabaja_con_presupuestos_sin_aprobarlos_ni_borrarlos()
    {
        $cliente = Cliente::factory()->create();
        $this->actuarComo('administrativa');

        $id = $this->postJson('/api/v1/presupuestos', ['cliente_id' => $cliente->id])->assertCreated()->json('data.id');
        $this->putJson("/api/v1/presupuestos/{$id}", ['iva' => 10])->assertOk();
        $this->postJson("/api/v1/presupuestos/{$id}/lineas", ['uds' => 1, 'precio' => 10])->assertCreated();

        $this->postJson('/api/v1/presupuestos', ['cliente_id' => $cliente->id, 'estado' => 'aprobado'])->assertForbidden();
        $this->putJson("/api/v1/presupuestos/{$id}", ['estado' => 'aprobado'])->assertForbidden();
        $this->assertDatabaseHas('presupuestos', ['id' => $id, 'estado' => 'no_aprobado']);
        $this->assertDatabaseCount('presupuestos', 1);
        $this->deleteJson("/api/v1/presupuestos/{$id}")->assertForbidden();
        $this->assertDatabaseHas('presupuestos', ['id' => $id]);
    }

    public function test_el_jefe_aprueba_y_borra_presupuestos()
    {
        $p = Presupuesto::factory()->create();
        $this->actuarComo('jefe');

        $this->putJson("/api/v1/presupuestos/{$p->id}", ['estado' => 'aprobado'])->assertOk()->assertJsonPath('data.estado', 'aprobado');
        $this->deleteJson("/api/v1/presupuestos/{$p->id}")->assertNoContent();
    }

    public static function partes(): array
    {
        return [
            'trabajo' => [ParteTrabajo::class, 'partes-trabajo', 'partes_trabajo', ['descripcion' => 'Cambiada']],
            'proveedor' => [ParteProveedor::class, 'partes-proveedor', 'partes_proveedor', ['estado_pago' => 'pagado']],
            'especialista' => [ParteEspecialista::class, 'partes-especialista', 'partes_especialista', ['estado_pago' => 'pagado']],
        ];
    }

    #[DataProvider('partes')]
    public function test_cada_uno_modifica_y_borra_sus_partes(string $modelo, string $ruta, string $tabla, array $cambio)
    {
        $obra = Obra::factory()->create();
        $yo = $this->actuarComo('administrativa');
        $mio = $modelo::factory()->create(['obra_id' => $obra->id, 'user_id' => $yo->id]);

        $this->putJson("/api/v1/obras/{$obra->id}/$ruta/{$mio->id}", $cambio)->assertOk();
        $this->deleteJson("/api/v1/obras/{$obra->id}/$ruta/{$mio->id}")->assertNoContent();
        $this->assertDatabaseMissing($tabla, ['id' => $mio->id]);
    }

    #[DataProvider('partes')]
    public function test_nadie_toca_los_partes_de_otro_salvo_el_jefe(string $modelo, string $ruta, string $tabla, array $cambio)
    {
        $obra = Obra::factory()->create();
        $ajeno = $modelo::factory()->create(['obra_id' => $obra->id, 'user_id' => User::factory()->create()->id]);

        $this->actuarComo('administrativa');
        $this->getJson("/api/v1/obras/{$obra->id}/$ruta/{$ajeno->id}")->assertOk();
        $this->putJson("/api/v1/obras/{$obra->id}/$ruta/{$ajeno->id}", $cambio)->assertForbidden();
        $this->deleteJson("/api/v1/obras/{$obra->id}/$ruta/{$ajeno->id}")->assertForbidden();
        $this->assertDatabaseHas($tabla, ['id' => $ajeno->id]);

        $this->actuarComo('jefe');
        $this->putJson("/api/v1/obras/{$obra->id}/$ruta/{$ajeno->id}", $cambio)->assertOk();
        $this->deleteJson("/api/v1/obras/{$obra->id}/$ruta/{$ajeno->id}")->assertNoContent();
    }
}
