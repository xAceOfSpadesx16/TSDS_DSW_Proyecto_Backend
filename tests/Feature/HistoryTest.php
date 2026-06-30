<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\History\Models\HistoryRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Cobertura del módulo History.
 *
 * Casos verificados:
 *   1) POST /api/history sin token devuelve 401.
 *   2) Persistencia: con token válido, el registro se guarda y
 *      se asocia al user_id del JWT (descarta inyecciones de user_id).
 *   3) Validación base (FormRequest): payload/result deben ser arrays,
 *      no strings sueltos.
 *   4) GET /api/histories solo devuelve los registros del usuario
 *      autenticado (aislamiento entre usuarios).
 *   5) El query param `per_page` se clampa a 1..100.
 *
 * Los shapes concretos de payload/result por módulo y la validación
 * semántica viven en {@see \Tests\Feature\HistoryStrategiesTest}.
 */
class HistoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Payload/result de un dado válido para el módulo `dice`,
     * alineado con DiceRollerStrategy. Reutilizado por varios tests.
     *
     * @return array{0: array<string,mixed>, 1: array<string,mixed>}
     */
    private function validDiceFixture(): array
    {
        return [
            ['sides' => 6, 'modifier' => 0],
            ['rolls' => [4], 'modifier' => 0, 'total' => 4],
        ];
    }

    private function makeUserAndToken(): array
    {
        $user = User::create([
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'super-secret',
        ]);

        $token = JWTAuth::fromUser($user);

        return [$user, $token];
    }

    /**
     * Sin Bearer token el middleware auth:api debe rechazar con 401.
     */
    public function test_store_rejects_request_without_token(): void
    {
        $this->postJson('/api/history', [
            'module_name' => 'dice',
            'action' => 'roll',
            'payload' => ['sides' => 6, 'modifier' => 0],
            'result' => ['rolls' => [4], 'modifier' => 0, 'total' => 4],
        ])->assertStatus(401);
    }

    /**
     * Con token válido el registro se persiste y se asocia al user_id del JWT.
     * Se valida además que un user_id inyectado por el cliente es descartado
     * (la fuente de verdad siempre es el token).
     */
    public function test_store_persists_record_with_token_user_id(): void
    {
        [$user, $token] = $this->makeUserAndToken();
        [$payload, $result] = $this->validDiceFixture();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/history', [
                'module_name' => 'dice',
                'action' => 'roll',
                'payload' => $payload,
                'result' => $result,
                'user_id' => 9999, // intento de impersonación
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.user_id', $user->getKey());
        $response->assertJsonPath('data.module_name', 'dice');

        $this->assertDatabaseHas('history_records', [
            'user_id' => $user->getKey(),
            'module_name' => 'dice',
            'action' => 'roll',
        ]);
        $this->assertDatabaseMissing('history_records', [
            'user_id' => 9999,
        ]);
    }

    /**
     * payload y result deben ser arrays JSON, no strings sueltos.
     * Esta validación es la del FormRequest (estructura básica); las
     * validaciones semánticas por módulo viven en las estrategias.
     */
    public function test_store_rejects_non_array_payload_or_result(): void
    {
        [, $token] = $this->makeUserAndToken();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/history', [
                'module_name' => 'dice',
                'action' => 'roll',
                'payload' => 'not-an-array',
                'result' => ['rolls' => [4], 'modifier' => 0, 'total' => 4],
            ])->assertStatus(422)
              ->assertJsonValidationErrors(['payload']);
    }

    /**
     * GET /api/histories devuelve únicamente los registros del usuario
     * autenticado, no los de terceros.
     */
    public function test_index_returns_only_authenticated_user_history(): void
    {
        [$ada, $adaToken] = $this->makeUserAndToken();

        $other = User::create([
            'name' => 'Other',
            'email' => 'other@example.com',
            'password' => 'super-secret',
        ]);

        HistoryRecord::create([
            'user_id' => $ada->getKey(),
            'module_name' => 'dice',
            'action' => 'roll',
            'payload' => ['sides' => 6, 'modifier' => 0],
            'result' => ['rolls' => [3], 'modifier' => 0, 'total' => 3],
        ]);

        HistoryRecord::create([
            'user_id' => $other->getKey(),
            'module_name' => 'roulette',
            'action' => 'spin',
            'payload' => ['options' => [['label' => 'a', 'color' => '#000']]],
            'result' => ['winner' => ['label' => 'a', 'color' => '#000']],
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$adaToken}")
            ->getJson('/api/histories');

        $response->assertOk();
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.user_id', $ada->getKey());
    }

    public function test_index_rejects_request_without_token(): void
    {
        $this->getJson('/api/histories')->assertStatus(401);
    }

    /**
     * El query param `per_page` controla el tamaño de página y se clampa
     * a un máximo de 100. Valores fuera de rango caen al default (20).
     */
    public function test_index_respects_per_page_query_param(): void
    {
        [$ada, $adaToken] = $this->makeUserAndToken();

        // Creamos 25 registros para poder distinguir entre 20 y 5.
        for ($i = 0; $i < 25; $i++) {
            HistoryRecord::create([
                'user_id' => $ada->getKey(),
                'module_name' => 'dice',
                'action' => 'roll',
                'payload' => ['sides' => 6, 'modifier' => $i],
                'result' => ['rolls' => [$i], 'modifier' => $i, 'total' => $i],
            ]);
        }

        // per_page=5 → 5 elementos por página
        $this->withHeader('Authorization', "Bearer {$adaToken}")
            ->getJson('/api/histories?per_page=5')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonCount(5, 'data');

        // per_page=999 → se clampa a 100, pero solo hay 25 totales.
        $this->withHeader('Authorization', "Bearer {$adaToken}")
            ->getJson('/api/histories?per_page=999')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100)
            ->assertJsonCount(25, 'data');
    }
}