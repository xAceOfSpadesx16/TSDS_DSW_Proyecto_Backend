<?php

namespace App\Modules\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Servicio de autenticación stateless con JWT.
 *
 * Esta clase concentra toda la lógica de negocio del módulo Auth
 * (registro, login, logout, me) para que el controlador quede como
 * una capa delgada de delegación. Las dependencias (Hash, JWTAuth)
 * se acceden vía Facades, lo cual es desacoplable en tests con
 * `Auth::shouldUse()` y mockeos.
 */
class AuthService
{
    /**
     * Crea un nuevo usuario y emite su JWT inicial.
     *
     * @param  array{name: string, email: string, password: string}  $data
     * @return array{user: User, token: string, token_type: string, expires_in: int}
     *
     * @throws \Tymon\JWTAuth\Exceptions\JWTException
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return $this->respondWithToken(JWTAuth::fromUser($user), $user);
    }

    /**
     * Autentica credenciales y emite JWT.
     *
     * @param  array{email: string, password: string}  $credentials
     * @return array{user: User, token: string, token_type: string, expires_in: int}|null
     *         Retorna null cuando las credenciales son inválidas.
     *
     * @throws \Tymon\JWTAuth\Exceptions\JWTException
     */
    public function login(array $credentials): ?array
    {
        $token = Auth::guard('api')->attempt($credentials);

        if (! $token) {
            return null;
        }

        return $this->respondWithToken($token, Auth::guard('api')->user());
    }

    /**
     * Invalida el token actual (logout real).
     *
     * @throws \Tymon\JWTAuth\Exceptions\JWTException
     */
    public function logout(): void
    {
        Auth::guard('api')->logout();
    }

    /**
     * Retorna el usuario autenticado por el token Bearer.
     *
     * @throws \Tymon\JWTAuth\Exceptions\JWTException
     * @throws \Tymon\JWTAuth\Exceptions\TokenInvalidException
     */
    public function me(): ?User
    {
        return Auth::guard('api')->user();
    }

    /**
     * Empaqueta el token con metadatos estándar del contrato OAuth2/JWT.
     *
     * Acepta el User explícitamente para que register() no dependa de
     * que el guard ya tenga la sesión hidratada (fromUser() emite
     * token sin autenticar el guard).
     *
     * @return array{user: User, token: string, token_type: string, expires_in: int}
     */
    private function respondWithToken(string $token, User $user): array
    {
        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ];
    }
}