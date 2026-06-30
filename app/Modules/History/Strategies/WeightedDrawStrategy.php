<?php

namespace App\Modules\History\Strategies;

use App\Modules\History\Exceptions\InvalidHistoryPayloadException;

/**
 * Estrategia de validación para el módulo de Sorteo Ponderado.
 *
 * Shape esperado (alineado con `WeightedDrawService.draw` del frontend):
 *
 *   payload: {
 *     entries:     [                            // participantes con sus pesos
 *       { name: string, weight: int|float },
 *       ...
 *     ],
 *     autoRemove?: bool,                        // si true, el ganador se quita de la lista
 *   }
 *
 *   result: {
 *     winner:      { name: string, weight: int|float },
 *     probability: int|float,                   // porcentaje redondeado por el frontend
 *   }
 */
class WeightedDrawStrategy implements HistoryStrategyInterface
{
    public function moduleName(): string
    {
        return 'weighted';
    }

    public function validate(array $payload, array $result): void
    {
        $errors = [];

        // --- payload.entries ---
        if (! array_key_exists('entries', $payload)) {
            $errors['payload.entries'] = ['El campo "entries" es obligatorio.'];
        } elseif (! is_array($payload['entries']) || $payload['entries'] === []) {
            $errors['payload.entries'] = ['El campo "entries" debe ser un arreglo no vacío.'];
        } else {
            foreach ($payload['entries'] as $i => $entry) {
                if (! is_array($entry)) {
                    $errors["payload.entries.$i"] = ['Cada entrada debe ser un objeto.'];
                    continue;
                }
                if (! isset($entry['name']) || ! is_string($entry['name']) || $entry['name'] === '') {
                    $errors["payload.entries.$i.name"] = ['Cada entrada debe tener un "name" string no vacío.'];
                }
                if (! array_key_exists('weight', $entry)) {
                    $errors["payload.entries.$i.weight"] = ['Cada entrada debe tener un "weight".'];
                } elseif (! is_int($entry['weight']) && ! is_float($entry['weight'])) {
                    $errors["payload.entries.$i.weight"] = ['El "weight" debe ser numérico (int o float).'];
                } elseif ($entry['weight'] <= 0) {
                    $errors["payload.entries.$i.weight"] = ['El "weight" debe ser positivo (> 0).'];
                }
            }
        }

        // --- payload.autoRemove (opcional) ---
        if (array_key_exists('autoRemove', $payload) && ! is_bool($payload['autoRemove'])) {
            $errors['payload.autoRemove'] = ['El campo "autoRemove" debe ser un booleano.'];
        }

        // --- result.winner ---
        if (! array_key_exists('winner', $result)) {
            $errors['result.winner'] = ['El campo "winner" es obligatorio.'];
        } elseif (! is_array($result['winner'])) {
            $errors['result.winner'] = ['El campo "winner" debe ser un objeto.'];
        } else {
            if (! isset($result['winner']['name']) || ! is_string($result['winner']['name']) || $result['winner']['name'] === '') {
                $errors['result.winner.name'] = ['"winner.name" debe ser un string no vacío.'];
            }
            if (! array_key_exists('weight', $result['winner'])) {
                $errors['result.winner.weight'] = ['"winner.weight" es obligatorio.'];
            } elseif (! is_int($result['winner']['weight']) && ! is_float($result['winner']['weight'])) {
                $errors['result.winner.weight'] = ['"winner.weight" debe ser numérico (int o float).'];
            }
        }

        // --- result.probability ---
        if (! array_key_exists('probability', $result)) {
            $errors['result.probability'] = ['El campo "probability" es obligatorio.'];
        } elseif (! is_int($result['probability']) && ! is_float($result['probability'])) {
            $errors['result.probability'] = ['El campo "probability" debe ser numérico (int o float).'];
        } elseif ($result['probability'] < 0 || $result['probability'] > 100) {
            $errors['result.probability'] = ['El campo "probability" debe estar entre 0 y 100.'];
        }

        if ($errors !== []) {
            throw new InvalidHistoryPayloadException(
                $errors,
                'El payload o el resultado del módulo "weighted" no cumplen con el shape esperado.',
            );
        }
    }
}