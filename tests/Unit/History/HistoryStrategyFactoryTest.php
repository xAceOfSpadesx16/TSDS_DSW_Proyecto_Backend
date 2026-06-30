<?php

namespace Tests\Unit\History;

use App\Modules\History\Exceptions\UnsupportedModuleException;
use App\Modules\History\Strategies\DiceRollerStrategy;
use App\Modules\History\Strategies\HistoryStrategyFactory;
use App\Modules\History\Strategies\HistoryStrategyInterface;
use App\Modules\History\Strategies\NumberGeneratorStrategy;
use App\Modules\History\Strategies\RouletteStrategy;
use App\Modules\History\Strategies\TeamSplitterStrategy;
use App\Modules\History\Strategies\WeightedDrawStrategy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Cubre la resolución del Factory: cada `module_name` conocido mapea
 * a la clase concreta correcta, y un módulo desconocido produce la
 * excepción adecuada con un mensaje identificable.
 */
class HistoryStrategyFactoryTest extends TestCase
{
    private HistoryStrategyFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new HistoryStrategyFactory();
    }

    /**
     * @return array<string, array{0: string, 1: class-string<HistoryStrategyInterface>, 2: string}>
     */
    public static function supportedModuleProvider(): array
    {
        return [
            'dice' => ['dice', DiceRollerStrategy::class, 'dice'],
            'numbers' => ['numbers', NumberGeneratorStrategy::class, 'numbers'],
            'roulette' => ['roulette', RouletteStrategy::class, 'roulette'],
            'teams' => ['teams', TeamSplitterStrategy::class, 'teams'],
            'weighted' => ['weighted', WeightedDrawStrategy::class, 'weighted'],
        ];
    }

    #[DataProvider('supportedModuleProvider')]
    public function test_make_resolves_each_supported_module(
        string $moduleName,
        string $expectedClass,
        string $expectedModuleName,
    ): void {
        $strategy = $this->factory->make($moduleName);

        $this->assertInstanceOf($expectedClass, $strategy);
        $this->assertInstanceOf(HistoryStrategyInterface::class, $strategy);
        $this->assertSame($expectedModuleName, $strategy->moduleName());
    }

    public function test_make_throws_for_unsupported_module(): void
    {
        $this->expectException(UnsupportedModuleException::class);
        $this->expectExceptionMessage("El módulo 'MysteryBox' no está soportado");

        $this->factory->make('MysteryBox');
    }

    public function test_make_is_case_sensitive(): void
    {
        $this->expectException(UnsupportedModuleException::class);

        // Las claves del factory son lowercase; cualquier variante debe fallar.
        $this->factory->make('Dice');
    }

    public function test_supported_modules_lists_all_known_keys(): void
    {
        $this->assertSame(
            ['dice', 'numbers', 'roulette', 'teams', 'weighted'],
            $this->factory->supportedModules(),
        );
    }
}