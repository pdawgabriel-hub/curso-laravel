<?php

namespace Tests\Feature;

use App\Models\Obra;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Feature\FeatureTestCase;

class RA9_5_ApiExternaTest extends FeatureTestCase
{
    private Obra $obra;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('administrativa');
        $this->obra = Obra::factory()->create();
        $this->obra->gasto->update(['direccion_obra' => 'Calle Mayor 1, Madrid']);
    }

    public function test_geocodifica_la_direccion_de_la_obra()
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([
            ['lat' => '40.4153', 'lon' => '-3.7074', 'display_name' => 'Calle Mayor, 1, Madrid, España'],
        ])]);

        $this->getJson("/api/v1/obras/{$this->obra->id}/ubicacion")
            ->assertOk()
            ->assertJsonPath('data.lat', 40.4153)
            ->assertJsonPath('data.lon', -3.7074)
            ->assertJsonPath('data.direccion', 'Calle Mayor, 1, Madrid, España')
            ->assertJsonPath('data.consulta', 'Calle Mayor 1, Madrid');

        Http::assertSent(fn (Request $r) => str_starts_with($r->url(), 'https://nominatim.openstreetmap.org/search')
            && $r['q'] === 'Calle Mayor 1, Madrid'
            && $r['format'] === 'json'
            && $r->hasHeader('User-Agent'));
    }

    public function test_usa_la_direccion_del_cliente_si_la_obra_no_tiene()
    {
        $this->obra->gasto->update(['direccion_obra' => null]);
        Http::fake(['*' => Http::response([['lat' => '1', 'lon' => '2', 'display_name' => 'X']])]);

        $this->getJson("/api/v1/obras/{$this->obra->id}/ubicacion")->assertOk()->assertJsonPath('data.consulta', $this->obra->cliente->direccion);
    }

    public function test_guarda_la_respuesta_en_cache()
    {
        Http::fake(['*' => Http::response([['lat' => '1', 'lon' => '2', 'display_name' => 'X']])]);

        $this->getJson("/api/v1/obras/{$this->obra->id}/ubicacion")->assertOk();
        $this->getJson("/api/v1/obras/{$this->obra->id}/ubicacion")->assertOk();

        Http::assertSentCount(1);
    }

    public function test_direccion_no_encontrada()
    {
        Http::fake(['*' => Http::response([])]);

        $this->getJson("/api/v1/obras/{$this->obra->id}/ubicacion")->assertNotFound();
    }

    public function test_servicio_caido()
    {
        Http::fake(['*' => Http::response('Error', 500)]);

        $this->getJson("/api/v1/obras/{$this->obra->id}/ubicacion")->assertStatus(503);
    }

    public function test_obra_sin_ninguna_direccion()
    {
        $obra = Obra::factory()->create(['cliente_id' => null]);
        Http::fake();

        $this->getJson("/api/v1/obras/{$obra->id}/ubicacion")->assertUnprocessable();
        Http::assertNothingSent();
    }
}
