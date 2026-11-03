<?php

namespace Tests\Feature;

use App\Models\Archivo;
use App\Models\Cliente;
use App\Models\Obra;
use App\Models\Presupuesto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\FeatureTestCase;

class RA7_5_ArchivosApiTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        $this->actuarComo('administrativa');
    }

    private function subir(string $tipo, int $id, UploadedFile $fichero)
    {
        return $this->postJson("/api/v1/{$tipo}/{$id}/archivos", ['archivo' => $fichero]);
    }

    public function test_sube_un_pdf_y_lo_clasifica()
    {
        $obra = Obra::factory()->create();

        $response = $this->subir('obras', $obra->id, UploadedFile::fake()->create('licencia.pdf', 50, 'application/pdf'));

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'nombre_archivo', 'tipo', 'created_at']])
            ->assertJsonPath('data.nombre_archivo', 'licencia.pdf')
            ->assertJsonPath('data.tipo', 'pdf');
        Storage::assertExists(Archivo::first()->ruta);
    }

    public function test_el_fichero_es_obligatorio()
    {
        $obra = Obra::factory()->create();

        $this->postJson("/api/v1/obras/{$obra->id}/archivos", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('archivo');
    }

    public function test_lista_y_descarga_los_adjuntos()
    {
        $p = Presupuesto::factory()->create();
        $id = $this->subir('presupuestos', $p->id, UploadedFile::fake()->create('plano.pdf', 5, 'application/pdf'))->json('data.id');

        $this->assertListado($this->getJson("/api/v1/presupuestos/{$p->id}/archivos"), ['id', 'nombre_archivo', 'tipo'], 1);
        $this->get("/api/v1/archivos/{$id}/descarga")->assertOk()->assertDownload('plano.pdf');
    }

    public function test_borrar_un_adjunto_borra_el_fichero()
    {
        $obra = Obra::factory()->create();
        $id = $this->subir('obras', $obra->id, UploadedFile::fake()->create('a.pdf', 5, 'application/pdf'))->json('data.id');
        $ruta = Archivo::find($id)->ruta;

        $this->deleteJson("/api/v1/archivos/{$id}")->assertNoContent();

        $this->assertDatabaseCount('archivos', 0);
        Storage::assertMissing($ruta);
    }

    public function test_entidad_sin_adjuntos_devuelve_404()
    {
        $cliente = Cliente::factory()->create();

        $this->subir('clientes', $cliente->id, UploadedFile::fake()->create('a.pdf', 5, 'application/pdf'))->assertNotFound();
    }
}
