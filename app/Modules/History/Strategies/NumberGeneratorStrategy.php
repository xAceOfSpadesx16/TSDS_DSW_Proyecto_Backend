<?php

namespace App\Modules\History\Strategies;

use App\Modules\History\Exceptions\InvalidHistoryPayloadException;

/**
 * Estrategia de validación para el módulo de Generador de Números.
 *
 * Shape esperado (alineado con `DiceService.generateUniqueNumbers` y
 * `DiceService.generateNumbers` del frontend):
 *
 *   payload: {
 *     count:  int >= 1,                     // cuántos números generar
 *     min:    int,                          // extremo inferior del rango (inclusivo)
 *     max:    int >= min,                   // extremo superior del rango (inclusivo)
 *     unique: bool,                         // true => no repetir; false => admite repetidos
 *   }
 *
 *   result: {
 *     numbers: int[],                       // los números generados (ordenados ascendente por el frontend)
 *   }
 */
class NumberGeneratorStrategy implements HistoryStrategyInterface
{
    public function moduleName(): string
    {
        return 'numbers';
    }

    public function validate(array $payload, array $result): void
    {
        $errors = [];

        // --- payload ---
        if (! array_key_exists('count', $payload)) {
            $errors['payload.count'] = ['El campo "count" es obligatorio.'];
        } elseif (! is_int($payload['count']) || $payload['count'] < 1) {
            $errors['payload.count'] = ['El campo "count" debe ser un entero >= 1.'];
        }

        if (! array_key_exists('min', $payload)) {
            $errors['payload.min'] = ['El campo "min" es obligatorio.'];
        } elseif (! is_int($payload['min'])) {
            $errors['payload.min'] = ['El campo "min" debe ser un entero.'];
        }

        if (! array_key_exists('max', $payload)) {
            $errors['payload.max'] = ['El campo "max" es obligatorio.'];
        } elseif (! is_int($payload['max'])) {
            $errors['payload.max'] = ['El campo "max" debe ser un entero.'];
        } elseif (
            array_key_exists('min', $payload)
            && is_int($payload['min'])
            && $payload['max'] < $payload['min']
        ) {
            $errors['payload.max'] = ['El campo "max" debe ser >= "min".'];
        }

        if (! array_key_exists('unique', $payload)) {
            $errors['payload.unique'] = ['El campo "unique" es obligatorio.'];
        } elseif (! is_bool($payload['unique'])) {
            $errors['payload.unique'] = ['El campo "unique" debe ser un booleano.'];
        }

        // --- result ---
        if (! array_key_exists('numbers', $result)) {
            $errors['result.numbers'] = ['El campo "numbers" es obligatorio.'];
        } elseif (! is_array($result['numbers'])) {
            $errors['result.numbers'] = ['El campo "numbers" debe ser un arreglo.'];
        } else {
            foreach ($result['numbers'] as $i => $n) {
                if (! is_int($n)) {
                    $errors["result.numbers.$i"] = ['Cada elemento de "numbers" debe ser un entero.'];
                }
            }
        }

        if ($errors !== []) {
            throw new InvalidHistoryPayloadException(
                $errors,
                'El payload o el resultado del módulo "numbers" no cumplen con el shape esperado.',
            );
        }
    }
}