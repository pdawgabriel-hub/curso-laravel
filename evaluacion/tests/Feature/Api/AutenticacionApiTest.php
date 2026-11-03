<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\FeatureTestCase;

class AutenticacionApiTest extends FeatureTestCase
{
    public function test_emite_un_token_con_credenciales_correctas()
    {
        User::factory()->create(['email' => 'jefe@obras.test', 'password' => 'secreto123', 'rol' => 'jefe']);

        $response = $this->postJson('/api/v1/tokens', ['email' => 'jefe@obras.test', 'password' => 'secreto123']);

        $response->assertOk()->assertJsonStructure(['token_type', 'access_token']);
        $this->assertSame('Bearer', $response->json('token_type'));
    }

    public function test_rechaza_credenciales_incorrectas()
    {
        User::factory()->create(['email' => 'jefe@obras.test', 'password' => 'secreto123']);

        $this->postJson('/api/v1/tokens', ['email' => 'jefe@obras.test', 'password' => 'otra'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_el_token_emitido_permite_acceder_a_la_api()
    {
        User::factory()->create(['email' => 'jefe@obras.test', 'password' => 'secreto123', 'rol' => 'jefe']);
        $token = $this->postJson('/api/v1/tokens', ['email' => 'jefe@obras.test', 'password' => 'secreto123'])->json('access_token');

        $this->withToken($token)->getJson('/api/v1/clientes')->assertOk();
    }

    public function test_revoca_el_token_actual()
    {
        $user = User::factory()->create(['rol' => 'jefe']);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->deleteJson('/api/v1/tokens')->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_sin_autenticar_devuelve_401()
    {
        $this->getJson('/api/v1/clientes')->assertUnauthorized();
        $this->getJson('/api/v1/obras')->assertUnauthorized();
        $this->getJson('/api/v1/user')->assertUnauthorized();
    }

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
}
