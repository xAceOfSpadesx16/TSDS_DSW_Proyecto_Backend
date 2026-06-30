<?php

use App\Modules\Auth\AuthController;
use App\Modules\History\HistoryController;
use App\Modules\Legal\LegalController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Define aquí las rutas de la API RESTful. Se montan bajo el prefijo
| `/api` configurado en bootstrap/app.php ->withRouting(api: ...).
|
| Convenciones aplicadas:
|   - Las rutas que requieren sesión usan `auth:api` (driver JWT).
|   - Las rutas públicas (registro, login, disclaimer) no llevan
|     middleware de autenticación.
|   - El health check vive en /api/health (sin prefijo de versión).
|
*/

/**
 * Health check del backend.
 *
 * Útil para readiness probes y para que el frontend verifique
 * disponibilidad antes de pegar a la API.
 */
Route::get('/health', function (): JsonResponse {
    return response()->json([
        'status' => 'ok',
        'service' => config('app.name'),
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String(),
    ]);
});

/*
|--------------------------------------------------------------------------
| Módulo Auth
|--------------------------------------------------------------------------
|
| Rutas públicas (registro y login) y protegidas (logout, me).
| La guardia `api` usa JWT (ver config/auth.php).
|
*/
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

/*
|--------------------------------------------------------------------------
| Módulo History
|--------------------------------------------------------------------------
|
| El cliente envía los sorteos ya calculados; el backend los persiste
| asociados al usuario autenticado por JWT. user_id NUNCA se acepta
| desde el cuerpo de la petición (se descarta en CreateRequest).
|
| Convención de paths del plan:
|   POST /api/history    → store
|   GET  /api/histories  → index
|
*/
Route::middleware('auth:api')->group(function () {
    Route::post('/history', [HistoryController::class, 'store']);
    Route::get('/histories', [HistoryController::class, 'index']);
});

/*
|--------------------------------------------------------------------------
| Módulo Legal
|--------------------------------------------------------------------------
|
| Disclaimers públicos. Contenido estático por ahora (ver
| LegalService); no requiere autenticación.
|
*/
Route::get('/legal/disclaimer', [LegalController::class, 'disclaimer']);