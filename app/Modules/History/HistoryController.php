<?php

namespace App\Modules\History;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\History\Requests\CreateRequest;
use App\Modules\History\Resources\HistoryRecordResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador delgado del historial.
 * Toda la lógica vive en {@see HistoryService}.
 */
class HistoryController extends Controller
{
    public function __construct(private readonly HistoryService $history)
    {
    }

    /**
     * POST /api/history  (auth:api)
     */
    public function store(CreateRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $record = $this->history->store($user, $request->validated());

        return response()->json([
            'data' => new HistoryRecordResource($record),
        ], 201);
    }

    /**
     * GET /api/histories  (auth:api)
     *
     * Query params:
     *   - page     (int, default 1)  página a recuperar
     *   - per_page (int, default 20) elementos por página (1-100)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->resolvePerPage($request);

        /** @var User $user */
        $user = $request->user();
        $page = $this->history->paginateForUser($user, $perPage);

        return response()->json([
            'data' => HistoryRecordResource::collection($page->items()),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /**
     * Resuelve y sanea `per_page` desde el query string.
     * Rango válido: 1..100. Default: 20. Cualquier valor inválido cae al default.
     */
    private function resolvePerPage(Request $request): int
    {
        $raw = $request->query('per_page', 20);

        if (! is_numeric($raw)) {
            return 20;
        }

        return max(1, min(100, (int) $raw));
    }
}