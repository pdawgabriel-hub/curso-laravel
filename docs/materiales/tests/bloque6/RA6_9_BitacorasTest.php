<?php

namespace Tests\Feature;

use App\Models\Archivo;
use App\Models\Cliente;
use App\Models\Obra;
use App\Models\ParteTrabajo;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RA6_9_BitacorasTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_mapa_polimorfico_usa_los_nombres_de_las_rutas()
    {
        $this->assertSame(Obra::class, Relation::getMorphedModel('obras'));
        $this->assertSame(ParteTrabajo::class, Relation::getMorphedModel('partes-trabajo'));
        $this->assertSame('clientes', (new Cliente())->getMorphClass());
    }

    public function test_observaciones_con_autor_y_de_la_mas_nueva_a_la_mas_antigua()
    {
        $user = User::factory()->create();
        $obra = Obra::factory()->create();

        $obra->observaciones()->create(['texto' => 'Primera', 'user_id' => $user->id]);
        $obra->observaciones()->create(['texto' => 'Segunda', 'user_id' => $user->id]);

        $this->assertSame(['Segunda', 'Primera'], $obra->observaciones()->pluck('texto')->all());
        $this->assertTrue($obra->observaciones->first()->usuario->is($user));
        $this->assertDatabaseHas('observaciones', ['observable_type' => 'obras', 'observable_id' => $obra->id]);
    }

    public function test_incidencias_separadas_de_las_observaciones()
    {
        $parte = ParteTrabajo::factory()->create();

        $parte->incidencias()->create(['texto' => 'Falta material']);

        $this->assertCount(1, $parte->incidencias);
        $this->assertDatabaseHas('incidencias', ['incidentable_type' => 'partes-trabajo']);
    }

    public function test_borrar_un_adjunto_borra_su_fichero()
    {
        Storage::fake();
        $ruta = UploadedFile::fake()->create('a.pdf', 5, 'application/pdf')->store('archivos');
        $obra = Obra::factory()->create();
        $archivo = $obra->archivos()->create(['nombre_archivo' => 'a.pdf', 'ruta' => $ruta, 'tipo' => 'pdf']);

        $archivo->delete();

        Storage::assertMissing($ruta);
    }

    public function test_borrar_la_entidad_borra_bitacoras_y_ficheros()
    {
        Storage::fake();
        $ruta = UploadedFile::fake()->create('a.pdf', 5, 'application/pdf')->store('archivos');
        $obra = Obra::factory()->create();
        $obra->observaciones()->create(['texto' => 'Nota']);
        $obra->incidencias()->create(['texto' => 'Problema']);
        $obra->archivos()->create(['nombre_archivo' => 'a.pdf', 'ruta' => $ruta, 'tipo' => 'pdf']);
        $obra->gasto->delete();

        $obra->delete();

        $this->assertDatabaseCount('observaciones', 0);
        $this->assertDatabaseCount('incidencias', 0);
        $this->assertDatabaseCount('archivos', 0);
        Storage::assertMissing($ruta);
    }
}
