<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UsersTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** 6.7: el rol de los usuarios, el administrador y la semilla de usuarios. */
class RA6_7_UsuariosTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_usuarios_tienen_rol()
    {
        $this->assertTrue(Schema::hasColumn('users', 'rol'));
        $this->assertNotEmpty(glob(database_path('migrations/*_add_rol_to_users_table.php')), 'Falta la migración add_rol_to_users_table');
        $this->assertContains('rol', (new User())->getFillable());
        $this->assertNull(User::factory()->create()->fresh()->rol);
        $this->assertSame('jefe', User::factory()->create(['rol' => 'jefe'])->fresh()->rol);
    }

    public function test_el_correo_del_administrador_se_configura()
    {
        $this->assertNotEmpty(config('app.admin.email'), "Falta 'admin' => ['email' => env('ADMIN_EMAIL')] en config/app.php");
        $this->assertStringContainsString('ADMIN_EMAIL=', file_get_contents(base_path('.env.example')));
    }

    public function test_la_semilla_crea_un_usuario_de_cada_tipo()
    {
        $this->seed(UsersTableSeeder::class);

        $this->assertTrue(User::where('email', config('app.admin.email'))->exists());
        $this->assertTrue(User::where('rol', 'jefe')->exists());
        $this->assertTrue(User::where('rol', 'administrativa')->exists());
    }

    public function test_la_semilla_general_incluye_los_usuarios()
    {
        $this->seed();

        $this->assertGreaterThanOrEqual(3, User::count());
    }

    public function test_el_menu_muestra_el_usuario_y_su_rol()
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['name' => 'Carla Usuaria', 'rol' => 'administrativa']));

        $this->get(route('clientes.index'))->assertOk()->assertSeeInOrder(['Carla Usuaria', 'Administrativa', 'Cerrar sesión']);
    }
}
