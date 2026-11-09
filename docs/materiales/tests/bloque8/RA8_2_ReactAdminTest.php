<?php

namespace Tests\Feature;

use App\Models\User;
use Inertia\Testing\AssertableInertia;

/** 8.2: Breeze con React, Inertia y TypeScript, y React-Admin montado en /dashboard. */
class RA8_2_ReactAdminTest extends FeatureTestCase
{
    public function test_el_panel_exige_iniciar_sesion()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_el_panel_es_la_pagina_inertia_dashboard()
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['rol' => 'jefe']));

        $this->get('/dashboard')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('Dashboard'));
    }

    public function test_el_proyecto_usa_typescript_y_react_admin()
    {
        $paquete = json_decode(file_get_contents(base_path('package.json')), true);
        $todas = ($paquete['dependencies'] ?? []) + ($paquete['devDependencies'] ?? []);

        foreach (['react-admin', 'ra-data-json-server', '@inertiajs/react', 'typescript'] as $dep) {
            $this->assertArrayHasKey($dep, $todas, "Falta {$dep} en package.json");
        }
        $this->assertArrayHasKey('type-check', $paquete['scripts'] ?? [], 'Falta el script type-check');
        $this->assertFileExists(base_path('tsconfig.json'));
        foreach (['App.tsx', 'dataProvider.ts', 'authProvider.ts'] as $fichero) {
            $this->assertFileExists(resource_path("js/react-admin/{$fichero}"));
        }
        $this->assertStringContainsString('react-admin/App', file_get_contents(resource_path('js/Pages/Dashboard.tsx')));
        $this->assertStringContainsString('VITE_API_URL=', file_get_contents(base_path('.env.example')));
    }
}
