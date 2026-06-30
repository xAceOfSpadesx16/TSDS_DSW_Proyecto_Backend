<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Cobertura end-to-end del Patrón Strategy dentro del módulo History.
 *
 * Verifica que cada `module_name` conocido:
 *   - persiste cuando payload y result cumplen el shape esperado,
 *   - devuelve 422 con errores por campo cuando NO lo cumplen,
 *   - devuelve 422 cuando el módulo no está soportado.
 *
 * Estos tests complementan {@see HistoryTest} (que cubre Auth, JWT
 * e isolation de dueño) y los unit tests de cada estrategia (que
 * cubren el shape en aislamiento).
 */
class HistoryStrategiesTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'super-secret',
        ]);

        $this->token = JWTAuth::fromUser($this->user);
    }

    /**
     * @return array<string, array{0: string, 1: array<string,mixed>, 2: array<string,mixed>}>
     */
    public static function validPayloadsProvider(): array
    {
        return [
            'dice' => [
                'dice',
                ['sides' => 6, 'modifier' => 2],
                ['rolls' => [5], 'modifier' => 2, 'total' => 7],
            ],
            'numbers' => [
                'numbers',
                ['count' => 3, 'min' => 1, 'max' => 10, 'unique' => true],
                ['numbers' => [2, 7, 9]],
            ],
            'roulette' => [
                'roulette',
                ['options' => [
                    ['label' => 'Ana', 'color' => '#ff0000'],
                    ['label' => 'Beto', 'color' => '#00ff00'],
                ]],
                ['winner' => ['label' => 'Ana', 'color' => '#ff0000']],
            ],
            'teams' => [
                'teams',
                ['participants' => ['Ana', 'Beto', 'Cami'], 'mode' => 'count', 'value' => 2],
                ['teams' => [['Ana'], ['Beto', 'Cami']], 'colors' => ['#6c5ce7', '#00cec9']],
            ],
            'weighted' => [
                'weighted',
                ['entries' => [['name' => 'Ana', 'weight' => 5]]],
                ['winner' => ['name' => 'Ana', 'weight' => 5], 'probability' => 100],
            ],
        ];
    }

    #[DataProvider('validPayloadsProvider')]
    public function test_store_persists_valid_payload_for_each_module(
        string $moduleName,
        array $payload,
        array $result,
    ): void {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/history', [
                'module_name' => $moduleName,
                'action' => 'run',
                'payload' => $payload,
                'result' => $result,
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.module_name', $moduleName);
        $response->assertJsonPath('data.payload', $payload);
        $response->assertJsonPath('data.result', $result);
        $response->assertJsonPath('data.user_id', $this->user->getKey());

        $this->assertDatabaseHas('history_records', [
            'user_id' => $this->user->getKey(),
            'module_name' => $moduleName,
        ]);
    }

    public function test_store_returns_422_when_payload_is_invalid_for_module(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/history', [
                'module_name' => 'dice',
                'action' => 'roll',
                // Falta `sides` y `modifier`, así que la estrategia rechaza.
                'payload' => ['count' => 1],
                'result' => ['rolls' => [4], 'total' => 4],
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors' => ['payload.sides', 'payload.modifier', 'result.modifier'],
        ]);
    }

    public function test_store_returns_422_when_module_is_unsupported(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/history', [
                'module_name' => 'MysteryBox',
                'action' => 'roll',
                'payload' => ['anything' => 'goes'],
                'result' => ['winner' => 'nothing'],
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath(
            'message',
            "El módulo 'MysteryBox' no está soportado por el sistema de historial.",
        );
    }

    public function test_store_returns_422_for_wrong_module_payload_mismatch(): void
    {
        // El cliente dice "dice" pero envía un payload de teams.
        // La estrategia del módulo `dice` debe rechazarlo.
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/history', [
                'module_name' => 'dice',
                'action' => 'roll',
                'payload' => ['participants' => ['Ana', 'Beto']], // shape de teams
                'result' => ['rolls' => [1], 'modifier' => 0, 'total' => 1],
            ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('payload.sides', $response->json('errors'));
    }

    public function test_no_history_record_persists_when_strategy_rejects(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/history', [
                'module_name' => 'roulette',
                'action' => 'spin',
                'payload' => [], // vacío → la estrategia rechaza
                'result' => [],
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('history_records', 0);
    }
}