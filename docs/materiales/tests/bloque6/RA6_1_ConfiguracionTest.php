<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RA6_1_ConfiguracionTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_usuarios_tienen_rol()
    {
        $this->assertTrue(Schema::hasColumn('users', 'rol'));
        $this->assertContains('rol', (new User())->getFillable());
        $this->assertNull(User::factory()->create()->fresh()->rol);
        $this->assertSame('jefe', User::factory()->create(['rol' => 'jefe'])->fresh()->rol);
    }
}
