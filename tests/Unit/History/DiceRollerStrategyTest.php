<?php

namespace Tests\Unit\History;

use App\Modules\History\Exceptions\InvalidHistoryPayloadException;
use App\Modules\History\Strategies\DiceRollerStrategy;
use PHPUnit\Framework\TestCase;

/**
 * Cubre DiceRollerStrategy: shape válido de un dado simple y de una
 * tirada múltiple con fórmula RPG, más los casos de error más comunes
 * (campos faltantes, tipos incorrectos, valores fuera de rango).
 */
class DiceRollerStrategyTest extends TestCase
{
    private DiceRollerStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new DiceRollerStrategy();
    }

    public function test_module_name_is_dice(): void
    {
        $this->assertSame('dice', $this->strategy->moduleName());
    }

    public function test_validate_accepts_single_die_roll(): void
    {
        $this->strategy->validate(
            ['sides' => 6, 'modifier' => 0],
            ['rolls' => [4], 'modifier' => 0, 'total' => 4],
        );

        $this->expectNotToPerformAssertions();
    }

    public function test_validate_accepts_multiple_dice_with_formula(): void
    {
        $this->strategy->validate(
            ['sides' => 6, 'modifier' => 3, 'count' => 2, 'formula' => '2d6+3'],
            ['rolls' => [5, 2], 'modifier' => 3, 'total' => 10, 'formula' => '2d6+3'],
        );

        $this->expectNotToPerformAssertions();
    }

    public function test_validate_rejects_missing_sides(): void
    {
        try {
            $this->strategy->validate(
                ['modifier' => 0],
                ['rolls' => [1], 'modifier' => 0, 'total' => 1],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('payload.sides', $e->errors());
        }
    }

    public function test_validate_rejects_sides_below_two(): void
    {
        try {
            $this->strategy->validate(
                ['sides' => 1, 'modifier' => 0],
                ['rolls' => [1], 'modifier' => 0, 'total' => 1],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('payload.sides', $e->errors());
        }
    }

    public function test_validate_rejects_non_array_rolls(): void
    {
        try {
            $this->strategy->validate(
                ['sides' => 6, 'modifier' => 0],
                ['rolls' => 'not-array', 'modifier' => 0, 'total' => 0],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('result.rolls', $e->errors());
        }
    }

    public function test_validate_rejects_empty_rolls_array(): void
    {
        try {
            $this->strategy->validate(
                ['sides' => 6, 'modifier' => 0],
                ['rolls' => [], 'modifier' => 0, 'total' => 0],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('result.rolls', $e->errors());
        }
    }

    public function test_validate_rejects_non_integer_total(): void
    {
        try {
            $this->strategy->validate(
                ['sides' => 6, 'modifier' => 0],
                ['rolls' => [4], 'modifier' => 0, 'total' => '4'],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('result.total', $e->errors());
        }
    }

    public function test_validate_rejects_missing_result_modifier(): void
    {
        try {
            $this->strategy->validate(
                ['sides' => 6, 'modifier' => 0],
                ['rolls' => [4], 'total' => 4],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('result.modifier', $e->errors());
        }
    }

    public function test_validate_accumulates_multiple_errors(): void
    {
        try {
            $this->strategy->validate(
                ['modifier' => 'oops'], // sides faltante, modifier de tipo incorrecto
                ['rolls' => 'nope', 'total' => null], // rolls y total mal
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('payload.sides', $errors);
            $this->assertArrayHasKey('payload.modifier', $errors);
            $this->assertArrayHasKey('result.rolls', $errors);
            $this->assertArrayHasKey('result.modifier', $errors);
            $this->assertArrayHasKey('result.total', $errors);
        }
    }
}