<?php

namespace App\Modules\History\Strategies;

use App\Modules\History\Exceptions\UnsupportedModuleException;

/**
 * Fábrica que resuelve la estrategia de validación adecuada para un
 * `module_name` enviado por el cliente.
 *
 * Mantiene un mapa explícito de claves soportadas a sus clases; agregar
 * un módulo nuevo = agregar una línea acá + crear la clase. No hay
 * magia ni service-locator: la dependencia entre el factory y las
 * strategies es por composición directa, lo cual conserva el tipado
 * estático y la trazabilidad de qué módulos existen.
 *
 * Esta clase se inyecta en HistoryService vía el constructor estándar
 * de Laravel (no necesita service provider propio porque vive dentro
 * del namespace del módulo).
 */
class HistoryStrategyFactory
{
    /**
     * Mapa inmutable de module_name → clase de estrategia.
     *
     * Las claves son las que el cliente debe enviar exactamente en
     * `module_name`. Cualquier valor fuera de este mapa produce
     * UnsupportedModuleException.
     *
     * @var array<string, class-string<HistoryStrategyInterface>>
     */
    private const STRATEGIES = [
        'dice' => DiceRollerStrategy::class,
        'numbers' => NumberGeneratorStrategy::class,
        'roulette' => RouletteStrategy::class,
        'teams' => TeamSplitterStrategy::class,
        'weighted' => WeightedDrawStrategy::class,
    ];

    /**
     * Resuelve la estrategia correspondiente al `module_name`.
     *
     * @throws UnsupportedModuleException Cuando el módulo no está registrado.
     */
    public function make(string $moduleName): HistoryStrategyInterface
    {
        if (! isset(self::STRATEGIES[$moduleName])) {
            throw UnsupportedModuleException::forModule($moduleName);
        }

        /** @var HistoryStrategyInterface $strategy */
        $strategy = app(self::STRATEGIES[$moduleName]);

        return $strategy;
    }

    /**
     * Lista los module_name soportados.
     *
     * Útil para documentación OpenAPI y para responder a clientes que
     * preguntan qué módulos pueden enviar.
     *
     * @return array<int, string>
     */
    public function supportedModules(): array
    {
        return array_keys(self::STRATEGIES);
    }
}