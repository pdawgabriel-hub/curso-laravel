<?php

namespace Tests\Feature;

use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class RA7_1_SwaggerTest extends TestCase
{
    private function especificacion(): array
    {
        $fichero = storage_path('api-docs/gestion_obras_api.yaml');
        $this->assertFileExists($fichero);

        return Yaml::parseFile($fichero);
    }

    public function test_la_configuracion_de_swagger()
    {
        $paths = config('l5-swagger.documentations.default.paths');

        $this->assertSame('gestion_obras_api.yaml', $paths['docs_yaml']);
        $this->assertSame('yaml', $paths['format_to_use_for_docs']);
    }

    public function test_la_especificacion_es_openapi_3()
    {
        $spec = $this->especificacion();

        $this->assertMatchesRegularExpression('/^3\.\d+\.\d+$/', $spec['openapi'] ?? '');
        $this->assertArrayHasKey('bearerAuth', $spec['components']['securitySchemes'] ?? []);
    }

    public function test_documenta_los_recursos_principales()
    {
        $rutas = array_keys($this->especificacion()['paths'] ?? []);

        foreach (['/tokens', '/user', '/clientes', '/clientes/{cliente}', '/obras', '/obras/{obra}', '/presupuestos', '/obras/{obra}/ingresos', '/gastos/{gasto}'] as $ruta) {
            $this->assertContains($ruta, $rutas, "Falta la ruta $ruta");
        }
    }

    public function test_documenta_las_rutas_que_faltaban()
    {
        $paths = $this->especificacion()['paths'] ?? [];

        $this->assertArrayHasKey('/partes-trabajo/{parteTrabajo}/lineas/lote', $paths);
        $this->assertArrayHasKey('201', $paths['/partes-trabajo/{parteTrabajo}/lineas/lote']['post']['responses']);
        $this->assertArrayHasKey('422', $paths['/partes-trabajo/{parteTrabajo}/lineas/lote']['post']['responses']);
        $this->assertArrayHasKey('/user/observaciones', $paths);
        $this->assertArrayHasKey('200', $paths['/user/observaciones']['get']['responses']);
    }

    public function test_hay_una_pagina_con_swagger_ui()
    {
        $this->get('/api/documentation')->assertOk()->assertSee('swagger-ui', false);
    }
}
