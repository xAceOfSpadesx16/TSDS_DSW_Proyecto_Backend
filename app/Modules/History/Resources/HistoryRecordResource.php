<?php

namespace App\Modules\History\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializador estándar del registro de historial.
 *
 * Expone el UUID `id` (no el autoincrement legacy) y los timestamps
 * para que el frontend pueda mostrar "hace X minutos" sin pedir más
 * requests.
 */
class HistoryRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'module_name' => $this->module_name,
            'action' => $this->action,
            'payload' => $this->payload,
            'result' => $this->result,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}