<?php

namespace App\Modules\Legal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Controlador delgado del módulo Legal.
 * Toda la fuente de los textos vive en {@see LegalService}.
 */
class LegalController extends Controller
{
    public function __construct(private readonly LegalService $legal)
    {
    }

    /**
     * GET /api/legal/disclaimer
     */
    public function disclaimer(): JsonResponse
    {
        return response()->json($this->legal->disclaimer());
    }
}