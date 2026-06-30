<?php

namespace Tests\Unit\History;

use App\Modules\History\Exceptions\InvalidHistoryPayloadException;
use App\Modules\History\Strategies\TeamSplitterStrategy;
use PHPUnit\Framework\TestCase;

class TeamSplitterStrategyTest extends TestCase
{
    private TeamSplitterStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new TeamSplitterStrategy();
    }

    public function test_module_name_is_teams(): void
    {
        $this->assertSame('teams', $this->strategy->moduleName());
    }

    public function test_validate_accepts_split_by_count(): void
    {
        $this->strategy->validate(
            ['participants' => ['Ana', 'Beto', 'Cami', 'Dani'], 'mode' => 'count', 'value' => 2],
            [
                'teams' => [['Ana', 'Cami'], ['Beto', 'Dani']],
                'colors' => ['#6c5ce7', '#00cec9'],
            ],
        );

        $this->expectNotToPerformAssertions();
    }

    public function test_validate_accepts_split_by_size_with_exclusions(): void
    {
        $this->strategy->validate(
            [
                'participants' => ['Ana', 'Beto', 'Cami'],
                'mode' => 'size',
                'value' => 2,
                'exclusions' => [['Ana', 'Beto']],
            ],
            [
                'teams' => [['Ana'], ['Beto'], ['Cami']],
                'colors' => ['#6c5ce7', '#00cec9', '#fd79a8'],
            ],
        );

        $this->expectNotToPerformAssertions();
    }

    public function test_validate_rejects_empty_participants(): void
    {
        try {
            $this->strategy->validate(
                ['participants' => [], 'mode' => 'count', 'value' => 1],
                ['teams' => [[]], 'colors' => ['#fff']],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('payload.participants', $e->errors());
        }
    }

    public function test_validate_rejects_invalid_mode(): void
    {
        try {
            $this->strategy->validate(
                ['participants' => ['Ana'], 'mode' => 'random', 'value' => 1],
                ['teams' => [['Ana']], 'colors' => ['#fff']],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('payload.mode', $e->errors());
        }
    }

    public function test_validate_rejects_value_below_one(): void
    {
        try {
            $this->strategy->validate(
                ['participants' => ['Ana'], 'mode' => 'count', 'value' => 0],
                ['teams' => [['Ana']], 'colors' => ['#fff']],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('payload.value', $e->errors());
        }
    }

    public function test_validate_rejects_exclusion_pair_of_wrong_length(): void
    {
        try {
            $this->strategy->validate(
                [
                    'participants' => ['Ana', 'Beto'],
                    'mode' => 'count',
                    'value' => 2,
                    'exclusions' => [['Ana']], // debería tener 2 elementos
                ],
                ['teams' => [['Ana'], ['Beto']], 'colors' => ['#fff', '#000']],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('payload.exclusions.0', $e->errors());
        }
    }

    public function test_validate_rejects_empty_teams(): void
    {
        try {
            $this->strategy->validate(
                ['participants' => ['Ana'], 'mode' => 'count', 'value' => 1],
                ['teams' => [], 'colors' => []],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('result.teams', $e->errors());
        }
    }

    public function test_validate_rejects_empty_colors(): void
    {
        try {
            $this->strategy->validate(
                ['participants' => ['Ana'], 'mode' => 'count', 'value' => 1],
                ['teams' => [['Ana']], 'colors' => ['']],
            );
            $this->fail('Expected InvalidHistoryPayloadException');
        } catch (InvalidHistoryPayloadException $e) {
            $this->assertArrayHasKey('result.colors.0', $e->errors());
        }
    }
}