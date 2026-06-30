<?php

namespace App\Modules\History\Strategies;

use App\Modules\History\Exceptions\InvalidHistoryPayloadException;

/**
 * Estrategia de validación para el módulo de Dados.
 *
 * Shape esperado (alineado con `DiceService.rollDice` y
 * `DiceService.rollMultiple` del frontend):
 *
 *   payload: {
 *     sides:    int >= 2,                   // caras del dado
 *     modifier: int,                        // modificador (+/-) aplicado al total
 *     count?:   int >= 1,                   // cantidad de dados (default 1)
 *     formula?: string,                     // notación RPG (ej. "2d6+3") opcional
 *   }
 *
 *   result: {
 *     rolls:    int[],                      // una entrada por dado tirado
 *     total:    int,                        // suma de rolls + modifier
 *     modifier: int,                        // eco del modificador (redundancia útil para auditoría)
 *     formula?: string,                     // eco opcional de la fórmula RPG
 *   }
 */
class DiceRollerStrategy implements HistoryStrategyInterface
{
    public function moduleName(): string
    {
        return 'dice';
    }

    public function validate(array $payload, array $result): void
    {
        $errors = [];

        // --- payload ---
        if (! array_key_exists('sides', $payload)) {
            $errors['payload.sides'] = ['El campo "sides" es obligatorio.'];
        } elseif (! is_int($payload['sides']) || $payload['sides'] < 2) {
            $errors['payload.sides'] = ['El campo "sides" debe ser un entero >= 2.'];
        }

        if (! array_key_exists('modifier', $payload)) {
            $errors['payload.modifier'] = ['El campo "modifier" es obligatorio.'];
        } elseif (! is_int($payload['modifier'])) {
            $errors['payload.modifier'] = ['El campo "modifier" debe ser un entero.'];
        }

        if (array_key_exists('count', $payload)) {
            if (! is_int($payload['count']) || $payload['count'] < 1) {
                $errors['payload.count'] = ['El campo "count" debe ser un entero >= 1.'];
            }
        }

        if (array_key_exists('formula', $payload) && ! is_string($payload['formula'])) {
            $errors['payload.formula'] = ['El campo "formula" debe ser un string.'];
        }

        // --- result ---
        if (! array_key_exists('rolls', $result)) {
            $errors['result.rolls'] = ['El campo "rolls" es obligatorio.'];
        } elseif (! is_array($result['rolls']) || $result['rolls'] === []) {
            $errors['result.rolls'] = ['El campo "rolls" debe ser un arreglo no vacío.'];
        } else {
            foreach ($result['rolls'] as $i => $roll) {
                if (! is_int($roll)) {
                    $errors["result.rolls.$i"] = ['Cada elemento de "rolls" debe ser un entero.'];
                }
            }
        }

        if (! array_key_exists('total', $result)) {
            $errors['result.total'] = ['El campo "total" es obligatorio.'];
        } elseif (! is_int($result['total'])) {
            $errors['result.total'] = ['El campo "total" debe ser un entero.'];
        }

        if (! array_key_exists('modifier', $result)) {
            $errors['result.modifier'] = ['El campo "modifier" es obligatorio en el resultado.'];
        } elseif (! is_int($result['modifier'])) {
            $errors['result.modifier'] = ['El campo "result.modifier" debe ser un entero.'];
        }

        if ($errors !== []) {
            throw new InvalidHistoryPayloadException(
                $errors,
                'El payload o el resultado del módulo "dice" no cumplen con el shape esperado.',
            );
        }
    }
}