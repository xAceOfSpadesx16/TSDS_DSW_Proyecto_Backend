<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Login es público, no requiere autenticación previa.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación del login.
     * `email` valida formato pero NO unicidad (es login, no registro).
     * `password` se mantiene opaca a validación de formato para no
     * exponer al cliente pistas sobre la política interna.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}