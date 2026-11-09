<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Especialista;
use App\Models\Falta;
use App\Models\Ingreso;
use App\Models\LineaParteTrabajo;
use App\Models\Obra;
use App\Models\ParteEspecialista;
use App\Models\ParteProveedor;
use App\Models\ParteTrabajo;
use App\Models\Presupuesto;
use App\Models\Proveedor;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * La tabla de permisos del contrato, recurso a recurso.
 * El usuario sin rol se comprueba en el test de cada recurso.
 */
/** 7.6: el resto de la tabla de permisos: obras, líneas, faltas, ingresos, gastos, adjuntos y bitácoras. */
class RA7_6_TablaPermisosTest extends FeatureTestCase
{    public function test_la_administrativa_no_retira_una_aprobacion()
    {
        $p = Presupuesto::factory()->create(['estado' => 'aprobado']);
        $this->actuarComo('administrativa');

        $this->putJson("/api/v1/presupuestos/{$p->id}", ['estado' => 'no_aprobado'])->assertForbidden();
        $this->putJson("/api/v1/presupuestos/{$p->id}", ['estado' => 'aprobado', 'iva' => 10])->assertOk();
        $this->assertDatabaseHas('presupuestos', ['id' => $p->id, 'estado' => 'aprobado']);
    }    public function test_la_administrativa_ve_las_obras_pero_no_las_toca()
    {
        $obra = Obra::factory()->create(['descripcion' => 'Original']);
        $libre = Presupuesto::factory()->create(['cliente_id' => $obra->cliente_id]);
        $this->actuarComo('administrativa');

        $this->getJson('/api/v1/obras')->assertOk();
        $this->getJson("/api/v1/obras/{$obra->id}")->assertOk();
        $this->postJson('/api/v1/obras', ['descripcion' => 'Nueva'])->assertForbidden();
        $this->putJson("/api/v1/obras/{$obra->id}", ['descripcion' => 'Cambiada'])->assertForbidden();
        $this->deleteJson("/api/v1/obras/{$obra->id}")->assertForbidden();
        $this->postJson("/api/v1/obras/{$obra->id}/vincular-presupuesto", ['presupuesto_id' => $libre->id])->assertForbidden();

        $this->assertDatabaseHas('obras', ['id' => $obra->id, 'descripcion' => 'Original']);
        $this->assertDatabaseCount('obras', 1);
        $this->assertDatabaseHas('presupuestos', ['id' => $libre->id, 'obra_id' => null]);
    }    public function test_las_lineas_siguen_los_permisos_de_su_parte()
    {
        $t = Trabajador::factory()->create();
        $yo = $this->actuarComo('administrativa');
        $mio = ParteTrabajo::factory()->create(['user_id' => $yo->id]);
        $ajeno = ParteTrabajo::factory()->create(['user_id' => User::factory()->create()->id]);
        $lineaAjena = LineaParteTrabajo::factory()->create(['parte_trabajo_id' => $ajeno->id, 'horas' => 2]);

        $id = $this->postJson("/api/v1/partes-trabajo/{$mio->id}/lineas", ['trabajador_id' => $t->id, 'horas' => 4])->assertCreated()->json('data.id');
        $this->putJson("/api/v1/partes-trabajo/{$mio->id}/lineas/{$id}", ['horas' => 5])->assertOk();
        $this->deleteJson("/api/v1/partes-trabajo/{$mio->id}/lineas/{$id}")->assertNoContent();
        $this->postJson("/api/v1/partes-trabajo/{$mio->id}/lineas/lote", ['trabajador_ids' => [$t->id], 'horas' => 8])->assertCreated();

        $this->getJson("/api/v1/partes-trabajo/{$ajeno->id}/lineas")->assertOk();
        $this->postJson("/api/v1/partes-trabajo/{$ajeno->id}/lineas", ['trabajador_id' => $t->id, 'horas' => 4])->assertForbidden();
        $this->postJson("/api/v1/partes-trabajo/{$ajeno->id}/lineas/lote", ['trabajador_ids' => [$t->id], 'horas' => 8])->assertForbidden();
        $this->putJson("/api/v1/partes-trabajo/{$ajeno->id}/lineas/{$lineaAjena->id}", ['horas' => 9])->assertForbidden();
        $this->deleteJson("/api/v1/partes-trabajo/{$ajeno->id}/lineas/{$lineaAjena->id}")->assertForbidden();
        $this->assertDatabaseHas('lineas_parte_trabajo', ['id' => $lineaAjena->id, 'horas' => 2]);
    }    public function test_nadie_toca_las_faltas_de_otro_salvo_el_jefe()
    {
        $t = Trabajador::factory()->create();
        $datos = ['fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-01-02', 'tipo' => 'otro'];
        $yo = $this->actuarComo('administrativa');
        $mia = Falta::factory()->create(['trabajador_id' => $t->id, 'user_id' => $yo->id]);
        $ajena = Falta::factory()->create(['trabajador_id' => $t->id, 'user_id' => User::factory()->create()->id, 'tipo' => 'personal']);

        $this->putJson("/api/v1/trabajadores/{$t->id}/faltas/{$mia->id}", $datos)->assertOk();
        $this->deleteJson("/api/v1/trabajadores/{$t->id}/faltas/{$mia->id}")->assertNoContent();
        $this->putJson("/api/v1/trabajadores/{$t->id}/faltas/{$ajena->id}", $datos)->assertForbidden();
        $this->deleteJson("/api/v1/trabajadores/{$t->id}/faltas/{$ajena->id}")->assertForbidden();
        $this->assertDatabaseHas('faltas', ['id' => $ajena->id, 'tipo' => 'personal']);

        $this->actuarComo('jefe');
        $this->deleteJson("/api/v1/trabajadores/{$t->id}/faltas/{$ajena->id}")->assertNoContent();
    }    public function test_los_ingresos_son_solo_del_jefe()
    {
        $ingreso = Ingreso::factory()->create(['importe' => 100]);
        $url = "/api/v1/obras/{$ingreso->obra_id}/ingresos";
        $this->actuarComo('administrativa');

        $this->getJson($url)->assertForbidden();
        $this->getJson("$url/{$ingreso->id}")->assertForbidden();
        $this->postJson($url, ['importe' => 10])->assertForbidden();
        $this->putJson("$url/{$ingreso->id}", ['importe' => 10])->assertForbidden();
        $this->deleteJson("$url/{$ingreso->id}")->assertForbidden();
        $this->getJson("/api/v1/ingresos/{$ingreso->id}/observaciones")->assertForbidden();
        $this->assertDatabaseHas('ingresos', ['id' => $ingreso->id, 'importe' => 100]);
        $this->assertDatabaseCount('ingresos', 1);

        $this->actuarComo('jefe');
        $this->getJson($url)->assertOk();
        $this->postJson($url, ['importe' => 10])->assertCreated();
    }    public function test_los_gastos_y_las_estadisticas_son_solo_del_jefe()
    {
        $gasto = Obra::factory()->create()->gasto;
        $this->actuarComo('administrativa');

        $this->getJson('/api/v1/gastos')->assertForbidden();
        $this->getJson("/api/v1/gastos/{$gasto->id}")->assertForbidden();
        $this->putJson("/api/v1/gastos/{$gasto->id}", ['direccion_obra' => 'X'])->assertForbidden();
        $this->getJson('/api/v1/estadisticas')->assertForbidden();

        $this->actuarComo('jefe');
        $this->getJson('/api/v1/gastos')->assertOk();
        $this->getJson('/api/v1/estadisticas')->assertOk();
    }    public function test_los_adjuntos_siguen_los_permisos_de_su_registro()
    {
        Storage::fake();
        $obra = Obra::factory()->create();
        $yo = $this->actuarComo('administrativa');
        $mio = ParteProveedor::factory()->create(['obra_id' => $obra->id, 'user_id' => $yo->id]);
        $pdf = fn () => ['archivo' => UploadedFile::fake()->create('a.pdf', 5, 'application/pdf')];

        $this->getJson("/api/v1/obras/{$obra->id}/archivos")->assertOk();
        $this->postJson("/api/v1/obras/{$obra->id}/archivos", $pdf())->assertForbidden();
        $id = $this->postJson("/api/v1/partes-proveedor/{$mio->id}/archivos", $pdf())->assertCreated()->json('data.id');
        $this->deleteJson("/api/v1/archivos/{$id}")->assertNoContent();
        $this->getJson("/api/v1/gastos/{$obra->gasto->id}/archivos")->assertForbidden();
    }    public function test_cualquiera_con_rol_anota_en_lo_que_puede_ver()
    {
        $obra = Obra::factory()->create();
        $this->actuarComo('administrativa');

        $this->postJson("/api/v1/obras/{$obra->id}/observaciones", ['texto' => 'Nota'])->assertCreated();
        $this->postJson("/api/v1/obras/{$obra->id}/incidencias", ['texto' => 'Fallo'])->assertCreated();
    }    public function test_el_administrador_lo_puede_todo()
    {
        $ajeno = ParteTrabajo::factory()->create(['user_id' => User::factory()->create()->id]);
        $this->actuarComoAdministrador();

        $this->postJson('/api/v1/obras', ['descripcion' => 'X'])->assertCreated();
        $this->getJson('/api/v1/estadisticas')->assertOk();
        $this->deleteJson("/api/v1/obras/{$ajeno->obra_id}/partes-trabajo/{$ajeno->id}")->assertNoContent();
    }
}
