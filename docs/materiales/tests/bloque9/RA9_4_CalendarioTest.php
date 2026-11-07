<?php

namespace Tests\Feature;

use App\Models\Ingreso;
use App\Models\ParteTrabajo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RA9_4_CalendarioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_la_pagina_del_calendario()
    {
        $this->actingAs(User::factory()->create(['rol' => 'jefe']));

        $this->get('/calendario')
            ->assertOk()
            ->assertSee('id="calendario"', false)
            ->assertSee(route('calendario.eventos'), false)
            ->assertSee('fullcalendar', false);
    }

    public function test_los_eventos_en_formato_fullcalendar()
    {
        $this->actingAs(User::factory()->create(['rol' => 'jefe']));
        $i = Ingreso::factory()->create(['fecha' => '2026-05-10']);
        ParteTrabajo::factory()->create(['fecha' => '2026-05-12', 'descripcion' => 'Alicatado']);
        Ingreso::factory()->create(['fecha' => '2026-08-01']);

        $response = $this->getJson('/calendario/eventos?start=2026-05-01T00:00:00%2B02:00&end=2026-06-01T00:00:00%2B02:00');

        $response->assertOk()->assertJsonCount(2)->assertJsonStructure(['*' => ['title', 'start', 'color', 'extendedProps' => ['tipo', 'modelo', 'id']]]);
        $this->assertSame('2026-05-10', $response->json('0.start'));
        $this->assertSame('ingreso', $response->json('0.extendedProps.tipo'));
        $this->assertSame($i->id, $response->json('0.extendedProps.id'));
        $this->assertSame('Alicatado', $response->json('1.title'));
        $this->assertNotSame($response->json('0.color'), $response->json('1.color'), 'Cada tipo de evento tiene su color');
    }

    public function test_el_calendario_exige_sesion()
    {
        $this->get('/calendario')->assertRedirect('/login');
    }
}
