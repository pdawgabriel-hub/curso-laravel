<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    /**
     * Crea un usuario con el rol indicado y lo autentica en la API.
     * Roles válidos: 'administrativa', 'jefe' o null (usuario sin rol).
     */
    protected function actuarComo(?string $rol = 'administrativa'): User
    {
        $user = User::factory()->create(['rol' => $rol]);
        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * Autentica al administrador técnico (email de config('app.admin.email')).
     */
    protected function actuarComoAdministrador(): User
    {
        $user = User::factory()->create(['email' => config('app.admin.email')]);
        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * Compara importes con tolerancia de un céntimo (acepta número o cadena numérica).
     */
    protected function assertImporte(float $esperado, mixed $valor, string $mensaje = ''): void
    {
        $this->assertIsNumeric($valor, $mensaje ?: 'El importe debe ser numérico');
        $this->assertEqualsWithDelta($esperado, (float) $valor, 0.001, $mensaje);
    }

    /**
     * Comprueba la estructura de un listado paginado (data + links + meta).
     */
    protected function assertListado(TestResponse $response, array $campos, ?int $cuantos = null): void
    {
        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['*' => $campos],
                'links',
                'meta',
            ]);

        if ($cuantos !== null) {
            $this->assertCount($cuantos, $response->json('data'));
        }
    }
}
