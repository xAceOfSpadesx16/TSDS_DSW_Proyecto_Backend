<?php

namespace Tests\Unit\History;

use App\Modules\History\Exceptions\InvalidHistoryPayloadException;
use App\Modules\History\Strategies\NumberGeneratorStrategy;
use PHPUnit\Framework\TestCase;

class NumberGeneratorStrategyTest extends TestCase
{
    private NumberGeneratorStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new NumberGeneratorStrategy();
    }

    public function test_module_name_is_numbers(): void
    {
        $this->assertSame('numbers', $this->strategy->moduleName());
    }

    public function test_validate_accepts_unique_numbers_draw(): void
    {
        $this->strategy->validate(
            ['count' => 5, 'min' => 1, 'max' => 90, 'unique' => true],
            ['numbers' => [3, 17, 42, 68, 89]],
        );

        $this->expectNotToPerformAssertions();
    }

    public function test_validate_accepts_non_unique_numbers_draw(): void
    {
        $this->strategy->validate(
            ['count' => 3, 'min' => 1, 'max' => 6, 'unique' => false],
            ['numbers' => [4, 4, 2]],
        );

        $this->expectNotToPerformAssertions();
    }

    public function test_validate_rejects_min_greater_than_max(): void
    {
        try {
            $this->strategy->validate(
                ['count' => 1, 'min' => 10, 'max' => 5, 'unique' => false],
                ['numbers' => []],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('payload.max', $e->errors());
        }
    }

    public function test_validate_rejects_non_boolean_unique(): void
    {
        try {
            $this->strategy->validate(
                ['count' => 1, 'min' => 1, 'max' => 5, 'unique' => 'yes'],
                ['numbers' => [3]],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('payload.unique', $e->errors());
        }
    }

    public function test_validate_rejects_count_below_one(): void
    {
        try {
            $this->strategy->validate(
                ['count' => 0, 'min' => 1, 'max' => 5, 'unique' => false],
                ['numbers' => []],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('payload.count', $e->errors());
        }
    }

    public function test_validate_rejects_non_integer_numbers(): void
    {
        try {
            $this->strategy->validate(
                ['count' => 1, 'min' => 1, 'max' => 5, 'unique' => false],
                ['numbers' => ['one']],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('result.numbers.0', $e->errors());
        }
    }
}