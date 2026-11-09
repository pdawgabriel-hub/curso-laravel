<?php

namespace Tests\Feature;

use Tests\Feature\FeatureTestCase;

class RA7_6_RolesApiTest extends FeatureTestCase
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

    public function test_jefe_y_administrativa_tienen_acceso_con_permisos_distintos()
    {
        $this->actuarComo('jefe');
        $this->postJson('/api/v1/obras', ['descripcion' => 'Obra del jefe'])->assertCreated();
        $this->getJson('/api/v1/clientes')->assertOk();

        $this->actuarComo('administrativa');
        $this->postJson('/api/v1/obras', ['descripcion' => 'Obra de la administrativa'])->assertForbidden();
        $this->getJson('/api/v1/clientes')->assertOk();

        $this->assertDatabaseCount('obras', 1);
    }
}
