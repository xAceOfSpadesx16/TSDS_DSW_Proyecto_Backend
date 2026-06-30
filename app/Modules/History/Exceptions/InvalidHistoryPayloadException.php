<?php

namespace App\Modules\History\Exceptions;

use RuntimeException;

/**
 * Lanzada cuando una estrategia de validación rechaza el payload o el
 * resultado de un módulo de historial porque no cumple con el shape
 * esperado para ese módulo.
 *
 * Se mapea a HTTP 422 en bootstrap/app.php para mantener consistencia
 * con el resto de errores de validación de la API.
 *
 * El mensaje es la causa raíz legible para el cliente; los detalles
 * adicionales (campo que falló, valor recibido, valor esperado) viajan
 * en el array pasado al constructor para que el renderer los exponga
 * bajo la misma clave `errors` que usa ValidationException.
 */
class InvalidHistoryPayloadException extends RuntimeException
{
    /**
     * @param  array<string, array<int, string>>  $errors  Errores por campo
     *         con el mismo formato que ValidationException::errors(),
     *         para que el cliente pueda renderizar inline junto a los
     *         inputs del formulario.
     * @param  string  $message  Mensaje legible (causa raíz).
     */
    public function __construct(
        private readonly array $errors,
        string $message,
    ) {
        parent::__construct($message);
    }

    /**
     * Errores por campo, formato compatible con ValidationException.
     *
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}