<?php

namespace App\Modules\History;

use Modularize\Module;

/**
 * Módulo History.
 *
 * El módulo participa del auto-bootstrapping de rutas configurables,
 * migraciones, modelos y recursos que sirve el paquete, PERO las
 * rutas concretas se declaran de forma explícita en routes/api.php
 * para respetar el contrato del plan:
 *   - POST /api/history    → store (auth:api)
 *   - GET  /api/histories  → index (auth:api)
 *
 * Se omite bootApiRoutes() para evitar el registro automático de
 * las rutas RESTful estándar (que no coinciden con ese contrato).
 */
class HistoryModule extends Module
{
    public function boot()
    {
        parent::boot();
    }
}