<?php

namespace App\Modules\History\Exceptions;

use RuntimeException;

/**
 * Lanzada por HistoryStrategyFactory cuando el `module_name` enviado
 * por el cliente no tiene una estrategia registrada en el factory.
 *
 * Se mapea a HTTP 422 en bootstrap/app.php: aunque la raíz es un
 * discriminador desconocido, semánticamente sigue siendo "input no
 * procesable por el backend" (consistente con el resto de errores 422).
 */
class UnsupportedModuleException extends RuntimeException
{
    /**
     * @param  string  $moduleName  Valor de `module_name` recibido.
     */
    public static function forModule(string $moduleName): self
    {
        return new self(sprintf(
            "El módulo '%s' no está soportado por el sistema de historial.",
            $moduleName,
        ));
    }
}