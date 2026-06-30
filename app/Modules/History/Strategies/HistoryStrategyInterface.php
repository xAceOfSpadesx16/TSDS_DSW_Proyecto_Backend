<?php

namespace App\Modules\History\Strategies;

use App\Modules\History\Exceptions\InvalidHistoryPayloadException;

/**
 * Contrato del Patrón Strategy aplicado al historial.
 *
 * Cada implementación valida la estructura interna de los arrays
 * `payload` y `result` que envía el cliente para un módulo concreto
 * (dados, ruleta, equipos, etc.). Mantener este contrato permite:
 *   - agregar un nuevo módulo creando una sola clase sin tocar el
 *     resto del módulo History (Open/Closed),
 *   - testear cada estrategia de forma aislada sin levantar HTTP,
 *   - centralizar el shape esperado en una sola fuente de verdad
 *     (la estrategia) en lugar de dispersarlo en FormRequests o
 *     condicionales en el controlador.
 */
interface HistoryStrategyInterface
{
    /**
     * Identificador único del módulo que la estrategia sabe validar.
     *
     * Es la clave que el cliente envía en `module_name` y la que
     * HistoryStrategyFactory usa para resolver la instancia.
     *
     * Convención: kebab-case / snake_case en minúsculas
     * (ej. `'dice'`, `'numbers'`, `'roulette'`, `'teams'`, `'weighted'`).
     */
    public function moduleName(): string;

    /**
     * Valida que `payload` y `result` cumplan con el shape esperado
     * para este módulo antes de persistir.
     *
     * @param  array<string, mixed>  $payload  Datos de entrada del sorteo.
     * @param  array<string, mixed>  $result   Resultado calculado por el cliente.
     *
     * @throws InvalidHistoryPayloadException Cuando algún campo falta,
     *         tiene el tipo equivocado o viola una regla del módulo.
     *         El array `errors` de la excepción usa el mismo formato
     *         que ValidationException para que el renderer de la API
     *         lo exponga de forma consistente al cliente.
     */
    public function validate(array $payload, array $result): void;
}