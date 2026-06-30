<?php

namespace App\Modules\History\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro del historial enviado por el cliente.
 *
 * El cliente calcula el sorteo en el frontend (dados, ruleta, equipos,
 * sorteos ponderados) y persiste aquí los parámetros de entrada y el
 * resultado, asociándolo opcionalmente al usuario autenticado por JWT.
 *
 * El identificador primario es UUID v4 para evitar enumeración y
 * facilitar la escalabilidad horizontal (sin autoincrement).
 *
 * @property string $id
 * @property int|null $user_id
 * @property string $module_name
 * @property string $action
 * @property array $payload
 * @property array $result
 */
class HistoryRecord extends Model
{
    use HasUuids;

    protected $table = 'history_records';

    /**
     * Asignación masiva: el id se omite porque lo gestiona HasUuids,
     * y los timestamps los llena Eloquent automáticamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'module_name',
        'action',
        'payload',
        'result',
    ];

    /**
     * Casts: payload y result son JSON estructurado (arrays asociativos).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'result' => 'array',
        ];
    }

    /**
     * Relación con el usuario dueño del registro (si está autenticado).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}