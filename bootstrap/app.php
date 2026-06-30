<?php

use App\Modules\History\Exceptions\InvalidHistoryPayloadException;
use App\Modules\History\Exceptions\UnsupportedModuleException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Para rutas /api/* NO redirigir a una página de login cuando
        // el usuario es guest: el Authenticate middleware llamaría a
        // `route('login')` (inexistente) y devolvería 500 antes de
        // lanzar AuthenticationException. Devolviendo null permitimos
        // que se lance AuthenticationException y el render de la API
        // lo convierta en 401 JSON.
        $middleware->redirectGuestsTo(function (Request $request) {
            return $request->is('api/*') ? null : '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Forzar respuesta JSON para cualquier ruta bajo /api/*.
        // Garantiza que el contrato sea siempre JSON, incluso si la
        // petición llega sin Accept: application/json.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // AuthenticationException -> 401 JSON.
        // Sin esto, el middleware Authenticate intenta redirigir a la
        // ruta `login` (inexistente en esta API) y termina en 500.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'No autenticado.',
                ], 401);
            }
        });

        // ValidationException -> 422 con estructura estandarizada.
        // Incluye los errores por campo para que el frontend pueda
        // mostrarlos inline junto a los inputs.
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // InvalidHistoryPayloadException -> 422 con errores por campo.
        // Lanzada por las estrategias del módulo History cuando el
        // shape interno de payload/result no cumple el contrato del
        // módulo. Compartimos el formato de ValidationException para
        // que el cliente pueda renderizar los errores inline.
        $exceptions->render(function (InvalidHistoryPayloadException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // UnsupportedModuleException -> 422.
        // El cliente envió un `module_name` que la factory no
        // reconoce. Sigue siendo "input no procesable", por lo que
        // 422 es coherente con el resto de validaciones de la API.
        $exceptions->render(function (UnsupportedModuleException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }
        });

        // ModelNotFoundException (findOrFail, firstOrFail) -> 404.
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Recurso no encontrado.',
                ], 404);
            }
        });

        // NotFoundHttpException (ruta inexistente) -> 404.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Endpoint no encontrado.',
                ], 404);
            }
        });

        // Cualquier otra Throwable -> 500 JSON estructurado en no-debug.
        // En debug se delega al renderer nativo (trace completo).
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') && ! config('app.debug')) {
                $payload = [
                    'message' => 'Error interno del servidor.',
                ];

                // Si la excepción expone getStatusCode (HttpException y
                // derivadas) y es 4xx, dejamos pasar el mensaje original
                // porque suele aportar contexto útil al cliente.
                if (method_exists($e, 'getStatusCode')) {
                    $status = $e->getStatusCode();
                    if ($status >= 400 && $status < 500) {
                        $payload['message'] = $e->getMessage() ?: $payload['message'];
                        return response()->json($payload, $status);
                    }
                }

                return response()->json($payload, 500);
            }
        });
    })->create();