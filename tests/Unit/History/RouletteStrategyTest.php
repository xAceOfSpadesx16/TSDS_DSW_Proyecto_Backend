<?php

namespace Tests\Unit\History;

use App\Modules\History\Exceptions\InvalidHistoryPayloadException;
use App\Modules\History\Strategies\RouletteStrategy;
use PHPUnit\Framework\TestCase;

class RouletteStrategyTest extends TestCase
{
    private RouletteStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new RouletteStrategy();
    }

    public function test_module_name_is_roulette(): void
    {
        $this->assertSame('roulette', $this->strategy->moduleName());
    }

    public function test_validate_accepts_valid_spin(): void
    {
        $this->strategy->validate(
            ['options' => [
                ['label' => 'Ana', 'color' => '#ff0000'],
                ['label' => 'Beto', 'color' => '#00ff00'],
            ]],
            ['winner' => ['label' => 'Ana', 'color' => '#ff0000']],
        );

        $this->expectNotToPerformAssertions();
    }

    public function test_validate_rejects_empty_options(): void
    {
        try {
            $this->strategy->validate(
                ['options' => []],
                ['winner' => ['label' => 'x', 'color' => '#000']],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('payload.options', $e->errors());
        }
    }

    public function test_validate_rejects_option_without_label(): void
    {
        try {
            $this->strategy->validate(
                ['options' => [['color' => '#ff0000']]],
                ['winner' => ['label' => 'x', 'color' => '#000']],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('payload.options.0.label', $e->errors());
        }
    }

    public function test_validate_rejects_option_without_color(): void
    {
        try {
            $this->strategy->validate(
                ['options' => [['label' => 'Ana']]],
                ['winner' => ['label' => 'Ana', 'color' => '#000']],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('payload.options.0.color', $e->errors());
        }
    }

    public function test_validate_rejects_missing_winner(): void
    {
        try {
            $this->strategy->validate(
                ['options' => [['label' => 'Ana', 'color' => '#ff0000']]],
                [],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('result.winner', $e->errors());
        }
    }

    public function test_validate_rejects_winner_with_empty_label(): void
    {
        try {
            $this->strategy->validate(
                ['options' => [['label' => 'Ana', 'color' => '#ff0000']]],
                ['winner' => ['label' => '', 'color' => '#ff0000']],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('result.winner.label', $e->errors());
        }
    }
}