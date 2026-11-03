<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Obra;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\FeatureTestCase;

class RA7_4_AutorizacionApiTest extends FeatureTestCase
{
    public function test_perfil_del_administrador()
    {
        $this->actuarComoAdministrador();

        $this->getJson('/api/v1/user')->assertOk()->assertJsonPath('roles', ['administrador']);
    }

    public function test_perfil_de_jefe_y_de_administrativa()
    {
        $this->actuarComo('jefe');
        $this->getJson('/api/v1/user')->assertOk()->assertJsonPath('roles', ['jefe']);

        $this->actuarComo('administrativa');
        $this->getJson('/api/v1/user')->assertOk()->assertJsonPath('roles', ['administrativa']);
    }

    public function test_usuario_sin_rol_no_tiene_roles_ni_acceso()
    {
        $this->actuarComo(null);

        $this->getJson('/api/v1/user')->assertOk()->assertJsonPath('roles', []);
        $this->getJson('/api/v1/clientes')->assertForbidden();
        $this->postJson('/api/v1/obras', ['descripcion' => 'X'])->assertForbidden();
    }

    public function test_jefe_y_administrativa_tienen_los_mismos_permisos()
    {
        foreach (['jefe', 'administrativa'] as $rol) {
            $this->actuarComo($rol);
            $this->postJson('/api/v1/obras', ['descripcion' => "Obra de $rol"])->assertCreated();
        }
        $this->assertDatabaseCount('obras', 2);
    }

    public function test_usuario_sin_rol_no_puede_crear_ni_borrar_clientes()
    {
        $cliente = Cliente::factory()->create();
        $this->actuarComo(null);

        $this->postJson('/api/v1/clientes', ['nombre' => 'A', 'direccion' => 'B', 'telefono' => 'C'])->assertForbidden();
        $this->deleteJson("/api/v1/clientes/{$cliente->id}")->assertForbidden();
        $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
    }

    public function test_el_administrador_pasa_por_gate_before()
    {
        $this->actuarComoAdministrador();

        $this->postJson('/api/v1/clientes', ['nombre' => 'A', 'direccion' => 'B', 'telefono' => 'C'])->assertCreated();
    }

    public function test_la_ficha_de_gastos_no_se_crea_ni_se_borra_por_la_api()
    {
        $this->actuarComoAdministrador();
        $obra = Obra::factory()->create();

        $this->postJson('/api/v1/gastos', ['obra_id' => $obra->id])->assertMethodNotAllowed();
        $this->deleteJson("/api/v1/gastos/{$obra->gasto->id}")->assertMethodNotAllowed();
    }
}
