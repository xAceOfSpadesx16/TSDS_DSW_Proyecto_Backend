<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobertura del módulo Auth.
 *
 * Casos verificados:
 *   1) Registro exitoso persiste el usuario y devuelve JWT estructurado.
 *   2) Login con credenciales válidas devuelve un token JWT Bearer.
 *   3) /api/auth/me con Bearer token retorna el usuario autenticado.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * El email debe ser único: validamos que duplicados sean rechazados (422).
     */
    public function test_register_creates_user_and_returns_jwt(): void
    {
        $payload = [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'super-secret',
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertCreated();
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
            'token',
            'token_type',
            'expires_in',
        ]);
        $response->assertJsonPath('user.email', 'ada@example.com');
        $response->assertJsonPath('token_type', 'bearer');
        $response->assertJsonPath('expires_in', 3600);

        $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::create([
            'name' => 'Existing',
            'email' => 'ada@example.com',
            'password' => 'whatever',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Duplicate',
            'email' => 'ada@example.com',
            'password' => 'super-secret',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_login_returns_jwt_for_valid_credentials(): void
    {
        User::create([
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'super-secret',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'ada@example.com',
            'password' => 'super-secret',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['user', 'token', 'token_type', 'expires_in']);
        $response->assertJsonPath('token_type', 'bearer');
    }

    public function test_login_rejects_invalid_password(): void
    {
        User::create([
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'super-secret',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'ada@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    public function test_me_returns_authenticated_user_with_bearer_token(): void
    {
        $user = User::create([
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'super-secret',
        ]);

        // Flujo real: login → tomar token → llamar a /me.
        $token = $this->postJson('/api/auth/login', [
            'email' => 'ada@example.com',
            'password' => 'super-secret',
        ])->json('token');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me');

        $response->assertOk();
        $response->assertJsonPath('user.email', 'ada@example.com');
        $this->assertEquals(
            $user->getKey(),
            $response->json('user.id'),
            'El id del usuario devuelto en /me debe coincidir con el usuario autenticado.',
        );
    }

    public function test_me_rejects_missing_token(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }
}