<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_oculta_la_contrasena_y_el_token_de_sesion()
    {
        $array = (new User(['password' => 'secreto', 'remember_token' => 'token']))->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    public function test_el_rol_es_asignable()
    {
        $this->assertContains('rol', (new User())->getFillable());
    }

    public function test_es_administrador_segun_la_configuracion()
    {
        $this->assertTrue(User::factory()->make(['email' => config('app.admin.email')])->esAdmin());
        $this->assertFalse(User::factory()->make(['email' => 'otro@correo.test', 'rol' => 'jefe'])->esAdmin());
    }

    public function test_jefe_y_administrativa()
    {
        $this->assertTrue(User::factory()->make(['rol' => 'jefe'])->esJefe());
        $this->assertFalse(User::factory()->make(['rol' => 'administrativa'])->esJefe());
        $this->assertTrue(User::factory()->make(['rol' => 'administrativa'])->esAdministrativa());
        $this->assertFalse(User::factory()->make(['rol' => null])->esAdministrativa());
    }

    public function test_es_propietario_de_un_recurso()
    {
        $user = User::factory()->create();

        $this->assertTrue($user->esPropietario((object) ['user_id' => $user->id]));
        $this->assertFalse($user->esPropietario((object) ['user_id' => $user->id + 1]));
        $this->assertTrue($user->esPropietario((object) ['tutor_id' => $user->id], 'tutor_id'));
    }

    public function test_tiene_rol()
    {
        $this->assertTrue(User::factory()->make(['rol' => 'jefe'])->tieneRol());
        $this->assertTrue(User::factory()->make(['rol' => 'administrativa'])->tieneRol());
        $this->assertTrue(User::factory()->make(['email' => config('app.admin.email')])->tieneRol());
        $this->assertFalse(User::factory()->make(['rol' => null])->tieneRol());
    }

    public function test_lista_de_roles()
    {
        $this->assertSame(['administrador'], User::factory()->make(['email' => config('app.admin.email')])->roles());
        $this->assertSame(['jefe'], User::factory()->make(['rol' => 'jefe'])->roles());
        $this->assertSame(['administrativa'], User::factory()->make(['rol' => 'administrativa'])->roles());
        $this->assertSame([], User::factory()->make(['rol' => null])->roles());
    }
}
