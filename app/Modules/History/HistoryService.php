<?php

namespace App\Modules\History;

use App\Models\User;
use App\Modules\History\Models\HistoryRecord;
use App\Modules\History\Strategies\HistoryStrategyFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Servicio de persistencia del historial.
 *
 * Encapsula toda la lógica de acceso a HistoryRecord para que el
 * controlador sea una capa delgada. Centraliza:
 *   - la asociación entre el registro y el usuario autenticado (JWT),
 *   - la validación semántica del payload/result según el módulo
 *     (delegada a la estrategia resuelta por {@see HistoryStrategyFactory}),
 *   - el filtrado por dueño (un usuario nunca ve historiales ajenos),
 *   - la paginación.
 */
class HistoryService
{
    public function __construct(
        private readonly HistoryStrategyFactory $strategies,
    ) {
    }

    /**
     * Registra una nueva entrada de historial.
     *
     * Flujo:
     *   1. Resuelve la estrategia de validación según `module_name`.
     *   2. La estrategia valida la estructura de `payload` y `result`.
     *      Si falla, lanza InvalidHistoryPayloadException (→ 422).
     *   3. Persiste el registro asociándolo al usuario autenticado.
     *
     * @param  array{module_name: string, action: string, payload: array, result: array}  $data
     *         Datos ya validados por el FormRequest. NO incluye user_id:
     *         se inyecta server-side desde el usuario autenticado.
     */
    public function store(User $user, array $data): HistoryRecord
    {
        $strategy = $this->strategies->make($data['module_name']);

        $strategy->validate($data['payload'], $data['result']);

        return HistoryRecord::create([
            'user_id' => $user->getKey(),
            'module_name' => $data['module_name'],
            'action' => $data['action'],
            'payload' => $data['payload'],
            'result' => $data['result'],
        ]);
    }

    /**
     * Lista paginada del historial del usuario autenticado, ordenado
     * descendente por creación (lo más reciente primero).
     */
    public function paginateForUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return HistoryRecord::query()
            ->where('user_id', $user->getKey())
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}