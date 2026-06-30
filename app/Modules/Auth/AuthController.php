<?php

namespace App\Modules\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\RegisterRequest;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * Controlador delgado de autenticación.
 *
 * Toda la lógica de negocio vive en {@see AuthService}. Este
 * controlador solo:
 *   1) recibe la request validada por FormRequest,
 *   2) delega al servicio,
 *   3) traduce el resultado a una respuesta HTTP JSON.
 */
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    /**
     * POST /api/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $payload = $this->auth->register($request->validated());
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'No se pudo generar el token de autenticación.',
            ], 500);
        }

        return response()->json($payload, 201);
    }

    /**
     * POST /api/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $payload = $this->auth->login($request->validated());
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'No se pudo generar el token de autenticación.',
            ], 500);
        }

        if ($payload === null) {
            return response()->json([
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

        return response()->json($payload);
    }

    /**
     * POST /api/auth/logout (auth:api)
     */
    public function logout(): JsonResponse
    {
        try {
            $this->auth->logout();
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'No se pudo invalidar el token.',
            ], 500);
        }

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    /**
     * GET /api/auth/me (auth:api)
     */
    public function me(): JsonResponse
    {
        try {
            $user = $this->auth->me();
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Token inválido o expirado.',
            ], 401);
        }

        if ($user === null) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        return response()->json([
            'user' => $user,
        ]);
    }
}