<?php

namespace Tests\Feature;

use Tests\TestCase;

class RA7_6_DocumentacionApiTest extends TestCase
{
    private function especificacion(): array
    {
        $fichero = public_path('openapi.json');
        $this->assertFileExists($fichero);

        return json_decode(file_get_contents($fichero), true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_la_especificacion_es_openapi_3()
    {
        $spec = $this->especificacion();

        $this->assertMatchesRegularExpression('/^3\.\d+\.\d+$/', $spec['openapi'] ?? '');
        $this->assertNotEmpty($spec['info']['title'] ?? null);
        $this->assertArrayHasKey('bearerAuth', $spec['components']['securitySchemes'] ?? []);
    }

    public function test_documenta_los_recursos_principales()
    {
        $rutas = array_keys($this->especificacion()['paths'] ?? []);

        foreach (['/api/v1/tokens', '/api/v1/clientes', '/api/v1/clientes/{cliente}', '/api/v1/obras', '/api/v1/obras/{obra}',
                  '/api/v1/presupuestos', '/api/v1/obras/{obra}/ingresos', '/api/v1/gastos/{gasto}'] as $ruta) {
            $this->assertContains($ruta, $rutas, "Falta la ruta $ruta en openapi.json");
        }
    }

    public function test_documenta_los_codigos_de_respuesta_de_clientes()
    {
        $paths = $this->especificacion()['paths'];

        $this->assertArrayHasKey('201', $paths['/api/v1/clientes']['post']['responses']);
        $this->assertArrayHasKey('422', $paths['/api/v1/clientes']['post']['responses']);
        $this->assertArrayHasKey('409', $paths['/api/v1/clientes/{cliente}']['delete']['responses']);
    }

    public function test_hay_una_pagina_con_swagger_ui()
    {
        $this->get('/api/documentacion')->assertOk()->assertSee('swagger-ui', false)->assertSee('openapi.json', false);
    }
}
